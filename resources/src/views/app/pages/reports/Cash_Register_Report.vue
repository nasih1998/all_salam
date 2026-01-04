<template>
  <div class="main-content">
    <breadcumb :page="$t('Cash_Register_Report')" :folder="$t('Reports')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <b-card class="wrapper" v-if="!isLoading">
      <div class="row align-items-end">
        <div class="col-md-3 mb-2">
          <label class="mb-1 d-block text-muted">{{$t('DateRange')}}</label>
          <date-range-picker
            v-model="dateRange"
            :locale-data="locale"
            :autoApply="true"
            :showDropdowns="true"
            :opens="'right'"
            :drops="'down'"
            :parentEl="'body'"
            :linkedCalendars="false"
            @update="onDateRangeUpdate"
          >
            <template v-slot:input="picker">
              <b-button variant="light" class="btn-pill">
                <i class="i-Calendar-4 mr-1"></i>
                {{ fmt(picker.startDate) }} - {{ fmt(picker.endDate) }}
              </b-button>
            </template>
          </date-range-picker>
        </div>
        <div class="col-md-3 mb-2">
          <label>{{$t('Cashier')}}</label>
          <b-form-select v-model="filters.user_id" :options="userOptions"></b-form-select>
        </div>
        <div class="col-md-3 mb-2">
          <label>{{$t('warehouse')}}</label>
          <b-form-select v-model="filters.warehouse_id" :options="warehouseOptions"></b-form-select>
        </div>
        <div class="col-md-2 mb-2">
          <label>{{$t('Status')}}</label>
          <b-form-select v-model="filters.status" :options="statusOptions"></b-form-select>
        </div>
      </div>

      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="rows"
        :search-options="{ enabled: true, placeholder: $t('Search_this_table') }"
        :pagination-options="{ enabled: true, mode: 'records', nextLabel: 'next', prevLabel: 'prev' }"
        styleClass="tableOne table-hover vgt-table mt-3"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-search="onSearch"
      >
        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field === 'cashier'">
            {{ formatCashier(props.row) }}
          </span>
          <span v-else-if="props.column.field === 'warehouse'">
            {{ props.row.warehouse_name }}
          </span>
          <span v-else-if="props.column.field === 'opened_at' || props.column.field === 'closed_at'">
            {{ formatDate(props.row[props.column.field]) }}
          </span>
          <span v-else-if="['opening_balance','cash_in','cash_out','total_sales','closing_balance','difference'].includes(props.column.field)">
            {{ formatMoney(props.row[props.column.field]) }}
          </span>
          <span v-else-if="props.column.field === 'status'">
            <span class="badge" :class="props.row.status === 'open' ? 'badge-success' : 'badge-danger'">{{ props.row.status }}</span>
          </span>
          <span v-else>
            {{ props.formattedRow[props.column.field] }}
          </span>
        </template>
      </vue-good-table>
    </b-card>
  </div>
  
</template>

<script>
import NProgress from 'nprogress'
import DateRangePicker from 'vue2-daterange-picker'
import 'vue2-daterange-picker/dist/vue2-daterange-picker.css'

