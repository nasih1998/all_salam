<template>
  <div>
    <b-row>
      <b-col md="3" sm="6" class="mb-3">
        <b-card class="text-center">
          <div class="mb-2"><b-badge :variant="connectionBadgeVariant">{{ connectionBadgeText }}</b-badge></div>
          <small class="text-muted">{{ $t('Connection_Status') }}</small>
        </b-card>
      </b-col>
      <b-col md="3" sm="6" class="mb-3">
        <b-card class="text-center">
          <div class="h5 mb-0">{{ lastSyncAtFromNow || '—' }}</div>
          <small class="text-muted">{{ $t('Last_Sync_Time') }}</small>
        </b-card>
      </b-col>
      <b-col md="3" sm="6" class="mb-3">
        <b-card class="text-center">
          <div class="h5 mb-0">{{ unsyncedCountDisplay }}</div>
          <small class="text-muted">{{ $t('Unsynced_Product_Count') }}</small>
        </b-card>
      </b-col>
      <b-col md="3" sm="6" class="mb-3">
        <b-card class="text-center">
          <div class="h5 mb-0">{{ syncedProductsDisplay }}</div>
          <small class="text-muted">{{ $t('Synced_Products') }}</small>
        </b-card>
      </b-col>
    </b-row>

    <b-card class="mt-2" header-bg-variant="transparent">
      <template #header>
        <h5 class="mb-0">{{ $t('Last_5_Log_Entries') }}</h5>
      </template>
      <b-table :items="lastFiveLogs" :fields="logFields" small responsive="sm">
        <template #cell(date)="{ item }">{{ formatDate(item.created_at) }}</template>
        <template #cell(action)="{ item }">{{ formatAction(item.action) }}</template>
        <template #cell(direction)="{ item }">{{ formatDirection(item.action) }}</template>
        <template #cell(status)="{ item }">
          <b-badge :variant="levelToVariant(item.level)">{{ formatStatus(item.level) }}</b-badge>
        </template>
      </b-table>
    </b-card>
  </div>
</template>

<script>
import moment from 'moment';

export default {
  data() {
    return {
      connectionOk: null,
      last_sync_at: null,
      totalProducts: null,
      unsyncedCount: null,
      logs: [],
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
    connectionBadgeVariant() { if (this.connectionOk === true) return 'success'; if (this.connectionOk === false) return 'danger'; return 'secondary'; },
    connectionBadgeText() { if (this.connectionOk === true) return this.$t('Connected'); if (this.connectionOk === false) return this.$t('Disconnected'); return this.$t('Unknown'); },
    lastSyncAtFromNow() { return this.last_sync_at ? moment(this.last_sync_at).fromNow() : null; },
    unsyncedCountDisplay() { return this.unsyncedCount != null ? this.unsyncedCount : '—'; },
    syncedProducts() { if (this.totalProducts == null || this.unsyncedCount == null) return null; return Math.max(0, (this.totalProducts || 0) - (this.unsyncedCount || 0)); },
    syncedProductsDisplay() { return this.syncedProducts != null ? this.syncedProducts : '—'; },
    lastFiveLogs() { const list = this.logs.slice(); list.sort((a,b)=>new Date(b.created_at)-new Date(a.created_at)); return list.slice(0,5); },
  },
  methods: {
    load() {
      const p1 = axios.get('woocommerce/settings').then(({ data }) => { if (data.settings) { this.last_sync_at = data.settings.last_sync_at; }});
      const p2 = axios.post('woocommerce/test-connection').then(({ data }) => { this.connectionOk = !!data.ok; }).catch(()=>{ this.connectionOk = false; });
      const p3 = axios.get('products', { params: { limit: 1 } }).then(({ data }) => { this.totalProducts = data.totalRows != null ? data.totalRows : null; });
      const p4 = axios.get('woocommerce/unsynced-count').then(({ data }) => { this.unsyncedCount = data.count; });
      const p5 = axios.get('woocommerce/logs').then(({ data }) => { this.logs = data.data || []; });
      return Promise.all([p1,p2,p3,p4,p5]);
    },
    formatDate(val) { return val ? moment(val).format('YYYY-MM-DD HH:mm') : ''; },
    formatAction(action) { if (!action) return ''; const key = String(action).split('.')[0]; if (key==='products') return this.$t('Product'); if (key==='orders') return this.$t('Order'); if (key==='stock') return this.$t('Stock'); return key; },
    formatDirection(action) { const key = String(action||'').split('.')[0]; if (key==='orders') return this.$t('WooCommerce_to_POS'); return this.$t('POS_to_WooCommerce'); },
    levelToVariant(level) { if (level==='error') return 'danger'; if (level==='warning') return 'warning'; return 'success'; },
    formatStatus(level) { if (level==='error') return this.$t('Failed'); if (level==='warning') return this.$t('Warning'); return this.$t('Success'); },
  },
  created() { this.load().finally(() => { this.$emit('ready'); }); }
};
</script>


