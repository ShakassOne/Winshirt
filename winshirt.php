<?php
/**
 * Plugin Name: WinShirt
 * Plugin URI: https://winshirt.com
 * Description: Plugin WordPress/WooCommerce pour personnalisation textile avec loteries
 * Version: 1.0.0
 * Author: WinShirt Team
 * Text Domain: winshirt
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Sécurité : interdire l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ===== CONSTANTES DU PLUGIN =====
define( 'WINSHIRT_VERSION', '1.0.0' );
define( 'WINSHIRT_PLUGIN_FILE', __FILE__ );
define( 'WINSHIRT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WINSHIRT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WINSHIRT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// ===== FONCTION D'INCLUSION SÉCURISÉE =====
/**
 * Inclure un fichier seulement s'il existe
 * @param string $file Chemin relatif du fichier
 * @return bool True si inclus avec succès
 */
function winshirt_require_if_exists( $file ) {
    $full_path = WINSHIRT_PLUGIN_DIR . $file;
    if ( file_exists( $full_path ) ) {
        require_once $full_path;
        return true;
    }
    
    // Log en mode debug
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( "WinShirt: Fichier manquant - {$file}" );
    }
    
    return false;
}

// ===== VÉRIFICATION DES DÉPENDANCES =====
/**
 * Vérifier que WooCommerce est actif
 */
function winshirt_check_dependencies() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>WinShirt</strong> : Ce plugin nécessite WooCommerce pour fonctionner.';
            echo '</p></div>';
        });
        return false;
    }
    return true;
}

// ===== CHARGEMENT DES CLASSES =====
/**
 * Charger toutes les classes dans l'ordre correct
 */
function winshirt_load_classes() {
    // Vérifier les dépendances d'abord
    if ( ! winshirt_check_dependencies() ) {
        return;
    }
    
    // ===== CORE CLASSES (ordre critique) =====
    
    // 1. Core principal - Base du système
    winshirt_require_if_exists( 'includes/class-winshirt-core.php' );
    
    // COMMENTÉ POUR TEST - DÉCOMMENTER UN PAR UN
    winshirt_require_if_exists( 'includes/class-winshirt-cpt.php' );
    winshirt_require_if_exists( 'includes/class-winshirt-admin-redirect.php' );
    winshirt_require_if_exists( 'includes/class-winshirt-admin-menu-fixed.php' );
    winshirt_require_if_exists( 'includes/class-winshirt-mockup-admin.php' );
    // winshirt_require_if_exists( 'includes/class-winshirt-assets.php' );
    winshirt_require_if_exists( 'includes/class-winshirt-roadmap.php' );
    // winshirt_require_if_exists( 'includes/class-winshirt-settings.php' );
    winshirt_require_if_exists( 'includes/class-winshirt-order.php' );
    winshirt_require_if_exists( 'includes/class-winshirt-customizer.php' );
    // winshirt_require_if_exists( 'includes/class-winshirt-lottery.php' );
    winshirt_require_if_exists( 'includes/class-winshirt-api.php' );
    
    // Debug (à supprimer en production)
    winshirt_require_if_exists( 'debug-winshirt.php' );
}

// ===== ACTIVATION DU PLUGIN =====
/**
 * Actions à effectuer lors de l'activation
 */
function winshirt_activate() {
    // Vérifier les dépendances
    if ( ! class_exists( 'WooCommerce' ) ) {
        wp_die( 
            'WinShirt nécessite WooCommerce pour fonctionner. Veuillez installer et activer WooCommerce d\'abord.',
            'Dépendance manquante',
            array( 'back_link' => true )
        );
    }
    
    // Charger les classes pour l'activation
    winshirt_load_classes();
    
    // Créer les tables si nécessaire (pour les futures fonctionnalités)
    winshirt_create_tables();
    
    // Créer les pages nécessaires
    winshirt_create_pages();
    
    // Flush des règles de réécriture
    flush_rewrite_rules();
    
    // Ajouter une option pour indiquer l'activation
    add_option( 'winshirt_activated', true );
    add_option( 'winshirt_version', WINSHIRT_VERSION );
}

