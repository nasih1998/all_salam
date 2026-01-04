<template>
  <div class="main-content">
    <breadcumb :page="'Update Product'" :folder="$t('Products')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <validation-observer ref="Edit_Product" v-if="!isLoading">
      <b-form @submit.prevent="Submit_Product" enctype="multipart/form-data">
        <!-- Barcode Scanner Modal -->
        <b-modal hide-footer id="open_scan" size="md" :title="$t('Barcode_Scanner')">
          <qrcode-scanner
            :qrbox="250"
            :fps="10"
            style="width: 100%; height: calc(100vh - 56px);"
            @result="onScan"
          />
        </b-modal>

        <b-row>
          <!-- Main Content Column -->
          <b-col lg="12" class="mb-4">
            <!-- ========== SECTION 1: BASIC INFORMATION ========== -->
            <div class="form-section">
              <div class="section-header">
                <i class="i-File section-icon"></i>
                <h4 class="section-title">{{ $t('BasicInformation') }}</h4>
              </div>
              <b-card class="section-card">
                <b-row>
                  <!-- Product Name -->
                  <b-col md="6" class="mb-3">
                    <validation-provider
                      name="Name"
                      :rules="{required:true , min:3 , max:55}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('Name_product') + ' *'">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="Name-feedback"
                          :placeholder="$t('Enter_Name_Product')"
                          v-model="product.name"
                          class="form-control-modern"
                        ></b-form-input>
                        <b-form-invalid-feedback id="Name-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Product Image -->
                  <b-col md="6" class="mb-3">
                    <validation-provider name="Image" ref="Image" rules="mimes:image/*">
                      <b-form-group slot-scope="{validate, valid, errors }" :label="$t('ProductImage')">
                        <div class="image-upload-wrapper">
                          <input
                            :state="errors[0] ? false : (valid ? true : null)"
                            :class="{'is-invalid': !!errors.length}"
                            @change="onFileSelected"
                            type="file"
                            class="form-control-file"
                          >
                          <small class="text-muted d-block mt-2">{{ $t('Supported_formats_JPG_PNG_GIF') }}</small>
                        </div>
                        <b-form-invalid-feedback id="Image-feedback" v-if="errors[0]">{{ errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Barcode Symbology -->
                  <b-col md="6" class="mb-3">
                    <validation-provider name="Barcode Symbology" :rules="{ required: true}">
                      <b-form-group
                        slot-scope="{ valid, errors }"
                        :label="$t('BarcodeSymbology') + ' *'"
                      >
                        <v-select
                          :class="{'is-invalid': !!errors.length}"
                          :state="errors[0] ? false : (valid ? true : null)"
                          v-model="product.Type_barcode"
                          :reduce="label => label.value"
                          :placeholder="$t('Choose_Symbology')"
                          :options="[
                            {label: 'Code 128', value: 'CODE128'},
                            {label: 'Code 39', value: 'CODE39'},
                            {label: 'EAN8', value: 'EAN8'},
                            {label: 'EAN13', value: 'EAN13'},
                            {label: 'UPC', value: 'UPC'},
                          ]"
                        ></v-select>
                        <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Product Code -->
                  <b-col md="6" class="mb-3">
                    <validation-provider name="Code Product" :rules="{ required: true}">
                      <b-form-group
                        slot-scope="{ valid, errors }"
                        :label="$t('CodeProduct') + ' *'"
                      >
                        <div class="input-group modern-input-group">
                          <div class="input-group-prepend">
                            <button type="button" class="btn-icon-scan" @click="showModal" title="Scan">
                              <img src="/assets_setup/scan.png" alt="Scan" class="scan-icon" />
                            </button>
                          </div>
                          <b-form-input
                            :class="{'is-invalid': !!errors.length}"
                            :state="errors[0] ? false : (valid ? true : null)"
                            aria-describedby="CodeProduct-feedback"
                            type="text"
                            v-model="product.code"
                            :placeholder="$t('Enter_Product_Code')"
                          ></b-form-input>
                          <div class="input-group-append">
                            <button type="button" class="btn-icon-gen" @click="generateNumber()" title="Generate">
                              <i class="i-Bar-Code"></i>
                            </button>
                          </div>
                        </div>
                        <small class="text-muted d-block mt-1">{{ $t('Scan_your_barcode_and_select_the_correct_symbology_below') }}</small>
                        <b-alert
                          show
                          variant="danger"
                          class="mt-2 mb-0"
                          v-if="code_exist !=''"
                        >{{ code_exist }}</b-alert>
                        <b-form-invalid-feedback id="CodeProduct-feedback" v-if="errors[0]">{{ errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Category -->
                  <b-col md="6" class="mb-3">
                    <validation-provider name="category" :rules="{ required: true}">
                      <b-form-group
                        slot-scope="{ valid, errors }"
                        :label="$t('Categorie') + ' *'"
                      >
                        <v-select
                          :class="{'is-invalid': !!errors.length}"
                          :state="errors[0] ? false : (valid ? true : null)"
                          :reduce="label => label.value"
                          :placeholder="$t('Choose_Category')"
                          v-model="product.category_id"
                          :options="categories.map(categories => ({label: categories.name, value: categories.id}))"
                        />
                        <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Brand -->
                  <b-col md="6" class="mb-3">
                    <b-form-group :label="$t('Brand')">
                      <v-select
                        :placeholder="$t('Choose_Brand')"
                        :reduce="label => label.value"
                        v-model="product.brand_id"
                        :options="brands.map(brands => ({label: brands.name, value: brands.id}))"
                      />
                    </b-form-group>
                  </b-col>

                  <!-- Description -->
                  <b-col md="12" class="mb-3">
                    <b-form-group :label="$t('Description')">
                      <textarea
                        rows="4"
                        class="form-control"
                        :placeholder="$t('Afewwords')"
                        v-model="product.note"
                      ></textarea>
                    </b-form-group>
                  </b-col>
                </b-row>
              </b-card>
            </div>

            <!-- ========== SECTION 2: INVENTORY ========== -->
            <div class="form-section">
              <div class="section-header">
                <i class="i-Box section-icon"></i>
                <h4 class="section-title">{{ $t('Inventory') }}</h4>
              </div>
              <b-card class="section-card">
                <b-row>
                  <!-- Product Type (display only) -->
                  <b-col md="6" class="mb-3" v-if="product.type == 'is_single'">
                    <b-form-group :label="$t('type')">
                      <b-form-input value="Standard Product" disabled="disabled"></b-form-input>
                    </b-form-group>
                  </b-col>
                  <b-col md="6" class="mb-3" v-else-if="product.type == 'is_variant'">
                    <b-form-group :label="$t('type')">
                      <b-form-input value="Variable Product" disabled="disabled"></b-form-input>
                    </b-form-group>
                  </b-col>
                  <b-col md="6" class="mb-3" v-else-if="product.type == 'is_service'">
                    <b-form-group :label="$t('type')">
                      <b-form-input value="Service Product" disabled="disabled"></b-form-input>
                    </b-form-group>
                  </b-col>
                  <b-col md="6" class="mb-3" v-else-if="product.type == 'is_combo'">
                    <b-form-group :label="$t('type')">
                      <b-form-input value="Combo Product" disabled="disabled"></b-form-input>
                    </b-form-group>
                  </b-col>

                  <!-- Unit Product -->
                  <b-col md="6" class="mb-3" v-if="product.type != 'is_service'">
                    <validation-provider name="Unit Product" :rules="{ required: true}">
                      <b-form-group
                        slot-scope="{ valid, errors }"
                        :label="$t('UnitProduct') + ' *'"
                      >
                        <v-select
                          :class="{'is-invalid': !!errors.length}"
                          :state="errors[0] ? false : (valid ? true : null)"
                          v-model="product.unit_id"
                          @input="Selected_Unit"
                          :placeholder="$t('Choose_Unit_Product')"
                          :reduce="label => label.value"
                          :options="units.map(units => ({label: units.name, value: units.id}))"
                        />
                        <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Unit Sale -->
                  <b-col md="6" class="mb-3" v-if="product.type != 'is_service'">
                    <validation-provider name="Unit Sale" :rules="{ required: true}">
                      <b-form-group
                        slot-scope="{ valid, errors }"
                        :label="$t('UnitSale') + ' *'"
                      >
                        <v-select
                          :class="{'is-invalid': !!errors.length}"
                          :state="errors[0] ? false : (valid ? true : null)"
                          v-model="product.unit_sale_id"
                          :placeholder="$t('Choose_Unit_Sale')"
                          :reduce="label => label.value"
                          :options="units_sub.map(units_sub => ({label: units_sub.name, value: units_sub.id}))"
                        />
                        <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Unit Purchase -->
                  <b-col md="6" class="mb-3" v-if="product.type != 'is_service'">
                    <validation-provider name="Unit Purchase" :rules="{ required: true}">
                      <b-form-group
                        slot-scope="{ valid, errors }"
                        :label="$t('UnitPurchase') + ' *'"
                      >
                        <v-select
                          :class="{'is-invalid': !!errors.length}"
                          :state="errors[0] ? false : (valid ? true : null)"
                          v-model="product.unit_purchase_id"
                          :placeholder="$t('Choose_Unit_Purchase')"
                          :reduce="label => label.value"
                          :options="units_sub.map(units_sub => ({label: units_sub.name, value: units_sub.id}))"
                        />
                        <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Stock Alert -->
                  <b-col md="6" class="mb-3" v-if="product.type != 'is_service'">
                    <validation-provider
                      name="Stock Alert"
                      :rules="{ regex: /^\d*\.?\d*$/}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('StockAlert')">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="StockAlert-feedback"
                          type="text"
                          placeholder="0"
                          v-model="product.stock_alert"
                        ></b-form-input>
                        <b-form-invalid-feedback id="StockAlert-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>
                </b-row>
              </b-card>
            </div>

            <!-- ========== SECTION 3: VARIANTS (if applicable) ========== -->
            <div class="form-section" v-if="product.type == 'is_variant'">
              <div class="section-header">
                <i class="i-Gear section-icon"></i>
                <h4 class="section-title">{{ $t('Variants') }}</h4>
              </div>
              <b-card class="section-card">
                <div class="variant-input-group mb-3">
                  <b-form-group>
                    <b-input-group>
                      <b-form-input
                        :placeholder="$t('Enter_the_Variant')"
                        v-model="tag"
                        class="form-control-modern"
                      ></b-form-input>
                      <b-input-group-append>
                        <b-button variant="primary" @click="add_variant(tag)">
                          <i class="i-Plus me-2"></i>{{ $t('Add') }}
                        </b-button>
                      </b-input-group-append>
                    </b-input-group>
                  </b-form-group>
                </div>

                <div class="table-responsive" v-if="variants.length > 0">
                  <table class="table table-hover table-modern">
                    <thead>
                      <tr>
                        <th>{{ $t('Code') }}</th>
                        <th>{{ $t('Name') }}</th>
                        <th>{{ $t('Cost') }}</th>
                        <th>{{ $t('Retail Price') }}</th>
                        <th>{{ $t('Wholesale_Price') }}</th>
                        <th>{{ $t('Min_Selling_Price') }}</th>
                        <th class="text-center" style="width: 50px;"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="variant in variants" :key="variant.var_id">
                        <td><b-form-input v-model="variant.code" type="text" size="sm"></b-form-input></td>
                        <td><b-form-input v-model="variant.text" type="text" size="sm"></b-form-input></td>
                        <td><b-form-input v-model="variant.cost" type="text" size="sm"></b-form-input></td>
                        <td><b-form-input v-model="variant.price" type="text" size="sm"></b-form-input></td>
                        <td><b-form-input v-model="variant.wholesale" type="text" size="sm"></b-form-input></td>
                        <td><b-form-input v-model="variant.min_price" type="text" size="sm"></b-form-input></td>
                        <td class="text-center">
                          <b-button
                            variant="danger"
                            size="sm"
                            @click="delete_variant(variant.var_id)"
                            title="Delete"
                          >
                            <i class="i-Close"></i>
                          </b-button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div v-else class="alert alert-info">
                  {{ $t('NodataAvailable') }}
                </div>
              </b-card>
            </div>

            <!-- ========== SECTION 4: PRICING & TAX ========== -->
            <div class="form-section">
              <div class="section-header">
                <i class="i-Tag section-icon"></i>
                <h4 class="section-title">{{ $t('PricingAndTax') }}</h4>
              </div>
              <b-card class="section-card">
                <b-row>
                  <!-- Product Cost -->
                  <b-col md="6" class="mb-3" v-if="product.type == 'is_single' || product.type == 'is_combo'">
                    <validation-provider
                      name="Product Cost"
                      :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('ProductCost') + ' *'">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="ProductCost-feedback"
                          type="text"
                          placeholder="0.00"
                          v-model="product.cost"
                        ></b-form-input>
                        <b-form-invalid-feedback id="ProductCost-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Product Price -->
                  <b-col
                    md="6"
                    class="mb-2"
                    v-if="product.type == 'is_single' || product.type == 'is_service' || product.type == 'is_combo'"
                  >
                    <validation-provider
                      name="Product Price"
                      :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('Retail Price') + ' *'">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="ProductPrice-feedback"
                          label="Price"
                          :placeholder="$t('Enter_Product_Price')"
                          v-model="product.price"
                        ></b-form-input>

                        <b-form-invalid-feedback id="ProductPrice-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Wholesale Price (optional) -->
                  <b-col
                    md="6"
                    class="mb-2"
                    v-if="product.type == 'is_single' || product.type == 'is_service' || product.type == 'is_combo'"
                  >
                    <validation-provider
                      name="Wholesale Price"
                      :rules="{ regex: /^\d*\.?\d*$/ }"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('Wholesale_Price')">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="WholesalePrice-feedback"
                          :placeholder="$t('Enter_Wholesale_Price')"
                          v-model="product.wholesale_price"
                        ></b-form-input>

                        <b-form-invalid-feedback id="WholesalePrice-feedback">
                          {{ validationContext.errors[0] }}
                        </b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Minimum Selling Price (optional) -->
                  <b-col
                    md="6"
                    class="mb-2"
                    v-if="product.type == 'is_single' || product.type == 'is_service' || product.type == 'is_combo'"
                  >
                    <validation-provider
                      name="Minimum Selling Price"
                      :rules="{ regex: /^\d*\.?\d*$/ }"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('Minimum_Selling_Price')">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="MinPrice-feedback"
                          :placeholder="$t('Enter_Minimum_Selling_Price')"
                          v-model="product.min_price"
                        ></b-form-input>

                        <b-form-invalid-feedback id="MinPrice-feedback">
                          {{ validationContext.errors[0] }}
                        </b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Tax Rate -->
                  <b-col md="6" class="mb-3">
                    <validation-provider
                      name="Order Tax"
                      :rules="{regex: /^\d*\.?\d*$/}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('OrderTax')">
                        <b-input-group append="%">
                          <b-form-input
                            :state="getValidationState(validationContext)"
                            aria-describedby="OrderTax-feedback"
                            type="text"
                            placeholder="0"
                            v-model.number="product.TaxNet"
                          ></b-form-input>
                        </b-input-group>
                        <b-form-invalid-feedback id="OrderTax-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Tax Method -->
                  <b-col md="6" class="mb-3">
                    <validation-provider name="Tax Method" :rules="{ required: true}">
                      <b-form-group
                        slot-scope="{ valid, errors }"
                        :label="$t('TaxMethod') + ' *'"
                      >
                        <v-select
                          :class="{'is-invalid': !!errors.length}"
                          :state="errors[0] ? false : (valid ? true : null)"
                          v-model="product.tax_method"
                          :reduce="label => label.value"
                          :placeholder="$t('Choose_Method')"
                          :options="[
                            {label: 'Exclusive', value: '1'},
                            {label: 'Inclusive', value: '2'}
                          ]"
                        ></v-select>
                        <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Discount Method -->
                  <b-col md="6" class="mb-3">
                    <validation-provider name="Discount Method" :rules="{ required: true}">
                      <b-form-group slot-scope="{ valid, errors }" :label="$t('Discount_Method') + ' *'">
                        <v-select
                          v-model="product.discount_method"
                          :reduce="label => label.value"
                          :placeholder="$t('Choose_Method')"
                          :class="{'is-invalid': !!errors.length}"
                          :state="errors[0] ? false : (valid ? true : null)"
                          :options="[
                            {label: 'Percent %', value: '1'},
                            {label: 'Fixed', value: '2'}
                          ]"
                        ></v-select>
                        <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Discount Rate -->
                  <b-col md="6" class="mb-3">
                    <validation-provider
                      name="Discount Rate"
                      :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('Discount')">
                        <b-form-input
                          v-model.number="product.discount"
                          :state="getValidationState(validationContext)"
                          aria-describedby="Discount-feedback"
                          type="text"
                          placeholder="0.00"
                        ></b-form-input>
                        <b-form-invalid-feedback id="Discount-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Points -->
                  <b-col md="6" class="mb-3">
                    <validation-provider
                      name="Points"
                      :rules="{ regex: /^\d*\.?\d*$/}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('Points')">
                        <b-form-input
                          v-model.number="product.points"
                          :state="getValidationState(validationContext)"
                          aria-describedby="Points-feedback"
                          type="text"
                          placeholder="0"
                        ></b-form-input>
                        <b-form-invalid-feedback id="Points-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>
                </b-row>
              </b-card>
            </div>

            <!-- ========== SECTION 5: COMBO PRODUCTS ========== -->
            <div class="form-section" v-if="product.type == 'is_combo'">
              <div class="section-header">
                <i class="i-Bag section-icon"></i>
                <h4 class="section-title">{{ $t('ComboProducts') }}</h4>
              </div>
              <b-card class="section-card">
                <div class="combo-search mb-3">
                  <b-form-group :label="$t('SearchProduct')">
                    <div class="autocomplete">
                      <input  
                        :placeholder="$t('Scan_Search_Product_by_Code_Name')"
                        @input='e => search_input = e.target.value'
                        @keyup="search(search_input)"
                        @focus="handleFocus"
                        @blur="handleBlur"
                        ref="product_autocomplete"
                        class="autocomplete-input form-control"
                      />
                      <ul class="autocomplete-result-list" v-show="focused">
                        <li class="autocomplete-result" v-for="product_fil in product_filter" :key="product_fil.id"
                            @mousedown="SearchProduct(product_fil)">{{ getResultValue(product_fil) }}</li>
                      </ul>
                    </div>
                  </b-form-group>
                </div>

                <div class="table-responsive">
                  <table class="table table-hover table-modern">
                    <thead>
                      <tr>
                        <th>{{ $t('ProductName') }}</th>
                        <th>{{ $t('Quantity') }}</th>
                        <th class="text-right">{{ $t('Cost') }}</th>
                        <th class="text-right">{{ $t('SubTotal') }}</th>
                        <th class="text-center" style="width: 50px;"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="materiels.length <= 0">
                        <td colspan="5" class="text-center text-muted">{{ $t('NodataAvailable') }}</td>
                      </tr>
                      <tr v-for="materiel in materiels" :key="materiel.product_id">
                        <td>
                          <div class="badge-wrapper">
                            <span class="badge badge-primary-light">{{ materiel.name }}</span>
                            <br>
                            <small class="text-muted">{{ materiel.code }}</small>
                          </div>
                        </td>
                        <td>
                          <b-input-group :append="materiel.unit_name">
                            <b-form-input 
                              v-model.number="materiel.quantity"
                              type="text"
                              min="1"
                              size="sm"
                              style="width: 60px;"
                            ></b-form-input>
                          </b-input-group>
                        </td>
                        <td class="text-right">{{ currentUser.currency }} {{ materiel.cost }}</td>
                        <td class="text-right font-weight-bold">{{ currentUser.currency }} {{ formatNumber(materiel.cost * materiel.quantity, 2) }}</td>
                        <td class="text-center">
                          <b-button
                            variant="danger"
                            size="sm"
                            @click="delete_materiel(materiel.product_id)"
                            title="Delete"
                          >
                            <i class="i-Close"></i>
                          </b-button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="combo-total mt-3" v-if="materiels.length > 0">
                  <div class="total-row">
                    <span class="total-label">{{ $t('TotalCost') }}</span>
                    <span class="total-value">{{ currentUser.currency }} {{ formatNumber(totalCost, 2) }}</span>
                  </div>
                </div>
              </b-card>
            </div>

            <!-- ========== SECTION 6: WARRANTY ========== -->
            <div class="form-section">
              <div class="section-header">
                <i class="i-Shield section-icon"></i>
                <h4 class="section-title">{{ $t('Warranty_Guarantee_Tracking') }}</h4>
              </div>
              <b-card class="section-card">
                <b-row>
                  <!-- Warranty Period -->
                  <b-col md="6" class="mb-3">
                    <b-form-group :label="$t('Warranty_Period')">
                      <b-input-group>
                        <b-form-input
                          type="text"
                          placeholder="0"
                          v-model="product.warranty_period"
                        ></b-form-input>
                        <b-form-select
                          v-model="product.warranty_unit"
                          :options="[
                            { value: 'days', text: $t('Days') },
                            { value: 'months', text: $t('Months') },
                            { value: 'years', text: $t('Years') }
                          ]"
                        ></b-form-select>
                      </b-input-group>
                    </b-form-group>
                  </b-col>

                  <!-- Guarantee Toggle -->
                  <b-col md="6" class="mb-3">
                    <b-form-group>
                      <b-form-checkbox
                        v-model="product.has_guarantee"
                        :unchecked-value="false"
                        :checked-value="true"
                        switch
                      >
                        {{ $t('HasGuarantee') }}
                      </b-form-checkbox>
                    </b-form-group>
                  </b-col>

                  <!-- Warranty Terms -->
                  <b-col md="12" class="mb-3">
                    <b-form-group :label="$t('WarrantyTerms')">
                      <b-form-textarea
                        :placeholder="$t('Enter_warranty_terms')"
                        rows="3"
                        v-model="product.warranty_terms"
                      ></b-form-textarea>
                    </b-form-group>
                  </b-col>

                  <!-- Guarantee Period -->
                  <b-col md="6" class="mb-3" v-if="product.has_guarantee">
                    <b-form-group :label="$t('Guarantee_Period')">
                      <b-input-group>
                        <b-form-input
                          type="text"
                          placeholder="0"
                          v-model="product.guarantee_period"
                        ></b-form-input>
                        <b-form-select
                          v-model="product.guarantee_unit"
                          :options="[
                            { value: 'days', text: $t('Days') },
                            { value: 'months', text: $t('Months') },
                            { value: 'years', text: $t('Years') }
                          ]"
                        ></b-form-select>
                      </b-input-group>
                    </b-form-group>
                  </b-col>
                </b-row>
              </b-card>
            </div>

            <!-- ========== SECTION 7: OPTIONS ========== -->
            <div class="form-section">
              <div class="section-header">
                <i class="i-Data-Settings section-icon"></i>
                <h4 class="section-title">{{ $t('Options') }}</h4>
              </div>
              <b-card class="section-card">
                <div class="options-grid">
                  <b-form-group>
                    <b-form-checkbox
                      v-model="product.is_imei"
                      :unchecked-value="false"
                      :checked-value="true"
                      switch
                    >
                      {{ $t('Product_Has_Imei_Serial_number') }}
                    </b-form-checkbox>
                  </b-form-group>

                  <b-form-group>
                    <b-form-checkbox
                      v-model="product.not_selling"
                      :unchecked-value="false"
                      :checked-value="true"
                      switch
                    >
                      {{ $t('This_Product_Not_For_Selling') }}
                    </b-form-checkbox>
                  </b-form-group>

                  <b-form-group>
                    <b-form-checkbox
                      v-model="product.is_featured"
                      :unchecked-value="false"
                      :checked-value="true"
                      switch
                    >
                      {{ $t('Featured_Product') }}
                    </b-form-checkbox>
                  </b-form-group>
                </div>
              </b-card>
            </div>

            <!-- Submit Buttons -->
            <div class="form-actions mt-4">
              <b-button variant="primary" type="submit" :disabled="SubmitProcessing" size="lg">
                <i class="i-Yes me-2 pr-2"></i>{{ $t('submit') }}
              </b-button>
              <div v-if="SubmitProcessing" class="spinner-inline">
                <div class="spinner sm spinner-primary"></div>
              </div>
            </div>
          </b-col>

          <!-- Sidebar - Summary/Info -->
          <b-col lg="4">
            <div class="sticky-sidebar">
            </div>
          </b-col>
        </b-row>
      </b-form>
    </validation-observer>
  </div>
