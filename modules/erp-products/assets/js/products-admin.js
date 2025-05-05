 /**
 * Script principale per il modulo Prodotti
 * Registra i componenti Vue con il router dell'ERP Core
 */
(function($) {
    'use strict';

    // Verifica che l'oggetto ERP sia disponibile
    if (typeof ERP === 'undefined' || typeof ERP.router === 'undefined') {
        console.error('ERP Core non inizializzato correttamente');
        return;
    }

    // Registra i componenti del modulo Prodotti
    ERP.registerModule('erp-prodotti', {
        // Registra le rotte per il router
        routes: [
            {
                path: '/prodotti',
                component: () => import('/wp-content/plugins/erp-core/modules/erp-prodotti/admin/ProdottiApp.vue'),
                children: [
                    {
                        path: '',
                        redirect: '/prodotti/elenco'
                    },
                    {
                        path: 'elenco',
                        name: 'prodotti-elenco',
                        component: () => import('/wp-content/plugins/erp-core/modules/erp-prodotti/components/ElencoTab.vue')
                    },
                    {
                        path: 'categorie',
                        name: 'prodotti-categorie',
                        component: () => import('/wp-content/plugins/erp-core/modules/erp-prodotti/components/CategorieTab.vue')
                    },
                    {
                        path: 'attributi',
                        name: 'prodotti-attributi',
                        component: () => import('/wp-content/plugins/erp-core/modules/erp-prodotti/components/AttributiTab.vue')
                    },
                    {
                        path: 'varianti',
                        name: 'prodotti-varianti',
                        component: () => import('/wp-content/plugins/erp-core/modules/erp-prodotti/components/VariantiTab.vue')
                    }
                ]
            },
            {
                path: '/report/vendite-prodotti',
                name: 'report-vendite-prodotti',
                component: () => import('/wp-content/plugins/erp-core/modules/erp-prodotti/reports/ReportVenditeProdotti.vue'),
            }
        ],

        // Registra le card per la dashboard
        dashboardCards: {
            'tot_prodotti': () => import('/wp-content/plugins/erp-core/modules/erp-prodotti/dashboard/StatsProdotti.vue'),
        }
    });

    // Registra gli eventi personalizzati
    $(document).on('erp_module_loaded_erp-prodotti', function() {
        console.log('Modulo Prodotti caricato con successo');
    });

})(jQuery);
