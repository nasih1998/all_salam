<template>
  <div>
    <b-row class="mb-3">
      <b-col md="3" sm="6" class="mb-2">
        <b-form-group :label="$t('Action')">
          <v-select :options="actionOptions" :reduce="o => o.value" v-model="filterAction" :clearable="false"/>
        </b-form-group>
      </b-col>
      <b-col md="3" sm="6" class="mb-2">
        <b-form-group :label="$t('Status')">
          <v-select :options="statusOptions" :reduce="o => o.value" v-model="filterStatus" :clearable="false"/>
        </b-form-group>
      </b-col>
      <b-col md="3" sm="6" class="mb-2">
        <b-form-group :label="$t('From')">
          <b-form-input type="date" v-model="filterFrom" />
        </b-form-group>
      </b-col>
      <b-col md="3" sm="6" class="mb-2">
        <b-form-group :label="$t('To')">
          <b-form-input type="date" v-model="filterTo" />
        </b-form-group>
      </b-col>
    </b-row>

    <div class="d-flex align-items-center justify-content-between mb-2">
      <div>
        <b-button size="sm" variant="outline-secondary" class="mr-2" @click="load">{{ $t('Refresh') }}</b-button>
      </div>
      <div>
        <b-button size="sm" variant="danger" @click="clearLogs" :disabled="processing">{{ $t('Clear_Logs') }}</b-button>
      </div>
    </div>

    <b-table :items="pagedLogs" :fields="logFields" small responsive="sm">
      <template #cell(date)="{ item }">{{ formatDate(item.created_at) }}</template>
      <template #cell(action)="{ item }">{{ formatAction(item.action) }}</template>
      <template #cell(direction)="{ item }">{{ formatDirection(item.action) }}</template>
      <template #cell(status)="{ item }">
        <b-badge :variant="levelToVariant(item.level)">{{ formatStatus(item.level) }}</b-badge>
      </template>
    </b-table>
    <div class="d-flex justify-content-end mt-2">
      <b-pagination v-model="currentLogPage" :total-rows="filteredLogs.length" :per-page="logsPerPage" size="sm" align="right"/>
    </div>
  </div>
</template>

<script>
import moment from 'moment';

export default {
  data() {
    return {
      processing: false,
      logs: [],
      filterAction: 'all',
      filterStatus: 'all',
      filterFrom: '',
      filterTo: '',
      currentLogPage: 1,
      logsPerPage: 10,
      actionOptions: [
        { label: this.$t('All'), value: 'all' },
        { label: this.$t('Product'), value: 'products' },
        { label: this.$t('Stock'), value: 'stock' },
        { label: this.$t('Order'), value: 'orders' },
      ],
      statusOptions: [
        { label: this.$t('All'), value: 'all' },
        { label: this.$t('Success'), value: 'info' },
        { label: this.$t('Warning'), value: 'warning' },
        { label: this.$t('Failed'), value: 'error' },
      ],
      logFields: [
        { key: 'date', label: this.$t('date') },
        { key: 'action', label: this.$t('Action') },
        { key: 'direction', label: this.$t('Direction') },
        { key: 'status', label: this.$t('Status') },
        { key: 'message', label: this.$t('Message') },
      ],
    };
  },
  computed: {
    filteredLogs() {
      let out = Array.isArray(this.logs) ? this.logs.slice() : [];
      if (this.filterAction !== 'all') out = out.filter(l => (l.action || '').startsWith(this.filterAction));
      if (this.filterStatus !== 'all') out = out.filter(l => (l.level || '') === this.filterStatus);
      if (this.filterFrom) {
        const from = new Date(this.filterFrom + 'T00:00:00');
        out = out.filter(l => new Date(l.created_at) >= from);
      }
      if (this.filterTo) {
        const to = new Date(this.filterTo + 'T23:59:59');
        out = out.filter(l => new Date(l.created_at) <= to);
      }
      out.sort((a,b) => new Date(b.created_at) - new Date(a.created_at));
      return out;
    },
    pagedLogs() {
      const start = (this.currentLogPage - 1) * this.logsPerPage;
      return this.filteredLogs.slice(start, start + this.logsPerPage);
    },
  },
  methods: {
    load() { return axios.get('woocommerce/logs').then(({ data }) => { this.logs = data.data || []; }); },
    clearLogs() {
      this.processing = true;
      axios.delete('woocommerce/logs').then(() => { this.toast('success', this.$t('Successfully_Updated')); this.load(); })
      .catch(() => { this.toast('danger', this.$t('Not_Available')); })
      .finally(() => { this.processing = false; });
    },
    formatDate(val) { return val ? moment(val).format('YYYY-MM-DD HH:mm') : ''; },
    formatAction(action) { if (!action) return ''; const key = String(action).split('.')[0]; if (key==='products') return this.$t('Product'); if (key==='orders') return this.$t('Order'); if (key==='stock') return this.$t('Stock'); return key; },
    formatDirection(action) { const key = String(action||'').split('.')[0]; if (key==='orders') return this.$t('WooCommerce_to_POS'); return this.$t('POS_to_WooCommerce'); },
    levelToVariant(level) { if (level==='error') return 'danger'; if (level==='warning') return 'warning'; return 'success'; },
    formatStatus(level) { if (level==='error') return this.$t('Failed'); if (level==='warning') return this.$t('Warning'); return this.$t('Success'); },
    toast(variant, msg) { this.$root.$bvToast.toast(msg, { title: this.$t('WooCommerce'), variant, solid: true }); },
  },
  created() { this.load().finally(() => { this.$emit('ready'); }); }
};
</script>


