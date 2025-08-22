<?php
if ( ! defined('ABSPATH') ) exit;

class WinShirt_Admin {

    public static function init(){
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'maybe_create_category']);
        add_action('admin_notices', [__CLASS__, 'notices']);
    }

    public static function menu(){
        add_menu_page(
            __('WinShirt','winshirt'),
            'WinShirt',
            'manage_options',
            'winshirt',
            [__CLASS__,'dashboard'],
            'dashicons-tickets',
            56
        );
        add_submenu_page('winshirt', __('Dashboard','winshirt'), __('Dashboard','winshirt'), 'manage_options', 'winshirt', [__CLASS__,'dashboard']);
        add_submenu_page('winshirt', __('Loteries (Articles)','winshirt'), __('Loteries (Articles)','winshirt'), 'edit_posts', 'edit.php?post_type=post&category_name=loterie');
        add_submenu_page('winshirt', __('Paramètres','winshirt'), __('Paramètres','winshirt'), 'manage_options', 'winshirt-settings', [__CLASS__,'settings']);
    }

    public static function dashboard(){
        ?>
        <div class="wrap">
          <h1>WinShirt</h1>
          <p>Module allégé pour rétablir le fonctionnement : menu + shortcode loteries.</p>
          <ol>
            <li>Créer vos loteries comme des <strong>Articles</strong> dans la catégorie <code>loterie</code>.</li>
            <li>Insérer <code>[winshirt_lotteries layout="grid|masonry|diagonal" columns="4" gap="24"]</code> dans une page.</li>
          </ol>
        </div>
        <?php
    }

    public static function settings(){
        if ( isset($_POST['winshirt_save']) && check_admin_referer('winshirt_settings') ) {
            update_option('winshirt_lottery_category', sanitize_text_field($_POST['lottery_category'] ?? 'loterie'));
            echo '<div class="updated"><p>Réglages sauvegardés.</p></div>';
        }
        $cat = get_option('winshirt_lottery_category','loterie');
        ?>
        <div class="wrap">
          <h1>Paramètres WinShirt</h1>
          <form method="post">
            <?php wp_nonce_field('winshirt_settings'); ?>
            <table class="form-table" role="presentation">
              <tr>
                <th scope="row"><label for="lottery_category">Catégorie des loteries</label></th>
                <td><input name="lottery_category" id="lottery_category" type="text" value="<?php echo esc_attr($cat); ?>" class="regular-text"></td>
              </tr>
            </table>
            <p class="submit"><button class="button button-primary" name="winshirt_save" value="1">Enregistrer</button></p>
          </form>
        </div>
        <?php
    }

    public static function maybe_create_category(){
        $cat = get_option('winshirt_lottery_category','loterie');
        if ( ! term_exists($cat,'category') ) {
            wp_insert_term($cat,'category',['slug'=>sanitize_title($cat)]);
        }
    }

    public static function notices(){
        $cat = get_option('winshirt_lottery_category','loterie');
        if ( ! current_user_can('edit_posts') ) return;
        if ( ! term_exists($cat,'category') ) {
            echo '<div class="notice notice-warning"><p>La catégorie <code>'.esc_html($cat).'</code> n’existe pas encore. Elle sera créée automatiquement.</p></div>';
        }
    }
}
