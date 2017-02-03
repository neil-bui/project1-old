<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (!class_exists('phoen_old_product_data_tab')) {

	class phoen_old_product_data_tab {
		
		public $tab_class = '';
		
		public $tab_additional_class = '';
		
		public $tab_id = '';
		
		public $tab_title = '';
		
		public $tab_icon = '';
		
		public $tab_script_src = '';

		public function __construct($tab_class, $tab_id, $tab_title, $tab_icon = '', $script = false) {

			if (is_array($tab_class)) {
				
				$this->tab_class = $tab_class[0];
				
				for ($x = 1; $x < count($tab_class); $x++) {
				
					$this->tab_additional_class .= ' ' . $tab_class[$x];
				}
			} else {
				
				$this->tab_class = $tab_class;
			
			}
			
			$this->tab_id = $tab_id;
			
			$this->tab_title = $tab_title;
			
			$this->tab_icon = $tab_icon;

			$this->tab_script_src;

			add_action('woocommerce_init', array(&$this, 'on_woocommerce_init'));

			add_action('woocommerce_product_write_panel_tabs', array(&$this, 'product_write_panel_tabs'), 99);
			
			add_action('woocommerce_product_write_panels', array(&$this, 'product_data_panel_wrap'), 99);
			
			add_action('woocommerce_process_product_meta', array(&$this, 'process_meta_box'), 1, 2);
		}

		public function on_woocommerce_init() {
			
			global $woocommerce;
			
			if (empty($this->tab_icon)) {
				
				$wc_default_icons = $woocommerce->plugin_url() . '/assets/images/logo-phoeniixx.png';
				
				$this->tab_icon = $wc_default_icons;
			}
		}


		public function product_write_panel_tabs() {
			
			?>
			
				<li class="<?php echo $this->tab_class; ?><?php echo $this->tab_additional_class; ?>"><a href="#<?php echo $this->tab_id; ?>"><?php echo $this->tab_title; ?></a></li>
			
			<?php
		}

		public function product_data_panel_wrap() {
			
			?>
			
				<div id="<?php echo $this->tab_id; ?>" class="panel <?php echo $this->tab_class; ?> woocommerce_options_panel">
				
				<?php $this->render_product_tab_content(); ?>
				
				</div>
			
			<?php
				
		}

		public function render_product_tab_content() {
				
		}

		public function process_meta_box($post_id, $post) {
				
		}

	}

}
	?>