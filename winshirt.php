<?php
/**
 * Plugin Name: Winshirt
 * Description: Shortcodes & layouts Winshirt (dont le carrousel diagonal).
 * Version:     1.0.0
 * Author:      Winshirt
 * License:     GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

// ------------------------------------------------------
// Constantes de chemins (root plugin)
// ------------------------------------------------------
if (!defined('WINSHIRT_PLUGIN_FILE')) define('WINSHIRT_PLUGIN_FILE', __FILE__);
if (!defined('WINSHIRT_PLUGIN_URL'))  define('WINSHIRT_PLUGIN_URL', plugin_dir_url(__FILE__));
if (!defined('WINSHIRT_PLUGIN_PATH')) define('WINSHIRT_PLUGIN_PATH', plugin_dir_path(__FILE__));

// ------------------------------------------------------
// Inclusions
// ------------------------------------------------------
// ⚠️ Ton fichier existe déjà: includes/diagonal-layout.php
// Il expose la fonction: winshirt_lotteries_render_diagonal( array $items = null )
require_once WINSHIRT_PLUGIN_PATH . 'includes/diagonal-layout.php';

// ------------------------------------------------------
// Outils communs (facultatifs)
// ------------------------------------------------------
/**
 * Construit des items standardisés à partir d'une WP_Query (CPT: winshirt_lottery).
 * Chaque item a: title, permalink, image, num
 */
function winshirt_build_items_from_query($count = 10) {
    $q = new WP_Query([
        'post_type'      => 'winshirt_lottery',
        'posts_per_page' => $count,
        'post_status'    => 'publish',
    ]);
    $items = [];
    while ($q->have_posts()) {
        $q->the_post();
        $items[] = [
            'title'     => get_the_title(),
            'permalink' => get_permalink(),
            'image'     => get_the_post_thumbnail_url(get_the_ID(), 'large') ?: WINSHIRT_PLUGIN_URL . 'assets/placeholder.jpg',
            'num'       => str_pad((string)($q->current_post + 1), 2, '0', STR_PAD_LEFT),
        ];
    }
    wp_reset_postdata();
    return $items;
}

// ------------------------------------------------------
// Shortcode principal: [winshirt_lotteries layout="..."]
// ------------------------------------------------------
/**
 * Attributs acceptés:
 *  - layout: "diagonal" | "grid" | "masonry" | etc. (par défaut: grid)
 *  - count : nombre d'éléments (par défaut: 10)
 */
function winshirt_lotteries_shortcode($atts) {
    $atts = shortcode_atts([
        'layout' => 'grid',
        'count'  => 10,
    ], $atts, 'winshirt_lotteries');

    $layout = strtolower(trim($atts['layout']));
    $count  = max(1, intval($atts['count']));

    // Tu as déjà mis le layout diagonal dans "includes/diagonal-layout.php"
    if ($layout === 'diagonal') {
        // Si tu as ton propre pipeline pour générer $items, remplace la ligne suivante :
        $items = winshirt_build_items_from_query($count);
        return winshirt_lotteries_render_diagonal($items);
    }

    // Placeholder simple pour les autres layouts (à remplacer par tes rendus existants)
    if ($layout === 'grid') {
        $items = winshirt_build_items_from_query($count);
        ob_start(); ?>
        <div class="winshirt-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;">
            <?php foreach ($items as $it): ?>
                <a href="<?php echo esc_url($it['permalink']); ?>" style="text-decoration:none;color:inherit;border:1px solid #eee;border-radius:8px;overflow:hidden;display:block;">
                    <img loading="lazy" src="<?php echo esc_url($it['image']); ?>" alt="<?php echo esc_attr($it['title']); ?>" style="width:100%;height:160px;object-fit:cover;display:block;">
                    <div style="padding:10px;font-weight:600;"><?php echo esc_html($it['title']); ?></div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // Si layout inconnu
    return '<div class="winshirt-empty">Layout inconnu : <code>' . esc_html($layout) . '</code></div>';
}
add_shortcode('winshirt_lotteries', 'winshirt_lotteries_shortcode');

// ------------------------------------------------------
// Admin: petite notice (facultatif)
// ------------------------------------------------------
function winshirt_admin_notice_wrong_theme() {
    // Ex. afficher une notice si besoin — laissé vide volontairement
}
