<template>
  <div>
    <b-row>
      <b-col md="3" sm="6" class="mb-3">
        <b-card class="text-center">
          <div class="h5 mb-0">{{ totalProductsDisplay }}</div>
          <small class="text-muted">{{ $t('Total_Products') }}</small>
        </b-card>
      </b-col>
      <b-col md="3" sm="6" class="mb-3">
        <b-card class="text-center">
          <div class="h5 mb-0">{{ syncedProductsDisplay }}</div>
          <small class="text-muted">{{ $t('Synced_Products') }}</small>
        </b-card>
      </b-col>
      <b-col md="3" sm="6" class="mb-3">
        <b-card class="text-center">
          <div class="h5 mb-0">{{ unsyncedCountDisplay }}</div>
          <small class="text-muted">{{ $t('Not_Synced') }}</small>
        </b-card>
      </b-col>
      <b-col md="3" sm="6" class="mb-3">
        <b-card class="text-center">
          <div class="h5 mb-0">{{ lastSyncAtFromNow || '—' }}</div>
          <small class="text-muted">{{ $t('Last_Sync') }}</small>
        </b-card>
      </b-col>
    </b-row>

    <b-row class="mb-3">
      <b-col md="12">
        <div class="d-flex flex-wrap align-items-center">
          <b-button variant="info" class="mr-2 mb-2 d-inline-flex align-items-center" @click="manualSync(false)" :disabled="syncing">
            <template v-if="!syncing">
              {{ $t('Run_Manual_Sync_Now') }}
            </template>
            <template v-else>
              <span class="mini-spinner mr-1"></span>
              {{ $t('Syncing') }}
            </template>
          </b-button>
         
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
      last_sync_at: null,
      totalProducts: null,
      unsyncedCount: null,
    };
  },
  computed: {
    lastSyncAtFromNow() { return this.last_sync_at ? moment(this.last_sync_at).fromNow() : null; },
    totalProductsDisplay() { return this.totalProducts != null ? this.totalProducts : '—'; },
    unsyncedCountDisplay() { return this.unsyncedCount != null ? this.unsyncedCount : '—'; },
    syncedProducts() { if (this.totalProducts == null || this.unsyncedCount == null) return null; return Math.max(0, (this.totalProducts || 0) - (this.unsyncedCount || 0)); },
    syncedProductsDisplay() { return this.syncedProducts != null ? this.syncedProducts : '—'; },
    unsyncedAvailable() { return this.unsyncedCount != null && this.unsyncedCount > 0; },
  },
  methods: {
    load() {
      const p1 = axios.get('woocommerce/settings').then(({ data }) => { if (data.settings) this.last_sync_at = data.settings.last_sync_at; });
      const p2 = axios.get('products', { params: { limit: 1 } }).then(({ data }) => { this.totalProducts = data.totalRows != null ? data.totalRows : null; });
      const p3 = axios.get('woocommerce/unsynced-count').then(({ data }) => { this.unsyncedCount = data.count; });
      return Promise.all([p1,p2,p3]);
    },
    manualSync(onlyUnsynced) {
      this.syncing = true;
      let url = 'woocommerce/sync/products?mode=push';
      if (onlyUnsynced) url += '&only_unsynced=1';
      axios.post(url).then(({ data }) => {
        if (data.ok) this.toast('success', this.$t('Sync_Completed')); else this.toast('danger', this.$t('Sync_Failed'));
      }).catch(() => {
        this.toast('danger', this.$t('Sync_Failed'));
      }).finally(() => {
        this.syncing = false;
        this.load();
        this.$emit('refreshed');
      });
    },
    toast(variant, msg) { this.$root.$bvToast.toast(msg, { title: this.$t('WooCommerce'), variant, solid: true }); },
  },
  created() { this.load().finally(() => { this.$emit('ready'); }); }
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


