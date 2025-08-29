<?php
/**
 * Classe WS_Scenarios - Simulateur de scénarios WinShirt
 * Gère l'affichage du simulateur dans l'admin WordPress
 */

if (!defined('ABSPATH')) exit;

class WS_Scenarios {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }
    
    /**
     * Ajoute le menu admin
     */
    public static function add_admin_menu() {
        // Créer le menu principal WinShirt s'il n'existe pas
        add_menu_page(
            'WinShirt',                    // Page title
            'WinShirt',                    // Menu title
            'manage_options',              // Capability
            'winshirt_main',               // Menu slug
            [__CLASS__, 'render_main_page'], // Callback pour la page principale
            'dashicons-tickets-alt',       // Icon
            30                            // Position
        );
        
        // Ajouter le sous-menu Scénarios
        add_submenu_page(
            'winshirt_main',               // Parent slug
            'Simulateur de Scénarios',     // Page title
            'Scénarios',                   // Menu title
            'manage_options',              // Capability
            'winshirt_scenarios',          // Menu slug
            [__CLASS__, 'render_page']     // Callback
        );
        
        // Renommer le premier sous-menu (éviter la duplication)
        add_submenu_page(
            'winshirt_main',
            'Dashboard WinShirt',
            'Dashboard',
            'manage_options',
            'winshirt_main',
            [__CLASS__, 'render_main_page']
        );
    }
    
    /**
     * Charge les scripts et styles
     */
    public static function enqueue_scripts($hook) {
        // Vérifier si on est sur une page WinShirt
        if (strpos($hook, 'winshirt') === false) return;
        
        // Chart.js pour les graphiques
        wp_enqueue_script(
            'chartjs',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
            [],
            '3.9.1',
            true
        );
        
        // Styles inline pour le simulateur
        wp_add_inline_style('wp-admin', self::get_css());
    }
    
    /**
     * Page principale du dashboard
     */
    public static function render_main_page() {
        ?>
        <div class="wrap">
            <h1>🎯 WinShirt Dashboard</h1>
            <div style="background: white; padding: 30px; border-radius: 8px; margin-top: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2>Bienvenue dans WinShirt !</h2>
                <p>Gérez vos loteries et simulez vos scénarios de rentabilité.</p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 10px; text-align: center;">
                        <h3 style="margin: 0 0 10px 0;">📊 Simulateur</h3>
                        <p style="margin: 0 0 15px 0; opacity: 0.9;">Testez la rentabilité de vos loteries</p>
                        <a href="<?php echo admin_url('admin.php?page=winshirt_scenarios'); ?>" 
                           style="background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; display: inline-block;">
                            Accéder →
                        </a>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 25px; border-radius: 10px; text-align: center;">
                        <h3 style="margin: 0 0 10px 0;">🎫 Loteries</h3>
                        <p style="margin: 0 0 15px 0; opacity: 0.9;">Gérez vos loteries en cours</p>
                        <span style="background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border-radius: 6px; display: inline-block;">
                            Bientôt disponible
                        </span>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 25px; border-radius: 10px; text-align: center;">
                        <h3 style="margin: 0 0 10px 0;">⚙️ Paramètres</h3>
                        <p style="margin: 0 0 15px 0; opacity: 0.9;">Configuration du plugin</p>
                        <span style="background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border-radius: 6px; display: inline-block;">
                            Bientôt disponible
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Rendu de la page admin
     */
    public static function render_page() {
        ?>
        <div class="wrap">
            <div class="winshirt-simulator-container">
                <div class="winshirt-header">
                    <h1>🎯 WinShirt Simulateur</h1>
                    <p>Simulateur de scénarios en temps réel pour vos loteries</p>
                </div>
                
                <div class="winshirt-main-content">
                    <div class="winshirt-sidebar">
                        <h2>⚙️ Configuration</h2>
                        
                        <div class="winshirt-form-group">
                            <label for="ticketPrice">Prix ticket TTC (€)</label>
                            <input type="number" id="ticketPrice" value="20" step="0.01">
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="tshirtCost">Coût T-Shirt (€)</label>
                            <input type="number" id="tshirtCost" value="2" step="0.01">
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="printCost">Coût impression (€)</label>
                            <input type="number" id="printCost" value="2" step="0.01">
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="bagCost">Coût sac (€)</label>
                            <input type="number" id="bagCost" value="0.5" step="0.01">
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="shippingCost">Coût port (€)</label>
                            <input type="number" id="shippingCost" value="0.17" step="0.01">
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="fixedCosts">Charges fixes (€)</label>
                            <input type="number" id="fixedCosts" value="17360" step="0.01">
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="prizeValue">Valeur du lot (€)</label>
                            <input type="number" id="prizeValue" value="0" step="0.01">
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="tvaRate">Taux TVA (%)</label>
                            <input type="number" id="tvaRate" value="20" step="0.01">
                        </div>
                        
                        <div class="winshirt-checkbox-group">
                            <input type="checkbox" id="refundEnabled" checked>
                            <label for="refundEnabled">Remboursement activé</label>
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="refundValue">Valeur remboursement ticket (€)</label>
                            <input type="number" id="refundValue" value="5" step="0.01">
                        </div>
                    </div>
                    
                    <div class="winshirt-results-panel">
                        <h2>📊 Scénarios de Rentabilité</h2>
                        
                        <div class="winshirt-scenarios-grid" id="scenariosGrid">
                            <!-- Les scénarios seront générés ici -->
                        </div>
                        
                        <div class="winshirt-chart-container">
                            <h3>📈 Évolution du Bénéfice par Nombre de Tickets</h3>
                            <canvas id="profitChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            <?php echo self::get_javascript(); ?>
        </script>
        <?php
    }
    
    /**
     * CSS du simulateur
     */
    private static function get_css() {
        return '
        .winshirt-simulator-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 20px 0;
        }
        
        .winshirt-header {
            background: linear-gradient(135deg, #2D3748 0%, #1A202C 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .winshirt-header h1 {
            font-size: 2rem;
            margin: 0 0 10px 0;
            font-weight: 700;
        }
        
        .winshirt-header p {
            font-size: 1.1rem;
            opacity: 0.8;
            margin: 0;
        }
        
        .winshirt-main-content {
            display: grid;
            grid-template-columns: 400px 1fr;
            min-height: 600px;
        }
        
        .winshirt-sidebar {
            background: #f8fafc;
            padding: 30px;
            border-right: 1px solid #e2e8f0;
        }
        
        .winshirt-sidebar h2 {
            margin: 0 0 25px 0;
            color: #2D3748;
            font-size: 1.4rem;
        }
        
        .winshirt-form-group {
            margin-bottom: 20px;
        }
        
        .winshirt-form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #2D3748;
            font-size: 0.9rem;
        }
        
        .winshirt-form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .winshirt-checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        
        .winshirt-checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        .winshirt-results-panel {
            padding: 30px;
        }
        
        .winshirt-results-panel h2 {
            margin: 0 0 30px 0;
            color: #2d3748;
            font-size: 1.6rem;
        }
        
        .winshirt-scenarios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .winshirt-scenario-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            border: 2px solid #e2e8f0;
            position: relative;
        }
        
        .winshirt-scenario-card.profitable {
            border-color: #10b981;
            background: #f0fdf4;
        }
        
        .winshirt-scenario-card.break-even {
            border-color: #f59e0b;
            background: #fffbeb;
        }
        
        .winshirt-scenario-card.loss {
            border-color: #ef4444;
            background: #fef2f2;
        }
        
        .winshirt-scenario-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #1a202c;
        }
        
        .winshirt-scenario-metrics {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .winshirt-metric {
            text-align: center;
            padding: 8px;
            background: white;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .winshirt-metric-value {
            font-weight: 700;
            color: #2d3748;
        }
        
        .winshirt-metric-label {
            color: #718096;
            margin-top: 2px;
        }
        
        .winshirt-result-summary {
            text-align: center;
            background: white;
            border-radius: 8px;
            padding: 15px;
        }
        
        .winshirt-result-value {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 5px;
        }
        
        .winshirt-result-value.positive { color: #10b981; }
        .winshirt-result-value.neutral { color: #f59e0b; }
        .winshirt-result-value.negative { color: #ef4444; }
        
        .winshirt-status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .winshirt-status-profitable { background: #d1fae5; color: #065f46; }
        .winshirt-status-break-even { background: #fef3c7; color: #92400e; }
        .winshirt-status-loss { background: #fecaca; color: #991b1b; }
        
        .winshirt-chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        
        .winshirt-chart-container h3 {
            margin: 0 0 20px 0;
            text-align: center;
            color: #2d3748;
        }
        
        @media (max-width: 1200px) {
            .winshirt-main-content {
                grid-template-columns: 1fr;
            }
            
            .winshirt-scenarios-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .winshirt-scenarios-grid {
                grid-template-columns: 1fr;
            }
        }
        ';
    }
    
    /**
     * JavaScript du simulateur
     */
    private static function get_javascript() {
        return "
        // Variables globales
        let profitChart = null;
        
        // Fonction pour calculer les métriques d'un scénario
        function calculateScenario(tickets, config) {
            const ticketPriceHT = config.ticketPrice / (1 + config.tvaRate / 100);
            const textileCostPerTicket = config.tshirtCost + config.printCost + config.bagCost + config.shippingCost;
            
            const revenueHT = tickets * ticketPriceHT;
            const textileCosts = tickets * textileCostPerTicket;
            const refundCosts = config.refundEnabled ? tickets * config.refundValue : 0;
            const totalCosts = config.fixedCosts + textileCosts + refundCosts + config.prizeValue;
            const netProfit = revenueHT - totalCosts;
            
            return {
                tickets,
                revenueHT,
                fixedCosts: config.fixedCosts,
                textileCosts,
                refundCosts,
                totalCosts,
                netProfit,
                isRefunded: config.refundEnabled
            };
        }
        
        // Fonction pour formater les euros
        function formatEuro(amount) {
            return new Intl.NumberFormat('fr-FR', { 
                style: 'currency', 
                currency: 'EUR',
                maximumFractionDigits: 0 
            }).format(amount);
        }
        
        // Fonction pour déterminer le statut d'un scénario
        function getScenarioStatus(netProfit) {
            if (netProfit > 1000) return 'profitable';
            if (netProfit >= -1000) return 'break-even';
            return 'loss';
        }
        
        // Fonction pour créer une card de scénario
        function createScenarioCard(scenario) {
            const status = getScenarioStatus(scenario.netProfit);
            const statusText = {
                'profitable': '🚀 Rentable',
                'break-even': '⚖️ Équilibre',
                'loss': '⚠️ Perte'
            }[status];
            
            return `
                <div class=\"winshirt-scenario-card \${status}\">
                    <div class=\"winshirt-scenario-title\">\${scenario.tickets.toLocaleString()} tickets</div>
                    <span class=\"winshirt-status-badge winshirt-status-\${status}\">\${statusText}</span>
                    
                    <div class=\"winshirt-scenario-metrics\">
                        <div class=\"winshirt-metric\">
                            <div class=\"winshirt-metric-value\">\${formatEuro(scenario.revenueHT)}</div>
                            <div class=\"winshirt-metric-label\">CA HT</div>
                        </div>
                        <div class=\"winshirt-metric\">
                            <div class=\"winshirt-metric-value\">\${formatEuro(scenario.totalCosts)}</div>
                            <div class=\"winshirt-metric-label\">Total Charges</div>
                        </div>
                        <div class=\"winshirt-metric\">
                            <div class=\"winshirt-metric-value\">\${formatEuro(scenario.textileCosts)}</div>
                            <div class=\"winshirt-metric-label\">Textile</div>
                        </div>
                        \${scenario.isRefunded ? `
                        <div class=\"winshirt-metric\">
                            <div class=\"winshirt-metric-value\">\${formatEuro(scenario.refundCosts)}</div>
                            <div class=\"winshirt-metric-label\">Remboursements</div>
                        </div>` : ''}
                    </div>
                    
                    <div class=\"winshirt-result-summary\">
                        <div class=\"winshirt-result-value \${scenario.netProfit > 1000 ? 'positive' : scenario.netProfit >= -1000 ? 'neutral' : 'negative'}\">
                            \${formatEuro(scenario.netProfit)}
                        </div>
                        <div style=\"font-size: 0.9rem; color: #718096;\">Résultat net</div>
                    </div>
                </div>
            `;
        }
        
        // Fonction pour mettre à jour le graphique
        function updateChart(scenarios) {
            const ctx = document.getElementById('profitChart').getContext('2d');
            
            if (profitChart) {
                profitChart.destroy();
            }
            
            const ticketCounts = scenarios.map(s => s.tickets);
            const profits = scenarios.map(s => s.netProfit);
            
            profitChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ticketCounts.map(t => `\${t.toLocaleString()}`),
                    datasets: [{
                        label: 'Bénéfice Net (€)',
                        data: profits,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: profits.map(p => p > 1000 ? '#10b981' : p >= -1000 ? '#f59e0b' : '#ef4444'),
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value) {
                                    return formatEuro(value);
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Nombre de tickets'
                            }
                        }
                    }
                }
            });
        }
        
        // Fonction principale de mise à jour
        function updateSimulation() {
            const config = {
                ticketPrice: parseFloat(document.getElementById('ticketPrice').value) || 20,
                tshirtCost: parseFloat(document.getElementById('tshirtCost').value) || 2,
                printCost: parseFloat(document.getElementById('printCost').value) || 2,
                bagCost: parseFloat(document.getElementById('bagCost').value) || 0.5,
                shippingCost: parseFloat(document.getElementById('shippingCost').value) || 0.17,
                fixedCosts: parseFloat(document.getElementById('fixedCosts').value) || 17360,
                prizeValue: parseFloat(document.getElementById('prizeValue').value) || 0,
                tvaRate: parseFloat(document.getElementById('tvaRate').value) || 20,
                refundEnabled: document.getElementById('refundEnabled').checked,
                refundValue: parseFloat(document.getElementById('refundValue').value) || 5
            };
            
            // Générer différents scénarios
            const ticketCounts = [1000, 2000, 3000, 4000, 5000, 6000];
            const scenarios = ticketCounts.map(count => calculateScenario(count, config));
            
            // Mettre à jour l'affichage
            const grid = document.getElementById('scenariosGrid');
            grid.innerHTML = scenarios.map(createScenarioCard).join('');
            
            // Mettre à jour le graphique
            updateChart(scenarios);
        }
        
        // Initialisation
        jQuery(document).ready(function($) {
            // Mise à jour initiale
            updateSimulation();
            
            // Écouter les changements
            $('input').on('input change', updateSimulation);
        });
        ";
    }
}
