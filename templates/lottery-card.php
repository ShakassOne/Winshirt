<?php
if ( ! defined('ABSPATH') ) exit;
/** @var array $item */
$img = $item['img'] ?: (function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src() : 'https://via.placeholder.com/600x400?text=WinShirt');
?>
<a class="ws-thumb" href="<?php echo esc_url($item['url']); ?>">
  <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($item['title']); ?>">
</a>
<div class="ws-body">
  <h3><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a></h3>
  <div class="ws-meta"><?php echo esc_html($item['date']); ?></div>
  <p><?php echo esc_html($item['excerpt']); ?></p>
</div>
<div class="ws-actions">
  <a class="ws-btn" href="<?php echo esc_url($item['url']); ?>">Voir</a>
</div>
