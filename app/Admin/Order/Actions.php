<?php
namespace Woo_BG\Admin\Order;

use Automattic\WooCommerce\Utilities\OrderUtil;
use Woo_BG\Admin\Tabs\Settings_Tab;
use Woo_BG\Invoice\Document;

defined( 'ABSPATH' ) || exit;

class Actions {
	function __construct() {
		$order_documents_trigger = woo_bg_get_option( 'invoice', 'trigger' );

		if ( !$order_documents_trigger || $order_documents_trigger === "order_created" ) {
			add_action( 'woocommerce_checkout_order_processed', array( '\Woo_BG\Admin\Order\Documents', 'generate_documents' ) );
		} else if ( $order_documents_trigger === "order_completed" ) {
			add_action( 'woocommerce_order_status_completed', array( '\Woo_BG\Admin\Order\Documents', 'generate_documents' ) );
		}

		add_action( 'woocommerce_order_actions', array( __CLASS__, 'add_order_meta_box_actions' ) );
		add_action( 'woocommerce_order_action_woo_bg_regenerate_pdfs', array( __CLASS__, 'process_order_meta_box_actions' ) );
		add_action( 'woocommerce_order_action_woo_bg_generate_invoice', array( __CLASS__, 'generate_invoice_action' ) );

		add_action( 'woocommerce_create_refund', array( __CLASS__, 'create_refund_action' ), 5, 2 );
		
		add_action( 'woocommerce_order_details_after_customer_details', array( __CLASS__, 'add_invoice_to_customer_order' ) );

		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'set_payment_method' ) );
	}

	public static function add_invoice_to_customer_order( $order ) {
		$ids = [ $order->get_id() ];

		if ( $refunds = $order->get_refunds() ) {
			foreach ( $refunds as $refund ) {
				$ids[] = $refund->get_id();
			}
		}
		?>
		<div class="woo-bg--files">
			<?php foreach ($ids as $id ): 
				$order = wc_get_order( $id );
			?>
				<?php if ( $invoice_pdf = $order->get_meta( 'woo_bg_invoice_document' ) ): ?>
					<p>
						<a target="_blank" href="<?php echo wp_get_attachment_url( $invoice_pdf ); ?>" class="woo-bg button button--pdf">
							<?php _e('Invoice PDF', 'woo-bg') ?>
						</a>
					</p>
				<?php endif ?>

				<?php if ( $refunded_invoice_pdf = $order->get_meta( 'woo_bg_refunded_invoice_document' ) ): ?>
					<p>
						<a target="_blank" href="<?php echo wp_get_attachment_url( $refunded_invoice_pdf ); ?>" class="woo-bg button button--pdf">
							<?php _e('Refunded Invoice PDF', 'woo-bg') ?>
						</a>
					</p>
				<?php endif ?>
			<?php endforeach ?>
		</div>
		<?php
	}

	public static function set_payment_method( $post ) {
		$order = wc_get_order( $post );
		$settings = new Settings_Tab();
		$options = $settings->get_localized_fields();

		if ( !isset( $options[ $order->get_payment_method() ] ) ) {
			return;
		}

		$payment_method = $options[ $order->get_payment_method() ];
		$payment_methods = woo_bg_get_payment_types_for_meta();

		$args = array(
			'type' => $payment_method[ 'payment_type' ]['value']['id'],
			'pos_number' => ( $payment_method['virtual_pos_number'] ) ? $payment_method['virtual_pos_number']['value'] : null,
			'identifier' => ( $payment_method['identifier'] ) ? $payment_method['identifier']['value'] : null,
		);

		if ( $payment_methods[ $payment_method[ 'payment_type' ]['value']['id'] ] ) {
			$order->update_meta_data( 'woo_bg_payment_method', $args );
			$order->save();
		}
	}

	public static function add_order_meta_box_actions( $actions ) {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() && isset( $_GET['id'] ) ) {
			$post = get_post( $_GET['id'] );
		} else {
			global $post;
		}

		if ( get_post_type( $post ) !== 'shop_subscription' ) {
			$actions[ 'woo_bg_generate_invoice' ] = __( 'Generate Invoice', 'woo-bg' );
			$actions[ 'woo_bg_regenerate_pdfs' ] = __( 'Regenerate PDF\'s', 'woo-bg' );
		}

		return $actions;
	}

	public static function process_order_meta_box_actions( $order ) {
		Documents::generate_documents( $order->get_id() );
	}

	public static function generate_invoice_action( $order ) {
		( new Document\Invoice( $order ) )->generate_file();
	}

	public static function create_refund_action( $order_get_id, $refund_get_id ) {
        Documents::generate_refunded_documents( $order_get_id->get_id(), $order_get_id->get_parent_id() );
	}
}