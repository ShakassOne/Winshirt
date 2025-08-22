<?php
/**
 * Diagonal Carousel layout (Winshirt)
 * Author: Shakass Communication
 * Description: Rendu "diagonal" avec fallback gracieux (grille visible sans JS)
 */

if (!defined('ABSPATH')) exit;

/**
 * Enqueue assets uniquement pour ce layout
 */
function winshirt_diagonal_enqueue_assets() {
    if (wp_style_is('winshirt-diagonal', 'enqueued')) return;

    wp_enqueue_style(
        'winshirt-diagonal',
        WINSHIRT_PLUGIN_URL . 'assets/css/diagonal.css',
        [],
        '1.1'
    );

    wp_enqueue_script(
        'winshirt-diagonal',
        WINSHIRT_PLUGIN_URL . 'assets/js/diagonal.js',
        [],
        '1.1',
        true
    );
}

/**
 * Fallback: tente de récupérer 10 éléments publiés.
 * Adapte le post_type ici si besoin.
 */
function winshirt_diagonal_fallback_items($max = 10) {
    $items = [];

    // 1) CPT attendu
    $q = new WP_Query([
        'post_type'      => 'winshirt_lottery',
        'posts_per_page' => $max,
        'post_status'    => 'publish',
        'no_found_rows'  => true,
    ]);

    if (!$q->have_posts()) {
        // 2) Fallback sur posts classiques
        $q = new WP_Query([
            'post_type'      => 'post',
            'posts_per_page' => $max,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
        ]);
    }

    $i = 0;
    while ($q->have_posts()) {
        $q->the_post();
        $i++;
        $items[] = [
            'title'     => get_the_title(),
            'permalink' => get_permalink(),
            'image'     => get_the_post_thumbnail_url(get_the_ID(), 'large') ?: WINSHIRT_PLUGIN_URL . 'assets/placeholder.jpg',
            'num'       => str_pad((string)$i, 2, '0', STR_PAD_LEFT),
        ];
    }
    wp_reset_postdata();

    return $items;
}

/**
 * Rendu du layout diagonal.
 * $items : tableau d’items (['title','image','permalink','num'])
 * Si null => fallback automatique.
 */
function winshirt_lotteries_render_diagonal(array $items = null) {
    if ($items === null) {
        $items = winshirt_diagonal_fallback_items(10);
    }

    // Enqueue CSS/JS
    winshirt_diagonal_enqueue_assets();

    $uid   = 'winshirt-diagonal-' . wp_generate_uuid4();
    $count = is_array($items) ? count($items) : 0;

    ob_start(); ?>
    <div id="<?php echo esc_attr($uid); ?>"
         class="winshirt-diagonal"
         data-uid="<?php echo esc_attr($uid); ?>"
         data-count="<?php echo esc_attr($count); ?>">
        <?php if ($count === 0): ?>
            <div class="winshirt-diagonal-empty">
                Aucune loterie à afficher.
            </div>
        <?php else: ?>
            <div class="carousel" aria-live="polite">
                <?php foreach ($items as $it): ?>
                    <div class="carousel-item">
                        <a class="carousel-box" href="<?php echo esc_url($it['permalink']); ?>">
                            <div class="title"><?php echo esc_html($it['title']); ?></div>
                            <?php if (!empty($it['num'])): ?>
                                <div class="num"><?php echo esc_html($it['num']); ?></div>
                            <?php endif; ?>
                            <img loading="lazy"
                                 src="<?php echo esc_url($it['image']); ?>"
                                 alt="<?php echo esc_attr($it['title']); ?>" />
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="cursor" aria-hidden="true"></div>
            <div class="cursor cursor2" aria-hidden="true"></div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * ⚠️ Intégration dans TON shortcode existant [winshirt_lotteries]
 * Exemple minimal à l'intérieur de ta fonction de rendu:
 *
 * if ( ( $atts['layout'] ?? '' ) === 'diagonal' ) {
 *     // Si tu as déjà $items calculés, passe-les ici, sinon laisse null:
 *     return winshirt_lotteries_render_diagonal( $items ?? null );
 * }
 */
