<template>
  <div class="main-content p-2 p-md-4">
    <breadcumb :page="$t('Tax_Summary_Report')" :folder="$t('Reports')" />

    <!-- Toolbar -->
    <b-card class="toolbar-card shadow-soft mb-3 border-0">
      <div class="d-flex flex-wrap align-items-center">
        <!-- Date range (responsive trigger) -->
        <div class="filter-block date-range-filter mr-3 mb-2">
          <label class="mb-1 d-block text-muted">{{ $t('DateRange') }}</label>
          <date-range-picker
            v-model="dateRange"
            :locale-data="locale"
            :autoApply="true"
            :showDropdowns="true"
            :opens="isMobile ? 'center' : 'right'"
            :drops="'down'"
            @update="fetchReport"
          >
            <template v-slot:input="picker">
              <b-button
                variant="light"
                class="btn-pill date-btn"
                :class="{ 'w-100': isMobile }"
              >
                <i class="i-Calendar-4 mr-1"></i>
                <span class="d-none d-sm-inline">
                  {{ fmt(picker.startDate) }} — {{ fmt(picker.endDate) }}
                </span>
                <span class="d-inline d-sm-none">
                  {{ fmtShort(picker.startDate) }}–{{ fmtShort(picker.endDate) }}
                </span>
              </b-button>
            </template>
          </date-range-picker>
        </div>

        <!-- Quick ranges -->
        <div class="mr-3 mb-2">
          <label class="mb-1 d-block text-muted">{{$t('QuickRanges')}}</label>
          <div class="btn-group quick-ranges">
            <b-button size="sm" variant="outline-primary" @click="quick('7d')">7D</b-button>
            <b-button size="sm" variant="outline-primary" @click="quick('30d')">30D</b-button>
            <b-button size="sm" variant="outline-primary" @click="quick('90d')">90D</b-button>
            <b-button size="sm" variant="outline-primary" @click="quick('mtd')">{{$t('MTD')}}</b-button>
            <b-button size="sm" variant="outline-primary" @click="quick('ytd')">{{$t('YTD')}}</b-button>
          </div>
        </div>

        <div class="ml-auto mb-2">
          <b-button variant="success" class="btn-pill mr-2" @click="exportPDF">
            <i class="i-File-PDF mr-1"></i> {{$t('Export_PDF')}}
          </b-button>
          <b-button variant="primary" class="btn-pill" @click="fetchReport">
            <i class="i-Reload mr-1"></i> {{$t('Refresh')}}
          </b-button>
        </div>
      </div>
    </b-card>

    <!-- Loading -->
    <div v-if="isLoading" class="mb-4">
      <b-row>
        <b-col md="4" v-for="n in 6" :key="'skel-'+n" class="mb-3">
          <b-skeleton-img class="rounded-xl shadow-soft" height="110px" />
        </b-col>
      </b-row>
    </div>

    <!-- Content -->
    <div v-else>
      <!-- Chart -->
      <b-card class="shadow-soft border-0 mb-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h6 class="m-0">{{$t('TaxesOverTime')}}</h6>
          <small class="text-muted">{{ fmt(dateRange.startDate) }} → {{ fmt(dateRange.endDate) }}</small>
        </div>
        <apexchart type="line" height="300" :options="apexLineOptions" :series="apexLineSeries" />
      </b-card>

      <!-- Table -->
      <b-card class="shadow-soft border-0">
        <vue-good-table
          mode="remote"
          :rows="rows"
          :columns="columns"
          :totalRows="totalRows"
          styleClass="tableOne table-hover vgt-table"
          :pagination-options="{enabled:true, mode:'records'}"
          :search-options="{enabled:true, placeholder:$t('Search_this_table')}"
          @on-page-change="onPageChange"
          @on-per-page-change="onPerPageChange"
          @on-sort-change="onSortChange"
          @on-search="onSearch"
        >
          <template slot="table-row" slot-scope="p">
            <span v-if="['taxable_base','tax_collected'].includes(p.column.field)">
              {{ money(p.row[p.column.field]) }}
            </span>
            <span v-else-if="p.column.field==='effective_rate'">
              {{ formatRate(p.row.effective_rate) }}
            </span>
            <span v-else>
              {{ p.formattedRow[p.column.field] }}
            </span>
          </template>

          <!-- Footer Totals -->
          <template slot="table-actions-bottom">
            <div class="d-flex justify-content-end w-100 pt-2">
              <div class="font-weight-bold">
                {{$t('Totals')}}:
                <span class="ml-2">{{$t('Taxable_Base')}} = {{ money(totals.base) }}</span>
                <span class="ml-3">{{$t('Tax_Collected')}} = {{ money(totals.tax) }}</span>
              </div>
            </div>
          </template>
        </vue-good-table>
      </b-card>
    </div>
  </div>
