<template>
  <div class="pos-codecanyon">
    <!-- Top Navigation (Desktop / Tablet) -->
    <nav class="pos-header" v-if="productsReady">
      <div class="header-left">
        <div class="brand">
          <div class="brand-icon">
            <img v-if="currentUser && currentUser.logo" :src="'/images/'+currentUser.logo" alt="logo" style="width:100%;height:100%;object-fit:cover;border-radius:12px;" />
            <span v-else>{{ (currentUser && currentUser.company) ? (currentUser.company[0] || 'S') : 'S' }}</span>
          </div>
         
        </div>
      </div>

      <div class="header-center">
        <div class="search-wrapper">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
          <input 
            type="text" 
            :placeholder="$t('Scan_Search_Product_by_Code_Name')"
            class="search-input"
            v-model="search_input"
            @keyup="search"
          />
          <button class="action-btn-icon" @click="showModal" :title="$t('Scan')">
            <i class="i-QR-Code"></i>
          </button>
          <ul v-if="product_filter && product_filter.length" class="pos-autocomplete-results">
            <li
              class="pos-autocomplete-item"
              v-for="product_fil in product_filter"
              :key="product_fil.id"
              @mousedown="SearchProduct(product_fil)"
            >
              {{ getResultValue(product_fil) }}
            </li>
          </ul>
        </div>
      </div>

      <div class="header-right">
        <!-- Cash Register Status Widget (optional module) -->
        <div class="register-status" v-if="registerEnabled">
          <span class="mr-2">
            <span v-if="currentRegister && currentRegister.status === 'open'">🟢 {{$t('Open')}}</span>
            <span v-else>🔴 {{$t('Closed')}}</span>
          </span>
          <b-button
            size="sm"
            class="register-toggle-btn mr-1"
            @click="(currentRegister && currentRegister.status === 'open') ? $bvModal.show('CloseRegisterModal') : $bvModal.show('OpenRegisterModal')"
          >
            <span v-if="currentRegister && currentRegister.status === 'open'">{{$t('Close Register')}}</span>
            <span v-else>{{$t('Open Register')}}</span>
          </b-button>
        </div>
        <select v-model="sale.warehouse_id" class="warehouse-select" @change="Selected_Warehouse(sale.warehouse_id)">
          <option value="">{{$t('Select_Warehouse')}}</option>
          <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
            {{ wh.name }}
          </option>
        </select>
        <select v-model="selectedClientId" class="customer-select-header" @change="onClientSelected(selectedClientId)">
          <option value="">{{$t('Select_Customer')}}</option>
          <option v-for="client in clients" :key="client.id" :value="client.id">
            {{ client.name }}
          </option>
        </select>
        <button class="action-btn-icon btn-new-customer" @click="New_Client" :title="$t('New_Customer')">
          <i class="i-Add-User"></i>
        </button>
        
        <!-- Today's Sales -->
        <button class="action-btn-icon" :title="$t('Today_Sales')" @click="get_today_sales">
          <i class="i-Receipt"></i>
        </button>

        <!-- POS Settings (permission-based) -->
        <router-link 
          v-if="currentUserPermissions && currentUserPermissions.includes('pos_settings')"
          class="action-btn-icon btn-pos-settings"
          to="/app/settings/pos_settings"
          :title="$t('POS_Settings')"
        >
          <i class="i-Gear"></i>
        </router-link>

        <!-- Languages Dropdown -->
        <div class="dropdown action-btn-icon" v-if="show_language">
          <b-dropdown id="lang-dd" right offset="8" boundary="window" toggle-class="action-btn-icon dropdown-toggle-no-caret" no-caret>
            <template #button-content>
              <a href="#" class="action-btn-icon" @click.prevent>
                <i class="i-Globe"></i>
              </a>
            </template>
            <div class="menu-icon-grid lang-menu">
              
              <button class="lang-item" v-for="lang in languages_available" :key="lang.locale" @click="SetLocal(lang.locale)">
                <img :src="`/flags/${lang.flag}`" :alt="lang.name" class="flag-icon" />
                <span class="title-lang">{{ lang.name }}</span>
              </button>
            </div>
          </b-dropdown>
        </div>

        <!-- Fullscreen Toggle -->
        <button class="action-btn-icon btn-fullscreen" @click="handleFullScreen" :title="$t('Fullscreen')">
          <i class="i-Full-Screen"></i>
        </button>

        <div class="dropdown">
          <b-dropdown
            id="user-dd"
            right
            toggle-class="dropdown-toggle-no-caret p-0 bg-transparent border-0"
            no-caret
            variant="link"
          >
            <template #button-content>
              <img
                v-if="currentUser && currentUser.avatar"
                :src="'/images/avatar/'+currentUser.avatar"
                alt="avatar"
                class="user-profile"
              />
              <div v-else class="user-profile">{{ currentUser && currentUser.name ? currentUser.name.charAt(0).toUpperCase() : 'U' }}</div>
            </template>

            <div class="dropdown-menu-left" aria-labelledby="userDropdown">
              <div class="dropdown-header">
                <i class="i-Lock-User mr-1"></i>
                <span>{{ currentUser && (currentUser.username || currentUser.name) }}</span>
              </div>
              <router-link to="/app/profile" class="dropdown-item">{{$t('profil')}}</router-link>
              <router-link
                v-if="currentUserPermissions && currentUserPermissions.includes('setting_system')"
                to="/app/settings/System_settings"
                class="dropdown-item"
              >{{$t('Settings')}}</router-link>
              <a class="dropdown-item" href="#" @click.prevent="logoutUser">{{$t('logout')}}</a>
            </div>
          </b-dropdown>
        </div>
      </div>
    </nav>

    <!-- Top Navigation (Mobile ≤480px) -->
    <nav class="pos-header-mobile" v-if="productsReady">
      <!-- Row 1: Brand + Icons -->
      <div class="mobile-row mobile-top">
        <div class="brand">
          <div class="brand-icon">
            <img v-if="currentUser && currentUser.logo" :src="'/images/'+currentUser.logo" alt="logo" style="width:100%;height:100%;object-fit:cover;border-radius:12px;" />
            <span v-else>{{ (currentUser && currentUser.company) ? (currentUser.company[0] || 'S') : 'S' }}</span>
          </div>
        </div>
        <div class="top-icons">
          <router-link class="action-btn-icon" to="/app/dashboard" :title="$t('pos.Home')">
            <i class="i-Home"></i>
          </router-link>
          <button class="action-btn-icon" :title="$t('Today_Sales')" @click="get_today_sales">
            <i class="i-Receipt"></i>
          </button>
          <div class="dropdown action-btn-icon" v-if="show_language">
            <b-dropdown id="lang-dd-mobile" right offset="8" boundary="window" toggle-class="action-btn-icon dropdown-toggle-no-caret" no-caret>
              <template #button-content>
                <a href="#" class="action-btn-icon" @click.prevent>
                  <i class="i-Globe"></i>
                </a>
              </template>
              <div class="menu-icon-grid lang-menu">
                <button class="lang-item" v-for="lang in languages_available" :key="lang.locale" @click="SetLocal(lang.locale)">
                  <img :src="`/flags/${lang.flag}`" :alt="lang.name" class="flag-icon" />
                  <span class="title-lang">{{ lang.name }}</span>
                </button>
              </div>
            </b-dropdown>
          </div>
          <router-link 
            v-if="currentUserPermissions && currentUserPermissions.includes('pos_settings')"
            class="action-btn-icon btn-pos-settings"
            to="/app/settings/pos_settings"
            :title="$t('POS_Settings')"
          >
            <i class="i-Gear"></i>
          </router-link>
          <div class="dropdown">
            <b-dropdown id="user-dd-mobile" right toggle-class="dropdown-toggle-no-caret p-0 bg-transparent border-0" no-caret variant="link">
              <template #button-content>
                <img v-if="currentUser && currentUser.avatar" :src="'/images/avatar/'+currentUser.avatar" alt="avatar" class="user-profile" />
                <div v-else class="user-profile">{{ currentUser && currentUser.name ? currentUser.name.charAt(0).toUpperCase() : 'U' }}</div>
              </template>
              <div class="dropdown-menu-left" aria-labelledby="userDropdown">
                <div class="dropdown-header">
                  <i class="i-Lock-User mr-1"></i>
                  <span>{{ currentUser && (currentUser.username || currentUser.name) }}</span>
                </div>
                <router-link to="/app/profile" class="dropdown-item">{{$t('profil')}}</router-link>
                <router-link v-if="currentUserPermissions && currentUserPermissions.includes('setting_system')" to="/app/settings/System_settings" class="dropdown-item">{{$t('Settings')}}</router-link>
                <a class="dropdown-item" href="#" @click.prevent="logoutUser">{{$t('logout')}}</a>
              </div>
            </b-dropdown>
          </div>
        </div>
      </div>

      <!-- Row 2: Register Status (single toggle button) -->
      <div class="mobile-row" v-if="registerEnabled">
        <div class="register-status">
          <span class="mr-2">
            <span v-if="currentRegister && currentRegister.status === 'open'">🟢 {{$t('Open')}}</span>
            <span v-else>🔴 {{$t('Closed')}}</span>
          </span>
          <b-button size="sm" class="register-toggle-btn" @click="(currentRegister && currentRegister.status === 'open') ? $bvModal.show('CloseRegisterModal') : $bvModal.show('OpenRegisterModal')">
            <span v-if="currentRegister && currentRegister.status === 'open'">{{$t('Close Register')}}</span>
            <span v-else>{{$t('Open Register')}}</span>
          </b-button>
        </div>
      </div>

      <!-- Row 3: Warehouse (100%) -->
      <div class="mobile-row">
        <select v-model="sale.warehouse_id" class="warehouse-select" @change="Selected_Warehouse(sale.warehouse_id)">
          <option value="">{{$t('Select_Warehouse')}}</option>
          <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
        </select>
      </div>

      <!-- Row 4: Customer (100%) -->
      <div class="mobile-row">
        <select v-model="selectedClientId" class="customer-select-header" @change="onClientSelected(selectedClientId)">
          <option value="">{{$t('Select_Customer')}}</option>
          <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
        </select>
      </div>

      <!-- Row 5: Search -->
      <div class="mobile-row">
        <div class="search-wrapper">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
          <input
            type="text"
            :placeholder="$t('Scan_Search_Product_by_Code_Name')"
            class="search-input"
            v-model="search_input"
            @keyup="search"
          />
          <button class="action-btn-icon" @click="showModal" :title="$t('Scan')">
            <i class="i-QR-Code"></i>
          </button>
          <ul v-if="product_filter && product_filter.length" class="pos-autocomplete-results">
            <li class="pos-autocomplete-item" v-for="product_fil in product_filter" :key="product_fil.id" @mousedown="SearchProduct(product_fil)">
              {{ getResultValue(product_fil) }}
            </li>
          </ul>
        </div>
      </div>
    </nav>
    <div v-else class="pos-gate-loader">
      <div class="text-center">
        <div class="spinner lg spinner-primary"></div>
        <div class="mt-2">{{ $t('Loading') }}...</div>
      </div>
    </div>

    <!-- Main Content with Two Main Cards -->
    <div class="pos-container" v-if="productsReady">
      <!-- LEFT COLUMN: Summary + Added Products -->
      <div class="pos-column-left">
        <!-- CARD: Unified Products & Summary -->
        <div class="card card-unified-checkout">
          <div class="card-header">
            <h3>{{ $t('pos.Checkout') }}</h3>
            <span v-if="details.length > 0" class="badge-count">{{ details.length }} {{$t('pos.items')}}</span>
          </div>

          <!-- Cart Items Section -->
          <div class="cart-section">
            <div v-if="details.length === 0" class="empty-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
              </svg>
              <p>{{ $t('pos.No_items_added') }}</p>
              <span class="empty-hint">{{ $t('pos.Select_products_from_right_panel') }}</span>
            </div>
            <div v-else class="cart-items-grid">
              <div v-for="(item, index) in details" :key="index" class="cart-item-card">
                <div class="item-header">
                  <h4 class="item-name">{{ item.name }}</h4>
                  <button @mousedown.prevent @click="Modal_Updat_Detail(item)" type="button" class="edit-btn" :title="$t('pos.Edit')">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1.003 1.003 0 0 0 0-1.42l-2.34-2.34a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z"></path>
                    </svg>
                  </button>
                  <button @mousedown.prevent @click="delete_Product_Detail(item.detail_id)" type="button" class="remove-btn" :title="$t('pos.Remove')">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"></path>
                    </svg>
                  </button>
                </div>
                <p class="item-sku">{{ item.code }}</p>
                <div class="item-qty-section">
                  <div class="qty-controller">
                    <button @click="decrement(item, item.detail_id)" class="qty-btn" :title="$t('pos.Decrease')">−</button>
                    <input v-model.number="item.quantity" type="text" class="qty-input" @change="Verified_Qty(item, item.detail_id)" />
                    <button @click="increment(item.detail_id)" class="qty-btn" :title="$t('pos.Increase')">+</button>
                  </div>
                </div>
                <div class="item-price">
                  <div class="d-flex align-items-center justify-content-end pos-price-container">
                    <span class="mr-2 item-amount">{{ currentUser.currency }} {{ formatNumber(item.Total_price, 2) }}</span>
                    <select
                      class="form-control ml-2 pos-price-select"
                      v-model="item.price_type"
                      @change="onChangePriceType(item)"
                    >
                      <option value="retail">{{$t('Retail Price')}}</option>
                      <option value="wholesale">{{$t('Wholesale Price')}}</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Summary Section -->
          <div class="summary-section">
            <!-- Charges -->
            <div class="charges-section">
              <div class="charge-row">
                <label>{{$t('pos.Tax')}}</label>
                <div class="charge-input-group">
                  <input v-model.number="sale.tax_rate" type="text" placeholder="0" @keyup="keyup_OrderTax" class="flat-input" />
                  <span class="input-suffix">%</span>
                </div>
              </div>
              <div class="charge-row">
                <label>{{$t('Discount')}}</label>
                <div class="charge-input-group">
                  <input v-model.number="sale.discount" type="text" placeholder="0" @keyup="keyup_Discount" class="flat-input" />
                  <span class="input-suffix">{{ currentUser.currency }}</span>
                </div>
              </div>
              <div class="charge-row no-border-bottom">
                <label>{{$t('Shipping')}}</label>
                <div class="charge-input-group">
                  <input v-model.number="sale.shipping" type="text" placeholder="0" @keyup="keyup_Shipping" class="flat-input" />
                  <span class="input-suffix">{{ currentUser.currency }}</span>
                </div>
              </div>

              <!-- Available Points with Convert -->
              <div class="charge-row points-convert-row" :class="{ converted: pointsConverted }" v-if="clientIsEligible && currentUserPermissions && currentUserPermissions.includes('edit_tax_discount_shipping_sale')">
            <div class="points-left">
              <div class="points-header">
                <div class="label-line">
                  <i v-if="pointsConverted" class="i-Yes"></i>
                  <span>{{ $t('Available_Points') }}</span>
                </div>
                <div class="points-value">{{ selectedClientPoints }}</div>
              </div>
              <div v-if="discount_from_points > 0" class="discount-hint">
                ✅ {{ $t('Discount') }} {{ discount_from_points }} {{ currentUser.currency }} {{ $t('pos.will_be_applied') }}
              </div>
            </div>
                <div class="points-actions">
                  <input
                    v-model.number="points_to_convert"
                    @input="onPointsToConvertInput"
                    type="text"
                    min="0"
                    :max="selectedClientPoints"
                    step="1"
                    :disabled="selectedClientPoints === 0 || pointsConverted"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    placeholder="0"
                    class="flat-input mr-2"
                  />
                  <button
                    class="convert-btn"
                    :class="{ converted: pointsConverted }"
                    :disabled="selectedClientPoints === 0"
                    @click="convertPointsToDiscount"
                  >
                    <template v-if="!pointsConverted">
                      <i class="i-Money"></i> {{ $t('Convert') }}
                    </template>
                    <template v-else>
                      <i class="i-Yes"></i> {{ $t('Unconverted') }}
                    </template>
                  </button>
                </div>
              </div>
            </div>

            <!-- Totals -->
            <div class="summary-totals">
              <div class="total-row">
          <span class="total-label">{{$t('pos.Subtotal')}}</span>
                <span class="total-value">{{ formatNumber(total, 2) }}</span>
              </div>
              <div class="total-row">
          <span class="total-label">{{$t('pos.Tax')}}</span>
                <span class="total-value">{{ formatNumber(sale.TaxNet, 2) }}</span>
              </div>
              <div class="total-row">
          <span class="total-label discount-row">{{$t('pos.Discount')}}</span>
                <span class="total-value discount-value">-{{ formatNumber(sale.discount, 2) }}</span>
              </div>
              <div class="total-row">
          <span class="total-label">{{$t('pos.Shipping')}}</span>
                <span class="total-value">{{ formatNumber(sale.shipping, 2) }}</span>
              </div>
              <div class="summary-divider"></div>
              <div class="total-row grand-total">
          <span class="total-label">{{$t('pos.Grand_Total')}}</span>
                <span class="total-value gradient-text">{{ formatNumber(GrandTotal, 2) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: Products Grid -->
      <div class="card card-products">
        <div class="card-header">
          <h3>{{ $t('pos.Available_Products') }}</h3>
          <div class="filter-section">
            <select v-model="category_id" class="flat-select" @change="getProducts(1)">
              <option value="">{{$t('pos.All_Categories')}}</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
            <select v-model="brand_id" class="flat-select" @change="getProducts(1)">
              <option value="">{{$t('pos.All_Brands')}}</option>
              <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
            </select>
            <button @click="getAllCategory, GetAllBrands" class="reset-filters-btn" :title="$t('pos.Reset')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 4v6h6"></path>
                <path d="M23 20v-6h-6"></path>
                <path d="M20.49 9A9 9 0 0 0 5.64 5.64"></path>
                <path d="M3.51 15A9 9 0 0 0 18.36 18.36"></path>
              </svg>
            </button>
          </div>
        </div>

  <!-- Functional Modals (non-intrusive to main layout) -->
  <b-modal id="open_scan" hide-footer :title="$t('Scan')">
    <qrcode-scanner
      :qrbox="250"
      :fps="10"
      style="width: 100%; height: calc(100vh - 56px);"
      @result="onScan"
    />
    <div class="text-center mt-2">
      <b-button variant="primary" @click="$bvModal.hide('open_scan')">{{ $t('Close') }}</b-button>
    </div>
  </b-modal>



  <!-- Modern Payment Modal Alternative -->
  <modern-payment-modal 
    ref="modernPaymentModal"
    :payment-methods="payment_methods"
    :accounts="accounts"
    :saved-payment-methods="savedPaymentMethods"
    :currency="currentUser.currency"
    :client-id="selectedClientId"
    :warehouse-id="sale.warehouse_id"
    :sale="sale"
    :details="details"
    :grand-total="GrandTotal"
    :stripe-key="stripe_key"
    :discount-from-points="discount_from_points"
    :used-points="used_points"
    :draft-sale-id="draft_sale_id"
    @payment-success="onModernPaymentSuccess"
  />

  <b-modal hide-footer size="md" scrollable id="Show_invoice" :title="$t('Invoice_POS')" @shown="renderZatcaQrPos">
          <div id="invoice-POS">
            <div style="max-width:600px;margin:0px auto;padding:5px;font-family:Arial,sans-serif;font-size:10pt;">
              
              <!-- Header: Address (Left), Logo (Center), Store Name (Right) -->
              <div style="border:1px solid #000;padding:5px;margin-bottom:10px;">
                <div style="display:flex;width:100%;justify-content:space-between;align-items:center;">
                  <div style="flex:1;text-align:left;font-size:7pt;line-height:1.15;">
                    <div v-show="pos_settings.show_address">📍 {{invoice_pos.setting.CompanyAdress}}</div>
                    <div v-show="pos_settings.show_phone">📞 {{invoice_pos.setting.CompanyPhone}}</div>
                  </div>
                  <div style="flex:0 0 auto;text-align:center;padding:0 10px;">
                    <img :src="'/images/'+invoice_pos.setting.logo" alt style="max-width:70px;max-height:70px;display:block;">
                  </div>
                  <div style="flex:1;text-align:right;line-height:1.2;">
                    <div style="font-size:6pt;font-weight:bold;">SALAM</div>
                    <div style="font-size:6pt;">CENTER</div>
                  </div>
                </div>
              </div>

              <!-- Invoice Details Table -->
              <table style="width:100%;border:1px solid #000;border-collapse:collapse;margin-bottom:10px;font-size:9pt;">
                <tr>
                  <td style="border:1px solid #000;padding:6px 10px;font-weight:bold;background:#f5f5f5;width:25%;">{{$t('Ref')}}:</td>
                  <td style="border:1px solid #000;padding:6px 10px;">{{invoice_pos.sale.Ref}}</td>
                  <td style="border:1px solid #000;padding:6px 10px;font-weight:bold;background:#f5f5f5;width:25%;">{{$t('date')}}:</td>
                  <td style="border:1px solid #000;padding:6px 10px;">{{invoice_pos.sale.date}}</td>
                </tr>
                <tr v-show="pos_settings.show_customer">
                  <td style="border:1px solid #000;padding:6px 10px;font-weight:bold;background:#f5f5f5;">{{$t('Customer')}}:</td>
                  <td style="border:1px solid #000;padding:6px 10px;">{{invoice_pos.sale.client_name}}</td>
                  <td style="border:1px solid #000;padding:6px 10px;font-weight:bold;background:#f5f5f5;">{{$t('Seller')}}:</td>
                  <td style="border:1px solid #000;padding:6px 10px;">{{invoice_pos.sale.seller_name}}</td>
                </tr>
                <tr v-show="pos_settings.show_Warehouse">
                  <td style="border:1px solid #000;padding:6px 10px;font-weight:bold;background:#f5f5f5;">{{$t('warehouse')}}:</td>
                  <td colspan="3" style="border:1px solid #000;padding:6px 10px;">{{invoice_pos.sale.warehouse_name}}</td>
                </tr>
              </table>

              <!-- Items Table -->
              <table style="width:100%;border:2px solid #000;border-collapse:collapse;margin-bottom:10px;font-size:9pt;">
                <thead>
                  <tr style="background:#f5f5f5;">
                    <th style="border:1px solid #000;padding:8px 5px;text-align:center;font-weight:bold;width:5%;">#</th>
                    <th style="border:1px solid #000;padding:8px 5px;text-align:left;font-weight:bold;">{{$t('ProductName')}}</th>
                    <th style="border:1px solid #000;padding:8px 5px;text-align:center;font-weight:bold;width:15%;">{{$t('Code')}}</th>
                    <th style="border:1px solid #000;padding:8px 5px;text-align:center;font-weight:bold;width:10%;">{{$t('Qty')}}</th>
                    <th style="border:1px solid #000;padding:8px 5px;text-align:right;font-weight:bold;width:15%;">{{$t('Price')}}</th>
                    <th style="border:1px solid #000;padding:8px 5px;text-align:right;font-weight:bold;width:15%;">{{$t('Total')}}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(detail_invoice, index) in invoice_pos.details" :key="index" :style="index % 2 === 0 ? '' : 'background:#fafafa;'">
                    <td style="border:1px solid #000;padding:6px 5px;text-align:center;">{{index + 1}}</td>
                    <td style="border:1px solid #000;padding:6px 5px;">
                      {{detail_invoice.name}}
                      <br v-show="detail_invoice.is_imei && detail_invoice.imei_number !==null">
                      <small v-show="detail_invoice.is_imei && detail_invoice.imei_number !==null" style="color:#666;">SN: {{detail_invoice.imei_number}}</small>
                    </td>
                    <td style="border:1px solid #000;padding:6px 5px;text-align:center;">{{detail_invoice.code}}</td>
                    <td style="border:1px solid #000;padding:6px 5px;text-align:center;">{{formatNumber(detail_invoice.quantity,2)}} {{detail_invoice.unit_sale}}</td>
                    <td style="border:1px solid #000;padding:6px 5px;text-align:right;">{{invoice_pos.symbol}} {{formatNumber(detail_invoice.total/detail_invoice.quantity,2)}}</td>
                    <td style="border:1px solid #000;padding:6px 5px;text-align:right;">{{invoice_pos.symbol}} {{formatNumber(detail_invoice.total,2)}}</td>
                  </tr>
                </tbody>
              </table>

              <!-- Total Items Counter -->
              <div style="text-align:center;padding:8px;font-weight:bold;background:#f5f5f5;border:1px solid #000;border-top:2px solid #000;margin-bottom:10px;">
                {{$t('total_items')}}: {{invoice_pos.details.length}}
              </div>

              <!-- Totals Table -->
              <table style="width:50%;margin-left:auto;border:1px solid #000;border-collapse:collapse;margin-bottom:10px;font-size:9pt;">
                <tr v-show="pos_settings.show_discount && invoice_pos.sale.taxe > 0">
                  <td style="border:1px solid #000;padding:6px 10px;font-weight:bold;background:#f5f5f5;">{{$t('Tax')}}:</td>
                  <td style="border:1px solid #000;padding:6px 10px;text-align:right;">{{invoice_pos.symbol}} {{formatNumber(invoice_pos.sale.taxe,2)}}</td>
                </tr>
                <tr v-show="pos_settings.show_discount && invoice_pos.sale.discount > 0">
                  <td style="border:1px solid #000;padding:6px 10px;font-weight:bold;background:#f5f5f5;">{{$t('Discount')}}:</td>
                  <td style="border:1px solid #000;padding:6px 10px;text-align:right;">- {{invoice_pos.symbol}} {{formatNumber(invoice_pos.sale.discount,2)}}</td>
                </tr>
                <tr v-show="pos_settings.show_discount && invoice_pos.sale.shipping > 0">
                  <td style="border:1px solid #000;padding:6px 10px;font-weight:bold;background:#f5f5f5;">{{$t('Shipping')}}:</td>
                  <td style="border:1px solid #000;padding:6px 10px;text-align:right;">{{invoice_pos.symbol}} {{formatNumber(invoice_pos.sale.shipping,2)}}</td>
                </tr>
                <tr style="font-weight:bold;font-size:10pt;">
                  <td style="border:1px solid #000;padding:8px 10px;background:#f5f5f5;">{{$t('Total').toUpperCase()}}:</td>
                  <td style="border:1px solid #000;padding:8px 10px;text-align:right;">{{invoice_pos.symbol}} {{formatNumber(invoice_pos.sale.GrandTotal,2)}}</td>
                </tr>
                <tr>
                  <td style="border:1px solid #000;padding:6px 10px;font-weight:bold;background:#f5f5f5;">{{$t('paid_amount')}}:</td>
                  <td style="border:1px solid #000;padding:6px 10px;text-align:right;">{{invoice_pos.symbol}} {{formatNumber(invoice_pos.sale.paid_amount,2)}}</td>
                </tr>
                <tr v-show="invoice_pos.sale.paid_amount < invoice_pos.sale.GrandTotal">
                  <td style="border:1px solid #000;padding:6px 10px;font-weight:bold;background:#f5f5f5;">{{$t('due_amount')}}:</td>
                  <td style="border:1px solid #000;padding:6px 10px;text-align:right;">{{invoice_pos.symbol}} {{parseFloat(invoice_pos.sale.GrandTotal - invoice_pos.sale.paid_amount).toFixed(2)}}</td>
                </tr>
              </table>

              <!-- Payment Details -->
              <table style="width:100%;border:1px solid #000;border-collapse:collapse;margin-bottom:15px;font-size:9pt;" v-show="invoice_pos.sale.paid_amount > 0">
                <thead>
                  <tr style="background:#f5f5f5;">
                    <th style="border:1px solid #000;padding:6px 10px;text-align:left;font-weight:bold;">{{$t('Paid_By')}}:</th>
                    <th style="border:1px solid #000;padding:6px 10px;text-align:center;font-weight:bold;">{{$t('Amount')}}:</th>
                    <th style="border:1px solid #000;padding:6px 10px;text-align:right;font-weight:bold;">{{$t('Change')}}:</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="payment_pos in payments" :key="payment_pos.id">
                    <td style="border:1px solid #000;padding:6px 10px;">{{payment_pos.payment_method?payment_pos.payment_method.name:'---'}}</td>
                    <td style="border:1px solid #000;padding:6px 10px;text-align:center;">{{formatNumber(payment_pos.montant,2)}}</td>
                    <td style="border:1px solid #000;padding:6px 10px;text-align:right;">{{formatNumber(payment_pos.change,2)}}</td>
                  </tr>
                </tbody>
              </table>

              <!-- Signature Section -->
              <table style="width:100%;margin-bottom:15px;">
                <tr>
                  <td style="width:48%;">
                    <div style="border:1px solid #000;padding:40px 10px 10px 10px;text-align:center;font-size:9pt;font-weight:bold;">
                      {{$t('Customer_Signature')}}
                    </div>
                  </td>
                  <td style="width:4%;"></td>
                  <td style="width:48%;">
                    <div style="border:1px solid #000;padding:40px 10px 10px 10px;text-align:center;font-size:9pt;font-weight:bold;">
                      {{$t('Seller_Signature')}}
                    </div>
                  </td>
                </tr>
              </table>

              <!-- Footer Note -->
              <div v-show="pos_settings.show_note" style="margin-top:10px;padding:10px;border:1px solid #000;background:#f9f9f9;text-align:center;font-size:8pt;">
                <strong>{{pos_settings.note_customer}}</strong>
              </div>

              <!-- ZATCA (Fatoorah) QR Code -->
              <div v-if="invoice_pos.setting && invoice_pos.setting.zatca_enabled && invoice_pos.zatca_qr" class="mt-2 text-center">
                <div class="zatca-qr">
                  <div class="zatca-qr-title">ZATCA</div>
                  <div ref="zatcaQrcodePos"></div>
                </div>
              </div>
            </div>
          </div>
          <button @click="print_pos()" class="btn btn-outline-primary">
            <i class="i-Billing"></i>
            {{$t('print')}}
          </button>
        </b-modal>

  <b-modal id="show_draft_sales" size="lg" hide-footer :title="$t('Recent_Drafts')">
    <div>
      <table class="table table-sm">
        <thead>
          <tr>
            <th>{{ $t('date') }}</th>
            <th>{{ $t('Reference') }}</th>
            <th>{{ $t('Customer') }}</th>
            <th class="text-right">{{ $t('Total') }}</th>
            <th class="text-right">{{ $t('Action') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in draft_sales" :key="d.id">
            <td>{{ d.date }}</td>
            <td>{{ d.Ref }}</td>
            <td>{{ d.client_name }}</td>
            <td class="text-right">{{ formatNumber(d.GrandTotal, 2) }}</td>
            <td class="text-right">
              <b-button size="sm" variant="outline-success" class="mr-2" @click="loadDraftSale(d.id)" :disabled="openingDraftId === d.id" :title="openingDraftId === d.id ? $t('Loading') : $t('Open')">
                <template v-if="openingDraftId === d.id">
                  <span class="spinner sm spinner-primary"></span>
                </template>
                <template v-else>
                  <i class="i-Arrow-Right"></i>
                </template>
              </b-button>
              <b-button size="sm" variant="outline-danger" @click="Remove_Draft_Sale(d.id)" :title="$t('Delete')">
                <i class="i-Remove"></i>
              </b-button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </b-modal>

  <validation-observer ref="Update_Detail">
    <b-modal hide-footer size="lg" id="form_Update_Detail" :title="detail.name">
    <b-form @submit.prevent="submit_Update_Detail">
        <b-row>
          <!-- Unit Price + Price Type -->
          <b-col lg="12" class="mb-2" v-if="detailLoading">
            <div class="text-center py-3">
              <div class="spinner sm spinner-primary"></div>
            </div>
          </b-col>
          <b-col lg="6" md="6" sm="12" v-show="!detailLoading">
            <validation-provider
              name="Product Price"
              :rules="{ required: true , regex: /^\d*\.?\d*$/}"
              v-slot="validationContext"
            >
              <b-form-group :label="$t('ProductPrice') + ' ' + '*'" id="Price-input">
                <div class="d-flex align-items-center">
                  <b-form-input
                    label="Product Price"
                    v-model="detail.Unit_price"
                    :state="getValidationState(validationContext)"
                    aria-describedby="Price-feedback"
                    class="mr-2"
                  ></b-form-input>
                  <select
                    class="form-control pos-price-select"
                    v-model="detail.price_type"
                    @change="onChangePriceType(detail)"
                  >
                    <option :value="'retail'">{{$t('Retail Price')}}</option>
                    <option :value="'wholesale'">{{$t('Wholesale Price')}}</option>
                  </select>
                </div>
                <b-form-invalid-feedback id="Price-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
              </b-form-group>
            </validation-provider>
          </b-col>

          <!-- Tax Method -->
          <b-col lg="6" md="6" sm="12" v-show="!detailLoading">
            <validation-provider name="Tax Method" :rules="{ required: true}">
              <b-form-group slot-scope="{ valid, errors }" :label="$t('TaxMethod') + ' ' + '*'"><v-select
                  :class="{'is-invalid': !!errors.length}"
                  :state="errors[0] ? false : (valid ? true : null)"
                  v-model="detail.tax_method"
                  :reduce="label => label.value"
                  :placeholder="$t('Choose_Method')"
                  :options="
                 [
                  {label: 'Exclusive', value: '1'},
                  {label: 'Inclusive', value: '2'}
                 ]"
                ></v-select>
                <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
      </b-form-group>
            </validation-provider>
          </b-col>

          <!-- Tax -->
          <b-col lg="6" md="6" sm="12" v-show="!detailLoading">
            <validation-provider
              name="Tax"
              :rules="{ required: true , regex: /^\d*\.?\d*$/}"
              v-slot="validationContext"
            >
              <b-form-group :label="$t('Tax') + ' ' + '*'"><b-input-group append="%">
                  <b-form-input
                    label="Tax"
                    v-model="detail.tax_percent"
                    :state="getValidationState(validationContext)"
                    aria-describedby="Tax-feedback"
                  ></b-form-input>
                </b-input-group>
                <b-form-invalid-feedback
                  id="Tax-feedback"
                >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
      </b-form-group>
            </validation-provider>
          </b-col>

          <!-- Discount Method -->
          <b-col lg="6" md="6" sm="12" v-show="!detailLoading">
            <validation-provider name="Discount Method" :rules="{ required: true}">
              <b-form-group slot-scope="{ valid, errors }" :label="$t('Discount_Method') + ' ' + '*'"><v-select
                  v-model="detail.discount_Method"
                  :reduce="label => label.value"
                  :placeholder="$t('Choose_Method')"
                  :class="{'is-invalid': !!errors.length}"
                  :state="errors[0] ? false : (valid ? true : null)"
                  :options="
                    [
                      {label: 'Percent %', value: '1'},
                      {label: 'Fixed', value: '2'}
                    ]"
                ></v-select>
                <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
      </b-form-group>
            </validation-provider>
          </b-col>

          <!-- Discount Rate -->
          <b-col lg="6" md="6" sm="12" v-show="!detailLoading">
            <validation-provider
              name="Discount Rate"
              :rules="{ required: true , regex: /^\d*\.?\d*$/}"
              v-slot="validationContext"
            >
              <b-form-group :label="$t('Discount') + ' ' + '*'"><b-form-input
                  label="Discount"
                  v-model="detail.discount"
                  :state="getValidationState(validationContext)"
                  aria-describedby="Discount-feedback"
                ></b-form-input>
                <b-form-invalid-feedback
                  id="Discount-feedback"
                >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
      </b-form-group>
            </validation-provider>
          </b-col>

          <!-- Unit Sale -->
          <b-col lg="6" md="6" sm="12" v-if="detail.product_type != 'is_service'" v-show="!detailLoading">
            <validation-provider name="Unit Sale" :rules="{ required: true}">
              <b-form-group slot-scope="{ valid, errors }" :label="$t('UnitSale') + ' ' + '*'"><v-select
                  :class="{'is-invalid': !!errors.length}"
                  :state="errors[0] ? false : (valid ? true : null)"
                  v-model="detail.sale_unit_id"
                  :placeholder="$t('Choose_Unit_Sale')"
                  :reduce="label => label.value"
                  :options="units.map(units => ({label: units.name, value: units.id}))"
                />
                <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
              </b-form-group>
            </validation-provider>
          </b-col>

          <!-- Imei or serial numbers -->
          <b-col lg="12" md="12" sm="12" v-show="detail.is_imei && !detailLoading">
            <b-form-group :label="$t('Add_product_IMEI_Serial_number')">
              <b-form-input
                label="Add_product_IMEI_Serial_number"
                v-model="detail.imei_number"
                :placeholder="$t('Add_product_IMEI_Serial_number')"
              ></b-form-input>
            </b-form-group>
          </b-col>

          <b-col md="12">
            <b-form-group>
              <b-button variant="primary" type="submit">{{$t('submit')}}</b-button>
            </b-form-group>
          </b-col>
        </b-row>
    </b-form>
  </b-modal>
  </validation-observer>

  <validation-observer ref="Create_Customer">
  <b-modal id="New_Customer" hide-footer size="lg" :title="$t('New_Customer')">
    <b-form @submit.prevent="Submit_Customer" class="new-customer-form">
      <b-row>
        <b-col md="6" sm="12">
          <b-form-group :label="$t('Name')">
            <b-form-input v-model="client.name" :placeholder="$t('Name')" required />
          </b-form-group>
        </b-col>
        <b-col md="6" sm="12">
          <b-form-group :label="$t('Email')">
            <b-form-input type="email" v-model="client.email" :placeholder="$t('Email')" />
          </b-form-group>
        </b-col>

        <b-col md="6" sm="12">
          <b-form-group :label="$t('Phone')">
            <b-form-input v-model="client.phone" :placeholder="$t('Phone')" />
          </b-form-group>
        </b-col>
        <b-col md="6" sm="12">
          <b-form-group :label="$t('Tax_Number')">
            <b-form-input v-model="client.tax_number" :placeholder="$t('Tax_Number')" />
          </b-form-group>
        </b-col>

        <b-col md="6" sm="12">
          <b-form-group :label="$t('Country')">
            <b-form-input v-model="client.country" :placeholder="$t('Country')" />
          </b-form-group>
        </b-col>
        <b-col md="6" sm="12">
          <b-form-group :label="$t('City')">
            <b-form-input v-model="client.city" :placeholder="$t('City')" />
          </b-form-group>
        </b-col>

        <b-col md="12">
          <b-form-group :label="$t('Adress')">
            <b-form-input v-model="client.adresse" :placeholder="$t('Adress')" />
          </b-form-group>
        </b-col>

        <b-col md="12">
          <b-form-group>
            <div class="loyalty-eligible-row">
              <b-form-checkbox v-model="client.is_royalty_eligible" switch class="mb-0 loyalty-switch">
                {{ $t('Loyalty_Eligible') }}
              </b-form-checkbox>
              <small class="loyalty-help text-muted">{{ $t('Loyalty_Points_Help') }}</small>
            </div>
          </b-form-group>
        </b-col>

        <b-col cols="12" class="d-flex justify-content-end">
        <b-button variant="secondary" class="mr-2" @click="$bvModal.hide('New_Customer')">{{ $t('Close') }}</b-button>
        <b-button variant="primary" type="submit">{{ $t('Save') }}</b-button>
        </b-col>
      </b-row>
    </b-form>
  </b-modal>
  </validation-observer>

  <b-modal id="modal_today_sales" hide-footer size="lg" :title="$t('Today_Sales')">
    <div class="today-sales-grid">
      <div class="ts-card">
        <div class="ts-icon primary"><i class="i-Money-2"></i></div>
        <div class="ts-content">
          <div class="ts-label">{{ $t('Total_Sales') }}</div>
          <div class="ts-value">{{ currentUser.currency }} {{ formatNumber(today_sales.total_sales_amount || 0, 2) }}</div>
        </div>
      </div>

      <div class="ts-card">
        <div class="ts-icon success"><i class="i-Yes"></i></div>
        <div class="ts-content">
          <div class="ts-label">{{ $t('Total_Amount_Paid') }}</div>
          <div class="ts-value">{{ currentUser.currency }} {{ formatNumber(today_sales.total_amount_paid || 0, 2) }}</div>
        </div>
      </div>

      <div class="ts-card">
        <div class="ts-icon warning"><i class="i-Money"></i></div>
        <div class="ts-content">
          <div class="ts-label">{{ $t('Cash') }}</div>
          <div class="ts-value">{{ currentUser.currency }} {{ formatNumber(today_sales.total_cash || 0, 2) }}</div>
        </div>
      </div>

      <div class="ts-card">
        <div class="ts-icon info"><i class="i-Credit-Card"></i></div>
        <div class="ts-content">
          <div class="ts-label">{{ $t('Credit_Card') }}</div>
          <div class="ts-value">{{ currentUser.currency }} {{ formatNumber(today_sales.total_credit_card || 0, 2) }}</div>
        </div>
      </div>

      <div class="ts-card">
        <div class="ts-icon danger"><i class="i-File"></i></div>
        <div class="ts-content">
          <div class="ts-label">{{ $t('Cheque') }}</div>
          <div class="ts-value">{{ currentUser.currency }} {{ formatNumber(today_sales.total_cheque || 0, 2) }}</div>
        </div>
      </div>
    </div>
  </b-modal>

        <!-- Products Grid -->
        <div class="products-container">
          <div v-if="paginated_Products.length === 0" class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.35-4.35"></path>
            </svg>
            <p>{{ $t('pos.No_products_found') }}</p>
          </div>
          <div v-else class="products-grid">
            <div 
              v-for="product in paginated_Products" 
              :key="product.product_variant_id ? (product.id + '-' + product.product_variant_id) : product.id"
              class="product-card"
              @click="handleProductClick(product)"
            >
              <div v-if="uiLoadingProductId === (product.product_variant_id ? (product.id + '-' + product.product_variant_id) : product.id)" class="card-loading-overlay">
                <div class="spinner sm spinner-primary"></div>
              </div>
              <div class="product-image-wrapper">
                <img
                  v-if="product.image"
                  :src="resolveProductImage(product.image)"
                  :alt="product.name"
                  class="product-image"
                  @error="product.image = null"
                />
                <div v-else class="product-image-placeholder">{{ product.category ? product.category[0] : 'P' }}</div>
                <div v-if="product.discount" class="discount-badge">-{{ product.discount }}%</div>
              </div>
              <div class="product-details">
                <h4 class="product-name">{{ product.name }}</h4>
                <p class="product-brand">{{ product.code }}</p>
                <p class="product-stock" v-if="product.product_type !== 'is_service'">
                  {{ formatNumber(product.qte_sale, 2) }} {{ product.unitSale }}
                </p>
                <div class="product-footer">
                  <span class="product-price">{{ currentUser.currency }} {{ formatNumber(product.Net_price, 2) }}</span>
                  <button class="add-to-cart-btn" @click.stop="handleProductClick(product)" :disabled="product.product_type !== 'is_service' && product.qte_sale <= 0" :title="$t('pos.Add_to_cart')">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination Footer -->
        <div v-if="paginated_Products.length > 0" class="pagination-footer">
          <button 
            class="pagination-btn"
            @click="Product_onPageChanged(product_currentPage - 1)"
            :disabled="product_currentPage === 1"
            :title="$t('pos.Previous_Page')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
          </button>

          <div class="pagination-info">
            <span class="page-number">{{$t('pos.Page')}} {{ product_currentPage }}</span>
            <span class="products-count">{{ product_totalRows }} {{$t('pos.products')}}</span>
          </div>

          <div class="pagination-dots">
            <button
              v-for="(item, idx) in product_visiblePageItems"
              :key="`pp-${idx}-${item}`"
              class="pagination-dot"
              :class="{ active: item === product_currentPage, ellipsis: item === '…' }"
              :disabled="item === '…'"
              @click="onProductPageItemClick(item)"
              :title="item === '…' ? '' : `${$t('pos.Go_to_page')} ${item}`"
            >
              {{ item }}
            </button>
          </div>

          <button 
            class="pagination-btn"
            @click="Product_onPageChanged(product_currentPage + 1)"
            :disabled="product_currentPage === product_lastPage"
            :title="$t('pos.Next_Page')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Open Register Modal -->
    <b-modal id="OpenRegisterModal" :title="$t('Open Register')" hide-footer>
      <div class="form-group">
        <label>{{$t('warehouse')}}</label>
        <b-form-select v-model="registerForm.warehouse_id" :options="warehouseOptions"></b-form-select>
      </div>
      <div class="form-group">
        <label>{{$t('Opening_Balance')}}</label>
        <input type="text" min="0" step="0.01" class="form-control" v-model.number="registerForm.opening_balance" />
      </div>
      <div class="form-group">
        <label>{{$t('notes')}}</label>
        <textarea class="form-control" v-model="registerForm.notes"></textarea>
      </div>
      <div class="text-right">
        <b-button variant="secondary" class="mr-2" @click="$bvModal.hide('OpenRegisterModal')">{{$t('Cancel')}}</b-button>
        <b-button variant="success" @click="submitOpenRegister" :disabled="registerBusy">{{$t('Open Register')}}</b-button>
      </div>
    </b-modal>

    <!-- Close Register Modal -->
    <b-modal id="CloseRegisterModal" :title="$t('Close Register')" hide-footer>
      <div class="form-group">
        <label>{{$t('Counted_Cash')}}</label>
        <input type="text" min="0" step="0.01" class="form-control" v-model.number="closeForm.counted_cash" />
      </div>
      <div class="form-group">
        <label>{{$t('Closing_Notes')}}</label>
        <textarea class="form-control" v-model="closeForm.notes"></textarea>
      </div>
      <div class="text-right">
        <b-button variant="secondary" class="mr-2" @click="$bvModal.hide('CloseRegisterModal')">{{$t('Cancel')}}</b-button>
        <b-button variant="danger" @click="submitCloseRegister" :disabled="registerBusy">{{$t('Close Register')}}</b-button>
      </div>
    </b-modal>
    <!-- FIXED FOOTER BAR -->
    <div class="pos-footer-bar" v-if="productsReady">
      <router-link class="action-btn action-btn-secondary" to="/app/dashboard" :title="$t('pos.Home')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 11l9-8 9 8"></path>
          <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"></path>
        </svg>
        <span>{{ $t('pos.Home') }}</span>
      </router-link>

      <button class="action-btn action-btn-secondary" @click="Reset_Pos" :title="$t('pos.Clear_all_items')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M1 4v6h6"></path>
          <path d="M23 20v-6h-6"></path>
          <path d="M20.49 9A9 9 0 0 0 5.64 5.64"></path>
          <path d="M3.51 15A9 9 0 0 0 18.36 18.36"></path>
        </svg>
        <span>{{ $t('pos.Reset') }}</span>
      </button>

      <button class="action-btn action-btn-secondary" @click="Show_Draft_Sales" :title="$t('pos.Drafts_list')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="14" rx="2" ry="2"></rect>
          <path d="M7 8h10M7 12h8"></path>
        </svg>
        <span>{{ $t('pos.Recent_Drafts') }}</span>
      </button>

      <button class="action-btn action-btn-secondary" @click="Submit_Draft" :disabled="DraftProcessing" :title="$t('pos.Hold_this_sale')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="M10 9v6"></path>
          <path d="M14 9v6"></path>
        </svg>
        <span>{{ DraftProcessing ? $t('pos.Saving') : $t('pos.Hold') }}</span>
      </button>

      <div class="footer-space"></div>

      <div class="total-payable-section">
        <span class="payable-label">{{ $t('pos.Total_Payable') }}</span>
        <span class="payable-amount">{{ formatNumber(GrandTotal, 2) }}</span>
      </div>

      <button class="action-btn action-btn-primary" @click="openModernPaymentModal" :disabled="paymentProcessing || details.length === 0" :title="$t('pos.Complete_and_process_payment')">
        <svg viewBox="0 0 24 24" fill="currentColor">
          <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"></path>
        </svg>
        <span>{{ paymentProcessing ? $t('pos.Processing') : $t('pos.Pay_Now') }}</span>
      </button>
    </div>
  </div>
</template>

<script>
import NProgress from "nprogress";
import { mapActions, mapGetters } from "vuex";
import vueEasyPrint from "vue-easy-print";
import VueBarcode from "vue-barcode";
import Util from "../../../utils";
import { loadStripe } from "@stripe/stripe-js";
import ModernPaymentModal from "../components/ModernPaymentModal.vue";

export default {
  components: {
    vueEasyPrint,
    barcode: VueBarcode,
    ModernPaymentModal,
  },
  metaInfo: {
    title: "POS"
  },
  data() {
    return {
     
      sendEmail: false,
      sendSMS: false,
      stripe: {},
      stripe_key: "",
      cardElement: {},
      paymentProcessing: false,
      DraftProcessing: false,
      savedPaymentMethods: [],
      hasSavedPaymentMethod: false,
      useSavedPaymentMethod: false,
      selectedCard:null,
      card_id:'',
      is_new_credit_card: false,
      submit_showing_credit_card: false,

      totalRows_draft_sales: "",
      draft_sales:[],
      limit: "10",
          draft_sale_id: '',
      openingDraftId: null,

      serverParams: {
        sort: {
          field: "id",
          type: "desc"
        },
        page: 1,
        perPage: 10
      },

      client_name:'',
      paymentLines: [
        { 
          // only the first line shows Received Amount
          amount: 0, 
          payment_method_id: '', 
        }
      ],
      globalPaymentNote: '', 
      selectedAccount: null, 
      payment_methods:[],
      // --- Customer Display (broadcast) ---
      _cd_broadcast_timer: null,
      search_category: '',
      search_brand: '',
      focused: false,
      timer:null,
      search_input:'',
      product_filter:[],
      isLoading: true,
      load_product: true,
      GrandTotal: 0,
      total: 0,
      Ref: "",
      clients: [],
      units: [],
      warehouses: [],
      payments: [],
      products: [],
      products_pos: [],
      details: [],
      detail: {},
      categories: [],
      brands: [],
      accounts: [],
      pos_settings:{},
      product_currentPage: 1,
      paginated_Products: [],
      product_perPage: 12,
      product_totalRows: 0,
      paginated_Brands: "",
      brand_currentPage: 1,
      brand_perPage: 3,
      paginated_Category: "",
      category_currentPage: 1,
      category_perPage: 3,
      barcodeFormat: "CODE128",
      today_sales:{
        total_sales_amount: "",
        total_amount_paid: "",
        today: "",
        total_cash: "",
        total_credit_card: "",
        total_cheque: "",
      },
      invoice_pos: {
        sale: {
          Ref: "",
          client_name: "",
          discount: "",
          taxe: "",
          date: "",
          tax_rate: "",
          shipping: "",
          GrandTotal: "",
          paid_amount: ""
        },
        details: [],
        setting: {
          logo: "",
          CompanyName: "",
          CompanyAdress: "",
          email: "",
          CompanyPhone: "",
          vat_number: "",
          company_name_ar: "",
          zatca_enabled: false
        },
        zatca_qr: ""
      },
      selectedClientPoints: 0,
      showPointsSection: false,
      points_to_convert: 0,
      discount_from_points: 0,
      used_points: 0,
      clientIsEligible: false,
      pointsConverted: false,
      point_to_amount_rate: 0,
      zatcaRenderedPos: false,
      sale: {
        warehouse_id: "",
        client_id: "",
        tax_rate: 0,
        shipping: 0,
        discount: 0,
        TaxNet: 0,
        notes:'',
      },
      client: {
        id: "",
        name: "",
        code: "",
        email: "",
        phone: "",
        country: "",
        tax_number: "",
        city: "",
        adresse: "",
        is_royalty_eligible: "",
      },
      category_id: "",
      brand_id: "",
      languages_available:[],
      product: {
        id: "",
        code: "",
        product_type: "",
        current: "",
        quantity: "",
        check_qty: "",
        discount: "",
        DiscountNet: "",
        discount_Method: "",
        sale_unit_id: "",
        fix_stock: "",
        fix_price: "",
        name: "",
        unitSale: "",
        Net_price: "",
        Unit_price: "",
        Unit_price_wholesale: "",
        wholesale_Net_price: "",
        min_price: 0,
        price_type: 'retail',
        retail_unit_price: "",
        wholesale_unit_price: "",
        Total_price: "",
        subtotal: "",
        product_id: "",
        detail_id: "",
        taxe: "",
        tax_percent: "",
        tax_method: "",
        product_variant_id: "",
        is_imei: "",
        imei_number:"",
      },
      sound: "/audio/Beep.wav",
      audio: new Audio("/audio/Beep.wav"),
      // Cash Register state (optional module)
      registerEnabled: true,
      currentRegister: null,
      registerBusy: false,
      registerForm: { warehouse_id: '', opening_balance: 0, notes: '' },
      closeForm: { counted_cash: 0, notes: '' },
      cashMove: { type: 'in', amount: 0, notes: '' },
      warehouseOptions: [],
      selectedClientId: "",
      productsReady: false,
      uiLoadingProductId: null,
      detailLoading: false,
      uiLoadingDetailId: null,
      detailLoading: false
    };
  },
  computed: {
    ...mapGetters(["currentUser", "currentUserPermissions","show_language"]),

    // Total pages for product list
    product_lastPage() {
      const total = Number(this.product_totalRows || 0);
      const per = Number(this.product_perPage || 1);
      const pages = Math.ceil(total / per);
      return pages > 0 ? pages : 1;
    },

    // Windowed list of page items with ellipses, e.g. [1, '…', 4, 5, 6, '…', 20]
    product_visiblePageItems() {
      const current = Number(this.product_currentPage || 1);
      const last = this.product_lastPage;
      const windowSize = 1; // pages to show on each side of current
      const pages = new Set([1, last]);
      for (let p = current - windowSize; p <= current + windowSize; p++) {
        if (p >= 1 && p <= last) pages.add(p);
      }
      const sorted = Array.from(pages).sort((a, b) => a - b);
      const out = [];
      let prev = null;
      for (const p of sorted) {
        if (prev !== null && p - prev > 1) out.push('…');
        out.push(p);
        prev = p;
      }
      return out;
    },


    anyCreditCardUsed() {
      return this.paymentLines.some(p => p.payment_method_id === '1' || p.payment_method_id === 1);
    },

     // Sum of all entered payment lines
    totalPaid() {
      return this.paymentLines.reduce((sum, p) => sum + Number(p.amount || 0), 0).toFixed(2);
    },
    // What's still due (never negative)
    balance() {
      const b = this.GrandTotal - this.totalPaid;
      return (b > 0 ? b : 0).toFixed(2);
    },
    // How much to return if over-paid
    changeReturn() {
      const c = this.totalPaid - this.GrandTotal;
      return (c > 0 ? c : 0).toFixed(2);
    },

    brand_totalRows() {
      return this.brands.length;
    },

    category_totalRows() {
      return this.categories.length;
    },

    filteredCategories() {
      if (this.search_category.trim() === '') {
        return this.paginated_Category;
      }
      return this.paginated_Category.filter(category =>
        category.name.toLowerCase().includes(this.search_category.toLowerCase())
      );
    },

    filteredBrands() {
      if (this.search_brand.trim() === '') {
        return this.paginated_Brands;
      }
      return this.paginated_Brands.filter(brand =>
        brand.name.toLowerCase().includes(this.search_brand.toLowerCase())
      );
    },

    displaySavedPaymentMethods() {
      if(this.hasSavedPaymentMethod){
        return true;
      }else{
        return false;
      }
    },

    displayFormNewCard() {
      if(this.useSavedPaymentMethod){
        return false;
      }else{
        return true;
      }
    },

    isSelectedCard() {
      return card => this.selectedCard === card;
    },

    columns_draft_sales() {
      return [
        {
          label: this.$t("date"),
          field: "date",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Customer"),
          field: "client_name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse_name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
       
        {
          label: this.$t("Total"),
          field: "GrandTotal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },

        {
          label: this.$t("Action"),
          field: "actions",
          html: true,
          tdClass: "text-right",
          thClass: "text-right",
          sortable: false
        }
     
      ];
    }

    

  },
  watch: {
    'invoice_pos.zatca_qr'(val){
      if(val){
        this.$nextTick(() => this.renderZatcaQrPos());
      }
    }
  },
  mounted() {
    this.changeSidebarProperties();
    this.paginate_products(this.product_perPage, 0);
  },
  methods: {
    async refreshCurrentRegister() {
      try {
        if (!this.currentUser) return;
        const params = {};
        if (this.sale && this.sale.warehouse_id) params.warehouse_id = this.sale.warehouse_id;
        const { data } = await axios.get(`cash-registers/current/${this.currentUser.id}`, { params });
        this.currentRegister = data.register || null;
      } catch (e) {
        this.currentRegister = null;
      }
    },
    // ---------- Customer Display helpers ----------
    _cd_emit(payload, completed = false) {
      try {
        if (window.Echo && window.Echo.channel) {
          // Prefer whisper (local-only) to avoid backend dependency
          window.Echo.channel('pos-cart').whisper('cart-updated', payload);
          if (completed) window.Echo.channel('pos-cart').whisper('sale-completed', true);
          return;
        }
      } catch (e) { /* ignore */ }
      // Fallback: POST to public API to broadcast and store last cart
      try {
        window.axios && window.axios.post('pos/customer-display/broadcast', { cart: payload, completed });
      } catch (e) { /* ignore */ }
    },
    _cd_queue_broadcast() {
      if (this._cd_broadcast_timer) clearTimeout(this._cd_broadcast_timer);
      this._cd_broadcast_timer = setTimeout(() => {
        const payload = {
          currency: (this.currentUser && this.currentUser.currency) || '',
          discount: this.sale && this.sale.discount ? this.sale.discount : 0,
          TaxNet: this.sale && this.sale.TaxNet ? this.sale.TaxNet : 0,
            shipping: this.sale && this.sale.shipping ? this.sale.shipping : 0,
          GrandTotal: this.GrandTotal || 0,
          details: (this.details || []).map(d => ({
            name: d.name,
            quantity: d.quantity,
              // Back-compat: keep total, but also send unit_price and line_total explicitly
              total: (d.total != null ? d.total : (d.Net_price || 0)),
              unit_price: (d.Net_price != null ? d.Net_price : (d.Unit_price != null ? d.Unit_price : (d.price != null ? d.price : 0))),
              line_total: (d.total != null ? d.total : ((d.Net_price || 0) * (d.quantity || 0))),
          }))
        };
        this._cd_emit(payload);
      }, 200); // small debounce
    },
    async submitOpenRegister() {
      if (!this.registerForm.warehouse_id) {
        this.makeToast('warning', this.$t('Please_select_warehouse'), this.$t('Warning'));
        return;
      }
      this.registerBusy = true;
      try {
        const { data } = await axios.post('cash-registers/open', {
          user_id: this.currentUser.id,
          warehouse_id: this.registerForm.warehouse_id,
          opening_balance: this.registerForm.opening_balance || 0,
          notes: this.registerForm.notes || ''
        });
        this.$bvModal.hide('OpenRegisterModal');
        this.makeToast('success', this.$t('RegisterOpened'), this.$t('Success'));
        // Immediately reflect UI without waiting for fetch
        this.currentRegister = data && data.register ? data.register : this.currentRegister;
        // Fallback refresh to ensure latest from server
        this.refreshCurrentRegister();
      } catch (e) {
        const msg = e.response?.data?.message || this.$t('OperationFailed');
        this.makeToast('danger', msg, this.$t('Failed'));
      } finally {
        this.registerBusy = false;
      }
    },
    async submitCloseRegister() {
      if (!this.currentRegister) return;
      this.registerBusy = true;
      try {
        await axios.post('cash-registers/close', {
          register_id: this.currentRegister.id,
          counted_cash: this.closeForm.counted_cash || 0,
          notes: this.closeForm.notes || ''
        });
        this.$bvModal.hide('CloseRegisterModal');
        this.makeToast('success', this.$t('RegisterClosed'), this.$t('Success'));
        this.refreshCurrentRegister();
      } catch (e) {
        const msg = e.response?.data?.message || this.$t('OperationFailed');
        this.makeToast('danger', msg, this.$t('Failed'));
      } finally {
        this.registerBusy = false;
      }
    },
    resolveProductImage(imagePath) {
      if (!imagePath) return '';
      // If already an absolute URL, return as is
      if (/^https?:\/\//i.test(imagePath)) return imagePath;
      // Normalize and prefix with public images directory
      const clean = String(imagePath).replace(/^\/+/, '');
      return `/images/products/${clean}`;
    },
    getResultValue(result) {
      return result.code + " (" + result.name + ")";
    },

    SearchProduct(result) {
      if (this.load_product) {
        this.load_product = false;
        this.product = {};

        if (result.product_type == 'is_service') {
          this.product.quantity = 1;
          this.product.code = result.code;
        } else {
          this.product.code = result.code;
          this.product.current = result.qte_sale;
          this.product.fix_stock = result.qte;
          this.product.quantity = result.qte_sale < 1 ? result.qte_sale : 1;
        }

        this.product.product_variant_id = result.product_variant_id;
        this.Get_Product_Details(result.id, result.product_variant_id);

        this.search_input = '';
        this.product_filter = [];
      } else {
        this.makeToast(
          "warning",
          this.$t("Please_wait_until_the_product_is_loaded"),
          this.$t("Warning")
        );
      }
    },
    ...mapActions(["changeSidebarProperties", "changeThemeMode", "logout"]),
    // ... All methods from old_pos will be injected here
    logoutUser() {
      this.$store.dispatch("logout");
    },
    
     handleFocus() {
      this.focused = true
    },
    handleBlur() {
      this.focused = false
    },

    
    showModal() {
      this.$bvModal.show('open_scan');
      
    },

    onScan (decodedText, decodedResult) {
      const code = decodedText;
      this.search_input = code;
      this.search();
      this.$bvModal.hide('open_scan');
    },

    addPaymentLine() {
      this.paymentLines.push({
        amount: 0,
        payment_method_id: '',
      })
    },
    removePaymentLine(idx) {
      if (this.paymentLines.length > 1) {
        this.paymentLines.splice(idx, 1)
      }
    },

    setQuickAmount(val) {
      // assign to current active line (e.g. last)
      const line = this.paymentLines[this.paymentLines.length - 1];
      line.amount = val;
    },
    appendDigit(d) {
      // append to the last line's amount
      let line = this.paymentLines[this.paymentLines.length - 1];
      let s = String(line.amount || '');
      if (s === '0') s = d;
      else s += d;
      line.amount = parseFloat(s);
    },
    clearInput() {
      this.paymentLines[this.paymentLines.length - 1].amount = 0;
    },
    backspace() {
      let line = this.paymentLines[this.paymentLines.length - 1];
      let s = String(line.amount || '');
      s = s.slice(0, -1) || '0';
      line.amount = parseFloat(s);
    },

     async Selected_PaymentMethod(value) {
      if (value == '1' || value == 1) {
        this.savedPaymentMethods = [];
        this.submit_showing_credit_card = true;
        this.selectedCard = null
        this.card_id = '';
        // Check if the customer has saved payment methods
        await axios.get(`/retrieve-customer?customerId=${this.selectedClientId}`)
            .then(response => {
                // If the customer has saved payment methods, display them
                this.savedPaymentMethods = response.data.data;
                this.card_id = response.data.customer_default_source;
                this.hasSavedPaymentMethod = true;
                this.useSavedPaymentMethod = true;
                this.is_new_credit_card = false;
                this.submit_showing_credit_card = false;
            })
            .catch(error => {
                // If the customer does not have saved payment methods, show the card element for them to enter their payment information
                this.hasSavedPaymentMethod = false;
                this.useSavedPaymentMethod = false;
                this.is_new_credit_card = true;
                this.card_id = '';

                setTimeout(() => {
                    this.loadStripe_payment();
                }, 1000);
                this.submit_showing_credit_card = false;
            });

         
        }else{
          this.hasSavedPaymentMethod = false;
          this.useSavedPaymentMethod = false;
          this.is_new_credit_card = false;
        }

    },

    show_saved_credit_card() {
      this.hasSavedPaymentMethod = true;
      this.useSavedPaymentMethod = true;
      this.is_new_credit_card = false;
      this.Selected_PaymentMethod(1);
    },

    show_new_credit_card() {
      this.selectedCard = null;
      this.card_id = '';
      this.useSavedPaymentMethod = false;
      this.hasSavedPaymentMethod = false;
      this.is_new_credit_card = true;

      setTimeout(() => {
        this.loadStripe_payment();
      }, 500);
    },

    selectCard(card) {
      this.selectedCard = card;
      this.card_id = card.card_id;
    },

    async loadStripe_payment() {
      this.stripe = await loadStripe(`${this.stripe_key}`);
      const elements = this.stripe.elements();
      this.cardElement = elements.create("card", {
        classes: {
          base:
            "bg-gray-100 rounded border border-gray-300 focus:border-indigo-500 text-base outline-none text-gray-700 p-3 leading-8 transition-colors duration-200 ease-in-out"
        }
      });
      this.cardElement.mount("#card-element");
    },

    SetLocal(locale) {
      this.$i18n.locale = locale;
      this.$store.dispatch("setLanguage", locale);
      Fire.$emit("ChangeLanguage");
      window.location.reload();
    },


    handleFullScreen() {
      Util.toggleFullScreen();
    },
    logoutUser() {
      this.logout();
    },
    // ==================== PAGINATION METHODS ====================
    Product_paginatePerPage() {
      this.paginate_products(this.product_perPage, 0);
    },
    onProductPageItemClick(item) {
      if (typeof item === 'number' && item >= 1 && item <= this.product_lastPage && item !== this.product_currentPage) {
        this.Product_onPageChanged(item);
      }
    },
    paginate_products(pageSize, pageNumber) {
      const itemsToParse = Array.isArray(this.products) ? this.products : [];
      this.paginated_Products = itemsToParse.slice(
        pageNumber * pageSize,
        (pageNumber + 1) * pageSize
      );
    },
    Product_onPageChanged(page) {
      this.product_currentPage = page;
      this.paginate_products(this.product_perPage, page - 1);
      this.getProducts(page);
    },
    BrandpaginatePerPage() {
      this.paginate_Brands(this.brand_perPage, 0);
    },
    paginate_Brands(pageSize, pageNumber) {
      let itemsToParse = this.brands;
      this.paginated_Brands = itemsToParse.slice(
        pageNumber * pageSize,
        (pageNumber + 1) * pageSize
      );
    },
    BrandonPageChanged(page) {
      this.paginate_Brands(this.brand_perPage, page - 1);
    },
    Category_paginatePerPage() {
      this.paginate_Category(this.category_perPage, 0);
    },
    paginate_Category(pageSize, pageNumber) {
      let itemsToParse = this.categories;
      this.paginated_Category = itemsToParse.slice(
        pageNumber * pageSize,
        (pageNumber + 1) * pageSize
      );
    },
    Category_onPageChanged(page) {
      this.paginate_Category(this.category_perPage, page - 1);
    },

    // ==================== SUBMIT & VALIDATION METHODS ====================
    Submit_Pos() {
      NProgress.start();
      NProgress.set(0.1);
      if (this.verifiedForm()) {
        Fire.$emit("pay_now");
      } else {
        NProgress.done();
      }
    },

    Submit_Draft() {
      NProgress.start();
      NProgress.set(0.1);
      if (this.verifiedForm()) {
        this.Create_Draft();
      } else {
        NProgress.done();
      }
    },

    verifiedForm() {
      if (this.selectedClientId == "" || this.selectedClientId === null) {
        this.makeToast(
          "danger",
          this.$t("Choose_Customer"),
          this.$t("Failed")
        );
        return false;
      } else if (
        this.sale.warehouse_id == "" ||
        this.sale.warehouse_id === null
      ) {
        this.makeToast(
          "danger",
          this.$t("Choose_Warehouse"),
          this.$t("Failed")
        );
        return false;
      } else if (this.details.length === 0) {
        this.makeToast(
          "danger",
          this.$t("PleaseAddProducts"),
          this.$t("Failed")
        );
        return false;
      }
      return true;
    },

    Create_Draft(){
      NProgress.start();
      NProgress.set(0.1);
      this.DraftProcessing = true;
      axios
        .post("pos/create_draft", {
          draft_sale_id: this.draft_sale_id || undefined,
          client_id: this.selectedClientId,
          warehouse_id: this.sale.warehouse_id,
          tax_rate: this.sale.tax_rate?this.sale.tax_rate:0,
          TaxNet: this.sale.TaxNet?this.sale.TaxNet:0,
          discount: this.sale.discount?this.sale.discount:0,
          shipping: this.sale.shipping?this.sale.shipping:0,
          notes: this.sale.notes,
          details: this.details,
          GrandTotal: this.GrandTotal,
        })
        .then(response => {
          if (response.data.success === true) {
            this.makeToast(
                "success",
                this.$t("Draft_Created_successfully"),
                this.$t("Success")
              );
            NProgress.done();
            this.DraftProcessing = false;
            this.Reset_Pos();
          }
        })
        .catch(error => {
          NProgress.done();
          this.DraftProcessing = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    // ==================== PAYMENT METHODS ====================
    Submit_Payment() {
      NProgress.start();
      NProgress.set(0.1);

      const total    = parseFloat(this.totalPaid);
      const due      = parseFloat(this.GrandTotal.toFixed(2));
      const multi    = this.paymentLines.length > 1;

      if (multi && total > due) {
        NProgress.done();
        this.makeToast(
          "warning",
          this.$t("TotalPaidExceedsGrandTotalForMultiPayment"),
          this.$t("Warning")
        );
        return;
      }

      this.CreatePOS();
    },

    CreatePOS() {
      NProgress.start();
      NProgress.set(0.1);
      if (this.paymentLines.length > 1 && this.totalPaid > this.GrandTotal) {
        this.makeToast(
          "warning",
          this.$t("TotalPaidExceedsGrandTotalForMultiPayment"),
          this.$t("Warning")
        );
        NProgress.done();
        return;
      }

      const anyNewCard = this.paymentLines.some(
        p => (p.payment_method_id === '1' || p.payment_method_id === 1) && this.is_new_credit_card
      );

      if (anyNewCard) {
        if (this.stripe_key !== '') {
          this.processPayment();
        } else {
          this.makeToast(
            'danger',
            this.$t('credit_card_account_not_available'),
            this.$t('Failed')
          );
          NProgress.done();
        }
      } else {
        this.paymentProcessing = true;
        axios
          .post("pos/create_pos", {
            client_id: this.selectedClientId,
            warehouse_id: this.sale.warehouse_id,
            tax_rate: this.sale.tax_rate?this.sale.tax_rate:0,
            TaxNet: this.sale.TaxNet?this.sale.TaxNet:0,
            discount: this.sale.discount?this.sale.discount:0,
            shipping: this.sale.shipping?this.sale.shipping:0,
            notes: this.sale.notes,
            details: this.details,
            GrandTotal: this.GrandTotal,
            payments: this.paymentLines,
            send_email: this.sendEmail,
            send_sms: this.sendSMS,
            account_id: this.selectedAccount,
            payment_note: this.globalPaymentNote || '',
            is_new_credit_card: this.is_new_credit_card,
            selectedCard: this.selectedCard,
            card_id: this.card_id,
            discount_from_points: this.discount_from_points,
            used_points: this.used_points,
            draft_sale_id: this.draft_sale_id || undefined,
          })
          .then(response => {
            if (response.data.success === true) {
              NProgress.done();
              this.paymentProcessing = false;
              const saleId = response.data.id;
              const draftId = this.draft_sale_id;
              const afterCleanup = () => {
                this.Invoice_POS(saleId);
                this.$bvModal.hide("Add_Payment");
                this.Reset_Pos();
              };
              if (draftId) {
                axios.delete("remove_draft_sale/" + draftId)
                  .then(() => { try { Fire.$emit("event_delete_draft_sale"); } catch(e) {} })
                  .catch(() => {})
                  .finally(() => { this.draft_sale_id = ''; afterCleanup(); });
              } else {
                afterCleanup();
              }
            }
          })
          .catch(error => {
            NProgress.done();
            this.paymentProcessing = false;
            this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          });
      }
    },

    async processPayment() {
      this.paymentProcessing = true;
      const { token, error } = await this.stripe.createToken(this.cardElement);
      if (error) {
        this.paymentProcessing = false;
        NProgress.done();
        this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
      } else {
        axios
          .post("pos/create_pos", {
            client_id: this.selectedClientId,
            warehouse_id: this.sale.warehouse_id,
            tax_rate: this.sale.tax_rate?this.sale.tax_rate:0,
            TaxNet: this.sale.TaxNet?this.sale.TaxNet:0,
            discount: this.sale.discount?this.sale.discount:0,
            shipping: this.sale.shipping?this.sale.shipping:0,
            details: this.details,
            GrandTotal: this.GrandTotal,
            notes: this.sale.notes,
            payments: this.paymentLines,
            send_email: this.sendEmail,
            send_sms: this.sendSMS,
            account_id: this.selectedAccount,
            payment_note: this.globalPaymentNote || '',
            token: token.id,
            is_new_credit_card: this.is_new_credit_card,
            selectedCard: this.selectedCard,
            card_id: this.card_id,
            discount_from_points: this.discount_from_points,
            used_points: this.used_points,
            draft_sale_id: this.draft_sale_id || undefined,
          })
          .then(response => {
            this.paymentProcessing = false;
            if (response.data.success === true) {
              NProgress.done();
              const saleId = response.data.id;
              const draftId = this.draft_sale_id;
              const afterCleanup = () => {
                this.Invoice_POS(saleId);
                this.$bvModal.hide("Add_Payment");
                this.Reset_Pos();
              };
              if (draftId) {
                axios.delete("remove_draft_sale/" + draftId)
                  .then(() => { try { Fire.$emit("event_delete_draft_sale"); } catch(e) {} })
                  .catch(() => {})
                  .finally(() => { this.draft_sale_id = ''; afterCleanup(); });
              } else {
                afterCleanup();
              }
            }
          })
          .catch(error => {
            this.paymentProcessing = false;
            NProgress.done();
            this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          });
      }
    },

    // ==================== UTILITY & CALCULATION METHODS ====================
    formatNumber(number, dec) {
      const decimals = Number.isInteger(dec) ? dec : 0;
      const n = Number(number);
      const safe = Number.isFinite(n) ? n : 0;
      try {
        return safe.toLocaleString('en-US', {
          minimumFractionDigits: decimals,
          maximumFractionDigits: decimals,
        });
      } catch (e) {
        // Fallback for environments without Intl
        const fixed = safe.toFixed(decimals);
        const [intPart, fracPart] = fixed.split('.');
        const withCommas = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return fracPart ? `${withCommas}.${fracPart}` : withCommas;
      }
    },

    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },

    CalculTotal() {
      this.total = 0;
      for (var i = 0; i < this.details.length; i++) {
        var tax = this.details[i].taxe * this.details[i].quantity;
        this.details[i].subtotal = parseFloat(
          this.details[i].quantity * this.details[i].Net_price + tax
        );
        this.total = parseFloat(this.total + this.details[i].subtotal);
      }
      const total_without_discount = parseFloat(
        this.total - this.sale.discount
      );
      this.sale.TaxNet = parseFloat(
        (total_without_discount * this.sale.tax_rate) / 100
      );
      this.GrandTotal = parseFloat(
        total_without_discount + this.sale.TaxNet + this.sale.shipping
      );
    var grand_total =  this.GrandTotal.toFixed(2);
    this.GrandTotal = parseFloat(grand_total);
    try { this._cd_queue_broadcast && this._cd_queue_broadcast(); } catch(e) {}
    },

    keyup_OrderTax() {
      if (isNaN(this.sale.tax_rate)) {
        this.sale.tax_rate = 0;
      } else if(this.sale.tax_rate == ''){
         this.sale.tax_rate = 0;
        this.CalculTotal();
      }else {
        this.CalculTotal();
      }
    },

    keyup_Discount() {
      if (isNaN(this.sale.discount)) {
        this.sale.discount = 0;
      } else if(this.sale.discount == ''){
         this.sale.discount = 0;
        this.CalculTotal();
      }else {
        this.CalculTotal();
      }
    },

    keyup_Shipping() {
      if (isNaN(this.sale.shipping)) {
        this.sale.shipping = 0;
      } else if(this.sale.shipping == ''){
         this.sale.shipping = 0;
        this.CalculTotal();
      }else {
        this.CalculTotal();
      }
    },

    //---------------------------------Get Product Details ------------------------\\
    Get_Product_Details(product_id, variant_id) {
       return axios.get("/show_product_data/" + product_id +"/"+ variant_id + "/" + this.sale.warehouse_id).then(response => {
        this.product.discount           = response.data.discount;
        this.product.DiscountNet        = response.data.DiscountNet;
        this.product.discount_Method    = response.data.discount_method;
        this.product.product_id         = response.data.id;
        this.product.product_type       = response.data.product_type;
        this.product.name               = response.data.name;
        this.product.Net_price          = response.data.Net_price;
        this.product.Total_price        = response.data.Total_price;
        this.product.Unit_price         = response.data.Unit_price;
        this.product.Unit_price_wholesale = response.data.Unit_price_wholesale;
        this.product.wholesale_Net_price   = response.data.wholesale_Net_price;
        this.product.min_price             = response.data.min_price || 0;
        this.product.retail_unit_price     = response.data.Unit_price;
        this.product.wholesale_unit_price  = response.data.Unit_price_wholesale;
        this.product.price_type            = 'retail';
        this.product.taxe               = response.data.tax_price;
        this.product.tax_method         = response.data.tax_method;
        this.product.tax_percent        = response.data.tax_percent;
        this.product.unitSale           = response.data.unitSale;
        this.product.product_variant_id = variant_id;
        this.product.code               = response.data.code;
        this.product.fix_price          = response.data.fix_price;
        this.product.sale_unit_id       = response.data.sale_unit_id;
        this.product.is_imei            = response.data.is_imei;
        this.product.imei_number        = '';

         // Set current stock quantity from warehouse data
        this.product.current = response.data.qte_sale || 0;
        this.product.fix_stock = response.data.qte || 0;
        
        // Ensure a valid default quantity when adding directly from the grid
        if (this.product.product_type === 'is_service') {
          this.product.quantity = 1;
        } else if (this.product.quantity === undefined || this.product.quantity === null || this.product.quantity <= 0) {
          this.product.quantity = this.product.current < 1 ? this.product.current : 1;
        }

        this.add_product(response.data.code);
        this.CalculTotal();
        // Complete the animation of theprogress bar.
        NProgress.done();
      });
    },

    add_product(code) {
      this.audio.play();
      // 1) If product already exists in the list (ignore price_type), merge and just increase quantity
      const hasProductIds = this.product.product_id !== undefined && this.product.product_id !== null;
      const targetVariantId = (this.product.product_variant_id === undefined || this.product.product_variant_id === null)
        ? null
        : this.product.product_variant_id;
      const existingIndex = this.details.findIndex(d => {
        const dVariant = (d.product_variant_id === undefined || d.product_variant_id === null) ? null : d.product_variant_id;
        const rowHasId = d.product_id !== undefined && d.product_id !== null;
        // Prefer strict match by ids when both sides have ids
        if (hasProductIds && rowHasId) {
          return (d.product_id === this.product.product_id) && (dVariant === targetVariantId) && (d.sale_unit_id === this.product.sale_unit_id);
        }
        // Fallback to matching by code + unit when ids are not available
        return (d.code === this.product.code) && (d.sale_unit_id === this.product.sale_unit_id);
      });

      if (existingIndex !== -1) {
        const row = this.details[existingIndex];
        const addQty = (typeof this.product.quantity === 'number' && this.product.quantity > 0) ? this.product.quantity : 1;
        if (row.product_type !== 'is_service') {
          const desiredQty = row.quantity + addQty;
          if (desiredQty > row.current) {
            this.makeToast("warning", this.$t("LowStock"), this.$t("Warning"));
            row.quantity = row.current;
          } else {
            row.quantity = desiredQty;
          }
        } else {
          row.quantity = row.quantity + addQty;
        }
        this.CalculTotal();
        this.$forceUpdate();
        setTimeout(() => { this.load_product = true; }, 300);
        if (row.is_imei) {
          this.Modal_Updat_Detail(row);
        }
        return;
      }

      // 2) No existing row → create a new one
      if (this.details.length > 0) {
        this.order_detail_id();
      } else if (this.details.length === 0) {
        this.product.detail_id = 1;
      }
      // initialize price type fields before pushing
      if (!this.product.price_type) {
        this.product.price_type = 'retail';
      }
      if (!this.product.retail_unit_price) {
        this.product.retail_unit_price = this.product.Unit_price;
      }
      if (!this.product.wholesale_unit_price) {
        this.product.wholesale_unit_price = this.product.Unit_price_wholesale;
      }

      // push a cloned object to avoid accidental reference sharing
      const newItem = JSON.parse(JSON.stringify(this.product));
      if (!newItem.price_type) newItem.price_type = 'retail';
      // ensure reactivity for newly-added prop on some browsers
      this.$set(newItem, 'price_type', newItem.price_type || 'retail');
      // Apply min_price on add: ensure Net_price >= min_price by adjusting Unit_price if required
      try {
        const min = Number(newItem.min_price || 0);
        const taxMethod = String(newItem.tax_method || '1');
        const discountMethod = String(newItem.discount_Method || '2');
        const discountVal = Number(newItem.discount || 0);
        const taxRate = Number(newItem.tax_percent || 0) / 100;
        const currentNet = Number(newItem.Net_price || 0);
        if (min > 0 && currentNet < min) {
          let unitPriceCandidate = Number(newItem.Unit_price || 0);
          if (taxMethod === '1') {
            if (discountMethod === '1') {
              const denom = 1 - (discountVal / 100);
              unitPriceCandidate = denom > 0 ? (min / denom) : min;
            } else {
              unitPriceCandidate = min + discountVal;
            }
            const discountNet = (discountMethod === '1') ? (unitPriceCandidate * (discountVal / 100)) : discountVal;
            const net = unitPriceCandidate - discountNet;
            const tax = (unitPriceCandidate - discountNet) * taxRate;
            newItem.Unit_price = parseFloat(unitPriceCandidate.toFixed(2));
            newItem.DiscountNet = parseFloat(discountNet.toFixed(2));
            newItem.Net_price = parseFloat(net.toFixed(2));
            newItem.taxe = parseFloat(tax.toFixed(2));
            newItem.Total_price = parseFloat((net + tax).toFixed(2));
          } else {
            if (discountMethod === '1') {
              const denom = (1 - (discountVal / 100)) * (1 - taxRate);
              unitPriceCandidate = denom > 0 ? (min / denom) : min;
            } else {
              const denom = (1 - taxRate);
              unitPriceCandidate = (denom > 0 ? (min / denom) : min) + discountVal;
            }
            const discountNet = (discountMethod === '1') ? (unitPriceCandidate * (discountVal / 100)) : discountVal;
            const taxBase = unitPriceCandidate - discountNet;
            const tax = taxBase * taxRate;
            const net = taxBase - tax;
            newItem.Unit_price = parseFloat(unitPriceCandidate.toFixed(2));
            newItem.DiscountNet = parseFloat(discountNet.toFixed(2));
            newItem.taxe = parseFloat(tax.toFixed(2));
            newItem.Net_price = parseFloat(net.toFixed(2));
            newItem.Total_price = parseFloat((net + tax).toFixed(2));
          }
          if (newItem.price_type === 'wholesale') {
            newItem.wholesale_unit_price = newItem.Unit_price;
          } else {
            newItem.retail_unit_price = newItem.Unit_price;
          }
        }
      } catch(e) {}
      this.details.push(newItem);
      setTimeout(() => {
        this.load_product = true;
      }, 300);
      if(newItem.is_imei){
        this.Modal_Updat_Detail(newItem);
      }
    },

    order_detail_id() {
      let id = 0;
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id > id) {
          id = this.details[i].detail_id;
        }
      }
      this.product.detail_id = id + 1;
    },

    increment_qty_scanner(code) {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].code === code) {
          if (this.details[i].product_type !== 'is_service' && this.details[i].quantity + 1 > this.details[i].current) {
            this.makeToast("warning", this.$t("LowStock"), this.$t("Warning"));
          } else {
            this.details[i].quantity++;
          }
        }
      }
      this.CalculTotal();
      this.$forceUpdate();

      NProgress.done();
      setTimeout(() => {
        this.load_product = true;
      }, 300);
    },

    increment(id) {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id == id) {
          if (this.details[i].product_type !== 'is_service' && this.details[i].quantity + 1 > this.details[i].current) {
            this.makeToast("warning", this.$t("LowStock"), this.$t("Warning"));
          } else {
            this.details[i].quantity++;
          }
        }
      }
      this.CalculTotal();
      this.$forceUpdate();
    },

    handleProductClick(product) {
      if (!product || (product.product_type !== 'is_service' && product.qte_sale <= 0)) return;
      // Use composite key for variants to avoid overlay conflicting across variants
      const key = product.product_variant_id ? (product.id + '-' + product.product_variant_id) : product.id;
      this.uiLoadingProductId = key;
      this.Get_Product_Details(product.id, product.product_variant_id)
        .catch(() => {})
        .finally(() => {
          // Clear only if still the same key (guard against fast double clicks)
          if (this.uiLoadingProductId === key) this.uiLoadingProductId = null;
        });
    },

    decrement(detail, id) {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id == id) {
          if (detail.quantity - 1 < 1) {
            this.makeToast("warning", this.$t("MinimumQuantity"), this.$t("Warning"));
          } else {
            this.details[i].quantity--;
          }
        }
      }
      this.CalculTotal();
      this.$forceUpdate();
    },

    Verified_Qty(detail, id) {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id === id) {
          if (isNaN(detail.quantity) || detail.quantity === null || detail.quantity === '') {
            this.details[i].quantity = 1;
          } else if (detail.quantity < 1) {
            this.makeToast("warning", this.$t("MinimumQuantity"), this.$t("Warning"));
            this.details[i].quantity = 1;
          } else if (this.details[i].product_type !== 'is_service' && detail.quantity > detail.current) {
            this.makeToast("warning", this.$t("LowStock"), this.$t("Warning"));
            this.details[i].quantity = detail.current;
          } else {
            this.details[i].quantity = detail.quantity;
          }
        }
      }
      this.$forceUpdate();
      this.CalculTotal();
    },

    delete_Product_Detail(id) {
      for (var i = 0; i < this.details.length; i++) {
        if (id === this.details[i].detail_id) {
          this.details.splice(i, 1);
          this.CalculTotal();
          try { this._cd_queue_broadcast && this._cd_queue_broadcast(); } catch(e) {}
        }
      }
    },

    // ---------- Edit Detail Logic (ported from old_pos) ----------
    //------ Show Modal Update Detail Product
    Modal_Updat_Detail(detail) {
      this.detailLoading = true;
      this.detail = {};
      this.detail.name = detail.name;
      this.$bvModal.show("form_Update_Detail");
      this.get_units(detail.product_id)
        .catch(() => {})
        .finally(() => {
          this.detail.detail_id = detail.detail_id;
          this.detail.sale_unit_id = detail.sale_unit_id;
          this.detail.product_type = detail.product_type;
          this.detail.Unit_price = detail.Unit_price;
          this.detail.price_type = detail.price_type || 'retail';
          this.detail.retail_unit_price = detail.retail_unit_price !== undefined ? detail.retail_unit_price : detail.Unit_price;
          this.detail.wholesale_unit_price = detail.wholesale_unit_price !== undefined ? detail.wholesale_unit_price : detail.Unit_price_wholesale;
          this.detail.min_price = detail.min_price !== undefined ? detail.min_price : 0;
          this.detail.fix_price = detail.fix_price;
          this.detail.fix_stock = detail.fix_stock;
          this.detail.current = detail.current;
          this.detail.tax_method = detail.tax_method;
          this.detail.discount_Method = detail.discount_Method;
          this.detail.discount = detail.discount;
          this.detail.quantity = detail.quantity;
          this.detail.tax_percent = detail.tax_percent;
          this.detail.is_imei = detail.is_imei;
          this.detail.imei_number = detail.imei_number;
          this.detailLoading = false;
        });
        console.log(detail);

    },


    //------ Submit Update Detail Product
    submit_Update_Detail() {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id === this.detail.detail_id) {
          // 1) Compute proposed pricing WITHOUT mutating the row yet
          let proposedUnitPrice = this.detail.Unit_price;
          const rawMinCandidate = (this.details[i].min_price !== undefined && this.details[i].min_price !== null)
            ? this.details[i].min_price
            : (this.detail.min_price || 0);
          const minPriceRow = parseFloat(String(rawMinCandidate).toString().replace(/,/g, '')) || 0;
          const unitPriceNum = parseFloat(String(proposedUnitPrice).toString().replace(/,/g, '')) || 0;
          // 1.a) Block if unit price is not strictly greater than min price
          if (minPriceRow > 0 && unitPriceNum <= minPriceRow) {
            this.makeToast('warning', this.$t('Price_below_min_not_allowed'), this.$t('Warning'));
            return;
          }

          let proposedDiscountNet = (this.detail.discount_Method == "2")
            ? this.detail.discount
            : parseFloat((proposedUnitPrice * this.detail.discount) / 100);
          let proposedNet, proposedTax;
          if (this.detail.tax_method == "1") {
            proposedNet = parseFloat(proposedUnitPrice - proposedDiscountNet);
            proposedTax = parseFloat((this.detail.tax_percent * (proposedUnitPrice - proposedDiscountNet)) / 100);
          } else {
            proposedTax = parseFloat((proposedUnitPrice - proposedDiscountNet) * (this.detail.tax_percent / 100));
            proposedNet = parseFloat(proposedUnitPrice - proposedTax - proposedDiscountNet);
          }

          // 2) Enforce min price by net as a fallback: if invalid, show toast and ABORT update (keep modal open)
          if (minPriceRow > 0 && proposedNet < minPriceRow) {
            this.makeToast('warning', this.$t('Price_below_min_not_allowed'), this.$t('Warning'));
            return;
          }

          // 3) Apply unit conversion now that price is valid (skip stock logic for services)
          if (this.details[i].product_type !== 'is_service') {
            for (var k = 0; k < this.units.length; k++) {
              if (this.units[k].id == this.detail.sale_unit_id) {
                if (this.units[k].operator == "/") {
                  this.details[i].current = this.detail.fix_stock * this.units[k].operator_value;
                  this.details[i].unitSale = this.units[k].ShortName;
                } else {
                  this.details[i].current = this.detail.fix_stock / this.units[k].operator_value;
                  this.details[i].unitSale = this.units[k].ShortName;
                }
              }
            }
          }

          // 4) Persist values to the row
          this.details[i].Unit_price = proposedUnitPrice;
          // update baseline for the NEWLY selected price type
          if (this.detail.price_type === 'wholesale') {
            this.details[i].wholesale_unit_price = proposedUnitPrice;
          } else {
            this.details[i].retail_unit_price = proposedUnitPrice;
          }
          this.details[i].price_type = this.detail.price_type;
          this.details[i].tax_percent = this.detail.tax_percent;
          this.details[i].tax_method = this.detail.tax_method;
          this.details[i].discount_Method = this.detail.discount_Method;
          this.details[i].discount = this.detail.discount;
          this.details[i].sale_unit_id = this.detail.sale_unit_id;
          this.details[i].imei_number = this.detail.imei_number;
          this.details[i].product_type = this.detail.product_type;

          // 5) Apply computed values
          this.details[i].DiscountNet = proposedDiscountNet;
          this.details[i].taxe = proposedTax;
          this.details[i].Net_price = proposedNet;
          this.details[i].Total_price = parseFloat(proposedNet + proposedTax);
          this.$forceUpdate();
        }
      }
      this.CalculTotal();
      setTimeout(() => {
        this.$bvModal.hide("form_Update_Detail");
      }, 1000);
    },

    // Toggle between retail and wholesale price baselines and recompute amounts
    onChangePriceType(detail){
      const isWholesale = detail.price_type === 'wholesale';
      const wholesaleBase = detail.wholesale_unit_price;
      const retailBase = detail.retail_unit_price;

      // 1) Apply selected baseline
      if (isWholesale) {
        detail.Unit_price = (wholesaleBase !== undefined && wholesaleBase !== null && wholesaleBase !== '') ? wholesaleBase : detail.Unit_price;
      } else {
        detail.Unit_price = (retailBase !== undefined && retailBase !== null && retailBase !== '') ? retailBase : detail.Unit_price;
      }

      // 2) Recompute derived values
      if (detail.discount_Method == "2") {
        detail.DiscountNet = detail.discount;
      } else {
        detail.DiscountNet = parseFloat((detail.Unit_price * detail.discount) / 100);
      }
      if (detail.tax_method == "1") {
        detail.Net_price = parseFloat(detail.Unit_price - detail.DiscountNet);
        detail.taxe = parseFloat((detail.tax_percent * (detail.Unit_price - detail.DiscountNet)) / 100);
        detail.Total_price = parseFloat(detail.Net_price + detail.taxe);
      } else {
        detail.taxe = parseFloat((detail.Unit_price - detail.DiscountNet) * (detail.tax_percent / 100));
        detail.Net_price = parseFloat(detail.Unit_price - detail.taxe - detail.DiscountNet);
        detail.Total_price = parseFloat(detail.Net_price + detail.taxe);
      }

      // 3) Enforce min price
      if ((detail.min_price || 0) > 0 && detail.Net_price < detail.min_price) {
        this.makeToast('warning', this.$t('Price_below_min_not_allowed'), this.$t('Warning'));
        // revert to retail
        detail.price_type = 'retail';
        detail.Unit_price = (retailBase !== undefined && retailBase !== null && retailBase !== '') ? retailBase : detail.Unit_price;
        // recompute again
        if (detail.discount_Method == "2") {
          detail.DiscountNet = detail.discount;
        } else {
          detail.DiscountNet = parseFloat((detail.Unit_price * detail.discount) / 100);
        }
        if (detail.tax_method == "1") {
          detail.Net_price = parseFloat(detail.Unit_price - detail.DiscountNet);
          detail.taxe = parseFloat((detail.tax_percent * (detail.Unit_price - detail.DiscountNet)) / 100);
          detail.Total_price = parseFloat(detail.Net_price + detail.taxe);
        } else {
          detail.taxe = parseFloat((detail.Unit_price - detail.DiscountNet) * (detail.tax_percent / 100));
          detail.Net_price = parseFloat(detail.Unit_price - detail.taxe - detail.DiscountNet);
          detail.Total_price = parseFloat(detail.Net_price + detail.taxe);
        }
      }

      // 4) Update baseline for the (final) selected type
      if (detail.price_type === 'wholesale') {
        detail.wholesale_unit_price = detail.Unit_price;
      } else {
        detail.retail_unit_price = detail.Unit_price;
      }

      this.$forceUpdate();
      this.CalculTotal();
    },

    // Ensure each rendered item always has a default price_type for binding
    ensurePriceType(detail){
      if (!detail) return;
      if (!detail.price_type) this.$set(detail, 'price_type', 'retail');
    },

    // ==================== RESET METHOD ====================
    async Reset_Pos() {
      this.details = [];
      this.product = {};
      this.draft_sale_id = '';
      this.paymentLines = [
        {
          amount: 0,
          payment_method_id: '2',
        }
      ];

      this.selectedAccount = null;
      this.globalPaymentNote = '';

      this.savedPaymentMethods= [],
      this.hasSavedPaymentMethod= false,
      this.useSavedPaymentMethod= false,
      this.selectedCard=null,
      this.card_id='',
      this.is_new_credit_card= false,
      this.submit_showing_credit_card= false,

      this.sale.tax_rate = 0;
      this.sale.TaxNet = 0;
      this.sale.shipping = 0;
      this.sale.discount = 0;
      this.sale.notes = '';
      this.GrandTotal = 0;
      this.total = 0;
      this.category_id = "";
      this.brand_id = "";
      
      this.selectedClientPoints = 0;
      this.points_to_convert = 0;
      this.used_points = 0;
      this.discount_from_points = 0;
      this.clientIsEligible = false;
      this.pointsConverted = false;
      try { this._cd_emit && this._cd_emit({ currency: (this.currentUser && this.currentUser.currency) || '', details: [], discount: 0, TaxNet: 0, GrandTotal: 0 }, true); } catch(e) {}
      
      const client = this.clients.find(client => client.id === 1);
      if (client) {
        this.client_name = client.name;
        this.selectedClientId = 1;

        try {
          const response = await axios.get(`/get_points_client/${this.selectedClientId}`);
          const data = response.data;

          if (data.is_royalty_eligible) {
            this.selectedClientPoints = data.points;
            this.clientIsEligible = true;
          } else {
            this.selectedClientPoints = 0;
            this.clientIsEligible = false;
          }
        } catch (error) {
        }
      }

      this.getProducts(1);
    },

    // ==================== SEARCH METHODS ====================
    search(){
      if (this.timer) {
            clearTimeout(this.timer);
            this.timer = null;
      }
      if (this.search_input.length < 2) {
        return this.product_filter= [];
      }
      if (this.sale.warehouse_id != "" &&  this.sale.warehouse_id != null) {
        this.timer = setTimeout(() => {

          let barcode = this.search_input.trim();
          let weight = null;
          if (barcode.length === 13 && !isNaN(barcode)) {
            let product = this.products_pos.find(prod => prod.code === barcode);
            if (product) {
              this.Check_Product_Exist(product, product.id, weight);
              return;
            }else{

              let productCode = barcode.substring(0, 7);
              let weight = parseFloat(barcode.substring(7, 12)) / 1000;
              let product = this.products_pos.find(prod => prod.code === productCode);
              if (product) {
                product.quantity = weight;
                this.Check_Product_Exist(product, product.id, weight);
                return;
              }
            }

            this.makeToast("danger", "Invalid product code scanned", this.$t("Error"));
            this.search_input= '';
            this.product_filter = [];
          }
          
          const product_filter = this.products_pos.filter(product => product.code === this.search_input || product.barcode.includes(this.search_input));
              if(product_filter.length === 1){
                this.Check_Product_Exist(product_filter[0], product_filter[0].id, weight = null);
              }else {
                this.product_filter=  this.products_pos.filter(product => {
                  return (
                    product.name.toLowerCase().includes(this.search_input.toLowerCase()) ||
                    product.code.toLowerCase().includes(this.search_input.toLowerCase()) ||
                    product.barcode.toLowerCase().includes(this.search_input.toLowerCase())
                    );
                });
            }
        }, 800);
      } else {
        this.makeToast(
          "warning",
          this.$t("SelectWarehouse"),
          this.$t("Warning")
        );
      }
    },

    Check_Product_Exist(product, id, weight = null) {
      if(this.load_product){
        this.load_product = false;
        NProgress.start();
        NProgress.set(0.1);
        this.product = {};

        if( product.product_type == 'is_service'){
          this.product.quantity = 1;

        }else{

          this.product.current = product.qte_sale;
          this.product.fix_stock = product.qte;

          if (weight !== null) {
            this.product.quantity = weight;
          } else {
            this.product.quantity = product.qte_sale < 1 ? product.qte_sale : 1;
          }

        }
        this.Get_Product_Details(id, product.product_variant_id);

        NProgress.done();
        this.search_input= '';
        this.product_filter = [];

      }else{
          this.makeToast(
            "warning",
            this.$t("Please_wait_until_the_product_is_loaded"),
            this.$t("Warning")
          );
      }
    },

    Products_by_Category(id) {
      this.category_id = id;
      this.getProducts(1);
    },

    Products_by_Brands(id) {
      this.brand_id = id;
      this.getProducts(1);
    },

    getAllCategory() {
      this.category_id = "";
      this.search_category = '';
      this.getProducts(1);
    },

    GetAllBrands() {
      this.brand_id = "";
      this.search_brand = '';
      this.getProducts(1);
    },

    // ==================== DRAFT SALES METHODS (from old_pos) ====================
    Show_Draft_Sales() {
      this.get_Draft_Sales(1);
      setTimeout(() => {
        this.$bvModal.show("show_draft_sales");
      }, 1000);
    },

    get_Draft_Sales(page) {
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "get_draft_sales?page=" +
            page +
            "&limit=" +
            this.limit
        )
        .then(response => {
          this.draft_sales = response.data.draft_sales;
          this.totalRows_draft_sales = response.data.totalRows;
          NProgress.done();
        })
        .catch(() => {
          NProgress.done();
        });
    },

    // Load a draft sale into the current POS view without navigating
    loadDraftSale(id) {
      this.openingDraftId = id;
      // If this draft is already loaded, do nothing (do not update on open)
      if (this.draft_sale_id && String(this.draft_sale_id) === String(id)) {
        this.openingDraftId = null;
        return;
      }

      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(`pos/data_draft_convert_sale/${id}`)
        .then(response => {
          this.draft_sale_id = id;
          const data = response.data || {};

          // Basic references (keep layout/logic unchanged; just inject data)
          if (Array.isArray(data.clients)) this.clients = data.clients;
          if (Array.isArray(data.accounts)) this.accounts = data.accounts;
          if (Array.isArray(data.warehouses)) this.warehouses = data.warehouses;
          if (Array.isArray(data.categories)) this.categories = data.categories;
          if (Array.isArray(data.brands)) this.brands = data.brands;
          if (Array.isArray(data.payment_methods)) this.payment_methods = data.payment_methods;

          // Customer & loyalty
          this.selectedClientId = data.client_id || (data.sale && data.sale.client_id) || this.selectedClientId;
          this.client_name = data.client_name || this.client_name;
          this.clientIsEligible = data.default_client_eligible === true || data.default_client_eligible === 1;
          this.selectedClientPoints = this.clientIsEligible ? parseFloat(data.default_client_points || 0) : 0;
          if (typeof data.point_to_amount_rate !== 'undefined') {
            this.point_to_amount_rate = data.point_to_amount_rate;
          }

          // Sale-level fields
          const saleData = data.sale || {};
          this.sale.warehouse_id = (data.warehouse_id !== undefined && data.warehouse_id !== null)
            ? data.warehouse_id
            : (saleData.warehouse_id || this.sale.warehouse_id);
          this.sale.tax_rate = saleData.tax_rate || 0;
          this.sale.TaxNet = saleData.TaxNet || 0;
          this.sale.discount = saleData.discount || 0;
          this.sale.shipping = saleData.shipping || 0;
          this.sale.notes = saleData.notes || '';

          // Map draft details to POS details shape (ensuring fields required by POS)
          const incoming = Array.isArray(data.details) ? data.details : [];
          const mapped = incoming.map((it, idx) => {
            const d = { ...it };
            if (d.detail_id === undefined || d.detail_id === null) d.detail_id = idx + 1;
            if (!d.price_type) d.price_type = 'retail';
            if (d.retail_unit_price === undefined) d.retail_unit_price = d.Unit_price;
            if (d.wholesale_unit_price === undefined) d.wholesale_unit_price = (d.Unit_price_wholesale !== undefined ? d.Unit_price_wholesale : d.Unit_price);
            if (d.min_price === undefined) d.min_price = 0;
            if (d.current === undefined || d.current === null) d.current = (d.fix_stock !== undefined ? d.fix_stock : d.quantity);
            if (d.fix_stock === undefined || d.fix_stock === null) d.fix_stock = d.current;

            const unitPrice = Number(d.Unit_price || 0);
            const discountVal = Number(d.discount || 0);
            const discountMethod = String(d.discount_Method || '2'); // 1: %, 2: fixed
            const taxPercent = Number(d.tax_percent || 0);
            const taxMethod = String(d.tax_method || '1'); // 1: Exclusive, 2: Inclusive

            if (typeof d.DiscountNet === 'undefined') {
              d.DiscountNet = discountMethod === '2' ? discountVal : (unitPrice * (discountVal / 100));
            }

            if (taxMethod === '1') {
              d.Net_price = parseFloat((unitPrice - d.DiscountNet).toFixed(2));
              d.taxe = parseFloat((((unitPrice - d.DiscountNet) * taxPercent) / 100).toFixed(2));
              d.Total_price = parseFloat((d.Net_price + d.taxe).toFixed(2));
            } else {
              d.taxe = parseFloat(((unitPrice - d.DiscountNet) * (taxPercent / 100)).toFixed(2));
              d.Net_price = parseFloat((unitPrice - d.taxe - d.DiscountNet).toFixed(2));
              d.Total_price = parseFloat((d.Net_price + d.taxe).toFixed(2));
            }
            return d;
          });
          this.details = mapped;

          // Totals
          this.GrandTotal = Number(data.GrandTotal || 0);
          this.CalculTotal();

          // Refresh product lists for the chosen warehouse
          if (this.sale.warehouse_id) {
            this.Get_Products_By_Warehouse(this.sale.warehouse_id);
            this.getProducts(1);
          }

          // Close draft list modal
          try { this.$bvModal.hide('show_draft_sales'); } catch (e) {}

          NProgress.done();
        })
        .catch(() => {
          NProgress.done();
        })
        .finally(() => {
          this.openingDraftId = null;
        });
    },

    Remove_Draft_Sale(id) {
      this.$swal({
        title: this.$t("Delete_Title"),
        text: this.$t("Delete_Text"),
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: this.$t("Delete_cancelButtonText"),
        confirmButtonText: this.$t("Delete_confirmButtonText")
      }).then(result => {
        if (result.value) {
          NProgress.start();
          NProgress.set(0.1);
          axios
            .delete("remove_draft_sale/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete_Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );
              Fire.$emit("event_delete_draft_sale");
            })
            .catch(() => {
              setTimeout(() => NProgress.done(), 500);
              this.$swal(
                this.$t("Delete_Failed"),
                this.$t("Delete_Therewassomethingwronge"),
                "warning"
              );
            });
        }
      });
    },

    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.get_Draft_Sales(currentPage);
      }
    },
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.get_Draft_Sales(1);
      }
    },

    getProducts(page = 1) {
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "pos/get_products_pos?page=" +
            page +
            "&category_id=" +
            this.category_id +
            "&brand_id=" +
            this.brand_id +
            "&warehouse_id=" +
            this.sale.warehouse_id +
            "&stock=" + 1 +
            "&product_service=" + 1
            + "&product_combo=" + 1
        )
        .then(response => {
          this.products = response.data.products;
          this.product_totalRows = response.data.totalRows;
          this.Product_paginatePerPage();
          this.productsReady = true;
          NProgress.done();
        })
        .catch(response => {
          this.productsReady = true; // avoid blocking UI forever on error
          NProgress.done();
        });
    },

    // ==================== WAREHOUSE & CLIENT SELECTION ====================
    Selected_Warehouse(value) {
      this.search_input= '';
      this.product_filter = [];
      this.Get_Products_By_Warehouse(value);
      this.getProducts(1);
    },

    Get_Products_By_Warehouse(id) {
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get("get_Products_by_warehouse/" + id + "?stock=" + 1 + "&is_sale=" + 1 + "&product_service=" + 1 + "&product_combo=" + 1)
         .then(response => {
            this.products_pos = response.data;
            // If main product list is already loaded, also mark ready
            if (Array.isArray(this.products) && this.products.length >= 0) {
              this.productsReady = true;
            }
             NProgress.done();
            })
          .catch(error => {
            this.productsReady = true;
          });
    },

    // ==================== DETAIL EDIT METHODS (from old_pos) ====================
    get_units(value) {
      return axios
        .get("get_units?id=" + value)
        .then(({ data }) => (this.units = data));
    },
    Modal_Updat_Detail(detail) {
      this.detailLoading = true;
      this.detail = {};
      this.detail.name = detail.name;
      this.$bvModal.show("form_Update_Detail");
      this.get_units(detail.product_id)
        .catch(() => {})
        .finally(() => {
          this.detail.detail_id = detail.detail_id;
          this.detail.sale_unit_id = detail.sale_unit_id;
          this.detail.product_type = detail.product_type;
          this.detail.Unit_price = detail.Unit_price;
          this.detail.price_type = detail.price_type || 'retail';
          this.detail.retail_unit_price = detail.retail_unit_price !== undefined ? detail.retail_unit_price : detail.Unit_price;
          this.detail.wholesale_unit_price = detail.wholesale_unit_price !== undefined ? detail.wholesale_unit_price : detail.Unit_price_wholesale;
          this.detail.min_price = detail.min_price !== undefined ? detail.min_price : 0;
          this.detail.fix_price = detail.fix_price;
          this.detail.fix_stock = detail.fix_stock;
          this.detail.current = detail.current;
          this.detail.tax_method = detail.tax_method;
          this.detail.discount_Method = detail.discount_Method;
          this.detail.discount = detail.discount;
          this.detail.quantity = detail.quantity;
          this.detail.tax_percent = detail.tax_percent;
          this.detail.is_imei = detail.is_imei;
          this.detail.imei_number = detail.imei_number;
          this.detailLoading = false;
        });
    },
    submit_Update_Detail() {
      this.$refs.Update_Detail && this.$refs.Update_Detail.validate().then(success => {
        if (!success) {
          return;
        } else {
          this.Update_Detail();
        }
      }).catch(() => {
        // Fallback: proceed without form validation if ref is absent in new design
        this.Update_Detail();
      });
    },
    Update_Detail() {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id === this.detail.detail_id) {
          // Min price validation (unit and net)
          const rawMinCandidate = (this.details[i].min_price !== undefined && this.details[i].min_price !== null)
            ? this.details[i].min_price
            : (this.detail.min_price || 0);
          const minPriceRow = parseFloat(String(rawMinCandidate).toString().replace(/,/g, '')) || 0;
          const unitPriceNum = parseFloat(String(this.detail.Unit_price).toString().replace(/,/g, '')) || 0;
          if (minPriceRow > 0 && unitPriceNum < minPriceRow) {
            this.makeToast('warning', this.$t('Price_below_min_not_allowed'), this.$t('Warning'));
            return;
          }
          // compute proposed net to check against min price
          let proposedDiscountNet = (this.detail.discount_Method == "2")
            ? this.detail.discount
            : parseFloat((unitPriceNum * this.detail.discount) / 100);
          let proposedNet, proposedTax;
          if (this.detail.tax_method == "1") {
            proposedNet = parseFloat(unitPriceNum - proposedDiscountNet);
            proposedTax = parseFloat((this.detail.tax_percent * (unitPriceNum - proposedDiscountNet)) / 100);
          } else {
            proposedTax = parseFloat((unitPriceNum - proposedDiscountNet) * (this.detail.tax_percent / 100));
            proposedNet = parseFloat(unitPriceNum - proposedTax - proposedDiscountNet);
          }
          if (minPriceRow > 0 && proposedNet < minPriceRow) {
            this.makeToast('warning', this.$t('Price_below_min_not_allowed'), this.$t('Warning'));
            return;
          }

          if (this.details[i].product_type !== 'is_service') {
            for (var k = 0; k < this.units.length; k++) {
              if (this.units[k].id == this.detail.sale_unit_id) {
                if (this.units[k].operator == "/") {
                  this.details[i].current =
                    this.detail.fix_stock * this.units[k].operator_value;
                  this.details[i].unitSale = this.units[k].ShortName;
                } else {
                  this.details[i].current =
                    this.detail.fix_stock / this.units[k].operator_value;
                  this.details[i].unitSale = this.units[k].ShortName;
                }
              }
            }
          if (this.details[i].current < this.details[i].quantity) {
            this.details[i].quantity = this.details[i].current;
          }
          }
        this.details[i].Unit_price = unitPriceNum;
        this.details[i].price_type = this.detail.price_type;
          this.details[i].tax_percent = this.detail.tax_percent;
          this.details[i].tax_method = this.detail.tax_method;
          this.details[i].discount_Method = this.detail.discount_Method;
          this.details[i].discount = this.detail.discount;
          this.details[i].sale_unit_id = this.detail.sale_unit_id;
          this.details[i].imei_number = this.detail.imei_number;
          this.details[i].product_type = this.detail.product_type;

          // reuse computed values
          this.details[i].DiscountNet = proposedDiscountNet;
          this.details[i].taxe = proposedTax;
          this.details[i].Net_price = proposedNet;
          this.details[i].Total_price = parseFloat(proposedNet + proposedTax);
          this.$forceUpdate();
        }
      }
      this.CalculTotal();
      setTimeout(() => {
        this.$bvModal.hide("form_Update_Detail");
      }, 1000);
    },

    async onClientSelected(selectedClientId) {
      this.client_name = '';
      this.selectedClientPoints = 0;
      this.points_to_convert = 0;
      this.discount_from_points = 0;
      this.used_points = 0;
      this.clientIsEligible = false;
      this.pointsConverted = false;
      this.sale.discount = 0;

      const client = this.clients.find(c => c.id === selectedClientId);
      if (client) {
        this.client_name = client.name;
        this.selectedClientId = selectedClientId;

        try {
          const response = await axios.get(`/get_points_client/${selectedClientId}`);
          const data = response.data;

          if (data.is_royalty_eligible) {
            this.selectedClientPoints = data.points;
            this.clientIsEligible = true;
          } else {
            this.selectedClientPoints = 0;
            this.clientIsEligible = false;
          }
        } catch (error) {
          console.error('Error fetching client points:', error);
        }

      }

      this.CalculTotal();
    },

    convertPointsToDiscount() {
      if (this.pointsConverted) {
        const current = Number(this.sale.discount || 0);
        const toRemove = Number(this.discount_from_points || 0);
        this.sale.discount = Math.max(0, parseFloat((current - toRemove).toFixed(2)));
        this.discount_from_points = 0;
        this.used_points = 0;
        this.points_to_convert = 0;
        this.pointsConverted = false;
      } else {
        const maxPoints = Number(this.selectedClientPoints) || 0;
        let pts = Number(this.points_to_convert);
        if (!Number.isFinite(pts) || pts <= 0) {
          this.makeToast('warning', this.$t ? this.$t('Please_enter_points_to_convert') : 'Please enter points to convert', this.$t ? this.$t('Warning') : 'Warning');
          return;
        }
        if (pts > maxPoints) pts = maxPoints;
        const discount = parseFloat((pts * this.point_to_amount_rate).toFixed(2));
        const base = Number(this.sale.discount || 0);

        this.discount_from_points = discount;
        this.sale.discount = parseFloat((base + discount).toFixed(2));
        this.used_points = pts;
        this.pointsConverted = true;
      }

      this.CalculTotal();
    },

    onPointsToConvertInput() {
      let max = Number(this.selectedClientPoints) || 0;
      let val = Number(this.points_to_convert);
      if (!Number.isFinite(val)) val = 0;
      if (val < 0) val = 0;
      // enforce integer points
      val = Math.floor(val);
      if (val > max) {
        val = max;
        this.makeToast('warning', this.$t ? this.$t('Entered_points_exceed_available') : 'Entered points exceed available', this.$t ? this.$t('Warning') : 'Warning');
      }
      this.points_to_convert = val;
    },

    // ==================== INVOICE & PRINT METHODS ====================
    Invoice_POS(id) {
      axios.get(`sales_print_invoice/${id}`).then(response => {
        this.invoice_pos.sale = response.data.sale;
        this.invoice_pos.details = response.data.details;
        this.invoice_pos.setting = response.data.setting;
        this.invoice_pos.symbol = response.data.symbol;
        this.invoice_pos.zatca_qr = response.data.zatca_qr;
        this.payments = response.data.payments;
        this.pos_settings = response.data.pos_settings;
        // Mirror index_sale behavior: show modal first, then optionally auto-print
        setTimeout(() => {
          try { this.$bvModal.show('Show_invoice'); } catch(e) {}
          this.$nextTick(() => this.renderZatcaQrPos());
        }, 500);

        if (response.data.pos_settings && response.data.pos_settings.is_printable) {
          setTimeout(() => {
            try {
              if (typeof window !== 'undefined' && (window.innerWidth <= 768 || window.matchMedia('(max-width: 768px)').matches)) {
                this.print_pos_mobile();
              } else {
                this.print_pos();
              }
            } catch(e) {
              try { this.print_pos(); } catch(_) {}
            }
          }, 1000);
        }
      });
    },
    renderZatcaQrPos() {
      try {
        if (!this.invoice_pos || !this.invoice_pos.setting || !this.invoice_pos.setting.zatca_enabled || !this.invoice_pos.zatca_qr) return;
        const mount = this.$refs.zatcaQrcodePos;
        if (!mount) return;
        mount.innerHTML = '';

        const draw = () => {
          try {
            if (!window.QRCode) return;
            const text = String(this.invoice_pos.zatca_qr || '');
            try { const m = this.$refs.zatcaQrcodePos; if (m) m.setAttribute('title', text); } catch(e) {}
            try {
              new window.QRCode(mount, {
                text,
                width: 180,
                height: 180,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: window.QRCode.CorrectLevel ? window.QRCode.CorrectLevel.H : undefined
              });
            } catch (e1) {
              new window.QRCode(mount, text);
            }
            this.zatcaRenderedPos = true;
            setTimeout(() => {
              if (mount && !mount.childNodes.length && window.QRCode) {
                try { new window.QRCode(mount, text); } catch(e2) {}
              }
              try {
                const img = mount.querySelector('img');
                if (img) {
                  img.style.display = '';
                  img.style.marginLeft = 'auto';
                  img.style.marginRight = 'auto';
                }
              } catch(e3) {}
            }, 150);
          } catch (e) {}
        };

        if (window.QRCode) {
          draw();
        } else {
          const loadScript = (src, onload, onerror) => {
            const s = document.createElement('script');
            s.src = src;
            s.onload = onload;
            s.onerror = onerror;
            document.head.appendChild(s);
          };

          // Prefer CDN, then vendor, then assets_setup
          loadScript('https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', () => {
            if (window.QRCode) return draw();
            loadScript('/vendor/qrcode/qrcode.min.js', () => {
              if (window.QRCode) return draw();
              loadScript('/assets_setup/js/qrcode.js', draw, draw);
            }, () => loadScript('/assets_setup/js/qrcode.js', draw, () => {}));
          }, () => {
            loadScript('/vendor/qrcode/qrcode.min.js', () => {
              if (window.QRCode) return draw();
              loadScript('/assets_setup/js/qrcode.js', draw, () => {});
            }, () => loadScript('/assets_setup/js/qrcode.js', draw, () => {}));
          });
        }
      } catch (e) {}
    },

    // ==================== CUSTOMER METHODS (from old_pos) ====================
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },
    Submit_Customer() {
      NProgress.start();
      NProgress.set(0.1);
      this.$refs.Create_Customer && this.$refs.Create_Customer.validate().then(success => {
        if (!success) {
          NProgress.done();
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          this.Create_Client();
        }
      }).catch(() => {
        // Fallback when ref not present in new design
        NProgress.done();
      });
    },
    Create_Client() {
      axios
        .post("clients", {
          name: this.client.name,
          email: this.client.email,
          phone: this.client.phone,
          tax_number: this.client.tax_number,
          country: this.client.country,
          city: this.client.city,
          adresse: this.client.adresse,
          is_royalty_eligible: this.client.is_royalty_eligible
        })
        .then(response => {
          NProgress.done();
          const newClient = response.data;
          this.clients.push({
            id: newClient.id,
            name: newClient.name,
          });
          this.selectedClientId = newClient.id;
          this.client_name = newClient.name;
          this.onClientSelected(newClient.id);
          this.makeToast(
            "success",
            this.$t("Successfully_Created"),
            this.$t("Success")
          );
          this.Get_Client_Without_Paginate();
          this.$bvModal.hide("New_Customer");
        })
        .catch(() => {
          NProgress.done();
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },
    New_Client() {
      this.reset_Form_client();
      this.$bvModal.show("New_Customer");
    },
    reset_Form_client() {
      this.client = {
        id: "",
        name: "",
        email: "",
        phone: "",
        tax_number: "",
        country: "",
        city: "",
        adresse: "",
        is_royalty_eligible: ""
      };
    },
    Get_Client_Without_Paginate() {
      axios
        .get("get_clients_without_paginate")
        .then(({ data }) => (this.clients = data));
    },

    // ==================== SALES SNAPSHOT (from old_pos) ====================
    get_today_sales() {
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get("get_today_sales")
        .then(response => {
          this.today_sales = response.data;
          setTimeout(() => {
            this.$bvModal.show("modal_today_sales");
            NProgress.done();
          }, 1000);
        })
        .catch(() => {});
    },

    // ==================== SEARCH HELPERS (from old_pos) ====================
    getResultValue(result) {
      return result.code + " " + "(" + result.name + ")";
    },
    SearchProduct(result) {
      if (this.load_product) {
        this.load_product = false;
        this.product = {};
        if (result.product_type == 'is_service') {
          this.product.quantity = 1;
          this.product.code = result.code;
        } else {
          this.product.code = result.code;
          this.product.current = result.qte_sale;
          this.product.fix_stock = result.qte;
          if (result.qte_sale < 1) {
            this.product.quantity = result.qte_sale;
          } else {
            this.product.quantity = 1;
          }
        }
        this.product.product_variant_id = result.product_variant_id;
        this.Get_Product_Details(result.id, result.product_variant_id);
        this.search_input = '';
        if (this.$refs && this.$refs.product_autocomplete) {
          this.$refs.product_autocomplete.value = "";
        }
        this.product_filter = [];
      } else {
        this.makeToast(
          "warning",
          this.$t("Please_wait_until_the_product_is_loaded"),
          this.$t("Warning")
        );
      }
    },
    print_pos() {
      if (typeof window !== 'undefined' && (window.innerWidth <= 768 || window.matchMedia('(max-width: 768px)').matches)) {
        return this.print_pos_mobile();
      }
      // Try to grab existing DOM markup; if not present, we will not print here.
      var el = document.getElementById('invoice-POS');
      if (!el) { return; }
      var divContents = el.innerHTML;
      var a = window.open('', '', 'height=600,width=480');
      if (!a) { return; }
      a.document.write('<html><head><link rel="stylesheet" href="/css/pos_print.css"></head><body>');
      a.document.write(divContents);
      a.document.write('</body></html>');
      a.document.close();
      const reloadParent = () => {
        try { window.removeEventListener('focus', reloadParent); } catch(e) {}
        try { a.close(); } catch(e) {}
        try { window.location.reload(); } catch(e) {}
      };
      try { window.addEventListener('focus', reloadParent); } catch(e) {}
      try { a.onafterprint = reloadParent; } catch(e) {}
      setTimeout(() => {
        try { a.focus(); } catch(e) {}
        try { a.print(); } catch(e) { reloadParent(); }
      }, 300);
    },

    // Mobile-friendly print via hidden iframe (avoids popup blockers)
    print_pos_mobile() {
      try {
        const el = document.getElementById('invoice-POS');
        if (!el) { return; }
        const html = `<!doctype html><html><head>
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <link rel="stylesheet" href="/css/pos_print.css">
        </head><body>${el.innerHTML}</body></html>`;

        const iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);

        const doc = iframe.contentWindow ? iframe.contentWindow.document : (iframe.contentDocument || null);
        if (!doc) { return; }
        doc.open();
        doc.write(html);
        doc.close();

        const doPrint = () => {
          try { (iframe.contentWindow || iframe).focus(); } catch(e) {}
          try { (iframe.contentWindow || iframe).print(); } catch(e) {}
          setTimeout(() => { try { document.body.removeChild(iframe); } catch(_) {} }, 1500);
        };

        // Give time for stylesheet to load
        setTimeout(doPrint, 500);
      } catch(e) {}
    },
    // Print without relying on the modal being shown
    printInvoiceFromData(data) {
      try {
        const s = data && data.sale ? data.sale : {};
        const set = data && data.setting ? data.setting : {};
        const ps = data && data.pos_settings ? data.pos_settings : {};
        const symbol = (data && data.symbol) ? data.symbol : '';
        const details = Array.isArray(data && data.details) ? data.details : [];
        const payments = Array.isArray(data && data.payments) ? data.payments : [];
        const rows = details.map(d => {
          const qty = (d && this.formatNumber ? this.formatNumber(d.quantity, 2) : (d ? d.quantity : ''));
          const unit = d && d.unit_sale ? d.unit_sale : '';
          const price = (d && this.formatNumber ? this.formatNumber((d.total || 0) / (d.quantity || 1), 2) : '');
          const total = (d && this.formatNumber ? this.formatNumber(d.total || 0, 2) : (d ? d.total : ''));
          const imei = (d && d.is_imei && d.imei_number) ? `<br><span>${this.$t ? this.$t('IMEI_SN') : 'IMEI/SN'} : ${d.imei_number}</span><br>` : '<br>';
          return `
            <tr>
              <td colspan="3">${(d && d.name) || ''}
                ${imei}
                <span>${qty} ${unit} x ${price}</span>
              </td>
              <td style="text-align:right;vertical-align:bottom">${total}</td>
            </tr>
          `;
        }).join('');
        const totalVal = (s.GrandTotal != null ? s.GrandTotal : (s.grand_total != null ? s.grand_total : 0));
        const taxe = (s.taxe != null ? s.taxe : 0);
        const taxRate = (s.tax_rate != null ? s.tax_rate : 0);
        const discount = (s.discount != null ? s.discount : 0);
        const shipping = (s.shipping != null ? s.shipping : 0);
        const paidAmount = (s.paid_amount != null ? s.paid_amount : 0);
        const dueAmount = Math.max(0, Number(totalVal) - Number(paidAmount));
        const totals = `
          ${ps && ps.show_discount ? `<tr><td colspan="3" class="total">${this.$t ? this.$t('OrderTax') : 'OrderTax'}</td><td style="text-align:right" class="total">${symbol} ${this.formatNumber ? this.formatNumber(taxe, 2) : taxe} (${this.formatNumber ? this.formatNumber(taxRate, 2) : taxRate} %)</td></tr>` : ''}
          ${ps && ps.show_discount ? `<tr><td colspan="3" class="total">${this.$t ? this.$t('Discount') : 'Discount'}</td><td style="text-align:right" class="total">${symbol} ${this.formatNumber ? this.formatNumber(discount, 2) : discount}</td></tr>` : ''}
          ${ps && ps.show_discount ? `<tr><td colspan="3" class="total">${this.$t ? this.$t('Shipping') : 'Shipping'}</td><td style="text-align:right" class="total">${symbol} ${this.formatNumber ? this.formatNumber(shipping, 2) : shipping}</td></tr>` : ''}
          <tr><td colspan="3" class="total">${this.$t ? this.$t('Total') : 'Total'}</td><td style="text-align:right" class="total">${symbol} ${this.formatNumber ? this.formatNumber(totalVal, 2) : totalVal}</td></tr>
          ${Number(paidAmount) < Number(totalVal) ? `<tr><td colspan="3" class="total">${this.$t ? this.$t('Paid') : 'Paid'}</td><td style="text-align:right" class="total">${symbol} ${this.formatNumber ? this.formatNumber(paidAmount, 2) : paidAmount}</td></tr>` : ''}
          ${Number(paidAmount) < Number(totalVal) ? `<tr><td colspan="3" class="total">${this.$t ? this.$t('Due') : 'Due'}</td><td style="text-align:right" class="total">${symbol} ${this.formatNumber ? this.formatNumber(dueAmount, 2) : dueAmount}</td></tr>` : ''}
        `;
        const paymentsTable = Number(paidAmount) > 0 ? `
          <table class="change mt-3" style="font-size: 10px;">
            <thead>
              <tr style="background: #eee; ">
                <th style="text-align: left;" colspan="1">${this.$t ? this.$t('PayeBy') : 'Pay By'}</th>
                <th style="text-align: center;" colspan="2">${this.$t ? this.$t('Amount') : 'Amount'}</th>
                <th style="text-align: right;" colspan="1">${this.$t ? this.$t('Change') : 'Change'}</th>
              </tr>
            </thead>
            <tbody>
              ${payments.map(p => {
                const method = p && p.payment_method && p.payment_method.name ? p.payment_method.name : '---';
                const amount = this.formatNumber ? this.formatNumber(p && p.montant ? p.montant : 0, 2) : (p && p.montant ? p.montant : 0);
                const change = this.formatNumber ? this.formatNumber(p && p.change ? p.change : 0, 2) : (p && p.change ? p.change : 0);
                return `<tr><td style="text-align:left" colspan="1">${method}</td><td style="text-align:center" colspan="2">${amount}</td><td style="text-align:right" colspan="1">${change}</td></tr>`;
              }).join('')}
            </tbody>
          </table>
        ` : '';
        const contactLines = `
          <span>${this.$t ? this.$t('date') : 'date'} : ${s.date || ''} <br></span>
          <span>${this.$t ? this.$t('Seller') : 'Seller'} : ${s.seller_name || ''} <br></span>
          ${ps && ps.show_address ? `<span>${this.$t ? this.$t('Adress') : 'Address'} : ${set.CompanyAdress || ''} <br></span>` : ''}
          ${ps && ps.show_email ? `<span>${this.$t ? this.$t('Email') : 'Email'} : ${set.email || ''} <br></span>` : ''}
          ${ps && ps.show_phone ? `<span>${this.$t ? this.$t('Phone') : 'Phone'} : ${set.CompanyPhone || ''} <br></span>` : ''}
          ${ps && ps.show_customer ? `<span>${this.$t ? this.$t('Customer') : 'Customer'} : ${s.client_name || ''} <br></span>` : ''}
          ${ps && ps.show_Warehouse ? `<span>${this.$t ? this.$t('warehouse') : 'Warehouse'} : ${s.warehouse_name || ''} <br></span>` : ''}
        `;
        const legalCopy = ps && ps.show_note ? `
          <div id="legalcopy" class="ml-2">
            <p class="legal"><strong>${ps.note_customer || ''}</strong></p>
          </div>
        ` : '';
        const refLine = s && s.Ref ? `<div class="mt-1" style="font-size:10px;">Ref: ${s.Ref}</div>` : '';
        const html = `
          <div id="invoice-POS">
            <div style="max-width:400px;margin:0 auto">
              <div class="info">
                <div class="invoice_logo text-center mb-2">
                  ${set.logo ? `<img src="/images/${set.logo}" alt width="60" height="60">` : ''}
                </div>
                <p>${contactLines}</p>
              </div>
              <table class="table_data"><tbody>
                ${rows}
                ${totals}
              </tbody></table>
              ${paymentsTable}
              ${legalCopy}
              ${refLine}
            </div>
          </div>
        `;
        const a = window.open('', '', 'height=600,width=480');
        if (!a) { return; }
        a.document.write('<html><head><link rel="stylesheet" href="/css/pos_print.css"></head><body>');
        a.document.write(html);
        a.document.write('</body></html>');
        a.document.close();
        const reloadParent = () => {
          try { window.removeEventListener('focus', reloadParent); } catch(e) {}
          try { a.close(); } catch(e) {}
          try { window.location.reload(); } catch(e) {}
        };
        try { window.addEventListener('focus', reloadParent); } catch(e) {}
        try { a.onafterprint = reloadParent; } catch(e) {}
        setTimeout(() => {
          try { a.focus(); } catch(e) {}
          try { a.print(); } catch(e) { reloadParent(); }
        }, 300);
      } catch(e) {
        try { window.location.reload(); } catch(_) {}
      }
    },

    // ==================== DATA INITIALIZATION ====================
    GetElementsPos() {
      axios
        .get("pos/data_create_pos")
        .then(response => {
          this.clients = response.data.clients;
          this.accounts = response.data.accounts;
          this.warehouses = response.data.warehouses;
          this.categories = response.data.categories;
          this.brands = response.data.brands;
          this.payment_methods = response.data.payment_methods;
          this.sale.warehouse_id = response.data.defaultWarehouse;
          this.selectedClientId = response.data.defaultClient;
          this.client_name = response.data.default_client_name;
          this.clientIsEligible = response.data.default_client_eligible === true || response.data.default_client_eligible === 1;
          this.selectedClientPoints = this.clientIsEligible ? parseFloat(response.data.default_client_points) : 0;
          this.point_to_amount_rate = response.data.point_to_amount_rate;

          this.product_perPage = response.data.products_per_page;
          this.languages_available = response.data.languages_available;
          this.getProducts();
          if (response.data.defaultWarehouse != "") {
            this.Get_Products_By_Warehouse(response.data.defaultWarehouse);
          }
          this.paginate_Brands(this.brand_perPage, 0);
          this.paginate_Category(this.category_perPage, 0);
          this.stripe_key = response.data.stripe_key;
          this.isLoading = false;
        })
        .catch(response => {
          this.isLoading = false;
        });
    },
    onModernPaymentSuccess(evt) {
      // After successful payment via modal, refresh drafts if needed and reset
      if (this.draft_sale_id) {
        try { Fire.$emit('event_delete_draft_sale'); } catch(e) {}
        this.draft_sale_id = '';
      }
      try { this.Reset_Pos(); } catch(e) {}
    },
    // Ensure all non-service items have sufficient stock before proceeding to payment
    verifyAllItemsInStock() {
      for (let i = 0; i < this.details.length; i++) {
        const d = this.details[i];
        if (d && d.product_type !== 'is_service') {
          const available = Number(d.current || 0);
          const qty = Number(d.quantity || 0);
          if (isNaN(available) || isNaN(qty) || qty > available) {
            return { ok: false, productName: d.name || d.code || 'item' };
          }
        }
      }
      return { ok: true, productName: null };
    },
    openModernPaymentModal() {
      // Guard: stock validation before opening payment modal
      const stockCheck = this.verifyAllItemsInStock();
      if (!stockCheck.ok) {
        const msg = this.$t ? `${this.$t('InsufficientStock')} ${stockCheck.productName}` : `Insufficient stock for ${stockCheck.productName}`;
        this.makeToast('danger', msg, this.$t ? this.$t('Failed') : 'Failed');
        return;
      }
      // Guard: total payable must not be negative (zero allowed)
      if (Number(this.GrandTotal) < 0) {
        const msg = this.$t ? `${this.$t('pos.Total_Payable')} cannot be negative` : 'Total Payable cannot be negative';
        this.makeToast('warning', msg, this.$t ? this.$t('Warning') : 'Warning');
        return;
      }
      // Open modern payment modal with current sale data
      this.$refs.modernPaymentModal.openModal({
        amountDue: this.GrandTotal,
        reference: this.sale.Ref || "POS-" + new Date().getTime(),
        notes: this.selectedClientId ? `Payment for Customer #${this.selectedClientId}` : 'POS Payment'
      });
    },
  },
  created() {
    this.GetElementsPos();
    this.addPaymentLine();
    // Initialize warehouse options and sync selection once data is loaded
    this.$watch('warehouses', (ws) => {
      this.warehouseOptions = (ws || []).map(w => ({ value: w.id, text: w.name }));
      if (!this.registerForm.warehouse_id && this.sale && this.sale.warehouse_id) {
        this.registerForm.warehouse_id = this.sale.warehouse_id;
      }
      // Always check current register after initial data load
      this.refreshCurrentRegister();
    });
    // refresh register when warehouse changes
    this.$watch(() => this.sale.warehouse_id, () => {
      this.registerForm.warehouse_id = this.sale.warehouse_id || '';
      this.refreshCurrentRegister();
    });
    // Reset POS after successful payment from ModernPaymentModal
    if (this.$refs && this.$refs.modernPaymentModal) {
      try {
        this.$refs.modernPaymentModal.$on('payment-success', () => {
          this.Reset_Pos();
        });
      } catch(e) {}
    }
    Fire.$on("pay_now", () => {
      setTimeout(() => {
        // Guard: prevent opening legacy payment modal if total is negative
        if (Number(this.GrandTotal) < 0) {
          const msg = this.$t ? `${this.$t('pos.Total_Payable')} cannot be negative` : 'Total Payable cannot be negative';
          this.makeToast('warning', msg, this.$t ? this.$t('Warning') : 'Warning');
          // Complete the animation of the progress bar.
          NProgress.done();
          return;
        }
        this.paymentLines = [{
          amount:          parseFloat(this.GrandTotal.toFixed(2)),
          payment_method_id:       2,
        }];
        this.globalPaymentNote = '';
        this.selectedAccount= null; 
        this.$bvModal.show("Add_Payment");
        // Complete the animation of theprogress bar.
        NProgress.done();
      }, 500);
    });

    Fire.$on("event_delete_draft_sale", () => {
      this.get_Draft_Sales(this.serverParams.page);
      // Complete the animation of theprogress bar.
      setTimeout(() => NProgress.done(), 500);
    });

  }
};
</script>

