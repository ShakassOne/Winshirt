<?php
/**
 * WinShirt - Menu Admin Corrigé
 * 
 * Remplace complètement l'ancien système de menu
 * 
 * @package WinShirt
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Admin_Menu_Fixed {
    
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 5 );
        add_action( 'admin_head', array( $this, 'hide_cpt_menus' ) );
        add_filter( 'submenu_file', array( $this, 'highlight_current_menu' ) );
    }
    
    /**
     * Ajouter le menu admin principal
     */
    public function add_admin_menu() {
        // Menu principal WinShirt
        add_menu_page(
            'WinShirt',                    // Page title
            'WinShirt',                    // Menu title
            'manage_options',              // Capability
            'winshirt',                    // Menu slug
            array( $this, 'dashboard_page' ), // Callback
            'dashicons-admin-customizer',  // Icon
            30                             // Position
        );
        
        // Sous-menu Dashboard (renommer le premier)
        add_submenu_page(
            'winshirt',
            'Tableau de Bord WinShirt',
            'Dashboard',
            'manage_options',
            'winshirt',
            array( $this, 'dashboard_page' )
        );
        
        // Sous-menu Mockups
        add_submenu_page(
            'winshirt',
            'Gestion des Mockups',
            'Mockups',
            'manage_options',
            'winshirt-mockups',
            array( $this, 'mockups_page' )
        );
        
        // Sous-menu Visuels
        add_submenu_page(
            'winshirt',
            'Gestion des Visuels',
            'Visuels',
            'manage_options',
            'winshirt-visuals',
            array( $this, 'visuals_page' )
        );
        
        // Sous-menu Loteries
        add_submenu_page(
            'winshirt',
            'Gestion des Loteries',
            'Loteries',
            'manage_options',
            'winshirt-lotteries',
            array( $this, 'lotteries_page' )
        );
        
        // Sous-menu Roadmap
        add_submenu_page(
            'winshirt',
            'Roadmap WinShirt',
            'Roadmap',
            'manage_options',
            'winshirt-roadmap',
            array( $this, 'roadmap_page' )
        );
        
        // Sous-menu Paramètres
        add_submenu_page(
            'winshirt',
            'Paramètres WinShirt',
            'Paramètres',
            'manage_options',
            'winshirt-settings',
            array( $this, 'settings_page' )
        );
        
        // Pages masquées (éditeurs)
        add_submenu_page(
            null, // Masqué
            'Éditeur de Mockup',
            'Éditer Mockup',
            'manage_options',
            'winshirt-edit-mockup',
            array( $this, 'edit_mockup_page' )
        );
        
        add_submenu_page(
            null, // Masqué
            'Éditeur de Visuel',
            'Éditer Visuel',
            'manage_options',
            'winshirt-edit-visual',
            array( $this, 'edit_visual_page' )
        );
        
        add_submenu_page(
            null, // Masqué
            'Éditeur de Loterie',
            'Éditer Loterie',
            'manage_options',
            'winshirt-edit-lottery',
            array( $this, 'edit_lottery_page' )
        );
    }
    
    /**
     * Masquer les menus CPT automatiques
     */
    public function hide_cpt_menus() {
        ?>
        <style>
        /* Masquer les menus CPT générés automatiquement */
        #menu-posts-winshirt_mockup,
        #menu-posts-winshirt_visual,
        #menu-posts-winshirt_lottery {
            display: none !important;
        }
        </style>
        <?php
    }
    
    /**
     * Surligner le bon menu selon la page
     */
    public function highlight_current_menu( $submenu_file ) {
        global $plugin_page;
        
        $winshirt_pages = array(
            'winshirt-mockups',
            'winshirt-edit-mockup',
            'winshirt-visuals', 
            'winshirt-edit-visual',
            'winshirt-lotteries',
            'winshirt-edit-lottery',
            'winshirt-roadmap',
            'winshirt-settings'
        );
        
        if ( in_array( $plugin_page, $winshirt_pages ) ) {
            // Pour les éditeurs, surligner la page liste correspondante
            if ( $plugin_page === 'winshirt-edit-mockup' ) {
                $submenu_file = 'winshirt-mockups';
            } elseif ( $plugin_page === 'winshirt-edit-visual' ) {
                $submenu_file = 'winshirt-visuals';
            } elseif ( $plugin_page === 'winshirt-edit-lottery' ) {
                $submenu_file = 'winshirt-lotteries';
            }
        }
        
        return $submenu_file;
    }
    
    /**
     * Page Dashboard
     */
    public function dashboard_page() {
        ?>
        <div class="wrap">
            <h1>WinShirt - Tableau de Bord</h1>
            
            <div class="winshirt-dashboard">
                <div class="winshirt-stats-grid">
                    <div class="stat-card">
                        <h3>Mockups</h3>
                        <div class="stat-number">
                            <?php echo wp_count_posts( 'winshirt_mockup' )->publish ?? 0; ?>
                        </div>
                        <a href="<?php echo admin_url( 'admin.php?page=winshirt-mockups' ); ?>" class="button">
                            Gérer les Mockups
                        </a>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Visuels</h3>
                        <div class="stat-number">
                            <?php echo wp_count_posts( 'winshirt_visual' )->publish ?? 0; ?>
                        </div>
                        <a href="<?php echo admin_url( 'admin.php?page=winshirt-visuals' ); ?>" class="button">
                            Gérer les Visuels
                        </a>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Loteries</h3>
                        <div class="stat-number">
                            <?php echo wp_count_posts( 'winshirt_lottery' )->publish ?? 0; ?>
                        </div>
                        <a href="<?php echo admin_url( 'admin.php?page=winshirt-lotteries' ); ?>" class="button">
                            Gérer les Loteries
                        </a>
                    </div>
                </div>
                
                <div class="winshirt-quick-actions">
                    <h2>Actions Rapides</h2>
                    <div class="quick-actions-grid">
                        <a href="<?php echo admin_url( 'admin.php?page=winshirt-edit-mockup' ); ?>" class="quick-action">
                            <span class="dashicons dashicons-admin-customizer"></span>
                            Créer un Mockup
                        </a>
                        <a href="<?php echo admin_url( 'admin.php?page=winshirt-edit-visual' ); ?>" class="quick-action">
                            <span class="dashicons dashicons-format-image"></span>
                            Créer un Visuel
                        </a>
                        <a href="<?php echo admin_url( 'admin.php?page=winshirt-edit-lottery' ); ?>" class="quick-action">
                            <span class="dashicons dashicons-tickets-alt"></span>
                            Créer une Loterie
                        </a>
                        <a href="<?php echo admin_url( 'admin.php?page=winshirt-roadmap' ); ?>" class="quick-action">
                            <span class="dashicons dashicons-analytics"></span>
                            Voir la Roadmap
                        </a>
                    </div>
                </div>
            </div>
            
            <style>
            .winshirt-dashboard {
                margin-top: 20px;
            }
            
            .winshirt-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .stat-card {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 6px;
                padding: 20px;
                text-align: center;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .stat-card h3 {
                margin: 0 0 10px 0;
                color: #333;
            }
            
            .stat-number {
                font-size: 36px;
                font-weight: bold;
                color: #0073aa;
                margin-bottom: 15px;
            }
            
            .quick-actions-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
            
            .quick-action {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 15px;
                background: #0073aa;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                transition: background 0.3s;
            }
            
            .quick-action:hover {
                background: #005a87;
                color: white;
            }
            
            .quick-action .dashicons {
                font-size: 20px;
            }
            </style>
        </div>
        <?php
    }
    
    /**
     * Page Mockups (déléguer à la classe WinShirt_Mockup_Admin)
     */
    public function mockups_page() {
        if ( class_exists( 'WinShirt_Mockup_Admin' ) ) {
            $mockup_admin = new WinShirt_Mockup_Admin();
            $mockup_admin->render_mockups_list();
        } else {
            echo '<div class="wrap"><h1>Erreur</h1><p>La classe WinShirt_Mockup_Admin n\'est pas chargée.</p></div>';
        }
    }
    
    /**
     * Page Éditeur de Mockup
     */
    public function edit_mockup_page() {
        if ( class_exists( 'WinShirt_Mockup_Admin' ) ) {
            $mockup_admin = new WinShirt_Mockup_Admin();
            $mockup_admin->render_mockup_editor();
        } else {
            echo '<div class="wrap"><h1>Erreur</h1><p>La classe WinShirt_Mockup_Admin n\'est pas chargée.</p></div>';
        }
    }
    
    /**
     * Page Visuels
     */
    public function visuals_page() {
        ?>
        <div class="wrap">
            <h1>
                Gestion des Visuels
                <a href="<?php echo admin_url('admin.php?page=winshirt-edit-visual'); ?>" class="page-title-action">
                    Ajouter un Visuel
                </a>
            </h1>
            <p>Interface de gestion des visuels à implémenter.</p>
        </div>
        <?php
    }
    
    /**
     * Page Éditeur de Visuel
     */
    public function edit_visual_page() {
        ?>
        <div class="wrap">
            <h1>Éditeur de Visuel</h1>
            <p>Interface d'édition des visuels à implémenter.</p>
        </div>
        <?php
    }
    
    /**
     * Page Loteries
     */
    public function lotteries_page() {
        ?>
        <div class="wrap">
            <h1>
                Gestion des Loteries
                <a href="<?php echo admin_url('admin.php?page=winshirt-edit-lottery'); ?>" class="page-title-action">
                    Ajouter une Loterie
                </a>
            </h1>
            <p>Interface de gestion des loteries à implémenter.</p>
        </div>
        <?php
    }
    
    /**
     * Page Éditeur de Loterie
     */
    public function edit_lottery_page() {
        ?>
        <div class="wrap">
            <h1>Éditeur de Loterie</h1>
            <p>Interface d'édition des loteries à implémenter.</p>
        </div>
        <?php
    }
    
    /**
     * Page Roadmap
     */
    public function roadmap_page() {
        if ( class_exists( 'WinShirt_Roadmap' ) ) {
            $roadmap = new WinShirt_Roadmap();
            $roadmap->render_roadmap_page();
        } else {
            echo '<div class="wrap"><h1>Roadmap</h1><p>La classe WinShirt_Roadmap n\'est pas chargée.</p></div>';
        }
    }
    
    /**
     * Page Paramètres
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Paramètres WinShirt</h1>
            <p>Interface de paramètres à implémenter.</p>
        </div>
        <?php
    }
}

// Initialiser la classe
new WinShirt_Admin_Menu_Fixed();
