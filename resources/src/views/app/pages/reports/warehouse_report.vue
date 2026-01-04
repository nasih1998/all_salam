<template>
  <div class="main-content">
    <breadcumb :page="$t('Warehouse_report')" :folder="$t('Reports')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <b-row class="justify-content-center mb-5" v-if="!isLoading">
      <!-- warehouse -->
      <b-col lg="3" md="6" sm="12">
        <b-form-group :label="$t('warehouse')">
          <v-select
            @input="Selected_Warehouse"
            v-model="Filter_warehouse"
            :reduce="label => label.value"
            :placeholder="$t('All_Warehouses')"
            :options="warehouses.map(warehouses => ({label: warehouses.name, value: warehouses.id}))"
          />
        </b-form-group>
      </b-col>
    </b-row>

    <b-row v-if="!isLoading">
      <b-col lg="3" md="6" sm="12" class="mb-3">
        <StatTile icon="i-Full-Cart" :label="$t('Sales')" :value="total.sales" theme="blue" />
      </b-col>
      <b-col lg="3" md="6" sm="12" class="mb-3">
        <StatTile icon="i-Checkout-Basket" :label="$t('Purchases')" :value="total.purchases" theme="teal" />
      </b-col>
      <b-col lg="3" md="6" sm="12" class="mb-3">
        <StatTile icon="i-Turn-Left" :label="$t('PurchasesReturn')" :value="total.ReturnPurchase" theme="orange" />
      </b-col>
      <b-col lg="3" md="6" sm="12" class="mb-3">
        <StatTile icon="i-Turn-Right" :label="$t('SalesReturn')" :value="total.ReturnSale" theme="purple" />
      </b-col>
    </b-row>

    <b-row v-if="!isLoading">
      <b-col md="12">
        <b-card no-body class="card mb-30" header-bg-variant="transparent ">
          <b-tabs active-nav-item-class="nav nav-tabs" content-class="mt-3">
            <!-- Quotations Table -->
            <b-tab :title="$t('Quotations')">
              <vue-good-table
                mode="remote"
                :columns="columns_quotations"
                :totalRows="totalRows_quotations"
                :rows="quotations"
                @on-page-change="PageChangeQuotation"
                @on-per-page-change="onPerPageChangeQuotation"
                @on-search="onSearch_Quotations"
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
                styleClass="order-table vgt-table mt-2"
              >
               <div slot="table-actions" class="mt-2 mb-3">
                <b-button @click="Quotation_PDF()" size="sm" variant="outline-success ripple m-1">
                  <i class="i-File-Copy"></i> PDF
                </b-button>
              </div>
                <template slot="table-row" slot-scope="props">
                  <div v-if="props.column.field == 'statut'">
                    <span
                      v-if="props.row.statut == 'sent'"
                      class="badge badge-outline-success"
                    >{{$t('Sent')}}</span>
                    <span v-else class="badge badge-outline-info">{{$t('Pending')}}</span>
                  </div>
                   <div v-else-if="props.column.field == 'Ref'">
                    <router-link
                      :to="'/app/quotations/detail/'+props.row.id"
                    >
                      <span class="ul-btn__text ml-1">{{props.row.Ref}}</span>
                    </router-link>
                  </div>
                </template>
              </vue-good-table>
            </b-tab>

            <!-- Sales Table -->
            <b-tab :title="$t('Sales')">
              <vue-good-table
                mode="remote"
                :columns="columns_sales"
                :totalRows="totalRows_sales"
                :rows="sales"
                @on-page-change="PageChangeSales"
                @on-per-page-change="onPerPageChangeSales"
                @on-search="onSearch_Sales"
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
                styleClass="order-table vgt-table mt-2"
              >
               <div slot="table-actions" class="mt-2 mb-3">
                <b-button @click="Sales_PDF()" size="sm" variant="outline-success ripple m-1">
                  <i class="i-File-Copy"></i> PDF
                </b-button>
              </div>
                <template slot="table-row" slot-scope="props">
                  <div v-if="props.column.field == 'statut'">
                    <span
                      v-if="props.row.statut == 'completed'"
                      class="badge badge-outline-success"
                    >{{$t('complete')}}</span>
                    <span
                      v-else-if="props.row.statut == 'pending'"
                      class="badge badge-outline-info"
                    >{{$t('Pending')}}</span>
                    <span v-else class="badge badge-outline-warning">{{$t('Ordered')}}</span>
                  </div>
                  <div v-else-if="props.column.field == 'payment_status'">
                    <span
                      v-if="props.row.payment_status == 'paid'"
                      class="badge badge-outline-success"
                    >{{$t('Paid')}}</span>
                    <span
                      v-else-if="props.row.payment_status == 'partial'"
                      class="badge badge-outline-primary"
                    >{{$t('partial')}}</span>
                    <span v-else class="badge badge-outline-warning">{{$t('Unpaid')}}</span>
                  </div>
                   <div v-else-if="props.column.field == 'shipping_status'">
                  <span
                    v-if="props.row.shipping_status == 'ordered'"
                    class="badge badge-outline-warning"
                  >{{$t('Ordered')}}</span>

                  <span
                    v-else-if="props.row.shipping_status == 'packed'"
                    class="badge badge-outline-info"
                  >{{$t('Packed')}}</span>

                  <span
                    v-else-if="props.row.shipping_status == 'shipped'"
                    class="badge badge-outline-secondary"
                  >{{$t('Shipped')}}</span>

                  <span
                    v-else-if="props.row.shipping_status == 'delivered'"
                    class="badge badge-outline-success"
                  >{{$t('Delivered')}}</span>

                  <span v-else-if="props.row.shipping_status == 'cancelled'" class="badge badge-outline-danger">{{$t('Cancelled')}}</span>
                </div>
                   <div v-else-if="props.column.field == 'Ref'">
                    <router-link
                      :to="'/app/sales/detail/'+props.row.id"
                    >
                      <span class="ul-btn__text ml-1">{{props.row.Ref}}</span>
                    </router-link>
                  </div>
                </template>
              </vue-good-table>
            </b-tab>

            <!-- Returns Sale Table -->
            <b-tab :title="$t('SalesReturn')">
              <vue-good-table
                mode="remote"
                :columns="columns_returns_sale"
                :totalRows="totalRows_Return_sale"
                :rows="returns_sale"
                @on-page-change="PageChangeReturn_Customer"
                @on-per-page-change="onPerPageChangeReturn_Sale"
                :pagination-options="{
                    enabled: true,
                    mode: 'records',
                    nextLabel: 'next',
                    prevLabel: 'prev',
                  }"
                @on-search="onSearch_Return_Sale"
                :search-options="{
                    placeholder: $t('Search_this_table'),
                    enabled: true,
                }"
                styleClass="order-table vgt-table mt-2"
              >
               <div slot="table-actions" class="mt-2 mb-3">
                <b-button @click="Sale_Return_PDF()" size="sm" variant="outline-success ripple m-1">
                  <i class="i-File-Copy"></i> PDF
                </b-button>
              </div>
                <template slot="table-row" slot-scope="props">
                  <div v-if="props.column.field == 'statut'">
                    <span
                      v-if="props.row.statut == 'received'"
                      class="badge badge-outline-success"
                    >{{$t('Received')}}</span>
                    <span v-else class="badge badge-outline-info">{{$t('Pending')}}</span>
                  </div>

                  <div v-else-if="props.column.field == 'payment_status'">
                    <span
                      v-if="props.row.payment_status == 'paid'"
                      class="badge badge-outline-success"
                    >{{$t('Paid')}}</span>
                    <span
                      v-else-if="props.row.payment_status == 'partial'"
                      class="badge badge-outline-primary"
                    >{{$t('partial')}}</span>
                    <span v-else class="badge badge-outline-warning">{{$t('Unpaid')}}</span>
                  </div>
                   <div v-else-if="props.column.field == 'Ref'">
                    <router-link
                      :to="'/app/sale_return/detail/'+props.row.id"
                    >
                      <span class="ul-btn__text ml-1">{{props.row.Ref}}</span>
                    </router-link>
                  </div>
                  <div v-else-if="props.column.field == 'sale_ref' && props.row.sale_id">
                  <router-link
                    :to="'/app/sales/detail/'+props.row.sale_id"
                  >
                    <span class="ul-btn__text ml-1">{{props.row.sale_ref}}</span>
                  </router-link>
                </div>
                </template>
              </vue-good-table>
            </b-tab>

            <!-- Returns Purchase Table -->
            <b-tab :title="$t('PurchasesReturn')">
              <vue-good-table
                mode="remote"
                :columns="columns_returns_purchase"
                :totalRows="totalRows_Return_purchase"
                :rows="returns_purchase"
                @on-page-change="PageChangeReturn_Purchase"
                @on-per-page-change="onPerPageChangeReturn_Purchase"
                :pagination-options="{
                  enabled: true,
                  mode: 'records',
                  nextLabel: 'next',
                  prevLabel: 'prev',
                }"
                @on-search="onSearch_Return_Purchase"
                :search-options="{
                    placeholder: $t('Search_this_table'),
                    enabled: true,
                }"
                styleClass="order-table vgt-table mt-2"
              >
               <div slot="table-actions" class="mt-2 mb-3">
                <b-button @click="Returns_Purchase_PDF()" size="sm" variant="outline-success ripple m-1">
                  <i class="i-File-Copy"></i> PDF
                </b-button>
              </div>
                <template slot="table-row" slot-scope="props">
                  <div v-if="props.column.field == 'statut'">
                    <span
                      v-if="props.row.statut == 'completed'"
                      class="badge badge-outline-success"
                    >{{$t('complete')}}</span>
                    <span v-else class="badge badge-outline-info">{{$t('Pending')}}</span>
                  </div>

                  <div v-else-if="props.column.field == 'payment_status'">
                    <span
                      v-if="props.row.payment_status == 'paid'"
                      class="badge badge-outline-success"
                    >{{$t('Paid')}}</span>
                    <span
                      v-else-if="props.row.payment_status == 'partial'"
                      class="badge badge-outline-primary"
                    >{{$t('partial')}}</span>
                    <span v-else class="badge badge-outline-warning">{{$t('Unpaid')}}</span>
                  </div>
                   <div v-else-if="props.column.field == 'Ref'">
                    <router-link
                      :to="'/app/purchase_return/detail/'+props.row.id"
                    >
                      <span class="ul-btn__text ml-1">{{props.row.Ref}}</span>
                    </router-link>
                  </div>
                   <div v-else-if="props.column.field == 'purchase_ref' && props.row.purchase_id">
                    <router-link
                      :to="'/app/purchases/detail/'+props.row.purchase_id"
                    >
                      <span class="ul-btn__text ml-1">{{props.row.purchase_ref}}</span>
                    </router-link>
                  </div>
                </template>
              </vue-good-table>
            </b-tab>

            <!-- Expense Table -->
            <b-tab :title="$t('Expenses')">
              <vue-good-table
                mode="remote"
                :columns="columns_Expense"
                :totalRows="totalRows_Expense"
                :rows="expenses"
                @on-page-change="PageChange_Expense"
                @on-per-page-change="onPerPageChange_Expense"
                :pagination-options="{
                  enabled: true,
                  mode: 'records',
                  nextLabel: 'next',
                  prevLabel: 'prev',
                }"
                @on-search="onSearch_Expense"
                :search-options="{
                    placeholder: $t('Search_this_table'),
                    enabled: true,
                }"
                styleClass="order-table vgt-table mt-2"
              >
               <div slot="table-actions" class="mt-2 mb-3">
                <b-button @click="Expense_PDF()" size="sm" variant="outline-success ripple m-1">
                  <i class="i-File-Copy"></i> PDF
                </b-button>
              </div>
              </vue-good-table>
            </b-tab>
          </b-tabs>
        </b-card>
      </b-col>
    </b-row>
    <b-row class="mt-3" v-if="!isLoading">
      <b-col lg="6" md="12" sm="12">
        <b-card class="mb-30">
          <h4 class="card-title m-0">{{$t('Total_Items_Quantity')}}</h4>
          <div class="chart-wrapper mt-3">
            <apexchart type="donut" height="300" :options="apexCountOptions" :series="apexCountSeries" />
          </div>
        </b-card>
      </b-col>
      <b-col col lg="6" md="12" sm="12">
        <b-card class="mb-30">
          <h4 class="card-title m-0">{{$t('Value_by_Cost_and_Price')}}</h4>
          <div class="chart-wrapper mt-3">
            <apexchart type="donut" height="300" :options="apexValueOptions" :series="apexValueSeries" />
          </div>
        </b-card>
      </b-col>
    </b-row>
  </div>
