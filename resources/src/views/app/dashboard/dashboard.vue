<template>
  <div class="main-content">
    <div v-if="loading" class="loading_page spinner spinner-primary mr-3"></div>

    <div v-else-if="!loading && currentUserPermissions && currentUserPermissions.includes('dashboard')">
      <!-- Welcome Header -->
      <div class="dashboard-header mb-4">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h2 class="mb-1 text-dark">{{ $t('dashboard') }}</h2>
            <p class="welcome-text mb-0">{{ $t('Welcome_back_message', { username: currentUser.username }) }}</p>
          </div>
          <div class="col-md-4 text-right">
            <div class="current-time">
              <i class="i-Calendar-4 mr-2"></i>
              <span class="font-weight-bold">{{ new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Filters -->
      <div class="filter-card mb-4" style="display: none;">
        <div class="row align-items-end">
          <!-- Warehouse -->
          <div class="col-lg-4 col-md-6 col-sm-12 mb-2">
            <label class="form-label text-muted font-weight-bold">{{ $t('Filter_by_warehouse') }}</label>
            <v-select
              @input="Selected_Warehouse"
              v-model="warehouse_id"
              :reduce="label => label.value"
              :placeholder="$t('Filter_by_warehouse')"
              :options="warehouses.map(w => ({label:w.name, value:w.id}))"
            />
          </div>

          <!-- Date range -->
          <div class="col-lg-4 col-md-6 col-sm-12 mb-2">
            <label class="form-label text-muted font-weight-bold">{{ $t('DateRange') }}</label>
            <date-range-picker
              v-model="dateRange"
              :startDate="dateRange.startDate"
              :endDate="dateRange.endDate"
              :locale-data="locale"
              :autoApply="true"
              :showDropdowns="true"
              @update="Submit_filter_dateRange"
            >
              <template v-slot:input="picker">
                <b-button variant="light" class="w-100 text-left date-picker-btn">
                  <i class="i-Calendar-4 mr-2"></i>
                  {{ fmt(picker.startDate) }} - {{ fmt(picker.endDate) }}
                </b-button>
              </template>
            </date-range-picker>
          </div>

          <!-- Quick ranges -->
          <div class="col-lg-4 col-md-12 col-sm-12 mb-2">
            <label class="form-label text-muted font-weight-bold">{{ $t('QuickRanges') }}</label>
            <div class="btn-group flex-wrap quick-wrap w-100">
              <b-button size="sm" variant="outline-primary" class="mr-1 mb-1" @click="quick('today')">{{ $t('Today') }}</b-button>
              <b-button size="sm" variant="outline-primary" class="mr-1 mb-1" @click="quick('7d')">{{ $t('7D') }}</b-button>
              <b-button size="sm" variant="outline-primary" class="mr-1 mb-1" @click="quick('30d')">{{ $t('30D') }}</b-button>
              <b-button size="sm" variant="outline-primary" class="mr-1 mb-1" @click="quick('90d')">{{ $t('90D') }}</b-button>
              <b-button size="sm" variant="outline-primary" class="mr-1 mb-1" @click="quick('mtd')">{{ $t('MTD') }}</b-button>
              <b-button size="sm" variant="outline-primary" class="mb-1" @click="quick('ytd')">{{ $t('YTD') }}</b-button>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
          <div class="stat-card sales-card">
            <div class="stat-card-icon">
              <i class="i-Full-Cart"></i>
            </div>
            <div class="stat-card-content">
              <p class="stat-card-label">{{ $t('Sales') }}</p>
              <h3 class="stat-card-value">{{ currentUser.currency }} {{ report_today.today_sales ? report_today.today_sales : 0 }}</h3>
              <router-link to="/app/sales/list" class="stat-card-link">
                {{ $t('View') }} {{ $t('All') }} {{ $t('Sales') }}
              </router-link>
            </div>
          </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
          <div class="stat-card purchases-card">
            <div class="stat-card-icon">
              <i class="i-Add-Cart"></i>
            </div>
            <div class="stat-card-content">
              <p class="stat-card-label">{{ $t('Purchases') }}</p>
              <h3 class="stat-card-value">{{ currentUser.currency }} {{ report_today.today_purchases ? report_today.today_purchases : 0 }}</h3>
              <router-link to="/app/purchases/list" class="stat-card-link">
                {{ $t('View') }} {{ $t('All') }} {{ $t('Purchases') }}
              </router-link>
            </div>
          </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
          <div class="stat-card returns-card">
            <div class="stat-card-icon">
              <i class="i-Right-4"></i>
            </div>
            <div class="stat-card-content">
              <p class="stat-card-label">{{ $t('SalesReturn') }}</p>
              <h3 class="stat-card-value">{{ currentUser.currency }} {{ report_today.return_sales ? report_today.return_sales : 0 }}</h3>
              <router-link to="/app/sale_return/list" class="stat-card-link">
                {{ $t('View') }} {{ $t('Returns') }}
              </router-link>
            </div>
          </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
          <div class="stat-card revenue-card">
            <div class="stat-card-icon">
              <i class="i-Left-4"></i>
            </div>
            <div class="stat-card-content">
              <p class="stat-card-label">{{ $t('PurchasesReturn') }}</p>
              <h3 class="stat-card-value">{{ currentUser.currency }} {{ report_today.return_purchases ? report_today.return_purchases : 0 }}</h3>
              <router-link to="/app/purchase_return/list" class="stat-card-link">
                {{ $t('View') }} {{ $t('Returns') }}
              </router-link>
            </div>
          </div>
        </div>
        
      </div>

      <!-- Charts Row 1 -->
      <div class="row mb-4">
        <div class="col-lg-8 col-md-12 col-sm-12 mb-3">
          <div class="chart-card">
            <div class="chart-card-header">
              <h4 class="chart-card-title">{{ $t('This_Week_Sales_Purchases') }}</h4>
            </div>
            <div class="chart-card-body">
              <apexchart
                v-if="!loading"
                type="bar"
                height="350"
                :options="chartSalesOptions"
                :series="chartSalesSeries"
              ></apexchart>
              <div v-else class="text-center py-5">
                <div class="spinner spinner-primary"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-12 col-sm-12 mb-3">
          <div class="chart-card">
            <div class="chart-card-header">
              <h4 class="chart-card-title">{{ $t('Top_Selling_Products') }} ({{ new Date().getFullYear() }})</h4>
            </div>
            <div class="chart-card-body">
              <apexchart
                v-if="!loading"
                type="donut"
                height="350"
                :options="chartProductOptions"
                :series="chartProductSeries"
              ></apexchart>
              <div v-else class="text-center py-5">
                <div class="spinner spinner-primary"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row 2 -->
      <div class="row mb-4">
        <div class="col-lg-8 col-md-12 col-sm-12 mb-3">
          <div class="chart-card">
            <div class="chart-card-header">
              <h4 class="chart-card-title">{{ $t('Payment_Sent_Received') }}</h4>
            </div>
            <div class="chart-card-body">
              <apexchart
                v-if="!loading"
                type="area"
                height="350"
                :options="chartPaymentOptions"
                :series="chartPaymentSeries"
              ></apexchart>
              <div v-else class="text-center py-5">
                <div class="spinner spinner-primary"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-12 col-sm-12 mb-3">
          <div class="chart-card">
            <div class="chart-card-header">
              <h4 class="chart-card-title">{{ $t('TopCustomers') }} ({{ CurrentMonth }})</h4>
            </div>
            <div class="chart-card-body">
              <apexchart
                v-if="!loading"
                type="pie"
                height="350"
                :options="chartCustomerOptions"
                :series="chartCustomerSeries"
              ></apexchart>
              <div v-else class="text-center py-5">
                <div class="spinner spinner-primary"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tables Row -->
      <div class="row mb-4">
        <div class="col-lg-8 col-md-12 col-sm-12 mb-3">
          <div class="table-card">
            <div class="table-card-header">
              <h4 class="table-card-title">{{ $t('StockAlert') }}</h4>
              <router-link to="/app/products/list" class="table-card-link">
                {{ $t('View') }} {{ $t('All') }} <i class="i-Arrow-Right"></i>
              </router-link>
            </div>
            <div class="table-card-body">
              <vue-good-table
                :columns="columns_stock"
                row-style-class="text-left"
                :rows="stock_alerts"
                :pagination-options="{
                  enabled: false
                }"
              >
                <template slot="table-row" slot-scope="props">
                  <div v-if="props.column.field == 'stock_alert'">
                    <span class="stock-alert-badge">{{ props.row.stock_alert }}</span>
                  </div>
                </template>
              </vue-good-table>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-12 col-sm-12 mb-3">
          <div class="table-card">
            <div class="table-card-header">
              <h4 class="table-card-title">{{ $t('Top_Selling_Products') }} ({{ CurrentMonth }})</h4>
            </div>
            <div class="table-card-body">
              <vue-good-table
                :columns="columns_products"
                row-style-class="text-left"
                :rows="products"
                :pagination-options="{
                  enabled: false
                }"
              >
                <template slot="table-row" slot-scope="props">
                  <div v-if="props.column.field == 'total'">
                    <span class="font-weight-bold text-success">{{ currentUser.currency }} {{ formatNumber(props.row.total, 2) }}</span>
                  </div>
                </template>
              </vue-good-table>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Sales -->
      <div class="row">
        <div class="col-12">
          <div class="table-card">
            <div class="table-card-header">
              <h4 class="table-card-title">{{ $t('Recent_Sales') }}</h4>
              <router-link to="/app/sales/list" class="table-card-link">
                {{ $t('View') }} {{ $t('All') }} <i class="i-Arrow-Right"></i>
              </router-link>
            </div>
            <div class="table-card-body">
              <vue-good-table
                v-if="!loading"
                :columns="columns_sales"
                row-style-class="text-left"
                :rows="sales"
                :pagination-options="{
                  enabled: false
                }"
              >
                <template slot="table-row" slot-scope="props">
                  <div v-if="props.column.field == 'statut'">
                    <span v-if="props.row.statut == 'completed'" class="badge badge-success">{{ $t('complete') }}</span>
                    <span v-else-if="props.row.statut == 'pending'" class="badge badge-info">{{ $t('Pending') }}</span>
                    <span v-else class="badge badge-warning">{{ $t('Ordered') }}</span>
                  </div>

                  <div v-else-if="props.column.field == 'payment_status'">
                    <span v-if="props.row.payment_status == 'paid'" class="badge badge-success">{{ $t('Paid') }}</span>
                    <span v-else-if="props.row.payment_status == 'partial'" class="badge badge-primary">{{ $t('partial') }}</span>
                    <span v-else class="badge badge-warning">{{ $t('Unpaid') }}</span>
                  </div>
                </template>
              </vue-good-table>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div v-else>
      <div class="welcome-card">
        <div class="welcome-icon">
          <i class="i-Home1"></i>
        </div>
        <h3>{{ $t('Welcome_to_your_Dashboard') }}</h3>
        <p class="text-muted">{{ $t('No_dashboard_permission') }}</p>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import VueApexCharts from "vue-apexcharts";
