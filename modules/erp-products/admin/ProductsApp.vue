<template>
    <div class="erp-products">
      <!-- Header con titolo e azioni principali -->
      <div class="erp-products-header">
        <div class="erp-products-title">
          <h1>{{ $t('Gestione Prodotti') }}</h1>
        </div>
        <div class="erp-products-actions">
          <q-btn 
            color="primary" 
            icon="add" 
            :label="$t('Nuovo Prodotto')" 
            @click="showNewProductModal = true"
            v-if="hasPermission('erp_products_create')"
          />
          <q-btn 
            color="secondary" 
            icon="filter_list" 
            :label="$t('Filtri')" 
            @click="showFilters = !showFilters"
          />
        </div>
      </div>
  
      <!-- Area filtri -->
      <div v-if="showFilters" class="erp-products-filters q-pa-md q-mb-md">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-3">
            <q-input 
              v-model="filters.search" 
              :label="$t('Cerca products')" 
              dense 
              outlined 
              clearable
              @update:model-value="onFilterChange"
            >
              <template v-slot:append>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-3">
            <q-select 
              v-model="filters.category" 
              :options="categoryOptions" 
              :label="$t('Categoria')" 
              dense 
              outlined 
              clearable
              @update:model-value="onFilterChange"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-select 
              v-model="filters.stockStatus" 
              :options="stockStatusOptions" 
              :label="$t('Disponibilità')" 
              dense 
              outlined 
              clearable
              @update:model-value="onFilterChange"
            />
          </div>
          <div class="col-12 col-md-3 flex items-center">
            <q-btn 
              color="primary" 
              :label="$t('Applica Filtri')" 
              @click="fetchProducts"
              class="full-width"
            />
          </div>
        </div>
      </div>
  
      <!-- Tabs per navigare tra le diverse sezioni -->
      <q-tabs
        v-model="activeTab"
        class="erp-products-tabs text-primary"
        active-color="primary"
        indicator-color="primary"
        align="left"
        dense
        narrow-indicator
      >
        <q-tab name="elenco" icon="layers" :label="$t('Tutti i products')" />
        <q-tab name="categorie" icon="folder" :label="$t('Categorie')" />
        <q-tab name="attributi" icon="tag" :label="$t('Attributi')" />
        <q-tab name="varianti" icon="box" :label="$t('Varianti')" />
      </q-tabs>
  
      <q-separator />
  
      <!-- Contenuto dei tab -->
      <q-tab-panels v-model="activeTab" animated class="erp-products-tab-panels">
        <q-tab-panel name="elenco">
          <div v-if="loading" class="text-center q-pa-lg">
            <dashboard-loading :message="$t('Caricamento products in corso...')" />
          </div>
          <div v-else>
            <!-- Tabella products -->
            <q-table
              :rows="products"
              :columns="productColumns"
              row-key="id"
              :pagination.sync="pagination"
              :loading="loading"
              :rows-per-page-options="[10, 20, 50, 100]"
              @request="onTableRequest"
            >
              <!-- Colonna thumbnail -->
              <template v-slot:body-cell-thumbnail="props">
                <q-td :props="props">
                  <div class="product-thumbnail">
                    <img v-if="props.row.thumbnail" :src="props.row.thumbnail" :alt="props.row.title">
                    <div v-else class="no-image">
                      <q-icon name="image" size="md" />
                    </div>
                  </div>
                </q-td>
              </template>
  
              <!-- Colonna SKU -->
              <template v-slot:body-cell-sku="props">
                <q-td :props="props">
                  <div class="product-sku">
                    {{ props.row.sku || '-' }}
                  </div>
                </q-td>
              </template>
  
              <!-- Colonna stock -->
              <template v-slot:body-cell-stock="props">
                <q-td :props="props">
                  <div class="product-stock">
                    <q-chip
                      v-if="props.row.manage_stock"
                      :color="getStockColor(props.row.stock, props.row.min_stock)"
                      text-color="white"
                      dense
                    >
                      {{ props.row.stock }}
                    </q-chip>
                    <span v-else>-</span>
                  </div>
                </q-td>
              </template>
  
              <!-- Colonna prezzo -->
              <template v-slot:body-cell-price="props">
                <q-td :props="props">
                  <div class="product-price">
                    {{ formatPrice(props.row.regular_price) }}
                    <div v-if="props.row.sale_price" class="sale-price">
                      {{ formatPrice(props.row.sale_price) }}
                    </div>
                  </div>
                </q-td>
              </template>
  
              <!-- Colonna azioni -->
              <template v-slot:body-cell-actions="props">
                <q-td :props="props">
                  <div class="row no-wrap">
                    <q-btn
                      flat
                      round
                      dense
                      icon="visibility"
                      color="primary"
                      @click="viewProduct(props.row)"
                    />
                    <q-btn
                      flat
                      round
                      dense
                      icon="edit"
                      color="amber"
                      @click="editProduct(props.row)"
                      v-if="hasPermission('erp_products_edit')"
                    />
                    <q-btn
                      flat
                      round
                      dense
                      icon="delete"
                      color="negative"
                      @click="confirmDelete(props.row)"
                      v-if="hasPermission('erp_products_delete')"
                    />
                  </div>
                </q-td>
              </template>
  
              <!-- Nessun risultato -->
              <template v-slot:no-data>
                <div class="text-center q-pa-lg">
                  <p>{{ $t('Nessun prodotto trovato') }}</p>
                  <q-btn
                    color="primary"
                    icon="add"
                    :label="$t('Aggiungi il primo prodotto')"
                    @click="showNewProductModal = true"
                    v-if="hasPermission('erp_products_create')"
                  />
                </div>
              </template>
            </q-table>
          </div>
        </q-tab-panel>
  
        <q-tab-panel name="categorie">
          <div class="erp-products-categories">
            <h2>{{ $t('Gestione Categorie') }}</h2>
            <p>{{ $t('Interfaccia per la gestione delle categorie prodotto') }}</p>
            <!-- Qui inseriremo la gestione categorie -->
          </div>
        </q-tab-panel>
  
        <q-tab-panel name="attributi">
          <div class="erp-products-attributes">
            <h2>{{ $t('Gestione Attributi') }}</h2>
            <p>{{ $t('Interfaccia per la gestione degli attributi prodotto') }}</p>
            <!-- Qui inseriremo la gestione attributi -->
          </div>
        </q-tab-panel>
  
        <q-tab-panel name="varianti">
          <div class="erp-products-variants">
            <h2>{{ $t('Gestione Varianti') }}</h2>
            <p>{{ $t('Interfaccia per la gestione delle varianti prodotto') }}</p>
            <!-- Qui inseriremo la gestione varianti -->
          </div>
        </q-tab-panel>
      </q-tab-panels>
  
      <!-- Modal per nuovo prodotto -->
      <q-dialog v-model="showNewProductModal" persistent>
        <q-card style="min-width: 500px">
          <q-card-section class="row items-center">
            <div class="text-h6">{{ $t('Nuovo Prodotto') }}</div>
            <q-space />
            <q-btn icon="close" flat round dense v-close-popup />
          </q-card-section>
  
          <q-separator />
  
          <q-card-section>
            <q-form @submit="createProduct" class="q-gutter-md">
              <q-input
                v-model="newProduct.title"
                :label="$t('Nome prodotto')"
                :rules="[val => !!val || $t('Il nome è obbligatorio')]"
                outlined
                autofocus
              />
              <q-input
                v-model="newProduct.sku"
                :label="$t('SKU')"
                outlined
              />
              <q-input
                v-model.number="newProduct.regular_price"
                type="number"
                step="0.01"
                :label="$t('Prezzo')"
                outlined
              />
              <q-checkbox
                v-model="newProduct.manage_stock"
                :label="$t('Gestisci inventario')"
              />
              <q-input
                v-if="newProduct.manage_stock"
                v-model.number="newProduct.stock"
                type="number"
                :label="$t('Quantità')"
                outlined
              />
              <q-editor
                v-model="newProduct.description"
                :label="$t('Descrizione')"
                min-height="150px"
              />
              <div class="row justify-end q-gutter-sm">
                <q-btn
                  :label="$t('Annulla')"
                  color="negative"
                  v-close-popup
                  flat
                />
                <q-btn
                  :label="$t('Salva')"
                  color="primary"
                  type="submit"
                />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>
  
      <!-- Modal per modifica prodotto -->
      <q-dialog v-model="showEditProductModal" persistent>
        <q-card style="min-width: 500px">
          <q-card-section class="row items-center">
            <div class="text-h6">{{ $t('Modifica Prodotto') }}</div>
            <q-space />
            <q-btn icon="close" flat round dense v-close-popup />
          </q-card-section>
  
          <q-separator />
  
          <q-card-section v-if="editingProduct">
            <q-form @submit="updateProduct" class="q-gutter-md">
              <q-input
                v-model="editingProduct.title"
                :label="$t('Nome prodotto')"
                :rules="[val => !!val || $t('Il nome è obbligatorio')]"
                outlined
              />
              <q-input
                v-model="editingProduct.sku"
                :label="$t('SKU')"
                outlined
              />
              <q-input
                v-model.number="editingProduct.regular_price"
                type="number"
                step="0.01"
                :label="$t('Prezzo normale')"
                outlined
              />
              <q-input
                v-model.number="editingProduct.sale_price"
                type="number"
                step="0.01"
                :label="$t('Prezzo scontato')"
                outlined
              />
              <q-checkbox
                v-model="editingProduct.manage_stock"
                :label="$t('Gestisci inventario')"
              />
              <q-input
                v-if="editingProduct.manage_stock"
                v-model.number="editingProduct.stock"
                type="number"
                :label="$t('Quantità')"
                outlined
              />
              <q-select
                v-model="editingProduct.stock_status"
                :options="stockStatusOptionsFull"
                :label="$t('Stato inventario')"
                outlined
              />
              <q-editor
                v-model="editingProduct.description"
                :label="$t('Descrizione')"
                min-height="150px"
              />
              <div class="row justify-end q-gutter-sm">
                <q-btn
                  :label="$t('Annulla')"
                  color="negative"
                  v-close-popup
                  flat
                />
                <q-btn
                  :label="$t('Aggiorna')"
                  color="primary"
                  type="submit"
                />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>
  
      <!-- Dialog di conferma eliminazione -->
      <q-dialog v-model="showDeleteConfirm" persistent>
        <q-card>
          <q-card-section class="row items-center">
            <q-avatar icon="delete_forever" color="negative" text-color="white" />
            <span class="q-ml-sm text-h6">{{ $t('Conferma eliminazione') }}</span>
          </q-card-section>
  
          <q-card-section>
            {{ $t('Sei sicuro di voler eliminare il prodotto') }} <strong>{{ deleteProduct?.title }}</strong>?
            <p class="text-negative">{{ $t('Questa azione non può essere annullata.') }}</p>
          </q-card-section>
  
          <q-card-actions align="right">
            <q-btn flat :label="$t('Annulla')" color="primary" v-close-popup />
            <q-btn flat :label="$t('Elimina')" color="negative" @click="deleteSelectedProduct" />
          </q-card-actions>
        </q-card>
      </q-dialog>
    </div>
  </template>
  
  <script>
  import DashboardLoading from '../components/DashboardLoading.vue';
  
  export default {
    name: 'ProductsApp',
    components: {
      DashboardLoading
    },
    data() {
      return {
        activeTab: 'elenco',
        loading: true,
        products: [],
        showFilters: false,
        showNewProductModal: false,
        showEditProductModal: false,
        showDeleteConfirm: false,
        deleteProduct: null,
        editingProduct: null,
        newProduct: {
          title: '',
          sku: '',
          regular_price: 0,
          stock: 0,
          manage_stock: true,
          description: ''
        },
        filters: {
          search: '',
          category: null,
          stockStatus: null
        },
        pagination: {
          sortBy: 'title',
          descending: false,
          page: 1,
          rowsPerPage: 10,
          rowsNumber: 0
        },
        categoryOptions: [],
        stockStatusOptions: [
          { label: this.$t('Tutti'), value: null },
          { label: this.$t('Disponibili'), value: 'instock' },
          { label: this.$t('Esauriti'), value: 'outofstock' },
          { label: this.$t('In arrivo'), value: 'onbackorder' }
        ],
        stockStatusOptionsFull: [
          { label: this.$t('Disponibile'), value: 'instock' },
          { label: this.$t('Esaurito'), value: 'outofstock' },
          { label: this.$t('In arrivo'), value: 'onbackorder' }
        ],
        productColumns: [
          { name: 'thumbnail', label: '', field: 'thumbnail', align: 'center' },
          { name: 'title', label: this.$t('Nome'), field: 'title', sortable: true },
          { name: 'sku', label: this.$t('SKU'), field: 'sku', sortable: true },
          { name: 'stock', label: this.$t('Disponibilità'), field: 'stock', sortable: true },
          { name: 'price', label: this.$t('Prezzo'), field: 'regular_price', sortable: true },
          { name: 'actions', label: this.$t('Azioni'), field: 'actions', align: 'center' }
        ]
      };
    },
    created() {
      this.fetchProducts();
      this.fetchCategories();
    },
    methods: {
      /**
       * Recupera i products dal server
       */
      fetchProducts() {
        this.loading = true;
        
        // Prepara i parametri per la richiesta API
        const params = {
          page: this.pagination.page,
          per_page: this.pagination.rowsPerPage,
          orderby: this.pagination.sortBy || 'title',
          order: this.pagination.descending ? 'DESC' : 'ASC'
        };
        
        // Aggiunge i filtri se presenti
        if (this.filters.search) {
          params.search = this.filters.search;
        }
        
        if (this.filters.category) {
          params.category = this.filters.category.value;
        }
        
        if (this.filters.stockStatus) {
          params.stock_status = this.filters.stockStatus.value;
        }
        
        // Esegue la richiesta API
        this.$erpApi.get('/products/list', { params })
          .then(response => {
            this.products = response.data;
            this.pagination.rowsNumber = parseInt(response.headers['x-wp-total']) || 0;
            this.loading = false;
          })
          .catch(error => {
            console.error('Errore nel caricamento dei products:', error);
            this.$q.notify({
              color: 'negative',
              position: 'top',
              message: this.$t('Errore nel caricamento dei products'),
              icon: 'report_problem'
            });
            this.loading = false;
          });
      },
      
      /**
       * Recupera le categorie dal server
       */
      fetchCategories() {
        this.$erpApi.get('/products/categories')
          .then(response => {
            this.categoryOptions = [
              { label: this.$t('Tutte le categorie'), value: null },
              ...response.data.map(category => ({
                label: category.name,
                value: category.id
              }))
            ];
          })
          .catch(error => {
            console.error('Errore nel caricamento delle categorie:', error);
          });
      },
      
      /**
       * Gestisce il cambio dei filtri
       */
      onFilterChange() {
        // Resetta la paginazione quando cambiano i filtri
        this.pagination.page = 1;
      },
      
      /**
       * Gestisce le richieste della tabella (ordinamento, paginazione)
       */
      onTableRequest(props) {
        this.pagination = props.pagination;
        this.fetchProducts();
      },
      
      /**
       * Visualizza i dettagli di un prodotto
       */
      viewProduct(product) {
        // Implementare la visualizzazione dettagliata del prodotto
        console.log('Visualizza prodotto:', product);
      },
      
      /**
       * Apre il modal per modificare un prodotto
       */
      editProduct(product) {
        this.editingProduct = { ...product };
        this.showEditProductModal = true;
      },
      
      /**
       * Apre il dialog di conferma per eliminare un prodotto
       */
      confirmDelete(product) {
        this.deleteProduct = product;
        this.showDeleteConfirm = true;
      },
      
      /**
       * Crea un nuovo prodotto
       */
      createProduct() {
        // Validazione base
        if (!this.newProduct.title) {
          this.$q.notify({
            color: 'negative',
            position: 'top',
            message: this.$t('Il nome del prodotto è obbligatorio'),
            icon: 'report_problem'
          });
          return;
        }
        
        this.loading = true;
        this.$erpApi.post('/products/create', this.newProduct)
          .then(response => {
            this.$q.notify({
              color: 'positive',
              position: 'top',
              message: this.$t('Prodotto creato con successo'),
              icon: 'check_circle'
            });
            
            // Resetta il form e chiude il modal
            this.newProduct = {
              title: '',
              sku: '',
              regular_price: 0,
              stock: 0,
              manage_stock: true,
              description: ''
            };
            this.showNewProductModal = false;
            
            // Ricarica i products
            this.fetchProducts();
          })
          .catch(error => {
            console.error('Errore nella creazione del prodotto:', error);
            this.$q.notify({
              color: 'negative',
              position: 'top',
              message: this.$t('Errore nella creazione del prodotto'),
              icon: 'report_problem'
            });
          })
          .finally(() => {
            this.loading = false;
          });
      },
      
      /**
       * Aggiorna un prodotto esistente
       */
      updateProduct() {
        if (!this.editingProduct || !this.editingProduct.id) {
          return;
        }
        
        this.loading = true;
        this.$erpApi.post(`/products/update/${this.editingProduct.id}`, this.editingProduct)
          .then(response => {
            this.$q.notify({
              color: 'positive',
              position: 'top',
              message: this.$t('Prodotto aggiornato con successo'),
              icon: 'check_circle'
            });
            
            this.showEditProductModal = false;
            this.fetchProducts();
          })
          .catch(error => {
            console.error('Errore nell\'aggiornamento del prodotto:', error);
            this.$q.notify({
              color: 'negative',
              position: 'top',
              message: this.$t('Errore nell\'aggiornamento del prodotto'),
              icon: 'report_problem'
            });
          })
          .finally(() => {
            this.loading = false;
            this.editingProduct = null;
          });
      },
      
      /**
       * Elimina il prodotto selezionato
       */
      deleteSelectedProduct() {
        if (!this.deleteProduct || !this.deleteProduct.id) {
          return;
        }
        
        this.loading = true;
        this.$erpApi.delete(`/products/delete/${this.deleteProduct.id}`)
          .then(response => {
            this.$q.notify({
              color: 'positive',
              position: 'top',
              message: this.$t('Prodotto eliminato con successo'),
              icon: 'check_circle'
            });
            
            this.showDeleteConfirm = false;
            this.deleteProduct = null;
            this.fetchProducts();
          })
          .catch(error => {
            console.error('Errore nell\'eliminazione del prodotto:', error);
            this.$q.notify({
              color: 'negative',
              position: 'top',
              message: this.$t('Errore nell\'eliminazione del prodotto'),
              icon: 'report_problem'
            });
          })
          .finally(() => {
            this.loading = false;
          });
      },
      
      /**
       * Verifica se l'utente ha un determinato permesso
       */
      hasPermission(permission) {
        // Implementare la verifica dei permessi utilizzando il sistema ACL dell'ERP
        return true; // Per ora restituisce sempre true
      },
      
      /**
       * Formatta un prezzo con la valuta
       */
      formatPrice(price) {
        return new Intl.NumberFormat('it-IT', {
          style: 'currency',
          currency: 'EUR'
        }).format(price || 0);
      },
      
      /**
       * Determina il colore in base alla disponibilità
       */
      getStockColor(stock, minStock) {
        if (stock <= 0) {
          return 'negative'; // Rosso
        } else if (stock <= minStock) {
          return 'warning'; // Giallo
        } else {
          return 'positive'; // Verde
        }
      }
    }
  };
  </script>
  
  <style>
  .erp-products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }
  
  .erp-products-filters {
    background-color: var(--q-dark-page);
    border-radius: 4px;
  }
  
  .erp-products-title h1 {
    font-size: 24px;
    margin: 0;
  }
  
  .product-thumbnail {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 4px;
    margin: 0 auto;
  }
  
  .product-thumbnail img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
  }
  
  .product-thumbnail .no-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f0f0f0;
    color: #aaa;
  }
  
  .product-price .sale-price {
    color: var(--q-negative);
    font-size: 0.8em;
    text-decoration: line-through;
  }
  
  /* Tema scuro */
  .body--dark .erp-products-filters {
    background-color: #2c2f48;
  }
  
  .body--dark .product-thumbnail .no-image {
    background-color: #3a3e5a;
    color: #6b7280;
  }
  </style>