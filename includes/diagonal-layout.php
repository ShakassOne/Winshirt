<?php
if ( ! defined('ABSPATH') ) exit;

function winshirt_render_diagonal( array $items, array $atts ){
    WinShirt_Assets::enqueue_diagonal();

    $columns = max(1, intval($atts['columns'] ?? 4));
    $gap     = max(0, intval($atts['gap'] ?? 24));

    ob_start(); ?>
    <div class="ws-diagonal" data-columns="<?php echo esc_attr($columns); ?>" data-gap="<?php echo esc_attr($gap); ?>">
      <div class="ws-track" style="--ws-gap:<?php echo esc_attr($gap); ?>px">
        <?php foreach($items as $item): ?>
          <article class="ws-card">
            <?php include WINSHIRT_DIR.'templates/lottery-card.php'; ?>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="ws-nav">
        <button type="button" class="ws-btn" data-prev>&larr;</button>
        <button type="button" class="ws-btn" data-next>&rarr;</button>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