<style scoped lang="scss">
* {
  box-sizing: border-box;
}

// Color Palette & Typography
$color-bg-light: #f8f9fb;
$color-card-bg: #ffffff;
$color-text-primary: #1a1a2e;
$color-text-secondary: #6b7280;
$color-text-tertiary: #9ca3af;
$color-border-light: #e5e7eb;
$color-gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
$color-gradient-hover: linear-gradient(135deg, #5568d3 0%, #69408f 100%);
$color-success: #10b981;
$color-warning: #f59e0b;
$color-danger: #ef4444;

$font-family-primary: 'Inter', 'Poppins', 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
$font-size-xs: 12px;
$font-size-sm: 14px;
$font-size-base: 16px;
$font-size-lg: 18px;
$font-size-xl: 20px;

$shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
$shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
$shadow-lg: 0 10px 32px rgba(0, 0, 0, 0.12);
$shadow-xl: 0 20px 48px rgba(0, 0, 0, 0.15);

$radius-sm: 6px;
$radius-md: 12px;
$radius-lg: 16px;

$transition-fast: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
$transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

.pos-codecanyon {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: $color-bg-light;
  font-family: $font-family-primary;
  color: $color-text-primary;
  overflow: hidden;

  /* Custom Scrollbar */
  ::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }

  ::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.02);
  }

  ::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.08);
    border-radius: 4px;

    &:hover {
      background: rgba(0, 0, 0, 0.12);
    }
  }
}

