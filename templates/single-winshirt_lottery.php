<?php
/** Template single loterie robuste (aucune fonction avancée qui pourrait fataler). */
if ( ! defined('ABSPATH') ) exit;
get_header();

$id      = get_the_ID();
$title   = get_the_title($id);
$thumb   = get_the_post_thumbnail($id,'xl',['class'=>'ws-hero-img']);
$excerpt = has_excerpt($id) ? get_the_excerpt($id) : '';
$content = apply_filters('the_content', get_post_field('post_content',$id));

$end     = get_post_meta($id,'_ws_lottery_end',true);
$end_ts  = $end ? strtotime($end) : 0;
$count   = (int) get_post_meta($id,'_ws_lottery_count',true);
$goal    = (int) get_post_meta($id,'_ws_lottery_goal',true);
$value   = (string) get_post_meta($id,'_ws_lottery_value',true);
$terms   = (string) get_post_meta($id,'_ws_lottery_terms_url',true);
$feat    = get_post_meta($id,'_ws_lottery_featured',true)==='yes';
$over    = $end_ts && $end_ts < current_time('timestamp');
?>
<main class="ws-wrap">
  <article class="ws-hero">
    <div class="ws-hero-media">
      <?php echo $thumb ?: '<div class="ws-hero-ph" style="height:360px;background:#111"></div>'; ?>
      <div class="ws-hero-badges">
        <span class="ws-badge <?php echo $over?'ws-badge-ended':'ws-badge-active'; ?>"><?php echo $over?esc_html__('Terminé','winshirt'):esc_html__('Active','winshirt'); ?></span>
        <?php if ($feat): ?><span class="ws-badge ws-badge-featured"><?php esc_html_e('En vedette','winshirt'); ?></span><?php endif; ?>
      </div>
      <div class="ws-hero-title">
        <h1><?php echo esc_html($title); ?></h1>
        <?php if ($value): ?><p class="ws-hero-sub"><?php echo esc_html(sprintf(__('Valeur: %s','winshirt'),$value)); ?></p><?php endif; ?>
      </div>
    </div>

    <div class="ws-hero-metrics" data-end="<?php echo (int)$end_ts; ?>" data-over="<?php echo $over?'1':'0'; ?>">
      <div class="m"><span data-u="d">--</span><label><?php esc_html_e('Jours','winshirt'); ?></label></div>
      <div class="m"><span data-u="h">--</span><label><?php esc_html_e('Heures','winshirt'); ?></label></div>
      <div class="m"><span data-u="m">--</span><label><?php esc_html_e('Minutes','winshirt'); ?></label></div>
      <div class="m"><span data-u="s">--</span><label><?php esc_html_e('Secondes','winshirt'); ?></label></div>
    </div>

    <div class="ws-hero-bottom">
      <div class="ws-stats">
        <span><?php echo esc_html(sprintf(_n('%d participant','%d participants',$count,'winshirt'),$count)); ?></span>
        <?php if ($goal): ?><span>— <?php echo esc_html(sprintf(__('Objectif: %d','winshirt'),$goal)); ?></span><?php endif; ?>
      </div>
      <?php if ($goal): $progress = max(0,min(100,$count/$goal*100)); ?>
        <div class="ws-progress"><div class="ws-progress-bar" style="width:<?php echo esc_attr(round($progress,1)); ?>%"></div></div>
      <?php endif; ?>
      <div class="ws-draw">
        <span>📅 <?php echo $end_ts ? esc_html(sprintf(__('Tirage le %s','winshirt'), date_i18n('d/m/Y',$end_ts))) : esc_html__('Date de tirage à venir','winshirt'); ?></span>
        <?php if ($over): ?><button class="ws-btn ws-btn-disabled" disabled><?php esc_html_e('Terminé','winshirt'); ?></button>
        <?php else: ?><a class="ws-btn" href="#ws-form"><?php esc_html_e('Participer','winshirt'); ?></a><?php endif; ?>
      </div>
    </div>
  </article>

  <section class="ws-grid-2">
    <div class="card">
      <?php if ($excerpt): ?><p class="ws-intro"><?php echo esc_html($excerpt); ?></p><?php endif; ?>
      <div class="ws-content"><?php echo $content; ?></div>
      <?php if ($terms): ?><p class="ws-terms"><a href="<?php echo esc_url($terms); ?>" target="_blank" rel="noopener"><?php esc_html_e('Consulter le règlement','winshirt'); ?></a></p><?php endif; ?>
    </div>
    <aside id="ws-form" class="card">
      <?php echo $over ? '<div class="ws-ended">'.esc_html__('Cette loterie est terminée.','winshirt').'</div>' : do_shortcode('[winshirt_lottery_form id="'.$id.'"]'); ?>
    </aside>
  </section>
</main>
<?php get_footer(); ?>
