<template>
  <div>
    <b-row class="mb-3">
      <b-col md="12">
        <div class="d-flex flex-wrap align-items-center">
          <b-button variant="outline-primary" class="mr-2 mb-2" @click="syncOrders" :disabled="syncing">{{ $t('Sync_Orders') }}</b-button>
        </div>
      </b-col>
    </b-row>

    <b-card :header="$t('Last_Synced_Orders')" class="mb-3">
      <b-table :items="orders" :fields="fields" small responsive="sm">
        <template #cell(date)="{ item }">{{ item.date }}</template>
        <template #cell(ref)="{ item }">{{ item.Ref }}</template>
        <template #cell(total)="{ item }">{{ item.GrandTotal }}</template>
        <template #cell(customer)="{ item }">{{ item.client_name }}</template>
        <template #cell(status)="{ item }">{{ item.statut }}</template>
      </b-table>
    </b-card>
  </div>
</template>

<script>
export default {
  data() {
    return {
      syncing: false,
      orders: [],
      fields: [
        { key: 'date', label: this.$t('date') },
        { key: 'Ref', label: this.$t('Ref') },
        { key: 'client_name', label: this.$t('Customer') },
        { key: 'GrandTotal', label: this.$t('Total') },
        { key: 'statut', label: this.$t('Status') },
      ],
    };
  },
  methods: {
    load() {
      // Use existing sales index with search on WC- prefix to approximate last synced orders
      return axios.get('sales', { params: { limit: 5, page: 1, SortField: 'id', SortType: 'desc', search: 'WC-' } })
        .then(({ data }) => { this.orders = (data.sales || []).slice(0, 5); });
    },
    syncOrders() {
      this.syncing = true;
      axios.post('woocommerce/sync/orders').then(({ data }) => {
        if (data.ok) this.toast('success', this.$t('Sync_Completed')); else this.toast('danger', this.$t('Sync_Failed'));
      }).catch(() => { this.toast('danger', this.$t('Sync_Failed')); })
      .finally(() => { this.syncing = false; this.load(); this.$emit('refreshed'); });
    },
    toast(variant, msg) { this.$root.$bvToast.toast(msg, { title: this.$t('WooCommerce'), variant, solid: true }); },
  },
  created() { this.load().finally(() => { this.$emit('ready'); }); }
};
</script>


