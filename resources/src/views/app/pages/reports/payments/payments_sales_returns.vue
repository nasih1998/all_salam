<template>
  <div class="main-content">
    <breadcumb :page="$t('payments_Sales_Return')" :folder="$t('Reports')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

      <b-col md="12" class="text-center" v-if="!isLoading">
        <date-range-picker 
          v-model="dateRange" 
          :startDate="startDate" 
          :endDate="endDate" 
           @update="Submit_filter_dateRange"
          :locale-data="locale" > 

          <template v-slot:input="picker" style="min-width: 350px;">
              {{ picker.startDate.toJSON().slice(0, 10)}} - {{ picker.endDate.toJSON().slice(0, 10)}}
          </template>        
        </date-range-picker>
      </b-col>

    <!-- Charts -->
    <b-row v-if="!isLoading">
      <b-col md="8" class="mb-3">
        <b-card class="shadow-soft border-0">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="m-0">{{$t('PaymentsOverTime')}}</h6>
            <small class="text-muted">{{ fmt(dateRange.startDate) }} → {{ fmt(dateRange.endDate) }}</small>
          </div>
          <apexchart type="line" height="320" :options="apexTimeOptions" :series="apexTimeSeries" />
        </b-card>
      </b-col>
      <b-col md="4" class="mb-3">
        <b-card class="shadow-soft border-0">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="m-0">{{$t('PaymentsByMethod')}}</h6>
            <small class="text-muted">{{$t('ByAmount')}}</small>
          </div>
          <apexchart type="bar" height="320" :options="apexMethodOptions" :series="apexMethodSeries" />
        </b-card>
      </b-col>
    </b-row>

    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="rows"
        :group-options="{
          enabled: true,
          headerPosition: 'bottom',
        }"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-search="onSearch"
        :search-options="{
        placeholder: $t('Search_this_table'),
        enabled: true,
      }"
        :pagination-options="{
        enabled: true,
        mode: 'records',
        nextLabel: 'next',
        prevLabel: 'prev',
      }"
        styleClass="table-hover tableOne vgt-table"
      >
        <div slot="table-actions" class="mt-2 mb-3">
          <b-button variant="outline-info ripple m-1" size="sm" v-b-toggle.sidebar-right>
            <i class="i-Filter-2"></i>
            {{ $t("Filter") }}
          </b-button>
          <b-button @click="Payment_PDF()" size="sm" variant="outline-success ripple m-1">
            <i class="i-File-Copy"></i> PDF
          </b-button>
          <vue-excel-xlsx
              class="btn btn-sm btn-outline-danger ripple m-1"
              :data="payments"
              :columns="columns"
              :file-name="'payments'"
              :file-type="'xlsx'"
              :sheet-name="'payments'"
              >
              <i class="i-File-Excel"></i> EXCEL
          </vue-excel-xlsx>
        </div>
      </vue-good-table>
    </b-card>

    <!-- Sidebar Filter -->
    <b-sidebar id="sidebar-right" :title="$t('Filter')" bg-variant="white" right shadow>
      <div class="px-3 py-2">
        <b-row>
         
          <!-- Reference -->
          <b-col md="12">
            <b-form-group :label="$t('Reference')">
              <b-form-input label="Reference" :placeholder="$t('Reference')" v-model="Filter_Ref"></b-form-input>
            </b-form-group>
          </b-col>

          <!-- Customers  -->
          <b-col md="12">
            <b-form-group :label="$t('Customer')">
              <v-select
                :reduce="label => label.value"
                :placeholder="$t('Choose_Customer')"
                v-model="Filter_client"
                :options="clients.map(clients => ({label: clients.name, value: clients.id}))"
              />
            </b-form-group>
          </b-col>

          <!-- Returns  -->
          <b-col md="12">
            <b-form-group :label="$t('Return')">
              <v-select
                :reduce="label => label.value"
                :placeholder="$t('PleaseSelect')"
                v-model="Filter_return"
                :options="sale_returns.map(sale_returns => ({label: sale_returns.Ref, value: sale_returns.id}))"
              />
            </b-form-group>
          </b-col>

          <!-- Payment choice -->
          <b-col md="12">
            <b-form-group :label="$t('Paymentchoice')">
              <v-select
                v-model="Filter_Reg"
                :reduce="label => label.value"
                :placeholder="$t('PleaseSelect')"
                :options="payment_methods.map(payment_methods => ({label: payment_methods.name, value: payment_methods.id}))"
              ></v-select>
            </b-form-group>
          </b-col>

          <b-col md="6" sm="12">
            <b-button
              @click="Payments_sale_returns(serverParams.page)"
              variant="primary ripple m-1"
              size="sm"
              block
            >
              <i class="i-Filter-2"></i>
              {{ $t("Filter") }}
            </b-button>
          </b-col>
          <b-col md="6" sm="12">
            <b-button @click="Reset_Filter()" variant="danger ripple m-1" size="sm" block>
              <i class="i-Power-2"></i>
              {{ $t("Reset") }}
            </b-button>
          </b-col>
        </b-row>
      </div>
    </b-sidebar>
  </div>
</template>


