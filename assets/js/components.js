 /**
 * ERP Core - Componenti Vue condivisi
 * Questo file contiene i componenti Vue riutilizzabili in tutta l'applicazione
 */

// Componente StatCard per visualizzare statistiche nella dashboard
const ERPStatCard = {
    props: {
        title: {
            type: String,
            required: true
        },
        value: {
            type: [String, Number],
            default: '0'
        },
        icon: {
            type: String,
            default: 'insights'
        },
        color: {
            type: String,
            default: null
        },
        footer: {
            type: String,
            default: null
        },
        loading: {
            type: Boolean,
            default: false
        }
    },
    template: `
        <div class="erp-stat-card" :class="{ 'erp-color-blue': color === 'blue', 'erp-color-green': color === 'green', 'erp-color-yellow': color === 'yellow', 'erp-color-red': color === 'red' }">
            <div class="erp-stat-card-header">
                <div class="erp-stat-card-icon">
                    <i class="material-icons">{{ icon }}</i>
                </div>
                <div class="erp-stat-card-title">
                    {{ title }}
                </div>
            </div>
            <div class="erp-stat-card-value">
                <div v-if="loading" class="q-spinner-dots" style="width: 24px; height: 24px;"></div>
                <template v-else>{{ value }}</template>
            </div>
            <div v-if="footer" class="erp-stat-card-footer">
                {{ footer }}
            </div>
        </div>
    `
};

// Componente ERPTable per visualizzare tabelle di dati
const ERPTable = {
    props: {
        columns: {
            type: Array,
            required: true
        },
        data: {
            type: Array,
            default: () => []
        },
        loading: {
            type: Boolean,
            default: false
        },
        pagination: {
            type: Object,
            default: () => ({
                page: 1,
                rowsPerPage: 10,
                totalItems: 0
            })
        },
        actions: {
            type: Array,
            default: () => []
        }
    },
    emits: ['page-change', 'action-click'],
    template: `
        <div class="erp-table-container">
            <div v-if="loading" class="erp-loading" style="padding: 40px; text-align: center;">
                <div class="q-spinner-dots" style="width: 40px; height: 40px; margin: 0 auto;"></div>
                <div style="margin-top: 16px;">Caricamento in corso...</div>
            </div>
            <table v-else class="erp-table">
                <thead>
                    <tr>
                        <th v-for="col in columns" :key="col.field">{{ col.label }}</th>
                        <th v-if="actions.length" style="width: 100px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, rowIndex) in data" :key="rowIndex">
                        <td v-for="col in columns" :key="col.field">
                            <template v-if="col.formatter">
                                {{ col.formatter(row[col.field], row) }}
                            </template>
                            <template v-else>
                                {{ row[col.field] }}
                            </template>
                        </td>
                        <td v-if="actions.length" class="erp-table-actions">
                            <div 
                                v-for="action in actions" 
                                :key="action.name"
                                class="erp-table-action"
                                @click="$emit('action-click', { action: action.name, row })"
                                :title="action.label"
                            >
                                <i class="material-icons">{{ action.icon }}</i>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="data.length === 0">
                        <td :colspan="columns.length + (actions.length > 0 ? 1 : 0)" style="text-align: center; padding: 20px;">
                            Nessun dato trovato
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="pagination && pagination.totalItems > 0" class="erp-pagination">
                <div class="erp-pagination-info">
                    Visualizzazione {{ (pagination.page - 1) * pagination.rowsPerPage + 1 }} - 
                    {{ Math.min(pagination.page * pagination.rowsPerPage, pagination.totalItems) }} 
                    di {{ pagination.totalItems }} elementi
                </div>
                <div class="erp-pagination-controls">
                    <button 
                        class="erp-btn erp-btn-secondary" 
                        :disabled="pagination.page === 1"
                        @click="$emit('page-change', pagination.page - 1)"
                    >
                        <i class="material-icons">navigate_before</i>
                    </button>
                    <span class="erp-pagination-page">{{ pagination.page }}</span>
                    <button 
                        class="erp-btn erp-btn-secondary" 
                        :disabled="pagination.page * pagination.rowsPerPage >= pagination.totalItems"
                        @click="$emit('page-change', pagination.page + 1)"
                    >
                        <i class="material-icons">navigate_next</i>
                    </button>
                </div>
            </div>
        </div>
    `
};

