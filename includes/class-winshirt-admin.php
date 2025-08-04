<?php
if (!defined('ABSPATH')) {
    exit;
}

class WinShirt_Admin {

    private $roadmap_steps = array(
        'initialization' => 'Initialisation & structure de base',
        'configuration'  => 'Configuration globale',
        'customization'  => 'Personnalisation produit',
        'modal'          => 'Modal de personnalisation (prestations frontend)',
        'editor'         => 'Éditeur d’éléments',
        'capture'        => 'Capture & sauvegarde',
        'mockups'        => 'Mockups produits',
        'visuals'        => 'Visuels clients',
        'orders'         => 'Commandes DTF',
        'tests'          => 'Tests & validation',
        'documentation'  => 'Documentation & publication'
    );

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
    }

    public function add_menu() {
        add_menu_page(
            __('WinShirt', 'winshirt'),
            __('WinShirt', 'winshirt'),
            'manage_options',
            'winshirt',
            array($this, 'progress_page'),
            'dashicons-admin-generic'
        );

        add_submenu_page(
            'winshirt',
            __('Paramètres', 'winshirt'),
            __('Paramètres', 'winshirt'),
            'manage_options',
            'winshirt-settings',
            ['WinShirt_Settings', 'render_settings_page']
        );
    }

    public function progress_page() {
        $completed = get_option('winshirt_roadmap_progress', array());

        if (isset($_POST['winshirt_roadmap'])) {
            check_admin_referer('winshirt_progress_save', 'winshirt_progress_nonce');
            $completed = array_map('sanitize_text_field', (array) $_POST['winshirt_roadmap']);
            update_option('winshirt_roadmap_progress', $completed);
        }

        $total   = count($this->roadmap_steps);
        $done    = count($completed);
        $percent = $total > 0 ? round($done / $total * 100) : 0;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Avancement du développement', 'winshirt') . '</h1>';
        echo '<p>' . esc_html__('Progression :', 'winshirt') . ' <span id="winshirt-progress">' . esc_html($percent) . '%</span></p>';
        echo '<form method="post">';
        wp_nonce_field('winshirt_progress_save', 'winshirt_progress_nonce');
        echo '<ul>';
        foreach ($this->roadmap_steps as $key => $label) {
            $checked = in_array($key, $completed, true) ? 'checked' : '';
            echo '<li><label><input type="checkbox" class="winshirt-roadmap-checkbox" name="winshirt_roadmap[]" value="' . esc_attr($key) . '" ' . $checked . '> ' . esc_html($label) . '</label></li>';
        }
        echo '</ul>';
        submit_button();
        echo '</form>';
        echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        const checkboxes = document.querySelectorAll(".winshirt-roadmap-checkbox");
        const progress = document.getElementById("winshirt-progress");
        const total = checkboxes.length;
        function updateProgress() {
            let checked = 0;
            checkboxes.forEach(cb => { if (cb.checked) checked++; });
            const percent = Math.round(checked / total * 100);
            progress.textContent = percent + "%";
        }
        checkboxes.forEach(cb => cb.addEventListener("change", updateProgress));
    });
    </script>';
        echo '</div>';
    }
}
?>
