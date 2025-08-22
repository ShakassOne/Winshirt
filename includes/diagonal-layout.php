<?php
/**
 * Diagonal Carousel layout (Winshirt)
 * Path-sensitive: uses WINSHIRT_PLUGIN_URL|PATH for assets
 */

if (!defined('ABSPATH')) exit;

/**
 * Enqueue assets uniquement pour le layout diagonal
 */
function winshirt_diagonal_enqueue_assets() {
    // évite double-enqueue
    if (wp_style_is('winshirt-diagonal', 'enqueued')) return;

    wp_enqueue_style(
        'winshirt-diagonal',
        WINSHIRT_PLUGIN_URL . 'assets/css/diagonal.css',
        [],
        '1.0'
    );

    wp_enqueue_script(
        'winshirt-diagonal',
        WINSHIRT_PLUGIN_URL . 'assets/js/diagonal.js',
        [],
        '1.0',
        true
    );
}

/**
 * Rendu HTML du carrousel diagonal.
 * $items : tableau d’items (['title','image','permalink','num'])
 * Si $items est null, on tente une requête de secours (10 derniers).
 */
function winshirt_lotteries_render_diagonal(array $items = null, array $atts = []) {
    // Si ton shortcode principal ne passe rien -> fallback
    if ($items === null) {
        $q = new WP_Query([
            'post_type'      => 'winshirt_lottery',
            'posts_per_page' => 10,
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
    }

    if (empty($items)) {
        return '<div class="winshirt-diagonal-empty">Aucune loterie à afficher.</div>';
    }

    // Enqueue CSS/JS
    winshirt_diagonal_enqueue_assets();

    // ID unique pour isoler chaque instance
    $uid = 'winshirt-diagonal-' . wp_generate_uuid4();

    ob_start(); ?>
    <div id="<?php echo esc_attr($uid); ?>" class="winshirt-diagonal">
        <div class="carousel">
            <?php foreach ($items as $i => $it): ?>
                <div class="carousel-item" style="--opacity:1"><!-- fallback visible si JS inactif -->
                    <a class="carousel-box" href="<?php echo esc_url($it['permalink']); ?>">
                        <div class="title"><?php echo esc_html($it['title']); ?></div>
                        <?php if (!empty($it['num'])): ?>
                            <div class="num"><?php echo esc_html($it['num']); ?></div>
                        <?php endif; ?>
                        <img loading="lazy" src="<?php echo esc_url($it['image']); ?>" alt="<?php echo esc_attr($it['title']); ?>" />
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- cursors pour l’effet -->
        <div class="cursor"></div>
        <div class="cursor cursor2"></div>
    </div>
    <?php
    return ob_get_clean();
}
