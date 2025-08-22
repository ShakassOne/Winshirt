<?php
/**
 * Plugin Name: Winshirt — by Shakass Communication
 * Plugin URI:  https://winshirt.fr
 * Description: Shortcodes & layouts Winshirt (dont le carrousel diagonal).
 * Version:     1.0.1
 * Author:      Shakass Communication
 * Author URI:  https://shakass.fr
 * License:     GPLv2 or later
 * Text Domain: winshirt
 */

if (!defined('ABSPATH')) exit;

// ------------------------------------------------------------------
// Chemins plugin
// ------------------------------------------------------------------
if (!defined('WINSHIRT_PLUGIN_FILE')) define('WINSHIRT_PLUGIN_FILE', __FILE__);
if (!defined('WINSHIRT_PLUGIN_URL'))  define('WINSHIRT_PLUGIN_URL', plugin_dir_url(__FILE__));
if (!defined('WINSHIRT_PLUGIN_PATH')) define('WINSHIRT_PLUGIN_PATH', plugin_dir_path(__FILE__));

// ------------------------------------------------------------------
// Include layout diagonal si dispo
// ------------------------------------------------------------------
$__diag_file = WINSHIRT_PLUGIN_PATH . 'includes/diagonal-layout.php';
if (file_exists($__diag_file)) {
    require_once $__diag_file;
}

/**
 * Normalise les alias de layout utilisés dans tes shortcodes
 * - "slider", "carousel", "diag" ⇒ "diagonal"
 * - "grid", "masonry" gardés tels quels (masonry retombe sur grid si pas implémenté ici)
 */
function winshirt_normalize_layout($layout) {
    $k = strtolower(trim((string)$layout));
    if (in_array($k, ['diagonal', 'diag', 'slider', 'diagonal-slider', 'carousel'], true)) {
        return 'diagonal';
    }
    if ($k === 'masonry') return 'masonry';
    return 'grid';
}

/**
 * Construit une liste d’items standardisés depuis la WP_Query de tes loteries
 * Chaque item = title, permalink, image, num
 * → Filtrable via 'winshirt_lotteries_items'
 */
function winshirt_build_items_from_query($count = 10, $atts = []) {
    $count = max(1, (int)$count);

    // Ajuste si ton CPT porte un autre nom
    $q = new WP_Query([
        'post_type'      => 'winshirt_lottery',
        'posts_per_page' => $count,
        'post_status'    => 'publish',
    ]);

    $items = [];
    while ($q->have_posts()) {
        $q->the_post();
        $items[] = [
            'id'        => get_the_ID(),
            'title'     => get_the_title(),
            'permalink' => get_permalink(),
            'image'     => get_the_post_thumbnail_url(get_the_ID(), 'large') ?: WINSHIRT_PLUGIN_URL . 'assets/placeholder.jpg',
            'num'       => str_pad((string)($q->current_post + 1), 2, '0', STR_PAD_LEFT),
        ];
    }
    wp_reset_postdata();

    return apply_filters('winshirt_lotteries_items', $items, $atts);
}

/**
 * Rendu simple Grid (fallback propre)
 */
function winshirt_render_grid($items, $atts) {
    $columns = max(1, (int)($atts['columns'] ?? 4));
    $gap     = max(0, (int)($atts['gap'] ?? 16));

    ob_start(); ?>
    <div class="winshirt-grid"
         style="display:grid;grid-template-columns:repeat(<?php echo (int)$columns; ?>, minmax(0,1fr));gap:<?php echo (int)$gap; ?>px;">
        <?php foreach ($items as $it): ?>
            <a class="winshirt-card" href="<?php echo esc_url($it['permalink']); ?>"
               style="text-decoration:none;color:inherit;border:1px solid #eee;border-radius:12px;overflow:hidden;background:#fff;display:block;">
                <img loading="lazy" src="<?php echo esc_url($it['image']); ?>"
                     alt="<?php echo esc_attr($it['title']); ?>"
                     style="width:100%;height:200px;object-fit:cover;display:block;">
                <div style="padding:12px 14px;font-weight:600;"><?php echo esc_html($it['title']); ?></div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Shortcode principal
 *  - [winshirt_lotteries layout="diagonal|slider|grid|masonry" count="10" columns="4" gap="24" show_timer="1" show_count="1" status="all"]
 */
function winshirt_lotteries_shortcode($atts = []) {
    $atts = shortcode_atts([
        'layout'      => 'grid',
        'count'       => 10,
        'columns'     => 4,
        'gap'         => 24,
        'show_timer'  => 0,
        'show_count'  => 0,
        'status'      => 'all',
    ], $atts, 'winshirt_lotteries');

    $atts['layout'] = winshirt_normalize_layout($atts['layout']);

    // Récup items
    $items = winshirt_build_items_from_query((int)$atts['count'], $atts);

    // Rendu DIAGONAL si dispo (fonction OU classe)
    if ($atts['layout'] === 'diagonal') {
        if (function_exists('winshirt_lotteries_render_diagonal')) {
            return winshirt_lotteries_render_diagonal($items, $atts);
        }
        if (class_exists('Winshirt_Diagonal_Layout') && method_exists('Winshirt_Diagonal_Layout', 'render')) {
            return Winshirt_Diagonal_Layout::render($items, $atts);
        }
        // Si pas trouvé, on tombe en grid
    }

    // Rendu MASONRY → fallback grid pour l’instant
    if ($atts['layout'] === 'masonry') {
        return winshirt_render_grid($items, $atts);
    }

    // Rendu GRID par défaut
    return winshirt_render_grid($items, $atts);
}
add_shortcode('winshirt_lotteries', 'winshirt_lotteries_shortcode');

/* Note: les assets diagonal (CSS/JS) sont gérés dans includes/diagonal-layout.php */
