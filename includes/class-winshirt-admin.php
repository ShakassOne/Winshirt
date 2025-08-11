<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Admin {

	/** Les étapes de la roadmap */
	private $roadmap_steps = array();

	public function __construct() {
		$this->roadmap_steps = $this->load_roadmap_steps();
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_menu', array( $this, 'cleanup_menu' ), 99 );
	}

	/**
	 * Charge toutes les étapes depuis le fichier roadmap.txt.
	 */
	private function load_roadmap_steps() {
		$file = WINSHIRT_PATH . 'roadmap.txt';
		if ( ! file_exists( $file ) ) return array();

		$lines   = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		$steps   = array();
		$section = '';
		$started = false;

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( ! $started ) {
				if ( stripos( $line, 'Roadmap détaillée' ) !== false ) $started = true;
				continue;
			}
			if ( preg_match( '/^\d+\.\s*(.+)$/', $line, $m ) ) { $section = $m[1]; continue; }
			if ( '' === $line ) continue;

			$label = $section ? $section . ' — ' . $line : $line;
			$key   = 'step_' . md5( $label );
			$steps[ $key ] = $label;
		}
		return $steps;
	}

	public function add_menu() {
		add_menu_page(
			__( 'WinShirt', 'winshirt' ),
			__( 'WinShirt', 'winshirt' ),
			'manage_options',
			'winshirt',
			array( $this, 'progress_page' ),
			'dashicons-tshirt', 56
		);

		// Sous-menus Mockups (CPT : ws-mockup)
		add_submenu_page(
			'winshirt',
			__( 'Tous les mockups', 'winshirt' ),
			__( 'Tous les mockups', 'winshirt' ),
			'edit_posts',
			'edit.php?post_type=ws-mockup'
		);
		add_submenu_page(
			'winshirt',
			__( 'Ajouter un mockup', 'winshirt' ),
			__( 'Ajouter un mockup', 'winshirt' ),
			'edit_posts',
			'post-new.php?post_type=ws-mockup'
		);

		// Sous-menus Visuels (CPT : ws-design)
		add_submenu_page(
			'winshirt',
			__( 'Tous les visuels', 'winshirt' ),
			__( 'Tous les visuels', 'winshirt' ),
			'edit_posts',
			'edit.php?post_type=ws-design'
		);
		add_submenu_page(
			'winshirt',
			__( 'Ajouter un visuel', 'winshirt' ),
			__( 'Ajouter un visuel', 'winshirt' ),
			'edit_posts',
			'post-new.php?post_type=ws-design'
		);

		// Sous-menu Paramètres (si la classe existe)
		if ( class_exists( 'WinShirt_Settings' ) && method_exists( 'WinShirt_Settings', 'render_settings_page' ) ) {
			add_submenu_page(
				'winshirt',
				__( 'Paramètres', 'winshirt' ),
				__( 'Paramètres', 'winshirt' ),
				'manage_options',
				'winshirt-settings',
				array( 'WinShirt_Settings', 'render_settings_page' )
			);
		}
	}

	public function cleanup_menu() {
		remove_submenu_page( 'winshirt', 'edit-tags.php?taxonomy=ws-design-category&post_type=ws-design' );
	}

	/** Page d’avancement (optionnelle) */
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

		echo '<div class="wrap"><h1>'.esc_html__( 'Avancement du développement', 'winshirt' ).'</h1>';
		echo '<p>'.esc_html__( 'Progression :', 'winshirt' ).' <strong id="winshirt-progress">'.$percent.'%</strong></p>';
		echo '<form method="post">'; wp_nonce_field( 'winshirt_progress_save', 'winshirt_progress_nonce' ); echo '<ul>';
		foreach ( $this->roadmap_steps as $key => $label ) {
			$checked = in_array( $key, $completed, true ) ? 'checked' : '';
			printf(
				'<li><label><input type="checkbox" class="winshirt-roadmap-checkbox" name="winshirt_roadmap[]" value="%1$s" %2$s> %3$s</label></li>',
				esc_attr( $key ), $checked, esc_html( $label )
			);
		}
		echo '</ul>'; submit_button(); echo '</form>';

		?>
		<script>
		document.addEventListener("DOMContentLoaded", function() {
			const boxes=[...document.querySelectorAll(".winshirt-roadmap-checkbox")];
			const span=document.getElementById("winshirt-progress");
			const total=boxes.length;
			function upd(){let c=0;boxes.forEach(b=>{if(b.checked)c++});span.textContent=Math.round(c/Math.max(1,total)*100)+"%";}
			boxes.forEach(b=>b.addEventListener("change",upd));
		});
		</script>
		<?php
		echo '</div>';
	}
}

// signal pour bootstrap
do_action('winshirt_admin_booted', new WinShirt_Admin() );
