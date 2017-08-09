<?php
/*
Plugin Name: Formilla Chat and Marketing Automation
Plugin URI: http://www.formilla.com
Description: Customer Support Software for your WooCommerce store with live chat, offline email support, and in-app messaging using Formilla's marketing automation tools.
Version: 1.0
Author: Formilla.com
Author URI: http://www.formilla.com/
Text Domain: formilla-chat-and-marketing
*/

$plugurldir = plugin_dir_url( __FILE__ );
load_plugin_textdomain('formilla-chat-and-marketing', false, basename( dirname( __FILE__ ) ));

add_action('init', 'formilla_tools_init');
add_action('wp_footer', 'formilla_tools_script', 100);
add_action('wp_ajax_fcm_save_formilla_tools_settings', 'fcm_save_formilla_tools_settings');
add_filter('plugin_action_links', 'formilla_tools_plugin_actions', 10, 2);
add_filter('plugin_row_meta', 'formilla_tools_plugin_links',10,2);
register_uninstall_hook(__FILE__, 'formilla_tools_uninstall');

add_action('wp_ajax_formilla_tools_get_wc_cart_ajax_action', 'formilla_tools_get_wc_cart_ajax_action');
add_action('wp_ajax_nopriv_formilla_tools_get_wc_cart_ajax_action', 'formilla_tools_get_wc_cart_ajax_action');

define('FORMILLA_TOOLS_DASH', "https://www.formilla.com/dashboard.aspx");
define('FORMILLA_TOOLS_REG', "https://www.formilla.com/sign-up.aspx?u=wpwc");

function formilla_tools_init() {
    if(function_exists('current_user_can') && current_user_can('manage_options')) {
        add_action('admin_menu', 'formilla_add_tools_settings_page');
        add_action('admin_menu', 'formilla_tools_create_menu');
    }
}

function fcm_save_formilla_tools_settings() {
	$nonce = $_GET['_wpnonce'];

	if (!wp_verify_nonce($nonce,'update-options'))
	{
		echo "Error";
	    die( 'Security check' );
	}
	else
	{
	    $formillaToolsID = filter_var(trim($_GET['FormillaToolsID']), FILTER_SANITIZE_STRING);
				
		// Validate Plugin ID
		if(strlen($formillaToolsID) != 36)
		{
			echo "Error";
			die( 'InvalidPluginID' );				
		}		

		$succeeded = add_option('FormillaToolsID', $formillaToolsID);

		if(!$succeeded)
		{
			update_option('FormillaToolsID', $formillaToolsID);
		}

		echo "Success";
		die(); // this is required to return a proper result
	}
}

/**
* Display the launch link if a FormillaToolsID exists for this user.
* Otherwise, display the signup page.
*/
function Formilla_tools_dashboard() {
	?>
	<br /> <br />
    <img src="<?php echo plugin_dir_url( __FILE__ ).'main-logo.png'; ?>"/>

    <?php

    if(!get_option('FormillaToolsID'))
    {
    ?>
    	   <form method="post" id="optionsform" action="options.php">
				<div class="error settings-error" id="setting-error-invalid_admin_email" style="margin: 4px 0px 5px 0px; width: 1100px;">
					<p style="padding:0px;">
						<?php echo '<a href="'.FORMILLA_TOOLS_REG.'"';?>  onclick="window.open('<?php echo FORMILLA_TOOLS_REG;?>', 'signuptab'); return false;">Sign Up</a> and save the Plugin ID you receive to activate your account.<br/><br/>
						<?php wp_nonce_field('update-options') ?>
						<label for="FormillaToolsID">
						<input type="text" name="FormillaToolsID" id="FormillaToolsID" value="<?php echo(get_option('FormillaToolsID')) ?>" style="width:300px" />
						<input type="hidden" name="page_options" value="FormillaToolsID" />
						<input type="submit" onclick="fcmSaveFormillaToolsSettings();return false;" name="formillaSettingsSubmit" id="formillaSettingsSubmit" value="<?php _e('Save Settings') ?>" class="button-primary" />
					</p>
				</div>
		   </form>
		   <p id="successMessage" style="display:none; color:green;">Your settings were saved successfully.  Your chat widget should now appear on your website!</p>
		   <p id="failureMessage" style="display:none; color:red;">There was an error saving your settings.  Please try again.</p>

	<?php
	    }
	?>

		<div class="metabox-holder" id="formillaLinks" <?php  if(!get_option('FormillaToolsID')){echo 'style="display:none"';} ?> >
			<div class="postbox">
				<div style="padding:10px;">
				<?php echo '<a href="'.FORMILLA_TOOLS_DASH.'"';?> onclick="window.open('<?php echo FORMILLA_TOOLS_DASH;?>', 'signuptab'); return false;window.focus()">Launch</a> the Formilla.com Dashboard.
				<br/><br/>
				<a href="options-general.php?page=formilla-chat-and-marketing">Modify</a> my Formilla Plugin ID.
				</div>
			</div>
		</div>


    <script>
    	function fcmSaveFormillaToolsSettings()
    	{
    		if(!fcmVerifyFormillaToolsID())
    		{
				alert('You entered an invalid Plugin ID.  Please try again.');
				return false;
    		}

			var data = { action: 'fcm_save_formilla_tools_settings' };

			jQuery.post(ajaxurl + '?' + jQuery('#optionsform').serialize(), data, function(response)
			{
				if(response == 'Success')
				{
					jQuery('#optionsform').hide();
					jQuery('#failureMessage').hide();
					jQuery('#successMessage').show();
					jQuery('#formillaLinks').slideDown(600);
					setTimeout('jQuery("#successMessage").slideUp(1000)', 10000);
				}
				else
				{
					var errorMessage = 'There was an error saving your settings.  Please try again.';
					
					if(response == 'ErrorInvalidPluginID')
					{
						errorMessage = 'You entered an invalid Plugin ID.  Please try again.';
					}
					
					jQuery('#failureMessage').text(errorMessage);
					jQuery('#failureMessage').show();
				}
			});
		}

		function fcmVerifyFormillaToolsID() {
		    if(jQuery('#FormillaToolsID').val().trim().length != 36)
		    	return false;
		    else
		    	return true;
		}

	</script>
	<?php
}

