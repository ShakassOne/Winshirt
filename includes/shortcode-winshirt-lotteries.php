<?php
if ( ! defined('ABSPATH') ) exit;

function winshirt_query_lotteries( $args = [] ){
    $cat = get_option('winshirt_lottery_category','loterie');
    $defaults = [
        'post_type'           => 'post',
        'posts_per_page'      => intval($args['per_page'] ?? 12),
        'ignore_sticky_posts' => true,
        'orderby'             => sanitize_text_field($args['orderby'] ?? 'date'),
        'order'               => (strtoupper($args['order'] ?? 'DESC') === 'ASC') ? 'ASC' : 'DESC',
        'tax_query'           => [
            [
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => $cat,
            ]
        ],
    ];
    return new WP_Query($defaults);
}

function winshirt_build_items_from_wpq( WP_Query $q ){
    $items = [];
    if ( $q->have_posts() ) {
        while ( $q->have_posts() ) { $q->the_post();
            $items[] = [
                'title' => get_the_title(),
                'url'   => get_permalink(),
                'img'   => get_the_post_thumbnail_url(get_the_ID(),'medium_large'),
                'date'  => get_the_date(),
                'excerpt' => wp_strip_all_tags(get_the_excerpt() ?: wp_trim_words(get_the_content(), 18)),
            ];
        }
        wp_reset_postdata();
    }
    return $items;
}

function winshirt_render_grid_layout( array $items, array $atts ){
    WinShirt_Assets::need_front();
    $columns = max(1, intval($atts['columns'] ?? 3));
    $gap     = max(0, intval($atts['gap'] ?? 16));
    ob_start(); ?>
    <div class="ws-grid" style="--ws-cols:<?php echo esc_attr($columns); ?>;--ws-gap:<?php echo esc_attr($gap); ?>px">
      <?php foreach($items as $item): ?>
        <article class="ws-card">
          <?php include WINSHIRT_DIR.'templates/lottery-card.php'; ?>
        </article>
      <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

function winshirt_render_masonry_layout( array $items, array $atts ){
    WinShirt_Assets::need_front();
    $columns = max(1, intval($atts['columns'] ?? 3));
    $gap     = max(0, intval($atts['gap'] ?? 16));
    ob_start(); ?>
    <div class="ws-masonry" style="--ws-cols:<?php echo esc_attr($columns); ?>;--ws-gap:<?php echo esc_attr($gap); ?>px">
      <?php foreach($items as $item): ?>
        <article class="ws-card">
          <?php include WINSHIRT_DIR.'templates/lottery-card.php'; ?>
        </article>
      <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

function winshirt_lotteries_shortcode( $atts ){
    $atts = shortcode_atts([
        'layout'   => 'grid',     // grid|masonry|diagonal
        'columns'  => 4,
        'gap'      => 24,
        'per_page' => 12,
        'orderby'  => 'date',
        'order'    => 'DESC',
    ], $atts, 'winshirt_lotteries');

    $q = winshirt_query_lotteries($atts);
    $items = winshirt_build_items_from_wpq($q);

    if ( empty($items) ) {
        WinShirt_Assets::need_front();
        return '<p>Aucune loterie trouvée. Créez des <strong>Articles</strong> dans la catégorie <code>'.esc_html(get_option('winshirt_lottery_category','loterie')).'</code>.</p>';
    }

    if ( $atts['layout'] === 'diagonal' ) {
        return winshirt_render_diagonal($items, $atts);
    }
    if ( $atts['layout'] === 'masonry' ) {
        return winshirt_render_masonry_layout($items, $atts);
    }
    return winshirt_render_grid_layout($items, $atts);
}
add_shortcode('winshirt_lotteries', 'winshirt_lotteries_shortcode');
