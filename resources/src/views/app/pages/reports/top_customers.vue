<template>
  <div class="main-content">
    <breadcumb :page="$t('Top_customers')" :folder="$t('Reports')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <vue-good-table
      v-if="!isLoading"
      mode="remote"
      :columns="columns"
      :totalRows="totalRows"
      :rows="customers"
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
     <div slot="table-actions" class="mt-2 mb-3">
        <b-button @click="export_PDF()" size="sm" variant="outline-success ripple m-1">
          <i class="i-File-Copy"></i> PDF
        </b-button>
      </div>

     <template slot="table-row" slot-scope="props">
      
        <div v-if="props.column.field == 'total'">
          <span>{{currentUser.currency}} {{props.row.total}}</span>
        </div>
      </template>

    </vue-good-table>
    <!-- </b-card> -->
  </div>
</template>

<script>
import NProgress from "nprogress";
import { mapGetters } from "vuex";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

export default {
  metaInfo: {
    title: "Top Customers"
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
      customers: [],
    };
  },

  computed: {
     ...mapGetters(["currentUser"]),
    columns() {
      return [
        {
          label: this.$t("Name"),
          field: "name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Phone"),
          field: "phone",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Email"),
          field: "email",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("TotalSales"),
          field: "total_sales",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
         {
          label: this.$t("TotalAmount"),
          field: "total",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
       
      ];
    }
  },

  methods: {

     //----------------------------------- Export PDF ------------------------------\\
    export_PDF() {
      var self = this;
      let pdf = new jsPDF("p", "pt");

      const fontPath = "/fonts/Vazirmatn-Bold.ttf";
      try {
        pdf.addFont(fontPath, "Vazirmatn", "normal");
        pdf.addFont(fontPath, "Vazirmatn", "bold");
      } catch(e) {}
      pdf.setFont("Vazirmatn", "normal");
      
      const headers = [
        self.$t("Name"),
        self.$t("Phone"),
        self.$t("Email"),
        self.$t("TotalSales"),
        self.$t("TotalAmount")
      ];

      const body = (self.customers || []).map(customer => ([
        customer.name,
        customer.phone,
        customer.email,
        customer.total_sales,
        customer.total
      ]));

      const marginX = 40;
      const rtl =
        (self.$i18n && ['ar','fa','ur','he'].includes(self.$i18n.locale)) ||
        (typeof document !== 'undefined' && document.documentElement.dir === 'rtl');

      autoTable(pdf, {
        head: [headers],
        body: body,
        startY: 110,
        theme: 'striped',
        margin: { left: marginX, right: marginX },
        styles: { font: 'Vazirmatn', fontSize: 9, cellPadding: 4, halign: rtl ? 'right' : 'left', textColor: 33 },
        headStyles: { font: 'Vazirmatn', fontStyle: 'bold', fillColor: [26,86,219], textColor: 255 },
        alternateRowStyles: { fillColor: [245,247,250] },
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
          const title = 'Top Customers';
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
        headStyles: {
          fillColor: [26,86,219], 
               textColor: [0, 0, 0], 
               fontStyle: "bold", 
             },

      });

      pdf.save("Top_Customers.pdf");
    },

    //---- update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_top_Customers(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_top_Customers(1);
      }
    },

    //----------------------------- Get_top_Customers-------------------\\
    Get_top_Customers(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "report/top_customers?page=" +
            page +
            "&limit=" +
            this.limit
        )
        .then(response => {
          this.customers = response.data.customers;
          this.totalRows = response.data.totalRows;
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
    this.Get_top_Customers(1);
  }
};
</script>