/* ============================================
   HEADER STYLES
   ============================================ */
.pos-header {
  display: flex;
  align-items: center;
  align-items: center;
  gap: 16px;
  padding: 16px 24px;
  background: $color-card-bg;
  border-bottom: 1px solid $color-border-light;
  box-shadow: $shadow-md;
  min-height: 70px;
}

/* Mobile header base styles (hidden by default; shown only on ≤480px) */
.pos-header-mobile {
  display: none;
}

.header-left {
  display: flex;
  align-items: center;
  height: 100%;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;

  .brand-icon {
    width: 40px;
    height: 40px;
    border-radius: $radius-md;
    background: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
    color: inherit;
    box-shadow: none;
    flex-shrink: 0;
  }

  .brand-info {
    h2 {
      margin: 0;
      font-size: 16px;
      font-weight: 700;
      color: $color-text-primary;
      letter-spacing: -0.3px;
      line-height: 1.2;
    }

    p {
      margin: 2px 0 0 0;
      font-size: 11px;
      color: $color-text-tertiary;
      font-weight: 400;
      line-height: 1.2;
    }
  }
}

.header-center {
  display: flex;
  align-items: center;
  height: 100%;
  flex: 1 1 auto;

  .search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    height: 40px;
    width: 100%;

    .search-icon {
      position: absolute;
      left: 12px;
      width: 18px;
      height: 18px;
      color: $color-text-tertiary;
      pointer-events: none;
    }

    .search-input {
      width: 100%;
      height: 100%;
      padding: 0 50px 0 40px;
      background: $color-bg-light;
      border: none;
      border-radius: $radius-md;
      font-size: $font-size-sm;
      color: $color-text-primary;
      transition: $transition-fast;

      &:focus {
        outline: none;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      }
    }

    /* Style the Scan button like an input-group append */
    > .action-btn-icon {
      position: absolute;
      right: 0;
      top: 0;
      height: 100% !important;
      width: 44px !important;
      padding: 0 !important;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: white;
      border: 1px solid $color-border-light;
      border-left: 1px solid $color-border-light;
      border-radius: 0 $radius-md $radius-md 0;
      box-shadow: none;
    }
  }
}

