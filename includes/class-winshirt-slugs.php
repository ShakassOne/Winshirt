<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Gestion des slugs/URLs (Portfolio, etc.)
 * - Option "winshirt_portfolio_slug" (ex: "loteries")
 * - Application via register_post_type_args (robuste, prioritaire)
 */
class WinShirt_Slugs {

    public static function init(){
        // Page d’options
        add_action('admin_menu', [__CLASS__,'menu']);
        add_action('admin_post_winshirt_save_slugs', [__CLASS__,'save']);

        // Applique le slug au moment du register_post_type du CPT "portfolio"
        add_filter('register_post_type_args', [__CLASS__,'filter_portfolio_args'], 50, 2);

        // Filet de sécurité tardif
        add_action('init', [__CLASS__,'force_portfolio_slug_late'], 999);

        // Flush si changement de thème (utile en staging)
        add_action('after_switch_theme', function(){ flush_rewrite_rules(); });
    }

    /* ------------------ Admin ------------------ */

    public static function menu(){
        add_submenu_page(
            'winshirt',
            __('Slugs & URLs','winshirt'),
            __('Slugs & URLs','winshirt'),
            'manage_options',
            'winshirt-slugs',
            [__CLASS__,'page']
        );
    }

    public static function page(){
        if ( ! current_user_can('manage_options') ) return;
        $slug = get_option('winshirt_portfolio_slug','');
        ?>
        <div class="wrap">
          <h1>Slugs & URLs</h1>
          <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
            <?php wp_nonce_field('winshirt_save_slugs','winshirt_slugs_nonce'); ?>
            <input type="hidden" name="action" value="winshirt_save_slugs" />
            <table class="form-table" role="presentation">
              <tr>
                <th scope="row"><label for="winshirt_portfolio_slug">Slug du Portfolio</label></th>
                <td>
                  <input id="winshirt_portfolio_slug" name="winshirt_portfolio_slug" type="text" class="regular-text" placeholder="ex: loteries" value="<?php echo esc_attr($slug); ?>" />
                  <p class="description">Ex.: “loteries” donnera <code>/loteries/mon-projet/</code>. Laisser vide pour garder celui du thème.</p>
                </td>
              </tr>
            </table>
            <?php submit_button('Enregistrer'); ?>
          </form>
        </div>
        <?php
    }

    public static function save(){
        if ( ! current_user_can('manage_options') ) wp_die('Not allowed');
        if ( ! isset($_POST['winshirt_slugs_nonce']) || ! wp_verify_nonce($_POST['winshirt_slugs_nonce'], 'winshirt_save_slugs') ) wp_die('Nonce error');
        $slug = isset($_POST['winshirt_portfolio_slug']) ? sanitize_title_with_dashes( wp_unslash($_POST['winshirt_portfolio_slug']) ) : '';
        update_option('winshirt_portfolio_slug', $slug);
        flush_rewrite_rules();
        wp_safe_redirect( add_query_arg(['page'=>'winshirt-slugs','updated'=>'1'], admin_url('admin.php')) );
        exit;
    }

    /* ------------- Application du slug ------------- */

    public static function filter_portfolio_args($args, $post_type){
        if ( $post_type !== 'portfolio' ) return $args;
        $slug = trim( (string) get_option('winshirt_portfolio_slug','') );
        if ( $slug === '' ) return $args;
        $slug = sanitize_title_with_dashes($slug);
        if ( ! isset($args['rewrite']) || ! is_array($args['rewrite']) ) $args['rewrite'] = [];
        $args['rewrite']['slug'] = $slug;
        $args['rewrite']['with_front'] = false;
        return $args;
    }

    public static function force_portfolio_slug_late(){
        $slug = trim( (string) get_option('winshirt_portfolio_slug','') );
        if ( $slug === '' ) return;
        global $wp_post_types;
        if ( isset($wp_post_types['portfolio']) ) {
            $slug = sanitize_title_with_dashes($slug);
            if ( ! is_array($wp_post_types['portfolio']->rewrite) ) $wp_post_types['portfolio']->rewrite = [];
            $wp_post_types['portfolio']->rewrite['slug'] = $slug;
            $wp_post_types['portfolio']->rewrite['with_front'] = false;
        }
    }
}
