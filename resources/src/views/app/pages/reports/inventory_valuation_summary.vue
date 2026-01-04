<template>
  <div class="main-content">
    <breadcumb :page="$t('Inventory_Valuation')" :folder="$t('Reports')" />

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <b-card class="wrapper" v-else>
     
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="reports"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-search="onSearch"
        :search-options="{ placeholder: $t('Search_this_table'), enabled: true }"
        :pagination-options="{ enabled: true, mode: 'records', nextLabel: 'next', prevLabel: 'prev' }"
        styleClass="tableOne table-hover vgt-table mt-3"
      >

        <!-- Filters -->
        <div slot="table-actions" class="mt-2 mb-3 quantity_alert_warehouse">
          <b-form-group :label="$t('warehouse')">
            <v-select
              @input="Selected_Warehouse"
              v-model="warehouse_id"
              :reduce="label => label.value"
              :placeholder="$t('Choose_Warehouse')"
              :options="[
                { label: $t('All_Warehouses'), value: 0 },
                ...warehouses.map(w => ({ label: w.name, value: w.id }))
              ]"
            />
          </b-form-group>
        </div>

        <div slot="table-actions" class="mt-2 mb-3">
          <b-button @click="stock_report_PDF()" size="sm" variant="outline-success ripple m-1">
            <i class="i-File-Copy"></i> PDF
          </b-button>
        </div>

        <!-- Safe cell rendering (no v-html) -->
        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field === 'variant_name'" class="pre">{{ props.row.variant_name }}</span>
          <span v-else-if="props.column.field === 'stock_hand'" class="pre">{{ props.row.stock_hand }}</span>
          <span v-else-if="props.column.field === 'inventory_value'" class="pre">{{ props.row.inventory_value }}</span>
          <span v-else>
            {{ props.formattedRow[props.column.field] }}
          </span>
        </template>
      </vue-good-table>
    </b-card>
  </div>
</template>

<script>
import NProgress from "nprogress";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

