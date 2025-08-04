<?php
// includes/class-winshirt-settings.php
if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Settings {

    const OPTION_KEY = 'winshirt_settings';
    const PAGE_SLUG  = 'winshirt-settings';

    public static function init() {
        // Register settings without adding a separate top-level menu. The menu
        // entry is provided by \WinShirt_Admin.
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    // Enregistrer les réglages (Settings API)
    public static function register_settings() {
        register_setting(
            'winshirt_settings_group',                // option group
            self::OPTION_KEY,                         // option name
            [ __CLASS__, 'sanitize_settings' ]        // sanitize callback
        );

        add_settings_section(
            'winshirt_section_main',                  // id
            __( 'Paramètres généraux', 'winshirt' ),  // title
            '__return_false',                         // callback description
            self::PAGE_SLUG                           // page
        );

        // Champs : API IA
        add_settings_field(
            'ftp_api_ia_key',
            __( 'Clé API IA', 'winshirt' ),
            [ __CLASS__, 'field_api_key_cb' ],
            self::PAGE_SLUG,
            'winshirt_section_main'
        );

        // Champs : Formats autorisés
        add_settings_field(
            'formats_allowed',
            __( 'Formats autorisés', 'winshirt' ),
            [ __CLASS__, 'field_formats_cb' ],
            self::PAGE_SLUG,
            'winshirt_section_main'
        );

        // Champs : Dimensions par défaut
        add_settings_field(
            'dimensions_default',
            __( 'Dimensions par défaut (L×H×U)', 'winshirt' ),
            [ __CLASS__, 'field_dimensions_cb' ],
            self::PAGE_SLUG,
            'winshirt_section_main'
        );

        // Champs : Préfixe export
        add_settings_field(
            'prefix_export',
            __( 'Préfixe export', 'winshirt' ),
            [ __CLASS__, 'field_prefix_cb' ],
            self::PAGE_SLUG,
            'winshirt_section_main'
        );

        // Champs : Chemins export JSON/XML
        add_settings_field(
            'path_export_json',
            __( 'Chemin export JSON', 'winshirt' ),
            [ __CLASS__, 'field_path_json_cb' ],
            self::PAGE_SLUG,
            'winshirt_section_main'
        );
        add_settings_field(
            'path_export_xml',
            __( 'Chemin export XML', 'winshirt' ),
            [ __CLASS__, 'field_path_xml_cb' ],
            self::PAGE_SLUG,
            'winshirt_section_main'
        );

        // Champs : Email huissier
        add_settings_field(
            'bailiff_email',
            __( 'Email huissier', 'winshirt' ),
            [ __CLASS__, 'field_bailiff_email_cb' ],
            self::PAGE_SLUG,
            'winshirt_section_main'
        );
    }

    // 3. Sanitize
    public static function sanitize_settings( $input ) {
        $output = [];
        $output['api_ia_key']        = sanitize_text_field( $input['api_ia_key'] ?? '' );
        $output['formats_allowed']   = sanitize_text_field( $input['formats_allowed'] ?? '' );
        $output['dimensions_default']= sanitize_text_field( $input['dimensions_default'] ?? '' );
        $output['prefix_export']     = sanitize_text_field( $input['prefix_export'] ?? '' );
        $output['path_export_json']  = sanitize_text_field( $input['path_export_json'] ?? '' );
        $output['path_export_xml']   = sanitize_text_field( $input['path_export_xml'] ?? '' );
        $output['bailiff_email']     = sanitize_email( $input['bailiff_email'] ?? '' );
        return $output;
    }

    // 4. Callbacks pour chaque champ
    public static function field_api_key_cb() {
        $opts = get_option( self::OPTION_KEY );
        printf(
            '<input type="text" name="%1$s[api_ia_key]" value="%2$s" class="regular-text" />',
            esc_attr( self::OPTION_KEY ),
            esc_attr( $opts['api_ia_key'] ?? '' )
        );
    }

    public static function field_formats_cb() {
        $opts = get_option( self::OPTION_KEY );
        printf(
            '<input type="text" name="%1$s[formats_allowed]" value="%2$s" placeholder="A4,A3,Coeur,Full" class="regular-text" />',
            esc_attr( self::OPTION_KEY ),
            esc_attr( $opts['formats_allowed'] ?? '' )
        );
    }

    public static function field_dimensions_cb() {
        $opts = get_option( self::OPTION_KEY );
        printf(
            '<input type="text" name="%1$s[dimensions_default]" value="%2$s" placeholder="100x150x1" class="regular-text" />',
            esc_attr( self::OPTION_KEY ),
            esc_attr( $opts['dimensions_default'] ?? '' )
        );
    }

    public static function field_prefix_cb() {
        $opts = get_option( self::OPTION_KEY );
        printf(
            '<input type="text" name="%1$s[prefix_export]" value="%2$s" placeholder="winshirt_" class="regular-text" />',
            esc_attr( self::OPTION_KEY ),
            esc_attr( $opts['prefix_export'] ?? '' )
        );
    }

    public static function field_path_json_cb() {
        $opts = get_option( self::OPTION_KEY );
        printf(
            '<input type="text" name="%1$s[path_export_json]" value="%2$s" placeholder="/exports/json/" class="regular-text" />',
            esc_attr( self::OPTION_KEY ),
            esc_attr( $opts['path_export_json'] ?? '' )
        );
    }

    public static function field_path_xml_cb() {
        $opts = get_option( self::OPTION_KEY );
        printf(
            '<input type="text" name="%1$s[path_export_xml]" value="%2$s" placeholder="/exports/xml/" class="regular-text" />',
            esc_attr( self::OPTION_KEY ),
            esc_attr( $opts['path_export_xml'] ?? '' )
        );
    }

    public static function field_bailiff_email_cb() {
        $opts = get_option( self::OPTION_KEY );
        printf(
            '<input type="email" name="%1$s[bailiff_email]" value="%2$s" class="regular-text" />',
            esc_attr( self::OPTION_KEY ),
            esc_attr( $opts['bailiff_email'] ?? '' )
        );
    }

    // 5. Rendu de la page
    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Configuration WinShirt', 'winshirt' ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'winshirt_settings_group' );
                do_settings_sections( self::PAGE_SLUG );
                submit_button( __( 'Enregistrer', 'winshirt' ) );
                ?>
            </form>
        </div>
        <?php
    }
}

// Lancement
WinShirt_Settings::init();
