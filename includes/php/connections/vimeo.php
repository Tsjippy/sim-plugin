<?php
namespace SIM;

//remove_the default vimeo media submenu
add_action( 'admin_menu', 'SIM\disable_category_menu',99);
function disable_category_menu () {
	//remove the old one
	remove_submenu_page("upload.php","dgv-library");
	//add a new one
	add_media_page(
		__( 'WP Vimeo Library', 'wp-vimeo-videos' ),
		'Vimeo',
		'upload_files',
		'simnigeria_add_vimeo',
		'SIM\add_vimeo_media_menu_output'
	);
}

//Render the output of the new menu item
function add_vimeo_media_menu_output(){
	ob_start();
	if ( isset( $_GET['action'] ) && isset( $_GET['id'] )&& $_GET['action'] === 'delete' ) {
		$vimeo_helper = new \WP_DGV_Api_Helper();
		$db_helper = new \WP_DGV_Db_Helper();
		if ( $vimeo_helper->is_connected ) {
			//Get the vimeo id
			$vimeo_id = $db_helper->get_vimeo_id( $_GET['id'] );
			//Remove the wp post
			$wp_result = wp_delete_post($_GET['id'],true);
			print_array("Deleted the page ".$_GET['id']);
			//Deleting video on vimeo
			$vimeo_result = $vimeo_helper->delete("/videos/$vimeo_id");

			//Showing the result
			if($wp_result and !isset($vimeo_result['body']['error'])){
				?>
				<style>
				.notice-success {
					background-color: #dff0d8;
					border: 1px solid #d6e9c6;
					color: #3c763d;
					padding: 10px;
					margin: 10px 0 20px 0;
				}
				</style>

				<div class="notice-success">
					<p>Video removed successfully.</p>
				</div>
				<?php
			}else{
				?>
				<style>
				.notice-error {
					background-color: #f2dede;
					color: #a94442;
					border: 1px solid #ebccd1;
					margin: 10px 10px 20px;
					padding: 10px;
					-webkit-border-radius: 3px;
					-moz-border-radius: 3px;
					border-radius: 3px;
					font-size: 13px;
				}
				</style>
				<div class='notice-error'>
					<p>
				<?php
				if(!$wp_result) echo "The post with id {$_GET['id']} could not be found.";
				if(isset($vimeo_result['body']['error'])) echo $vimeo_result['body']['error'];
				echo "</p></div>";
			}
		}
		
		
	}
			
	
	?>
	<h2><?php _e( 'Vimeo Videos', 'wp-vimeo-videos' ); ?></h2>

		<a href="<?php echo admin_url( 'upload.php?page=' . \WP_DGV_Admin::PAGE_VIMEO . '&action=new' ); ?>"
		   class="page-title-action button"><?php _e( 'Upload new', 'wp-vimeo-videos' ); ?></a>

		<?php if ( current_user_can( 'manage_options' ) ): ?>
			<a href="<?php echo admin_url( 'options-general.php?page=' . \WP_DGV_Admin::PAGE_SETTINGS . '&action=settings' ); ?>"
			   class="page-title-action button"><?php _e( 'Settings', 'wp-vimeo-videos' ); ?></a>
		<?php endif; ?>

	

	<form method="post">

		<input type="hidden" name="page" value="test_list_table">

		<?php

		$list_table = new List_Table();

		$list_table->prepare_items();

		//$list_table->search_box( 'search', 'search_id' ); //TODO

		$list_table->display();

		?>

	</form>

	<?php

	echo ob_get_clean();
}

if ( ! class_exists( 'WP_DGV_List_Table' ) ) {
	if(!file_exists(ABSPATH . 'wp-content/plugins/wp-vimeo-videos/includes/class-wp-dgv-list-table.php')) return;
	
	require_once ABSPATH . 'wp-content/plugins/wp-vimeo-videos/includes/class-wp-dgv-list-table.php';
	
}

/**
 * The videos list table used to display all the videos
 *
 * @since      1.0.0
 * @package    WP_DGV
 * @subpackage WP_DGV/includes
 * @copyright     Darko Gjorgjijoski <info@codeverve.com>
 * @license    GPLv2
 */
class List_Table extends \WP_DGV_List_Table {
	/**
	 * Render the designation name column
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	public function column_title( $item ) {
		$actions           	= array();
		$url               	= admin_url( 'upload.php?page=' . \WP_DGV_Admin::PAGE_VIMEO . '&action=edit&id=' . $item->ID );
		$vimeo_uri  		= $this->db_helper->get_vimeo_uri($item->ID);
		$vimeo_id   		= $this->db_helper->get_vimeo_id($item->ID);
		$vimeo_link       	= 'https://vimeo.com/'.$vimeo_id;
		$delete_url         = admin_url( 'upload.php?page=simnigeria_add_vimeo&action=delete&id=' . $item->ID );
		$actions['edit']   	= sprintf( '<a href="%s" data-id="%d" title="%s">%s</a>', $url, $item->ID, __( 'Manage this video', 'wp-vimeo-videos' ), __( 'Manage', 'wp-vimeo-videos' ) );
		$actions['vimeo'] 	= sprintf('<a href="%s" target="_blank" data-id="%d" title="%s">%s</a>', $vimeo_link, $item->ID, __('Vimeo video link', 'wp-vimeo-videos'), __('Vimeo Link', 'wp-vimeo-videos'));
		$actions['remove']  = sprintf( '<a href="%s" data-id="%d" title="%s">%s</a>', $delete_url, $item->ID, __( 'Delete this video', 'wp-vimeo-videos' ), __( 'Delete', 'wp-vimeo-videos' ) );

		return sprintf( '<a href="%1$s"><strong>%2$s</strong></a> %3$s', $url, $item->post_title, $this->row_actions( $actions ) );
	}
}

add_action( 'dgv_after_upload', '_dgv_after_uploadn', 10, 2 );
function _dgv_after_uploadn( $response, $api ) {
	//Hide the video from vimeo
	$uri = wvv_response_to_uri( $response );
	if ( ! empty( $uri ) ) {
		try {
			$response = $api->request( $uri, array(
				'privacy' => array(
					'view' => "disable"
				)
			), 'PATCH' );
		} catch ( \Exception $e ) {
			print_array( 'Hide Vimeo video: ' . $e->getMessage() );
		}
	} else {
		print_array( 'Hide Vimeo video: Video not found.');
	}
	
	wp_send_json_success( array(
		'message' => 'Video uploaded successfully.<br>I have already put the shortcode for the video, in the "Post Content" field for you.<br>This code will be replaced by the video on publish.<br>You can close this window now.',
	) );
}