</template>


<script>
import { mapActions, mapGetters } from "vuex";
import VueApexCharts from "vue-apexcharts";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

export default {
  components: {
    apexchart: VueApexCharts,
    StatTile: {
      name: "StatTile",
      functional: true,
      props: { icon:String, label:String, sub:String, value:[String,Number], theme:{type:String,default:'blue'} },
      render(h,{props}){
        return h('div',{class:['stat-card',`theme-${props.theme}`,'shadow-soft','rounded-xl']},[
          h('div',{class:'stat-inner'},[
            h('div',{class:'stat-icon'},[ h('i',{class:[props.icon]}) ]),
            h('div',{class:'stat-content'},[
              h('div',{class:'stat-label'},props.label),
              props.sub ? h('div',{class:'stat-sub text-muted'},props.sub) : null,
              h('div',{class:'stat-value'},props.value),
            ])
          ])
        ]);
      }
    }
  },
  metaInfo: {
    // if no subcomponents specify a metaInfo.title, this title will be used
    title: "Warehouse Report"
  },
  data() {
    return {
      // ApexCharts data
      apexCountLabels: [],
      apexCountSeries: [],
      apexCountExtra: [], // quantity or secondary value for tooltip
      apexValueLabels: [],
      apexValueSeries: [],
      apexValueExtra: [], // cost for tooltip
      totalRows_quotations: "",
      totalRows_sales: "",
      totalRows_Return_sale: "",
      totalRows_Return_purchase: "",
      totalRows_Expense: "",
      limit_quotations: "10",
      limit_returns_Sale: "10",
      limit_returns_Purchase: "10",
      limit_sales: "10",
      limit_expenses: "10",
      search_quotation: "",
      search_sale: "",
      search_expense: "",
      search_return_Sale: "",
      search_return_Purchase: "",
      sales_page: 1,
      quotations_page: 1,
      Return_sale_page: 1,
      Return_purchase_page: 1,
      Expense_page: 1,
      isLoading: true,
      Filter_warehouse: "",
      sales: [],
      quotations: [],
      warehouses: [],
      expenses: [],
      returns_sale: [],
      returns_purchase: [],
      total: {
        sales: "",
        purchases: "",
        ReturnPurchase: "",
        ReturnSale: ""
      }
    };
  },

  computed: {
    ...mapGetters(["currentUser"]),
    // ApexCharts options
    apexCountOptions() {
      return {
        chart: { toolbar: { show: false } },
        labels: this.apexCountLabels,
        legend: { show: true, position: 'bottom' },
        colors: ["#6D28D9", "#A78BFA", "#7C3AED", "#8B5CF6", "#C4B5FD"],
        dataLabels: { enabled: true },
        tooltip: {
          y: {
            formatter: (val, opts) => {
              const idx = opts && typeof opts.seriesIndex === 'number' ? opts.seriesIndex : -1;
              const extra = idx >= 0 ? (this.apexCountExtra[idx] || 0) : 0;
              return `${this.$t('Items')}: ${this.formatNumber(val, 0)}\n${this.$t('Quantity')}: ${this.formatNumber(extra, 0)}`;
            }
          }
        }
      };
    },
    apexValueOptions() {
      return {
        chart: { toolbar: { show: false } },
        labels: this.apexValueLabels,
        legend: { show: true, position: 'bottom' },
        colors: ["#6D28D9", "#A78BFA", "#7C3AED", "#8B5CF6", "#C4B5FD"],
        dataLabels: { enabled: true },
        tooltip: {
          y: {
            formatter: (val, opts) => {
              const idx = opts && typeof opts.seriesIndex === 'number' ? opts.seriesIndex : -1;
              const extra = idx >= 0 ? (this.apexValueExtra[idx] || 0) : 0;
              return `${this.$t('Stock_Value_by_Price') || 'Stock Value by Price'}: ${this.formatNumber(val, 2)}\n${this.$t('Stock_Value_by_Cost') || 'Stock Value by Cost'}: ${this.formatNumber(extra, 2)}`;
            }
          }
        }
      };
    },
    columns_quotations() {
      return [
        {
          label: this.$t("date"),
          field: "date",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Customer"),
          field: "client_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Total"),
          field: "GrandTotal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Status"),
          field: "statut",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        }
      ];
    },
    columns_sales() {
      return [
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Customer"),
          field: "client_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        
        {
          label: this.$t("Total"),
          field: "GrandTotal",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Paid"),
          field: "paid_amount",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Due"),
          field: "due",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Status"),
          field: "statut",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("PaymentStatus"),
          field: "payment_status",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
         {
          label: this.$t("Shipping_status"),
          field: "shipping_status",
          tdClass: "text-left",
          thClass: "text-left"
        },
      ];
    },
    columns_returns_sale() {
      return [
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Customer"),
          field: "client_name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Sale_Ref"),
          field: "sale_ref",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
       
        {
          label: this.$t("Total"),
          field: "GrandTotal",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Paid"),
          field: "paid_amount",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Due"),
          field: "due",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
         {
          label: this.$t("Status"),
          field: "statut",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("PaymentStatus"),
          field: "payment_status",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        }
      ];
    },
    columns_returns_purchase() {
      return [
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Supplier"),
          field: "provider_name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Purchase_Ref"),
          field: "purchase_ref",
          tdClass: "text-left",
          thClass: "text-left"
        },
        
        {
          label: this.$t("Total"),
          field: "GrandTotal",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Paid"),
          field: "paid_amount",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Due"),
          field: "due",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Status"),
          field: "statut",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("PaymentStatus"),
          field: "payment_status",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        }
      ];
    },
    columns_Expense() {
      return [
        {
          label: this.$t("date"),
          field: "date",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Details"),
          field: "details",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Amount"),
          field: "amount",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Categorie"),
          field: "category_name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        }
      ];
    }
  },

  methods: {

    //---------------------- Expenses PDF -------------------------------\\
    Expense_PDF() {
      const pdf = new jsPDF("p", "pt");
      const fontPath = "/fonts/Vazirmatn-Bold.ttf";
      try {
        pdf.addFont(fontPath, "Vazirmatn", "normal");
        pdf.addFont(fontPath, "Vazirmatn", "bold");
      } catch(e) { /* ignore if already added */ }
      pdf.setFont("Vazirmatn", "normal");

      const headers = [
        this.$t("date"),
        this.$t("Reference"),
        this.$t("Amount"),
        this.$t("Categorie"),
        this.$t("warehouse")
      ];
      const body = (this.expenses || []).map(r => ([ r.date, r.Ref, r.amount, r.category_name, r.warehouse_name ]));

      const marginX = 40;
      const rtl =
        (this.$i18n && ['ar','fa','ur','he'].includes(this.$i18n.locale)) ||
        (typeof document !== 'undefined' && document.documentElement.dir === 'rtl');

      autoTable(pdf, {
        head: [headers],
        body,
        startY: 110,
        theme: 'striped',
        margin: { left: marginX, right: marginX },
        styles: { font: 'Vazirmatn', fontSize: 9, cellPadding: 4, halign: rtl ? 'right' : 'left', textColor: 33 },
        headStyles: { font: 'Vazirmatn', fontStyle: 'bold', fillColor: [26,86,219], textColor: 255 },
        alternateRowStyles: { fillColor: [245,247,250] },
        columnStyles: { 2: { halign: 'right' } },
        didDrawPage: (d) => {
          const pageW = pdf.internal.pageSize.getWidth();
          const pageH = pdf.internal.pageSize.getHeight();

          pdf.setFillColor(26,86,219);
          pdf.rect(0, 0, pageW, 60, 'F');
          pdf.setTextColor(255);
          pdf.setFont('Vazirmatn', 'bold');
          pdf.setFontSize(16);
          const title = 'Expense List';
          rtl ? pdf.text(title, pageW - marginX, 38, { align: 'right' })
              : pdf.text(title, marginX, 38);
          pdf.setTextColor(33);
          pdf.setFontSize(8);
          const pn = `${d.pageNumber} / ${pdf.internal.getNumberOfPages()}`;
          rtl ? pdf.text(pn, marginX, pageH - 14, { align: 'left' })
              : pdf.text(pn, pageW - marginX, pageH - 14, { align: 'right' });
        }
      });

      pdf.save("Expense_List.pdf");
    },

       //----------------------------------------- Returns Purchase PDF -----------------------\\
    Returns_Purchase_PDF() {
      const pdf = new jsPDF("p", "pt");
      const fontPath = "/fonts/Vazirmatn-Bold.ttf";
      try {
        pdf.addFont(fontPath, "Vazirmatn", "normal");
        pdf.addFont(fontPath, "Vazirmatn", "bold");
      } catch(e) { /* ignore if already added */ }
      pdf.setFont("Vazirmatn", "normal");

      const headers = [
        this.$t('Reference'), this.$t('Supplier'), this.$t('warehouse'), this.$t('Purchase'),
        this.$t('Total'), this.$t('Paid'), this.$t('Due'), this.$t('Status'), this.$t('PaymentStatus')
      ];
      const body = (this.returns_purchase || []).map(r => ([
        r.Ref, r.provider_name, r.warehouse_name, r.purchase_ref,
        r.GrandTotal, r.paid_amount, r.due, r.statut, r.payment_status
      ]));

      const marginX = 40;
      const rtl =
        (this.$i18n && ['ar','fa','ur','he'].includes(this.$i18n.locale)) ||
        (typeof document !== 'undefined' && document.documentElement.dir === 'rtl');

      autoTable(pdf, {
        head: [headers], body,
        startY: 110, theme: 'striped', margin: { left: marginX, right: marginX },
        styles: { font: 'Vazirmatn', fontSize: 9, cellPadding: 4, halign: rtl ? 'right' : 'left', textColor: 33 },
        headStyles: { font: 'Vazirmatn', fontStyle: 'bold', fillColor: [26,86,219], textColor: 255 },
        alternateRowStyles: { fillColor: [245,247,250] },
        columnStyles: { 4: { halign: 'right' }, 5: { halign: 'right' }, 6: { halign: 'right' } },
        didDrawPage: (d) => {
          const pageW = pdf.internal.pageSize.getWidth();
          const pageH = pdf.internal.pageSize.getHeight();
          pdf.setFillColor(26,86,219); pdf.rect(0, 0, pageW, 60, 'F');
          pdf.setTextColor(255); pdf.setFont('Vazirmatn', 'bold'); pdf.setFontSize(16);
          const title = 'Purchase Return List';
          rtl ? pdf.text(title, pageW - marginX, 38, { align: 'right' }) : pdf.text(title, marginX, 38);
          pdf.setTextColor(33); pdf.setFontSize(8);
          const pn = `${d.pageNumber} / ${pdf.internal.getNumberOfPages()}`;
          rtl ? pdf.text(pn, marginX, pageH - 14, { align: 'left' }) : pdf.text(pn, pageW - marginX, pageH - 14, { align: 'right' });
        }
      });

      pdf.save("purchase_returns.pdf");
    },

      //----------------------------------------- Sales Return PDF -----------------------\\
    Sale_Return_PDF() {
      const pdf = new jsPDF("p", "pt");
      const fontPath = "/fonts/Vazirmatn-Bold.ttf";
      try {
        pdf.addFont(fontPath, "Vazirmatn", "normal");
        pdf.addFont(fontPath, "Vazirmatn", "bold");
      } catch(e) { /* ignore if already added */ }
      pdf.setFont("Vazirmatn", "normal");

      const headers = [
        this.$t('Reference'), this.$t('Customer'), this.$t('Sale_Ref'), this.$t('warehouse'),
        this.$t('Total'), this.$t('Paid'), this.$t('Due'), this.$t('Status'), this.$t('PaymentStatus')
      ];
      const body = (this.returns_sale || []).map(r => ([
        r.Ref, r.client_name, r.sale_ref, r.warehouse_name,
        r.GrandTotal, r.paid_amount, r.due, r.statut, r.payment_status
      ]));

      const marginX = 40; const rtl = (this.$i18n && ['ar','fa','ur','he'].includes(this.$i18n.locale)) || (typeof document !== 'undefined' && document.documentElement.dir === 'rtl');

      autoTable(pdf, {
        head: [headers], body,
        startY: 110, theme: 'striped', margin: { left: marginX, right: marginX },
        styles: { font: 'Vazirmatn', fontSize: 9, cellPadding: 4, halign: rtl ? 'right' : 'left', textColor: 33 },
        headStyles: { font: 'Vazirmatn', fontStyle: 'bold', fillColor: [26,86,219], textColor: 255 },
        alternateRowStyles: { fillColor: [245,247,250] },
        columnStyles: { 4: { halign: 'right' }, 5: { halign: 'right' }, 6: { halign: 'right' } },
        didDrawPage: (d) => {
          const pageW = pdf.internal.pageSize.getWidth(); const pageH = pdf.internal.pageSize.getHeight();
          pdf.setFillColor(26,86,219); pdf.rect(0, 0, pageW, 60, 'F');
          pdf.setTextColor(255); pdf.setFont('Vazirmatn', 'bold'); pdf.setFontSize(16);
          const title = 'Sales Return List';
          rtl ? pdf.text(title, pageW - marginX, 38, { align: 'right' }) : pdf.text(title, marginX, 38);
          pdf.setTextColor(33); pdf.setFontSize(8);
          const pn = `${d.pageNumber} / ${pdf.internal.getNumberOfPages()}`;
          rtl ? pdf.text(pn, marginX, pageH - 14, { align: 'left' }) : pdf.text(pn, pageW - marginX, pageH - 14, { align: 'right' });
        }
      });

      pdf.save("Sales Return.pdf");
    },

      //----------------------------------- Sales PDF ------------------------------\\
    Sales_PDF() {
      const pdf = new jsPDF('p','pt');
      const fontPath = '/fonts/Vazirmatn-Bold.ttf';
      try { pdf.addFont(fontPath,'Vazirmatn','normal'); pdf.addFont(fontPath,'Vazirmatn','bold'); } catch(e){}
      pdf.setFont('Vazirmatn','normal');

      const headers = [ this.$t('Reference'), this.$t('Customer'), this.$t('warehouse'), this.$t('Status'), this.$t('Total'), this.$t('Paid'), this.$t('Due'), this.$t('PaymentStatus'), this.$t('Shipping_status') ];
      const body = (this.sales||[]).map(r=>[ r.Ref, r.client_name, r.warehouse_name, r.statut, r.GrandTotal, r.paid_amount, r.due, r.payment_status, r.shipping_status ]);

      const marginX = 40; const rtl = (this.$i18n && ['ar','fa','ur','he'].includes(this.$i18n.locale)) || (typeof document!=='undefined' && document.documentElement.dir==='rtl');

      autoTable(pdf, {
        head:[headers], body, startY:110, theme:'striped', margin:{left:marginX,right:marginX},
        styles:{ font:'Vazirmatn', fontSize:9, cellPadding:4, halign: rtl?'right':'left', textColor:33 },
        headStyles:{ font:'Vazirmatn', fontStyle:'bold', fillColor:[26,86,219], textColor:255 },
        alternateRowStyles:{ fillColor:[245,247,250] },
        columnStyles:{ 4:{halign:'right'}, 5:{halign:'right'}, 6:{halign:'right'} },
        didDrawPage:(d)=>{
          const pageW = pdf.internal.pageSize.getWidth(); const pageH = pdf.internal.pageSize.getHeight();
          pdf.setFillColor(26,86,219); pdf.rect(0,0,pageW,60,'F'); pdf.setTextColor(255); pdf.setFont('Vazirmatn','bold'); pdf.setFontSize(16);
          const title = 'Sales List';
          rtl ? pdf.text(title, pageW - marginX, 38, { align:'right' }) : pdf.text(title, marginX, 38);
          pdf.setTextColor(33); pdf.setFontSize(8);
          const pn = `${d.pageNumber} / ${pdf.internal.getNumberOfPages()}`;
          rtl ? pdf.text(pn, marginX, pageH - 14, { align: 'left' }) : pdf.text(pn, pageW - marginX, pageH - 14, { align: 'right' });
        }
      });

      pdf.save('Sale_List.pdf');
    },

      //------------------------------------- Quotations PDF -------------------------\\
    Quotation_PDF() {
      var self = this;
      let pdf = new jsPDF("p", "pt");

      const fontPath = "/fonts/Vazirmatn-Bold.ttf";
      pdf.addFont(fontPath, "VazirmatnBold", "bold"); 
      pdf.setFont("VazirmatnBold"); 
      
      let columns = [
        { header: self.$t("date"), dataKey: "date" },
        { header: self.$t("Reference"), dataKey: "Ref" },
        { header: self.$t("Customer"), dataKey: "client_name" },
        { header: self.$t("warehouse"), dataKey: "warehouse_name" },
        { header: self.$t("Status"), dataKey: "statut" },
        { header: self.$t("Total"), dataKey: "GrandTotal" }
      ];
      autoTable(pdf, {
             columns: columns,
             body: self.quotations,
             startY: 70,
             theme: "grid", 
             didDrawPage: (data) => {
               pdf.setFont("VazirmatnBold");
               pdf.setFontSize(18);
               pdf.text("Quotation List", 40, 25);   
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

      });

      pdf.save("Quotation_List.pdf");
    },

    //------------------------------Formetted Numbers -------------------------\\
    formatNumber(number, dec) {
      const value = (typeof number === "string"
        ? number
        : number.toString()
      ).split(".");
      if (dec <= 0) return value[0];
      let formated = value[1] || "";
      if (formated.length > dec)
        return `${value[0]}.${formated.substr(0, dec)}`;
      while (formated.length < dec) formated += "0";
      return `${value[0]}.${formated}`;
    },

    //---------------------- Event Select Warehouse ------------------------------\\
    Selected_Warehouse(value) {
      this.isLoading = true;
      if (value === null) {
        this.Filter_warehouse = "";
      }
      this.Get_Reports();
      this.Get_Sales(1);
      this.Get_Quotations(1);
      this.Get_Returns_Sale(1);
      this.Get_Returns_Purchase(1);
      this.Get_Expenses(1);

      setTimeout(() => {
        this.isLoading = false;
      }, 1000);
    },

    //------------------------------ Show Reports -------------------------\\
    Get_Reports() {
      axios
        .get("report/warehouse_report?warehouse_id=" + this.Filter_warehouse)
        .then(response => {
          this.total = response.data.data;
          this.warehouses = response.data.warehouses;
        })
        .catch(response => {});
    },

    //--------------------------- Sales Event Page Change  -------------\\
    PageChangeSales({ currentPage }) {
      if (this.sales_page !== currentPage) {
        this.Get_Sales(currentPage);
      }
    },

    //--------------------------- Limit Page Sales -------------\\
    onPerPageChangeSales({ currentPerPage }) {
      if (this.limit_sales !== currentPerPage) {
        this.limit_sales = currentPerPage;
        this.Get_Sales(1);
      }
    },

    onSearch_Sales(value) {
      this.search_sale = value.searchTerm;
      this.Get_Sales(1);
    },

    //--------------------------- Get sales By warehouse -------------\\
    Get_Sales(page) {
      axios
        .get(
          "report/sales_warehouse?page=" +
            page +
            "&limit=" +
            this.limit_sales +
            "&warehouse_id=" +
            this.Filter_warehouse +
            "&search=" +
            this.search_sale
        )
        .then(response => {
          this.sales = response.data.sales;
          this.totalRows_sales = response.data.totalRows;
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        })
        .catch(response => {
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    },

    //--------------------------- Event Page Change -------------\\
    PageChangeQuotation({ currentPage }) {
      if (this.quotations_page !== currentPage) {
        this.Get_Quotations(currentPage);
      }
    },

    //--------------------------- Limit Page Quotations -------------\\
    onPerPageChangeQuotation({ currentPerPage }) {
      if (this.limit_quotations !== currentPerPage) {
        this.limit_quotations = currentPerPage;
        this.Get_Quotations(1);
      }
    },

    onSearch_Quotations(value) {
      this.search_quotation = value.searchTerm;
      this.Get_Quotations(1);
    },

    //--------------------------- Get Quotations By Warehouse -------------\\
    Get_Quotations(page) {
      axios
        .get(
          "report/quotations_warehouse?page=" +
            page +
            "&limit=" +
            this.limit_quotations +
            "&warehouse_id=" +
            this.Filter_warehouse +
            "&search=" +
            this.search_quotation
        )
        .then(response => {
          this.quotations = response.data.quotations;
          this.totalRows_quotations = response.data.totalRows;
        })
        .catch(response => {});
    },

    //--------------------------- Event Page Change -------------\\
    PageChangeReturn_Customer({ currentPage }) {
      if (this.Return_sale_page !== currentPage) {
        this.Get_Returns_Sale(currentPage);
      }
    },

    //--------------------------- Limit Page Returns Sale -------------\\
    onPerPageChangeReturn_Sale({ currentPerPage }) {
      if (this.limit_returns_Sale !== currentPerPage) {
        this.limit_returns_Sale = currentPerPage;
        this.Get_Returns_Sale(1);
      }
    },

    onSearch_Return_Sale(value) {
      this.search_return_Sale = value.searchTerm;
      this.Get_Returns_Sale(1);
    },

    //--------------------------- Get Returns Sale By warehouse -------------\\
    Get_Returns_Sale(page) {
      axios
        .get(
          "report/returns_sale_warehouse?page=" +
            page +
            "&limit=" +
            this.limit_returns_Sale +
            "&warehouse_id=" +
            this.Filter_warehouse +
            "&search=" +
            this.search_return_Sale
        )
        .then(response => {
          this.returns_sale = response.data.returns_sale;
          this.totalRows_Return_sale = response.data.totalRows;
        })
        .catch(response => {});
    },

    //--------------------------- Event Page Change -------------\\
    PageChangeReturn_Purchase({ currentPage }) {
      if (this.Return_purchase_page !== currentPage) {
        this.Get_Returns_Purchase(currentPage);
      }
    },

    //--------------------------- Limit Page Returns Purchase -------------\\
    onPerPageChangeReturn_Purchase({ currentPerPage }) {
      if (this.limit_returns_Purchase !== currentPerPage) {
        this.limit_returns_Purchase = currentPerPage;
        this.Get_Returns_Purchase(1);
      }
    },

    onSearch_Return_Purchase(value) {
      this.search_return_Purchase = value.searchTerm;
      this.Get_Returns_Purchase(1);
    },

    //--------------------------- Get Returns Purchase By warehouse -------------\\
    Get_Returns_Purchase(page) {
      axios
        .get(
          "report/returns_purchase_warehouse?page=" +
            page +
            "&limit=" +
            this.limit_returns_Purchase +
            "&warehouse_id=" +
            this.Filter_warehouse +
            "&search=" +
            this.search_return_Purchase
        )
        .then(response => {
          this.returns_purchase = response.data.returns_purchase;
          this.totalRows_Return_purchase = response.data.totalRows;
        })
        .catch(response => {});
    },

    //--------------------------- Expense Event Page Change -------------\\
    PageChange_Expense({ currentPage }) {
      if (this.Expense_page !== currentPage) {
        this.Get_Expenses(currentPage);
      }
    },

    //--------------------------- Limit Page Expense -------------\\
    onPerPageChange_Expense({ currentPerPage }) {
      if (this.limit_expenses !== currentPerPage) {
        this.limit_expenses = currentPerPage;
        this.Get_Expenses(1);
      }
    },

    onSearch_Expense(value) {
      this.search_expense = value.searchTerm;
      this.Get_Expenses(1);
    },

    //--------------------------- Get Expenses By warehouse -------------\\
    Get_Expenses(page) {
      axios
        .get(
          "report/expenses_warehouse?page=" +
            page +
            "&limit=" +
            this.limit_expenses +
            "&warehouse_id=" +
            this.Filter_warehouse +
            "&search=" +
            this.search_expense
        )
        .then(response => {
          this.expenses = response.data.expenses;
          this.totalRows_Expense = response.data.totalRows;
        })
        .catch(response => {});
    },

    //---------------------------------- Report Warehouse Count Stock (ApexCharts)
    report_with_apex() {
      axios
        .get(`report/warhouse_count_stock`)
        .then(response => {
          const d = response.data || {};

          // Prefer backend-provided Apex arrays; fallback to mapping objects
          const ccLabels = Array.isArray(d.count_labels) && d.count_labels.length
            ? d.count_labels : (Array.isArray(d.stock_count) ? d.stock_count.map(x => x.name) : []);
          const ccItems  = Array.isArray(d.count_items) && d.count_items.length
            ? d.count_items : (Array.isArray(d.stock_count) ? d.stock_count.map(x => Number(x && x.value || 0)) : []);
          const ccQty    = Array.isArray(d.count_qty) && d.count_qty.length
            ? d.count_qty   : (Array.isArray(d.stock_count) ? d.stock_count.map(x => Number(x && x.value1 || 0)) : []);

          const cvLabels = Array.isArray(d.value_labels) && d.value_labels.length
            ? d.value_labels : (Array.isArray(d.stock_value) ? d.stock_value.map(x => x.name) : []);
          const cvPrice  = Array.isArray(d.value_price) && d.value_price.length
            ? d.value_price : (Array.isArray(d.stock_value) ? d.stock_value.map(x => Number(x && (x.value ?? x.price) || 0)) : []);
          const cvCost   = Array.isArray(d.value_cost) && d.value_cost.length
            ? d.value_cost  : (Array.isArray(d.stock_value) ? d.stock_value.map(x => Number(x && (x.value1 ?? x.cost) || 0)) : []);

          this.apexCountLabels = ccLabels;
          this.apexCountSeries = ccItems;
          this.apexCountExtra  = ccQty;

          this.apexValueLabels = cvLabels;
          this.apexValueSeries = cvPrice;
          this.apexValueExtra  = cvCost;
        })
        .catch(() => {});
    }
  }, //end Methods

  //----------------------------- Created function------------------- \\

  created: function() {
    this.report_with_apex();
    this.Get_Reports();
    this.Get_Sales(1);
    this.Get_Quotations(1);
    this.Get_Returns_Sale(1);
    this.Get_Returns_Purchase(1);
    this.Get_Expenses(1);
  }
};
</script>

<style scoped>
.rounded-xl { border-radius: 1rem; }
.shadow-soft { box-shadow: 0 12px 24px rgba(0,0,0,0.06), 0 2px 6px rgba(0,0,0,0.05); }

.stat-card {
  background: linear-gradient(135deg, var(--gradA,#f7f9ff), var(--gradB,#ffffff));
  padding:14px 16px; min-height:110px; position:relative;
}
.stat-inner { display:flex; align-items:center; }
.stat-icon {
  width:48px; height:48px; border-radius:12px; margin-right:12px;
  display:flex; align-items:center; justify-content:center;
  background: rgba(255,255,255,0.75);
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.7), 0 1px 2px rgba(0,0,0,0.05);
}
.stat-icon i { font-size:22px; }
.stat-label { font-size:.85rem; font-weight:600; }
.stat-sub { font-size:.75rem; margin-top:-2px; }
.stat-value { font-size:1.35rem; font-weight:700; line-height:1.2; margin-top:2px; }

.theme-blue   { --gradA:#e6f0ff; --gradB:#ffffff; color:#0b5fff; }
.theme-teal   { --gradA:#e6fbf6; --gradB:#ffffff; color:#138f7a; }
.theme-indigo { --gradA:#eef0ff; --gradB:#ffffff; color:#3949ab; }
.theme-green  { --gradA:#edf9ee; --gradB:#ffffff; color:#2e7d32; }
.theme-orange { --gradA:#fff4e6; --gradB:#ffffff; color:#cc6b00; }
.theme-purple { --gradA:#f5e6ff; --gradB:#ffffff; color:#6a2ecc; }
</style>