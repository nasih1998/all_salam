<template>
  <div class="main-content">
    <breadcumb :page="$t('ProductQuantityAlerts')" :folder="$t('Reports')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <vue-good-table
      v-if="!isLoading"
      mode="remote"
      :columns="columns"
      :totalRows="totalRows"
      :rows="products"
      @on-page-change="onPageChange"
      @on-per-page-change="onPerPageChange"
      :pagination-options="{
        enabled: true,
        mode: 'records',
        nextLabel: 'next',
        prevLabel: 'prev',
      }"
      styleClass="table-hover tableOne vgt-table"
    >
      <div slot="table-actions" class="mt-2 mb-3 quantity_alert_warehouse">
        <!-- warehouse -->
        <b-form-group :label="$t('warehouse')">
          <v-select
            @input="Selected_Warehouse"
            v-model="warehouse_id"
            :reduce="label => label.value"
            :placeholder="$t('Choose_Warehouse')"
            :options="warehouses.map(warehouses => ({label: warehouses.name, value: warehouses.id}))"
          />
        </b-form-group>
      </div>

      <div slot="table-actions" class="mt-2 mb-3">
        
          <b-button @click="stock_alert_PDF()" size="sm" variant="outline-success ripple m-1">
            <i class="i-File-Copy"></i> PDF
          </b-button>
           <vue-excel-xlsx
              class="btn btn-sm btn-outline-danger ripple m-1"
              :data="products"
              :columns="columns"
              :file-name="'Alerts_report'"
              :file-type="'xlsx'"
              :sheet-name="'Alerts_report'"
              >
              <i class="i-File-Excel"></i> EXCEL
          </vue-excel-xlsx>
        </div>

      <template slot="table-row" slot-scope="props">
        <div v-if="props.column.field == 'stock_alert'">
          <span class="badge badge-outline-danger">{{props.row.stock_alert}}</span>
        </div>
      </template>
    </vue-good-table>
    <!-- </b-card> -->
  </div>
</template>

<script>
import NProgress from "nprogress";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

export default {
  metaInfo: {
    title: "Products Alert"
  },
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
      totalRows: "",
      products: [],
      warehouses: [],
      warehouse_id: ""
    };
  },

  computed: {
    columns() {
      return [
        {
          label: this.$t("ProductCode"),
          field: "code",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("ProductName"),
          field: "name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Quantity"),
          field: "quantity",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("AlertQuantity"),
          field: "stock_alert",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        }
      ];
    }
  },

  methods: {

      //----------------------------------- Sales PDF ------------------------------\\
    stock_alert_PDF() {
      var self = this;
      let pdf = new jsPDF("p", "pt");

      const fontPath = "/fonts/Vazirmatn-Bold.ttf";
      try {
        pdf.addFont(fontPath, "Vazirmatn", "normal");
        pdf.addFont(fontPath, "Vazirmatn", "bold");
      } catch(e) {}
      pdf.setFont("Vazirmatn", "normal");

      const headers = [
        self.$t("ProductCode"),
        self.$t("ProductName"),
        self.$t("warehouse"),
        self.$t("Quantity"),
        self.$t("AlertQuantity")
      ];

      const body = (self.products || []).map(product => ([
        product.code,
        product.name,
        product.warehouse,
        product.quantity,
        product.stock_alert
      ]));

      // Calculate totals
      let totalquantity = self.products.reduce((sum, product) => sum + parseFloat(product.quantity || 0), 0);
      let totalstock_alert = self.products.reduce((sum, product) => sum + parseFloat(product.stock_alert || 0), 0);

      const footer = [[
        self.$t("Total"),
        '',
        '',
        totalquantity.toFixed(2),
        totalstock_alert.toFixed(2)
      ]];

      const marginX = 40;
      const rtl =
        (self.$i18n && ['ar','fa','ur','he'].includes(self.$i18n.locale)) ||
        (typeof document !== 'undefined' && document.documentElement.dir === 'rtl');

      autoTable(pdf, {
        head: [headers],
        body: body,
        foot: footer,
        startY: 110,
        theme: 'striped',
        margin: { left: marginX, right: marginX },
        styles: { font: 'Vazirmatn', fontSize: 9, cellPadding: 4, halign: rtl ? 'right' : 'left', textColor: 33 },
        headStyles: { font: 'Vazirmatn', fontStyle: 'bold', fillColor: [26,86,219], textColor: 255 },
        alternateRowStyles: { fillColor: [245,247,250] },
        footStyles: { font: 'Vazirmatn', fontStyle: 'bold', fillColor: [26,86,219], textColor: 255 },
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
          const title = 'Stock Alert report';
          rtl ? pdf.text(title, pageW - marginX, 38, { align: 'right' })
              : pdf.text(title, marginX, 38);

          // Reset text color
          pdf.setTextColor(33);

          // Footer page numbers
          pdf.setFontSize(8);
          const pn = `${d.pageNumber} / ${pdf.internal.getNumberOfPages()}`;
          rtl ? pdf.text(pn, marginX, pageH - 14, { align: 'left' })
              : pdf.text(pn, pageW - marginX, pageH - 14, { align: 'right' });
        },
        styles: {
          font: "Vazirmatn", 
          halign: rtl ? 'right' : 'left', 
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

      pdf.save("Stock_alert_report.pdf");

    },


    //---- update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Stock_Alerts(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Stock_Alerts(1);
      }
    },

    //---------------------- Event Select Warehouse ------------------------------\\
    Selected_Warehouse(value) {
      if (value === null) {
        this.warehouse_id = "";
      }
      this.Get_Stock_Alerts(1);
    },

    //----------------------------- Get Stock Alerts-------------------\\
    Get_Stock_Alerts(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "get_products_stock_alerts?page=" +
            page +
            "&warehouse=" +
            this.warehouse_id +
            "&limit=" +
            this.limit
        )
        .then(response => {
          this.products = response.data.products.data;
          this.warehouses = response.data.warehouses;
          this.totalRows = response.data.products.total;
          // Complete the animation of theprogress bar.
          NProgress.done();
          this.isLoading = false;
        })
        .catch(response => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    }
  }, //end Methods

  //----------------------------- Created function------------------- \\

  created: function() {
    this.Get_Stock_Alerts(1);
  }
};
</script>