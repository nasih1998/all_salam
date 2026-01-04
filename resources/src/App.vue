<template>
  <div v-if="Loading">
    <router-view></router-view>


    <customizer v-if="show_language && !isPosPage"></customizer>
  </div>
</template>


<script>
import { mapActions, mapGetters } from "vuex";

export default {
  data() {
    return {
      Loading:false,
    };
  },
  computed: {
    
    ...mapGetters(["getThemeMode","isAuthenticated","show_language","currentUser"]),
    themeName() {
      return this.getThemeMode.dark ? "dark-theme" : " ";
    },
    rtl() {
      return this.getThemeMode.rtl ? "rtl" : " ";
    },

    isPosPage() {
      return this.$route.path === '/app/pos';
    },
    titleTemplate() {
      return `%s | ${this.currentUser?.page_title_suffix || "Ultimate Inventory With POS"}`;
    }
  },

  metaInfo() {
    return {
      // if no subcomponents specify a metaInfo.title, this title will be used
      title: "Stocky",
      titleTemplate: this.titleTemplate,

      bodyAttrs: {
        class: [this.themeName, "text-left"]
      },
      htmlAttrs: {
        dir: this.rtl
      },
      
    };
  },
methods:{
    ...mapActions([
      "refreshUserPermissions",
    ]),
    async initializeApp() {
      try {
        // Ensure initial permissions and user info are fetched
        await this.refreshUserPermissions(this.$i18n);
      } catch (e) {
        // ignore; guards/interceptors will handle routing on auth errors
      } finally {
        this.Loading = true;
        // Signal that the app rendered initial route and is allowed to hide loader when no pending requests
        if (window) {
          window.__appReadyToHideLoader = true;
          if (typeof window.__hideInitialLoaderIfDone === 'function') {
            window.__hideInitialLoaderIfDone();
          }
        }
      }
    }
},

beforeMount() {
        // Replace timeout with awaited initialization
        this.initializeApp();
  }

};
</script>

