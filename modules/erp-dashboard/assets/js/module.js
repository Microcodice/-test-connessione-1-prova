/**
 * ERP Core - Script del modulo dashboard
 * Questo file gestisce l'inizializzazione del modulo dashboard e le sue interazioni specifiche
 */

// Utilizziamo una IIFE (Immediately Invoked Function Expression) per isolare il codice
(function() {
    // Verifica se il modulo è già stato caricato
    if (typeof window.erpDashboardModuleLoaded !== 'undefined') {
        return; // Evita doppio caricamento
    }
    
    // Segna il modulo come caricato
    window.erpDashboardModuleLoaded = true;
    
    // Segnala che il modulo è stato inizializzato
    console.log('ERP Dashboard: Modulo inizializzato');
    
    // Quando il DOM è completamente caricato
    document.addEventListener('DOMContentLoaded', function() {
        // Definisci l'API del modulo dashboard
        const dashboardApi = {
            /**
             * Carica i dati della dashboard
             * @returns {Promise} Promise con i dati della dashboard
             */
            loadDashboardData: async function() {
                try {
                    // In una vera implementazione, chiamata alle API del server
                    return {
                        stats: {
                            orders: 126,
                            products: 1254,
                            clients: 857,
                            monthlySales: '€24.530'
                        },
                        recentOrders: [
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
                        ],
                        inventoryAlerts: [
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
                        ]
                    };
                } catch (error) {
                    console.error('Errore durante il caricamento dei dati:', error);
                    throw error;
                }
            }
        };
        
        // Registra l'API nel namespace globale
        if (typeof window.erpModules === 'undefined') {
            window.erpModules = {};
        }
        window.erpModules.dashboard = dashboardApi;
        
        // Emetti un evento che segnala che il modulo dashboard è pronto
        const event = new CustomEvent('erp:dashboard-module-ready', { 
            detail: { api: dashboardApi } 
        });
        document.dispatchEvent(event);
        
        console.log('ERP Dashboard: API registrate');
    });
    
    // Definisci il componente Vue per la dashboard
    window.ERPDashboardView = {
        template: `
            <div class="dashboard-container">
                <!-- Statistiche principali -->
                <div class="erp-stats-grid">
                    <div class="erp-stat-card">
                        <div class="erp-stat-icon blue">
                            <i class="material-icons">shopping_cart</i>
                        </div>
                        <div class="erp-stat-info">
                            <div class="erp-stat-value">{{ stats.orders }}</div>
                            <div class="erp-stat-label">Ordini Recenti</div>
                        </div>
                    </div>
                    
                    <div class="erp-stat-card">
                        <div class="erp-stat-icon purple">
                            <i class="material-icons">inventory_2</i>
                        </div>
                        <div class="erp-stat-info">
                            <div class="erp-stat-value">{{ stats.products }}</div>
                            <div class="erp-stat-label">Prodotti</div>
                        </div>
                    </div>
                    
                    <div class="erp-stat-card">
                        <div class="erp-stat-icon green">
                            <i class="material-icons">people</i>
                        </div>
                        <div class="erp-stat-info">
                            <div class="erp-stat-value">{{ stats.clients }}</div>
                            <div class="erp-stat-label">Clienti</div>
                        </div>
                    </div>
                    
                    <div class="erp-stat-card">
                        <div class="erp-stat-icon orange">
                            <i class="material-icons">payments</i>
                        </div>
                        <div class="erp-stat-info">
                            <div class="erp-stat-value">{{ stats.monthlySales }}</div>
                            <div class="erp-stat-label">Vendite Mensili</div>
                        </div>
                    </div>
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
                            <div v-else class="loading-container">
                                <div class="spinner"></div>
                                <p>Caricamento ordini recenti...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sezione Avvisi Magazzino -->
                    <div class="erp-card">
                        <div class="erp-card-header">
                            Avvisi Magazzino
                        </div>
                        <div class="erp-card-content">
                            <div class="erp-alert-list" v-if="!loading.alerts">
                                <div class="erp-alert-item" v-for="alert in inventoryAlerts" :key="alert.id">
                                    <div class="erp-alert-icon">
                                        <i class="material-icons">warning_amber</i>
                                    </div>
                                    <div class="erp-alert-content">
                                        <div class="erp-alert-title">{{ alert.titolo }}</div>
                                        <div class="erp-alert-desc">{{ alert.descrizione }}</div>
                                    </div>
                                </div>
                                <!-- Messaggio quando non ci sono avvisi -->
                                <div v-if="inventoryAlerts.length === 0" class="empty-alerts">
                                    <i class="material-icons">check_circle</i>
                                    <p>Nessun avviso di magazzino presente</p>
                                </div>
                            </div>
                            <div v-else class="loading-container">
                                <div class="spinner"></div>
                                <p>Caricamento avvisi magazzino...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `,
        data() {
            return {
                loading: {
                    orders: true,
                    alerts: true
                },
                stats: {
                    orders: 0,
                    products: 0,
                    clients: 0,
                    monthlySales: '€0'
                },
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
                    // Se disponibile, usa l'API del modulo
                    if (window.erpModules && window.erpModules.dashboard) {
                        const data = await window.erpModules.dashboard.loadDashboardData();
                        this.stats = data.stats;
                        this.recentOrders = data.recentOrders;
                        this.inventoryAlerts = data.inventoryAlerts;
                    } else {
                        // Dati di fallback
                        setTimeout(() => {
                            this.stats = {
                                orders: 126,
                                products: 1254,
                                clients: 857,
                                monthlySales: '€24.530'
                            };
                            
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
                                }
                            ];
                            
                            this.inventoryAlerts = [
                                {
                                    id: 1,
                                    titolo: 'Mouse Wireless MX500',
                                    descrizione: 'Stock basso (3 rimasti)',
                                    tipo: 'warning'
                                }
                            ];
                        }, 500);
                    }
                    
                    // Aggiorna lo stato di caricamento
                    this.loading.orders = false;
                    this.loading.alerts = false;
                } catch (error) {
                    console.error('Errore durante il caricamento dei dati:', error);
                    this.loading.orders = false;
                    this.loading.alerts = false;
                }
            }
        },
        mounted() {
            this.loadDashboardData();
        }
    };
    
    // Emetti un evento che segnala che il componente dashboard è pronto
    document.dispatchEvent(new CustomEvent('erp:dashboard-view-ready'));
})();