import DateRangePicker from "vue2-daterange-picker";
import "vue2-daterange-picker/dist/vue2-daterange-picker.css";
import moment from "moment";

export default {
  components: {
    apexchart: VueApexCharts,
    "date-range-picker": DateRangePicker,
  },
  metaInfo: { title: "Dashboard" },
  data() {
    const end = moment().endOf("day");
    const start = end.clone().startOf("day");
    return {
      dateRange: { startDate: start.toDate(), endDate: end.toDate() },
      startDate: start.format("YYYY-MM-DD"),
      endDate: end.format("YYYY-MM-DD"),
      locale: {
        Label: this.$t("Apply") || "Apply",
        cancelLabel: this.$t("Cancel") || "Cancel",
        weekLabel: "W",
        customRangeLabel: this.$t("CustomRange") || "Custom Range",
        daysOfWeek: moment.weekdaysMin(),
        monthNames: moment.monthsShort(),
        firstDay: 1
      },
      today_mode: true,

      sales: [],
      warehouses: [],
      warehouse_id: "",
      stock_alerts: [],
      report_today: {
        revenue: 0,
        today_purchases: 0,
        today_sales: 0,
        return_sales: 0,
        return_purchases: 0
      },
      products: [],
      CurrentMonth: "",
      loading: true,
      

      // ApexCharts data
      chartSalesSeries: [],
      chartProductSeries: [],
      chartCustomerSeries: [],
      chartPaymentSeries: [],

      // ApexCharts options
      chartSalesOptions: {},
      chartProductOptions: {},
      chartCustomerOptions: {},
      chartPaymentOptions: {}
    };
  },
  computed: {
    ...mapGetters(["currentUserPermissions", "currentUser"]),
    
    columns_sales() {
      return [
        { label: this.$t("Reference"), field: "Ref", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("Customer"), field: "client_name", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("warehouse"), field: "warehouse_name", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("Status"), field: "statut", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("Total"), field: "GrandTotal", type: "decimal", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("Paid"), field: "paid_amount", type: "decimal", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("Due"), field: "due", type: "decimal", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("PaymentStatus"), field: "payment_status", sortable: false, tdClass: "text-left", thClass: "text-left" }
      ];
    },
    columns_stock() {
      return [
        { label: this.$t("ProductCode"), field: "code", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("ProductName"), field: "name", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("warehouse"), field: "warehouse", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("Quantity"), field: "quantity", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("AlertQuantity"), field: "stock_alert", tdClass: "text-left", thClass: "text-left", sortable: false }
      ];
    },
    columns_products() {
      return [
        { label: this.$t("ProductName"), field: "name", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("TotalSales"), field: "total_sales", tdClass: "text-left", thClass: "text-left", sortable: false },
        { label: this.$t("TotalAmount"), field: "total", tdClass: "text-left", thClass: "text-left", sortable: false }
      ];
    }
  },
  methods: {
    fmt(d) {
      return moment(d).format("YYYY-MM-DD");
    },

    quick(key) {
      const end = moment().endOf("day");
      let start = end.clone();
      switch (key) {
        case "today": start = end.clone().startOf("day"); break;
        case "7d": start = end.clone().subtract(6, "days").startOf("day"); break;
        case "30d": start = end.clone().subtract(29, "days").startOf("day"); break;
        case "90d": start = end.clone().subtract(89, "days").startOf("day"); break;
        case "mtd": start = moment().startOf("month"); break;
        case "ytd": start = moment().startOf("year"); break;
      }
      this.dateRange = { startDate: start.toDate(), endDate: end.toDate() };
      this.startDate = start.format("YYYY-MM-DD");
      this.endDate = end.format("YYYY-MM-DD");
      this.all_dashboard_data();
    },

    Submit_filter_dateRange() {
      const s = moment(this.dateRange.startDate);
      const e = moment(this.dateRange.endDate);
      this.startDate = s.format("YYYY-MM-DD");
      this.endDate = e.format("YYYY-MM-DD");
      this.all_dashboard_data();
    },

    get_data_loaded() {
      if (this.today_mode) {
        const end = moment().endOf("day");
        const start = end.clone().startOf("day");
        this.startDate = start.format("YYYY-MM-DD");
        this.endDate = end.format("YYYY-MM-DD");
        this.dateRange = { startDate: start.toDate(), endDate: end.toDate() };
      }
    },

    Selected_Warehouse(value) {
      if (value === null) this.warehouse_id = "";
      this.all_dashboard_data();
    },

    all_dashboard_data() {
      this.get_data_loaded();
      axios
        .get(`/dashboard_data?warehouse_id=${this.warehouse_id}&to=${this.endDate}&from=${this.startDate}`)
        .then(response => {
          this.today_mode = false;
          const responseData = response.data;
          const seriesSalesName = this.$t('Sales');
          const seriesPurchasesName = this.$t('Purchases');
          const amountLabel = this.$t('Amount');
          const totalSalesLabel = this.$t('Total_Sales');
          const salesLabel = this.$t('Sales');
          const paymentSentName = this.$t('Payment_Sent');
          const paymentReceivedName = this.$t('Payment_Received');

          this.report_today = response.data.report_dashboard.original.report;
          this.warehouses = response.data.warehouses;
          this.stock_alerts = response.data.report_dashboard.original.stock_alert;
          this.products = response.data.report_dashboard.original.products;
          this.sales = response.data.report_dashboard.original.last_sales;

          

          // Sales & Purchases Chart (Bar Chart)
          this.chartSalesSeries = [
            {
              name: seriesSalesName,
              data: responseData.sales.original.data
            },
            {
              name: seriesPurchasesName,
              data: responseData.purchases.original.data
            }
          ];
          this.chartSalesOptions = {
            chart: {
              type: "bar",
              toolbar: { show: true },
              fontFamily: "inherit"
            },
            colors: ["#8B5CF6", "#DDD6FE"],
            plotOptions: {
              bar: {
                horizontal: false,
                columnWidth: "55%",
                borderRadius: 8,
                dataLabels: {
                  position: "top"
                }
              }
            },
            dataLabels: {
              enabled: false
            },
            stroke: {
              show: true,
              width: 2,
              colors: ["transparent"]
            },
            xaxis: {
              categories: responseData.sales.original.days,
              labels: {
                rotate: -45,
                style: {
                  fontSize: "12px"
                }
              }
            },
            yaxis: {
              title: {
                text: amountLabel
              },
              labels: {
                formatter: function(value) {
                  try {
                    return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(value);
                  } catch (e) {
                    var n = Number(value);
                    if (isNaN(n)) return value;
                    var s = n.toFixed(2);
                    return s.replace(/\.00$/, "");
                  }
                }
              }
            },
            fill: {
              opacity: 1
            },
            tooltip: {
              y: {
                formatter: function(val) {
                  try {
                    return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(val);
                  } catch (e) {
                    var n = Number(val);
                    if (isNaN(n)) return val;
                    var s = n.toFixed(2);
                    return s.replace(/\.00$/, "");
                  }
                }
              }
            },
            legend: {
              position: "bottom",
              horizontalAlign: "center",
              fontSize: "14px",
              fontFamily: "inherit",
              fontWeight: 500,
              markers: {
                width: 12,
                height: 12,
                radius: 6
              },
              itemMargin: {
                horizontal: 15
              }
            },
            grid: {
              borderColor: "#e0e6ed",
              strokeDashArray: 5,
              xaxis: {
                lines: {
                  show: true
                }
              },
              yaxis: {
                lines: {
                  show: true
                }
              },
              padding: {
                top: 0,
                right: 0,
                bottom: 0,
                left: 0
              }
            }
          };

          // Top Selling Products Chart (Donut Chart)
          const productData = responseData.product_report.original;
          this.chartProductSeries = productData.map(item => item.value);
          this.chartProductOptions = {
            chart: {
              type: "donut",
              fontFamily: "inherit"
            },
            labels: productData.map(item => item.name),
            colors: ["#8B5CF6", "#A78BFA", "#C4B5FD", "#DDD6FE", "#EDE9FE"],
            legend: {
              position: "bottom",
              fontSize: "12px"
            },
            dataLabels: {
              enabled: true,
              formatter: function(val) {
                return Math.floor(val) + "%";
              }
            },
            plotOptions: {
              pie: {
                donut: {
                  size: "65%",
                  labels: {
                    show: true,
                    total: {
                      show: true,
                      label: totalSalesLabel,
                      formatter: function() {
                        return Math.floor(productData.reduce((sum, item) => sum + item.value, 0));
                      }
                    }
                  }
                }
              }
            },
            tooltip: {
              y: {
                formatter: function(val) {
                  return Math.floor(val) + " " + salesLabel;
                }
              }
            }
          };

          // Top Customers Chart (Pie Chart)
          const customerData = responseData.customers.original;
          this.chartCustomerSeries = customerData.map(item => item.value);
          this.chartCustomerOptions = {
            chart: {
              type: "pie",
              fontFamily: "inherit"
            },
            labels: customerData.map(item => item.name),
            colors: ["#8B5CF6", "#A78BFA", "#C4B5FD", "#DDD6FE", "#EDE9FE"],
            legend: {
              position: "bottom",
              fontSize: "12px"
            },
            dataLabels: {
              enabled: true,
              formatter: function(val) {
                return Math.floor(val) + "%";
              }
            },
            tooltip: {
              y: {
                formatter: function(val) {
                  return Math.floor(val) + " " + salesLabel;
                }
              }
            }
          };

          // Payment Sent/Received Chart (Area Chart)
          this.chartPaymentSeries = [
            {
              name: paymentSentName,
              data: responseData.payments.original.payment_sent
            },
            {
              name: paymentReceivedName,
              data: responseData.payments.original.payment_received
            }
          ];
          this.chartPaymentOptions = {
            chart: {
              type: "area",
              toolbar: { show: true },
              fontFamily: "inherit"
            },
            colors: ["#EF4444", "#10B981"],
            dataLabels: {
              enabled: false
            },
            stroke: {
              curve: "smooth",
              width: 3
            },
            xaxis: {
              categories: responseData.payments.original.days,
              labels: {
                rotate: -45,
                style: {
                  fontSize: "12px"
                }
              }
            },
            yaxis: {
              title: {
                text: amountLabel
              },
              labels: {
                formatter: function(value) {
                  try {
                    return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(value);
                  } catch (e) {
                    var n = Number(value);
                    if (isNaN(n)) return value;
                    var s = n.toFixed(2);
                    return s.replace(/\.00$/, "");
                  }
                }
              }
            },
            fill: {
              type: "gradient",
              gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.3,
                stops: [0, 90, 100]
              }
            },
            tooltip: {
              y: {
                formatter: function(val) {
                  try {
                    return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(val);
                  } catch (e) {
                    var n = Number(val);
                    if (isNaN(n)) return val;
                    var s = n.toFixed(2);
                    return s.replace(/\.00$/, "");
                  }
                }
              }
            },
            legend: {
              position: "bottom",
              horizontalAlign: "center",
              fontSize: "14px",
              fontFamily: "inherit",
              fontWeight: 500,
              markers: {
                width: 12,
                height: 12,
                radius: 6
              },
              itemMargin: {
                horizontal: 15
              }
            },
            grid: {
              borderColor: "#e0e6ed",
              strokeDashArray: 5,
              xaxis: {
                lines: {
                  show: true
                }
              },
              yaxis: {
                lines: {
                  show: true
                }
              }
            }
          };

          this.loading = false;
        })
        .catch(() => {
          this.today_mode = false;
        });
    },

    GetMonth() {
      const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
      this.CurrentMonth = months[new Date().getMonth()];
    },

    formatNumber(number, dec) {
      const value = (typeof number === "string" ? number : number.toString()).split(".");
      if (dec <= 0) return value[0];
      let f = value[1] || "";
      if (f.length > dec) return `${value[0]}.${f.substr(0, dec)}`;
      while (f.length < dec) f += "0";
      return `${value[0]}.${f}`;
    }
  },
  async mounted() {
    await this.all_dashboard_data();
    this.GetMonth();
  }
};
</script>