/**
* The actual Formilla script to create the chat button on the wordpress site.
*/
function formilla_tools_script() {
    global $current_user;

    if(get_option('FormillaToolsID')) {
		echo("\n\n <div id=\"formillatools\" style=\"z-index:100 \"></div><div id=\"formillawindowholder\"><span style=\"display:none\"></span></div><script type=\"text/javascript\">");
		  echo("   (function () { ");
		    echo("  var head = document.getElementsByTagName(\"head\").item(0); ");
		    echo("  var script = document.createElement('script'); ");
		    echo("  var src = (document.location.protocol == \"https:\" ? 'https://www.formilla.com/scripts/feedback.js' : 'http://www.formilla.com/scripts/feedback.js');");
		    echo("  script.setAttribute(\"type\", \"text/javascript\"); script.setAttribute(\"src\", src); script.setAttribute(\"async\", true); ");
		    echo("  var complete = false; ");

		    echo("  script.onload = script.onreadystatechange = function () { ");
		    echo("  if (!complete && (!this.readyState || this.readyState == 'loaded' || this.readyState == 'complete')) { ");
		    echo("   complete = true; ");
		    echo("   Formilla.guid = '".get_option('FormillaToolsID')."';");
		    if(is_user_logged_in()) {
		    	get_currentuserinfo();
		    	echo("  Formilla.customerEmail = '".$current_user->user_email."';");
		    }

		    $current_user = wp_get_current_user();
		    $customer_id = $current_user->ID;

			if (formilla_tools_is_wc_active()) {
				$wc_cart_json = formilla_tools_get_wc_cart_content_as_json();
				
				echo ("Formilla.cart = " . ($wc_cart_json == 'null' 
												? "'';" 
												: "JSON.stringify($wc_cart_json);"));
			}
			
			echo("   Formilla.loadWidgets(); ");						
		    echo("     }");
		    echo("   }; ");

		    echo("   head.appendChild(script); ");
		    echo("  })(); ");
				
			if (formilla_tools_is_wc_active()) {	
				$ajax_url = admin_url() . 'admin-ajax.php';
			?>			
				(function () {
					if(window.jQuery)
					{
						// jQuery is defined
						jQuery(document.body).on('added_to_cart updated_cart_totals', function() { 
							formillaUpdateWcCart(); 
						});
					}				
				})();			
				
				function formillaUpdateWcCart() {
					var params = 'action=formilla_tools_get_wc_cart_ajax_action';
					var obj;
					try {
						obj = new XMLHttpRequest();
					} catch(e){
						try {
						obj = new ActiveXObject("Msxml2.XMLHTTP");
						} catch(e) {
							try {
							obj = new ActiveXObject("Microsoft.XMLHTTP");
							} catch(e) {
								return false;
							}
						}
					}
					obj.onreadystatechange = function() {
						if(obj.readyState === 4) {
							if(obj.responseText == "null") {
								Formilla.cart = '';
							} else {
								Formilla.cart = JSON.stringify(JSON.parse(obj.responseText));;
							}
							
							// Update Formilla thru feedback.js
							var formillaService = new FormillaService();
							formillaService.updateShoppingCart(Formilla.cart);
						}
					}
					obj.open('POST', '<?php echo $ajax_url ?>', true);
					obj.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=UTF-8");
					obj.send(params);
				}
			<?php		
			}			
    		echo(" </script> ");
    }
}

function formilla_tools_plugin_links($links, $file) {
	$base = plugin_basename(__FILE__);
	if ($file == $base) {
		$links[] = '<a href="options-general.php?page=formilla-chat-and-marketing">' . __('Settings','formilla-chat-and-marketing') . '</a>';
	}
	return $links;
}

