<?php
/**
 * WinShirt - Script de Réparation
 * À placer dans le dossier racine du plugin et exécuter UNE FOIS
 */

// Sécurité
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Vérifier les permissions
if (!current_user_can('manage_options')) {
    die('Accès refusé');
}

echo '<h1>WinShirt - Réparation</h1>';

// 1. Nettoyer les mockups corrompus
echo '<h2>1. Nettoyage des mockups...</h2>';

global $wpdb;

// Supprimer tous les mockups existants (corrupted)
$mockups = get_posts(array(
    'post_type' => 'winshirt_mockup',
    'post_status' => 'any',
    'numberposts' => -1
));

foreach ($mockups as $mockup) {
    wp_delete_post($mockup->ID, true);
    echo "Mockup supprimé: {$mockup->post_title}<br>";
}

// 2. Créer un mockup de test fonctionnel
echo '<h2>2. Création d\'un mockup de test...</h2>';

$mockup_id = wp_insert_post(array(
    'post_title' => 'T-Shirt Test',
    'post_type' => 'winshirt_mockup',
    'post_status' => 'publish',
    'post_content' => ''
));

if ($mockup_id) {
    echo "Mockup créé avec l'ID: {$mockup_id}<br>";
    
    // Ajouter une couleur par défaut
    $colors = array(
        'color_1' => array(
            'name' => 'Blanc',
            'hex' => '#FFFFFF',
            'front' => 'https://via.placeholder.com/400x500/FFFFFF/000000?text=RECTO',
            'back' => 'https://via.placeholder.com/400x500/FFFFFF/000000?text=VERSO'
        )
    );
    
    update_post_meta($mockup_id, '_mockup_colors', $colors);
    update_post_meta($mockup_id, '_default_color', 'color_1');
    
    // Ajouter des zones de test
    $zones = array(
        'zone_1' => array(
            'id' => 'zone_1',
            'name' => 'Logo Poitrine',
            'side' => 'front',
            'x' => 30,
            'y' => 25,
            'width' => 40,
            'height' => 30,
            'price' => 5.0
        ),
        'zone_2' => array(
            'id' => 'zone_2',
            'name' => 'Dos',
            'side' => 'back',
            'x' => 20,
            'y' => 20,
            'width' => 60,
            'height' => 50,
            'price' => 10.0
        )
    );
    
    update_post_meta($mockup_id, '_zones', $zones);
    
    echo "Couleurs et zones ajoutées<br>";
} else {
    echo "Erreur lors de la création du mockup<br>";
}

// 3. Vérifier l'intégration WooCommerce
echo '<h2>3. Test WooCommerce...</h2>';

if (class_exists('WooCommerce')) {
    echo "✓ WooCommerce détecté<br>";
    
    // Créer un produit de test
    $product_id = wp_insert_post(array(
        'post_title' => 'T-Shirt Personnalisable Test',
        'post_type' => 'product',
        'post_status' => 'publish'
    ));
    
    if ($product_id) {
        // Configurer comme produit simple
        wp_set_object_terms($product_id, 'simple', 'product_type');
        update_post_meta($product_id, '_regular_price', '25');
        update_post_meta($product_id, '_price', '25');
        
        // Activer la personnalisation
        update_post_meta($product_id, '_winshirt_customizable', '1');
        update_post_meta($product_id, '_winshirt_mockup_id', $mockup_id);
        
        echo "✓ Produit test créé (ID: {$product_id}) avec personnalisation activée<br>";
    }
} else {
    echo "⚠ WooCommerce non détecté<br>";
}

// 4. Forcer le rechargement des assets
echo '<h2>4. Rechargement des assets...</h2>';

// Supprimer les caches
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "✓ Cache vidé<br>";
}

// Flush des règles de réécriture
flush_rewrite_rules();
echo "✓ Règles de réécriture mises à jour<br>";

echo '<h2>✅ Réparation terminée !</h2>';
echo '<p><strong>Actions à faire maintenant :</strong></p>';
echo '<ol>';
echo '<li>Retourner dans l\'admin WordPress</li>';
echo '<li>Aller dans WinShirt → Mockups</li>';
echo '<li>Éditer le "T-Shirt Test"</li>';
echo '<li>Tester l\'ajout de zones</li>';
echo '<li>Aller sur le produit "T-Shirt Personnalisable Test" côté front</li>';
echo '</ol>';

echo '<p><a href="' . admin_url('admin.php?page=winshirt') . '">→ Retourner au Dashboard WinShirt</a></p>';
?>
