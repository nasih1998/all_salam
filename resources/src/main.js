import store from "./store";

import Vue from "vue";
import router, { setupRouterGuards } from "./router";

import App from "./App.vue";
import Auth from './auth/index.js';
window.auth = new Auth();
import { ValidationObserver, ValidationProvider, extend, localize } from 'vee-validate';
import * as rules from "vee-validate/dist/rules";

localize({
  en: {
    messages: {
      required: 'This field is required',
      required_if: 'This field is required',
      regex: 'This field must be a valid',
      mimes: `This field must have a valid file type.`,
      size: (_, { size }) => `This field size must be less than ${size}.`,
      min: 'This field must have no less than {length} characters',
      max: (_, { length }) => `This field must have no more than ${length} characters`
    }
  },
});
// Install VeeValidate rules and localization
Object.keys(rules).forEach(rule => {
  extend(rule, rules[rule]);
});

// Register it globally
Vue.component("ValidationObserver", ValidationObserver);
Vue.component('ValidationProvider', ValidationProvider);


Vue.component('qrcode-scanner', {
  props: {
    qrbox: {
      type: Number,
      default: 250
    },
    fps: {
      type: Number,
      default: 10
    },
  },
  data() {
    return {
      isFirstScan: true,
      html5QrcodeScanner: null,
    };
  },
  template: `<div id="reader"></div>`, // Use ref instead of id for dynamic rendering

  mounted () {
    this.initializeScanner();
  },
  methods: {
    initializeScanner() {
      const config = {
        fps: this.fps,
        qrbox: this.qrbox,
      };
      this.html5QrcodeScanner = new Html5QrcodeScanner('reader', config); // Use id for dynamic rendering
      this.html5QrcodeScanner.render(this.onScanSuccess);
    },
    onScanSuccess (decodedText, decodedResult) {
      if (this.isFirstScan) {
        this.isFirstScan = false;
        this.$emit('result', decodedText, decodedResult);
      } else {
        this.html5QrcodeScanner.stop();
      }
    },

  },

  beforeDestroy() {
    if (this.html5QrcodeScanner) {
      this.html5QrcodeScanner.clear();
    }
  }

});

import StockyKit from "./plugins/stocky.kit";
Vue.use(StockyKit);
import VueCookies from 'vue-cookies'
Vue.use(VueCookies);

var VueCookie = require('vue-cookie');
Vue.use(VueCookie);

// Register Excel Export Component globally
import ExcelExport from "./components/ExcelExport.vue";
Vue.component('vue-excel-xlsx', ExcelExport);

window.axios = require('axios');
window.axios.defaults.baseURL = '/api/';

window.axios.defaults.withCredentials = true;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ===== Initial load loader control =====
// Track pending axios requests during the first page load to keep the loader visible
window.__axiosPendingCount = 0;
window.__initialLoaderActive = true; // true only during the first SPA boot after a full reload
window.__appReadyToHideLoader = false; // set to true by App.vue when it renders initial route

window.__hideInitialLoaderIfDone = function __hideInitialLoaderIfDone() {
  if (!window.__initialLoaderActive) return;
  if (!window.__appReadyToHideLoader) return;
  if (window.__axiosPendingCount === 0) {
    var el = document.getElementById('loading_wrap');
    if (el) el.style.display = 'none';
    window.__initialLoaderActive = false;
  }
};

function incrementPending(config) {
  if (window.__initialLoaderActive && !(config && config.meta && config.meta.skipInitialLoader)) {
    window.__axiosPendingCount = (window.__axiosPendingCount || 0) + 1;
  }
}
function decrementPending(config) {
  if (window.__initialLoaderActive && !(config && config.meta && config.meta.skipInitialLoader)) {
    window.__axiosPendingCount = Math.max(0, (window.__axiosPendingCount || 0) - 1);
    // Attempt to hide when app signaled readiness and no more pending requests
    window.__hideInitialLoaderIfDone();
  }
}

axios.interceptors.request.use((config) => {
  incrementPending(config);
  return config;
}, (error) => {
  decrementPending(error && error.config);
  return Promise.reject(error);
});

axios.interceptors.response.use((response) => {
  // decrement before returning
  decrementPending(response && response.config);

  return response;
}, (error) => {
  // always decrement
  decrementPending(error && error.config);

  if (error.response && error.response.data) {
    if (error.response.status === 401) {
      window.location.href='/login';
    }

    if (error.response.status === 404) {
      router.push({ name: 'NotFound' });
    }
    if (error.response.status === 403) {
      router.push({ name: 'not_authorize' });
    }

    return Promise.reject(error.response.data);
  }
  return Promise.reject(error.message);
});

import vSelect from 'vue-select'
Vue.component('v-select', vSelect)
import 'vue-select/dist/vue-select.css';

import '@trevoreyre/autocomplete-vue/dist/style.css';

window.Fire = new Vue();

import Breadcumb from "./components/breadcumb";
import VueI18n from 'vue-i18n';
Vue.use(VueI18n);


Vue.component("breadcumb", Breadcumb);

Vue.config.productionTip = true;
Vue.config.silent = true;
Vue.config.devtools = false;

import { loadI18n } from './plugins/i18n.loader';

loadI18n().then(i18n => {
 store.commit('SetDefaultLanguage', { i18n, Language: i18n.locale });
  setupRouterGuards(i18n); // ✅ inject into router

  new Vue({
    store,
    router,
    VueCookie,
    i18n, // vue-i18n will inject $i18n to all components
    render: h => h(App),
  }).$mount("#app");
});

  
