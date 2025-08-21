<?php
/**
 * WinShirt — Template (grid, masonry, diagonal “escalier” RTL, slider scroll-snap)
 * - Diagonal réellement visible (offset par colonne), orientation bas droite → haut gauche
 * - Aucune dépendance externe
 * - Mobile <640px : 1 colonne pour grid/diagonal, 1 colonne CSS pour masonry
 */
namespace WinShirt;

if (!defined('ABSPATH')) { exit; }

class Lottery_Template
{
    private static $instance = null;
    public static function instance(): self { return self::$instance ?? (self::$instance = new self()); }
    public function init(): void { /* inline CSS/JS pour éviter soucis d’enqueue */ }

    /** -------- Shortcodes API -------- */
    public static function render_list(array $atts): string {
        $a = shortcode_atts([
            'status'      => 'all',
            'featured'    => '0',
            'limit'       => '12',
            'layout'      => 'grid',   // grid | masonry | diagonal | slider
            'columns'     => '4',
            'gap'         => '24',
            'show_timer'  => '1',
            'show_count'  => '1',
            'autoplay'    => '0', 'speed' => '600', 'loop' => '0',
        ], $atts, 'winshirt_lotteries');

        $layout    = self::coerce_layout($a['layout']);
        $limit     = max(1, (int) $a['limit']);
        $columns   = max(1, (int) $a['columns']);
        $gap       = max(0, (int) $a['gap']);
        $showTimer = !empty($a['show_timer']);
        $showCount = !empty($a['show_count']);

        $items = self::fetch_items($limit, (string)$a['status'], (int)$a['featured']);
        if (empty($items)) {
            $items = [[ 'id'=>0, 'title'=>'Exemple de loterie', 'perma'=>'#', 'thumb'=>'',
                'meta'=>['status'=>$a['status'],'count'=>0,'ends'=>null], ]];
        }

        ob_start();
        self::print_base_css(); // injecté une seule fois
        $ns = 'wslt-'.wp_generate_password(6, false, false); // namespace pour scoper le CSS dynamique
        ?>
        <div class="winshirt-lotteries" data-wslt="<?php echo esc_attr($ns); ?>" data-layout="<?php echo esc_attr($layout); ?>">
            <?php
            switch ($layout) {
                case 'masonry':
                    self::render_masonry($items, $columns, $gap, $showTimer, $showCount);
                    break;
                case 'diagonal':
                    self::render_diagonal($items, $columns, $gap, $showTimer, $showCount, $ns);
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
        return (string)ob_get_clean();
    }

    public static function render_card(array $atts): string {
        $id        = isset($atts['id']) ? (int)$atts['id'] : 0;
        $showTimer = !empty($atts['show_timer']);
        $showCount = !empty($atts['show_count']);
        $title = $id ? get_the_title($id) : 'Loterie';
        $perma = $id ? get_permalink($id) : '#';
        $thumb = $id ? (get_the_post_thumbnail_url($id, 'large') ?: '') : '';

        ob_start(); self::print_base_css(); ?>
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
        <?php return (string)ob_get_clean();
    }

    /** -------- Internals -------- */
    private static function coerce_layout(string $layout): string {
        $l = strtolower(trim($layout));
        return in_array($l, ['grid','masonry','diagonal','slider'], true) ? $l : 'grid';
    }

    private static function fetch_items(int $limit, string $status, int $featured): array {
        $items = [];
        $cpt_candidates = ['ws-lottery','winshirt_lottery','lottery']; $post_type = null;
        foreach ($cpt_candidates as $cpt) { if (function_exists('post_type_exists') && post_type_exists($cpt)) { $post_type = $cpt; break; } }

        if ($post_type) {
            $args = [
                'post_type' => $post_type, 'posts_per_page' => $limit, 'post_status' => ['publish'],
                'no_found_rows' => true, 'orderby' => 'date', 'order' => 'DESC',
            ];
            if ($featured === 1) $args['meta_query'] = [[ 'key'=>'_ws_featured','value'=>'1' ]];
            $q = new \WP_Query($args);
            if ($q->have_posts()) { while ($q->have_posts()) { $q->the_post(); $items[] = self::map_post_to_item(get_the_ID(), $status); } wp_reset_postdata(); }
        } else {
            $q = new \WP_Query([
                'post_type'=>'post','posts_per_page'=>$limit,'post_status'=>['publish'],'no_found_rows'=>true,
                'orderby'=>'date','order'=>'DESC',
                'tax_query'=>['relation'=>'OR',
                    ['taxonomy'=>'category','field'=>'slug','terms'=>['loterie','lottery','jeu','contest']],
                    ['taxonomy'=>'post_tag','field'=>'slug','terms'=>['loterie','lottery','jeu','contest']],
                ],
            ]);
            if ($q->have_posts()) { while ($q->have_posts()) { $q->the_post(); $items[] = self::map_post_to_item(get_the_ID(), $status); } wp_reset_postdata(); }
        }
        return $items;
    }

    private static function map_post_to_item(int $id, string $status): array {
        return [
            'id'=>$id, 'title'=>get_the_title($id), 'perma'=>get_permalink($id),
            'thumb'=>get_the_post_thumbnail_url($id,'large') ?: '',
            'meta'=>['status'=>$status,'count'=>0,'ends'=>null],
        ];
    }

    private static function print_base_css(): void {
        static $done = false; if ($done) return; $done = true; ?>
        <style>
            /* Base */
            .wslt-card{border:1px solid #eee;border-radius:12px;overflow:hidden;background:#fff}
            .wslt-media{display:block;aspect-ratio:16/9;overflow:hidden;background:#f6f7f9}
            .wslt-media img{width:100%;height:100%;object-fit:cover;display:block}
            .wslt-body{padding:12px 14px 16px}
            .wslt-title{margin:0 0 6px;font:600 16px/1.35 system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
            .wslt-title a{color:#111;text-decoration:none}
            .wslt-meta{opacity:.75;font:400 13px/1.45 system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
            .wslt-sep{opacity:.6}

            /* Grid */
            .wslt-grid{display:grid}

            /* Masonry */
            .wslt-masonry{column-gap:var(--wslt-gap,24px)}
            .wslt-masonry .wslt-masonry-item{break-inside:avoid;border:1px solid #eee;border-radius:12px;background:#fff;display:block;margin:0 0 var(--wslt-gap,24px)}

            /* Diagonal (base) */
            .wslt-diagonal{display:grid;direction:rtl;} /* RTL = orientation visuelle droite→gauche */
            .wslt-diagonal .wslt-card{transition:transform .25s ease}

            /* Slider scroll-snap */
            .wslt-slider-wrap{position:relative}
            .wslt-slider{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;gap:var(--wslt-gap,16px);padding-bottom:8px}
            .wslt-slide{min-width:75%;scroll-snap-align:center}
            @media (min-width:560px){ .wslt-slide{min-width:48%} }
            @media (min-width:900px){ .wslt-slide{min-width:32%} }
            .wslt-nav{position:absolute;inset:0 0 auto 0;display:flex;justify-content:space-between;pointer-events:none}
            .wslt-btn{pointer-events:auto;border:none;border-radius:999px;background:rgba(255,255,255,.8);box-shadow:0 1px 6px rgba(0,0,0,.08);width:36px;height:36px;margin:8px;cursor:pointer}

            /* Mobile : 1 colonne */
            @media (max-width:640px){
              .wslt-grid{grid-template-columns:1fr !important; gap: var(--wslt-gap,16px) !important;}
              .wslt-diagonal{grid-template-columns:1fr !important; gap: var(--wslt-gap,16px) !important;}
              .wslt-diagonal .wslt-card{transform:none !important;}
              .wslt-masonry{columns:1 !important; column-gap: var(--wslt-gap,16px) !important;}
            }
        </style>
        <?php
    }

    private static function render_media(string $thumb, string $perma): void {
        if ($thumb) {
            echo '<a class="wslt-media" href="'.esc_url($perma).'"><img src="'.esc_url($thumb).'" alt=""></a>';
        } else {
            echo '<a class="wslt-media" href="'.esc_url($perma).'" style="background:linear-gradient(135deg,#f3f4f6,#e5e7eb)"></a>';
        }
    }

    private static function render_grid(array $items, int $columns, int $gap, bool $showTimer, bool $showCount): void {
        $style = '--wslt-gap:'.$gap.'px;grid-template-columns:repeat('.(int)$columns.',minmax(0,1fr));gap:'.(int)$gap.'px;';
        echo '<div class="wslt-grid" style="'.esc_attr($style).'">';
        foreach ($items as $it) { self::card($it, $showTimer, $showCount); }
        echo '</div>';
    }

    private static function render_masonry(array $items, int $columns, int $gap, bool $showTimer, bool $showCount): void {
        $style = '--wslt-gap:'.$gap.'px;columns:'.$columns.';';
        echo '<div class="wslt-masonry" style="'.esc_attr($style).'">';
        foreach ($items as $it) {
            echo '<article class="wslt-masonry-item">'; self::card_inner($it, $showTimer, $showCount); echo '</article>';
        }
        echo '</div>';
    }

    private static function render_diagonal(array $items, int $columns, int $gap, bool $showTimer, bool $showCount, string $ns): void {
        // Desktop: N colonnes; Mobile: forcée à 1 par media query
        $style = '--wslt-gap:'.$gap.'px;grid-template-columns:repeat('.(int)$columns.',minmax(0,1fr));gap:'.(int)$gap.'px;';
        $class = 'wslt-diagonal '.$ns.'-diagonal';
        echo '<div class="'.esc_attr($class).'" style="'.esc_attr($style).'">';

        // Cartes
        foreach ($items as $it) { self::card($it, $showTimer, $showCount); }
        echo '</div>';

        // CSS dynamique : offsets par colonne selon $columns (jusqu’à 6 colonnes)
        // Orientation RTL : la 1ère carte “visuellement” est à droite.
        $max = min(6, max(1, $columns));
        $offsetBase = max(6, (int)round($gap * 0.6)); // proportionnel au gap
        // Profils d’offsets (px) par indice de colonne (1..N) pour donner une vraie diagonale
        $profiles = [
            1 => [0],
            2 => [ $offsetBase, -$offsetBase ],
            3 => [ $offsetBase*1.5, 0, -$offsetBase*1.5 ],
            4 => [ $offsetBase*2, $offsetBase*0.7, -$offsetBase*0.7, -$offsetBase*2 ],
            5 => [ $offsetBase*2.2, $offsetBase*1.1, 0, -$offsetBase*1.1, -$offsetBase*2.2 ],
            6 => [ $offsetBase*2.4, $offsetBase*1.4, $offsetBase*0.4, -$offsetBase*0.4, -$offsetBase*1.4, -$offsetBase*2.4 ],
        ];
        $profile = $profiles[$max];

        // Génère des règles nth-child(Kn + i) → transform: translateY(offset)
        echo "<style>";
        for ($i = 1; $i <= $max; $i++) {
            $off = (int)round($profile[$i-1]);
            // nth-child($max n + $i) — en RTL, la colonne 1 est visuellement à droite
            echo ".".esc_js($ns)."-diagonal .wslt-card:nth-child({$max}n+{$i}){transform:translateY({$off}px)}";
        }
        // Optionnel : petit tilt subtil (facultatif, léger)
        echo ".".esc_js($ns)."-diagonal .wslt-card{will-change:transform}";
        echo "@media (hover:hover){." .esc_js($ns). "-diagonal .wslt-card:hover{transform:translateY(0) scale(1.01)}}";
        echo "</style>";
    }

    private static function render_slider(array $items, int $gap, bool $showTimer, bool $showCount, string $ns): void {
        $style = '--wslt-gap:'.$gap.'px;'; $prevId = 'btn-prev-'.$ns; $nextId = 'btn-next-'.$ns; $trackId = 'track-'.$ns;
        echo '<div class="wslt-slider-wrap" style="'.esc_attr($style).'">';
        echo '  <div id="'.esc_attr($trackId).'" class="wslt-slider">';
        foreach ($items as $it) { echo '<div class="wslt-slide">'; self::card_inner($it, $showTimer, $showCount); echo '</div>'; }
        echo '  </div>';
        echo '  <div class="wslt-nav">';
        echo '    <button id="'.esc_attr($prevId).'" class="wslt-btn" aria-label="Précédent">&#8592;</button>';
        echo '    <button id="'.esc_attr($nextId).'" class="wslt-btn" aria-label="Suivant">&#8594;</button>';
        echo '  </div>';
        echo '</div>';
        ?>
        <script>(function(){try{
          var track=document.getElementById('<?php echo esc_js($trackId); ?>'); if(!track) return;
          var prev=document.getElementById('<?php echo esc_js($prevId); ?>');
          var next=document.getElementById('<?php echo esc_js($nextId); ?>');
          function slideBy(d){try{track.scrollBy({left:d,behavior:'smooth'});}catch(e){track.scrollLeft+=d;}}
          var step=Math.round(track.clientWidth*0.8)||300;
          if(prev) prev.addEventListener('click',function(){slideBy(-step);});
          if(next) next.addEventListener('click',function(){slideBy(step);});
          window.addEventListener('resize',function(){step=Math.round(track.clientWidth*0.8)||300;});
        }catch(e){}})();</script>
        <?php
    }

    /** Helpers de rendu */
    private static function card(array $it, bool $showTimer, bool $showCount): void {
        echo '<article class="wslt-card">'; self::card_inner($it, $showTimer, $showCount); echo '</article>';
    }
    private static function card_inner(array $it, bool $showTimer, bool $showCount): void {
        self::render_media($it['thumb'] ?? '', $it['perma'] ?? '#');
        echo '<div class="wslt-body">';
        echo '<h3 class="wslt-title"><a href="'.esc_url($it['perma'] ?? '#').'">'.esc_html($it['title'] ?? '').'</a></h3>';
        echo '<div class="wslt-meta">';
        if ($showCount) echo '<span>Participants : '.(int)($it['meta']['count'] ?? 0).'</span>';
        if ($showTimer) echo '<span class="wslt-sep"> · </span><span>Statut : '.esc_html($it['meta']['status'] ?? '—').'</span>';
        echo '</div></div>';
    }
}
