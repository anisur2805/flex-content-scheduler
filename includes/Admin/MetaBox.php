<?php
/**
 * Meta box for content expiry scheduling.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler\Admin;

use Anisur\ContentScheduler\Helpers\DateTimeHelper;
use Anisur\ContentScheduler\Loader;
use Anisur\ContentScheduler\Scheduler\ScheduleManager;

/**
 * Class MetaBox
 *
 * Adds a meta box to post edit screens for scheduling content expiry.
 *
 * @since 1.0.0
 */
class MetaBox {
	/**
	 * Schedule manager instance.
	 *
	 * @since 1.0.0
	 * @var ScheduleManager
	 */
	private ScheduleManager $schedule_manager;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ScheduleManager $schedule_manager Schedule manager instance.
	 */
	public function __construct( ScheduleManager $schedule_manager ) {
		$this->schedule_manager = $schedule_manager;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 *
	 * @param Loader $loader Hook loader instance.
	 * @return void
	 */
	public function register_hooks( Loader $loader ): void {
		$loader->add_action( 'add_meta_boxes', $this, 'register_meta_box' );
		$loader->add_action( 'save_post', $this, 'save_meta_box_data' );
		$loader->add_action( 'quick_edit_custom_box', $this, 'render_quick_edit_fields', 10, 2 );
		$loader->add_action( 'bulk_edit_custom_box', $this, 'render_bulk_edit_fields', 10, 2 );
		$loader->add_action( 'save_post', $this, 'save_inline_edit_data' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_scripts' );
	}

	/**
	 * Register meta box for supported post types.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_meta_box(): void {
		$post_types = $this->get_supported_post_types();

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'flex-cs-content-expiry',
				__( 'Content Expiry Schedule', 'flex-content-scheduler' ),
				array( $this, 'render' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render meta box content.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post Current post object.
	 * @return void
	 */
	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'flex_cs_metabox_save', 'flex_cs_metabox_nonce' );
		echo '<div id="flex-cs-metabox-root" data-post-id="' . esc_attr( (string) $post->ID ) . '"></div>';
	}

	/**
	 * Save meta box data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_meta_box_data( int $post_id ): void {
		if ( ! isset( $_POST['flex_cs_metabox_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['flex_cs_metabox_nonce'] ) ), 'flex_cs_metabox_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( empty( $_POST['flex_cs_expiry_action'] ) || empty( $_POST['flex_cs_expiry_date'] ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['flex_cs_expiry_action'] ) );
		$date   = sanitize_text_field( wp_unslash( $_POST['flex_cs_expiry_date'] ) );

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $date ) ) {
			return;
		}

		$utc_date = gmdate( 'Y-m-d H:i:s', strtotime( $date ) );
		$data     = array(
			'post_id'       => $post_id,
			'expiry_date'   => $utc_date,
			'expiry_action' => $action,
			'redirect_url'  => isset( $_POST['flex_cs_redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['flex_cs_redirect_url'] ) ) : '',
			'new_status'    => isset( $_POST['flex_cs_new_status'] ) ? sanitize_key( wp_unslash( $_POST['flex_cs_new_status'] ) ) : '',
		);

		$existing = $this->schedule_manager->get_schedule_by_post( $post_id );

		if ( isset( $_POST['flex_cs_delete_schedule'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['flex_cs_delete_schedule'] ) ) ) {
			if ( $existing ) {
				$this->schedule_manager->delete_schedule( (int) $existing->id );
			}
			return;
		}

		if ( $existing ) {
			$this->schedule_manager->update_schedule( (int) $existing->id, $data );
			return;
		}

		$this->schedule_manager->create_schedule( $data );
	}

	/**
	 * Render custom fields in Quick Edit.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column_name Current column name.
	 * @param string $post_type   Current post type.
	 * @return void
	 */
	public function render_quick_edit_fields( string $column_name, string $post_type ): void {
		if ( 'title' !== $column_name || ! in_array( $post_type, $this->get_supported_post_types(), true ) ) {
			return;
		}

		$this->render_inline_fields();
	}

	/**
	 * Render custom fields in Bulk Edit.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column_name Current column name.
	 * @param string $post_type   Current post type.
	 * @return void
	 */
	public function render_bulk_edit_fields( string $column_name, string $post_type ): void {
		if ( 'title' !== $column_name || ! in_array( $post_type, $this->get_supported_post_types(), true ) ) {
			return;
		}

		$this->render_inline_fields();
	}

