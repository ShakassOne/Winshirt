<?php
/**
 * Shortcode [winshirt_lotteries]
 * Author: Shakass Communication
 * Description: Récupère les loteries et délègue le rendu aux layouts (dont "diagonal").
 */

if (!defined('ABSPATH')) exit;

/**
 * Enregistre le shortcode à l'init.
 */
add_action('init', function () {
    add_shortcode('winshirt_lotteries', 'winshirt_lotteries_shortcode_cb');
});

/**
 * Shortcode handler.
 *
 * Usage :
 *   [winshirt_lotteries layout="diagonal" posts_per_page="10" category="" columns="4" gap="24"]
 *
 * Attributs :
 * - layout          : "diagonal" (ou défaut: "grid")
 * - posts_per_page  : nombre d'éléments (défaut 10)
 * - category        : slug de catégorie (si vous en utilisez)
 * - columns, gap    : uniquement pour le fallback "grid"
 */
function winshirt_lotteries_shortcode_cb($atts = [], $content = null, $tag = '') {
    $atts = shortcode_atts([
        'layout'         => 'grid',
        'posts_per_page' => 10,
        'category'       => '',
        'columns'        => 4,
        'gap'            => 24,
    ], $atts, $tag);

    // ——————————————————————————————————————————
    // 1) Requête des items
    // Adapte le post_type/ taxonomies si besoin côté site.
    $args = [
        'post_type'      => 'winshirt_lottery',
        'post_status'    => 'publish',
        'posts_per_page' => intval($atts['posts_per_page']),
        'no_found_rows'  => true,
    ];

    // Si une taxonomie "category" est utilisée pour ce CPT
    if (!empty($atts['category'])) {
        $args['tax_query'] = [[
            'taxonomy' => 'category',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($atts['category']),
        ]];
    }

    $q = new WP_Query($args);

    $items = [];
    $i = 0;
    if ($q->have_posts()) {
        while ($q->have_posts()) {
            $q->the_post(); $i++;
            $items[] = [
                'title'     => get_the_title(),
                'permalink' => get_permalink(),
                'image'     => get_the_post_thumbnail_url(get_the_ID(), 'large') ?: WINSHIRT_PLUGIN_URL . 'assets/placeholder.jpg',
                'num'       => str_pad((string)$i, 2, '0', STR_PAD_LEFT),
            ];
        }
        wp_reset_postdata();
    }
    // ——————————————————————————————————————————

    // 2) Dispatch selon le layout
    $layout = strtolower(trim($atts['layout']));

    if ($layout === 'diagonal') {
        // Appelle la fonction fournie dans includes/diagonal-layout.php
        if (!function_exists('winshirt_lotteries_render_diagonal')) {
            return '<div class="winshirt-diagonal-empty">Layout "diagonal" indisponible (fichier manquant).</div>';
        }
        return winshirt_lotteries_render_diagonal($items);
    }

    // 3) Fallback: grille simple (si layout autre que "diagonal")
    $cols = max(1, intval($atts['columns']));
    $gap  = max(0, intval($atts['gap']));

    ob_start(); ?>
    <div class="winshirt-grid" style="display:grid;grid-template-columns:repeat(<?php echo esc_attr($cols); ?>,1fr);gap:<?php echo esc_attr($gap); ?>px;">
        <?php if (empty($items)): ?>
            <div style="grid-column:1/-1;padding:16px;border:1px dashed #ccc;border-radius:10px;text-align:center;color:#666;">
                Aucune loterie à afficher.
            </div>
        <?php else: ?>
            <?php foreach ($items as $it): ?>
                <a href="<?php echo esc_url($it['permalink']); ?>" style="display:block;border-radius:12px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,.08);">
                    <img src="<?php echo esc_url($it['image']); ?>" alt="<?php echo esc_attr($it['title']); ?>" style="display:block;width:100%;height:220px;object-fit:cover;">
                    <div style="padding:10px 12px;font-weight:600;"><?php echo esc_html($it['title']); ?></div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