/* Autocomplete dropdown positioning */
.pos-autocomplete-results {
  position: absolute;
  top: 100%;
  right: 39px;
  left: -2px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1 1 auto;
  justify-content: flex-end;
}

/* Register status unified button styling */
.register-status {
  display: inline-flex;
  align-items: center;
}

.register-status .register-toggle-btn {
  background: $color-bg-light;
  color: $color-text-primary;
  border: 1px solid $color-border-light;
  padding: 4px 10px;
  font-weight: 600;
}

.register-status .register-toggle-btn:hover {
  background: white;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.08);
}

.register-status .register-toggle-btn:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.12);
}

.warehouse-select,
.customer-select-header {
  padding: 8px 12px;
  height: 40px;
  background: $color-bg-light;
  border: none;
  border-radius: $radius-sm;
  font-size: 13px;
  color: $color-text-primary;
  cursor: pointer;
  transition: $transition-fast;
  flex: 1;
  min-width: 0;

  &:focus {
    outline: none;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }
}

.user-profile {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: $color-gradient-primary;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  color: white;
  font-size: $font-size-sm;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
  cursor: pointer;
  transition: $transition-fast;
  flex-shrink: 0;

  &:hover {
    transform: scale(1.05);
  }
}

/* ============================================
   MAIN CONTAINER & LAYOUT
   ============================================ */