	/**
	 * Save expiry schedule data submitted from Quick Edit or Bulk Edit.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_inline_edit_data( int $post_id ): void {
		if ( ! isset( $_POST['flex_cs_inline_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['flex_cs_inline_nonce'] ) ), 'flex_cs_inline_edit' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$existing = $this->schedule_manager->get_schedule_by_post( $post_id );

		$remove_schedule = isset( $_POST['flex_cs_inline_remove'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['flex_cs_inline_remove'] ) );
		if ( $remove_schedule ) {
			if ( $existing ) {
				$this->schedule_manager->delete_schedule( (int) $existing->id );
			}
			return;
		}

		if ( empty( $_POST['flex_cs_inline_expiry_action'] ) || empty( $_POST['flex_cs_inline_expiry_date'] ) ) {
			return;
		}

		$date = sanitize_text_field( wp_unslash( $_POST['flex_cs_inline_expiry_date'] ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $date ) ) {
			return;
		}

		$data = array(
			'post_id'       => $post_id,
			'expiry_date'   => $this->convert_local_datetime_to_utc( $date ),
			'expiry_action' => sanitize_key( wp_unslash( $_POST['flex_cs_inline_expiry_action'] ) ),
			'redirect_url'  => isset( $_POST['flex_cs_inline_redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['flex_cs_inline_redirect_url'] ) ) : '',
			'new_status'    => isset( $_POST['flex_cs_inline_new_status'] ) ? sanitize_key( wp_unslash( $_POST['flex_cs_inline_new_status'] ) ) : '',
		);

		if ( $existing ) {
			$this->schedule_manager->update_schedule( (int) $existing->id, $data );
			return;
		}

		$this->schedule_manager->create_schedule( $data );
	}

	/**
	 * Enqueue scripts and styles for meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		global $post;

		wp_enqueue_script(
			'flex-cs-metabox',
			FLEX_CS_PLUGIN_URL . 'assets/dist/metabox.js',
			array( 'wp-element', 'wp-api-fetch', 'wp-i18n' ),
			FLEX_CS_VERSION,
			true
		);

		wp_enqueue_style(
			'flex-cs-metabox',
			FLEX_CS_PLUGIN_URL . 'assets/dist/metabox.css',
			array(),
			FLEX_CS_VERSION
		);

		wp_localize_script(
			'flex-cs-metabox',
			'flexCSMetabox',
			array(
				'restUrl'  => esc_url_raw( rest_url( 'flex-cs/v1' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'postId'   => isset( $post->ID ) ? (int) $post->ID : 0,
				'schedule' => isset( $post->ID ) ? $this->schedule_manager->get_schedule_by_post( (int) $post->ID ) : null,
			)
		);
	}

	/**
	 * Render shared inline-edit schedule fields.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function render_inline_fields(): void {
		wp_nonce_field( 'flex_cs_inline_edit', 'flex_cs_inline_nonce' );
		?>
		<fieldset class="inline-edit-col-left flex-cs-inline-edit">
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php echo esc_html__( 'Expiry Date', 'flex-content-scheduler' ); ?></span>
					<span class="input-text-wrap"><input type="datetime-local" name="flex_cs_inline_expiry_date" /></span>
				</label>
				<label>
					<span class="title"><?php echo esc_html__( 'Action', 'flex-content-scheduler' ); ?></span>
					<span class="input-text-wrap">
						<select name="flex_cs_inline_expiry_action">
							<option value="unpublish"><?php echo esc_html__( 'Unpublish', 'flex-content-scheduler' ); ?></option>
							<option value="delete"><?php echo esc_html__( 'Delete', 'flex-content-scheduler' ); ?></option>
							<option value="redirect"><?php echo esc_html__( 'Redirect', 'flex-content-scheduler' ); ?></option>
							<option value="change_status"><?php echo esc_html__( 'Change status', 'flex-content-scheduler' ); ?></option>
						</select>
					</span>
				</label>
				<label>
					<span class="title"><?php echo esc_html__( 'Redirect URL', 'flex-content-scheduler' ); ?></span>
					<span class="input-text-wrap"><input type="url" name="flex_cs_inline_redirect_url" /></span>
				</label>
				<label>
					<span class="title"><?php echo esc_html__( 'New Status', 'flex-content-scheduler' ); ?></span>
					<span class="input-text-wrap">
						<select name="flex_cs_inline_new_status">
							<option value="draft"><?php echo esc_html__( 'Draft', 'flex-content-scheduler' ); ?></option>
							<option value="private"><?php echo esc_html__( 'Private', 'flex-content-scheduler' ); ?></option>
							<option value="pending"><?php echo esc_html__( 'Pending', 'flex-content-scheduler' ); ?></option>
							<option value="publish"><?php echo esc_html__( 'Publish', 'flex-content-scheduler' ); ?></option>
						</select>
					</span>
				</label>
				<label>
					<span class="title"><?php echo esc_html__( 'Remove schedule', 'flex-content-scheduler' ); ?></span>
					<span class="input-text-wrap"><input type="checkbox" name="flex_cs_inline_remove" value="1" /></span>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Get supported post types for schedule controls.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	private function get_supported_post_types(): array {
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		/**
		 * Filter the post types that support content expiry scheduling.
		 *
		 * @since 1.0.0
		 *
		 * @param array<int, string> $post_types Array of post type names.
		 */
		$post_types = apply_filters( 'flex_cs_supported_post_types', $post_types );

		return is_array( $post_types ) ? $post_types : array();
	}

	/**
	 * Convert a local datetime-local value to UTC (Y-m-d H:i:s).
	 *
	 * @since 1.0.0
	 *
	 * @param string $local_datetime Datetime-local value.
	 * @return string UTC datetime string.
	 */
	private function convert_local_datetime_to_utc( string $local_datetime ): string {
		$timezone = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : 'UTC';
		if ( '' === $timezone ) {
			$timezone = 'UTC';
		}

		try {
			return DateTimeHelper::to_utc( str_replace( 'T', ' ', $local_datetime ) . ':00', $timezone );
		} catch ( \Throwable $exception ) {
			return gmdate( 'Y-m-d H:i:s', strtotime( $local_datetime ) );
		}
	}
}
