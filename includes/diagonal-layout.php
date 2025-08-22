<?php
/**
 * Diagonal Carousel layout (WinShirt by Shakass Communication)
 * - Ne modifie rien ailleurs : ce fichier se suffit à lui-même.
 * - Enqueue ses assets via WINSHIRT_PLUGIN_URL + assets/css|js
 */

if (!defined('ABSPATH')) exit;

/**
 * Enqueue CSS/JS du layout diagonal (idempotent)
 */
function winshirt_diagonal_enqueue_assets() {
    if (!function_exists('wp_enqueue_style')) return;

    if (!wp_style_is('winshirt-diagonal', 'enqueued')) {
        wp_enqueue_style(
            'winshirt-diagonal',
            WINSHIRT_PLUGIN_URL . 'assets/css/diagonal.css',
            [],
            '1.1'
        );
    }

    if (!wp_script_is('winshirt-diagonal', 'enqueued')) {
        wp_enqueue_script(
            'winshirt-diagonal',
            WINSHIRT_PLUGIN_URL . 'assets/js/diagonal.js',
            [],
            '1.1',
            true
        );
    }
}

/**
 * Fallback data si aucun tableau $items n’est fourni.
 * On tente plusieurs post_types pour être sûr d’avoir quelque chose (CPT loterie, produits, articles).
 */
function winshirt_diagonal_build_items_fallback($max = 10) : array {
    $types_to_try = ['winshirt_lottery', 'lottery', 'product', 'post'];
    $items = [];

    foreach ($types_to_try as $pt) {
        $q = new WP_Query([
            'post_type'      => $pt,
            'posts_per_page' => $max,
            'post_status'    => 'publish',
        ]);

        while ($q->have_posts()) {
            $q->the_post();
            $items[] = [
                'title'     => get_the_title(),
                'permalink' => get_permalink(),
                'image'     => get_the_post_thumbnail_url(get_the_ID(), 'large') ?: WINSHIRT_PLUGIN_URL . 'assets/placeholder.jpg',
                'num'       => null, // num sera ajouté plus bas
            ];
        }
        wp_reset_postdata();

        if (!empty($items)) break; // on s’arrête dès qu’on a trouvé des contenus
    }

    // ajoute un numéro 01, 02, …
    foreach ($items as $i => &$it) {
        $it['num'] = str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT);
    }
    return $items;
}

/**
 * Rendu du layout diagonal.
 * @param array|null $items tableau d’items [title, permalink, image, num]
 */
function winshirt_lotteries_render_diagonal(array $items = null) {
    // Fallback contenu
    if ($items === null) {
        $items = winshirt_diagonal_build_items_fallback(10);
    }

    // Sécurité anti-array vide : on montre un texte (donc pas “vide”)
    if (empty($items)) {
        return '<div class="winshirt-diagonal-empty">Aucune loterie/élément à afficher.</div>';
    }

    // Enqueue assets CSS/JS
    winshirt_diagonal_enqueue_assets();

    $uid = 'winshirt-diagonal-' . wp_generate_uuid4();

    // Inline CSS minimaliste de secours (au cas où diagonal.css ne se charge pas)
    $inline_css = '
    <style>
      #'.$uid.'.winshirt-diagonal .carousel{position:relative;height:70vh;overflow:hidden}
      #'.$uid.'.winshirt-diagonal .carousel-item{position:absolute;top:50%;left:50%;width:300px;height:400px;margin:-200px 0 0 -150px;border-radius:12px;background:#000;box-shadow:0 10px 50px 10px rgba(0,0,0,.2)}
      #'.$uid.'.winshirt-diagonal .carousel-item img{width:100%;height:100%;object-fit:cover;display:block}
      @media (max-width:600px){#'.$uid.'.winshirt-diagonal .carousel{height:auto}#'.$uid.'.winshirt-diagonal .carousel-item{position:static;margin:12px 0;width:100%;height:auto;aspect-ratio:16/10}}
    </style>';

    ob_start(); ?>
    <?php echo $inline_css; ?>
    <div id="<?php echo esc_attr($uid); ?>"
         class="winshirt-diagonal"
         data-uid="<?php echo esc_attr($uid); ?>"
         data-count="<?php echo (int)count($items); ?>">
        <div class="carousel">
            <?php foreach ($items as $i => $it): ?>
                <div class="carousel-item" data-index="<?php echo (int)$i; ?>">
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
        <div class="cursor"></div>
        <div class="cursor cursor2"></div>
    </div>
    <?php
    return ob_get_clean();
}