</template>

<script>
import VueTagsInput from "@johmun/vue-tags-input";
import NProgress from "nprogress";
import { mapActions, mapGetters } from "vuex";

export default {
  metaInfo: {
    title: "Edit Product"
  },
  data() {
    return {
      focused: false,
      timer:null,
      search_input:'',
      product_filter:[],
      materiels: [],
      products_ing: [],
      tag: "",
      len: 8,
      change: false,
      isLoading: true,
      SubmitProcessing:false,
      data: new FormData(),
      categories: [],
      Subcategories: [],
      units: [],
      units_sub: [],
      brands: [],
      roles: {},
      variants: [],
      product: {
        type: "",
        name: "",
        points: "",
        code: "",
        Type_barcode: "",
        cost: "",
        price: "",
        brand_id: "",
        category_id: "",
        TaxNet: "",
        tax_method: "1",
        unit_id: "",
        unit_sale_id: "",
        unit_purchase_id: "",
        stock_alert: "",
        image: "",
        note: "",
        is_variant: false,
        is_imei: false,
        not_selling: false,
      },
      code_exist: ""
    };
  },

  components: {
    VueTagsInput
  },

  computed: {
    ...mapGetters(["currentUserPermissions","currentUser"]),
    totalCost() {
      return this.materiels.reduce((total, materiel) => {
        return total + (materiel.cost * materiel.quantity);
      }, 0);
    }
  },

  methods: {

      //------------------------------Formetted Numbers -------------------------\\
      formatNumber(number, dec) {
      const value = (typeof number === "string"
        ? number
        : number.toString()
      ).split(".");
      if (dec <= 0) return value[0];
      let formated = value[1] || "";
      if (formated.length > dec)
        return `${value[0]}.${formated.substr(0, dec)}`;
      while (formated.length < dec) formated += "0";
      return `${value[0]}.${formated}`;
    },

     //------------------------------ Event Upload Image -------------------------------\\
     async onFileSelected(e) {
      const { valid } = await this.$refs.Image.validate(e);

      if (valid) {
        this.product.image = e.target.files[0];
      } else {
        this.product.image = "";
      }
    },



    //---------------------- get_products_materiels------------------------------\\
    get_products_materiels(value) {
      axios
      .get("get_products_materiels")
      .then(({ data }) => (this.products_ing = data));
    },


    handleFocus() {
      this.focused = true
    },


    handleBlur() {
      this.focused = false
    },


    // Search Products
    search(){
      if (this.timer) {
          clearTimeout(this.timer);
          this.timer = null;
      }
      if (this.search_input.length < 1) {
      return this.product_filter= [];
      }
      this.timer = setTimeout(() => {
      const product_filter = this.products_ing.filter(ingredient => ingredient.code === this.search_input);
          if(product_filter.length === 1){
              this.SearchProduct(product_filter[0])
          }else{
              this.product_filter=  this.products_ing.filter(ingredient => {
              return (
                  ingredient.name.toLowerCase().includes(this.search_input.toLowerCase()) ||
                  ingredient.code.toLowerCase().includes(this.search_input.toLowerCase())
                  );
              });
          }
      }, 800);

    },

    // get Result Value Search Products
    getResultValue(result) {
      return result.code + " " + "(" + result.name + ")";
    },


      // Submit Search Products
      SearchProduct(result) {
        this.ingredient = {};
        if (
            this.materiels.length > 0 &&
            this.materiels.some(detail => detail.code === result.code)
        ) {
            toastr.error('Product_Already_added');
            
        } else {

            var materiel_tag = {
                product_id:result.product_id,
                name:result.name,
                code:result.code,
                unit_name:result.unit_name,
                cost:result.cost,
                quantity:1,
            }
            this.materiels.push(materiel_tag);
            
        }
        this.search_input= '';
        this.$refs.product_autocomplete.value = "";
        this.product_filter = [];
      },


    //-----------------------------------Delete variant------------------------------\\
    delete_materiel(product_id) {

      for (var i = 0; i < this.materiels.length; i++) {
          if (product_id === this.materiels[i].product_id) {
          this.materiels.splice(i, 1);
          }
      }
    },


    showModal() {
      this.$bvModal.show('open_scan');
      
    },

    onScan (decodedText, decodedResult) {
      const code = decodedText;
      this.product.code = code;
      this.$bvModal.hide('open_scan');
    },


     //------ Generate code
     generateNumber() {
      this.code_exist = "";
      this.product.code = Math.floor(
        Math.pow(10, 7) +
          Math.random() *
            (Math.pow(10, 8) - Math.pow(10, 7) - 1)
      );
    },
    
    //------------- Submit Validation Update Product
    Submit_Product() {
      this.$refs.Edit_Product.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {

            if (this.product.type == 'is_variant' && this.variants.length <= 0) {
              this.makeToast("danger", "The variants array is required.", this.$t("Failed"));
            }else{
              this.Update_Product();
            }
        }
      });
    },

    //------ Validation state fields
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },

    add_variant(tag) {
      if (
        this.variants.length > 0 &&
        this.variants.some(variant => variant.text === tag)
      ) {
         this.makeToast(
            "warning",
            this.$t("VariantDuplicate"),
            this.$t("Warning")
          );
      } else {
          if(this.tag != ''){
            var variant_tag = {
              var_id: this.variants.length + 1, // generate unique ID
              text: tag
            };
            this.variants.push(variant_tag);
            this.tag = "";
          }else{

            this.makeToast(
              "warning",
              "Please Enter the Variant",
              this.$t("Warning")
            );
            
          }
      }
    },
    //-----------------------------------Delete variant------------------------------\\
    delete_variant(var_id) {
      for (var i = 0; i < this.variants.length; i++) {
        if (var_id === this.variants[i].var_id) {
          this.variants.splice(i, 1);
        }
      }
    },



    //---------------------------------------Get Product Elements ------------------------------\\
    GetElements() {
      let id = this.$route.params.id;
      axios
        .get(`products/${id}/edit`)
        .then(response => {
          this.product = response.data.product;
          this.variants = response.data.product.ProductVariant;
          this.categories = response.data.categories;
          this.brands = response.data.brands;
          this.units = response.data.units;
          this.units_sub = response.data.units_sub;
          if(this.product.type == 'is_combo'){
              this.get_products_materiels();
              this.materiels = response.data.materiels;
          }

          this.isLoading = false;
        })
        .catch(response => {
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    },

    //---------------------- Get Sub Units with Unit id ------------------------------\\
    Get_Units_SubBase(value) {
      axios
        .get("get_sub_units_by_base?id=" + value)
        .then(({ data }) => (this.units_sub = data));
    },

    //---------------------- Event Select Unit Product ------------------------------\\
    Selected_Unit(value) {
      this.units_sub = [];
      this.product.unit_sale_id = "";
      this.product.unit_purchase_id = "";
      this.Get_Units_SubBase(value);
    },

    //------------------------------ Update Product ------------------------------\\
    Update_Product() {
      
      NProgress.start();
      NProgress.set(0.1);
      var self = this;
      self.data = new FormData();
      self.SubmitProcessing = true;

      if (self.product.type == 'is_variant' && self.variants.length > 0) {
        self.product.is_variant = true;
      }else{
        self.product.is_variant = false;
      }

      // append objet product
      Object.entries(self.product).forEach(([key, value]) => {
          self.data.append(key, value);
      });

      
       // append array variants
       if (self.materiels.length && self.product.type == 'is_combo') {
        self.data.append("materiels", JSON.stringify(self.materiels));
      }
                
      //append array variants
      if (self.variants.length) {
          for (var i = 0; i < self.variants.length; i++) {
          Object.entries(self.variants[i]).forEach(([key, value]) => {
              self.data.append("variants[" + i + "][" + key + "]", value);
          });
          }
      }


      self.data.append("_method", "put");

      //send Data with axios
      axios
        .post("products/" + this.product.id, self.data)
        .then(response => {
          NProgress.done();
          self.SubmitProcessing = false;
          this.$router.push({ name: "index_products" });
          this.makeToast(
            "success",
            this.$t("Successfully_Updated"),
            this.$t("Success")
          );
        })
        .catch(error => {
            NProgress.done();
            self.SubmitProcessing = false;
            if (error.errors.code && error.errors.code.length > 0) {
              self.code_exist = error.errors.code[0];
              this.makeToast("danger", error.errors.code[0], this.$t("Failed"));
            }else if(error.errors.variants && error.errors.variants.length > 0){
              this.makeToast("danger", error.errors.variants[0], this.$t("Failed"));
            }else{
              this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
            }
        });
    }
  }, //end Methods

  //-----------------------------Created function-------------------

  created: function() {
    this.GetElements();
  }
};
</script>

