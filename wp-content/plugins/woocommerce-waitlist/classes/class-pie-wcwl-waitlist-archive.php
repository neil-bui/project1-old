<?php
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * The Waitlist Archive Admin Page
 *
 * @package WooCommerce Waitlist
 * @since   1.4.0
 */
class Pie_WCWL_Waitlist_Archive {

	public $parent = '';

	/**
	 * Base function to output standard header and wrapper html for archive
	 * Calls output_html
	 */
	public function render_archived_waitlist_page() {
		$product_id = $_GET['product_id'];

		if ( isset( $_GET['wcwl-delete-archive'] ) &&
		     isset( $_GET['wcwl_delete_archive_nonce'] ) &&
		     wp_verify_nonce( $_GET['wcwl_delete_archive_nonce'], 'wcwl_delete_archive' ) ) {

			delete_post_meta( $_GET['wcwl-delete-archive'], 'wcwl_waitlist_archive' );
		}

		echo '<div class="wrap" >';
		echo '<h2>' . __( 'Archived Waitlists for ', 'woocommerce-waitlist' ) . get_the_title( $product_id ) . '</h2>';

		$this->output_html( $product_id );

		echo '</div>';
	}

	/**
	 * Determine product type and call appropriate function to output html for archive
	 *
	 * @param int $product_id current product ID
	 */
	public function output_html( $product_id ) {
		$this->parent = $product_id;
		$product      = get_product( $product_id );

		if ( $product->is_type( 'variable' ) ) {
			echo $this->return_html_for_variable_product( $product );
		} else {
			echo $this->return_html_for_simple_product( $product_id );
		}
	}

	/**
	 * Return html required for a simple product archive
	 *
	 * @param  int    $product_id current product ID
	 * @return string $html
	 */
	public function return_html_for_simple_product( $product_id ) {
		$archives = $this->retrieve_and_sort_archives( $product_id );
		$html     = '';

		if ( is_array( $archives ) ) {
			$html       .= $this->return_html_for_archive_list( $product_id, $archives );
		} else {
			$html = '<p>' . esc_html__( 'There are no archived waitlists for this product.', 'woocommerce-waitlist' ) . '</p>';
		}
		return $html;
	}

	/**
	 * Return html required for a variable product archive
	 *
	 * @param  object $product current parent product
	 * @return string $html
	 */
	public function return_html_for_variable_product( $product ) {
		$children      = $product->get_available_variations();
		$html          = '<div id="wcwl_varition_archive">';
		$have_archives = false;

		foreach ( $children as $child ) {
			$archives = $this->retrieve_and_sort_archives( $child['variation_id'] );

			if ( is_array( $archives ) ) {
				$have_archives = true;
				$html         .= '<div class="wcwl_variation_tab">';
				$html         .= '<div class="wcwl_variation_title"><h3>Archives for ' . $this->return_variation_title( $child ) . '</h3></div>';
				$html         .= $this->return_html_for_archive_list( $child['variation_id'], $archives );
				$html         .= '</div>';
			}
		}

		if ( ! $have_archives ) {
			$html = '<p>' . esc_html__( 'There are no archived waitlists for this product.', 'woocommerce-waitlist' ) . '</p>';
		} else {
			$html .= '</div>';
		}
		return $html;
	}

	/**
	 * Return attributes as string to form tab title
	 *
	 * @param  object $variation current variation object
	 *
	 * @return string $title     title string for current tab
	 */
	public function return_variation_title( $variation ) {
		$title = '#' . $variation['variation_id'];
		if ( !empty( $variation['attributes'] ) ) {
			$title .= ' - ';
			foreach ( $variation['attributes'] as $attribute ) {
				$title .= ucwords( $attribute ) . ' ';
			}
		}
		return $title;
	}

	/**
	 * Retrieve archives for current product from database and sort in reverse time order
	 *
	 * @param  int   $product_id current product ID
	 *
	 * @return mixed
	 */
	public function retrieve_and_sort_archives( $product_id ) {
		$archives = get_post_meta( $product_id, 'wcwl_waitlist_archive', true );

		if( is_array( $archives ) ) {
			krsort( $archives );
		}
		return $archives;
	}

	/**
	 * Return html required for the current archive list
	 *
	 * @param  int     $product_id current product ID
	 * @param  array   $archives   archived lists for this product
	 *
	 * @return string  $html       html required to display the archives
	 */
	public function return_html_for_archive_list( $product_id, $archives ) {
		$html = '<ul class="wcwl_archive_list">';
		foreach ( $archives as $time => $waitlist ) {
			$html .= '<li class="wcwl_archive_' . $product_id . '" ><h4>' . __( 'The following users were mailed an in-stock notification on ', 'woocommerce-waitlist' ) . date( 'd M y H:i:s', $time ) .
			         '<div class="dashicons dashicons-email-alt wcwl_email_all_tab"></div>
				          <a class="wcwl_mail_all_link" href="' .  esc_url_raw( $this->get_mailto_link_content( $waitlist ) ) . '">' . __( 'Email all users on this list', 'woocommerce-waitlist' ) . '</a></h4>';
			$html .= '<table class="widefat wcwl_archive_list">';
			foreach ( $waitlist as $user_id ) {
				$userdata = get_userdata( $user_id );
				$html    .= '<tr><td class="wcwl_archive_email">' . $userdata->user_email . '</td>';
				$html    .= '<td class="wcwl_archive_mailto"><a href="mailto:' . $userdata->user_email . '" title="' . esc_attr__( 'Email User', 'woocommerce-waitlist' ) . '" ><div class="dashicons dashicons-email-alt"></div></a></td>';
				$html    .= '</tr>';
			}
			$html .= '</table></li>';
		}
		$delete_url  = wp_nonce_url( admin_url( '?page=wcwl-waitlist-archive&product_id=' . $this->parent . '&wcwl-delete-archive=' . $product_id ), 'wcwl_delete_archive', 'wcwl_delete_archive_nonce' );
		$html       .= '<li class="wcwl_delete_archive"><a href="' . $delete_url . '"><button class="button" id="wcwl_delete_archives_" ' . $product_id . '>' . __( 'Delete all archives', 'woocommerce-waitlist' ) . '</button></a>';
		$html       .= '</li></ul>';
		return $html;
	}

	/**
	 * Returns information needed for the 'email user' links in product tab
	 *
	 * @access private
	 *
	 * @param $waitlist
	 *
	 * @return string 'mailto' information required
	 */
	private function get_mailto_link_content( $waitlist ) {
		$emails       = array();
		foreach ( $waitlist as $user_id ) {
			$userdata = get_userdata( $user_id );
			$emails[] = $userdata->user_email;
		}
		return 'mailto:' . get_option( 'woocommerce_email_from_address' ) . '?bcc=' . implode( ',', $emails ) ;
	}
}