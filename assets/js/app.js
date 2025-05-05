/**
 * ERP Core - Script principale dell'applicazione (versione aggiornata)
 * Questo file contiene l'applicazione Vue principale secondo il mockup fornito
 */

// Assicurati che erpCoreData sia disponibile
if (typeof erpCoreData === 'undefined') {
    console.error('ERP Core: Dati di configurazione non trovati!');
}

// Crea l'app Vue quando il DOM è caricato
document.addEventListener('DOMContentLoaded', function() {
    // Ottieni il container dell'app
    const appContainer = document.getElementById('erp-app');
    if (!appContainer) {
        console.error('ERP Core: Container app non trovato!');
        return;
    }

    // Importazioni Vue
    const { createApp, ref, reactive, computed, onMounted, watch } = Vue;
    const { createRouter, createWebHashHistory } = VueRouter;

    // Funzione per verificare se il dispositivo è supportato
    function checkDeviceSupport() {
        const isPortrait = window.innerHeight > window.innerWidth;
        const isMobile = window.innerWidth < 768;
        return !isPortrait || !isMobile;
    }

    // Crea una variabile reattiva per il supporto del dispositivo
    const isDeviceSupported = ref(checkDeviceSupport());

    // Funzione di utility per mappare i nomi delle icone Material Icons a Lucide
    function getMappedLucideIcon(materialIcon) {
        const iconMap = {
            'dashboard': 'layout-dashboard',
            'grid_view': 'layout-grid',
            'shopping_cart': 'shopping-cart',
            'inventory_2': 'package',
            'receipt': 'file-text',
            'people': 'users',
            'local_shipping': 'truck',
            'inventory': 'clipboard-list',
            'account_balance': 'landmark',
            'shopping_bag': 'shopping-bag',
            'bar_chart': 'bar-chart',
            'settings': 'settings',
            'dark_mode': 'moon',
            'logout': 'log-out',
            'search': 'search',
            'notifications': 'bell',
            'devices': 'smartphone',
            'warning_amber': 'alert-triangle',
            'payments': 'credit-card',
            'person': 'user',
            'inbox': 'inbox'
        };
        
        return iconMap[materialIcon] || 'help-circle'; // Fallback a un'icona di default
    }

    // Configura Axios per le richieste API
    const api = axios.create({
        baseURL: erpCoreData.apiUrl,
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': erpCoreData.nonce
        }
    });

    // Store per lo stato globale dell'applicazione
    const store = {
        state: reactive({
            user: erpCoreData.currentUser || {},
            loading: false,
            notifications: [],
            dashboardData: {
                stats: {
                    orders: { value: 126, label: 'Ordini Recenti' },
                    products: { value: 1254, label: 'Prodotti' },
                    clients: { value: 857, label: 'Clienti' },
                    sales: { value: '€24.530', label: 'Vendite Mensili' }
                },
                recentOrders: [],
                inventoryAlerts: [
                    { 
                        id: 1, 
                        title: 'Mouse Wireless MX500', 
                        description: 'Stock basso (3 rimasti)', 
                        type: 'warning' 
                    },
                    { 
                        id: 2, 
                        title: 'Tastiera Meccanica K95', 
                        description: 'Esaurito - Ordine in elaborazione', 
                        type: 'danger' 
                    },
                    { 
                        id: 3, 
                        title: 'Monitor HD 27"', 
                        description: 'Stock basso (2 rimasti)', 
                        type: 'warning' 
                    }
                ]
            }
        }),
        
        // Actions
        setLoading(value) {
            store.state.loading = value;
        },
        async loadDashboardData() {
            store.setLoading(true);
            try {
                // In una vera implementazione, qui dovresti chiamare le API
                // Per ora, usiamo dati fittizi
                setTimeout(() => {
                    store.state.dashboardData.recentOrders = [
                        {
                            id: 'ORD-12345',
                            client: 'Mario Rossi',
                            date: '2025-05-01',
                            status: 'completed',
                            total: '€175,00'
                        },
                        {
                            id: 'ORD-12346',
                            client: 'Giulia Verdi',
                            date: '2025-05-02',
                            status: 'processing',
                            total: '€320,50'
                        },
                        {
                            id: 'ORD-12347',
                            client: 'Paolo Neri',
                            date: '2025-05-03',
                            status: 'pending',
                            total: '€89,90'
                        },
                        {
                            id: 'ORD-12348',
                            client: 'Lucia Bianchi',
                            date: '2025-05-03',
                            status: 'cancelled',
                            total: '€245,00'
                        }
                    ];
                    store.setLoading(false);
                }, 500);
            } catch (error) {
                console.error('Errore nel caricamento dei dati dashboard:', error);
                store.setLoading(false);
            }
        }
    };

    // Componente Dashboard (seguendo il mockup)
    const DashboardView = {
        template: `
            <div>
                <!-- Statistiche -->
                <div class="erp-stats-grid">
                    <div class="erp-stat-card">
                        <div class="erp-stat-icon blue">
                            <i data-lucide="shopping-cart"></i>
                        </div>
                        <div class="erp-stat-info">
                            <div class="erp-stat-value">{{ dashboardData.stats.orders.value }}</div>
                            <div class="erp-stat-label">{{ dashboardData.stats.orders.label }}</div>
                        </div>
                    </div>
                    
                    <div class="erp-stat-card">
                        <div class="erp-stat-icon purple">
                            <i data-lucide="package"></i>
                        </div>
                        <div class="erp-stat-info">
                            <div class="erp-stat-value">{{ dashboardData.stats.products.value }}</div>
                            <div class="erp-stat-label">{{ dashboardData.stats.products.label }}</div>
                        </div>
                    </div>
                    
                    <div class="erp-stat-card">
                        <div class="erp-stat-icon green">
                            <i data-lucide="users"></i>
                        </div>
                        <div class="erp-stat-info">
                            <div class="erp-stat-value">{{ dashboardData.stats.clients.value }}</div>
                            <div class="erp-stat-label">{{ dashboardData.stats.clients.label }}</div>
                        </div>
                    </div>
                    
                    <div class="erp-stat-card">
                        <div class="erp-stat-icon orange">
                            <i data-lucide="credit-card"></i>
                        </div>
                        <div class="erp-stat-info">
                            <div class="erp-stat-value">{{ dashboardData.stats.sales.value }}</div>
                            <div class="erp-stat-label">{{ dashboardData.stats.sales.label }}</div>
                        </div>
                    </div>
                </div>
                
                <!-- Layout principale dashboard -->
                <div class="erp-dashboard-layout">
                    <!-- Ordini recenti -->
                    <div class="erp-card">
                        <div class="erp-card-header">
                            Ordini Recenti
                        </div>
                        <div class="erp-card-content">
                            <table class="erp-table" v-if="!loading && dashboardData.recentOrders.length">
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
                                    <tr v-for="order in dashboardData.recentOrders" :key="order.id">
                                        <td>{{ order.id }}</td>
                                        <td>{{ order.client }}</td>
                                        <td>{{ formatDate(order.date) }}</td>
                                        <td>
                                            <span class="erp-status" :class="'erp-status-' + order.status">
                                                {{ translateStatus(order.status) }}
                                            </span>
                                        </td>
                                        <td>{{ order.total }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <div v-else-if="loading" class="erp-loading-indicator">
                                <div class="spinner"></div>
                                <div>Caricamento in corso...</div>
                            </div>
                            
                            <div v-else class="erp-empty-state">
                                <i data-lucide="inbox"></i>
                                <div>Nessun ordine recente</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Avvisi magazzino -->
                    <div class="erp-card">
                        <div class="erp-card-header">
                            Avvisi Magazzino
                        </div>
                        <div class="erp-card-content">
                            <div class="erp-alert-list">
                                <div class="erp-alert-item" v-for="alert in dashboardData.inventoryAlerts" :key="alert.id">
                                    <div class="erp-alert-icon">
                                        <i data-lucide="alert-triangle"></i>
                                    </div>
                                    <div class="erp-alert-content">
                                        <div class="erp-alert-title">{{ alert.title }}</div>
                                        <div class="erp-alert-desc">{{ alert.description }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `,
        setup() {
            const loading = ref(true);
            const dashboardData = computed(() => store.state.dashboardData);
            
            // Metodi
            const formatDate = (dateString) => {
                const date = new Date(dateString);
                return date.toLocaleDateString('it-IT');
            };
            
            const translateStatus = (status) => {
                const translations = {
                    'completed': 'Completato',
                    'processing': 'In elaborazione',
                    'pending': 'In attesa',
                    'cancelled': 'Annullato'
                };
                
                return translations[status] || status;
            };
            
            // Lifecycle hooks
            onMounted(() => {
                // Carica i dati dashboard
                store.loadDashboardData();
                
                // Reinizializza le icone Lucide
                if (typeof lucide !== 'undefined') {
                    setTimeout(() => {
                        lucide.createIcons();
                    }, 0);
                }
            });
            
            return {
                loading,
                dashboardData,
                formatDate,
                translateStatus
            };
        }
    };

    // Placeholder per le altre viste
    const ProductsView = { template: '<div>Prodotti</div>' };
    const OrdersView = { template: '<div>Ordini</div>' };
    const InvoicesView = { template: '<div>Fatture</div>' };
    const ClientsView = { template: '<div>Clienti</div>' };
    const SuppliersView = { template: '<div>Fornitori</div>' };
    const InventoryView = { template: '<div>Magazzino</div>' };
    const AccountingView = { template: '<div>Contabilità</div>' };
    const WooCommerceView = { template: '<div>WooCommerce</div>' };
    const ReportsView = { template: '<div>Report</div>' };
    const SystemView = { template: '<div>Sistema</div>' };
    const NotFoundView = { template: '<div>Pagina non trovata</div>' };

    // Crea il router Vue con le rotte base DOPO aver definito i componenti delle viste
    const router = createRouter({
        history: createWebHashHistory(),
        routes: [
            {
                path: '/',
                redirect: '/dashboard'
            },
            {
                path: '/dashboard',
                component: DashboardView,
                meta: {
                    title: 'Dashboard',
                    icon: 'dashboard',
                    permission: 'erp_dashboard_view'
                }
            },
            {
                path: '/prodotti',
                component: ProductsView,
                meta: {
                    title: 'Prodotti',
                    icon: 'inventory_2',
                    permission: 'erp_products_view'
                }
            },
            {
                path: '/ordini',
                component: OrdersView,
                meta: {
                    title: 'Ordini',
                    icon: 'shopping_cart',
                    permission: 'erp_documents_view'
                }
            },
            {
                path: '/fatture',
                component: InvoicesView,
                meta: {
                    title: 'Fatture',
                    icon: 'receipt',
                    permission: 'erp_documents_view'
                }
            },
            {
                path: '/clienti',
                component: ClientsView,
                meta: {
                    title: 'Clienti',
                    icon: 'people',
                    permission: 'erp_clients_view'
                }
            },
            {
                path: '/fornitori',
                component: SuppliersView,
                meta: {
                    title: 'Fornitori',
                    icon: 'local_shipping',
                    permission: 'erp_suppliers_view'
                }
            },
            {
                path: '/magazzino',
                component: InventoryView,
                meta: {
                    title: 'Magazzino',
                    icon: 'inventory',
                    permission: 'erp_inventory_view'
                }
            },
            {
                path: '/contabilita',
                component: AccountingView,
                meta: {
                    title: 'Contabilità',
                    icon: 'account_balance',
                    permission: 'erp_accounting_view'
                }
            },
            {
                path: '/woocommerce',
                component: WooCommerceView,
                meta: {
                    title: 'WooCommerce',
                    icon: 'shopping_bag',
                    permission: 'erp_woocommerce_view'
                }
            },
            {
                path: '/report',
                component: ReportsView,
                meta: {
                    title: 'Report',
                    icon: 'bar_chart',
                    permission: 'erp_reports_view'
                }
            },
            {
                path: '/sistema',
                component: SystemView,
                meta: {
                    title: 'Sistema',
                    icon: 'settings',
                    permission: 'erp_system_view'
                }
            },
            {
                path: '/:pathMatch(.*)*',
                component: NotFoundView,
                meta: {
                    title: 'Pagina non trovata'
                }
            }
        ]
    });

    // Reinizializza le icone Lucide dopo ogni cambio di rotta
    router.afterEach(() => {
        if (typeof lucide !== 'undefined') {
            setTimeout(() => {
                lucide.createIcons();
            }, 0);
        }
    });

    // Aggiungi le rotte dei moduli
    if (erpCoreData.routes && Array.isArray(erpCoreData.routes)) {
        erpCoreData.routes.forEach(route => {
            router.addRoute(route);
        });
    }

    // Guardia per il router che verifica i permessi
    router.beforeEach((to, from, next) => {
        // Imposta il titolo della pagina
        document.title = to.meta.title ? `${to.meta.title} - ERP Core` : 'ERP Core';
        
        // Verifica i permessi
        if (to.meta.permission && !hasPermission(to.meta.permission)) {
            // Reindirizza alla dashboard o mostra un errore
            next({ path: '/access-denied' });
            showToast('Non hai i permessi per accedere a questa pagina', 'error');
        } else {
            next();
        }
    });

    // Componente principale dell'app
    const App = {
        template: `
            <div v-if="isDeviceSupported" class="erp-app-container">
                <!-- Sidebar -->
                <div class="erp-sidebar">
					<div class="erp-sidebar-fixed">
						<div class="erp-sidebar-header">
							<div class="erp-sidebar-logo">
								<i data-lucide="layout-grid"></i> ERP System
							</div>
						</div>
						
						<div class="erp-sidebar-user">
							<div class="erp-sidebar-avatar">
								<i data-lucide="user"></i>
							</div>
							<div class="erp-sidebar-userinfo">
								<div class="erp-sidebar-username">Admin User</div>
								<div class="erp-sidebar-role">Administrator</div>
							</div>
						</div>
					</div>
                    
                    <div class="erp-sidebar-scrollable">
						<div class="erp-nav">
							<div 
								v-for="route in filteredRoutes" 
								:key="route.path"
								class="erp-nav-item"
								:class="{ 'active': $route.path === route.path }"
								@click="navigateTo(route.path)"
							>
								<div class="erp-nav-item-icon">
									<i :data-lucide="getMappedLucideIcon(route.meta.icon)"></i>
								</div>
								<div class="erp-nav-item-text">
									{{ route.meta.title }}
								</div>
							</div>
						</div>
					</div>
                    
                    <div class="erp-sidebar-footer">
                        <div class="erp-sidebar-footer-icon" @click="toggleTheme">
                            <i data-lucide="moon"></i>
                        </div>
                        <div class="erp-sidebar-footer-icon" @click="exitErp">
                            <i data-lucide="log-out"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Contenuto principale -->
                <div class="erp-content">
                    <!-- Topbar -->
                    <div class="erp-topbar">
                        <div class="erp-topbar-title">{{ currentPageTitle }}</div>
                        
                        <div class="erp-search-box">
                            <i data-lucide="search"></i>
                            <input type="text" placeholder="Cerca..." />
                        </div>
                        
<div class="erp-topbar-actions">
    <div class="erp-topbar-clock" id="erp-clock"></div>
    <div class="erp-topbar-action">
        <i data-lucide="bell"></i>
        <span class="erp-notification-badge">1</span>
    </div>
</div>
                    </div>
                    
                    <!-- Area principale con router-view -->
                    <div class="erp-main">
                        <router-view></router-view>
                    </div>
                </div>
            </div>
            
            <!-- Vista per dispositivi non supportati -->
            <div v-else class="erp-unsupported-view">
                <div class="erp-unsupported-icon">
                    <i data-lucide="smartphone"></i>
                </div>
                <div class="erp-unsupported-title">
                    Dispositivo non supportato
                </div>
                <div class="erp-unsupported-text">
                    ERP Core richiede un dispositivo con schermo più grande o in modalità orizzontale.
                    Per favore, ruota il tuo dispositivo o accedi da un computer desktop.
                </div>
            </div>
        `,
		setup() {
			// Variabile reattiva per l'orologio
			const currentTime = ref(new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
			
			// Non creiamo un nuovo ref qui, usiamo quello globale
			// Computed properties
			const currentPageTitle = computed(() => {
				return router.currentRoute.value.meta.title || 'ERP Core';
			});
            
const filteredRoutes = computed(() => {
    // Ottieni tutte le rotte tranne quelle di sistema (404, ecc.)
    const routes = router.getRoutes()
        .filter(route => 
            route.meta.title && 
            route.meta.icon && 
            route.path !== '/:pathMatch(.*)*' &&
            route.path !== '/'
        )
        .filter(route => {
            // Filtra per permesso
            if (route.meta.permission) {
                return hasPermission(route.meta.permission);
            }
            return true;
        });
        
    // Elimina i duplicati basati sul path
    const uniqueRoutes = [];
    const paths = new Set();
    
    for (const route of routes) {
        if (!paths.has(route.path)) {
            paths.add(route.path);
            uniqueRoutes.push(route);
        }
    }
    
    return uniqueRoutes;
});
            
            // Metodi
            const navigateTo = (path) => {
                router.push(path);
            };
            
            const exitErp = () => {
                window.location.href = erpCoreData.adminUrl;
            };
            
const toggleTheme = () => {
    const currentTheme = localStorage.getItem('erp_theme') || 'dark';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    // Debug per verificare che la funzione venga chiamata
    console.log('Toggle theme:', currentTheme, '->', newTheme);
    
    // Trova tutti i link al foglio di stile che contengono "theme-"
    const themeLinks = document.querySelectorAll('link[href*="theme-"]');
    console.log('Theme links found:', themeLinks.length);
    
    themeLinks.forEach(link => {
        const oldHref = link.getAttribute('href');
        const newHref = oldHref.replace(`theme-${currentTheme}.css`, `theme-${newTheme}.css`);
        
        // Debug
        console.log('Changing:', oldHref, '->', newHref);
        
        // Cambia l'href
        link.setAttribute('href', newHref);
    });
    
    // Aggiorna il localStorage
    localStorage.setItem('erp_theme', newTheme);
    
    // Se tutto fallisce, ricarica la pagina
    if (themeLinks.length === 0) {
        console.log('No theme links found, reloading page');
        location.reload();
    }
};
            
            // Lifecycle hooks
            onMounted(() => {
                // Inizializza il tema
                const savedTheme = localStorage.getItem('erp_theme') || 'dark';
                if (savedTheme === 'dark') {
                    document.body.classList.add('dark-theme');
                }
                
                // Controlla il dispositivo
                window.addEventListener('resize', () => {
                    isDeviceSupported.value = checkDeviceSupport();
                });
                
                // Inizializza le icone Lucide
                if (typeof lucide !== 'undefined') {
                    setTimeout(() => {
                        lucide.createIcons();
                    }, 0);
                }
				
				// Aggiorna l'ora ogni minuto
				const timer = setInterval(() => {
					currentTime.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
				}, 60000); // 60000 ms = 1 minuto				
				
				
				// Richiedi la modalità a schermo intero dopo un breve ritardo
				setTimeout(() => {
					const element = document.documentElement; // Elemento HTML intero
					
					if (element.requestFullscreen) {
						element.requestFullscreen();
					} else if (element.mozRequestFullScreen) { // Firefox
						element.mozRequestFullScreen();
					} else if (element.webkitRequestFullscreen) { // Chrome, Safari, Opera
						element.webkitRequestFullscreen();
					} else if (element.msRequestFullscreen) { // IE/Edge
						element.msRequestFullscreen();
					}
				}, 1000); // Ritardo di 1 secondo per assicurarsi che tutto sia caricato

            });
            
            return {
                isDeviceSupported, // Usa la variabile reattiva definita a livello globale
                currentPageTitle,
                filteredRoutes,
                navigateTo,
                exitErp,
                toggleTheme,
                getMappedLucideIcon // Aggiungi questa funzione
            };
        }
    };

    // Funzione per verificare se l'utente ha un permesso
    function hasPermission(permission) {
        // In una vera implementazione, verifica i permessi effettivi
        // Per ora, consideriamo l'utente come amministratore
        return true;
    }

    // Crea e monta l'app Vue
    const app = createApp(App);
    
    // Registra i componenti
    app.component('dashboard-view', DashboardView);
    app.component('products-view', ProductsView);
    app.component('orders-view', OrdersView);
    app.component('invoices-view', InvoicesView);
    app.component('clients-view', ClientsView);
    app.component('suppliers-view', SuppliersView);
    app.component('inventory-view', InventoryView);
    app.component('accounting-view', AccountingView);
    app.component('woocommerce-view', WooCommerceView);
    app.component('reports-view', ReportsView);
    app.component('system-view', SystemView);
    app.component('not-found-view', NotFoundView);
    
    // Usa i plugin
    app.use(router);
    
    // Fornisci lo store come proprietà globale
    app.config.globalProperties.$store = store;
    
    // Monta l'app
    app.mount('#erp-app');
});