</template>

<script>
import NProgress from "nprogress";
import moment from "moment";
import { mapGetters } from "vuex";

import DateRangePicker from "vue2-daterange-picker";
import "vue2-daterange-picker/dist/vue2-daterange-picker.css";

import VueApexCharts from "vue-apexcharts";

/* PDF export */
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

export default {
  metaInfo: { title: "Tax Summary Report" },
  components: { "date-range-picker": DateRangePicker, apexchart: VueApexCharts },

  data() {
    const end = new Date(); const start = new Date(); start.setDate(end.getDate() - 29);
    return {
      isLoading: true,

      dateRange: { startDate: start, endDate: end },
      locale: {
        Label: this.$t("Apply") || "Apply",
        cancelLabel: this.$t("Cancel") || "Cancel",
        weekLabel: "W",
        customRangeLabel: this.$t("CustomRange") || "Custom Range",
        daysOfWeek: moment.weekdaysMin(),
        monthNames: moment.monthsShort(),
        firstDay: 1
      },

      isMobile: false,

      // table state
      serverParams: { page: 1, perPage: 10, sort: { field: "date_time", type: "desc" } },
      limit: 10,
      search: "",
      totalRows: 0,
      rows: [],
      totals: { base: 0, tax: 0 },

      // chart
      timeseries: [] // [{ d, taxable_base, tax_collected }]
    };
  },

  computed: {
    ...mapGetters(["currentUser"]),
    currency(){ return (this.currentUser && this.currentUser.currency) || "USD"; },

    columns() {
      return [
        { label: this.$t('ID'),            field:'sale_id',       sortable:true },
        { label: this.$t('date'),          field:'date_time',     sortable:true },
        { label: this.$t('User'),          field:'user_name',     sortable:true, tdClass:'text-left', thClass:'text-left' },
        { label: this.$t('Taxable_Base'),  field:'taxable_base',  type:'number', sortable:true },
        { label: this.$t('Tax_Collected'), field:'tax_collected', type:'number', sortable:true },
        { label: this.$t('Effective_Rate')+' %', field:'effective_rate', type:'number', sortable:true },
      ];
    },

    // ApexCharts options/series
    apexLineOptions(){
      const dates = this.timeseries.map(x => x.d);
      return {
        chart: { type: 'line', toolbar: { show: false } },
        stroke: { curve: 'smooth', width: 3 },
        dataLabels: { enabled: false },
        xaxis: { categories: dates, labels: { rotate: -45 } },
        yaxis: { labels: { formatter: (v) => {
          try { return new Intl.NumberFormat(undefined,{notation:'compact',maximumFractionDigits:1}).format(Number(v||0)); }
          catch { return v; }
        } } },
        tooltip: { y: { formatter: (v) => {
          try { return new Intl.NumberFormat(undefined,{style:'currency',currency:this.currency}).format(Number(v||0)); }
          catch { return `${this.currency} ${(Number(v||0)).toLocaleString()}`; }
        } } },
        legend: { show: true },
        grid: { padding: { left: 10, right: 10, top: 10, bottom: 10 } }
      };
    },
    apexLineSeries(){
      const base = this.timeseries.map(x => Number(x.taxable_base || 0));
      const tax  = this.timeseries.map(x => Number(x.tax_collected || 0));
      return [
        { name: this.$t('Taxable_Base'),  data: base },
        { name: this.$t('Tax_Collected'), data: tax  }
      ];
    }
  },

  methods: {
    // responsiveness
    handleResize(){ this.isMobile = window.innerWidth < 576; },

    // formatting
    fmt(d){ return moment(d).format('YYYY-MM-DD'); },
    fmtShort(d){ return moment(d).format('MMM D'); },
    formatRate(v){ if(v === null || v === undefined) return '—'; const n = Number(v); return isNaN(n) ? '—' : n.toFixed(2); },
    money(v){
      try { return new Intl.NumberFormat(undefined,{style:'currency',currency:this.currency}).format(Number(v||0)); }
      catch { return `${this.currency} ${(Number(v||0)).toLocaleString()}`; }
    },

    // quick ranges
    quick(kind){
      const now = moment(); let s, e = now.clone();
      if(kind==='7d')  s = now.clone().subtract(6,'days');
      if(kind==='30d') s = now.clone().subtract(29,'days');
      if(kind==='90d') s = now.clone().subtract(89,'days');
      if(kind==='mtd') s = now.clone().startOf('month');
      if(kind==='ytd') s = now.clone().startOf('year');
      this.dateRange = { startDate: s.toDate(), endDate: e.toDate() };
      this.fetchReport();
    },

    // table events
    onPageChange({ currentPage }) { this.serverParams.page = currentPage; this.fetchReport(); },
    onPerPageChange({ currentPerPage }) { this.serverParams.perPage = currentPerPage; this.limit = currentPerPage; this.serverParams.page = 1; this.fetchReport(); },
    onSortChange(params){ if (params && params[0]) this.serverParams.sort = params[0]; this.fetchReport(); },
    onSearch(v){ this.search = v.searchTerm || ''; this.fetchReport(); },

    // Export PDF (RTL + Vazirmatn + safe totals)
    exportPDF() {
      // Normalize rows: support either [{children:[]}] or a flat array
      const items =
        Array.isArray(this.rows?.[0]?.children) && this.rows.length === 1
          ? this.rows[0].children
          : (this.rows || []);

      // Totals (fallback if this.totals is missing)
      const tBase = (this.totals && typeof this.totals.base === 'number')
        ? this.totals.base
        : items.reduce((a,b)=> a + Number(b.taxable_base || 0), 0);

      const tTax  = (this.totals && typeof this.totals.tax === 'number')
        ? this.totals.tax
        : items.reduce((a,b)=> a + Number(b.tax_collected || 0), 0);

      // Overall effective rate (optional)
      const overallRate = tBase > 0 ? (tTax / tBase) * 100 : null;
      const fmtRate = (v) => this.formatRate ? this.formatRate(v) : (v == null ? '' : `${Number(v).toFixed(2)}%`);

      const doc = new jsPDF({ orientation:'portrait', unit:'pt', format:'a4' });
      const pageW = doc.internal.pageSize.getWidth();
      const marginX = 40;

      // Use your single Vazirmatn-Bold for normal + bold
      const fontPath = "/fonts/Vazirmatn-Bold.ttf";
      try {
        doc.addFont(fontPath, "Vazirmatn", "normal");
        doc.addFont(fontPath, "Vazirmatn", "bold");
      } catch(_) { /* ignore if already added */ }
      doc.setFont("Vazirmatn", "normal");

      // RTL?
      const rtl =
        (this.$i18n && ['ar','fa','ur','he'].includes(this.$i18n.locale)) ||
        (typeof document !== 'undefined' && document.documentElement.dir === 'rtl');

      const title = 'Tax Summary Report';
      const range = `${this.fmt(this.dateRange.startDate)} — ${this.fmt(this.dateRange.endDate)}`;

      // Header
      doc.setFont("Vazirmatn", "bold"); doc.setFontSize(14);
      rtl ? doc.text(title, pageW - marginX, 40, { align:'right' })
          : doc.text(title, marginX, 40);

      doc.setFont("Vazirmatn", "normal"); doc.setFontSize(10);
      rtl ? doc.text(range, pageW - marginX, 58, { align:'right' })
          : doc.text(range, marginX, 58);

      // Table
      const head = [[
        this.$t('ID'),
        this.$t('date'),
        this.$t('User'),
        this.$t('Taxable_Base'),
        this.$t('Tax_Collected'),
        this.$t('Effective_Rate') + ' %'
      ]];

      const body = items.map(r => ([
        r.sale_id ?? '',
        r.date_time ?? '',
        r.user_name ?? '',
        Number(r.taxable_base  || 0).toFixed(2),
        Number(r.tax_collected || 0).toFixed(2),
        fmtRate(r.effective_rate)
      ]));

      autoTable(doc, {
        startY: 80,
        head, body,
        styles: {
          font: "Vazirmatn",
          fontSize: 9,
          cellPadding: 6,
          halign: rtl ? 'right' : 'left'
        },
        headStyles: {
          font: "Vazirmatn",
          fontStyle: "bold",
          fillColor: [26,86,219],
          textColor: 255,
          halign: rtl ? 'right' : 'left'
        },
        columnStyles: {
          3: { halign:'right' }, // Taxable base
          4: { halign:'right' }, // Tax collected
          5: { halign:'right' }, // Effective rate
        },
        foot: [[
          { content: this.$t('Totals'), styles:{ font: 'Vazirmatn', fontStyle:'bold', halign: rtl ? 'right' : 'left' } },
          '', '',
          { content: this.money ? this.money(tBase) : Number(tBase).toFixed(2), styles:{ halign:'right', fontStyle:'bold' } },
          { content: this.money ? this.money(tTax)  : Number(tTax).toFixed(2),  styles:{ halign:'right', fontStyle:'bold' } },
          { content: overallRate != null ? fmtRate(overallRate) : '', styles:{ halign:'right', fontStyle:'bold' } }
        ]],
        margin: { left: marginX, right: marginX }
      });

      doc.save(`tax-summary_${this.fmt(this.dateRange.startDate)}_${this.fmt(this.dateRange.endDate)}.pdf`);
    },


    // data
    fetchReport(){
      NProgress.start(); NProgress.set(0.1); this.isLoading = true;

      const qs = new URLSearchParams({
        from: this.fmt(this.dateRange.startDate),
        to:   this.fmt(this.dateRange.endDate),
        page: String(this.serverParams.page),
        limit: String(this.serverParams.perPage || this.limit),
        SortField: this.serverParams.sort?.field || 'date_time',
        SortType:  this.serverParams.sort?.type || 'desc',
        search: this.search || ''
      }).toString();

      axios.get(`report/tax_summary?${qs}`)
        .then(({data})=>{
          this.rows = Array.isArray(data.report) ? data.report : [];
          this.totalRows = Number(data.totalRows || 0);
          const t = data.totals || { base:0, tax:0 };
          this.totals = { base: Number(t.base||0), tax: Number(t.tax||0) };
          this.timeseries = Array.isArray(data.timeseries) ? data.timeseries : [];
          this.isLoading = false; NProgress.done();
        })
        .catch(()=>{ this.isLoading = false; NProgress.done(); });
    }
  },

  mounted(){
    this.handleResize();
    window.addEventListener('resize', this.handleResize);
  },
  beforeDestroy(){
    window.removeEventListener('resize', this.handleResize);
  },
  created(){ this.fetchReport(); }
};
</script>

<style scoped>
.rounded-xl { border-radius:1rem; }
.shadow-soft { box-shadow:0 12px 24px rgba(0,0,0,.06), 0 2px 6px rgba(0,0,0,.05); }
.toolbar-card { background:#fff; }
.btn-pill { border-radius:999px; }

/* date-range responsiveness */
.date-range-filter { min-width: 240px; }
@media (max-width: 575.98px) {
  .date-range-filter { width: 100%; }
  .date-btn { justify-content: center; }
  .daterangepicker {
    left: 0 !important; right: 0 !important; margin: 0 !important;
    width: 100vw !important; max-width: 100vw !important;
  }
  .daterangepicker .ranges, .daterangepicker .drp-calendar {
    float: none !important; width: 100% !important;
  }

  /* Wrap quick range buttons into two columns */
  .quick-ranges { display:flex !important; flex-wrap:wrap; width:100%; }
  .quick-ranges .btn { flex:1 1 calc(50% - 6px); margin-bottom:6px; }
}
</style>