// Componente ERPModal per visualizzare finestre modali
const ERPModal = {
    props: {
        show: {
            type: Boolean,
            default: false
        },
        title: {
            type: String,
            default: 'Modal'
        },
        size: {
            type: String,
            default: 'md', // sm, md, lg, xl
            validator: (val) => ['sm', 'md', 'lg', 'xl'].includes(val)
        },
        persistent: {
            type: Boolean,
            default: false
        }
    },
    emits: ['update:show', 'confirm', 'cancel'],
    template: `
        <div v-if="show" class="erp-modal-backdrop" @click="closeIfNotPersistent">
            <div 
                class="erp-modal" 
                :style="modalStyle" 
                @click.stop
            >
                <div class="erp-modal-header">
                    <div class="erp-modal-title">{{ title }}</div>
                    <div class="erp-modal-close" @click="close">
                        <i class="material-icons">close</i>
                    </div>
                </div>
                <div class="erp-modal-body">
                    <slot></slot>
                </div>
                <div class="erp-modal-footer">
                    <slot name="footer">
                        <button class="erp-btn erp-btn-secondary" @click="cancel">Annulla</button>
                        <button class="erp-btn erp-btn-primary" @click="confirm">Conferma</button>
                    </slot>
                </div>
            </div>
        </div>
    `,
    setup(props, { emit }) {
        const { computed } = Vue;
        
        // Calcola lo stile del modal in base alla dimensione
        const modalStyle = computed(() => {
            let width;
            
            switch (props.size) {
                case 'sm': width = '400px'; break;
                case 'lg': width = '800px'; break;
                case 'xl': width = '1100px'; break;
                case 'md':
                default: width = '600px';
            }
            
            return { 'max-width': width };
        });
        
        // Metodi
        const close = () => {
            emit('update:show', false);
        };
        
        const closeIfNotPersistent = () => {
            if (!props.persistent) {
                close();
            }
        };
        
        const confirm = () => {
            emit('confirm');
            close();
        };
        
        const cancel = () => {
            emit('cancel');
            close();
        };
        
        return {
            modalStyle,
            close,
            closeIfNotPersistent,
            confirm,
            cancel
        };
    }
};

// Componente ERPAlert per visualizzare messaggi di avviso
const ERPAlert = {
    props: {
        type: {
            type: String,
            default: 'info',
            validator: (val) => ['info', 'success', 'warning', 'error'].includes(val)
        },
        title: {
            type: String,
            default: null
        },
        message: {
            type: String,
            required: true
        },
        dismissible: {
            type: Boolean,
            default: true
        }
    },
    emits: ['close'],
    template: `
        <div class="erp-alert" :class="'erp-alert-' + type">
            <div class="erp-alert-icon">
                <i class="material-icons">{{ alertIcon }}</i>
            </div>
            <div class="erp-alert-content">
                <div v-if="title" class="erp-alert-title">{{ title }}</div>
                <div class="erp-alert-message">{{ message }}</div>
            </div>
            <div v-if="dismissible" class="erp-alert-close" @click="$emit('close')">
                <i class="material-icons">close</i>
            </div>
        </div>
    `,
    setup(props) {
        const { computed } = Vue;
        
        // Icona in base al tipo di alert
        const alertIcon = computed(() => {
            switch (props.type) {
                case 'success': return 'check_circle';
                case 'warning': return 'warning';
                case 'error': return 'error';
                case 'info':
                default: return 'info';
            }
        });
        
        return {
            alertIcon
        };
    }
};

// Componente ERPTabs per visualizzare schede
const ERPTabs = {
    props: {
        tabs: {
            type: Array,
            required: true
        },
        modelValue: {
            type: String,
            default: null
        }
    },
    emits: ['update:modelValue'],
    template: `
        <div class="erp-tabs">
            <div 
                v-for="tab in tabs" 
                :key="tab.id"
                class="erp-tab"
                :class="{ 'active': modelValue === tab.id }"
                @click="$emit('update:modelValue', tab.id)"
            >
                <div v-if="tab.icon" class="erp-tab-icon">
                    <i class="material-icons">{{ tab.icon }}</i>
                </div>
                <div class="erp-tab-label">{{ tab.label }}</div>
            </div>
        </div>
        
        <div class="erp-tab-content">
            <slot></slot>
        </div>
    `,
    setup(props, { emit }) {
        const { onMounted, watch } = Vue;
        
        // Se non è selezionata nessuna tab, seleziona la prima
        onMounted(() => {
            if (!props.modelValue && props.tabs.length > 0) {
                emit('update:modelValue', props.tabs[0].id);
            }
        });
        
        // Se le tab cambiano e quella selezionata non esiste più, seleziona la prima
        watch(() => props.tabs, (newTabs) => {
            if (newTabs.length > 0 && !newTabs.find(tab => tab.id === props.modelValue)) {
                emit('update:modelValue', newTabs[0].id);
            }
        }, { deep: true });
    }
};

