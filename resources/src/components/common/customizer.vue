<template>
  <div>
    <div class="customizer" :class="{ open: isOpen }">
      <div class="handle" @click="isOpen = !isOpen">
        <i class="i-Gear spin"></i>
      </div>

      <vue-perfect-scrollbar
        :settings="{ suppressScrollX: true, wheelPropagation: false }"
        class="customizer-body ps rtl-ps-none"
      >
        <div
          class
          v-if="getThemeMode.layout != 'vertical-sidebar' && getThemeMode.layout != 'vertical-sidebar-two'"
        >
          <div class="card-header" id="headingOne">
            <p class="mb-0">RTL</p>
          </div>

          <div class="card-body">
            <label class="checkbox checkbox-primary">
              <input type="checkbox" id="rtl-checkbox" @change="changeThemeRtl" />
              <span>Enable RTL</span>
              <span class="checkmark"></span>
            </label>
          </div>
        </div>

        <div class>
          <div class="card-header">
            <p class="mb-0">Sidebar Layout</p>
          </div>

          <div class="card-body">
            <div class="layout-options">
              <label class="layout-option" :class="{ active: getSidebarLayout === 'horizontal' }">
                <input 
                  type="radio" 
                  name="sidebar-layout" 
                  value="horizontal" 
                  @change="changeSidebarLayout('horizontal')"
                  :checked="getSidebarLayout === 'horizontal'"
                />
                <span class="option-label">
                  <i class="i-Split-Horizontal"></i>
                  Horizontal
                </span>
              </label>
              <label class="layout-option" :class="{ active: getSidebarLayout === 'vertical' }">
                <input 
                  type="radio" 
                  name="sidebar-layout" 
                  value="vertical" 
                  @change="changeSidebarLayout('vertical')"
                  :checked="getSidebarLayout === 'vertical'"
                />
                <span class="option-label">
                  <i class="i-Split-Vertical"></i>
                  Vertical
                </span>
              </label>
            </div>
          </div>
        </div>

        <div class>
          <div class="card-header">
            <p class="mb-0">Dark Mode</p>
          </div>

          <div class="card-body">
            <label class="switch switch-primary mr-3 mt-2" v-b-popover.hover.left="'Dark Mode'">
              <input type="checkbox" @click="changeThemeMode" />
              <span class="slider"></span>
            </label>
          </div>
        </div>

         <div class>
          <div class="card-header">
            <p class="mb-0">Language</p>
          </div>

          <div class="card-body">
             <div class="menu-icon-language">

                <a v-for="lang in getAvailableLanguages" :key="lang.locale" @click="SetLocal(lang.locale)">
                  <img
                    :src="`/flags/${lang.flag}`"
                    :alt="lang.name"
                    class="flag-icon flag-icon-squared"
                    style="width: 20px; margin-right: 8px"
                  />
                  <span class="title-lang">{{ lang.name }}</span>
                </a>
            
            </div>
          </div>
        </div>
      </vue-perfect-scrollbar>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapActions } from "vuex";

export default {
  data() {
    return {
      isOpen: false,
      languages: [],
    };
  },

  computed: {
    ...mapGetters(["getThemeMode", "getcompactLeftSideBarBgColor", "getAvailableLanguages", "getSidebarLayout"]),
  },

  methods: {
    ...mapActions([
      "changeThemeRtl",
      "changeThemeLayout",
      "changeThemeMode",
      "changecompactLeftSideBarBgColor",
      "setSidebarLayout",
    ]),

    changeSidebarLayout(layout) {
      this.setSidebarLayout(layout);
      this.$root.$bvToast.toast(
        `Switched to ${layout} sidebar layout`,
        {
          title: 'Layout Changed',
          variant: 'success',
          solid: true,
          autoHideDelay: 2000
        }
      );
    },

    SetLocal(locale) {
      this.$i18n.locale = locale;
      this.$store.dispatch("setLanguage", locale);
      Fire.$emit("ChangeLanguage");
      window.location.reload();
    },
    
    // async fetchLanguages() {
    //   try {
    //     const response = await axios.get("/languages");
    //     this.languages = response.data;
    //   } catch (error) {
    //     console.warn("Failed to load languages");
    //   }
    // },
  },

  created() {
    this.$store.dispatch("loadAvailableLanguages");
  }
};
</script>

<style lang="scss" scoped>
.layout-options {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.layout-option {
  display: flex;
  align-items: center;
  padding: 12px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
}

.layout-option:hover {
  border-color: #663399;
  background: #f7f7f7;
}

.layout-option.active {
  border-color: #663399;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
}

.layout-option input[type="radio"] {
  position: absolute;
  opacity: 0;
  cursor: pointer;
}

.option-label {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  font-weight: 500;
  color: #47404f;
  width: 100%;
}

.option-label i {
  font-size: 20px;
  color: #663399;
}

.layout-option.active .option-label {
  color: #663399;
  font-weight: 600;
}

/* Dark mode support */
body.dark-theme .layout-option {
  border-color: #444;
  background: transparent;
}

body.dark-theme .layout-option:hover {
  border-color: #764ba2;
  background: rgba(118, 75, 162, 0.1);
}

body.dark-theme .layout-option.active {
  border-color: #764ba2;
  background: rgba(118, 75, 162, 0.2);
}

body.dark-theme .option-label {
  color: #e0e0e0;
}

body.dark-theme .layout-option.active .option-label {
  color: #fff;
}
</style>