<?php
/**
 * Services list admin template.
 *
 * Variables available: $services (AppointKit_Service[]).
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
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Services', 'appointkit' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=appointkit-services&action=new' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'appointkit' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( $appointkit_saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Service saved.', 'appointkit' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $appointkit_deleted ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Service deleted.', 'appointkit' ); ?></p></div>
	<?php endif; ?>

	<table class="wp-list-table widefat fixed striped table-view-list">
		<thead>
			<tr>
				<th scope="col" class="column-primary"><?php esc_html_e( 'Name', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Duration', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Price', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Buffers', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Status', 'appointkit' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $services ) ) : ?>
			<tr class="no-items">
				<td class="colspanchange" colspan="5">
					<?php esc_html_e( 'No services yet. Add your first service to start taking bookings.', 'appointkit' ); ?>
				</td>
			</tr>
		<?php else : ?>
			<?php foreach ( $services as $appointkit_service ) : ?>
				<?php
				$appointkit_edit_url   = admin_url( 'admin.php?page=appointkit-services&action=edit&id=' . absint( $appointkit_service->id ) );
				$appointkit_delete_url = wp_nonce_url(
					admin_url( 'admin.php?page=appointkit-services&action=delete&id=' . absint( $appointkit_service->id ) ),
					'appointkit_delete_service_' . absint( $appointkit_service->id )
				);
				?>
				<tr>
					<td class="column-primary has-row-actions">
						<span class="appointkit-swatch" aria-hidden="true"
							style="background: <?php echo esc_attr( $appointkit_service->color ); ?>;"></span>
						<strong><a href="<?php echo esc_url( $appointkit_edit_url ); ?>"><?php echo esc_html( $appointkit_service->name ); ?></a></strong>
						<div class="row-actions">
							<span class="edit">
								<a href="<?php echo esc_url( $appointkit_edit_url ); ?>"><?php esc_html_e( 'Edit', 'appointkit' ); ?></a> |
							</span>
							<span class="trash">
								<a href="<?php echo esc_url( $appointkit_delete_url ); ?>" class="appointkit-confirm submitdelete">
									<?php esc_html_e( 'Delete', 'appointkit' ); ?>
								</a>
							</span>
						</div>
					</td>
					<td><?php echo esc_html( appointkit_format_duration( $appointkit_service->duration ) ); ?></td>
					<td><?php echo esc_html( appointkit_format_price( $appointkit_service->price ) ); ?></td>
					<td>
						<?php
						printf(
							/* translators: 1: buffer before in minutes, 2: buffer after in minutes */
							esc_html__( '%1$d min before, %2$d min after', 'appointkit' ),
							absint( $appointkit_service->buffer_before ),
							absint( $appointkit_service->buffer_after )
						);
						?>
					</td>
					<td>
						<span class="appointkit-status appointkit-status--<?php echo esc_attr( $appointkit_service->status ); ?>">
							<?php echo esc_html( 'active' === $appointkit_service->status ? __( 'Active', 'appointkit' ) : __( 'Inactive', 'appointkit' ) ); ?>
						</span>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
