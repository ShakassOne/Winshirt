<?php
/**
 * WinShirt — Template stable (grid, masonry, diagonal, slider scroll-snap)
 * - Aucune dépendance externe (pas de Swiper).
 * - CSS/JS minimal inline, isolé, sans fatal possible.
 * - Diagonal orienté "bas droite → haut gauche" (RTL pour l'effet visuel).
 * - Mobile : 1 colonne pour grid/diagonal, 1 colonne CSS pour masonry.
 * - Source: CPT (ws-lottery|winshirt_lottery|lottery) sinon Articles (cat/tag loterie...).
 */
namespace WinShirt;

if (!defined('ABSPATH')) { exit; }

class Lottery_Template
{
    private static $instance = null;

    public static function instance(): self {
        return self::$instance ?? (self::$instance = new self());
    }

    public function init(): void {
        // Rien : inline CSS/JS pour éviter les soucis d’enqueue/dépendances.
    }

    /** ------ Public API utilisée par le bootstrap ------ */

    public static function render_list(array $atts): string {
        $a = shortcode_atts([
            'status'      => 'all',
            'featured'    => '0',
            'limit'       => '12',
            'layout'      => 'grid',   // grid | masonry | diagonal | slider
            'columns'     => '3',
            'gap'         => '24',
            'show_timer'  => '1',
            'show_count'  => '1',
            'autoplay'    => '0',      // ignoré (pas de lib)
            'speed'       => '600',    // ignoré (pas de lib)
            'loop'        => '0',      // ignoré (pas de lib)
        ], $atts, 'winshirt_lotteries');

        // Coercition défensive
        $layout    = self::coerce_layout($a['layout']);
        $limit     = max(1, (int) $a['limit']);
        $columns   = max(1, (int) $a['columns']);
        $gap       = max(0, (int) $a['gap']);
        $showTimer = !empty($a['show_timer']);
        $showCount = !empty($a['show_count']);

        // Récupération des items (CPT sinon posts)
        $items = self::fetch_items($limit, (string) $a['status'], (int) $a['featured']);

        // Placeholder si vide
        if (empty($items)) {
            $items = [[
                'id'    => 0,
                'title' => 'Exemple de loterie',
                'perma' => '#',
                'thumb' => '',
                'meta'  => ['status' => $a['status'], 'count' => 0, 'ends' => null],
            ]];
        }

        ob_start();
        self::print_base_css(); // injecte une seule fois

        $ns = 'wslt-'.wp_generate_password(6, false, false); // namespace isolant
        ?>
        <div class="winshirt-lotteries" data-wslt="<?php echo esc_attr($ns); ?>" data-layout="<?php echo esc_attr($layout); ?>">
            <?php
            switch ($layout) {
                case 'masonry':
                    self::render_masonry($items, $columns, $gap, $showTimer, $showCount);
                    break;
                case 'diagonal':
                    // Orientation voulue : "bas droite → haut gauche"
                    self::render_diagonal($items, $columns, $gap, $showTimer, $showCount, true /*rtl*/);
                    break;
                case 'slider':
                    self::render_slider($items, $gap, $showTimer, $showCount, $ns);
                    break;
                case 'grid':
                default:
                    self::render_grid($items, $columns, $gap, $showTimer, $showCount);
                    break;
            }
            ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public static function render_card(array $atts): string {
        $id        = isset($atts['id']) ? (int)$atts['id'] : 0;
        $showTimer = !empty($atts['show_timer']);
        $showCount = !empty($atts['show_count']);

        $title = $id ? get_the_title($id) : 'Loterie';
        $perma = $id ? get_permalink($id) : '#';
        $thumb = $id ? (get_the_post_thumbnail_url($id, 'large') ?: '') : '';

        ob_start();
        self::print_base_css();
        ?>
        <article class="wslt-card" style="max-width:560px">
            <?php self::render_media($thumb, $perma); ?>
            <div class="wslt-body">
                <h3 class="wslt-title"><a href="<?php echo esc_url($perma); ?>"><?php echo esc_html($title); ?></a></h3>
                <div class="wslt-meta">
                    <?php if ($showCount): ?><span>Participants : 0</span><?php endif; ?>
                    <?php if ($showTimer): ?><span class="wslt-sep"> · </span><span>Statut : actif</span><?php endif; ?>
                </div>
            </div>
        </article>
        <?php
        return (string) ob_get_clean();
    }

    /** ------ Internals ------ */

    private static function coerce_layout(string $layout): string {
        $l = strtolower(trim($layout));
        return in_array($l, ['grid','masonry','diagonal','slider'], true) ? $l : 'grid';
    }

    private static function fetch_items(int $limit, string $status, int $featured): array {
        $items = [];

        // 1) CPT s’il existe
        $cpt_candidates = ['ws-lottery','winshirt_lottery','lottery'];
        $post_type = null;
        foreach ($cpt_candidates as $cpt) {
            if (function_exists('post_type_exists') && post_type_exists($cpt)) { $post_type = $cpt; break; }
        }

        if ($post_type) {
            $args = [
                'post_type'      => $post_type,
                'posts_per_page' => $limit,
                'post_status'    => ['publish'],
                'no_found_rows'  => true,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ];
            if ($featured === 1) {
                $args['meta_query'] = [[ 'key' => '_ws_featured', 'value' => '1' ]];
            }
            $q = new \WP_Query($args);
            if ($q->have_posts()) {
                while ($q->have_posts()) {
                    $q->the_post();
                    $items[] = self::map_post_to_item(get_the_ID(), $status);
                }
                wp_reset_postdata();
            }
            return $items;
        }

        // 2) Fallback Articles: catégorie/étiquette loterie|lottery|jeu|contest
        $q = new \WP_Query([
            'post_type'      => 'post',
            'posts_per_page' => $limit,
            'post_status'    => ['publish'],
            'no_found_rows'  => true,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'tax_query'      => [
                'relation' => 'OR',
                [
                    'taxonomy' => 'category',
                    'field'    => 'slug',
                    'terms'    => ['loterie','lottery','jeu','contest'],
                ],
                [
                    'taxonomy' => 'post_tag',
                    'field'    => 'slug',
                    'terms'    => ['loterie','lottery','jeu','contest'],
                ],
            ],
        ]);
        if ($q->have_posts()) {
            while ($q->have_posts()) {
                $q->the_post();
                $items[] = self::map_post_to_item(get_the_ID(), $status);
            }
            wp_reset_postdata();
        }

        return $items;
    }

    private static function map_post_to_item(int $id, string $status): array {
        return [
            'id'    => $id,
            'title' => get_the_title($id),
            'perma' => get_permalink($id),
            'thumb' => get_the_post_thumbnail_url($id, 'large') ?: '',
            'meta'  => [
                'status' => $status,
                'count'  => 0,        // TODO: brancher la vraie donnée
                'ends'   => null,     // TODO: brancher la vraie donnée
            ],
        ];
    }

    private static function print_base_css(): void {
        static $done = false;
        if ($done) return;
        $done = true;
        ?>
        <style>
            /* Base cards */
            .wslt-card{border:1px solid #eee;border-radius:12px;overflow:hidden;background:#fff}
            .wslt-media{display:block;aspect-ratio:16/9;overflow:hidden;background:#f6f7f9}
            .wslt-media img{width:100%;height:100%;object-fit:cover;display:block}
            .wslt-body{padding:12px 14px 16px}
            .wslt-title{margin:0 0 6px;font:600 16px/1.35 system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
            .wslt-title a{color:#111;text-decoration:none}
            .wslt-meta{opacity:.75;font:400 13px/1.45 system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
            .wslt-sep{opacity:.6}

            /* Grid (desktop: inline style pour colonnes/gap ; mobile: 1 col) */
            .wslt-grid{display:grid}

            /* Masonry (CSS columns, pas de JS) */
            .wslt-masonry{column-gap:var(--wslt-gap,24px)}
            .wslt-masonry .wslt-masonry-item{
                break-inside:avoid;border:1px solid #eee;border-radius:12px;background:#fff;display:block;
                margin:0 0 var(--wslt-gap,24px)
            }

            /* Diagonal : orientation bas droite → haut gauche via flux RTL */
            .wslt-diagonal{display:grid; direction: rtl;} /* important pour l'orientation visuelle */
            .wslt-diagonal .wslt-card{transform:translateY(0);transition:transform .25s ease}
            /* Décalage alterné : en RTL l'ordre visuel se lit de droite à gauche */
            .wslt-diagonal .wslt-card:nth-child(odd){ transform:translateY(6px) }
            .wslt-diagonal .wslt-card:nth-child(even){ transform:translateY(-6px) }
            @media (hover:hover){
              .wslt-diagonal .wslt-card:hover{transform:translateY(0) scale(1.01)}
            }

            /* Slider scroll-snap (sans lib) */
            .wslt-slider-wrap{position:relative}
            .wslt-slider{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;gap:var(--wslt-gap,16px);padding-bottom:8px}
            .wslt-slide{min-width:75%;scroll-snap-align:center}
            @media (min-width:560px){ .wslt-slide{min-width:48%} }
            @media (min-width:900px){ .wslt-slide{min-width:32%} }
            .wslt-nav{position:absolute;inset:0 0 auto 0;display:flex;justify-content:space-between;pointer-events:none}
            .wslt-btn{pointer-events:auto;border:none;border-radius:999px;background:rgba(255,255,255,.8);box-shadow:0 1px 6px rgba(0,0,0,.08);width:36px;height:36px;margin:8px;cursor:pointer}

            /* ===== Responsive mobile : une seule colonne ===== */
            @media (max-width: 640px){
              .wslt-grid{grid-template-columns:1fr !important; gap: var(--wslt-gap,16px) !important;}
              .wslt-diagonal{grid-template-columns:1fr !important; gap: var(--wslt-gap,16px) !important;}
              .wslt-masonry{columns:1 !important; column-gap: var(--wslt-gap,16px) !important;}
            }
        </style>
        <?php
    }

    private static function render_media(string $thumb, string $perma): void {
        if (!empty($thumb)) {
            echo '<a class="wslt-media" href="'.esc_url($perma).'"><img src="'.esc_url($thumb).'" alt=""></a>';
        } else {
            echo '<a class="wslt-media" href="'.esc_url($perma).'" style="background:linear-gradient(135deg,#f3f4f6,#e5e7eb)"></a>';
        }
    }

    private static function render_grid(array $items, int $columns, int $gap, bool $showTimer, bool $showCount): void {
        // Desktop: colonnes/gap inline. Mobile: media query forcera 1 colonne.
        $style = '--wslt-gap:'.$gap.'px;grid-template-columns:repeat('.(int)$columns.',minmax(0,1fr));gap:'.(int)$gap.'px;';
        echo '<div class="wslt-grid" style="'.esc_attr($style).'">';
        foreach ($items as $it) {
            echo '<article class="wslt-card">';
            self::render_media($it['thumb'], $it['perma']);
            echo '<div class="wslt-body">';
            echo '<h3 class="wslt-title"><a href="'.esc_url($it['perma']).'">'.esc_html($it['title']).'</a></h3>';
            echo '<div class="wslt-meta">';
            if ($showCount) echo '<span>Participants : '.(int)($it['meta']['count'] ?? 0).'</span>';
            if ($showTimer) echo '<span class="wslt-sep"> · </span><span>Statut : '.esc_html($it['meta']['status'] ?? '—').'</span>';
            echo '</div></div></article>';
        }
        echo '</div>';
    }

    private static function render_masonry(array $items, int $columns, int $gap, bool $showTimer, bool $showCount): void {
        // Desktop: N colonnes; mobile: 1 via media query.
        $style = '--wslt-gap:'.$gap.'px;columns:'.$columns.';';
        echo '<div class="wslt-masonry" style="'.esc_attr($style).'">';
        foreach ($items as $it) {
            echo '<article class="wslt-masonry-item">';
            self::render_media($it['thumb'], $it['perma']);
            echo '<div class="wslt-body">';
            echo '<h3 class="wslt-title"><a href="'.esc_url($it['perma']).'">'.esc_html($it['title']).'</a></h3>';
            echo '<div class="wslt-meta">';
            if ($showCount) echo '<span>Participants : '.(int)($it['meta']['count'] ?? 0).'</span>';
            if ($showTimer) echo '<span class="wslt-sep"> · </span><span>Statut : '.esc_html($it['meta']['status'] ?? '—').'</span>';
            echo '</div></div></article>';
        }
        echo '</div>';
    }

    private static function render_diagonal(array $items, int $columns, int $gap, bool $showTimer, bool $showCount, bool $rtl = true): void {
        // Desktop: N colonnes; mobile: 1 via media query.
        // Pour l’orientation "bas droite → haut gauche", on passe en flux RTL si $rtl = true.
        $style = '--wslt-gap:'.$gap.'px;grid-template-columns:repeat('.(int)$columns.',minmax(0,1fr));gap:'.(int)$gap.'px;';
        $dir   = $rtl ? ' wslt-diagonal-rtl' : '';
        echo '<div class="wslt-diagonal'.$dir.'" style="'.esc_attr($style).'">';
        foreach ($items as $it) {
            echo '<article class="wslt-card">';
            self::render_media($it['thumb'], $it['perma']);
            echo '<div class="wslt-body">';
            echo '<h3 class="wslt-title"><a href="'.esc_url($it['perma']).'">'.esc_html($it['title']).'</a></h3>';
            echo '<div class="wslt-meta">';
            if ($showCount) echo '<span>Participants : '.(int)($it['meta']['count'] ?? 0).'</span>';
            if ($showTimer) echo '<span class="wslt-sep"> · </span><span>Statut : '.esc_html($it['meta']['status'] ?? '—').'</span>';
            echo '</div></div></article>';
        }
        echo '</div>';
    }

    private static function render_slider(array $items, int $gap, bool $showTimer, bool $showCount, string $ns): void {
        $style = '--wslt-gap:'.$gap.'px;';
        $prevId = 'btn-prev-'.$ns;
        $nextId = 'btn-next-'.$ns;
        $trackId = 'track-'.$ns;

        echo '<div class="wslt-slider-wrap" style="'.esc_attr($style).'">';
        echo '  <div id="'.esc_attr($trackId).'" class="wslt-slider">';
        foreach ($items as $it) {
            echo '<div class="wslt-slide"><article class="wslt-card">';
            self::render_media($it['thumb'], $it['perma']);
            echo '<div class="wslt-body">';
            echo '<h3 class="wslt-title"><a href="'.esc_url($it['perma']).'">'.esc_html($it['title']).'</a></h3>';
            echo '<div class="wslt-meta">';
            if ($showCount) echo '<span>Participants : '.(int)($it['meta']['count'] ?? 0).'</span>';
            if ($showTimer) echo '<span class="wslt-sep"> · </span><span>Statut : '.esc_html($it['meta']['status'] ?? '—').'</span>';
            echo '</div></div></article></div>';
        }
        echo '  </div>';
        echo '  <div class="wslt-nav">';
        echo '    <button id="'.esc_attr($prevId).'" class="wslt-btn" aria-label="Précédent">&#8592;</button>';
        echo '    <button id="'.esc_attr($nextId).'" class="wslt-btn" aria-label="Suivant">&#8594;</button>';
        echo '  </div>';
        echo '</div>';

        ?>
        <script>
        (function(){
          try{
            var track = document.getElementById('<?php echo esc_js($trackId); ?>');
            if(!track) return;
            var prev = document.getElementById('<?php echo esc_js($prevId); ?>');
            var next = document.getElementById('<?php echo esc_js($nextId); ?>');

            function slideBy(delta){
              try{ track.scrollBy({ left: delta, behavior: 'smooth' }); }
              catch(e){ track.scrollLeft += delta; }
            }

            var step = Math.round(track.clientWidth * 0.8) || 300;
            if(prev){ prev.addEventListener('click', function(){ slideBy(-step); }); }
            if(next){ next.addEventListener('click', function(){ slideBy(step); }); }
            window.addEventListener('resize', function(){ step = Math.round(track.clientWidth * 0.8) || 300; });
          }catch(e){ /* no-op */ }
        })();
        </script>
        <?php
    }
}
