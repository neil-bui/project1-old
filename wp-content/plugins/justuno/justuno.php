<?php
/**
 * @package Justuno
 */
/*
Plugin Name: Justuno Social Offers
Plugin URI: 
Description: Grow your social audience, email subscribers & sales!
Version: 1.2.2
Author: Justuno
Author URI: http://www.justuno.com
License: GPLv2 or later
*/


// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

include_once(dirname(__FILE__).'/JustunoAccess.php');

class WpJustuno{
	const APP_ID = 'c33bef26-8df0-4c19-9208-171f994796f8';
	private $domainName;
	
    public function __construct(){	
		$this->domainName = preg_replace('/^www\./','',$_SERVER['SERVER_NAME']);	
        if(is_admin()){
			add_action('admin_menu', array($this, 'add_plugin_page'));
			add_action('admin_init', array($this, 'page_init'));			
			add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2);
		}
		else{		
			add_action('wpsc_purchase_log_insert',array($this,'checkoutComplete'));
			add_action('wp_head',array($this,'place_script'));
		}
		
    }
	
    public function add_plugin_page(){
        // This page will be under "Settings"
		add_options_page('Settings Admin', 'Justuno', 'manage_options', 'justuno-settings-conf', array($this, 'create_admin_page'));
    }
    
    public function plugin_action_links( $links, $file ){
        if (basename($file) != basename( __FILE__ ))
			return $links;

		//  create the custom link to the Justuno Settings
		$link = sprintf( '<a href="%s?page=justuno-settings-conf" title="%s">%s</a>',
						esc_url( admin_url( 'options-general.php' ) ),						 
						__( 'Settings'),
						__( 'Settings') );
		//  add the custom url to the $links array
		array_unshift($links,$link);

		//  return the links array
		return $links;
    }
   

   public function create_admin_page(){		
        ?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>Justuno Social Offers</h2>			
			<form method="post" action="options.php">
				<?php
				if(isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true'){			
					$justuno_conf = get_option('justuno_conf');	
					$accSettings = array('apiKey'=>self::APP_ID,'email'=>$justuno_conf['email'],'domain'=>$this->domainName);
					if(isset($justuno_conf['password']) && $justuno_conf['password'] != ""){
						$accSettings['password'] = $justuno_conf['password'];
					}
					$jAccess = new JustunoAccess($accSettings);
					try{
						$conf = $jAccess->getWidgetConfig();
						$justuno_conf['embed'] = $conf['embed'];
						$justuno_conf['conversion'] = $conf['conversion'];
						$justuno_conf['guid'] = $conf['guid'];
						update_option('justuno_conf',$justuno_conf);
					}
					catch(JustunoAccessException $e){	
						$this->displayError($e->getMessage());						
					}										
				}		
				// This prints out all hidden setting fields
				settings_fields('justuno_option_group');	
				do_settings_sections('justuno-main-settings');
				?>
				<div style="margin-left:300px">
				<?php
				submit_button('Update');
				?>
				</div>
				<?php
				do_settings_sections('justuno-ext-settings');
			?>				
			</form>
			<?php $justuno_conf = get_option('justuno_conf');
			if($justuno_conf){
				if(!isset($jAccess)){
					$jAccess = new JustunoAccess(array('apiKey'=>self::APP_ID,'email'=>$justuno_conf['email'],'domain'=>$this->domainName,'guid'=>$justuno_conf['guid']));
				}
				$link = $jAccess->getDashboardLink();				
			}
			else{
				$link = 'http://www.justuno.com/getstarted.html';
			}
			?>
			<a class="button button-primary" href='<?php echo $link;?>' target='_blank'>Justuno Dashboard</a>
		</div>
		<?php
    }
	
    public function page_init(){		
		register_setting('justuno_option_group', 'justuno_conf');
			
		add_settings_section(
			'setting_section_1',
			'1. Create or Connect Existing Justuno Account:',
			array($this, 'print_section_info'),
			'justuno-main-settings'
		);	
		
		add_settings_section(
			'setting_section_id1',
			'2. Set Up or Manage Account:',
			array($this, 'print_section_info1'),
			'justuno-ext-settings'
		);	
			
		add_settings_field(
			'email', 
			'Email', 
			array($this, 'create_email_field'), 
			'justuno-main-settings',
			'setting_section_1'			
		);	
		
		add_settings_field(
			'password', 
			'Password', 
			array($this, 'create_password_field'), 
			'justuno-main-settings',
			'setting_section_1'			
		);	
			
		add_settings_field(
			'domain', 
			'Domain', 
			array($this, 'create_domain_field'), 
			'justuno-main-settings',
			'setting_section_1'			
		);		
		
			
    }	
   
	
    public function print_section_info(){
		print '';
    }
    
    public function print_section_info1(){
		print '';
    }
	
    public function create_email_field(){
		$options = get_option('justuno_conf');
		$email = $options['email'];
        ?><input type="email" id="email" name="justuno_conf[email]" value="<?php echo $email;?>" ><?php
    }
    
    public function create_domain_field(){		
        ?><input type="text" id="domain" name="justuno_conf[domain]" value="<?php echo $this->domainName;?>" readonly ><?php
    }
    
    public function create_password_field(){
		$options = get_option('justuno_conf');
		$password = $options['password'];
        ?><input type="password" id="password" name="justuno_conf[password]" value="<?php echo $password;?>" ><?php
    }
    
    public function place_script(){		
		$justuno_conf = get_option('justuno_conf');
		if(!$justuno_conf || !$justuno_conf['embed'])
			return false;
		?><script>
			<?php echo $justuno_conf['embed']?>
		</script>		
		<?php
		
		if ($justuno_conf && $justuno_conf['conversion'] && $this->checkConversion() ){
		?><script>
			<?php echo $justuno_conf['conversion']?>
		</script>
		<?php
		}
	}

    public function checkConversion(){		
		if(isset($_SESSION['jus_conversion']) && $_SESSION['jus_conversion'] == 1){
			unset($_SESSION['jus_conversion']);
			return 1;
		}
		return 0;
    }   
	
	public function checkoutComplete(){		
		$_SESSION['jus_conversion'] = 1;
	}
    
    private function displayError($msg){
		?>
		<div id="message" class="error">
			<?php echo $msg?>
		</div>
		<?php
	}
	
	private function debugLog($data){
		file_put_contents(dirname( __FILE__ ) . '/log.txt',$data."\r\n",FILE_APPEND);
	}
}

$wpjustuno = new WpJustuno();
?>
