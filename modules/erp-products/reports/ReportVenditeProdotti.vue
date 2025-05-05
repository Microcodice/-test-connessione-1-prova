<template>
    <div class="erp-report">
      <div class="erp-report-header">
        <h1>{{ $t('Report Vendite per Prodotto') }}</h1>
        <div class="erp-report-actions">
          <q-btn 
            color="primary" 
            icon="filter_list" 
            :label="$t('Filtri')" 
            @click="showFilters = !showFilters"
          />
          <q-btn 
            color="secondary" 
            icon="file_download" 
            :label="$t('Esporta')" 
            @click="exportReport"
          />
        </div>
      </div>
  
      <!-- Filtri report -->
      <div v-if="showFilters" class="erp-report-filters q-pa-md q-mb-md">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-3">
            <q-input
              v-model="filters.dateFrom"
              filled
              type="date"
              :label="$t('Data Inizio')"
              clearable
            />
          </div>
          <div class="col-12 col-md-3">
            <q-input
              v-model="filters.dateTo"
              filled
              type="date"
              :label="$t('Data Fine')"
              clearable
            />
          </div>
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.category"
              :options="categoryOptions"
              filled
              :label="$t('Categoria')"
              clearable
            />
          </div>
          <div class="col-12 col-md-3 flex items-center">
            <q-btn
              color="primary"
              :label="$t('Applica Filtri')"
              @click="fetchData"
              class="full-width"
            />
          </div>
        </div>
      </div>
  
      <!-- Statistiche riepilogative -->
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-md-3">
          <q-card class="bg-primary text-white">
            <q-card-section>
              <div class="text-subtitle2">{{ $t('Totale Vendite') }}</div>
              <div class="text-h4">{{ formatPrice(summary.totalSales) }}</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-12 col-md-3">
          <q-card class="bg-secondary text-white">
            <q-card-section>
              <div class="text-subtitle2">{{ $t('Prodotti Venduti') }}</div>
              <div class="text-h4">{{ summary.totalItems }}</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-12 col-md-3">
          <q-card class="bg-positive text-white">
            <q-card-section>
              <div class="text-subtitle2">{{ $t('Prezzo Medio') }}</div>
              <div class="text-h4">{{ formatPrice(summary.averagePrice) }}</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-12 col-md-3">
          <q-card class="bg-info text-white">
            <q-card-section>
              <div class="text-subtitle2">{{ $t('Margine Medio') }}</div>
              <div class="text-h4">{{ summary.averageMargin }}%</div>
            </q-card-section>
          </q-card>
        </div>
      </div>
  
      <!-- Grafico vendite -->
      <q-card class="q-mb-md">
        <q-card-section>
          <h2>{{ $t('Andamento Vendite') }}</h2>
          <div class="chart-container" style="position: relative; height: 300px;">
            <canvas ref="salesChart"></canvas>
          </div>
        </q-card-section>
      </q-card>
  
      <!-- Tabella prodotti più venduti -->
      <q-card>
        <q-card-section>
          <h2>{{ $t('Prodotti Più Venduti') }}</h2>
          <q-table
            :rows="topProducts"
            :columns="topProductsColumns"
            row-key="id"
            :loading="loading"
            :pagination.sync="pagination"
            :rows-per-page-options="[5, 10, 20, 50]"
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
  
            <!-- Colonna unità vendute -->
            <template v-slot:body-cell-quantity="props">
              <q-td :props="props">
                <q-badge color="primary" :label="props.row.quantity" />
              </q-td>
            </template>
  
            <!-- Colonna fatturato -->
            <template v-slot:body-cell-revenue="props">
              <q-td :props="props">
                {{ formatPrice(props.row.revenue) }}
              </q-td>
            </template>
  
            <!-- Colonna margine -->
            <template v-slot:body-cell-margin="props">
              <q-td :props="props">
                <q-badge
                  :color="getMarginColor(props.row.margin)"
                  :label="props.row.margin + '%'"
                />
              </q-td>
            </template>
          </q-table>
        </q-card-section>
      </q-card>
    </div>
  </template>
  
  <script>
  import Chart from 'chart.js/auto';
  
  export default {
    name: 'ReportVenditeProdotti',
    data() {
      return {
        loading: true,
        showFilters: false,
        salesChart: null,
        filters: {
          dateFrom: this.getDefaultDateFrom(),
          dateTo: this.getDefaultDateTo(),
          category: null
        },
        summary: {
          totalSales: 0,
          totalItems: 0,
          averagePrice: 0,
          averageMargin: 0
        },
        topProducts: [],
        salesData: [],
        categoryOptions: [],
        pagination: {
          sortBy: 'revenue',
          descending: true,
          page: 1,
          rowsPerPage: 10
        },
        topProductsColumns: [
          { name: 'thumbnail', label: '', field: 'thumbnail', align: 'center' },
          { name: 'title', label: this.$t('Prodotto'), field: 'title', sortable: true },
          { name: 'sku', label: this.$t('SKU'), field: 'sku', sortable: true },
          { name: 'quantity', label: this.$t('Quantità'), field: 'quantity', sortable: true },
          { name: 'revenue', label: this.$t('Fatturato'), field: 'revenue', sortable: true },
          { name: 'margin', label: this.$t('Margine'), field: 'margin', sortable: true }
        ]
      };
    },
    mounted() {
      this.fetchData();
      this.fetchCategories();
    },
    beforeDestroy() {
      if (this.salesChart) {
        this.salesChart.destroy();
      }
    },
    methods: {
      /**
       * Recupera i dati dal server
       */
      fetchData() {
        this.loading = true;
        
        // Prepara i parametri per la richiesta API
        const params = {
          date_from: this.filters.dateFrom,
          date_to: this.filters.dateTo
        };
        
        if (this.filters.category) {
          params.category = this.filters.category.value;
        }
        
        // Esegue la richiesta API
        this.$erpApi.get('/prodotti/report/vendite', { params })
          .then(response => {
            // Aggiorna il riepilogo
            this.summary = response.data.summary;
            
            // Aggiorna i prodotti più venduti
            this.topProducts = response.data.top_products;
            
            // Aggiorna i dati per il grafico
            this.salesData = response.data.sales_data;
            
            // Aggiorna il grafico
            this.updateChart();
          })
          .catch(error => {
            console.error('Errore nel caricamento del report:', error);
            this.$q.notify({
              color: 'negative',
              position: 'top',
              message: this.$t('Errore nel caricamento del report'),
              icon: 'report_problem'
            });
          })
          .finally(() => {
            this.loading = false;
          });
      },
      
      /**
       * Recupera le categorie dal server
       */
      fetchCategories() {
        this.$erpApi.get('/prodotti/categories')
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
       * Aggiorna il grafico delle vendite
       */
      updateChart() {
        if (this.salesChart) {
          this.salesChart.destroy();
        }
        
        const ctx = this.$refs.salesChart.getContext('2d');
        
        // Estrae i dati per il grafico
        const labels = this.salesData.map(item => item.date);
        const revenues = this.salesData.map(item => item.revenue);
        const quantities = this.salesData.map(item => item.quantity);
        
        this.salesChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [
              {
                label: this.$t('Fatturato'),
                data: revenues,
                backgroundColor: 'rgba(59, 130, 246, 0.6)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                yAxisID: 'y'
              },
              {
                label: this.$t('Quantità'),
                data: quantities,
                type: 'line',
                fill: false,
                backgroundColor: 'rgba(250, 204, 21, 0.6)',
                borderColor: 'rgba(250, 204, 21, 1)',
                borderWidth: 2,
                yAxisID: 'y1'
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
              mode: 'index',
              intersect: false
            },
            scales: {
              y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                  display: true,
                  text: this.$t('Fatturato')
                }
              },
              y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                  display: true,
                  text: this.$t('Quantità')
                },
                grid: {
                  drawOnChartArea: false
                }
              }
            }
          }
        });
      },
      
      /**
       * Esporta il report in formato CSV
       */
      exportReport() {
        // Implementazione dell'esportazione del report
        this.$q.notify({
          color: 'secondary',
          position: 'top',
          message: this.$t('Esportazione report in corso...'),
          icon: 'file_download'
        });
        
        // Prepara i parametri per la richiesta API
        const params = {
          date_from: this.filters.dateFrom,
          date_to: this.filters.dateTo,
          format: 'csv'
        };
        
        if (this.filters.category) {
          params.category = this.filters.category.value;
        }
        
        // Effettua la richiesta per scaricare il file
        this.$erpApi.get('/prodotti/report/vendite/export', { 
          params: params,
          responseType: 'blob'
        })
          .then(response => {
            // Crea un URL per il blob
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `report-vendite-${this.filters.dateFrom}-${this.filters.dateTo}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.$q.notify({
              color: 'positive',
              position: 'top',
              message: this.$t('Report esportato con successo'),
              icon: 'check_circle'
            });
          })
          .catch(error => {
            console.error('Errore nell\'esportazione del report:', error);
            this.$q.notify({
              color: 'negative',
              position: 'top',
              message: this.$t('Errore nell\'esportazione del report'),
              icon: 'report_problem'
            });
          });
      },
      
      /**
       * Ottiene una data di inizio predefinita (30 giorni fa)
       */
      getDefaultDateFrom() {
        const date = new Date();
        date.setDate(date.getDate() - 30);
        return date.toISOString().split('T')[0];
      },
      
      /**
       * Ottiene una data di fine predefinita (oggi)
       */
      getDefaultDateTo() {
        const date = new Date();
        return date.toISOString().split('T')[0];
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
       * Determina il colore in base al margine
       */
      getMarginColor(margin) {
        if (margin < 10) {
          return 'negative';
        } else if (margin < 20) {
          return 'warning';
        } else if (margin < 30) {
          return 'info';
        } else {
          return 'positive';
        }
      }
    }
  };
  </script>
  
  <style>
  .erp-report {
    padding: 20px;
  }
  
  .erp-report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }
  
  .erp-report-header h1 {
    font-size: 24px;
    margin: 0;
  }
  
  .erp-report-filters {
    background-color: var(--q-dark-page);
    border-radius: 4px;
    margin-bottom: 20px;
  }
  
  .erp-report h2 {
    font-size: 18px;
    margin-top: 0;
    margin-bottom: 15px;
  }
  
  .chart-container {
    background-color: white;
    padding: 10px;
    border-radius: 4px;
  }
  
  /* Tema scuro */
  .body--dark .chart-container {
    background-color: #2c2f48;
  }
  
  .body--dark .erp-report-filters {
    background-color: #2c2f48;
  }
  </style>