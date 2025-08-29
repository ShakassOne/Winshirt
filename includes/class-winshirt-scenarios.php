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
                            <label for="ticketPrice">💰 Prix de vente du produit TTC (€)</label>
                            <input type="number" id="ticketPrice" value="20" step="0.01">
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="tshirtCost">👕 Prix d'achat du produit (€)</label>
                            <input type="number" id="tshirtCost" value="2" step="0.01">
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="printCost">🎨 Coût personnalisation/impression (€)</label>
                            <input type="number" id="printCost" value="2" step="0.01">
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="bagCost">🛍️ Coût emballage/sac (€)</label>
                            <input type="number" id="bagCost" value="0.5" step="0.01">
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="shippingCost">📦 Coût expédition unitaire (€)</label>
                            <input type="number" id="shippingCost" value="0.17" step="0.01">
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="stockBuffer">📦 Stock tampon (unités)</label>
                            <input type="number" id="stockBuffer" value="50" step="1">
                            <small style="color: #666; font-size: 0.8rem;">Produits déjà imprimés en stock</small>
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="fixedCosts">🏢 Charges fixes totales (€)</label>
                            <input type="number" id="fixedCosts" value="17360" step="0.01">
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="prizeValue">🎁 Coût du lot à gagner (€)</label>
                            <input type="number" id="prizeValue" value="0" step="0.01">
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="objectiveTickets">🎯 Objectif minimum de tickets</label>
                            <input type="number" id="objectiveTickets" value="5000" step="1">
                            <small style="color: #666; font-size: 0.8rem;">Seuil pour éviter les remboursements</small>
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="actualSold">📈 Tickets déjà vendus (simulation)</label>
                            <input type="number" id="actualSold" value="0" step="1">
                            <small style="color: #666; font-size: 0.8rem;">Pour voir votre position actuelle</small>
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="tvaRate">Taux TVA (%)</label>
                            <input type="number" id="tvaRate" value="20" step="0.01">
                        </div>
                        
                        <div class="winshirt-checkbox-group">
                            <input type="checkbox" id="refundEnabled" checked>
                            <label for="refundEnabled">🔄 Remboursement si objectif non atteint</label>
                        </div>
                        
                        <div class="winshirt-form-group">
                            <label for="refundValue">💸 Montant remboursement par ticket (€)</label>
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
            gap: 8px;
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
        
        .winshirt-objective-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin-top: 5px;
            background: #d1fae5;
            color: #065f46;
        }
        
        .winshirt-objective-badge.warning {
            background: #fed7d7;
            color: #c53030;
        }
        
        .winshirt-scenario-card.objective-reached {
            box-shadow: 0 0 0 2px #10b981;
        }
        
        .winshirt-metric.warning {
            background: #fed7d7 !important;
            color: #c53030;
        }
        
        .winshirt-form-group small {
            display: block;
            margin-top: 3px;
        }
        
        .winshirt-scenario-card.break-even-special {
            border: 3px solid #f59e0b !important;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%) !important;
            position: relative;
            overflow: visible;
        }
        
        .winshirt-scenario-card.break-even-special::before {
            content: "🎯";
            position: absolute;
            top: -15px;
            right: -15px;
            background: #f59e0b;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
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
        
        // Fonction pour calculer le point d'équilibre (break-even) par recherche précise
        function calculateBreakEven(config) {
            let bestTickets = null;
            let bestProfit = Infinity;
            
            // Recherche grossière d'abord (par 100)
            for (let tickets = 100; tickets <= 10000; tickets += 100) {
                let scenario = calculateScenario(tickets, config);
                if (Math.abs(scenario.netProfit) < Math.abs(bestProfit)) {
                    bestProfit = scenario.netProfit;
                    bestTickets = tickets;
                }
            }
            
            if (bestTickets === null) return null;
            
            // Recherche fine autour du meilleur résultat (par 1)
            let start = Math.max(1, bestTickets - 100);
            let end = bestTickets + 100;
            
            for (let tickets = start; tickets <= end; tickets++) {
                let scenario = calculateScenario(tickets, config);
                if (Math.abs(scenario.netProfit) < Math.abs(bestProfit)) {
                    bestProfit = scenario.netProfit;
                    bestTickets = tickets;
                }
                
                // Si on trouve exactement 0, on s'arrête
                if (Math.abs(scenario.netProfit) < 1) {
                    return tickets;
                }
            }
            
            return bestTickets;
        }
        
        // Fonction pour calculer les métriques d'un scénario
        function calculateScenario(tickets, config) {
            const ticketPriceHT = config.ticketPrice / (1 + config.tvaRate / 100);
            const productCostPerTicket = config.tshirtCost + config.printCost + config.bagCost;
            const shippingCostPerTicket = config.shippingCost;
            
            const revenueHT = tickets * ticketPriceHT;
            
            // LOGIQUE CORRECTE : Stock tampon = coût minimum même si on vend moins
            let productCosts;
            if (tickets <= config.stockBuffer) {
                // Si on vend moins que le stock : on paie quand même tout le stock
                productCosts = config.stockBuffer * productCostPerTicket;
            } else {
                // Si on vend plus que le stock : on paie ce qu'on vend réellement
                productCosts = tickets * productCostPerTicket;
            }
            
            // Transport : seulement pour ce qui est vendu et expédié
            const shippingCosts = tickets * shippingCostPerTicket;
            
            // Remboursement seulement si objectif non atteint ET remboursement activé
            const needsRefund = config.refundEnabled && tickets < config.objectiveTickets;
            const refundCosts = needsRefund ? tickets * config.refundValue : 0;
            
            const totalCosts = config.fixedCosts + productCosts + shippingCosts + refundCosts + config.prizeValue;
            const netProfit = revenueHT - totalCosts;
            
            return {
                tickets: tickets,
                revenueHT: revenueHT,
                fixedCosts: config.fixedCosts,
                productCosts: productCosts,
                shippingCosts: shippingCosts,
                refundCosts: refundCosts,
                totalCosts: totalCosts,
                netProfit: netProfit,
                isRefunded: needsRefund,
                objectiveReached: tickets >= config.objectiveTickets,
                stockBuffer: config.stockBuffer
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
            
            // Cas spécial pour le break-even
            if (scenario.isBreakEven) {
                return '<div class=\"winshirt-scenario-card break-even break-even-special\">' +
                    '<div class=\"winshirt-scenario-title\">🎯 ' + scenario.tickets.toLocaleString() + ' tickets</div>' +
                    '<span class=\"winshirt-status-badge winshirt-status-break-even\">💰 Point d\\'équilibre</span>' +
                    '<div class=\"winshirt-objective-badge\">⚖️ Break-Even</div>' +
                    '<div class=\"winshirt-scenario-metrics\">' +
                        '<div class=\"winshirt-metric\">' +
                            '<div class=\"winshirt-metric-value\">' + formatEuro(scenario.revenueHT) + '</div>' +
                            '<div class=\"winshirt-metric-label\">CA HT</div>' +
                        '</div>' +
                        '<div class=\"winshirt-metric\">' +
                            '<div class=\"winshirt-metric-value\">' + formatEuro(scenario.totalCosts) + '</div>' +
                            '<div class=\"winshirt-metric-label\">Total Charges</div>' +
                        '</div>' +
                        '<div class=\"winshirt-metric\">' +
                            '<div class=\"winshirt-metric-value\">' + formatEuro(scenario.productCosts) + '</div>' +
                            '<div class=\"winshirt-metric-label\">Prod. + Stock</div>' +
                        '</div>' +
                        '<div class=\"winshirt-metric\">' +
                            '<div class=\"winshirt-metric-value\">' + formatEuro(scenario.shippingCosts) + '</div>' +
                            '<div class=\"winshirt-metric-label\">Transport</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class=\"winshirt-result-summary\">' +
                        '<div class=\"winshirt-result-value neutral\">' +
                            formatEuro(scenario.netProfit) +
                        '</div>' +
                        '<div style=\"font-size: 0.9rem; color: #718096;\">Seuil de rentabilité</div>' +
                    '</div>' +
                '</div>';
            }
            
            const statusText = {
                'profitable': scenario.objectiveReached ? '🚀 Objectif atteint' : '💡 Rentable',
                'break-even': scenario.objectiveReached ? '⚖️ Équilibre OK' : '⚖️ Équilibre',
                'loss': scenario.objectiveReached ? '⚠️ Objectif mais perte' : '⚠️ Perte + Remb.'
            }[status];
            
            var refundHtml = '';
            if (scenario.isRefunded) {
                refundHtml = '<div class=\"winshirt-metric warning\">' +
                    '<div class=\"winshirt-metric-value\">' + formatEuro(scenario.refundCosts) + '</div>' +
                    '<div class=\"winshirt-metric-label\">Remboursements</div>' +
                '</div>';
            }
            
            var objectiveBadge = scenario.objectiveReached ? 
                '<div class=\"winshirt-objective-badge\">✅ Objectif OK</div>' : 
                '<div class=\"winshirt-objective-badge warning\">❌ Sous objectif</div>';
            
            var resultClass = scenario.netProfit > 1000 ? 'positive' : 
                             scenario.netProfit >= -1000 ? 'neutral' : 'negative';
            
            var cardClass = status + (scenario.objectiveReached ? ' objective-reached' : '');
            
            return '<div class=\"winshirt-scenario-card ' + cardClass + '\">' +
                '<div class=\"winshirt-scenario-title\">' + scenario.tickets.toLocaleString() + ' tickets</div>' +
                '<span class=\"winshirt-status-badge winshirt-status-' + status + '\">' + statusText + '</span>' +
                objectiveBadge +
                '<div class=\"winshirt-scenario-metrics\">' +
                    '<div class=\"winshirt-metric\">' +
                        '<div class=\"winshirt-metric-value\">' + formatEuro(scenario.revenueHT) + '</div>' +
                        '<div class=\"winshirt-metric-label\">CA HT</div>' +
                    '</div>' +
                    '<div class=\"winshirt-metric\">' +
                        '<div class=\"winshirt-metric-value\">' + formatEuro(scenario.totalCosts) + '</div>' +
                        '<div class=\"winshirt-metric-label\">Total Charges</div>' +
                    '</div>' +
                    '<div class=\"winshirt-metric\">' +
                        '<div class=\"winshirt-metric-value\">' + formatEuro(scenario.productCosts) + '</div>' +
                        '<div class=\"winshirt-metric-label\">Prod. + Stock</div>' +
                    '</div>' +
                    '<div class=\"winshirt-metric\">' +
                        '<div class=\"winshirt-metric-value\">' + formatEuro(scenario.shippingCosts) + '</div>' +
                        '<div class=\"winshirt-metric-label\">Transport</div>' +
                    '</div>' +
                    refundHtml +
                '</div>' +
                '<div class=\"winshirt-result-summary\">' +
                    '<div class=\"winshirt-result-value ' + resultClass + '\">' +
                        formatEuro(scenario.netProfit) +
                    '</div>' +
                    '<div style=\"font-size: 0.9rem; color: #718096;\">Résultat net</div>' +
                '</div>' +
            '</div>';
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
                    labels: ticketCounts.map(function(t) { return t.toLocaleString(); }),
                    datasets: [{
                        label: 'Bénéfice Net (€)',
                        data: profits,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: profits.map(function(p) { 
                            return p > 1000 ? '#10b981' : p >= -1000 ? '#f59e0b' : '#ef4444'; 
                        }),
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
            // CORRECTION: Lecture explicite des valeurs avec vérification
            const ticketPriceValue = document.getElementById('ticketPrice').value;
            const tshirtCostValue = document.getElementById('tshirtCost').value;
            const printCostValue = document.getElementById('printCost').value;
            const bagCostValue = document.getElementById('bagCost').value;
            const shippingCostValue = document.getElementById('shippingCost').value;
            const stockBufferValue = document.getElementById('stockBuffer').value;
            const fixedCostsValue = document.getElementById('fixedCosts').value;
            const prizeValueValue = document.getElementById('prizeValue').value;
            const tvaRateValue = document.getElementById('tvaRate').value;
            const refundValueValue = document.getElementById('refundValue').value;
            const objectiveTicketsValue = document.getElementById('objectiveTickets').value;
            const actualSoldValue = document.getElementById('actualSold').value;
            const refundEnabledValue = document.getElementById('refundEnabled').checked;
            
            // DEBUG: Log des valeurs lues
            console.log('Valeurs lues:', {
                fixedCosts: fixedCostsValue,
                prizeValue: prizeValueValue,
                refundEnabled: refundEnabledValue,
                refundValue: refundValueValue
            });
            
            const config = {
                ticketPrice: parseFloat(ticketPriceValue) || 0,
                tshirtCost: parseFloat(tshirtCostValue) || 0,
                printCost: parseFloat(printCostValue) || 0,
                bagCost: parseFloat(bagCostValue) || 0,
                shippingCost: parseFloat(shippingCostValue) || 0,
                stockBuffer: parseFloat(stockBufferValue) || 0,
                fixedCosts: parseFloat(fixedCostsValue) || 0,
                prizeValue: parseFloat(prizeValueValue) || 0,
                tvaRate: parseFloat(tvaRateValue) || 20,
                refundEnabled: refundEnabledValue,
                refundValue: parseFloat(refundValueValue) || 0,
                objectiveTickets: parseFloat(objectiveTicketsValue) || 1000,
                actualSold: parseFloat(actualSoldValue) || 0
            };
            
            // DEBUG: Log de la config finale
            console.log('Config finale:', config);
            
            // Calculer le point d'équilibre
            const breakEvenTickets = calculateBreakEven(config);
            
            // Générer différents scénarios
            let ticketCounts = [1000, 2000, 3000, 4000, 5000, 6000];
            
            // Ajouter le point d'équilibre
            if (breakEvenTickets && breakEvenTickets > 0) {
                ticketCounts.push(breakEvenTickets);
            }
            
            // Ajouter l'objectif s'il est différent des valeurs par défaut
            if (config.objectiveTickets && !ticketCounts.includes(config.objectiveTickets)) {
                ticketCounts.push(config.objectiveTickets);
            }
            
            // Ajouter le nombre actuel vendu s'il est renseigné
            if (config.actualSold > 0 && !ticketCounts.includes(config.actualSold)) {
                ticketCounts.push(config.actualSold);
            }
            
            // Trier les valeurs
            ticketCounts.sort((a, b) => a - b);
            
            // Créer les scénarios
            const scenarios = ticketCounts.map(count => {
                const scenario = calculateScenario(count, config);
                // DEBUG: Log du premier scénario
                if (count === ticketCounts[0]) {
                    console.log('Premier scénario:', scenario);
                }
                // Marquer le break-even
                if (count === breakEvenTickets) {
                    scenario.isBreakEven = true;
                }
                return scenario;
            });
            
            // Mettre à jour l'affichage
            const grid = document.getElementById('scenariosGrid');
            grid.innerHTML = scenarios.map(createScenarioCard).join('');
            
            // Mettre à jour le graphique
            updateChart(scenarios);
        }
        
        // Initialisation
        jQuery(document).ready(function() {
            // Mise à jour initiale
            updateSimulation();
            
            // Écouter les changements
            jQuery('input').on('input change', updateSimulation);
        });
        ";
    }
}
