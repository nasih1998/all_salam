<template>
  <div class="main-content">
    <breadcumb :page="$t('update_settings')" :folder="$t('Settings')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>


    <div class="col-md-12" v-if="!isLoading">

            <div class="card">
                <div class="card-header">
                    <span>{{$t('Update_Log')}}</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">{{$t('Note_update')}}</div>

                    <b-tabs active-nav-item-class="nav nav-tabs" content-class="mt-3">
                      <!-- <b-tab :title="$t('Automatic_Update')" active>
                        <div class="row border rounded p-3 mt-3 w-100" style="background:#0f172a10;border:1px solid #e5e7eb">
                          <div class="col-md-12">
                            <div class="d-flex align-items-center mb-2">
                              <span class="badge" :class="canUpdate ? 'badge-success' : 'badge-warning'">
                                {{ canUpdate ? $t('Ready') : $t('ChecksPending') }}
                              </span>
                              <button class="btn btn-link btn-sm ml-2" :disabled="preflightLoading" @click="run_preflight">
                                <span v-if="preflightLoading" class="spinner-border spinner-border-sm mr-1"></span>
                                {{$t('Run_Checks')}}
                              </button>
                            </div>
                            <div class="alert alert-info" v-if="version !=''">
                              <strong>{{$t('Update_Available')}}
                                <span class="badge badge-pill badge-info">
                                  {{version}}
                                </span>
                              </strong>
                            </div>
                            <div class="alert alert-info" v-else>
                              <strong>{{$t('You_already_have_the_latest_version')}} <span class="badge badge-pill badge-info"></span></strong>
                            </div>

                            <div class="d-flex align-items-center mt-3">
                              <button class="btn btn-primary" :disabled="SubmitProcessing || isLoading || !canUpdate" @click="Update_system">
                                <span v-if="SubmitProcessing" class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>
                                {{$t('Update_Now')}}
                              </button>
                              <button class="btn btn-outline-secondary ml-2" :disabled="isLoading" @click="get_version_info">
                                {{$t('Check_Again')}}
                              </button>
                            </div>
                            <div class="progress mt-3" v-if="updating">
                              <div class="progress-bar" role="progressbar" :style="{width: (updatePercent||0) + '%'}">
                                {{updatePercent || 0}}%
                              </div>
                            </div>
                            <small class="text-muted d-block" v-if="updating">{{updateStep}}</small>
                            <small class="text-muted d-block mt-2">
                              {{$t('AutoUpdate_Note')}}
                            </small>
                          </div>
                        </div>
                      </b-tab> -->

                      <b-tab :title="$t('Manual_Update')">
                        <div class="col-md-12 mt-3">
                          <h5>Please follow these steps, To Update your application</h5>
                          <div class="allert alert-danger">Note 1: If you have made any changes in the code manually then your changes will be lost.</div>
                          <div class="allert alert-danger">Note 2: only admin or user who has permission "system_setting" he can upgrade the system</div>
                          <ul>
                            <li>
                              <strong>Step 1 : </strong>Take back up of your database,  Go to <a href="/app/settings/Backup">Backup</a> Click on Generate Backup ,
                              You will find it in <strong>/storage/app/public/backup</strong>  and save it to your pc To restore it if there is an error ,
                              or Go to your PhpMyAdmin and export your database then and save it to your pc To restore it if there is an error
                            </li>

                            <li>
                              <strong>Step 2 : </strong> Take back up of your files before updating.
                            </li>

                            <li>
                              <strong>Step 3 : </strong>  Download the latest version from your codecanyon and Extract it .
                            </li>

                            <li>
                              <strong>Step 4 : </strong>  Make sure to remove the previous files , <strong>except</strong> the following :
                              <ul>
                                <li>file   : <strong>.env</strong></li>
                                <li>Folder : <strong>storage</strong></li>
                                <li>Folder : <strong>images folder in public : /public/images</strong></li>
                              </ul>
                            </li>

                            <li>
                              <strong>Step 5 : </strong> Re-upload the files and folders from the new update , <strong>except</strong> the following :
                              <ul>
                                <li>file   : <strong>.env</strong></li>
                                <li>Folder : <strong>storage</strong></li>
                                <li>Folder : <strong>images folder in public : /public/images</strong></li>
                              </ul>
                            </li>

                            <li>
                              <strong>Step 6 : </strong>Visit  http://your_app/update to update your database
                            </li>

                            <li>
                              <strong>Step 7 : </strong> Hard Clear your cache browser
                            </li>

                            <li>
                              <strong>Step 8 : </strong> You are done! Enjoy the updated application
                            </li>

                          </ul>
                          <div class="allert alert-danger">Note: If any pages are not loading or blank, make sure you cleared your browser cache.</div>
                        </div>
                      </b-tab>
                    </b-tabs>

                </div>
            </div>
        </div>

  </div>
