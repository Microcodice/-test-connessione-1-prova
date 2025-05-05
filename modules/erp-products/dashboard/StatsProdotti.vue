<template>
    <div class="row q-col-gutter-md">
      <!-- Totale Prodotti -->
      <div class="col-12 col-md-6 col-lg-3">
        <dashboard-stat-card 
          :title="$t('Totale Prodotti')" 
          :value="stats.total" 
          icon="package" 
          color="#2563eb"
          :loading="loading"
        />
      </div>
      
      <!-- Valore Magazzino -->
      <div class="col-12 col-md-6 col-lg-3">
        <dashboard-stat-card 
          :title="$t('Valore Magazzino')" 
          :value="formatPrice(stats.value)" 
          icon="dollar-sign" 
          color="#22c55e"
          :loading="loading"
        />
      </div>
      
      <!-- Prodotti in Esaurimento -->
      <div class="col-12 col-md-6 col-lg-3">
        <dashboard-stat-card 
          :title="$t('In Esaurimento')" 
          :value="stats.lowStock" 
          icon="alert-triangle" 
          color="#facc15"
          :loading="loading"
        />
      </div>
      
      <!-- Prodotti Esauriti -->
      <div class="col-12 col-md-6 col-lg-3">
        <dashboard-stat-card 
          :title="$t('Esauriti')" 
          :value="stats.outOfStock" 
          icon="x-circle" 
          color="#b91c1c"
          :loading="loading"
        />
      </div>
    </div>
  </template>
  
  <script>
  import DashboardStatCard from '../components/DashboardStatCard.vue';
  
  export default {
    name: 'StatsProdotti',
    components: {
      DashboardStatCard
    },
    data() {
      return {
        loading: true,
        stats: {
          total: 0,
          value: 0,
          lowStock: 0,
          outOfStock: 0
        }
      };
    },
    created() {
      this.fetchStats();
    },
    methods: {
      /**
       * Recupera le statistiche dei prodotti
       */
      fetchStats() {
        this.loading = true;
        
        // Promesse per le diverse statistiche
        const promises = [
          this.$erpApi.get('/prodotti/count'),
          this.$erpApi.get('/prodotti/valore'),
          this.$erpApi.get('/prodotti/esaurimento'),
          this.$erpApi.get('/prodotti/esauriti')
        ];
        
        Promise.all(promises)
          .then(([totalRes, valueRes, lowStockRes, outOfStockRes]) => {
            this.stats.total = totalRes.data.count;
            this.stats.value = valueRes.data.value;
            this.stats.lowStock = lowStockRes.data.count;
            this.stats.outOfStock = outOfStockRes.data.count;
          })
          .catch(error => {
            console.error('Errore nel caricamento delle statistiche:', error);
            this.$q.notify({
              color: 'negative',
              position: 'top',
              message: this.$t('Errore nel caricamento delle statistiche'),
              icon: 'report_problem'
            });
          })
          .finally(() => {
            this.loading = false;
          });
      },
      
      /**
       * Formatta un prezzo con la valuta
       */
      formatPrice(price) {
        return new Intl.NumberFormat('it-IT', {
          style: 'currency',
          currency: 'EUR'
        }).format(price || 0);
      }
    }
  };
  </script>
