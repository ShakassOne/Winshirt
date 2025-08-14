<?php
/**
 * Script de diagnostic WinShirt
 * À placer à la racine du plugin et exécuter via admin
 */

// Sécurité
if ( ! defined( 'ABSPATH' ) ) exit;

// Ajouter une page de diagnostic dans le menu admin
add_action( 'admin_menu', function() {
    add_submenu_page(
        'winshirt',
        'Diagnostic WinShirt',
        'Diagnostic',
        'manage_options',
        'winshirt-debug',
        'winshirt_debug_page'
    );
});

function winshirt_debug_page() {
    echo '<div class="wrap"><h1>Diagnostic WinShirt</h1>';
    
    // 1. Vérifier les constantes
    echo '<h2>1. Constantes</h2>';
    echo '<ul>';
    echo '<li>WINSHIRT_PLUGIN_DIR: ' . (defined('WINSHIRT_PLUGIN_DIR') ? WINSHIRT_PLUGIN_DIR : 'NON DÉFINIE') . '</li>';
    echo '<li>WINSHIRT_PLUGIN_URL: ' . (defined('WINSHIRT_PLUGIN_URL') ? WINSHIRT_PLUGIN_URL : 'NON DÉFINIE') . '</li>';
    echo '</ul>';
    
    // 2. Vérifier les fichiers
    echo '<h2>2. Fichiers Critiques</h2>';
    $files = array(
        'includes/class-winshirt-mockup-admin.php',
        'assets/css/mockup-admin.css',
        'assets/js/mockup-admin.js',
        'includes/class-winshirt-admin-redirect.php',
        'includes/class-winshirt-admin-menu-fixed.php'
    );
    
    echo '<ul>';
    foreach ($files as $file) {
        $path = WINSHIRT_PLUGIN_DIR . $file;
        $exists = file_exists($path);
        $readable = $exists ? is_readable($path) : false;
        
        echo '<li>' . $file . ': ';
        if ($exists) {
            echo '✅ Existe ';
            echo $readable ? '(Lisible)' : '❌ (Non lisible)';
            echo ' - Taille: ' . filesize($path) . ' bytes';
        } else {
            echo '❌ Manquant';
        }
        echo '</li>';
    }
    echo '</ul>';
    
    // 3. Vérifier les classes chargées
    echo '<h2>3. Classes Chargées</h2>';
    $classes = array(
        'WinShirt_Mockup_Admin',
        'WinShirt_Admin_Redirect', 
        'WinShirt_Admin_Menu_Fixed'
    );
    
    echo '<ul>';
    foreach ($classes as $class) {
        echo '<li>' . $class . ': ' . (class_exists($class) ? '✅ Chargée' : '❌ Non chargée') . '</li>';
    }
    echo '</ul>';
    
    // 4. Vérifier les erreurs PHP
    echo '<h2>4. Erreurs PHP Récentes</h2>';
    $log_file = ini_get('error_log');
    if ($log_file && file_exists($log_file)) {
        $lines = file($log_file);
        $recent_errors = array_slice($lines, -10); // 10 dernières lignes
        
        echo '<pre style="background: #f1f1f1; padding: 10px; max-height: 300px; overflow-y: auto;">';
        foreach ($recent_errors as $line) {
            if (strpos($line, 'WinShirt') !== false || strpos($line, 'winshirt') !== false) {
                echo esc_html($line);
            }
        }
        echo '</pre>';
    } else {
        echo '<p>Fichier de log non trouvé ou inaccessible.</p>';
    }
    
    // 5. Test de chargement manuel
    echo '<h2>5. Test de Chargement Manuel</h2>';
    $test_file = WINSHIRT_PLUGIN_DIR . 'includes/class-winshirt-mockup-admin.php';
    
    if (file_exists($test_file)) {
        echo '<p>Tentative de chargement manuel...</p>';
        
        ob_start();
        $result = include_once $test_file;
        $output = ob_get_clean();
        
        echo '<p>Résultat: ' . ($result ? '✅ Succès' : '❌ Échec') . '</p>';
        
        if ($output) {
            echo '<p>Sortie: <pre>' . esc_html($output) . '</pre></p>';
        }
        
        echo '<p>Classe maintenant disponible: ' . (class_exists('WinShirt_Mockup_Admin') ? '✅ Oui' : '❌ Non') . '</p>';
    }
    
    // 6. Informations système
    echo '<h2>6. Informations Système</h2>';
    echo '<ul>';
    echo '<li>PHP Version: ' . phpversion() . '</li>';
    echo '<li>WordPress Version: ' . get_bloginfo('version') . '</li>';
    echo '<li>WooCommerce: ' . (class_exists('WooCommerce') ? '✅ Actif' : '❌ Inactif') . '</li>';
    echo '<li>Memory Limit: ' . ini_get('memory_limit') . '</li>';
    echo '<li>Max Execution Time: ' . ini_get('max_execution_time') . '</li>';
    echo '</ul>';
    
    echo '</div>';
}

// Ajouter automatiquement le diagnostic si on est sur une page winshirt
add_action('plugins_loaded', function() {
    if (defined('WINSHIRT_PLUGIN_DIR')) {
        include_once __FILE__;
    }
});