.pos-container {
  display: grid;
  grid-template-columns: 1fr 1.4fr;
  gap: 24px;
  padding: 24px;
  padding-bottom: 100px;
  flex: 1;
  overflow: hidden;
  height: 100%;
}

.pos-column-left {
  display: flex;
  flex-direction: column;
  gap: 24px;
  overflow: hidden;
  height: 100%;
  min-height: 0;
}

/* ============================================
   CARD STYLING
   ============================================ */
.card {
  background: $color-card-bg;
  border-radius: $radius-lg;
  box-shadow: $shadow-md;
  border: 1px solid $color-border-light;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: $transition-smooth;

  &:hover {
    box-shadow: $shadow-lg;
  }
}

.card-header {
  padding: 14px 20px;
  border-bottom: 1px solid $color-border-light;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(to right, #fafbfc 0%, white 100%);
  flex-shrink: 0;

  h3 {
    margin: 0;
    font-size: $font-size-lg;
    font-weight: 600;
    color: $color-text-primary;
    letter-spacing: -0.2px;
  }

  .badge-count {
    background: $color-gradient-primary;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
  }

  .filter-section {
    display: flex;
    gap: 10px;
    align-items: center;
  }
}

/* ============================================
   FLAT INPUTS & SELECTS
   ============================================ */
.flat-input,
.flat-select {
  padding: 10px 12px;
  background: $color-bg-light;
  border: none;
  border-radius: $radius-sm;
  font-size: $font-size-sm;
  color: $color-text-primary;
  font-family: $font-family-primary;
  transition: $transition-fast;
  cursor: pointer;

  &:focus {
    outline: none;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  &::placeholder {
    color: $color-text-tertiary;
  }
}

.flat-select {
  appearance: none;
  background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="rgb(107, 114, 128)" stroke-width="2"%3e%3cpolyline points="6 9 12 15 18 9"%3e%3c/polyline%3e%3c/svg%3e');
  background-repeat: no-repeat;
  background-position: right 8px center;
  background-size: 20px;
  padding-right: 36px;
}

/* ============================================
   CARD: UNIFIED CHECKOUT
   ============================================ */
.card-unified-checkout {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-height: 0;
}

.cart-section {
  flex: 0 0 auto;
  overflow-y: auto;
  max-height: 45%;
  padding: 12px 16px;
  border-bottom: 1px solid $color-border-light;
  min-height: 80px;
}

.cart-items-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 8px;
}

.cart-item-card {
  padding: 10px;
  background: linear-gradient(to right, #f9fafb 0%, white 100%);
  border: 1px solid $color-border-light;
  border-radius: $radius-sm;
  transition: $transition-smooth;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 8px;
  grid-template-areas:
    "header header"
    "sku sku"
    "qty price";

  &:hover {
    border-color: #667eea;
    background: white;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
  }

  .item-header {
    grid-area: header;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 6px;

    .item-name {
      margin: 0;
      font-size: 12px;
      font-weight: 600;
      color: $color-text-primary;
      flex: 1;
      word-break: break-word;
    }

    .edit-btn {
      width: 24px;
      height: 24px;
      min-width: 24px;
      border: 1px solid $color-border-light;
      background: white;
      color: $color-text-secondary;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: $transition-fast;
      padding: 0;
      -webkit-tap-highlight-color: transparent;

      svg {
        width: 14px;
        height: 14px;
        display: block;
      }

      &:hover {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.06);
        color: #667eea;
        transform: scale(1.05);
      }

      &:focus,
      &:active,
      &:focus-visible {
        outline: none !important;
        box-shadow: none !important;
      }
    }

    .remove-btn {
      width: 24px;
      height: 24px;
      min-width: 24px;
      border: 1px solid $color-border-light;
      background: white;
      color: #ef4444;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: $transition-fast;
      padding: 0;
      -webkit-tap-highlight-color: transparent;

      svg {
        width: 14px;
        height: 14px;
        display: block;
      }

      &:hover {
        border-color: #ef4444;
        background: rgba(239, 68, 68, 0.06);
        color: #ef4444;
        transform: scale(1.05);
      }

      &:focus,
      &:active,
      &:focus-visible {
        outline: none !important;
        box-shadow: none !important;
      }
    }
  }

  .item-sku {
    grid-area: sku;
    margin: 0;
    font-size: 10px;
    color: $color-text-tertiary;
    font-weight: 500;
  }

.item-qty-section {
  grid-area: qty;
  display: flex;
  align-items: center;
  gap: 6px;

  .qty-controller {
    display: flex;
    align-items: center;
    gap: 4px;

    .qty-btn {
      width: 24px;
      height: 24px;
      border: 1px solid $color-border-light;
      background: white;
      border-radius: 3px;
      cursor: pointer;
      font-size: 12px;
      font-weight: 600;
      color: $color-text-primary;
      transition: $transition-fast;
      padding: 0;
      -webkit-tap-highlight-color: transparent;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      outline: none;

      &:hover {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.05);
      }

      &:focus,
      &:focus-visible,
      &:active {
        outline: none !important;
        box-shadow: none !important;
        -webkit-box-shadow: none !important;
        border-color: $color-border-light !important;
      }

      &::-moz-focus-inner { border: 0; }
      &:-moz-focusring { outline: none; }
    }

    .qty-input {
      width: 40px;
      padding: 4px 6px;
      background: $color-bg-light;
      border: none;
      border-radius: 3px;
      text-align: center;
      font-size: 11px;
      font-weight: 600;
      transition: $transition-fast;

      &:focus {
        outline: none;
        background: white;
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
      }
    }
  }
}

.pos-price-container {
  max-width: 220px;
}

.pos-price-select {
  min-width: 120px;
  padding: 2px 6px;
  height: 28px;
}

  .item-price {
    grid-area: price;
    text-align: right;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;

    .item-amount {
      background: $color-gradient-primary;
      background-clip: text;
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .pos-price-select {
      -webkit-text-fill-color: initial;
      -webkit-background-clip: initial;
      background-clip: initial;
      color: $color-text-primary;
    }
  }
}

.summary-section {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  min-height: 0;
  padding-bottom: 80px;
}

/* ============================================
   CARD: SUMMARY
   ============================================ */
.card-summary {
  flex: 1;
  min-height: auto;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  padding-bottom: 0;
}

.charges-section {
  padding: 12px 20px;
  border-bottom: 1px solid $color-border-light;
  display: flex;
  flex-direction: column;
  gap: 0px;
  flex-shrink: 0;
}

.charge-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0;
  padding: 3px 0;
  border-bottom: 1px solid $color-border-light;

  &:last-child {
    border-bottom: none;
  }

  &.no-border-bottom {
    border-bottom: none;
  }

  label {
    font-size: $font-size-xs;
    font-weight: 600;
    color: $color-text-secondary;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    flex-shrink: 0;
  }

  .charge-input-group {
    position: relative;
    display: flex;
    align-items: center;
    width: 100px;

    .flat-input {
      width: 100%;
      text-align: left;
      font-size: 13px;
      padding: 5px 10px;
    }

    .input-suffix {
      position: absolute;
      right: 10px;
      font-size: 10px;
      color: $color-text-tertiary;
      pointer-events: none;
      font-weight: 600;
    }
  }
}