/**
 * Créer les tables de base de données
 */
function winshirt_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Table pour les statistiques des loteries (exemple)
    $table_lottery_stats = $wpdb->prefix . 'winshirt_lottery_stats';
    
    $sql = "CREATE TABLE $table_lottery_stats (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        lottery_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        action varchar(50) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY lottery_id (lottery_id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

/**
 * Créer les pages nécessaires
 */
function winshirt_create_pages() {
    $pages = array(
        'winshirt-customizer' => array(
            'title' => 'Personnaliser',
            'content' => '[winshirt_customizer]',
            'template' => 'page-customizer.php'
        ),
        'winshirt-gallery' => array(
            'title' => 'Galerie Créations',
            'content' => '[winshirt_gallery]',
            'template' => 'page-gallery.php'
        )
    );
    
    foreach ( $pages as $slug => $page_data ) {
        // Vérifier si la page existe déjà
        $existing_page = get_page_by_path( $slug );
        
        if ( ! $existing_page ) {
            $page_id = wp_insert_post( array(
                'post_title' => $page_data['title'],
                'post_content' => $page_data['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => $slug,
                'post_author' => get_current_user_id()
            ));
            
            if ( $page_id && ! is_wp_error( $page_id ) ) {
                // Sauvegarder l'ID de la page dans les options
                update_option( "winshirt_page_{$slug}", $page_id );
                
                // Définir le template si nécessaire
                if ( isset( $page_data['template'] ) ) {
                    update_post_meta( $page_id, '_wp_page_template', $page_data['template'] );
                }
            }
        }
    }
}

// ===== DÉSACTIVATION DU PLUGIN =====
/**
 * Actions à effectuer lors de la désactivation
 */
function winshirt_deactivate() {
    // Flush des règles de réécriture
    flush_rewrite_rules();
    
    // Nettoyer les options temporaires
    delete_option( 'winshirt_activated' );
    
    // Ne pas supprimer les données utilisateur lors de la désactivation
    // (seulement lors de la suppression complète du plugin)
}

// ===== SUPPRESSION DU PLUGIN =====
/**
 * Actions à effectuer lors de la suppression complète
 */
function winshirt_uninstall() {
    // Supprimer toutes les options
    $options_to_delete = array(
        'winshirt_version',
        'winshirt_settings',
        'winshirt_activated',
        'winshirt_page_winshirt-customizer',
        'winshirt_page_winshirt-gallery'
    );
    
    foreach ( $options_to_delete as $option ) {
        delete_option( $option );
    }
    
    // Supprimer les tables personnalisées
    global $wpdb;
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}winshirt_lottery_stats" );
    
    // Supprimer tous les posts de nos types personnalisés
    $post_types = array( 'winshirt_mockup', 'winshirt_visual', 'winshirt_lottery' );
    
    foreach ( $post_types as $post_type ) {
        $posts = get_posts( array(
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        foreach ( $posts as $post ) {
            wp_delete_post( $post->ID, true );
        }
    }
    
    // Flush des règles de réécriture
    flush_rewrite_rules();
}

// ===== HOOKS ET ACTIONS =====

// Charger le plugin après que WordPress soit initialisé
add_action( 'plugins_loaded', 'winshirt_load_classes', 10 );

// Hook d'activation
register_activation_hook( __FILE__, 'winshirt_activate' );

// Hook de désactivation  
register_deactivation_hook( __FILE__, 'winshirt_deactivate' );

// Hook de suppression (dans un fichier séparé uninstall.php normalement)
register_uninstall_hook( __FILE__, 'winshirt_uninstall' );

// ===== ACTIONS D'INITIALISATION =====

/**
 * Initialisation après activation
 */
add_action( 'admin_init', function() {
    if ( get_option( 'winshirt_activated' ) ) {
        delete_option( 'winshirt_activated' );
        
        // Rediriger vers la page de configuration après activation
        wp_redirect( admin_url( 'admin.php?page=winshirt' ) );
        exit;
    }
});

// ===== SUPPORT MULTISITE =====

/**
 * Activation sur réseau multisite
 */
function winshirt_activate_network( $network_wide ) {
    if ( is_multisite() && $network_wide ) {
        $blog_ids = get_sites( array( 'fields' => 'ids' ) );
        
        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            winshirt_activate();
            restore_current_blog();
        }
    } else {
        winshirt_activate();
    }
}

/**
 * Activation pour nouveau site sur réseau
 */
add_action( 'wpmu_new_blog', function( $blog_id ) {
    if ( is_plugin_active_for_network( WINSHIRT_PLUGIN_BASENAME ) ) {
        switch_to_blog( $blog_id );
        winshirt_activate();
        restore_current_blog();
    }
});

// ===== GESTION DES ERREURS =====

/**
 * Affichage des erreurs en mode debug
 */
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    add_action( 'admin_notices', function() {
        $errors = get_transient( 'winshirt_errors' );
        if ( $errors ) {
            foreach ( $errors as $error ) {
                echo '<div class="notice notice-error"><p><strong>WinShirt:</strong> ' . esc_html( $error ) . '</p></div>';
            }
            delete_transient( 'winshirt_errors' );
        }
    });
}

