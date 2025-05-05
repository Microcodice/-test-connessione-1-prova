<template>
    <div class="dashboard-container">
      <!-- Statistiche principali -->
      <div class="erp-stats-grid">
        <dashboard-stat-card 
          v-for="card in statCards" 
          :key="card.id"
          :title="card.label"
          :value="card.value"
          :icon="card.icon"
          :color="card.color"
          :loading="loading.stats"
        />
      </div>
      
      <!-- Layout principale dashboard -->
      <div class="erp-dashboard-layout">
        <!-- Sezione Ordini Recenti -->
        <div class="erp-card">
          <div class="erp-card-header">
            Ordini Recenti
          </div>
          <div class="erp-card-content">
            <table class="erp-table" v-if="!loading.orders">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Cliente</th>
                  <th>Data</th>
                  <th>Stato</th>
                  <th>Totale</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="order in recentOrders" :key="order.id">
                  <td>{{ order.id }}</td>
                  <td>{{ order.cliente }}</td>
                  <td>{{ formatDate(order.data) }}</td>
                  <td>
                    <span class="erp-status" :class="'erp-status-' + getStatusClass(order.stato)">
                      {{ order.stato }}
                    </span>
                  </td>
                  <td>{{ order.totale }}</td>
                </tr>
                <!-- Riga vuota quando non ci sono dati -->
                <tr v-if="recentOrders.length === 0">
                  <td colspan="5" class="empty-data">Nessun ordine recente trovato</td>
                </tr>
              </tbody>
            </table>
            <dashboard-loading v-else text="Caricamento ordini recenti..." />
          </div>
        </div>
        
        <!-- Sezione Avvisi Magazzino -->
        <div class="erp-card">
          <div class="erp-card-header">
            Avvisi Magazzino
          </div>
          <div class="erp-card-content">
            <div class="erp-alert-list" v-if="!loading.alerts">
              <dashboard-alert-item 
                v-for="alert in inventoryAlerts" 
                :key="alert.id"
                :title="alert.titolo"
                :description="alert.descrizione"
                :type="alert.tipo"
              />
              <!-- Messaggio quando non ci sono avvisi -->
              <div v-if="inventoryAlerts.length === 0" class="empty-alerts">
                <i class="material-icons">check_circle</i>
                <p>Nessun avviso di magazzino presente</p>
              </div>
            </div>
            <dashboard-loading v-else text="Caricamento avvisi magazzino..." />
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import DashboardStatCard from './components/DashboardStatCard.vue';
  import DashboardAlertItem from './components/DashboardAlertItem.vue';
  import DashboardLoading from './components/DashboardLoading.vue';
  
  export default {
    name: 'DashboardApp',
    components: {
      DashboardStatCard,
      DashboardAlertItem,
      DashboardLoading
    },
    data() {
      return {
        loading: {
          stats: true,
          orders: true,
          alerts: true
        },
        statCards: [
          {
            id: 'orders',
            label: 'Ordini Recenti',
            value: '126',
            icon: 'shopping_cart',
            color: 'blue'
          },
          {
            id: 'products',
            label: 'Prodotti',
            value: '1.254',
            icon: 'inventory_2',
            color: 'purple'
          },
          {
            id: 'clients',
            label: 'Clienti',
            value: '857',
            icon: 'people',
            color: 'green'
          },
          {
            id: 'sales',
            label: 'Vendite Mensili',
            value: '€24.530',
            icon: 'payments',
            color: 'orange'
          }
        ],
        recentOrders: [],
        inventoryAlerts: []
      }
    },
    methods: {
      formatDate(dateString) {
        const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
        return new Date(dateString).toLocaleDateString('it-IT', options);
      },
      getStatusClass(stato) {
        // Normalizza lo stato per le classi CSS
        const statusMap = {
          'Completato': 'completed',
          'In Attesa': 'pending',
          'In Lavorazione': 'processing',
          'Annullato': 'cancelled'
        };
        
        return statusMap[stato] || 'pending';
      },
      async loadDashboardData() {
        try {
          // In una vera implementazione, questi dati verrebbero dalle API
          setTimeout(() => {
            this.loading.stats = false;
          }, 500);
          
          setTimeout(() => {
            this.recentOrders = [
              {
                id: 'ORD-2501',
                cliente: 'Mario Rossi',
                data: '2025-05-01',
                stato: 'Completato',
                totale: '€125,50'
              },
              {
                id: 'ORD-2502',
                cliente: 'Giulia Bianchi',
                data: '2025-05-02',
                stato: 'In Attesa',
                totale: '€350,00'
              },
              {
                id: 'ORD-2503',
                cliente: 'Luigi Verdi',
                data: '2025-05-03',
                stato: 'In Lavorazione',
                totale: '€78,90'
              },
              {
                id: 'ORD-2504',
                cliente: 'Anna Neri',
                data: '2025-05-04',
                stato: 'Annullato',
                totale: '€210,00'
              }
            ];
            this.loading.orders = false;
          }, 800);
          
          setTimeout(() => {
            this.inventoryAlerts = [
              {
                id: 1,
                titolo: 'Mouse Wireless MX500',
                descrizione: 'Stock basso (3 rimasti)',
                tipo: 'warning'
              },
              {
                id: 2,
                titolo: 'Tastiera Meccanica K95',
                descrizione: 'Esaurito - Ordine in elaborazione',
                tipo: 'danger'
              },
              {
                id: 3,
                titolo: 'Monitor HD 27"',
                descrizione: 'Stock basso (2 rimasti)',
                tipo: 'warning'
              }
            ];
            this.loading.alerts = false;
          }, 1000);
        } catch (error) {
          console.error('Errore durante il caricamento dei dati:', error);
          this.loading = {
            stats: false,
            orders: false,
            alerts: false
          };
        }
      }
    },
    mounted() {
      this.loadDashboardData();
    }
  }
  </script>
  
  <style scoped>
  .dashboard-container {
    width: 100%;
  }
  
  .empty-data {
    text-align: center;
    padding: 20px;
    color: #6e6b7b;
  }
  
  .empty-alerts {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px 0;
    color: #28c76f;
    text-align: center;
  }
  
  .empty-alerts i {
    font-size: 40px;
    margin-bottom: 10px;
  }
  </style>