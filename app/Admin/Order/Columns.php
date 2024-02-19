<?php
namespace Woo_BG\Admin\Order;

defined( 'ABSPATH' ) || exit;

class Columns {
	function __construct() {
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_order_list_column' ), 11 );
		add_filter( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'add_order_list_column_content' ), 11, 2 );

		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'add_order_list_column' ), 11 );
		add_filter( 'manage_woocommerce_page_wc-orders_custom_column', array( __CLASS__, 'add_order_list_column_content' ), 11, 2 );
	}

	public static function add_order_list_column( $columns ) {
		$reordered_columns = array();

		foreach( $columns as $key => $column){
			$reordered_columns[ $key ] = $column;

			if ( $key ==  'order_status' ) {
				$reordered_columns[ 'order_docs' ] = __( 'Documents', 'woo-bg' );
			}
		}

		return $reordered_columns;
	}

	public static function add_order_list_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'order_docs' :
				$order = wc_get_order( $post_id );
				$files = Documents::get_order_documents( $order );

				foreach ( $files as $file ) {
					?>
					<p>
						<a
							href="<?php echo esc_url( $file[ 'file_url' ] ) ?>"
							target="_blank"
						> 
							<i class="dashicons-before dashicons-pdf"></i>

							<?php echo esc_html( $file[ 'name' ] ) ?>
						</a>
					</p>
					<?php
				}
				
				break;
		}
	}
}