.summary-totals {
  padding: 12px 20px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
  background: $color-card-bg;
  border-top: 2px solid $color-border-light;
  margin-top: auto;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
  gap: 8px;

  .total-label {
    color: $color-text-secondary;
    font-weight: 500;
    flex: 1;
    min-width: 0;

    &.discount-row {
      color: $color-danger;
    }
  }

  .total-value {
    color: $color-text-primary;
    font-weight: 600;
    text-align: right;
    flex-shrink: 0;

    &.discount-value {
      color: $color-danger;
    }
  }

  &.grand-total {
    margin-top: 4px;
    padding-top: 6px;
    border-top: 1px solid $color-border-light;
    margin-bottom: 0;

    .total-label {
      font-weight: 700;
      color: $color-text-primary;
    }

    .total-value {
      font-size: 15px;
      font-weight: 700;
    }
  }
}

.summary-divider {
  height: 1px;
  background: $color-border-light;
  margin: 2px 0;
}

.gradient-text {
  background: $color-gradient-primary;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* ============================================
   EMPTY STATE
   ============================================ */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  color: $color-text-tertiary;
  text-align: center;
  padding: 20px;
  min-height: 80px;

  svg {
    width: 40px;
    height: 40px;
    margin-bottom: 10px;
    opacity: 0.15;
    stroke-width: 1.5;
  }

  p {
    margin: 0 0 4px 0;
    font-size: 13px;
    font-weight: 500;
    color: $color-text-secondary;
  }

  .empty-hint {
    font-size: 11px;
    color: $color-text-tertiary;
  }
}

