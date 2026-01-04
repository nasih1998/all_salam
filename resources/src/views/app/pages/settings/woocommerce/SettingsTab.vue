<template>
  <div>
    <validation-observer ref="form">
      <b-form @submit.prevent="onSubmit">
        <b-row>
          <b-col lg="6" md="6" sm="12">
            <validation-provider :name="$t('Store_URL')" :rules="{ required: true, regex: urlPattern }" v-slot="v">
              <b-form-group :label="$t('Store_URL') + ' *'">
                <b-form-input v-model="form.store_url" :state="getState(v)" :placeholder="$t('Enter_Store_URL')" />
                <b-form-invalid-feedback>{{ v.errors[0] }}</b-form-invalid-feedback>
              </b-form-group>
            </validation-provider>
          </b-col>

          <b-col lg="6" md="6" sm="12">
            <validation-provider :name="$t('Consumer_Key')" :rules="{ required: true }" v-slot="v">
              <b-form-group :label="$t('Consumer_Key') + ' *'">
                <b-form-input v-model="form.consumer_key" :state="getState(v)" />
                <b-form-invalid-feedback>{{ v.errors[0] }}</b-form-invalid-feedback>
              </b-form-group>
            </validation-provider>
          </b-col>

          <b-col lg="6" md="6" sm="12">
            <validation-provider :name="$t('Consumer_Secret')" :rules="{ required: true }" v-slot="v">
              <b-form-group :label="$t('Consumer_Secret') + ' *'">
                <b-form-input type="password" v-model="form.consumer_secret" :state="getState(v)" />
                <b-form-invalid-feedback>{{ v.errors[0] }}</b-form-invalid-feedback>
              </b-form-group>
            </validation-provider>
          </b-col>

          <b-col lg="6" md="6" sm="12">
            <b-form-group :label="$t('WP_Username_Optional')">
              <b-form-input v-model="form.wp_username" :placeholder="$t('Enter_WP_Username')" />
              <small class="text-muted">{{ $t('Used_for_media_upload_fallback') }}</small>
            </b-form-group>
          </b-col>

          <b-col lg="6" md="6" sm="12">
            <b-form-group :label="$t('WP_Application_Password_Optional')">
              <b-form-input type="password" v-model="form.wp_app_password" :placeholder="$t('Enter_WP_Application_Password')" />
              <small class="text-muted">{{ $t('Create_from_WordPress_Profile') }}</small>
            </b-form-group>
          </b-col>

          

          

          <b-col lg="6" md="6" sm="12">
            <b-form-group :label="$t('Connection_Status')">
              <div class="d-flex align-items-center">
                <b-badge :variant="connectionBadgeVariant">
                  {{ connectionBadgeText }}
                </b-badge>
                <span class="mini-spinner ml-2" v-if="connecting"></span>
              </div>
            </b-form-group>
          </b-col>

          <b-col lg="12" md="12" sm="12">
            <div class="d-flex flex-wrap align-items-center">
              <b-button variant="primary" type="submit" class="mr-2">
                <i class="i-Yes me-2"></i> {{ $t('Save') }}
              </b-button>

              <b-button variant="outline-success" class="mr-2 d-inline-flex align-items-center" @click="testConnection" :disabled="connecting">
                <template v-if="!connecting">
                  <i class="i-Cloud-Check me-2"></i> {{ $t('Test_Connection') }}
                </template>
                <template v-else>
                  <span class="mini-spinner mr-1"></span>
                  {{ $t('Testing') }}
                </template>
              </b-button>
            </div>
          </b-col>

          <b-col lg="12" md="12" sm="12" class="mt-3" v-if="last_sync_at">
            <b-alert show variant="light">{{ $t('Last_Sync') }}: <strong>{{ lastSyncAtFromNow }}</strong></b-alert>
          </b-col>
        </b-row>
      </b-form>
    </validation-observer>

    <b-card class="mt-3" header="Auto Sync Guide (Stocky → WooCommerce)">
      <b-card-text>
        <p class="mb-2"><strong>What it does</strong></p>
        <ul class="mb-3">
          <li>Products: Pushes products from Stocky to WooCommerce (create/update).</li>
          <li>Stock: Pushes stock quantities/status from Stocky to WooCommerce.</li>
        </ul>

        <p class="mb-2"><strong>How to enable</strong></p>
        <ul class="mb-3">
          <li>Click <em>Save</em> to store your WooCommerce credentials.</li>
          <li>Configure a system cron to run the command below at your desired frequency.</li>
          <li>Optionally run <em>Test Connection</em> to verify connectivity.</li>
        </ul>

        <p class="mb-2"><strong>When it runs</strong></p>
        <ul class="mb-3">
          <li>Controlled by your server cron schedule (see Production setup below).</li>
        </ul>

        <p class="mb-2"><strong>Manual sync (on demand)</strong></p>
        <ul class="mb-3">
          <li>Products tab: <em>Run Manual Sync Now</em> or <em>Sync Only Unsynced</em> (Stocky → WooCommerce).</li>
          <li>Stock tab: start stock sync with progress (queued job).</li>
        </ul>

        <p class="mb-2"><strong>Production cron: run direct Woo sync</strong></p>
        <p class="mb-1">Every minute:</p>
        <pre class="bg-light p-2" style="white-space: pre-wrap; word-break: break-word;">
