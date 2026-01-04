<template>
  <!-- NEW FEATURE - SAFE ADDITION -->
  <div class="main-content">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div>
        <h4 class="mb-1">{{ $t('Balance_Sheet_Title') }}</h4>
        <div class="text-muted small">{{ $t('Balance_Sheet_Subtitle') }}</div>
      </div>
    </div>

    <div class="card p-3 mb-3">
      <div class="row align-items-end">
        <div class="col-md-6 mb-2">
          <label class="small text-muted mb-1">{{ $t('As_Of') }}</label>
          <input v-model="to" type="date" class="form-control" />
        </div>
        <div class="col-md-6 mb-2 text-right">
          <button class="btn btn-outline-primary" @click="fetch"><i class="i-Reload"></i> {{ $t('Refresh') }}</button>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4">
        <div class="card p-3 mb-3 kpi">
          <div class="text-muted small">{{ $t('Assets') }}</div>
          <div class="h4 mb-0">{{ toMoney(data.assets) }}</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3 mb-3 kpi">
          <div class="text-muted small">{{ $t('Liabilities') }}</div>
          <div class="h4 mb-0">{{ toMoney(data.liabilities) }}</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3 mb-3 kpi">
          <div class="text-muted small">{{ $t('Equity') }}</div>
          <div class="h4 mb-0">{{ toMoney(data.equity) }}</div>
        </div>
      </div>
    </div>

    <div class="card p-3 text-right" :class="Math.abs(data.balance) < 0.01 ? 'border-success' : 'border-warning'">
      <span class="mr-3">{{ $t('Balance_Check') }}</span> <strong>{{ toMoney(data.balance) }}</strong>
    </div>
  </div>
</template>

<script>

export default {
  name: "BalanceSheetV2",
  data() {
    return { data: { assets: 0, liabilities: 0, equity: 0, balance: 0 }, to: "" };
  },
  created() { this.fetch(); },
  methods: {
    async fetch() {
      try {
        const { data } = await axios.get("/accounting/v2/reports/balance-sheet", { params: { to: this.to || undefined } });
        this.data = data || this.data;
      } catch (e) {}
    },
    toMoney(v) {
      const n = parseFloat(v || 0);
      return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
  }
};
</script>

<style scoped>
.kpi { border-left: 4px solid #663399; }
</style>