<style>
  .scan-icon {
    width: 43px;
    height: 34px;
    margin-right: 8px;
    cursor: pointer;
  }

  /* ===== Form Sections ===== */
  .form-section {
    margin-bottom: 2rem;
  }

  .section-header {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #f0f0f0;
  }

  .section-icon {
    font-size: 1.5rem;
    color: #667eea;
    margin-right: 0.75rem;
    width: 28px;
    text-align: center;
  }

  .section-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a1a1a;
    letter-spacing: -0.3px;
  }

  .section-card {
    border: 1px solid #e8e8e8;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
  }

  .section-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  }

  /* ===== Form Controls ===== */
  .form-control-modern {
    border-radius: 8px;
    border: 1.5px solid #e0e0e0;
    padding: 0.625rem 0.875rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
  }

  .form-control-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  .form-control-file {
    display: block;
    padding: 0.5rem 0;
    border-radius: 8px;
    cursor: pointer;
  }

  .image-upload-wrapper {
    background: #fafafa;
    padding: 1rem;
    border-radius: 8px;
    border: 2px dashed #d0d0d0;
    transition: all 0.2s ease;
  }

  .image-upload-wrapper:hover {
    border-color: #667eea;
    background: #f5f7ff;
  }

  /* ===== Input Groups ===== */
  .modern-input-group {
    display: flex;
    align-items: center;
    border-radius: 8px;
    overflow: hidden;
    border: 1.5px solid #e0e0e0;
  }

  .btn-icon-scan,
  .btn-icon-gen {
    background: #f5f5f5;
    border: none;
    padding: 0.625rem 0.875rem;
    color: #667eea;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .btn-icon-scan:hover,
  .btn-icon-gen:hover {
    background: #667eea;
    color: white;
  }

  /* ===== Tables ===== */
  .table-modern {
    margin-bottom: 0;
  }

  .table-modern thead {
    background: #f8f9fa;
    border-bottom: 2px solid #e0e0e0;
  }

  .table-modern thead th {
    font-weight: 600;
    color: #333;
    padding: 1rem 0.875rem;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
  }

  .table-modern tbody tr {
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s ease;
  }

  .table-modern tbody tr:hover {
    background-color: #f9f9f9;
  }

  .table-modern td {
    padding: 1rem 0.875rem;
    vertical-align: middle;
  }

  /* ===== Variant Input ===== */
  .variant-input-group {
    background: #f5f7ff;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e0e8ff;
  }

  /* ===== Combo Section ===== */
  .combo-search {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
  }

  .autocomplete {
    position: relative;
  }

  .autocomplete-input {
    width: 100%;
    padding: 0.625rem 2.875rem;
    border-radius: 8px;
    border: 1.5px solid #e0e0e0;
    font-size: 0.95rem;
  }

  .autocomplete-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  .autocomplete-result-list {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e0e0e0;
    border-top: none;
    border-radius: 0 0 8px 8px;
    list-style: none;
    margin: 0;
    padding: 0;
    max-height: 250px;
    overflow-y: auto;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .autocomplete-result {
    padding: 0.75rem 3rem;
    cursor: pointer;
    transition: background-color 0.15s ease;
  }

  .autocomplete-result:hover {
    background-color: #f5f7ff;
    color: #667eea;
  }

  .badge-wrapper {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .badge-primary-light {
    background-color: #e0e8ff;
    color: #667eea;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 600;
    display: inline-block;
    width: fit-content;
  }

  .combo-total {
    background: #f8f9fa;
    padding: 1.25rem;
    border-radius: 8px;
    border-left: 4px solid #667eea;
  }

  .total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .total-label {
    font-weight: 600;
    color: #333;
    font-size: 1rem;
  }

  .total-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #667eea;
  }

  /* ===== Options Grid ===== */
  .options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
  }

  /* ===== Form Actions ===== */
  .form-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .spinner-inline {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  /* ===== Sidebar ===== */
  .sticky-sidebar {
    position: sticky;
    top: 20px;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .summary-card,
  .help-card {
    background: white;
    border: 1px solid #e8e8e8;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  }

  .summary-title,
  .help-title {
    margin: 0 0 1rem;
    font-size: 1rem;
    font-weight: 700;
    color: #1a1a1a;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #f0f0f0;
  }

  .summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f5f5f5;
  }

  .summary-item:last-child {
    border-bottom: none;
  }

  .summary-label {
    font-size: 0.85rem;
    color: #666;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }

  .summary-value {
    font-size: 1rem;
    font-weight: 700;
    color: #667eea;
  }

  /* ===== Responsive ===== */
  @media (max-width: 768px) {
    .sticky-sidebar {
      position: relative;
      top: 0;
    }

    .options-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
