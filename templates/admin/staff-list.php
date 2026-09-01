<?php
/**
 * Staff list admin template.
 *
 * Variables available: $staff_list (AppointKit_Staff[]).
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only display flags.
$appointkit_saved   = isset( $_GET['saved'] );
$appointkit_deleted = isset( $_GET['deleted'] );
// phpcs:enable WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap appointkit-admin">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Staff', 'appointkit' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=appointkit-staff&action=new' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'appointkit' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( $appointkit_saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Staff member saved.', 'appointkit' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $appointkit_deleted ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Staff member deleted.', 'appointkit' ); ?></p></div>
	<?php endif; ?>

	<table class="wp-list-table widefat fixed striped table-view-list">
		<thead>
			<tr>
				<th scope="col" class="column-primary"><?php esc_html_e( 'Name', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Email', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Timezone', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Google Calendar', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Status', 'appointkit' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $staff_list ) ) : ?>
			<tr class="no-items">
				<td class="colspanchange" colspan="5">
					<?php esc_html_e( 'No staff yet. Add a staff member, then set their availability.', 'appointkit' ); ?>
				</td>
			</tr>
		<?php else : ?>
			<?php foreach ( $staff_list as $appointkit_member ) : ?>
				<?php
				$appointkit_edit_url   = admin_url( 'admin.php?page=appointkit-staff&action=edit&id=' . absint( $appointkit_member->id ) );
				$appointkit_avail_url  = admin_url( 'admin.php?page=appointkit-availability&staff_id=' . absint( $appointkit_member->id ) );
				$appointkit_delete_url = wp_nonce_url(
					admin_url( 'admin.php?page=appointkit-staff&action=delete&id=' . absint( $appointkit_member->id ) ),
					'appointkit_delete_staff_' . absint( $appointkit_member->id )
				);
				?>
				<tr>
					<td class="column-primary has-row-actions">
						<strong><a href="<?php echo esc_url( $appointkit_edit_url ); ?>"><?php echo esc_html( $appointkit_member->name ); ?></a></strong>
						<div class="row-actions">
							<span class="edit">
								<a href="<?php echo esc_url( $appointkit_edit_url ); ?>"><?php esc_html_e( 'Edit', 'appointkit' ); ?></a> |
							</span>
							<span class="view">
								<a href="<?php echo esc_url( $appointkit_avail_url ); ?>"><?php esc_html_e( 'Availability', 'appointkit' ); ?></a> |
							</span>
							<span class="trash">
								<a href="<?php echo esc_url( $appointkit_delete_url ); ?>" class="appointkit-confirm submitdelete">
									<?php esc_html_e( 'Delete', 'appointkit' ); ?>
								</a>
							</span>
						</div>
					</td>
					<td>
						<?php if ( $appointkit_member->email ) : ?>
							<a href="mailto:<?php echo esc_attr( $appointkit_member->email ); ?>"><?php echo esc_html( $appointkit_member->email ); ?></a>
						<?php else : ?>
							<span aria-hidden="true">&#45;</span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $appointkit_member->timezone ); ?></td>
					<td>
						<?php if ( ! empty( $appointkit_member->google_calendar_token ) ) : ?>
							<span class="appointkit-status appointkit-status--active"><?php esc_html_e( 'Connected', 'appointkit' ); ?></span>
						<?php else : ?>
							<span class="appointkit-status appointkit-status--inactive"><?php esc_html_e( 'Not connected', 'appointkit' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<span class="appointkit-status appointkit-status--<?php echo esc_attr( $appointkit_member->status ); ?>">
							<?php echo esc_html( 'active' === $appointkit_member->status ? __( 'Active', 'appointkit' ) : __( 'Inactive', 'appointkit' ) ); ?>
						</span>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
