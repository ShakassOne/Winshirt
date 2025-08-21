<?php
/**
 * WinShirt — Template minimal SAFE (sans slider)
 * Rend un grid basique pour les shortcodes, sans dépendances JS/CSS.
 * Objectif: neutraliser les plantages côtés front tout en affichant quelque chose.
 */
namespace WinShirt;

if (!defined('ABSPATH')) { exit; }

class Lottery_Template
{
    /** Singleton (pour compat init()) */
    private static $instance = null;
    public static function instance(): self {
        return self::$instance ?? (self::$instance = new self());
    }

    /** Hook placeholder (rien d’obligatoire ici) */
    public function init(): void {
        // Pas d’enqueue d’assets ici pour éviter tout conflit.
        // On laisse le bootstrap gérer les shortcodes (déjà enregistrés côté plugin).
    }

    /**
     * Rendu liste — grid minimal
     * @param array $atts
     * @return string
     */
    public static function render_list(array $atts): string
    {
        // Coercition défensive des attributs (évite les notices)
        $status     = isset($atts['status'])      ? (string)$atts['status']       : 'all';
        $limit      = isset($atts['limit'])       ? (int)$atts['limit']           : 12;
        $columns    = isset($atts['columns'])     ? max(1, (int)$atts['columns']) : 3;
        $gap        = isset($atts['gap'])         ? (int)$atts['gap']             : 24;
        $showTimer  = !empty($atts['show_timer']);
        $showCount  = !empty($atts['show_count']);

        // IMPORTANT : on ignore totalement layout=slider/diagonal → grid only
        $layout = 'grid';

        // Option “source” ultra-safe : on essaie de lister des posts du CPT s’il existe.
        // On évite tout fatal si le CPT n’existe pas.
        $cpt_candidates = ['ws-lottery','winshirt_lottery','lottery'];
        $post_type = null;
        foreach ($cpt_candidates as $cpt) {
            if (function_exists('post_type_exists') && post_type_exists($cpt)) { $post_type = $cpt; break; }
        }

        $items = [];
        if ($post_type) {
            // Query défensive
            $q = new \WP_Query([
                'post_type'      => $post_type,
                'posts_per_page' => $limit,
                'post_status'    => ['publish'],
                'no_found_rows'  => true,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ]);
            if ($q->have_posts()) {
                while ($q->have_posts()) {
                    $q->the_post();
                    $items[] = [
                        'id'    => get_the_ID(),
                        'title' => get_the_title(),
                        'perma' => get_permalink(),
                        'thumb' => get_the_post_thumbnail_url(get_the_ID(), 'large') ?: '',
                        'meta'  => [
                            'status' => $status,
                            'count'  => null, // tu pluggeras plus tard
                            'ends'   => null, // tu pluggeras plus tard
                        ],
                    ];
                }
                wp_reset_postdata();
            }
        }

        // Si aucune source valide, on affiche un placeholder propre
        if (empty($items)) {
            $items = [
                [
                    'id'    => 0,
                    'title' => 'Exemple de loterie',
                    'perma' => '#',
                    'thumb' => '',
                    'meta'  => ['status' => $status, 'count' => 0, 'ends' => null],
                ]
            ];
        }

        // Rendu HTML minimal, aucun JS
        ob_start(); ?>
        <div class="winshirt-lotteries grid-safe" data-layout="<?php echo esc_attr($layout); ?>"
             style="display:grid;grid-template-columns:repeat(<?php echo (int)$columns; ?>,minmax(0,1fr));gap:<?php echo (int)$gap; ?>px;">
            <?php foreach ($items as $it): ?>
                <article class="ws-card" style="border:1px solid #eee;border-radius:12px;overflow:hidden;background:#fff;">
                    <?php if (!empty($it['thumb'])): ?>
                        <a href="<?php echo esc_url($it['perma']); ?>" class="ws-media" style="display:block;aspect-ratio:16/9;overflow:hidden;background:#f6f7f9;">
                            <img src="<?php echo esc_url($it['thumb']); ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">
                        </a>
                    <?php else: ?>
                        <a href="<?php echo esc_url($it['perma']); ?>" class="ws-media" style="display:block;aspect-ratio:16/9;background:linear-gradient(135deg,#f3f4f6,#e5e7eb);"></a>
                    <?php endif; ?>
                    <div class="ws-body" style="padding:12px 14px 16px;">
                        <h3 class="ws-title" style="margin:0 0 6px;font:600 16px/1.35 system-ui, -apple-system, Segoe UI, Roboto, sans-serif;">
                            <a href="<?php echo esc_url($it['perma']); ?>" style="text-decoration:none;color:#111;"><?php echo esc_html($it['title']); ?></a>
                        </h3>
                        <div class="ws-meta" style="opacity:.7;font:400 13px/1.4 system-ui, -apple-system, Segoe UI, Roboto, sans-serif;">
                            <?php if ($showCount): ?>
                                <span class="ws-count">Participants : <?php echo isset($it['meta']['count']) ? (int)$it['meta']['count'] : 0; ?></span>
                            <?php endif; ?>
                            <?php if ($showTimer): ?>
                                <span class="ws-sep"> · </span>
                                <span class="ws-timer">Statut : <?php echo esc_html($it['meta']['status']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
        return (string)ob_get_clean();
    }

    /**
     * Rendu carte unique minimal
     * @param array $atts
     * @return string
     */
    public static function render_card(array $atts): string
    {
        $id        = isset($atts['id']) ? (int)$atts['id'] : 0;
        $showTimer = !empty($atts['show_timer']);
        $showCount = !empty($atts['show_count']);

        $title = $id ? get_the_title($id) : 'Loterie';
        $perma = $id ? get_permalink($id) : '#';
        $thumb = $id ? (get_the_post_thumbnail_url($id, 'large') ?: '') : '';

        ob_start(); ?>
        <article class="winshirt-lottery-card safe"
                 style="border:1px solid #eee;border-radius:12px;overflow:hidden;background:#fff;max-width:560px">
            <?php if (!empty($thumb)): ?>
                <a href="<?php echo esc_url($perma); ?>" class="ws-media" style="display:block;aspect-ratio:16/9;overflow:hidden;background:#f6f7f9;">
                    <img src="<?php echo esc_url($thumb); ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">
                </a>
            <?php else: ?>
                <a href="<?php echo esc_url($perma); ?>" class="ws-media" style="display:block;aspect-ratio:16/9;background:linear-gradient(135deg,#f3f4f6,#e5e7eb);"></a>
            <?php endif; ?>
            <div class="ws-body" style="padding:12px 14px 16px;">
                <h3 class="ws-title" style="margin:0 0 6px;font:600 16px/1.35 system-ui, -apple-system, Segoe UI, Roboto, sans-serif;">
                    <a href="<?php echo esc_url($perma); ?>" style="text-decoration:none;color:#111;"><?php echo esc_html($title); ?></a>
                </h3>
                <div class="ws-meta" style="opacity:.7;font:400 13px/1.4 system-ui, -apple-system, Segoe UI, Roboto, sans-serif;">
                    <?php if ($showCount): ?><span>Participants : 0</span><?php endif; ?>
                    <?php if ($showTimer): ?><span class="ws-sep"> · </span><span>Statut : actif</span><?php endif; ?>
                </div>
            </div>
        </article>
        <?php
        return (string)ob_get_clean();
    }
}
