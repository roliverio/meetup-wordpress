<?php
/**
 * Plugin Name:     Notify page updates
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Notify users when a page is updated.
 * Author:          Miguel Useche
 * Author URI:      https://migueluseche.com/
 * Text Domain:     notify-update
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Notify_Update
 */

// Add the metabox to the page editor
function npu_add_metabox() {
	add_meta_box(
		'npu_metabox',
		'Notify users',
		'npu_render_metabox',
		'page',
		'normal',
		'high'
	);
}

add_action( 'add_meta_boxes', 'npu_add_metabox' );

// Render the metabox HTML
function npu_render_metabox( $post ) {
	// Retrieve the saved meta data
	$npu_role    = get_post_meta( $post->ID, 'npu_role', true );
	$npu_subject = get_post_meta( $post->ID, 'npu_subject', true );
	$npu_message = get_post_meta( $post->ID, 'npu_message', true );

	// Output the HTML for the metabox fields
	?>
	<div>
		<label for="npu_role">Recipient Role</label><br/>
		<select name="npu_role" id="npu_role">
			<?php
			// Retrieve all user roles
			$roles = get_editable_roles();
			foreach ( $roles as $role => $details ) {
				echo '<option value="' . esc_attr( $role ) . '" ' . selected( $npu_role, $role, false ) . '>' . esc_html( $details['name'] ) . '</option>';
			}
			?>
		</select>
	</div>
	<br>
	<div>
		<label for="npu_subject">Subject</label><br/>
		<input
			type="text"
			name="npu_subject"
			id="npu_subject"
			value="<?php echo esc_attr( $npu_subject ); ?>"
			style="width: 100%; padding: 5px;"
		>
	</div>
	<br>
	<div>
		<label>Message</label> <br/>
		<?php
		$editor_settings = array(
			'textarea_name' => 'npu_message',
			'textarea_rows' => 5,
		);
		wp_editor( $npu_message, 'npu_message', $editor_settings );
		?>
	</div>
	<?php
}

// Save the metabox data
function npu_save_metabox( $post_id ) {
	if ( isset( $_POST['npu_subject'] ) ) {
		update_post_meta( $post_id, 'npu_subject', sanitize_text_field( $_POST['npu_subject'] ) );
	}
	if ( isset( $_POST['npu_message'] ) ) {
		update_post_meta( $post_id, 'npu_message', wp_kses_post( $_POST['npu_message'] ) );
	}
	if ( isset( $_POST['npu_role'] ) ) {
		update_post_meta( $post_id, 'npu_role', sanitize_text_field( $_POST['npu_role'] ) );
	}
}

add_action( 'save_post_page', 'npu_save_metabox' );


// Send email on page save
function npu_send_email( $post_id ) {
	$npu_message = get_post_meta( $post_id, 'npu_message', true );

	if ( empty( $npu_message ) ) {
		return;
	}

	$npu_subject = get_post_meta( $post_id, 'npu_subject', true );
	$npu_role    = get_post_meta( $post_id, 'npu_role', true );

	$users         = get_users( array(
		'role__in' => $npu_role
	) );
	$email_headers = array( 'Content-Type: text/html' );

	foreach ( $users as $user ) {
		$email_headers[] = 'Cc: ' . $user->user_email;
	}

	if ( count( $email_headers ) > 1 ) {
		$admin_email = get_bloginfo( 'admin_email' );
		wp_mail( $admin_email, $npu_subject, $npu_message, $email_headers );
	}
}

add_action( 'save_post_page', 'npu_send_email' );
