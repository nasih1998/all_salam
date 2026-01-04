<template>
  <div class="main-content">
    <breadcumb :page="$t('WooCommerce_Settings')" :folder="$t('Settings')"/>
    <div v-if="loading" class="loading_page spinner spinner-primary mr-3"></div>

    <div v-else>
      <b-card no-body>
        <div class="d-flex align-items-center justify-content-between px-3 pt-3">
          <div>
            <b-badge :variant="connectionBadgeVariant">{{ connectionBadgeText }}</b-badge>
          </div>
          <div>
            <b-button variant="danger" size="sm" :disabled="resetting" @click="resetSync">
              <span v-if="!resetting">{{ $t('Reset_Sync_State') }}</span>
              <span v-else>{{ $t('Resetting') }}...</span>
            </b-button>
          </div>
        </div>
        <b-tabs v-model="activeTab" @input="onTabChange" content-class="mt-3 position-relative">
          <div v-if="tabLoading" class="loading_page spinner spinner-primary mr-3"></div>
          <b-tab lazy>
            <template #title>{{ $t('Settings') }}</template>
            <div v-show="!tabLoading">
              <SettingsTab @ready="onTabReady" @connection="onConnectionUpdate" @updated="onChildRefreshed" />
            </div>
          </b-tab>
          <b-tab lazy>
            <template #title>
              {{ $t('Products') }}
              <b-badge variant="warning" v-if="unsyncedCount !== null" class="ml-2">{{ unsyncedCount }}</b-badge>
            </template>
            <div v-show="!tabLoading">
              <ProductsTab @ready="onTabReady" @refreshed="onChildRefreshed" />
            </div>
          </b-tab>
          <b-tab lazy>
            <template #title>{{ $t('Stock') }}</template>
            <div v-show="!tabLoading">
              <StockTab @ready="onTabReady" @refreshed="onChildRefreshed" @view-logs="switchToLogs" />
            </div>
          </b-tab>
          <b-tab lazy>
            <template #title>{{ $t('Categories') }}</template>
            <div v-show="!tabLoading">
              <CategoriesTab @ready="onTabReady" @refreshed="onChildRefreshed" />
            </div>
          </b-tab>
          <b-tab lazy>
            <template #title>{{ $t('View_Logs') }}</template>
            <div v-show="!tabLoading">
              <LogsTab @ready="onTabReady" />
            </div>
          </b-tab>
          <b-tab lazy>
            <template #title>{{ $t('Status_Overview') }}</template>
            <div v-show="!tabLoading">
              <StatusOverviewTab @ready="onTabReady" />
            </div>
          </b-tab>
        </b-tabs>
      </b-card>

      <!-- Log details viewer -->
      <b-modal id="log-detail" v-model="selectedLog" :title="$t('Log_Details')" hide-footer>
        <div v-if="selectedLog">
          <p class="mb-1"><strong>{{ $t('date') }}:</strong> {{ formatDate(selectedLog.created_at) }}</p>
          <p class="mb-1"><strong>{{ $t('Action') }}:</strong> {{ selectedLog.action }}</p>
          <p class="mb-1"><strong>{{ $t('Level') }}:</strong> {{ selectedLog.level }}</p>
          <p class="mb-1"><strong>{{ $t('Message') }}:</strong> {{ selectedLog.message }}</p>
          <hr>
          <p class="mb-1"><strong>{{ $t('Context') }}</strong></p>
          <pre class="bg-light p-2" style="white-space: pre-wrap; word-break: break-word; max-height: 400px; overflow: auto;">{{ stringify(selectedLog.context) }}</pre>
        </div>
      </b-modal>
    </div>
  </div>
</template>

<script>
export default {
  metaInfo: { title: 'WooCommerce Settings' },
  components: {
    SettingsTab: () => import(/* webpackChunkName: "woo-settings-tab" */ './woocommerce/SettingsTab.vue'),
    ProductsTab: () => import(/* webpackChunkName: "woo-products-tab" */ './woocommerce/ProductsTab.vue'),
    StockTab: () => import(/* webpackChunkName: "woo-stock-tab" */ './woocommerce/StockTab.vue'),
    CategoriesTab: () => import(/* webpackChunkName: "woo-categories-tab" */ './woocommerce/CategoriesTab.vue'),
    LogsTab: () => import(/* webpackChunkName: "woo-logs-tab" */ './woocommerce/LogsTab.vue'),
    StatusOverviewTab: () => import(/* webpackChunkName: "woo-status-tab" */ './woocommerce/StatusOverviewTab.vue'),
  },
  data() {
    return {
      loading: true,
      connectionOk: null,
      totalProducts: null,
      unsyncedCount: null,
      activeTab: 0,
      tabLoading: true,
      resetting: false,
    };
  },
  computed: {
    connectionBadgeVariant() {
      if (this.connectionOk === true) return 'success';
      if (this.connectionOk === false) return 'danger';
      return 'secondary';
    },
    connectionBadgeText() {
      if (this.connectionOk === true) return this.$t('Connected');
      if (this.connectionOk === false) return this.$t('Disconnected');
      return this.$t('Unknown');
    },
  },
  methods: {
    onTabChange() { this.tabLoading = true; },
    onTabReady() {
      this.tabLoading = false;
    },
    switchToLogs() {
      // Find index of Logs tab (after Categories)
      // Tabs order: Settings=0, Products=1, Stock=2, Categories=3, Logs=4, Status=5
      this.activeTab = 4;
    },
    fetchCounts() {
      axios.get('products', { params: { limit: 1 } }).then(({ data }) => {
        this.totalProducts = data.totalRows != null ? data.totalRows : null;
      }).catch(() => {
        this.totalProducts = null;
      });

      axios.get('woocommerce/unsynced-count').then(({ data }) => {
        this.unsyncedCount = data.count;
      }).catch(() => {
        this.unsyncedCount = null;
      }).finally(() => {
        this.loading = false;
      });
    },
    testConnection() {
      axios.post('woocommerce/test-connection').then(({ data }) => {
        this.connectionOk = !!data.ok;
      }).catch(() => {
        this.connectionOk = false;
      });
    },
    onConnectionUpdate(val) {
      this.connectionOk = val;
    },
    onChildRefreshed() {
      this.fetchCounts();
      this.testConnection();
    },
    resetSync() {
      if (this.resetting) return;
      this.resetting = true;
      axios.post('woocommerce/reset-sync')
        .then(() => {
          this.$root.$bvToast.toast(this.$t('Successfully_Updated'), { title: this.$t('WooCommerce'), variant: 'success', solid: true });
        })
        .catch(() => {
          this.$root.$bvToast.toast(this.$t('Sync_Failed'), { title: this.$t('WooCommerce'), variant: 'danger', solid: true });
        })
        .finally(() => {
          this.resetting = false;
          this.onChildRefreshed();
        });
    },
  },
  created() {
    this.fetchCounts();
    this.testConnection();
  }
};
</script>