export default {
  metaInfo: { title: "Inventory Valuation Summary" },

  data() {
    return {
      isLoading: true,
      serverParams: { sort: { field: "id", type: "desc" }, page: 1, perPage: 10 },
      limit: "10",
      search: "",
      totalRows: "",
      reports: [],
      warehouses: [],
      warehouse_id: 0
    };
  },

  computed: {
    columns() {
      return [
        { label: this.$t("ITEM_NAME"), field: "name", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("SKU"),       field: "code", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("Variant_NAME"), field: "variant_name", tdClass: "text-left pre", thClass: "text-left", sortable: false },
        { label: this.$t("STOCK_ON_HAND"), field: "stock_hand", tdClass: "text-left pre", thClass: "text-left", sortable: false },
        { label: this.$t("ASSET_VALUE"), field: "inventory_value", tdClass: "text-left pre", thClass: "text-left", sortable: false },
      ];
    }
  },

  methods: {
    // PDF export (handles newline-delimited cells)
    stock_report_PDF() {
      const pdf = new jsPDF("p", "pt");
      const fontPath = "/fonts/Vazirmatn-Bold.ttf";
      try {
        pdf.addFont(fontPath, "Vazirmatn", "normal");
        pdf.addFont(fontPath, "Vazirmatn", "bold");
      } catch(e) { /* ignore if already added */ }
      pdf.setFont("Vazirmatn", "normal");

      const marginX = 40;
      const rtl =
        (this.$i18n && ['ar','fa','ur','he'].includes(this.$i18n.locale)) ||
        (typeof document !== 'undefined' && document.documentElement.dir === 'rtl');

      const columns = [
        { header: this.$t("ITEM_NAME"), dataKey: "name" },
        { header: this.$t("SKU"), dataKey: "code" },
        { header: this.$t("Variant_NAME"), dataKey: "variant_name" },
        { header: this.$t("STOCK_ON_HAND"), dataKey: "stock_hand" },
        { header: this.$t("ASSET_VALUE"), dataKey: "inventory_value" },
      ];

      const report_pdf = JSON.parse(JSON.stringify(this.reports));

      // totals
      let totalStock = 0;
      let totalValue = 0;

      // compute totals by summing each line split by \n
      report_pdf.forEach(item => {
        const stocks = String(item.stock_hand || '').split('\n').map(v => parseFloat(v || 0));
        const values = String(item.inventory_value || '').split('\n').map(v => parseFloat(v || 0));

        const sumStock = stocks.reduce((s, n) => s + (isNaN(n) ? 0 : n), 0);
        const sumValue = values.reduce((s, n) => s + (isNaN(n) ? 0 : n), 0);

        totalStock += sumStock;
        totalValue += sumValue;
      });

      const footer = [{
        name: this.$t("Total"),
        code: '',
        variant_name: '',
        stock_hand: totalStock.toFixed(2),
        inventory_value: totalValue.toFixed(2),
      }];

      autoTable(pdf, {
        columns,
        body: report_pdf,
        foot: footer,
        startY: 110,
        theme: 'grid',
        margin: { left: marginX, right: marginX },
        styles: { font: 'Vazirmatn', fontSize: 9, cellPadding: 4, halign: rtl ? 'right' : 'left', textColor: 33 },
        headStyles: { font: 'Vazirmatn', fontStyle: 'bold', fillColor: [26,86,219], textColor: 255 },
        footStyles: { font: 'Vazirmatn', fontStyle: 'bold', fillColor: [26,86,219], textColor: 255, halign: rtl ? 'right' : 'left' },
        // ensure numeric columns are right-aligned in all sections (by index)
        columnStyles: { 3: { halign: 'right' }, 4: { halign: 'right' } },
        didParseCell: (data) => {
          if (data.section === 'foot') {
            if (data.column.index === 3 || data.column.index === 4) {
              data.cell.styles.halign = 'right';
            } else {
              data.cell.styles.halign = rtl ? 'right' : 'left';
            }
          }
        },
        didDrawPage: (d) => {
          const pageW = pdf.internal.pageSize.getWidth();
          const pageH = pdf.internal.pageSize.getHeight();

          // Header banner
          pdf.setFillColor(26,86,219);
          pdf.rect(0, 0, pageW, 60, 'F');

          // Title
          pdf.setTextColor(255);
          pdf.setFont('Vazirmatn', 'bold');
          pdf.setFontSize(16);
          const title = 'Inventory Valuation Summary';
          rtl ? pdf.text(title, pageW - marginX, 38, { align: 'right' })
              : pdf.text(title, marginX, 38);

          // Reset text color
          pdf.setTextColor(33);
          pdf.setFontSize(9);
        },
      });

      pdf.save("Inventory_Valuation_Summary.pdf");
    },

    updateParams(newProps) { this.serverParams = Object.assign({}, this.serverParams, newProps); },

    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Stock_Report(currentPage);
      }
    },

    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Stock_Report(1);
      }
    },

    onSortChange(params) {
      this.updateParams({ sort: { type: params[0].type, field: params[0].field } });
      this.Get_Stock_Report(this.serverParams.page);
    },

    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Stock_Report(this.serverParams.page);
    },

    Selected_Warehouse(value) {
      if (value === null) this.warehouse_id = 0;
      this.Get_Stock_Report(1);
    },

    Get_Stock_Report(page) {
      NProgress.start(); NProgress.set(0.1);
      axios.get(
        "report/inventory_valuation_summary?page=" + page +
        "&SortField=" + encodeURIComponent(this.serverParams.sort.field) +
        "&SortType=" + encodeURIComponent(this.serverParams.sort.type) +
        "&warehouse_id=" + encodeURIComponent(this.warehouse_id) +
        "&search=" + encodeURIComponent(this.search || "") +
        "&limit=" + encodeURIComponent(this.limit)
      )
      .then(response => {
        this.reports    = response.data.reports;
        this.totalRows  = response.data.totalRows;
        this.warehouses = response.data.warehouses;
        NProgress.done(); this.isLoading = false;
      })
      .catch(() => {
        NProgress.done(); setTimeout(() => { this.isLoading = false; }, 500);
      });
    }
  },

  created() {
    this.Get_Stock_Report(1);
  }
};
</script>

<style scoped>
.pre { white-space: pre-line; }
</style>
