<?php
/**
 * Template per la pagina amministrativa dell'ERP
 * Questo file viene incluso dal metodo render_admin_page
 */

// Assicurati che questo file non sia accessibile direttamente
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="erp-app" class="erp-app-container">
    <!-- Template base Vue -->
    <div v-cloak>
        <!-- Sidebar -->
        <div class="erp-sidebar">
            <div class="erp-sidebar-header">
                <div class="erp-sidebar-logo">
                    <i class="material-icons">grid_view</i> ERP System
                </div>
            </div>
            
            <div class="erp-sidebar-user">
                <div class="erp-sidebar-avatar">
                    <i class="material-icons">person</i>
                </div>
                <div class="erp-sidebar-userinfo">
                    <div class="erp-sidebar-username">{{ user.name }}</div>
                    <div class="erp-sidebar-role">{{ user.role }}</div>
                </div>
            </div>
            
            <div class="erp-nav">
                <div 
                    v-for="item in menu" 
                    :key="item.id"
                    class="erp-nav-item"
                    :class="{ 'active': item.active }"
                    @click="setActivePage(item.id)"
                >
                    <div class="erp-nav-item-icon">
                        <i class="material-icons">{{ item.icon }}</i>
                    </div>
                    <div class="erp-nav-item-text">
                        {{ item.label }}
                    </div>
                </div>
            </div>
            
            <div class="erp-sidebar-footer">
                <div class="erp-sidebar-footer-icon" @click="toggleTheme">
                    <i class="material-icons">dark_mode</i>
                </div>
                <div class="erp-sidebar-footer-icon" @click="exitErp">
                    <i class="material-icons">logout</i>
                </div>
            </div>
        </div>
        
        <!-- Contenuto principale -->
        <div class="erp-content">
            <!-- Topbar -->
            <div class="erp-topbar">
                <div class="erp-topbar-title">{{ currentPage }}</div>
                
                <div class="erp-search-box">
                    <i class="material-icons">search</i>
                    <input type="text" placeholder="Cerca..." />
                </div>
                
                <div class="erp-topbar-actions">
                    <div class="erp-topbar-action">
                        <i class="material-icons">notifications</i>
                        <span class="erp-notification-badge">1</span>
                    </div>
                </div>
            </div>
            
            <!-- Area principale -->
            <div class="erp-main">
                <!-- Dashboard (default) -->
                <div v-if="currentPage === 'Dashboard'">
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
                                <div class="erp-stat-value">{{ stats.sales }}</div>
                                <div class="erp-stat-label">Vendite Mensili</div>
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
                                <table class="erp-table">
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
                                            <td>{{ order.client }}</td>
                                            <td>{{ formatDate(order.date) }}</td>
                                            <td>
                                                <span class="erp-status" :class="'erp-status-' + order.status">
                                                    {{ order.statusLabel }}
                                                </span>
                                            </td>
                                            <td>{{ order.total }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Avvisi magazzino -->
                        <div class="erp-card">
                            <div class="erp-card-header">
                                Avvisi Magazzino
                            </div>
                            <div class="erp-card-content">
                                <div class="erp-alert-list">
                                    <div class="erp-alert-item" v-for="alert in inventoryAlerts" :key="alert.id">
                                        <div class="erp-alert-icon">
                                            <i class="material-icons">warning_amber</i>
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
                
                <!-- Pagina placeholder per altri moduli -->
                <div v-else>
                    <div class="erp-card">
                        <div class="erp-card-header">
                            {{ currentPage }}
                        </div>
                        <div class="erp-card-content" style="text-align: center; padding: 50px 20px;">
                            <i class="material-icons" style="font-size: 48px; margin-bottom: 20px; color: #3f8cff;">construction</i>
                            <h2>Modulo in costruzione</h2>
                            <p>Questa sezione è in fase di sviluppo.</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- Debug info -->
<script>
console.log('ERP Admin Template loaded');
</script>