<style scoped>
/* Dashboard Header */
.dashboard-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
  border-radius: 12px;
  color: white;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.dashboard-header h2 {
  color: white !important;
  font-weight: 600;
}

.welcome-text {
  color: #FFFFFF !important;
  font-size: 1rem;
  font-weight: 500;
}

.current-time {
  background: rgba(255, 255, 255, 0.2);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  display: inline-block;
  color: white;
}

/* Filter Card */
.filter-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.form-label {
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.date-picker-btn {
  border: 1px solid #e0e6ed;
  transition: all 0.3s ease;
}

.date-picker-btn:hover {
  border-color: #8B5CF6;
  color: #8B5CF6;
}

.quick-wrap .btn {
  min-width: 58px;
  border-radius: 6px;
  transition: all 0.3s ease;
}

.quick-wrap .btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(139, 92, 246, 0.2);
}

/* Stat Cards */
.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  height: 100%;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.stat-card-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-right: 1rem;
  flex-shrink: 0;
}

.sales-card .stat-card-icon {
  background: linear-gradient(135deg, #8B5CF6 0%, #A78BFA 100%);
  color: white;
}

.purchases-card .stat-card-icon {
  background: linear-gradient(135deg, #10B981 0%, #34D399 100%);
  color: white;
}

.returns-card .stat-card-icon {
  background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%);
  color: white;
}

.revenue-card .stat-card-icon {
  background: linear-gradient(135deg, #EF4444 0%, #F87171 100%);
  color: white;
}

.stat-card-content {
  flex: 1;
}

.stat-card-label {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0 0 0.25rem 0;
  font-weight: 500;
}

.stat-card-value {
  color: #1f2937;
  font-size: 1.4rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
}

.stat-card-link {
  color: #8B5CF6;
  font-size: 0.875rem;
  text-decoration: none;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.stat-card-link:hover {
  color: #6D28D9;
  text-decoration: none;
}

/* Chart Cards */
.chart-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  transition: all 0.3s ease;
}

.chart-card:hover {
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.chart-card-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e0e6ed;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.chart-card-header .btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.chart-card-header .btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(139, 92, 246, 0.2);
}

.chart-card-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.chart-card-legend {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.legend-item {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  color: #6b7280;
}

.legend-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  margin-right: 0.5rem;
}

.sales-dot {
  background: #8B5CF6;
}

.purchases-dot {
  background: #DDD6FE;
}

.sent-dot {
  background: #EF4444;
}

.received-dot {
  background: #10B981;
}

.chart-card-body {
  padding: 1.5rem;
  height: 400px;
}

/* Table Cards */
.table-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  transition: all 0.3s ease;
}

.table-card:hover {
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.table-card-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e0e6ed;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.table-card-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.table-card-link {
  color: #8B5CF6;
  font-size: 0.875rem;
  text-decoration: none;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  transition: all 0.3s ease;
}

.table-card-link:hover {
  color: #6D28D9;
  text-decoration: none;
}

.table-card-link i {
  margin-left: 0.25rem;
  transition: transform 0.3s ease;
}

.table-card-link:hover i {
  transform: translateX(4px);
}

.table-card-body {
  padding: 1rem;
}

/* Removed dashboard-scoped table overrides; all vue-good-table styles are global */

/* Modern Table Styles */
.modern-table {
  font-size: 0.875rem;
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: #ffffff;
  border: 1px solid #dee2e6; /* align with Bootstrap table border */
  border-radius: 8px;
  overflow: hidden;
}

.modern-table thead th,
.modern-table.vgt-table thead th {
  background: #f8f9fa !important; /* Bootstrap-like header background */
  color: #212529; /* Bootstrap text color */
  font-weight: 600;
  text-transform: none;
  font-size: 0.8125rem;
  letter-spacing: 0.01em;
  padding: 0.75rem 1rem !important; /* align cell size with demo */
  border-bottom: 2px solid #dee2e6 !important; /* thicker header divider */
}

.modern-table tbody td,
.modern-table.vgt-table tbody td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e9ecef; /* Bootstrap-ish row divider */
  vertical-align: middle;
}

.modern-table tbody tr:nth-child(even) {
  background: #fcfcfd;
}

.modern-table tbody tr:hover {
  background: #f8f9fa; /* match demo hover feel */
}

/* Stock Alert Table Styles - Clean & Minimal */
.stock-alert-table {
  font-size: 0.875rem;
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: #ffffff;
  border: 1px solid #e5e7eb !important;
  border-radius: 8px;
  overflow: hidden;
}

.stock-alert-table thead {
  background: #f9fafb !important;
}

.stock-alert-table thead th {
  background: #f9fafb !important;
  color: #374151 !important;
  font-weight: 600 !important;
  text-transform: none;
  font-size: 0.8125rem !important;
  letter-spacing: 0.01em;
  padding: 0.75rem 1rem !important;
  border-bottom: 1px solid #e5e7eb !important;
}

.stock-alert-table tbody td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
  background: #ffffff;
  transition: background-color 0.2s ease;
}

