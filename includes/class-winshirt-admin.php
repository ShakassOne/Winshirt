<?php
if ( ! defined('ABSPATH') ) exit;

class WS_Admin {

    public static function init(){
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_post_ws_save_settings', [__CLASS__, 'save_settings']);
    }

    public static function menu(){
        add_menu_page(
            'WinShirt',
            'WinShirt',
            'manage_options',
            'winshirt',
            [__CLASS__, 'dashboard'],
            'dashicons-shirt',
            26
        );

        add_submenu_page(
            'winshirt',
            'Paramètres',
            'Paramètres',
            'manage_options',
            'winshirt-settings',
            [__CLASS__, 'settings']
        );
    }

    public static function dashboard(){
        echo '<div class="wrap"><h1>WinShirt</h1><p>Gestion des loteries, slugs et overlays.</p></div>';
    }

    public static function settings(){
        if ( ! current_user_can('manage_options') ) return;
        $cat = get_option('winshirt_lottery_category','loterie');
        $action = admin_url('admin-post.php');
        ?>
        <div class="wrap">
            <h1>Paramètres WinShirt</h1>
            <form method="post" action="<?php echo esc_url($action); ?>">
                <?php wp_nonce_field('ws_save_settings','ws_settings_nonce'); ?>
                <input type="hidden" name="action" value="ws_save_settings" />
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="winshirt_lottery_category">Catégorie de loterie</label></th>
                        <td>
                            <input type="text" id="winshirt_lottery_category" name="winshirt_lottery_category" class="regular-text" value="<?php echo esc_attr($cat); ?>" />
                            <p class="description">Slug (ou nom) de la catégorie utilisée pour les articles de type loterie (par défaut : <code>loterie</code>).</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Enregistrer'); ?>
            </form>
        </div>
        <?php
    }

    public static function save_settings(){
        if ( ! current_user_can('manage_options') ) wp_die('Not allowed');
        if ( ! isset($_POST['ws_settings_nonce']) || ! wp_verify_nonce($_POST['ws_settings_nonce'],'ws_save_settings') ) wp_die('Nonce error');

        $cat = isset($_POST['winshirt_lottery_category'])
            ? sanitize_text_field( wp_unslash($_POST['winshirt_lottery_category']) )
            : 'loterie';

        update_option('winshirt_lottery_category', $cat);

        wp_safe_redirect( add_query_arg(['page'=>'winshirt-settings','updated'=>'1'], admin_url('admin.php')) );
        exit;
    }
}