<script>
import NProgress from "nprogress";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import DateRangePicker from 'vue2-daterange-picker'
//you need to import the CSS manually
import 'vue2-daterange-picker/dist/vue2-daterange-picker.css'
import moment from 'moment'
import VueApexCharts from "vue-apexcharts";

export default {
  metaInfo: {
    title: "Payment Sale Returns"
  },
  components: { DateRangePicker, apexchart: VueApexCharts },
  data() {
    return {
      isLoading: true,
      serverParams: {
        sort: {
          field: "id",
          type: "desc"
        },
        page: 1,
        perPage: 10
      },
      limit: "10",
      search: "",
      totalRows: "",
      Filter_client: "",
      Filter_Ref: "",
      Filter_return: "",
      Filter_Reg: "",
      payments: [],
      clients: [],
      payment_methods:[],
      rows: [{
          payment_method: 'Total',
         
          children: [
             
          ],
      },],
      sale_returns: [],
      today_mode: true,
      startDate: "", 
      endDate: "", 
      dateRange: { 
       startDate: "", 
       endDate: "" 
      }, 
      locale:{ 
          //separator between the two ranges apply
          Label: "Apply", 
          cancelLabel: "Cancel", 
          weekLabel: "W", 
          customRangeLabel: "Custom Range", 
          daysOfWeek: moment.weekdaysMin(), 
          //array of days - see moment documenations for details 
          monthNames: moment.monthsShort(), //array of month names - see moment documenations for details 
          firstDay: 1 //ISO first day of week - see moment documenations for details
        },
    };
  },
  computed: {
    columns() {
      return [
        {
          label: this.$t("date"),
          field: "date",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Return"),
          field: "Ref_return",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Customer"),
          field: "client_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("ModePaiement"),
          field: "payment_method",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Account"),
          field: "account_name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Amount"),
          field: "montant",
          type: "decimal",
          headerField: this.sumCount,
          tdClass: "text-left",
          thClass: "text-left"
        }
      ];
    },

    // ApexCharts: time series (line)
    apexTimeOptions(){
      const map = new Map();
      (this.payments || []).forEach(p => {
        const d = p.date ? String(p.date).slice(0,10) : "";
        const amt = Number(p.montant || 0);
        if (!d) return;
        map.set(d, (map.get(d) || 0) + amt);
      });
      const dates = Array.from(map.keys()).sort();
      return {
        chart: { type: 'line', toolbar: { show: false } },
        stroke: { curve: 'smooth', width: 3 },
        dataLabels: { enabled: false },
        xaxis: { categories: dates, labels: { rotate: -45 } },
        yaxis: { labels: { formatter: (v) => {
          try { return new Intl.NumberFormat(undefined,{ notation:'compact', maximumFractionDigits:1 }).format(Number(v||0)); }
          catch { return v; }
        } } },
        tooltip: { y: { formatter: (v) => {
          try { return Number(v||0).toLocaleString(undefined, { maximumFractionDigits: 2 }); }
          catch { return v; }
        } } },
        grid: { padding: { left: 10, right: 10, top: 10, bottom: 10 } }
      };
    },
    apexTimeSeries(){
      const map = new Map();
      (this.payments || []).forEach(p => {
        const d = p.date ? String(p.date).slice(0,10) : "";
        const amt = Number(p.montant || 0);
        if (!d) return;
        map.set(d, (map.get(d) || 0) + amt);
      });
      const dates = Array.from(map.keys()).sort();
      const vals = dates.map(d => map.get(d));
      return [{ name: this.$t('Amount'), data: vals }];
    },

    // ApexCharts: by method (horizontal bar)
    apexMethodOptions(){
      const map = new Map();
      (this.payments || []).forEach(p => {
        const k = p.payment_method || this.$t('Unknown');
        map.set(k, (map.get(k) || 0) + Number(p.montant || 0));
      });
      const cats = Array.from(map.keys());
      return {
        chart: { type: 'bar', toolbar: { show: false } },
        plotOptions: { bar: { horizontal: true } },
        dataLabels: { enabled: false },
        xaxis: { categories: cats },
        tooltip: { y: { formatter: (v) => {
          try { return Number(v||0).toLocaleString(undefined, { maximumFractionDigits: 2 }); }
          catch { return v; }
        } } },
        grid: { padding: { left: 10, right: 10, top: 10, bottom: 10 } }
      };
    },
    apexMethodSeries(){
      const map = new Map();
      (this.payments || []).forEach(p => {
        const k = p.payment_method || this.$t('Unknown');
        map.set(k, (map.get(k) || 0) + Number(p.montant || 0));
      });
      const cats = Array.from(map.keys());
      const vals = cats.map(k => map.get(k));
      return [{ name: this.$t('Amount'), data: vals }];
    }
  },

  methods: {
    fmt(d){
      try { return moment(d).format('YYYY-MM-DD'); } catch(e){ return String(d || ''); }
    },

    sumCount(rowObj) {
     
    	let sum = 0;
      for (let i = 0; i < rowObj.children.length; i++) {
        sum += rowObj.children[i].montant;
      }
      return sum;
    },

    //---- update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Payments_sale_returns(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Payments_sale_returns(1);
      }
    },

    //---- Event on Sort Change
    onSortChange(params) {
      let field = "";
      if (params[0].field == "Ref_return") {
        field = "sale_return_id";
      } else {
        field = params[0].field;
      }
      this.updateParams({
        sort: {
          type: params[0].type,
          field: field
        }
      });
      this.Payments_sale_returns(this.serverParams.page);
    },
    //---- Event on Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Payments_sale_returns(this.serverParams.page);
    },

    //------ Reset Filter
    Reset_Filter() {
      this.search = "";
      this.Filter_client = "";
      this.Filter_Ref = "";
      this.Filter_return = "";
      this.Filter_Reg = "";
      this.Payments_sale_returns(this.serverParams.page);
    },

    //---------------------------------------- Set To Strings-------------------------\\
    setToStrings() {
      // Simply replaces null values with strings=''
      if (this.Filter_client === null) {
        this.Filter_client = "";
      } else if (this.Filter_return === null) {
        this.Filter_return = "";
      }
    },

    //------------------------ Payments Sale Returns PDF -----------------------\\
    Payment_PDF() {
      var self = this;
      let pdf = new jsPDF("p", "pt");

      const fontPath = "/fonts/Vazirmatn-Bold.ttf";
      pdf.addFont(fontPath, "VazirmatnBold", "bold"); 
      pdf.setFont("VazirmatnBold"); 

      let columns = [
        { header: self.$t("date"), dataKey: "date" },
        { header: self.$t("Reference"), dataKey: "Ref" },
        { header: self.$t("Return"), dataKey: "Ref_return" },
        { header: self.$t("Customer"), dataKey: "client_name" },
        { header: self.$t("ModePaiement"), dataKey: "payment_method" },
        { header: self.$t("Account"), dataKey: "account_name" },
        { header: self.$t("Amount"), dataKey: "montant" }
      ];
      
    // Calculate totals
     let totalGrandTotal = self.payments.reduce((sum, payment) => sum + parseFloat(payment.montant || 0), 0);
     
     let footer = [{
       date: self.$t("Total"),
       Ref: '',
       Ref_return: '',
       client_name: '',
       payment_method: '',
       account_name: '',
       montant: `${totalGrandTotal.toFixed(2)}`,
      
     }];


    autoTable(pdf, {
             columns: columns,
             body: self.payments,
             foot: footer,
             startY: 70,
             theme: "grid", 
             didDrawPage: (data) => {
               pdf.setFont("VazirmatnBold");
               pdf.setFontSize(18);
               pdf.text("Payments Sale Returns", 40, 25);   
             },
             styles: {
               font: "VazirmatnBold", 
               halign: "center", // 
             },
             headStyles: {
               fillColor: [26, 86, 219], 
               textColor: 255, 
               fontStyle: "bold", 
             },
             footStyles: {
               fillColor: [26, 86, 219], 
               textColor: 255, 
               fontStyle: "bold", 
             },
    });

    pdf.save("Payments_Sale_Returns.pdf");

    },

    //----------------------------- Submit Date Picker -------------------\\
    Submit_filter_dateRange() {
      var self = this;
      self.startDate =  self.dateRange.startDate.toJSON().slice(0, 10);
      self.endDate = self.dateRange.endDate.toJSON().slice(0, 10);
      self.Payments_sale_returns(1);
    },


    get_data_loaded() {
      var self = this;
      if (self.today_mode) {
        let today = new Date()

        self.startDate = today.getFullYear();
        self.endDate = new Date().toJSON().slice(0, 10);

        self.dateRange.startDate = today.getFullYear();
        self.dateRange.endDate = new Date().toJSON().slice(0, 10);
        
      }
    },


    //-------------------------------- Get All Payments Sale returns ---------------------\\
    Payments_sale_returns(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      this.setToStrings();
      this.get_data_loaded();

      axios
        .get(
          "payment/returns_sale?page=" +
            page +
            "&Ref=" +
            this.Filter_Ref +
            "&client_id=" +
            this.Filter_client +
            "&sale_return_id=" +
            this.Filter_return +
            "&payment_method_id=" +
            this.Filter_Reg +
            "&SortField=" +
            this.serverParams.sort.field +
            "&SortType=" +
            this.serverParams.sort.type +
            "&search=" +
            this.search +
            "&limit=" +
            this.limit +
            "&to=" +
            this.endDate +
            "&from=" +
            this.startDate
        )

        .then(response => {
          this.payments = response.data.payments;
          this.clients = response.data.clients;
          this.sale_returns = response.data.sale_returns;
          this.payment_methods = response.data.payment_methods;
          this.totalRows = response.data.totalRows;
          this.rows[0].children = this.payments;
          // Complete the animation of theprogress bar.
          NProgress.done();
          this.isLoading = false;
          this.today_mode = false;
        })
        .catch(response => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          setTimeout(() => {
            this.isLoading = false;
            this.today_mode = false;
          }, 500);
        });
    }
  },

  //----------------------------- Created function-------------------\\
  created: function() {
    this.Payments_sale_returns(1);
  }
};
</script>