<?php
/**
 * WinShirt Roadmap - Suivi des tâches avec checkboxes (Recovery v1.0)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Roadmap' ) ) {

class WinShirt_Roadmap {

	private static $roadmap_data = null;

	public static function init() {
		add_action( 'wp_ajax_ws_update_task_status', [ __CLASS__, 'ajax_update_task_status' ] );
		add_action( 'wp_ajax_ws_reset_roadmap', [ __CLASS__, 'ajax_reset_roadmap' ] );
	}

	/**
	 * Charger les données de roadmap depuis CSV
	 */
	public static function load_roadmap_data() {
		if ( self::$roadmap_data !== null ) {
			return self::$roadmap_data;
		}

		$csv_file = WINSHIRT_PATH . 'roadmap_winshirt.csv';
		if ( ! file_exists( $csv_file ) ) {
			return [];
		}

		$data = [];
		if ( ( $handle = fopen( $csv_file, 'r' ) ) !== false ) {
			$headers = fgetcsv( $handle ); // Lire headers
			
			while ( ( $row = fgetcsv( $handle ) ) !== false ) {
				if ( count( $row ) >= count( $headers ) ) {
					$data[] = array_combine( $headers, $row );
				}
			}
			fclose( $handle );
		}

		self::$roadmap_data = $data;
		return $data;
	}

	/**
	 * Obtenir le statut des tâches sauvegardées
	 */
	public static function get_completed_tasks() {
		return get_option( 'winshirt_completed_tasks', [] );
	}

	/**
	 * Calculer les statistiques de progression
	 */
	public static function get_progress_stats() {
		$roadmap = self::load_roadmap_data();
		$completed = self::get_completed_tasks();
		
		$total_tasks = count( $roadmap );
		$completed_count = count( $completed );
		$progress_percent = $total_tasks > 0 ? round( ( $completed_count / $total_tasks ) * 100, 1 ) : 0;

		// Stats par phase
		$phases = [];
		foreach ( $roadmap as $task ) {
			$phase = $task['Phase'] ?? 'Unknown';
			if ( ! isset( $phases[ $phase ] ) ) {
				$phases[ $phase ] = [ 'total' => 0, 'completed' => 0 ];
			}
			$phases[ $phase ]['total']++;
			
			if ( in_array( $task['ID'], $completed ) ) {
				$phases[ $phase ]['completed']++;
			}
		}

		// Stats par priorité
		$priorities = [];
		foreach ( $roadmap as $task ) {
			$priority = $task['Priority'] ?? 'Unknown';
			if ( ! isset( $priorities[ $priority ] ) ) {
				$priorities[ $priority ] = [ 'total' => 0, 'completed' => 0 ];
			}
			$priorities[ $priority ]['total']++;
			
			if ( in_array( $task['ID'], $completed ) ) {
				$priorities[ $priority ]['completed']++;
			}
		}

		return [
			'total_tasks' => $total_tasks,
			'completed_count' => $completed_count,
			'progress_percent' => $progress_percent,
			'phases' => $phases,
			'priorities' => $priorities,
		];
	}

	/**
	 * Render la page roadmap
	 */
	public static function render_roadmap_page() {
		$roadmap = self::load_roadmap_data();
		$completed = self::get_completed_tasks();
		$stats = self::get_progress_stats();

		// Grouper par phase
		$grouped = [];
		foreach ( $roadmap as $task ) {
			$phase = $task['Phase'] ?? 'Unknown';
			$grouped[ $phase ][] = $task;
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WinShirt - Roadmap & Progression', 'winshirt' ); ?></h1>

			<!-- Statistiques globales -->
			<div class="ws-roadmap-stats">
				<div class="card">
					<h2><?php esc_html_e( 'Progression Globale', 'winshirt' ); ?></h2>
					<div class="ws-progress-circle">
						<div class="ws-progress-number"><?php echo esc_html( $stats['progress_percent'] ); ?>%</div>
						<div class="ws-progress-text">
							<?php printf( 
								esc_html__( '%d / %d tâches terminées', 'winshirt' ),
								$stats['completed_count'],
								$stats['total_tasks']
							); ?>
						</div>
					</div>
					
					<div class="ws-progress-bar">
						<div class="ws-progress-fill" style="width: <?php echo esc_attr( $stats['progress_percent'] ); ?>%;"></div>
					</div>
				</div>

				<!-- Stats par phase -->
				<div class="card">
					<h3><?php esc_html_e( 'Progression par Phase', 'winshirt' ); ?></h3>
					<?php foreach ( $stats['phases'] as $phase => $data ) : ?>
						<?php $phase_percent = $data['total'] > 0 ? round( ( $data['completed'] / $data['total'] ) * 100, 1 ) : 0; ?>
						<div class="ws-phase-progress">
							<strong><?php echo esc_html( $phase ); ?></strong>
							<span class="ws-phase-stats"><?php echo $data['completed']; ?> / <?php echo $data['total']; ?> (<?php echo $phase_percent; ?>%)</span>
							<div class="ws-mini-progress">
								<div class="ws-mini-fill" style="width: <?php echo esc_attr( $phase_percent ); ?>%;"></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Contrôles -->
			<div class="ws-roadmap-controls">
				<button id="ws-reset-roadmap" class="button button-secondary">
					<?php esc_html_e( 'Reset Progression', 'winshirt' ); ?>
				</button>
				<button id="ws-toggle-completed" class="button">
					<?php esc_html_e( 'Masquer/Afficher terminées', 'winshirt' ); ?>
				</button>
			</div>

			<!-- Liste des tâches par phase -->
			<div class="ws-roadmap-tasks">
				<?php foreach ( $grouped as $phase => $tasks ) : ?>
					<div class="ws-phase-section">
						<h2 class="ws-phase-title">
							<?php echo esc_html( $phase ); ?>
							<span class="ws-phase-count">(<?php echo count( $tasks ); ?> tâches)</span>
						</h2>
						
						<div class="ws-tasks-grid">
							<?php foreach ( $tasks as $task ) : ?>
								<?php $is_completed = in_array( $task['ID'], $completed ); ?>
								<div class="ws-task-item <?php echo $is_completed ? 'ws-completed' : ''; ?>" data-task-id="<?php echo esc_attr( $task['ID'] ); ?>">
									<label class="ws-task-checkbox">
										<input type="checkbox" 
											   class="ws-task-toggle" 
											   data-task-id="<?php echo esc_attr( $task['ID'] ); ?>"
											   <?php checked( $is_completed ); ?> />
										<span class="ws-task-title"><?php echo esc_html( $task['Task'] ?? $task['ID'] ); ?></span>
									</label>
									
									<div class="ws-task-meta">
										<span class="ws-priority ws-priority-<?php echo esc_attr( strtolower( $task['Priority'] ?? 'p3' ) ); ?>">
											<?php echo esc_html( $task['Priority'] ?? 'P3' ); ?>
										</span>
										<span class="ws-area"><?php echo esc_html( $task['Area'] ?? '' ); ?></span>
										<span class="ws-estimate"><?php echo esc_html( $task['Estimate_d'] ?? 1 ); ?>j</span>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<style>
		.ws-roadmap-stats {
			display: flex;
			gap: 20px;
			margin: 20px 0;
		}
		.ws-roadmap-stats .card {
			flex: 1;
			padding: 20px;
		}
		.ws-progress-circle {
			text-align: center;
			margin: 20px 0;
		}
		.ws-progress-number {
			font-size: 48px;
			font-weight: bold;
			color: #0073aa;
		}
		.ws-progress-bar {
			background: #f0f0f0;
			height: 20px;
			border-radius: 10px;
			overflow: hidden;
		}
		.ws-progress-fill {
			background: linear-gradient(90deg, #0073aa, #00a0d2);
			height: 100%;
			transition: width 0.3s ease;
		}
		.ws-phase-progress {
			display: flex;
			align-items: center;
			margin: 10px 0;
			gap: 10px;
		}
		.ws-mini-progress {
			flex: 1;
			background: #f0f0f0;
			height: 8px;
			border-radius: 4px;
			overflow: hidden;
		}
		.ws-mini-fill {
			background: #0073aa;
			height: 100%;
			transition: width 0.3s ease;
		}
		.ws-roadmap-controls {
			margin: 20px 0;
		}
		.ws-phase-section {
			margin: 30px 0;
		}
		.ws-phase-title {
			background: #f8f9fa;
			padding: 15px;
			margin: 0 0 15px 0;
			border-left: 4px solid #0073aa;
		}
		.ws-tasks-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
			gap: 15px;
		}
		.ws-task-item {
			background: white;
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 15px;
			transition: all 0.2s ease;
		}
		.ws-task-item:hover {
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
		}
		.ws-task-item.ws-completed {
			background: #f0f8f0;
			border-color: #46b450;
		}
		.ws-task-checkbox {
			display: flex;
			align-items: flex-start;
			gap: 10px;
			cursor: pointer;
		}
		.ws-task-title {
			flex: 1;
			font-weight: 500;
		}
		.ws-completed .ws-task-title {
			text-decoration: line-through;
			opacity: 0.7;
		}
		.ws-task-meta {
			margin-top: 10px;
			display: flex;
			gap: 10px;
			flex-wrap: wrap;
		}
		.ws-task-meta span {
			background: #f0f0f0;
			padding: 2px 8px;
			border-radius: 3px;
			font-size: 12px;
		}
		.ws-priority-p0 { background: #dc3232 !important; color: white; }
		.ws-priority-p1 { background: #ffb900 !important; color: white; }
		.ws-priority-p2 { background: #0073aa !important; color: white; }
		.ws-priority-p3 { background: #666 !important; color: white; }
		</style>

		<script>
		jQuery(document).ready(function($) {
			// Toggle tâche
			$('.ws-task-toggle').on('change', function() {
				const taskId = $(this).data('task-id');
				const isCompleted = $(this).prop('checked');
				const $item = $(this).closest('.ws-task-item');
				
				$.post(ajaxurl, {
					action: 'ws_update_task_status',
					task_id: taskId,
					completed: isCompleted ? 1 : 0,
					nonce: '<?php echo wp_create_nonce( 'ws_roadmap_nonce' ); ?>'
				}, function(response) {
					if (response.success) {
						$item.toggleClass('ws-completed', isCompleted);
						location.reload(); // Recharger pour mettre à jour les stats
					}
				});
			});

			// Reset roadmap
			$('#ws-reset-roadmap').on('click', function() {
				if (confirm('<?php esc_html_e( 'Êtes-vous sûr de vouloir réinitialiser toute la progression ?', 'winshirt' ); ?>')) {
					$.post(ajaxurl, {
						action: 'ws_reset_roadmap',
						nonce: '<?php echo wp_create_nonce( 'ws_roadmap_nonce' ); ?>'
					}, function(response) {
						if (response.success) {
							location.reload();
						}
					});
				}
			});

			// Toggle terminées
			$('#ws-toggle-completed').on('click', function() {
				$('.ws-completed').toggle();
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX: Mettre à jour le statut d'une tâche
	 */
	public static function ajax_update_task_status() {
		check_ajax_referer( 'ws_roadmap_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$task_id = sanitize_text_field( $_POST['task_id'] ?? '' );
		$completed = intval( $_POST['completed'] ?? 0 );
		
		$completed_tasks = self::get_completed_tasks();
		
		if ( $completed ) {
			if ( ! in_array( $task_id, $completed_tasks ) ) {
				$completed_tasks[] = $task_id;
			}
		} else {
			$completed_tasks = array_diff( $completed_tasks, [ $task_id ] );
		}
		
		update_option( 'winshirt_completed_tasks', array_values( $completed_tasks ) );
		
		wp_send_json_success();
	}

	/**
	 * AJAX: Reset roadmap
	 */
	public static function ajax_reset_roadmap() {
		check_ajax_referer( 'ws_roadmap_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		delete_option( 'winshirt_completed_tasks' );
		wp_send_json_success();
	}
}

WinShirt_Roadmap::init();
}