/* ============================================
   CARD: PRODUCTS
   ============================================ */
.card-products {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.reset-filters-btn {
  width: 36px;
  height: 36px;
  border: 1px solid $color-border-light;
  background: white;
  border-radius: $radius-sm;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: $transition-fast;
  outline: none;
  box-shadow: none;

  svg {
    width: 16px;
    height: 16px;
    color: $color-text-secondary;
  }

  &:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);

    svg {
      color: #667eea;
    }
  }

  &:focus,
  &:active,
  &:focus-visible {
    outline: none !important;
    box-shadow: none !important;
  }
  -webkit-tap-highlight-color: transparent;
}

.products-container {
  flex: 1;
  overflow-y: auto;
  padding: 20px 24px;
  padding-bottom: 100px;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 16px;
}

.product-card {
  background: $color-card-bg;
  border: 1px solid $color-border-light;
  border-radius: $radius-md;
  overflow: hidden;
  cursor: pointer;
  transition: $transition-smooth;
  display: flex;
  flex-direction: column;
  height: 100%;

  &:hover {
    border-color: #667eea;
    box-shadow: 0 12px 32px rgba(102, 126, 234, 0.15);
    transform: translateY(-6px);

    .add-to-cart-btn {
      transform: scale(1.15);
    }
  }
}

.product-image-wrapper {
  position: relative;
  width: 100%;
  height: 140px;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border-bottom: 1px solid $color-border-light;

  .product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
  }

  .product-image-placeholder {
    font-size: 48px;
    font-weight: 700;
    color: rgba(102, 126, 234, 0.2);
  }

  .discount-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    background: linear-gradient(135deg, $color-warning 0%, $color-danger 100%);
    color: white;
    padding: 6px 10px;
    border-radius: $radius-sm;
    font-size: 11px;
    font-weight: 700;
    box-shadow: 0 3px 10px rgba(239, 68, 68, 0.25);
  }
}

.product-details {
  padding: 12px;
  flex: 1;
  display: flex;
  flex-direction: column;

  .product-name {
    margin: 0 0 4px 0;
    font-size: 13px;
    font-weight: 600;
    color: $color-text-primary;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .product-brand {
    margin: 0 0 4px 0;
    font-size: 11px;
    color: $color-text-tertiary;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    font-weight: 500;
  }

  .product-stock {
    margin: 0 0 8px 0;
    font-size: 11px;
    color: $color-success;
    font-weight: 600;

    &.low-stock {
      color: $color-warning;
    }
  }

  .product-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid $color-border-light;

    .product-price {
      font-size: 14px;
      font-weight: 700;
      background: $color-gradient-primary;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .add-to-cart-btn {
      width: 32px;
      height: 32px;
      border: none;
      background: rgba(102, 126, 234, 0.1);
      color: #667eea;
      border-radius: $radius-sm;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: $transition-fast;
      outline: none;
      box-shadow: none;

      svg {
        width: 16px;
        height: 16px;
      }

      &:hover:not(:disabled) {
        background: rgba(102, 126, 234, 0.2);
      }

      &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
      }

      &:focus,
      &:active,
      &:focus-visible {
        outline: none !important;
        box-shadow: none !important;
      }
      -webkit-tap-highlight-color: transparent;
    }
  }
}

/* ============================================
   FIXED FOOTER BAR
   ============================================ */
.pos-footer-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  padding: 12px 24px;
  background: $color-card-bg;
  border-top: 1px solid $color-border-light;
  box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.08);
  z-index: 1000;
  height: auto;
}

.action-btn {
  padding: 12px 20px;
  border: none;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: $transition-smooth;
  font-family: $font-family-primary;
  outline: none;
  box-shadow: none;

  svg {
    width: 18px;
    height: 18px;
  }

  &:hover {
    transform: translateY(-2px);
  }

  &:active {
    transform: translateY(0);
  }

  &:focus,
  &:active,
  &:focus-visible {
    outline: none !important;
    box-shadow: none !important;
  }
  -webkit-tap-highlight-color: transparent;
}

.action-btn-secondary {
  border: 1.5px solid $color-border-light;
  background: white;
  color: $color-text-secondary;

  &:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
    color: #667eea;
  }
}

::v-deep(.action-btn-icon) {
  width: 44px;
  height: 44px;
  padding: 0;
  border: 1px solid $color-border-light;
  background: white;
  color: $color-text-secondary;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  cursor: pointer;

  i {
    font-size: 18px;
    line-height: 1;
    display: inline-block;
    vertical-align: middle;
  }

  &:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
    color: #667eea;
  }

  &:focus,
  &:active,
  &:focus-visible {
    outline: none !important;
    box-shadow: none !important;
  }
  -webkit-tap-highlight-color: transparent;
}

/* Ensure icomoon icons render consistently inside this component */
::v-deep(i[class^="i-"]) {
  line-height: 1;
  display: inline-block;
  vertical-align: middle;
}

.action-btn-primary {
  background: $color-gradient-primary;
  color: white;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.25);
  flex: 1;
  max-width: 300px;
  justify-content: center;

  &:hover:not(:disabled) {
    box-shadow: 0 6px 24px rgba(102, 126, 234, 0.35);
    background: $color-gradient-hover;
  }

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

.footer-space {
  flex: 1;
}
/* Languages dropdown */
::v-deep(#lang-dd .dropdown-menu) {
  min-width: 220px;
  padding: 8px;
}

.lang-menu {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 6px;
}

.lang-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  border: 1px solid $color-border-light;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  width: 100%;
  text-align: left;
}
.lang-item:hover {
  border-color: #667eea;
  background: rgba(102, 126, 234, 0.06);
}
.lang-item .flag-icon { width: 20px; height: 14px; object-fit: cover; }
.lang-item .title-lang { font-size: 12px; color: $color-text-primary; }
/* New Customer Modal improvements */
::v-deep(.new-customer-form) {
  .form-group {
    margin-bottom: 12px;
  }

  input.form-control {
    height: 38px;
    border-radius: 8px;
  }

  .custom-control-label {
    user-select: none;
  }

  .loyalty-eligible-row {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 10px 12px;
    border: 1px solid $color-border-light;
    border-radius: 10px;
    background: #fff;
  }

  .loyalty-info {
    display: flex;
    flex-direction: column;
  }

  .loyalty-title {
    font-weight: 700;
    color: $color-text-primary;
    margin-bottom: 2px;
  }

  .loyalty-help {
    font-size: 12px;
  }
}

.pos-gate-loader {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh;
  width: 100vw;
}

.card-loading-overlay {
  position: absolute;
  inset: 0;
  background: rgba(255,255,255,0.6);
  backdrop-filter: saturate(150%) blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: inherit;
  z-index: 2;
}

/* Today Sales modal */
.today-sales-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.ts-card {
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid $color-border-light;
  border-radius: 10px;
  padding: 12px;
  background: #fff;
}