/**
 * Logger les erreurs
 */
function winshirt_log_error( $message ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( "WinShirt Error: " . $message );
        
        $errors = get_transient( 'winshirt_errors' ) ?: array();
        $errors[] = $message;
        set_transient( 'winshirt_errors', $errors, 300 ); // 5 minutes
    }
}

// ===== FONCTIONS UTILITAIRES =====

/**
 * Obtenir la version du plugin
 */
function winshirt_get_version() {
    return WINSHIRT_VERSION;
}

/**
 * Vérifier si le plugin est correctement configuré
 */
function winshirt_is_configured() {
    return get_option( 'winshirt_version' ) === WINSHIRT_VERSION;
}

/**
 * Obtenir l'URL d'un asset
 */
function winshirt_get_asset_url( $asset ) {
    return WINSHIRT_PLUGIN_URL . 'assets/' . ltrim( $asset, '/' );
}

// ===== SHORTCODES (exemples) =====

/**
 * Shortcode pour le customizer
 */
add_shortcode( 'winshirt_customizer', function( $atts ) {
    $atts = shortcode_atts( array(
        'mockup_id' => '',
        'product_id' => ''
    ), $atts );
    
    // Retourner le HTML du customizer
    ob_start();
    echo '<div id="winshirt-customizer" data-mockup-id="' . esc_attr( $atts['mockup_id'] ) . '" data-product-id="' . esc_attr( $atts['product_id'] ) . '">';
    echo '<p>Customizer WinShirt (à implémenter)</p>';
    echo '</div>';
    return ob_get_clean();
});

/**
 * Shortcode pour la galerie
 */
add_shortcode( 'winshirt_gallery', function( $atts ) {
    $atts = shortcode_atts( array(
        'limit' => 12,
        'category' => ''
    ), $atts );
    
    // Retourner le HTML de la galerie
    ob_start();
    echo '<div id="winshirt-gallery">';
    echo '<p>Galerie WinShirt (à implémenter)</p>';
    echo '</div>';
    return ob_get_clean();
});

// ===== COMPATIBILITÉ =====

/**
 * Déclaration de compatibilité WooCommerce HPOS
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});

/**
 * Support des images WebP
 */
add_filter( 'upload_mimes', function( $mimes ) {
    $mimes['webp'] = 'image/webp';
    return $mimes;
});

// ===== FIN DU FICHIER =====

// Ajouter un commentaire pour indiquer que le plugin est bien chargé
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    add_action( 'wp_footer', function() {
        echo '<!-- WinShirt Plugin v' . WINSHIRT_VERSION . ' chargé -->';
    });
}
