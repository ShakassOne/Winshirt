<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WinShirt_Admin {

    /** Les étapes de la roadmap */
    private $roadmap_steps = array();

    public function __construct() {
        $this->roadmap_steps = $this->load_roadmap_steps();
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
    }

    /**
     * Charge toutes les étapes depuis le fichier roadmap.txt.
     *
     * @return array
     */
    private function load_roadmap_steps() {
        $file = WINSHIRT_PATH . 'roadmap.txt';

        if ( ! file_exists( $file ) ) {
            return array();
        }

        $lines   = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        $steps   = array();
        $section = '';
        $started = false;

        foreach ( $lines as $line ) {
            $line = trim( $line );

            if ( ! $started ) {
                if ( stripos( $line, 'Roadmap détaillée' ) !== false ) {
                    $started = true;
                }
                continue;
            }

            if ( preg_match( '/^\d+\.\s*(.+)$/', $line, $matches ) ) {
                $section = $matches[1];
                continue;
            }

            if ( '' === $line ) {
                continue;
            }

            $label = $section ? $section . ' — ' . $line : $line;
            $key   = 'step_' . md5( $label );
            $steps[ $key ] = $label;
        }

        return $steps;
    }

    public function add_menu() {
        // Menu principal WinShirt
        add_menu_page(
            __( 'WinShirt', 'winshirt' ),
            __( 'WinShirt', 'winshirt' ),
            'manage_options',
            'winshirt',
            array( $this, 'progress_page' ),
            'dashicons-admin-generic'
        );

        // Sous-menu Mockups
        add_submenu_page(
            'winshirt',
            __( 'Mockups', 'winshirt' ),
            __( 'Mockups', 'winshirt' ),
            'edit_posts',
            'edit.php?post_type=ws-mockup'
        );

        // Sous-menu Paramètres (Settings API)
        add_submenu_page(
            'winshirt',
            __( 'Paramètres', 'winshirt' ),
            __( 'Paramètres', 'winshirt' ),
            'manage_options',
            'winshirt-settings',
            array( 'WinShirt_Settings', 'render_settings_page' )
        );
    }

    /**
     * Affiche la page de suivi de roadmap
     */
    public function progress_page() {
        $completed = get_option( 'winshirt_roadmap_progress', array() );

        if ( isset( $_POST['winshirt_roadmap'] ) ) {
            check_admin_referer( 'winshirt_progress_save', 'winshirt_progress_nonce' );
            $completed = array_map( 'sanitize_text_field', (array) $_POST['winshirt_roadmap'] );
            update_option( 'winshirt_roadmap_progress', $completed );
        }

        $total   = count( $this->roadmap_steps );
        $done    = count( $completed );
        $percent = $total > 0 ? round( $done / $total * 100 ) : 0;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Avancement du développement', 'winshirt' ) . '</h1>';
        echo '<p>' . esc_html__( 'Progression :', 'winshirt' ) . ' <strong id="winshirt-progress">' . esc_html( $percent ) . '%</strong></p>';

        echo '<form method="post">';
        wp_nonce_field( 'winshirt_progress_save', 'winshirt_progress_nonce' );
        echo '<ul>';
        foreach ( $this->roadmap_steps as $key => $label ) {
            $checked = in_array( $key, $completed, true ) ? 'checked' : '';
            printf(
                '<li><label><input type="checkbox" class="winshirt-roadmap-checkbox" name="winshirt_roadmap[]" value="%1$s" %2$s> %3$s</label></li>',
                esc_attr( $key ),
                $checked,
                esc_html( $label )
            );
        }
        echo '</ul>';
        submit_button();
        echo '</form>';

        // Live update du pourcentage
        ?>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const checkboxes = document.querySelectorAll(".winshirt-roadmap-checkbox");
            const progress   = document.getElementById("winshirt-progress");
            const total      = checkboxes.length;

            function updateProgress() {
                let checked = 0;
                checkboxes.forEach(cb => { if (cb.checked) checked++; });
                const pct = Math.round(checked / total * 100);
                progress.textContent = pct + "%";
            }

            checkboxes.forEach(cb => cb.addEventListener("change", updateProgress));
        });
        </script>
        <?php
        echo '</div>';
    }
}