export default {
  metaInfo: { title: 'Cash Register Report' },
  components: { 'date-range-picker': DateRangePicker },
  data() {
    return {
      isLoading: true,
      serverParams: {
        sort: { field: 'opened_at', type: 'desc' },
        page: 1,
        perPage: 10,
        searchTerm: ''
      },
      totalRows: 0,
      rows: [],
      userOptions: [{ value: '', text: this.$t('All') }],
      warehouseOptions: [{ value: '', text: this.$t('All') }],
      statusOptions: [
        { value: '', text: this.$t('All') },
        { value: 'open', text: 'Open' },
        { value: 'closed', text: 'Closed' }
      ],
      filters: { user_id: '', warehouse_id: '', status: '' },
      dateRange: { startDate: new Date(new Date().setDate(new Date().getDate()-6)), endDate: new Date() },
      locale: {
        Label: this.$t('Apply') || 'Apply',
        cancelLabel: this.$t('Cancel') || 'Cancel',
        weekLabel: 'W',
        customRangeLabel: this.$t('CustomRange') || 'Custom Range',
        daysOfWeek: ['Su','Mo','Tu','We','Th','Fr','Sa'],
        monthNames: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        firstDay: 1,
      },
    }
  },
  computed: {
    columns() {
      return [
        { label: this.$t('Cashier'), field: 'cashier', thClass: 'text-left', tdClass: 'text-left', sortable: false },
        { label: this.$t('warehouse'), field: 'warehouse', thClass: 'text-left', tdClass: 'text-left', sortable: false },
        { label: this.$t('Opened'), field: 'opened_at', thClass: 'text-left', tdClass: 'text-left', sortable: true },
        { label: this.$t('Opening'), field: 'opening_balance', type: 'decimal', thClass: 'text-right', tdClass: 'text-right', sortable: true },
        { label: this.$t('Cash In'), field: 'cash_in', type: 'decimal', thClass: 'text-right', tdClass: 'text-right', sortable: true },
        { label: this.$t('Cash Out'), field: 'cash_out', type: 'decimal', thClass: 'text-right', tdClass: 'text-right', sortable: true },
        { label: this.$t('TotalSales'), field: 'total_sales', type: 'decimal', thClass: 'text-right', tdClass: 'text-right', sortable: true },
        { label: this.$t('Closed'), field: 'closed_at', thClass: 'text-left', tdClass: 'text-left', sortable: true },
        { label: this.$t('Closing'), field: 'closing_balance', type: 'decimal', thClass: 'text-right', tdClass: 'text-right', sortable: true },
        { label: this.$t('Difference'), field: 'difference', type: 'decimal', thClass: 'text-right', tdClass: 'text-right', sortable: true },
        { label: this.$t('Status'), field: 'status', thClass: 'text-left', tdClass: 'text-left', sortable: true },
      ]
    }
  },
  created() {
    this.bootstrapFilters().finally(() => {
      this.getData(1)
    })
  },
  methods: {
    fmt(d) { try { return new Date(d).toISOString().slice(0,10) } catch(e) { return '' } },
    onDateRangeUpdate() { this.getData(1) },
    bootstrapFilters() {
      // Load from the same endpoint (like sales_report) to avoid extra requests
      return this.getData(1, true)
    },
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps)
    },
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage })
        this.getData(currentPage)
      }
    },
    onPerPageChange({ currentPerPage }) {
      if (this.serverParams.perPage !== currentPerPage) {
        this.updateParams({ page: 1, perPage: currentPerPage })
        this.getData(1)
      }
    },
    onSortChange(params) {
      if (!params || !params[0]) return
      const { field, type } = params[0]
      this.updateParams({ sort: { field, type } })
      this.getData(1)
    },
    onSearch(value) {
      this.updateParams({ searchTerm: value })
      this.getData(1)
    },
    getData(page = 1, preloadOnly = false) {
      NProgress.start(); NProgress.set(0.1)
      this.isLoading = true
      const params = {
        page: page,
        limit: this.serverParams.perPage,
        SortField: this.serverParams.sort.field,
        SortType: this.serverParams.sort.type,
        search: this.serverParams.searchTerm,
        user_id: this.filters.user_id || undefined,
        warehouse_id: this.filters.warehouse_id || undefined,
        status: this.filters.status || undefined,
        from: this.fmt(this.dateRange.startDate),
        to: this.fmt(this.dateRange.endDate),
      }
      return axios.get('report/cash_registers', { params }).then(res => {
        // Mirrors sales_report payload style
        const payload = res.data || {}
        const data = payload.registers || []
        if (!preloadOnly) {
          this.rows = data
          this.totalRows = payload.totalRows || data.length
        }
        // Preload users & warehouses for filters
        if (Array.isArray(payload.users)) {
          const users = payload.users.map(x => ({ value: x.id, text: (x.firstname && x.lastname) ? (x.firstname + ' ' + x.lastname) : (x.username || x.name || ('User #' + x.id)) }))
          this.userOptions = [{ value: '', text: this.$t('All') }, ...users]
        }
        if (Array.isArray(payload.warehouses)) {
          const warehouses = payload.warehouses.map(x => ({ value: x.id, text: x.name }))
          this.warehouseOptions = [{ value: '', text: this.$t('All') }, ...warehouses]
        }
      }).catch(() => {
        if (this.$bvToast && this.$bvToast.toast) this.$bvToast.toast(this.$t('OperationFailed'), { title: this.$t('Failed'), variant: 'danger', solid: true })
      }).finally(() => {
        this.isLoading = false
        setTimeout(() => NProgress.done(), 300)
      })
    },
    formatMoney(x) {
      const n = parseFloat(x || 0)
      return n.toFixed(2)
    },
    formatDate(x) {
      if (!x) return '-'
      const s = String(x)
      if (s.includes('T')) {
        // Avoid timezone shifts: show as plain DB timestamp
        return s.replace('T', ' ').replace('Z', '').slice(0, 19)
      }
      return s
    },
    formatCashier(row) {
      const first = row.cashier_firstname || ''
      const last = row.cashier_lastname || ''
      const full = [first, last].filter(Boolean).join(' ')
      return full || row.cashier_username || row.cashier_name || ('User #' + row.cashier_id)
    },
    resetFilters() {
      this.filters = { user_id: '', warehouse_id: '', status: '' }
      this.dateRange = { startDate: new Date(new Date().setDate(new Date().getDate()-6)), endDate: new Date() }
      this.getData(1)
    }
  }
}
</script>

<style scoped>
</style>