.ts-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.ts-icon i { font-size: 18px; }
.ts-icon.primary { background: #667eea; }
.ts-icon.success { background: #10b981; }
.ts-icon.warning { background: #f59e0b; }
.ts-icon.info { background: #3b82f6; }
.ts-icon.danger { background: #ef4444; }

.ts-content { display: flex; flex-direction: column; }
.ts-label { font-size: 12px; color: $color-text-tertiary; }
.ts-value { font-weight: 700; color: $color-text-primary; }

/* ============================================
   TOTAL PAYABLE SECTION
   ============================================ */
.total-payable-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 0 20px;
  border-radius: $radius-md;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
  padding: 12px 20px;
}

/* Points convert UI */
.points-group {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 8px;
  align-items: center;
}

.convert-points-btn {
  border: 1px solid $color-border-light;
  background: white;
  color: #111827;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}
.convert-points-btn:hover {
  border-color: #667eea;
  background: rgba(102,126,234,.06);
}
.convert-points-btn.converted {
  border: 1px solid #9CA3AF;
  color: #6B7280;
}

/* Redesigned points row */
.points-convert-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
  align-items: stretch;
  padding: 10px;
  border: 1px solid $color-border-light;
  border-radius: 10px;
  background: #fff;
}
.points-convert-row.converted {
  border-color: #9CA3AF;
  background: #f9fafb;
}
.points-left { display: flex; flex-direction: column; gap: 6px; }
.points-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.label-line {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 600;
  color: $color-text-primary;
}
.points-value { font-weight: 700; color: $color-text-primary; text-align: right; }
.discount-hint {
  grid-column: 1 / -1;
  font-size: 12px;
  color: #10b981;
}
.points-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
.points-actions .flat-input { width: 140px; max-width: 100%; }
.convert-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid #e5e7eb;
  background: white;
  color: #111827;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}
.convert-btn:hover { border-color: #667eea; background: rgba(102,126,234,.06); }
.convert-btn.converted { border-color: #9CA3AF; color: #6B7280; }
.convert-btn:focus,
.convert-btn:active,
.convert-btn:focus-visible { outline: none !important; box-shadow: none !important; }
.convert-btn { -webkit-tap-highlight-color: transparent; }

/* Backward compatibility for earlier class name */
.convert-points-btn:focus,
.convert-points-btn:active,
.convert-points-btn:focus-visible { outline: none !important; box-shadow: none !important; }
.convert-points-btn { -webkit-tap-highlight-color: transparent; }

@media (max-width: 576px) {
  .points-actions { justify-content: flex-start; flex-direction: column; align-items: stretch; }
  .points-actions .flat-input { width: 100%; }
  .convert-btn { width: 100%; justify-content: center; }
}

.payable-label {
  font-size: 11px;
  font-weight: 600;
  color: $color-text-tertiary;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.payable-amount {
  font-size: 20px;
  font-weight: 700;
  background: $color-gradient-primary;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.footer-divider {
  width: 1px;
  height: 40px;
  background: $color-border-light;
  margin: 0 8px;
}

/* ============================================
   PAGINATION FOOTER
   ============================================ */
.pagination-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 12px 16px;
  border-top: 1px solid $color-border-light;
  background: linear-gradient(to right, #fafbfc 0%, white 100%);
  flex-shrink: 0;
  position: sticky;
  bottom: 0;
  z-index: 50;
}

.pagination-btn {
  width: 36px;
  height: 36px;
  border: 1px solid $color-border-light;
  background: white;
  border-radius: $radius-sm;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: $transition-fast;
  color: $color-text-secondary;
  flex-shrink: 0;

  svg {
    width: 16px;
    height: 16px;
  }

  &:hover:not(:disabled) {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
    color: #667eea;
  }

  &:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
}

.pagination-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  min-width: 140px;

  .page-number {
    font-size: 12px;
    font-weight: 600;
    color: $color-text-primary;
  }

  .products-count {
    font-size: 11px;
    color: $color-text-tertiary;
  }
}

.pagination-dots {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  justify-content: center;
  max-width: 300px;
}

.pagination-dot {
  width: 32px;
  height: 32px;
  border: 1px solid $color-border-light;
  background: white;
  border-radius: $radius-sm;
  cursor: pointer;
  font-size: 11px;
  font-weight: 600;
  color: $color-text-secondary;
  transition: $transition-fast;
  display: flex;
  align-items: center;
  justify-content: center;

  &:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
    color: #667eea;
  }

  &.active {
    background: $color-gradient-primary;
    color: white;
    border-color: transparent;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
  }
}

/* ============================================
   RESPONSIVE DESIGN
   ============================================ */
@media (max-width: 1400px) {
  .pos-container {
    grid-template-columns: 400px 1fr;
    gap: 20px;
  }

  .products-grid[data-v-4cc49487] {
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  }

  .pos-header { gap: 16px; }

  .header-right {
    gap: 8px;

    .warehouse-select,
    .customer-select-header {
      padding: 8px 10px;
      font-size: 12px;
    }
  }
}

@media (max-width: 1200px) {
  .pos-header { gap: 12px; }

  .summary-breakdown {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px dashed var(--color-border);
    display: grid;
    grid-template-columns: 1fr;
    gap: 6px;
  }

  .bd-item {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #6b7280;
  }

  .brand-icon {
    width: 36px;
    height: 36px;
    font-size: 18px;
  }

  .brand-info h2 {
    font-size: 16px;
  }

  .brand-info p {
    font-size: 10px;
  }

  .header-right {
    .warehouse-select,
    .customer-select-header {
      padding: 6px 8px;
      font-size: 11px;
    }
  }
}

@media (max-width: 1000px) {
  .pos-header { gap: 10px; align-items: stretch; flex-wrap: wrap; }

  .header-left {
    min-width: 0;
  }

  .brand-info h2 {
    font-size: 15px;
  }

  .header-center {
    order: 3;
    flex: 1 1 100%;
    grid-column: 1 / -1;

    .search-wrapper {
      width: 100%;
    }
  }

  .header-right {
    gap: 6px;
    flex-wrap: wrap;
    width: 100%;

    .warehouse-select,
    .customer-select-header {
      padding: 6px 8px;
      font-size: 11px;
      flex: 1;
      min-width: 0;
    }

    .user-profile {
      width: 36px;
      height: 36px;
      font-size: 12px;
      flex-shrink: 0;
    }
  }
  .pos-codecanyon {
    height: auto;
    min-height: 100vh;
    overflow: visible;
  }

  .pos-container {
    grid-template-columns: 1fr;
    gap: 16px;
    padding-bottom: 24px;
    height: auto;
    overflow: visible;
  }

  .pos-column-left {
    gap: 16px;
    height: auto;
    flex-direction: row;
    overflow-x: auto;
  }

  .card-added-products {
    max-height: none;
    flex: 0 0 45%;
    min-width: 0;
  }

  .card-summary {
    flex: 0 0 55%;
    min-width: 0;
  }

  .products-grid {
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  }
  .products-container { padding-bottom: 16px; }
  .cart-section { max-height: none; }
  .pos-footer-bar { position: static; }
}

@media (max-width: 768px) {
  .pos-header { gap: 12px; padding: 12px 16px; min-height: auto; position: static !important; height: 180px; }
  .pos-footer-bar { position: static; }

  /* Show brand icon at tablet size */
  .header-left {
    display: flex !important;
    width: 100%;
    height: auto;
  }

  .header-center {
    width: 100%;
    height: 40px;

    .search-wrapper {
      height: 40px;
      margin-top: 20px;
      
      > .action-btn-icon {
        width: 36px !important;
      }
    }
  }

  .header-right {
    width: 100%;
    height: 40px;
    gap: 6px;
    flex-wrap: wrap;

    .warehouse-select,
    .customer-select-header {
      height: 40px;
      padding: 6px 8px;
      font-size: 12px;
    }

    .user-profile {
      width: 40px;
      height: 40px;
      flex-shrink: 0;
    }
  }

  /* Small size language dropdown toggle (override Bootstrap-Vue) */
  ::v-deep(button#lang-dd__BV_toggle_) {
    width: 30px !important;
    height: 30px !important;
    min-width: 30px !important;
    min-height: 30px !important;
    line-height: 30px !important;
    padding: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
  }
  ::v-deep(button#lang-dd__BV_toggle_ > a.action-btn-icon),
  ::v-deep(button#lang-dd__BV_toggle_ .action-btn-icon) {
    width: 30px !important;
    height: 30px !important;
    padding: 0 !important;
    line-height: 30px !important;
  }

  .brand {
    gap: 8px;
  }

  .brand-icon {
    width: 32px;
    height: 32px;
    font-size: 16px;
  }

  .brand-info h2 {
    font-size: 14px;
  }

  .brand-info p {
    font-size: 10px;
  }

  .pos-container {
    padding: 12px 16px;
    gap: 12px;
  }

  /* Small screen spacing removed per request */

  /* Compact header icons on small screens */
  ::v-deep(.action-btn-icon) { width: 30px !important; height: 30px !important; }

  .pos-footer-bar {
    padding: 12px 16px;
    gap: 8px;
    flex-wrap: wrap;
  }

  .action-btn[data-v-4cc49487] {
    padding: 10px 53px;
    font-size: 12px;
  }

  .action-btn-primary {
    max-width: none;
    flex: 1;
    min-width: 100%;
  }

  .total-payable-section {
    order: -1;
    width: 100%;
    margin-bottom: 8px;
  }

  .footer-divider {
    display: none;
  }

  .products-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  }
  .pos-autocomplete-results { left: 0; right: 0; }
  
  /* Filter section full width with 40/40/20 layout */
  .card.card-products .card-header .filter-section {
    width: 100%;
    display: flex;
    gap: 10px;
  }
  .card.card-products .card-header .filter-section > .flat-select {
    flex: 0 0 40%;
    max-width: 40%;
    min-width: 0;
  }
  .card.card-products .card-header .filter-section > .reset-filters-btn[data-v-4cc49487] {
    flex: 0 0 15%;
    max-width: 15%;
  }
  
  /* Hide specific header actions on small screens */
  .header-right .btn-new-customer,
  .header-right .btn-pos-settings,
  .header-right .btn-fullscreen { display: none !important; }

  /* Hide Available Products heading on small screens */
  .card.card-products .card-header > h3 { display: none !important; }
}

@media (max-width: 640px) {
  .pos-header { padding: 10px 12px; gap: 10px; min-height: auto; }

  .header-center {
    height: 38px;

    .search-wrapper {
      height: 38px;
    }

    .search-input {
      padding: 0 10px 0 36px;
      font-size: 12px;
    }

    .search-icon {
      width: 16px;
      height: 16px;
      left: 10px;
    }
  }

  .header-right {
    height: 38px;
    gap: 6px;

    .warehouse-select,
    .customer-select-header {
      height: 38px;
      padding: 6px 8px;
      font-size: 11px;
    }

    .user-profile {
      width: 38px;
      height: 38px;
      font-size: 11px;
    }
  }

  .pos-container {
    padding: 12px 12px;
    gap: 10px;
  }

  .card-header {
    padding: 14px 16px;
    h3 {
      font-size: 15px;
    }
  }

  .charge-row {
    gap: 0;

    label {
      font-size: 10px;
    }

    .charge-input-group {
      width: 100px;
    }
  }

  .products-grid {
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
  }
  
  /* Hide elements on small screens */
  .header-left { display: none !important; }
  .header-right .btn-new-customer,
  .header-right .btn-pos-settings,
  .header-right .btn-fullscreen { display: none !important; }
  .card.card-products .card-header > h3 { display: none !important; }

  .pagination-footer {
    gap: 8px;
    padding: 10px 12px;
  }

  .pagination-dots {
    max-width: 250px;
  }

  .pos-footer-bar {
    padding: 10px 12px;
    gap: 6px;
  }

  .total-payable-section {
    padding: 10px 12px;
  }

  .payable-amount {
    font-size: 18px;
  }

  /* Ensure filter section is full width with 40/40/20 on ≤640px */
  .card.card-products .card-header .filter-section {
    width: 100%;
    display: flex;
    gap: 10px;
  }
  .card.card-products .card-header .filter-section > .flat-select {
    flex: 0 0 40%;
    max-width: 40%;
    min-width: 0;
  }
  .card.card-products .card-header .filter-section > .reset-filters-btn[data-v-4cc49487] {
    flex: 0 0 15%;
    max-width: 15%;
  }
}

@media (max-width: 480px) {
  .pos-header[data-v-4cc49487] { padding: 20px 10px; gap: 1px; min-height: auto; }
  /* Swap headers: hide desktop header, show mobile header */
  .pos-header { display: none !important; }
  .pos-header-mobile { display: block; padding: 12px 10px; background: $color-card-bg; border-bottom: 1px solid $color-border-light; box-shadow: $shadow-md; }

  .pos-header-mobile .mobile-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
  .pos-header-mobile .mobile-row:last-child { margin-bottom: 0; }

  .pos-header-mobile .mobile-top { justify-content: space-between; }
  .pos-header-mobile .mobile-top .brand { display: flex; align-items: center; }
  .pos-header-mobile .mobile-top .brand .brand-icon { width: 44px; height: 44px; border-radius: $radius-md; display: flex; align-items: center; justify-content: center; font-weight: 700; }
  .pos-header-mobile .mobile-top .top-icons { display: inline-flex; align-items: center; gap: 6px; }
  /* Keep icon containers same size as desktop */
  .pos-header-mobile .mobile-top .top-icons .action-btn-icon { width: 40px !important; height: 40px !important; display: inline-flex; align-items: center; justify-content: center; }
  .pos-header-mobile .mobile-top .top-icons .btn-pos-settings { width: 40px !important; height: 40px !important; }
  .pos-header-mobile .mobile-top .top-icons .user-profile { width: 40px !important; height: 40px !important; }
  /* Bootstrap-Vue language toggle button size */
  ::v-deep(button#lang-dd-mobile__BV_toggle_) { width: 40px !important; height: 40px !important; min-width: 40px !important; min-height: 40px !important; padding: 0 !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; }
  ::v-deep(button#lang-dd-mobile__BV_toggle_ > a.action-btn-icon),
  ::v-deep(button#lang-dd-mobile__BV_toggle_ .action-btn-icon) { width: 40px !important; height: 40px !important; }

  /* Bootstrap-Vue user dropdown container and toggle size */
  .pos-header-mobile #user-dd-mobile { width: 40px !important; height: 40px !important; }
  ::v-deep(button#user-dd-mobile__BV_toggle_) { width: 40px !important; height: 40px !important; min-width: 40px !important; min-height: 40px !important; padding: 0 !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; }

  .pos-header-mobile .warehouse-select,
  .pos-header-mobile .customer-select-header { width: 100%; height: 36px; padding: 6px 8px; font-size: 12px; background: $color-bg-light; border: none; border-radius: $radius-sm; }

  .pos-header-mobile .search-wrapper { position: relative; display: flex; align-items: center; height: 36px; width: 100%; }
  .pos-header-mobile .search-icon { position: absolute; left: 8px; width: 14px; height: 14px; color: $color-text-tertiary; pointer-events: none; }
  .pos-header-mobile .search-input { width: 100%; height: 100%; padding: 0 36px 0 32px; background: $color-bg-light; border: none; border-radius: $radius-md; font-size: 12px; color: $color-text-primary; }
  .pos-header-mobile .search-wrapper > .action-btn-icon { position: absolute; right: 0; top: 0; height: 100% !important; width: 32px !important; display: inline-flex; align-items: center; justify-content: center; background: white; border: 1px solid $color-border-light; border-left: 1px solid $color-border-light; border-radius: 0 $radius-md $radius-md 0; }

  /* Reuse existing register button neutral style */
  .pos-header-mobile .register-status { display: inline-flex; align-items: center; gap: 6px; margin-left: auto; }
  .pos-header-mobile .register-toggle-btn { background: $color-bg-light; color: $color-text-primary; border: 1px solid $color-border-light; padding: 4px 10px; font-weight: 600; }
  /* Mobile-only POS header layout */
  .pos-header {
    position: static !important;
    height: auto !important;
    flex-wrap: wrap; /* allow stacking below top row */
    align-items: center; /* align brand with icons on the top row */
  }
  /* Ensure brand is visible and first */
  .header-left {
    display: flex !important;
    order: 0;
    width: auto;
    height: 36px;
    align-items: center;
  }

  .header-center {
    height: 36px;

    .search-wrapper {
      height: 36px;
    }

    .search-input {
      padding: 0 8px 0 32px;
      font-size: 11px;
    }

    .search-icon {
      width: 14px;
      height: 14px;
      left: 8px;
    }
  }

  /* Place search right below register-status */
  .header-center {
    order: 5;
    width: 100%;

    .search-wrapper {
      margin-top: 0;

      > .action-btn-icon {
        width: 32px !important;
      }
    }

    .search-input {
      padding: 0 36px 0 32px;
    }
  }

  .header-right {
    height: 36px;
    gap: 6px;

    .warehouse-select,
    .customer-select-header {
      height: 36px;
      padding: 4px 6px;
      font-size: 10px;
    }

    .user-profile {
      width: 36px;
      height: 36px;
      font-size: 10px;
    }
  }

  /* Arrange header-right content rows and ordering */
  .header-right {
    order: 1;
    width: auto;
    flex: 1 1 auto;
    display: flex;
    flex-wrap: wrap;
    align-content: flex-start;
    min-width: 0;
  }

  /* Top row items: i-Receipt, language, profile (brand is separate in .header-left) */
  .header-right > .action-btn-icon { order: 1; }
  .header-right > .dropdown.action-btn-icon { order: 2; display: inline-flex !important; }
  .header-right > .dropdown:not(.action-btn-icon) { order: 3; }

  /* Next rows: register, search (as sibling), then selects full width; move POS settings below */
  .header-right > .register-status { order: 4; flex: 1 1 100%; min-width: 0; }
  .header-right > .warehouse-select { order: 6; flex: 1 1 100%; min-width: 0; }
  .header-right > .customer-select-header { order: 7; flex: 1 1 100%; min-width: 0; }
  .header-right > .btn-pos-settings { order: 8; display: inline-flex !important; }

  /* Keep brand and icons on the same row */
  .header-left { flex: 0 0 auto; }
  .header-right { flex: 1 1 auto; }

  .brand {
    gap: 6px;
  }

  .brand-icon {
    width: 28px;
    height: 28px;
    font-size: 14px;
  }

  .brand-info h2 {
    font-size: 12px;
  }

  .brand-info p {
    font-size: 9px;
  }

  .pos-container {
    padding: 8px 10px;
    padding-bottom: 100px;
    gap: 8px;
  }

  .pos-column-left {
    gap: 12px;
  }

  .card-header {
    padding: 12px 12px;
    h3 {
      font-size: 13px;
    }
  }

  .charge-row {
    gap: 0;

    label {
      font-size: 10px;
    }

    .charge-input-group {
      width: 100px;
    }
  }

  .products-grid {
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 10px;
  }

  .pagination-footer {
    gap: 6px;
    padding: 8px 10px;
  }

  .pagination-info {
    min-width: 120px;
  }

  .pagination-dots {
    max-width: 200px;
  }

  .pagination-dot {
    width: 28px;
    height: 28px;
    font-size: 10px;
  }

  .product-image-wrapper {
    height: 120px;
  }

  .product-details {
    padding: 10px;

    .product-name {
      font-size: 11px;
    }

    .product-brand {
      font-size: 9px;
    }

    .product-stock {
      font-size: 10px;
    }

    .product-footer {
      .product-price {
        font-size: 12px;
      }
    }
  }

  .pos-footer-bar {
    padding: 8px 10px;
    gap: 4px;

    .action-btn {
      padding: 8px 12px;
      font-size: 10px;
    }

    .action-btn-icon {
      width: 36px;
      height: 36px;

      svg {
        width: 14px;
        height: 14px;
      }
    }

    .action-btn-primary {
      min-width: 100%;
      padding: 8px 12px;
    }
  }

  /* Per request: specific override for action button sizing */
  .pos-footer-bar .action-btn[data-v-4cc49487][data-v-4cc49487] {
    padding: 4px 16px;
    font-size: 10px;
  }
}

.premium-payment-modal {
  --color-primary: #667eea;
  --color-secondary: #764ba2;
  --color-success: #10b981;
  --color-danger: #ef4444;
  --color-warning: #f59e0b;
  --color-border: #e5e7eb;
  --color-bg: #f8f9fb;
  --color-text: #1a1a2e;
  --color-gray: #6b7280;
}

.payment-checkout-wrapper {
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  background: white;
  border-radius: 16px;
  overflow: hidden;
}

/* HEADER */
.checkout-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 28px 32px;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
  color: white;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
}

.checkout-header-content {
  flex: 1;
}

.checkout-title {
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.5px;
}

.checkout-subtitle {
  margin: 4px 0 0 0;
  font-size: 14px;
  opacity: 0.95;
}

.checkout-close {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 10px;
  transition: all 0.2s;

  &:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: scale(1.1);
  }

  svg {
    width: 24px;
    height: 24px;
    stroke-width: 2.5;
  }
}

/* BODY */
.checkout-body {
  flex: 1;
  overflow-y: auto;
  padding: 24px 32px;
}

.checkout-form {
  display: contents;
}

.checkout-row {
  display: grid;
  grid-template-columns: 1fr 1.3fr;
  gap: 28px;

  @media (max-width: 1024px) {
    grid-template-columns: 1fr;
    gap: 20px;
  }
}

/* ORDER SUMMARY */
.order-summary-card {
  background: linear-gradient(135deg, var(--color-bg) 0%, #ffffff 100%);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  height: fit-content;
  position: sticky;
  top: 0;
}

.summary-card-title {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: var(--color-text);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.summary-items {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

.summary-label {
  color: var(--color-gray);
  font-weight: 500;
}

.summary-value {
  color: var(--color-text);
  font-weight: 600;

  &.text-danger {
    color: var(--color-danger);
  }
}

.summary-divider {
  height: 1px;
  background: var(--color-border);
  margin: 4px 0;
}

.summary-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  border-radius: 8px;
}

.total-label {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-gray);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.total-amount {
  font-size: 20px;
  font-weight: 700;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.payment-status {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.status-item {
  text-align: center;
  padding: 10px;
  background: var(--color-bg);
  border-radius: 8px;
}

.status-label {
  display: block;
  font-size: 11px;
  color: var(--color-gray);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
  margin-bottom: 4px;
}

.status-value {
  display: block;
  font-size: 15px;
  font-weight: 700;
  color: var(--color-text);

  &.text-warning {
    color: var(--color-warning);
  }

  &.text-success {
    color: var(--color-success);
  }
}

/* PAYMENT FORM */
.payment-form-card {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-title {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: var(--color-text);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.line-count {
  font-size: 12px;
  color: var(--color-gray);
  background: var(--color-bg);
  padding: 4px 8px;
  border-radius: 4px;
}

/* PAYMENT METHOD TABS */
.payment-method-tabs {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 10px;
}

.method-tab {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 12px;
  border: 2px solid var(--color-border);
  background: white;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text);

  &:hover {
    border-color: var(--color-primary);
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.04) 0%, rgba(118, 75, 162, 0.04) 100%);
  }

  &.active {
    border-color: var(--color-primary);
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    color: var(--color-primary);
  }
}

.method-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;

  svg {
    width: 100%;
    height: 100%;
    stroke-width: 1.5;
  }
}

.method-name {
  text-align: center;
  font-size: 11px;
}

/* PAYMENT LINES */
.payment-lines-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.payment-line {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  transition: all 0.2s;

  &:hover {
    background: white;
    border-color: var(--color-primary);
  }
}

.line-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  min-width: 32px;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
  color: white;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
}

.line-content {
  flex: 1;
}

.amount-input-group {
  font-size: 13px;
}

.line-amount-input {
  border: none;
  background: white;
  border-radius: 6px;
  font-weight: 600;
  font-size: 14px;

  &:focus {
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }
}

.line-remove-btn {
  padding: 4px 8px !important;
  color: var(--color-danger);
  font-size: 16px;
}

.add-line-btn {
  border-color: var(--color-primary);
  color: var(--color-primary);
  margin-top: 4px;
  font-weight: 600;
}

/* QUICK AMOUNT */
.quick-amount-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.quick-btn {
  font-weight: 600;
  border-color: var(--color-border);
  transition: all 0.2s;

  &:hover {
    border-color: var(--color-primary);
    color: var(--color-primary);
  }
}

/* KEYPAD */
.keypad {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.keypad-key {
  padding: 12px;
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text);
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    border-color: var(--color-primary);
    background: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
  }

  &:active {
    transform: translateY(0);
  }
}

/* CREDIT CARD */
.saved-cards {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.saved-cards-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
  font-weight: 600;
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
}

.card-option {
  padding: 16px 12px;
  background: white;
  border: 2px solid var(--color-border);
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: center;
  position: relative;

  &:hover {
    border-color: var(--color-primary);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
  }

  &.selected {
    border-color: var(--color-primary);
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  }
}

.card-chip {
  width: 40px;
  height: 30px;
  background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.card-last-four {
  font-size: 14px;
  font-weight: 700;
  color: var(--color-text);
}

.card-exp {
  font-size: 11px;
  color: var(--color-gray);
}

.card-checkmark {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 24px;
  height: 24px;
  background: var(--color-success);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
}

.new-card-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.card-form-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-gray);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stripe-card-element {
  padding: 12px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  background: white;
}

.card-errors {
  color: var(--color-danger);
  font-size: 12px;
}

/* FORM ELEMENTS */
.form-label-sm {
  font-size: 12px !important;
  font-weight: 600 !important;
  color: var(--color-gray) !important;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-textarea-sm {
  font-size: 13px;
  border: 1px solid var(--color-border);
  border-radius: 6px;

  &:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }
}

.checkboxes-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.checkbox-item {
  font-size: 13px;
  color: var(--color-text);

  i {
    margin-right: 8px;
    font-size: 14px;
  }
}

/* FOOTER */
.checkout-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 32px;
  border-top: 1px solid var(--color-border);
  background: var(--color-bg);
  flex-wrap: wrap;
}

.footer-info {
  display: flex;
  align-items: center;
  gap: 24px;
  flex-wrap: wrap;
}

.footer-amount {
  display: flex;
  flex-direction: column;

  .label {
    font-size: 11px;
    color: var(--color-gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
  }

  .amount {
    font-size: 16px;
    font-weight: 700;
    color: var(--color-text);

    &.text-warning {
      color: var(--color-warning);
    }

    &.text-success {
      color: var(--color-success);
    }
  }
}

.footer-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-cancel {
  padding: 10px 24px;
  font-weight: 600;
  font-size: 13px;
}

.btn-pay {
  padding: 10px 32px;
  background: linear-gradient(135deg, var(--color-success) 0%, #059669 100%);
  border: none;
  color: white;
  font-weight: 600;
  min-width: 160px;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);

  &:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.35);
  }

  &:disabled {
    opacity: 0.6;
  }

  i {
    margin-right: 8px;
  }
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .payment-checkout-wrapper {
    max-height: 100vh;
  }

  .checkout-body {
    padding: 16px;
  }

  .checkout-row {
    gap: 16px;
  }

  .order-summary-card {
    position: static;
    padding: 16px;
  }

  .payment-method-tabs {
    grid-template-columns: repeat(2, 1fr);
  }

  .quick-amount-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .keypad {
    grid-template-columns: repeat(3, 1fr);
  }

  .checkout-footer {
    flex-direction: column;
    align-items: flex-end;
    padding: 12px 16px;
  }

  .footer-info {
    width: 100%;
    justify-content: space-around;
  }

  .footer-actions {
    width: 100%;
    gap: 8px;

    button {
      flex: 1;
    }
  }
}
</style>

