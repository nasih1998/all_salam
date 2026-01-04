<template>
  <div class="main-content">
    <breadcumb :page="$t('Assets_List')" :folder="$t('Assets')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <div v-else class="page-wrapper">
      <div class="control-bar">
        <div class="control-right">
          <router-link to="/app/assets/store" class="btn btn-primary btn-sm">
            <i class="i-Add mr-1"></i>{{ $t('Add') }}
          </router-link>
        </div>
      </div>

      <div class="table-card">
        <vue-good-table
          mode="remote"
          :columns="columns"
          :totalRows="totalRows"
          :rows="assets"
          @on-page-change="onPageChange"
          @on-per-page-change="onPerPageChange"
          @on-sort-change="onSortChange"
          @on-search="onSearch"
          :search-options="{ enabled: true, placeholder: $t('Search_this_table') }"
          :pagination-options="{ enabled: true, mode: 'records' }"
          styleClass="tableOne vgt-table">

          <template slot="table-row" slot-scope="props">
            <span v-if="props.column.field == 'actions'">
              <router-link :to="'/app/assets/edit/' + props.row.id" class="btn btn-sm btn-outline-primary mr-2">
                <i class="i-Edit"></i>
              </router-link>
              <button class="btn btn-sm btn-outline-danger" @click="removeAsset(props.row.id)">
                <i class="i-Close-Window"></i>
              </button>
            </span>
          </template>

        </vue-good-table>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AssetsIndex',
  data() {
    return {
      isLoading: true,
      assets: [],
      totalRows: 0,
      serverParams: {
        columnFilters: {},
        sort: { field: 'id', type: 'desc' },
        page: 1,
        perPage: 10,
        searchTerm: ''
      },
      columns: [
        { label: this.$t('Tag'), field: 'tag' },
        { label: this.$t('Name'), field: 'name' },
        { label: this.$t('Category'), field: 'asset_category_name' },
        { label: this.$t('Serial'), field: 'serial_number' },
        { label: this.$t('Status'), field: 'status' },
        { label: this.$t('Warehouse'), field: 'warehouse_name' },
        { label: this.$t('Actions'), field: 'actions', sortable: false }
      ]
    }
  },
  mounted() {
    this.getAssets();
  },
  methods: {
    async getAssets() {
      this.isLoading = true;
      const params = {
        page: this.serverParams.page,
        limit: this.serverParams.perPage,
        SortField: this.serverParams.sort.field,
        SortType: this.serverParams.sort.type,
        search: this.serverParams.searchTerm
      };
      try {
        const { data } = await axios.get('assets', { params });
        this.assets = data.assets;
        this.totalRows = data.totalRows;
      } finally {
        this.isLoading = false;
      }
    },
    onPageChange({ currentPage }) {
      this.serverParams.page = currentPage;
      this.getAssets();
    },
    onPerPageChange({ currentPerPage }) {
      this.serverParams.perPage = currentPerPage;
      this.getAssets();
    },
    onSortChange(params) {
      this.serverParams.sort.field = params[0].field;
      this.serverParams.sort.type = params[0].type;
      this.getAssets();
    },
    onSearch(params) {
      this.serverParams.searchTerm = params.searchTerm;
      this.getAssets();
    },
    async removeAsset(id) {
      const ok = await this.$bvModal.msgBoxConfirm(this.$t('AreYouSure'), { size: 'sm' });
      if (!ok) return;
      await axios.delete(`assets/${id}`);
      this.getAssets();
    }
  }
}
</script>