// Componente ERPActionButton per visualizzare pulsanti di azione flottanti
const ERPActionButton = {
    props: {
        icon: {
            type: String,
            default: 'add'
        },
        color: {
            type: String,
            default: 'primary',
            validator: (val) => ['primary', 'secondary', 'danger', 'warning', 'success'].includes(val)
        },
        position: {
            type: String,
            default: 'bottom-right',
            validator: (val) => ['bottom-right', 'bottom-left', 'top-right', 'top-left'].includes(val)
        }
    },
    emits: ['click'],
    template: `
        <div 
            class="erp-action-button" 
            :class="['erp-btn-' + color, 'erp-action-button-' + position]"
            @click="$emit('click')"
        >
            <i class="material-icons">{{ icon }}</i>
        </div>
    `
};

// Componente per creare un menu dropdown
const ERPMenu = {
    props: {
        items: {
            type: Array,
            required: true
        },
        show: {
            type: Boolean,
            default: false
        },
        position: {
            type: String,
            default: 'bottom-right',
            validator: (val) => ['bottom-right', 'bottom-left', 'top-right', 'top-left'].includes(val)
        }
    },
    emits: ['update:show', 'item-click'],
    template: `
        <div class="erp-menu-container">
            <div v-if="show" class="erp-menu-backdrop" @click="close"></div>
            <div 
                v-if="show" 
                class="erp-menu" 
                :class="'erp-menu-' + position"
            >
                <div 
                    v-for="(item, index) in items" 
                    :key="index"
                    class="erp-menu-item"
                    :class="{ 'erp-menu-item-divider': item.divider, 'erp-menu-item-disabled': item.disabled }"
                    @click="handleItemClick(item)"
                >
                    <div v-if="item.icon" class="erp-menu-item-icon">
                        <i class="material-icons">{{ item.icon }}</i>
                    </div>
                    <div class="erp-menu-item-label">{{ item.label }}</div>
                </div>
            </div>
            <slot></slot>
        </div>
    `,
    setup(props, { emit }) {
        // Metodi
        const close = () => {
            emit('update:show', false);
        };
        
        const handleItemClick = (item) => {
            if (item.divider || item.disabled) return;
            
            emit('item-click', item);
            close();
        };
        
        return {
            close,
            handleItemClick
        };
    }
};

// Componente per visualizzare notifiche toast
const ERPNotify = {
    props: {
        message: {
            type: String,
            required: true
        },
        type: {
            type: String,
            default: 'info',
            validator: (val) => ['info', 'success', 'warning', 'error'].includes(val)
        },
        timeout: {
            type: Number,
            default: 3000
        },
        position: {
            type: String,
            default: 'top-right',
            validator: (val) => ['top-right', 'top-left', 'bottom-right', 'bottom-left', 'top', 'bottom'].includes(val)
        }
    },
    template: `
        <div 
            class="erp-toast" 
            :class="['erp-toast-' + type, 'erp-toast-' + position]"
            v-if="isVisible"
        >
            <div class="erp-toast-content">
                <div class="erp-toast-icon">
                    <i class="material-icons">{{ toastIcon }}</i>
                </div>
                <div class="erp-toast-message">{{ message }}</div>
                <div class="erp-toast-close" @click="dismiss">
                    <i class="material-icons">close</i>
                </div>
            </div>
        </div>
    `,
    setup(props) {
        const { ref, computed, onMounted } = Vue;
        
        // Stato
        const isVisible = ref(false);
        
        // Computed properties
        const toastIcon = computed(() => {
            switch (props.type) {
                case 'success': return 'check_circle';
                case 'warning': return 'warning';
                case 'error': return 'error';
                case 'info':
                default: return 'info';
            }
        });
        
        // Metodi
        const dismiss = () => {
            isVisible.value = false;
        };
        
        // Lifecycle hooks
        onMounted(() => {
            isVisible.value = true;
            
            if (props.timeout > 0) {
                setTimeout(() => {
                    dismiss();
                }, props.timeout);
            }
        });
        
        return {
            isVisible,
            toastIcon,
            dismiss
        };
    }
};

// Registra i componenti nell'app Vue globalmente
document.addEventListener('DOMContentLoaded', function() {
    // Questi componenti saranno disponibili nell'app Vue
    if (typeof app !== 'undefined') {
        app.component('erp-stat-card', ERPStatCard);
        app.component('erp-table', ERPTable);
        app.component('erp-modal', ERPModal);
        app.component('erp-alert', ERPAlert);
        app.component('erp-tabs', ERPTabs);
        app.component('erp-action-button', ERPActionButton);
        app.component('erp-menu', ERPMenu);
        app.component('erp-notify', ERPNotify);
    }
});