function formilla_tools_plugin_actions($links, $file) {
    static $this_plugin;
    if(!$this_plugin) $this_plugin = plugin_basename(__FILE__);
    if($file == $this_plugin && function_exists('admin_url')) {

        if(trim(get_option('FormillaToolsID')) == "") {
        	$settings_link = '<a href="'.admin_url('admin.php?page=Formilla_tools_dashboard').'">'.__('Get Started').'</a>';
        }
        else {
        	$settings_link = '<a href="'.admin_url('options-general.php?page=formilla-chat-and-marketing').'">'.__('Settings').'</a>';
        }

        array_unshift($links, $settings_link);
    }
    return($links);
}

/**
* Formilla Tools Settings page. Once user signs up, Formilla Plugin ID must be entered here to activate account.
*/
function formilla_add_tools_settings_page() {
    function formilla_tools_settings_page() {
        global $plugurldir; ?>
<div class="wrap">
        <?php screen_icon() ?>
    <img src="<?php echo plugin_dir_url( __FILE__ ).'main-logo.png'; ?>"/>
    <div class="metabox-holder meta-box-sortables ui-sortable pointer">
        <div class="postbox" style="float:left;width:40em;margin-right:10px">
            <div class="inside" style="padding: 0 10px">
                <form method="post" action="options.php">
                    <p style="text-align:center">
                    <?php wp_nonce_field('update-options') ?>
                    <p><label for="FormillaToolsID">Activate Formilla Chat and Marketing Automation by entering the Plugin ID received when registering.

                    <?php
						if(trim(get_option('FormillaToolsID')) == "") {
					?>
							If you don't have an account, click <a href="admin.php?page=Formilla_tools_dashboard">here</a> to get started.
					<?php
						}
					?>
                    <input type="text" name="FormillaToolsID" id="FormillaToolsID" value="<?php echo(get_option('FormillaToolsID')) ?>" style="width:100%" /></p>
                    <p class="submit" style="padding:0">
						<input type="hidden" name="action" value="update" />
                        <input type="hidden" name="page_options" value="FormillaToolsID" />
                        <input type="submit" name="formillaSettingsSubmit" id="formillaSettingsSubmit" value="<?php _e('Save Settings') ?>" class="button-primary" />
					</p>
               </form>
            </div>
        </div>
    </div>
</div>

    <?php }
    add_submenu_page('options-general.php', __('Formilla Tools Settings'), __('Formilla Tools Settings'), 'manage_options', 'formilla-chat-and-marketing', 'formilla_tools_settings_page');
}

function formilla_tools_create_menu() {
    //create new top-level menu
    add_menu_page('Account Configuration', 'Formilla Tools', 'administrator', 'Formilla_tools_dashboard', 'Formilla_tools_dashboard', plugin_dir_url( __FILE__ ).'logo.png');
    add_submenu_page('Formilla_tools_dashboard', 'Dashboard', 'Dashboard', 'administrator', 'Formilla_tools_dashboard', 'Formilla_tools_dashboard');
}

function formilla_tools_uninstall() {
    if(get_option('FormillaToolsID')) {
	    delete_option( 'FormillaToolsID');
	}
}

function formilla_tools_get_wc_cart_ajax_action() {
	echo formilla_tools_get_wc_cart_content_as_json();
		
	wp_die();
}

function formilla_tools_is_wc_active() {
	return class_exists( 'WooCommerce' );
}

function formilla_tools_get_wc_cart_content_as_json(){
	if (formilla_tools_is_wc_active()) {
		$data = formilla_tools_get_wc_cart_content();			
	} else {
		$data = null;	
	}
	
	return json_encode($data);
}

function formilla_tools_get_wc_cart_content() {
	$wc_cart = WC()->cart;
					
	if(!$wc_cart->is_empty()) {
		$items = $wc_cart->get_cart();										
		
		$formillaToolsVisitorCartDto = new FormillaToolsVisitorCartDto();
		$formillaToolsVisitorCartDto->st = $wc_cart->cart_contents_total;				
		$formillaToolsVisitorCartDto->gt = WC()->cart->total;
								
		foreach($items as $item => $product) {
			$formillaToolsVisitorCartItemDto = new FormillaToolsVisitorCartItemDto();
			$formillaToolsVisitorCartItemDto->pn = $product['data']->post->post_title;
			$formillaToolsVisitorCartItemDto->pq = $product['quantity'];
			$formillaToolsVisitorCartItemDto->pp = get_post_meta($product['product_id'] , '_price', true);
			
			array_push($formillaToolsVisitorCartDto->ps, $formillaToolsVisitorCartItemDto);		
		}
		
		return $formillaToolsVisitorCartDto;
	} else {
		return null;
	}
}

class FormillaToolsVisitorCartDto {
    public $ps = array();
	public $st;
    public $gt;	
}

class FormillaToolsVisitorCartItemDto {
    public $pn;
    public $pq;
    public $pp;
}

?>