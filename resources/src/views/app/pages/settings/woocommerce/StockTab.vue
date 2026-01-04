<template>
  <div>
    <b-row class="mb-3">
      <b-col md="12">
        <div class="d-flex flex-wrap align-items-center">
          <b-button variant="primary" class="mr-2 mb-2 d-inline-flex align-items-center" @click="syncStock" :disabled="syncing">
            <template v-if="!syncing">{{ $t('Sync_Stock_Now') }}</template>
            <template v-else>
              <span class="mini-spinner mr-1"></span>
              {{ $t('Syncing') }}
            </template>
          </b-button>
          
          <b-button variant="outline-secondary" class="ml-auto mb-2" size="sm" @click="$emit('refreshed')">{{ $t('Refresh') }}</b-button>
        </div>
      </b-col>
    </b-row>

    <b-row class="mb-3">
      <b-col md="4" sm="12" class="mb-2">
        <b-card class="text-center">
          <div class="h5 mb-0">{{ metrics.in_stock }}</div>
          <small>🟢 {{ $t('In_Stock') }}</small>
        </b-card>
      </b-col>
      <b-col md="4" sm="12" class="mb-2">
        <b-card class="text-center">
          <div class="h5 mb-0">{{ metrics.out_stock }}</div>
          <small>🔴 {{ $t('Out_of_Stock') }}</small>
        </b-card>
      </b-col>
      <b-col md="4" sm="12" class="mb-2">
        <b-card class="text-center">
          <div class="h6 mb-0">{{ formatDate(metrics.last_sync) || '-' }}</div>
          <small>🕒 {{ $t('Last_Sync') }}</small>
        </b-card>
      </b-col>
    </b-row>

    <b-row v-if="syncing">
      <b-col md="12">
        <b-progress :value="progress.percentage" :max="100" height="24px" show-progress animated>
          <span class="ml-2">{{ $t('Syncing_Products') }}... {{ progress.percentage }}% ({{ progress.synced_products }} / {{ progress.total_products }})</span>
        </b-progress>
        <div class="mt-2 small text-muted" v-if="progress.failed_products > 0">
          {{ $t('Errors') }}: {{ progress.failed_products }}
          <b-link @click="$emit('view-logs')">{{ $t('View_Logs') }}</b-link>
        </div>
      </b-col>
    </b-row>
  </div>
</template>

<script>
import moment from 'moment';
export default {
  data() {
    return {
      syncing: false,
      token: '',
      poller: null,
      progress: { total_products: 0, synced_products: 0, failed_products: 0, percentage: 0 },
      metrics: { in_stock: 0, out_stock: 0, last_sync: null },
      autoSync: false,
    };
  },
  methods: {
    load() {
      return this.fetchMetrics();
    },
    fetchMetrics() {
      return axios.get('woocommerce/stock-metrics').then(({ data }) => {
        this.metrics = data || { in_stock: 0, out_stock: 0, last_sync: null };
      }).catch(() => { this.metrics = { in_stock: 0, out_stock: 0, last_sync: null }; });
    },
    syncStock() {
      if (this.syncing) return;
      this.syncing = true;
      this.progress = { total_products: 0, synced_products: 0, failed_products: 0, percentage: 0 };
      axios.post('woocommerce/sync/stock').then(({ data }) => {
        if (data.ok && data.token) {
          this.token = data.token;
          this.startPolling();
        } else {
          this.toast('danger', this.$t('Sync_Failed'));
          this.syncing = false;
        }
      }).catch(() => { this.toast('danger', this.$t('Sync_Failed')); this.syncing = false; });
    },
    startPolling() {
      if (this.poller) clearInterval(this.poller);
      this.poller = setInterval(this.fetchProgress, 2000);
      this.fetchProgress();
    },
    fetchProgress() {
      if (!this.token) return;
      axios.get('woocommerce/sync/stock/progress', { params: { token: this.token } }).then(({ data }) => {
        if (data && data.state) {
          this.progress = data.state;
          if (this.progress.finished) {
            clearInterval(this.poller);
            this.poller = null;
            this.syncing = false;
            this.toast('success', this.$t('Sync_Completed'));
            this.fetchMetrics();
            this.$emit('refreshed');
          }
        }
      }).catch(() => {});
    },
    
    toast(variant, msg) { this.$root.$bvToast.toast(msg, { title: this.$t('WooCommerce'), variant, solid: true }); },
    formatDate(v) { return v ? moment(v).format('YYYY-MM-DD HH:mm') : ''; },
  },
  created() { this.load().finally(() => { this.$emit('ready'); }); },
  beforeDestroy() { if (this.poller) clearInterval(this.poller); }
};
</script>


<style scoped>
.mini-spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(0,0,0,0.1);
  border-top-color: rgba(0,0,0,0.4);
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