</template>

<script>
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "Update Settings"
  },
  data() {
    return {
      
      isLoading: true,
      SubmitProcessing:false,
      version:"",
      preflightLoading: false,
      preflight: null,
      canUpdate: false,
      updating: false,
      updatePercent: 0,
      updateStep: 'idle',
      progressTimer: null,
     
    };
  },

  methods: {

        //------------------------ Update ---------------------------\\
        Update_system() {
            var self = this;
            self.SubmitProcessing = true;
            self.updating = true;
            self.updatePercent = 0;
            self.updateStep = 'starting';
            this.startProgressPolling();
            NProgress.start();
            NProgress.set(0.1);
            axios.post("one_click_update").then(response => {
                self.SubmitProcessing = false;
                self.updating = false;
                self.stopProgressPolling();
                NProgress.done();
                this.makeToast(
                  "success",
                  this.$t("Successfully_Updated"),
                  this.$t("Success")
                );
                Fire.$emit("Event_update");
            })
            .catch(error => {
                 self.SubmitProcessing = false;
                 self.updating = false;
                 self.stopProgressPolling();
                 NProgress.done();
                if(error && error.response && error.response.status == 400){
                     this.makeToast("danger", error.response.data.message || this.$t("InvalidData"), this.$t("Failed"));
                }else{
                    this.makeToast("danger", (error && error.response && error.response.data && error.response.data.message) || this.$t("InvalidData"), this.$t("Failed"));
                }
            });
        },

        //------ Toast
        makeToast(variant, msg, title) {
          this.$root.$bvToast.toast(msg, {
            title: title,
            variant: variant,
            solid: true
          });
        },

        //---------------------------------- get_version_info ----------------\\
        get_version_info() {
           var self = this;
          axios
            .get("get_version_info")
            .then(response => {
              self.version = response.data;
              self.isLoading = false;
            })
            .catch(error => {
              setTimeout(() => {
                self.isLoading = false;
              }, 500);
            });
        },

        run_preflight() {
          this.preflightLoading = true;
          axios.get('update/preflight')
          .then(res => {
            this.preflight = res.data;
            this.canUpdate = !!(res.data && res.data.ok);
          })
          .catch(() => {
            this.preflight = null;
            this.canUpdate = false;
          })
          .finally(() => {
            this.preflightLoading = false;
          });
        },

        startProgressPolling(){
          if(this.progressTimer) return;
          this.progressTimer = setInterval(() => {
            axios.get('update/progress')
            .then(res => {
              if(res && res.data){
                this.updatePercent = res.data.percent || 0;
                this.updateStep = res.data.step || 'running';
                if(this.updatePercent >= 100){
                  this.stopProgressPolling();
                }
              }
            })
            .catch(() => {});
          }, 1000);
        },

        stopProgressPolling(){
          if(this.progressTimer){
            clearInterval(this.progressTimer);
            this.progressTimer = null;
          }
        },   



   
  }, //end Methods

  //----------------------------- Created function-------------------

  created: function() {
    this.get_version_info();
    this.run_preflight();


    Fire.$on("Event_update", () => {
      this.get_version_info();
    });
  }
};
</script>