* * * * * php /path-to-your-project/artisan woocommerce:sync --scope=all --no-interaction >> /dev/null 2>&1
        </pre>
        <p class="mb-1">Hourly:</p>
        <pre class="bg-light p-2" style="white-space: pre-wrap; word-break: break-word;">
0 * * * * php /path-to-your-project/artisan woocommerce:sync --scope=all --no-interaction >> /dev/null 2>&1
        </pre>
        <p class="mb-1">Daily at 02:00:</p>
        <pre class="bg-light p-2" style="white-space: pre-wrap; word-break: break-word;">
0 2 * * * php /path-to-your-project/artisan woocommerce:sync --scope=all --no-interaction >> /dev/null 2>&1
        </pre>
        

        <p class="mb-2"><strong>Notes</strong></p>
        <ul class="mb-0">
          <li>Changing Woo store URL/keys resets mappings so items can re-sync to the new store.</li>
          <li>SKU consistency enables safe relinking without duplicates.</li>
        </ul>
      </b-card-text>
    </b-card>
  </div>
</template>

<script>
import NProgress from 'nprogress';
import moment from 'moment';

export default {
  data() {
    return {
      connecting: false,
      connectionOk: null,
      last_sync_at: null,
      form: {
        store_url: '',
        consumer_key: '',
        consumer_secret: '',
        wp_username: '',
        wp_app_password: '',
      },
      
      urlPattern: /^https?:\/\//,
    };
  },
  computed: {
    lastSyncAtFromNow() {
      return this.last_sync_at ? moment(this.last_sync_at).fromNow() : null;
    },
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
    getState(v) { return v.validated ? v.valid : null; },
    loadSettings() {
      return axios.get('woocommerce/settings').then(({ data }) => {
        if (data.settings) {
          this.form = Object.assign(this.form, data.settings);
          this.last_sync_at = data.settings.last_sync_at;
        }
      });
    },
    onSubmit() {
      this.$refs.form.validate().then(valid => {
        if (!valid) {
          this.toast('danger', this.$t('Please_fill_the_form_correctly'));
          return;
        }
        NProgress.start(); NProgress.set(0.1);
        axios.post('woocommerce/settings', this.form).then(() => {
          this.toast('success', this.$t('Successfully_Updated'));
          NProgress.done();
          this.$emit('updated');
          this.testConnection();
        }).catch(() => {
          this.toast('danger', this.$t('InvalidData'));
          NProgress.done();
        });
      });
    },
    testConnection() {
      this.connecting = true;
      axios.post('woocommerce/test-connection').then(({ data }) => {
        this.connectionOk = !!data.ok;
        this.$emit('connection', this.connectionOk);
        if (data.ok) this.toast('success', this.$t('Connection_successful'));
        else this.toast('danger', this.$t('Connection_failed'));
      }).catch(() => {
        this.connectionOk = false;
        this.$emit('connection', false);
        this.toast('danger', this.$t('Connection_failed'));
      }).finally(() => { this.connecting = false; });
    },
    toast(variant, msg) {
      this.$root.$bvToast.toast(msg, { title: this.$t('WooCommerce'), variant, solid: true });
    }
  },
  created() {
    this.loadSettings().then(() => this.testConnection()).finally(() => { this.$emit('ready'); });
  }
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