.stock-alert-table tbody tr:nth-child(even) {
  background: #fcfcfd;
}

.stock-alert-table tbody tr {
  transition: background-color 0.2s ease;
}

.stock-alert-table tbody tr:hover {
  background: #f9fafb;
}

.stock-alert-table tbody tr:last-child td {
  border-bottom: none;
}

.stock-alert-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.375rem 0.75rem;
  background: #fee2e2;
  color: #991b1b;
  font-weight: 600;
  font-size: 0.875rem;
  border-radius: 6px;
  border: 1px solid #fecaca;
  transition: all 0.2s ease;
}

.stock-alert-badge:hover {
  background: #fecaca;
  border-color: #fca5a5;
}

/* Badges */
.badge {
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.75rem;
}

.badge-success {
  background: #d1fae5;
  color: #065f46;
}

.badge-info {
  background: #dbeafe;
  color: #1e40af;
}

.badge-warning {
  background: #fef3c7;
  color: #92400e;
}

.badge-primary {
  background: #e0e7ff;
  color: #3730a3;
}

.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

/* Welcome Card */
.welcome-card {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.welcome-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #8B5CF6 0%, #A78BFA 100%);
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: white;
  margin-bottom: 1.5rem;
}

.welcome-card h3 {
  color: #1f2937;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
  .dashboard-header {
    text-align: center;
  }

  .current-time {
    margin-top: 1rem;
  }

  .stat-card {
    flex-direction: column;
    text-align: center;
  }

  .stat-card-icon {
    margin-right: 0;
    margin-bottom: 1rem;
  }

  .chart-card-header,
  .table-card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .quick-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .quick-wrap .btn {
    flex: 1;
    min-width: auto;
  }
}
</style>
