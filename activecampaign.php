<?php
/*
Plugin Name: ActiveCampaign
Plugin URI: http://www.activecampaign.com/apps/wordpress
Description: Allows you to add ActiveCampaign contact forms to any post, page, or sidebar. Also allows you to embed <a href="http://www.activecampaign.com/help/site-event-tracking/">ActiveCampaign site tracking</a> code in your pages. To get started, please activate the plugin and add your <a href="http://www.activecampaign.com/help/using-the-api/">API credentials</a> in the <a href="options-general.php?page=activecampaign">plugin settings</a>.
Author: ActiveCampaign
Version: 5.92
Author URI: http://www.activecampaign.com
*/

# Changelog
## version 1: - initial release
## version 1.1: Verified this works with latest versions of WordPress and ActiveCampaign; Updated installation instructions
## version 2.0: Re-configured to work with ActiveCampaign version 5.4. Also improved some areas.
## version 2.1: Changed internal API requests to use only API URL and Key instead of Username and Password. Also provided option to remove style blocks, and converting `input type="button"` into `input type="submit"`
## version 3.0: Re-wrote widget backend to use most recent WordPress Widget structure. Improvements include streamlined code and API usage, ability to reset or refresh your forms, and better form width detection.
## version 3.5: You can now use a shortcode to display your subscription form.
## version 4.0: Added many additional settings to control how your form is displayed and submitted.
## version 4.5: Added ActiveCampaign to the Settings menu so you can use the shortcode independent of the widget.
## version 5.0: Added support for multiple forms. Removed widget entirely.
## version 5.1: Added button to TinyMCE toolbar to more easily choose and embed the form shortcode into the post body.
## version 5.2: Default form behavior is now "sync." This coincided with WordPress version 3.9 release.
## version 5.5: Added site tracking.
## version 5.6: Patched major security bug.
## version 5.7: Removed ability to add custom form "action" URL.
## version 5.8: Security fix.
## version 5.9: Use current user's email for site tracking.
## version 5.91: Updates to avoid conflicts with other plugins using the ActiveCampaign PHP API wrapper.
## version 5.92: Support for captcha validation when using the 'Submit form without refreshing page' (Ajax) option. Also added success or error CSS classes to the Ajax response div.

define("ACTIVECAMPAIGN_URL", "");
define("ACTIVECAMPAIGN_API_KEY", "");
require_once(dirname(__FILE__) . "/activecampaign-api-php/ActiveCampaign.class.php");

function activecampaign_shortcodes($args) {
	// check for Settings options saved first.
	$settings = get_option("settings_activecampaign");
	if ($settings) {
		if (isset($settings["form_html"]) && $settings["form_html"]) {
			if (isset($args) && isset($args["form"])) {
				$form_id = $args["form"];
				if (isset($settings["form_html"][$form_id])) {
					// return the specified form (as long as it's ID exists in the array).
					return $settings["form_html"][$form_id];
				}
			}
		}
	}
	else {
		// try widget options.
		$widget = get_option("widget_activecampaign_widget");
		// it comes out as an array with other things in it, so loop through it
		foreach ($widget as $k => $v) {
			// look for the one that appears to be the ActiveCampaign widget settings
			if (isset($v["api_url"]) && isset($v["api_key"]) && isset($v["form_html"])) {
				$widget_display = $v["form_html"];
				return $widget_display;
			}
		}
	}
  return "";
}

/*
 * The ActiveCampaign settings page.
 */
function activecampaign_plugin_options() {

	if (!current_user_can("manage_options"))  {
		wp_die(__("You do not have sufficient permissions to access this page."));
	}

	$step = 1;
	$instance = array();
	$connected = false;

	if ($_SERVER["REQUEST_METHOD"] == "POST") {

		// saving the settings page.

		if ($_POST["api_url"] && $_POST["api_key"]) {

			$ac = new ActiveCampaignWordPress($_POST["api_url"], $_POST["api_key"]);

			if (!(int)$ac->credentials_test()) {
				echo "<p style='color: red; font-weight: bold;'>" . __("Access denied: Invalid credentials (URL and/or API key).", "menu-activecampaign") . "</p>";
			}
			else {

				$instance = $_POST;

				// first form submit (after entering API credentials).

				// get account details.
				$account = $ac->api("account/view");
				$domain = (isset($account->cname) && $account->cname) ? $account->cname : $account->account;
				$instance["account"] = $domain;

				$user_me = $ac->api("user/me");
				// the tracking ID from the Integrations page.
				$instance["tracking_actid"] = $user_me->trackid;

				// get forms.
				$instance = activecampaign_getforms($ac, $instance);
				$instance = activecampaign_form_html($ac, $instance);
				
				$connected = true;

			}

		}
		else {
			// one or both of the credentials fields is empty. it will just disconnect below because $instance is empty.
		}

		update_option("settings_activecampaign", $instance);

	}
	else {

		$instance = get_option("settings_activecampaign");
//dbg($instance);

		if (isset($instance["api_url"]) && $instance["api_url"] && isset($instance["api_key"]) && $instance["api_key"]) {

			// instance saved already.
			$connected = true;

		}
		else {

			// settings not saved yet.

			// see if they set up our widget (maybe we can pull the API URL and Key from that).
			$widget = get_option("widget_activecampaign_widget");

			if ($widget) {
				// if the ActiveCampaign widget is activated in a sidebar (dragged to a sidebar).

				$widget_info = current($widget); // take the first item.

				if (isset($widget_info["api_url"]) && $widget_info["api_url"] && isset($widget_info["api_key"]) && $widget_info["api_key"]) {
					// if they already supplied an API URL and key in the widget.
					$instance["api_url"] = $widget_info["api_url"];
					$instance["api_key"] = $widget_info["api_key"];
				}
			}

		}

	}

	?>

	<div class="wrap">

		<div id="icon-options-general" class="icon32"><br></div>

		<h2><?php echo __("ActiveCampaign Settings", "menu-activecampaign"); ?></h2>
	
		<p>
			<?php

				echo __("Configure your ActiveCampaign subscription form to be used as a shortcode anywhere on your site. Use <code>[activecampaign form=ID]</code> shortcode in posts, pages, or a sidebar after setting up everything below. Questions or problems? Contact help@activecampaign.com.", "menu-activecampaign");

			?>
		</p>
	
		<form name="activecampaign_settings_form" method="post" action="">

			<hr style="border: 1px dotted #ccc; border-width: 1px 0 0 0; margin-top: 30px;" />

			<h3><?php echo __("API Credentials", "menu-activecampaign"); ?></h3>

			<p>
				<b><?php echo __("API URL", "menu-activecampaign"); ?>:</b>
				<br />
				<input type="text" name="api_url" id="activecampaign_api_url" value="<?php echo esc_attr($instance["api_url"]); ?>" style="width: 400px;" />
			</p>

			<p>
				<b><?php echo __("API Key", "menu-activecampaign"); ?>:</b>
				<br />
				<input type="text" name="api_key" id="activecampaign_api_key" value="<?php echo esc_attr($instance["api_key"]); ?>" style="width: 500px;" />
			</p>

			<?php

				if (!$connected) {

					?>

					<p><?php echo __("Get your API credentials from the Settings > API section:", "menu-activecampaign"); ?></p>
		
					<p><img src="<?php echo plugins_url("activecampaign-subscription-forms"); ?>/settings1.jpg" /></p>

					<?php

				}
				else {

					?>



					<?php

				}

			?>

			<?php

				if (isset($instance["forms"]) && $instance["forms"]) {

					?>

					<hr style="border: 1px dotted #ccc; border-width: 1px 0 0 0; margin-top: 30px;" />

					<h3><?php echo __("Subscription Forms", "menu-activecampaign"); ?></h3>
					<p><i><?php echo __("Choose subscription forms to cache locally. To add new forms go to your <a href=\"http://" . $instance["account"] . "/admin/main.php?action=form\" target=\"_blank\">ActiveCampaign > Integration section</a>.", "menu-activecampaign"); ?></i></p>

					<?php

					// just a flag to know if ANY form is checked (chosen)
					$form_checked = 0;

					$settings_st_checked = (isset($instance["site_tracking"]) && (int)$instance["site_tracking"]) ? "checked=\"checked\"" : "";

					foreach ($instance["forms"] as $form) {

						// $instance["form_id"] is an array of form ID's (since we allow multiple now).

						$checked = "";
						$options_visibility = "none";
						if ($instance["form_id"] && in_array($form["id"], $instance["form_id"])) {
							$checked = "checked=\"checked\"";
							$form_checked = 1;
							$options_visibility = "block";
						}

						$settings_swim_checked = (isset($instance["syim"][$form["id"]]) && $instance["syim"][$form["id"]] == "swim") ? "checked=\"checked\"" : "";
						$settings_sync_checked = (isset($instance["syim"][$form["id"]]) && $instance["syim"][$form["id"]] == "sync") ? "checked=\"checked\"" : "";
						if (!$settings_swim_checked && !$settings_sync_checked) $settings_swim_checked = "checked=\"checked\""; // default
						$settings_ajax_checked = (isset($instance["ajax"][$form["id"]]) && (int)$instance["ajax"][$form["id"]]) ? "checked=\"checked\"" : "";

						$settings_css_checked = "";
						if ( (isset($instance["css"][$form["id"]]) && (int)$instance["css"][$form["id"]]) || !$form_checked) {
							// either it's been checked before, OR
							// no form is chosen yet, so it's likely coming from step 1, so default the CSS checkbox to checked.
							$settings_css_checked = "checked=\"checked\"";
						}

						$settings_action_value = (isset($instance["action"][$form["id"]]) && $instance["action"][$form["id"]]) ? $instance["action"][$form["id"]] : "";

						?>

						<hr style="border: 1px dotted #ccc; border-width: 1px 0 0 0; margin: 30px 0 20px 0;" />

						<input type="checkbox" name="form_id[]" id="activecampaign_form_<?php echo $form["id"]; ?>" value="<?php echo $form["id"]; ?>" onclick="toggle_form_options(this.value, this.checked);" <?php echo $checked; ?> />
						<label for="activecampaign_form_<?php echo $form["id"]; ?>"><a href="http://<?php echo $instance["account"]; ?>/admin/main.php?action=form_edit&id=<?php echo $form["id"]; ?>" target="_blank"><?php echo $form["name"]; ?></a></label>
						<br />
						
						<div id="form_options_<?php echo $form["id"]; ?>" style="display: <?php echo $options_visibility; ?>; margin-left: 30px;">
							<h4><?php echo __("Form Options", "menu-activecampaign"); ?></h4>
							<p><i><?php echo __("Leave as default for normal behavior, or customize based on your needs.", "menu-activecampaign"); ?></i></p>
							<div style="display: none;">
								<input type="radio" name="syim[<?php echo $form["id"]; ?>]" id="activecampaign_form_swim_<?php echo $form["id"]; ?>" value="swim" <?php echo $settings_swim_checked; ?> onchange="swim_toggle(<?php echo $form["id"]; ?>, this.checked);" />
								<label for="activecampaign_form_swim_<?php echo $form["id"]; ?>" style="">Add Subscriber</label>
								<br />
								<input type="radio" name="syim[<?php echo $form["id"]; ?>]" id="activecampaign_form_sync_<?php echo $form["id"]; ?>" value="sync" <?php echo $settings_sync_checked; ?> onchange="sync_toggle(<?php echo $form["id"]; ?>, this.checked);" />
								<label for="activecampaign_form_sync_<?php echo $form["id"]; ?>" style="">Sync Subscriber</label>
								<br />
								<br />
							</div>
							<input type="checkbox" name="ajax[<?php echo $form["id"]; ?>]" id="activecampaign_form_ajax_<?php echo $form["id"]; ?>" value="1" <?php echo $settings_ajax_checked; ?> onchange="ajax_toggle(<?php echo $form["id"]; ?>, this.checked);" />
							<label for="activecampaign_form_ajax_<?php echo $form["id"]; ?>" style="">Submit form without refreshing page</label>
							<br />
							<input type="checkbox" name="css[<?php echo $form["id"]; ?>]" id="activecampaign_form_css_<?php echo $form["id"]; ?>" value="1" <?php echo $settings_css_checked; ?> />
							<label for="activecampaign_form_css_<?php echo $form["id"]; ?>" style="">Keep original form CSS</label>
						</div>
						
						<?php

					}

					?>

					<hr style="border: 1px dotted #ccc; border-width: 1px 0 0 0; margin: 30px 0 20px 0;" />

					<h3><?php echo __("Site Tracking", "menu-activecampaign"); ?></h3>
					<p><i><?php echo __("Site tracking lets you record visitor history on your site to use for targeted segmenting. Learn more on the <a href=\"http://" . $instance["account"] . "/track/\" target=\"_blank\">ActiveCampaign > Integration section</a>.", "menu-activecampaign"); ?></i></p>

					<input type="checkbox" name="site_tracking" id="activecampaign_site_tracking" value="1" <?php echo $settings_st_checked; ?> onchange="site_tracking_toggle(this.checked);" />
					<label for="activecampaign_site_tracking" style=""><?php echo __("Enable Site Tracking", "menu-activecampaign"); ?></label>
					(<a href="http://www.activecampaign.com/help/site-event-tracking/" target="_blank">?</a>)

					<script type='text/javascript'>

						// shows or hides the sub-options section beneath each form checkbox.
						function toggle_form_options(form_id, ischecked) {
							var form_options = document.getElementById("form_options_" + form_id);
							var display = (ischecked) ? "block" : "none";
							form_options.style.display = display;
						}

						//var swim_radio = document.getElementById("activecampaign_form_swim");

						function ac_str_is_url(url) {
							url += '';
							return url.match( /((http|https|ftp):\/\/|www)[a-z0-9\-\._]+\/?[a-z0-9_\.\-\?\+\/~=&#%;:\|,\[\]]*[a-z0-9\/=?&;%\[\]]{1}/i );
						}

						function swim_toggle(form_id, swim_checked) {
							if (swim_checked) {

							}
						}

						function sync_toggle(form_id, sync_checked) {
							var ajax_checkbox = document.getElementById("activecampaign_form_ajax_" + form_id);
							var action_textbox = document.getElementById("activecampaign_form_action_" + form_id);
							if (sync_checked && action_textbox.value == "") {
								// if Sync is chosen, and there is no custom action URL, check the Ajax option.
								ajax_checkbox.checked = true;
							}
						}

						function ajax_toggle(form_id, ajax_checked) {
							var ajax_checkbox = document.getElementById("activecampaign_form_ajax_" + form_id);
							var sync_radio = document.getElementById("activecampaign_form_sync_" + form_id);
							var action_textbox = document.getElementById("activecampaign_form_action_" + form_id);
							var site_tracking_checkbox = document.getElementById("activecampaign_site_tracking");
							if (ajax_checked && site_tracking_checkbox.checked)  {
								alert("If you use this option, site tracking cannot be enabled.");
								site_tracking_checkbox.checked = false;
							}
						}

						function action_toggle(form_id, action_value) {
							var action_textbox = document.getElementById("activecampaign_form_action_" + form_id);
							if (action_textbox.value && ac_str_is_url(action_textbox.value)) {

							}
						}

						function site_tracking_toggle(is_checked) {
							// we can't allow site tracking if ajax is used because that uses the API.
							// so here we check to see if they have chosen ajax for any form, an if so alert them and uncheck the ajax options.
							if (is_checked)  {
								var inputs = document.getElementsByTagName("input");
								// if Sync is checked, and action value is empty or invalid, and they UNcheck Ajax, alert them.
								var checked_already = [];
								for (var i in inputs) {
									var c = inputs[i];
									if (c.type == "checkbox" && c.name.match(/^ajax\[/) && c.checked) {;
										// example: <input type="checkbox" name="ajax[1642]" id="activecampaign_form_ajax_1642" value="1" checked="checked" onchange="ajax_toggle(1642, this.checked);">
										checked_already.push(c.id);
									}
								}
								if (checked_already.length) {
									// if at least one of the ajax checkboxes is checked.
									alert("If you enable site tracking, a page refresh is required.");
									for (var i in checked_already) {
										var id = checked_already[i];
										var dom_item = document.getElementById(id);
										dom_item.checked = false;
									}
								}
							}
						}

					</script>

					<?php

				}

				$button_value = ($connected) ? "Update" : "Connect";

			?>

			<p><button type="submit" style="font-size: 16px; margin-top: 25px; padding: 10px;"><?php echo __($button_value, "menu-activecampaign"); ?></button></p>

		</form>

		<?php

			if (isset($instance["form_html"])) {

				?>

				<hr style="border: 1px dotted #ccc; border-width: 1px 0 0 0; margin-top: 30px;" />
				<h3><?php echo __("Subscription Form(s) Preview", "menu-activecampaign"); ?></h3>	

				<?php

				foreach ($instance["form_html"] as $form_id => $form_html) {
			
					echo $form_html;
					
					?>
					
					<p><?php echo __("Embed using"); ?><code>[activecampaign form=<?php echo $form_id; ?>]</code></p>
					
					<hr style="border: 1px dotted #ccc; border-width: 1px 0 0 0; margin-top: 40px;" />
					
					<?php
				
				}
		
			}

		?>

	</div>

	<?php

}

function ac_dbg($var, $continue = 0, $element = "pre") {
  echo "<" . $element . ">";
  echo "Vartype: " . gettype($var) . "\n";
  if ( is_array($var) ) echo "Elements: " . count($var) . "\n\n";
  elseif ( is_string($var) ) echo "Length: " . strlen($var) . "\n\n";
  print_r($var);
  echo "</" . $element . ">";
  if (!$continue) exit();
}

function activecampaign_getforms($ac, $instance) {
  $forms = $ac->api("form/getforms");
  if ((int)$forms->success) {
    $items = array();
    $forms = get_object_vars($forms);
    foreach ($forms as $key => $value) {
      if (is_numeric($key)) {
        $items[] = get_object_vars($value);
      }
    }
    $instance["forms"] = $items;
  }
  else {
  	if ($forms->error == "Failed: Nothing is returned") {
  		$instance["error"] = "Nothing was returned. Do you have at least one form created in your ActiveCampaign account?";
  	}
  	else {
    	$instance["error"] = $forms->error;
    }
  }
  return $instance;
}

function activecampaign_form_html($ac, $instance) {

	if ($instance["forms"]) {
		foreach ($instance["forms"] as $form) {

			// $instance["form_id"] is an array of form ID's (since we allow multiple now).

			if (isset($instance["form_id"]) && in_array($form["id"], $instance["form_id"])) {

				$form_embed_params = array(
					"id" => $form["id"],
					"ajax" => $instance["ajax"][$form["id"]],
					"css" => $instance["css"][$form["id"]],
				);

				$sync = ($instance["syim"][$form["id"]] == "sync") ? 1 : 0;

				if ($instance["action"][$form["id"]]) {
					$form_embed_params["action"] = $instance["action"][$form["id"]];
				}

				if ((int)$form_embed_params["ajax"] && !isset($form_embed_params["action"])) {
					// if they are using Ajax, but have not provided a custom action URL, we need to push it to a script where we can submit the form/process API request.
					// remove the "http(s)" portion, because it was conflicting with the Ajax request (I was getting 404's).
					$api_url_process = preg_replace("/https:\/\//", "", $instance["api_url"]);
					$form_embed_params["action"] = plugins_url("form_process.php?sync=" . $sync, __FILE__);
				}

				// prepare the params for the API call
				$api_params = array();
				foreach ($form_embed_params as $var => $val) {
					$api_params[] = $var . "=" . urlencode($val);
				}

				// fetch the HTML source
				$html = $ac->api("form/embed?" . implode("&", $api_params));

				if ((int)$form_embed_params["ajax"]) {
					// used for the result message that is displayed after submitting the form via Ajax
					$html = "<div id=\"form_result_message\"></div>" . $html;
				}

				if ($html) {
					if ($instance["account"]) {
						// replace the API URL with the account URL (IE: https://account.api-us1.com is changed to http://account.activehosted.com).
						// (the form has to submit to the account URL.)
						if (!$instance["action"]) {
							$protocol = "";
							$domain = $instance["account"];
							if (strpos($domain, "activehosted.com") === false) { 
								$protocol = "http:";
							}
							$html = preg_replace("/action=['\"][^'\"]+['\"]/", "action='" . $protocol . "//" . $domain . "/proc.php'", $html);
						}
					}
					// replace the Submit button to be an actual submit type.
					//$html = preg_replace("/input type='button'/", "input type='submit'", $html);
				}

				if ((int)$form_embed_params["css"]) {
					// get the style content so we can prepend each rule with the form ID (IE: #_form_1341).
					// this is in case there are multiple forms on the same page - their styles need to be unique.
					preg_match_all("|<style[^>]*>(.*)</style>|iUs", $html, $style_blocks);
					if (isset($style_blocks[1]) && isset($style_blocks[1][0]) && $style_blocks[1][0]) {
						$css = $style_blocks[1][0];
						// remove excess whitespace from within the string.
						$css = preg_replace("/\s+/", " ", $css);
						// remove whitespace from beginning and end of string.
						$css = trim($css);
						$css_rules = explode("}", $css);
						$css_rules_new = array();
						foreach ($css_rules as $rule) {
							$rule_array = explode("{", $rule);
							$rule_array[0] = preg_replace("/\s+/", " ", $rule_array[0]);
							$rule_array[0] = trim($rule_array[0]);
							$rule_array[1] = preg_replace("/\s+/", " ", $rule_array[1]);
							$rule_array[1] = trim($rule_array[1]);
							if ($rule_array[1]) {
								// there could be comma-separated rules.
								$rule_array2 = explode(",", $rule_array[0]);
								foreach ($rule_array2 as $rule_) {
									$rule_ = "#_form_" . $form["id"] . " " . $rule_;
									$css_rules_new[] = $rule_ . " {" . $rule_array[1] . "}";
								}
							}
						}
					};

					$new_css = implode("\n\n", $css_rules_new);
					// remove existing styles.
					$html = preg_replace("/<style[^>]*>(.*)<\/style>/s", "", $html);
					// replace with updated CSS string.
					$html = "<style>" . $new_css . "</style>" . $html;
				}

				// check for custom width.
				if ((int)$form["widthpx"]) {
					// if there is a custom width set
					// find the ._form CSS rule
					preg_match_all("/\._form {[^}]*}/", $html, $_form_css);
					if (isset($_form_css[0]) && $_form_css[0]) {
						foreach ($_form_css[0] as $_form) {
							// find "width:400px"
							preg_match("/width:[0-9]+px/", $_form, $width);
							if (isset($width[0]) && $width[0]) {
								// IE: replace "width:400px" with "width:200px"
								$html = preg_replace("/" . $width[0] . "/", "width:" . (int)$form["widthpx"] . "px", $html);
							}
						}
					}
				}

				$instance["form_html"][$form["id"]] = $html;

			}

		}
  }
  else {
		// no forms created in the AC account yet.
		echo "<p style='color: red;'>" . __("Make sure you have at least one form created in ActiveCampaign.") . "</p>";
  }

  return $instance;
}

function activecampaign_register_widgets() {
  register_widget("ActiveCampaign_Widget");
}

function activecampaign_display($args) {
  extract($args);
}

function activecampaign_register_shortcodes() {
  add_shortcode("activecampaign", "activecampaign_shortcodes");
}

function activecampaign_plugin_menu() {
	add_options_page(__("ActiveCampaign Settings", "menu-activecampaign"), __("ActiveCampaign", "menu-activecampaign"), "manage_options", "activecampaign", "activecampaign_plugin_options");
}

function activecampaign_editor_buttons() {
	add_filter("mce_external_plugins", "activecampaign_add_buttons");
	add_filter("mce_buttons", "activecampaign_register_buttons");
}

function activecampaign_add_buttons($plugin_array) {
	$plugin_array["activecampaign_editor_buttons"] = plugins_url("editor_buttons.js", __FILE__);
	return $plugin_array;
}

function activecampaign_register_buttons($buttons) {
	array_push($buttons, "activecampaign_editor_forms");
	return $buttons;
}

//add_action("widgets_init", "activecampaign_register_widgets");
add_action("init", "activecampaign_register_shortcodes");
add_action("init", "activecampaign_editor_buttons");
add_action("admin_menu", "activecampaign_plugin_menu");
add_filter("widget_text", "do_shortcode");

global $pagenow;

add_action("wp_ajax_activecampaign_get_forms", "activecampaign_get_forms_callback");
add_action("wp_ajax_activecampaign_get_forms_html", "activecampaign_get_forms_html_callback");
add_action("admin_enqueue_scripts", "activecampaign_custom_wp_admin_style");
add_action("wp_enqueue_scripts", "activecampaign_frontend_scripts");
add_action("admin_enqueue_scripts", "activecampaign_backend_scripts");

// get the raw forms data (array) for use in multiple spots.
function activecampaign_get_forms_ajax() {
	// get forms that are cached after setting things up from the ActiveCampaign settings page.
	global $wpdb; // this is how you get access to the database
	$forms = array();
	$settings = get_option("settings_activecampaign");
//ac_dbg($settings);
	if ($settings["form_id"]) {
		foreach ($settings["forms"] as $form) {
			if (in_array($form["id"], $settings["form_id"])) {
				$forms[$form["id"]] = $form["name"];
			}
		}
	}
	return $forms;
}

// JSON output.
function activecampaign_get_forms_callback() {
	$forms = activecampaign_get_forms_ajax();
	$forms = json_encode($forms);
	echo $forms;
	die();
}

// HTML output of forms (for the post dialog/window after you click the icon in the editor toolbar).
// version 3.9 has this.
function activecampaign_get_forms_html_callback() {
	$forms = activecampaign_get_forms_ajax();
	echo "<div style='font-family: Arial, Helvetica, sans-serif; font-size: 13px;'>";
	echo "<p>" . __("Choose an integration form below to embed into your post or page body. Add or edit forms in ActiveCampaign and then refresh the forms on the <a href='" . get_site_url() . "/wp-admin/options-general.php?page=activecampaign' target='_blank'>Settings page</a>.") . "</p>";
	if ($forms) {
		echo "<ul style='list-style-type: none; padding: 0; margin: 0 0 0 5px;'>";
	}
	foreach ($forms as $formid => $formname) {
		echo "<li style='margin-bottom: 8px;'><a href='#' onclick='parent.activecampaign_editor_form_embed(" . $formid . "); return false;'>" . $formname . "</a></li>";
	}
	if ($forms) {
		echo "</ul>";
	}
	echo "</div>";
	die();
}

function activecampaign_custom_wp_admin_style() {
	wp_register_style("activecampaign-subscription-forms", "//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css");
	wp_enqueue_style("activecampaign-subscription-forms");
	wp_enqueue_script("jquery-ui-dialog");
	wp_enqueue_style("wp-jquery-ui-dialog");
}

// scripts run only on the front-end.
function activecampaign_frontend_scripts() {
	wp_enqueue_script("site_tracking", plugins_url("site_tracking.js", __FILE__), array(), false, true);
	$settings = get_option("settings_activecampaign");
	unset($settings["api_url"]);
	unset($settings["api_key"]);
	$current_user = wp_get_current_user();
	$user_email = "";
	if (isset($current_user->data->user_email)) {
		$user_email = $current_user->data->user_email;
	}
	// any data we need to access in JavaScript.
	$data = array(
		"site_url" => __(site_url()),
		"ac_settings" => $settings,
		"user_email" => $user_email,
	);
	wp_localize_script("site_tracking", "php_data", $data);
}

function activecampaign_backend_scripts() {
	if (in_array($GLOBALS["pagenow"], array('post.php', 'page.php', 'post-new.php', 'post-edit.php'))) {
		// this loads the JavaScript file on pages where we use it (any post page that uses the Editor).
		wp_enqueue_script("editor_pages", plugins_url("editor_pages.js", __FILE__), array(), false, true);
		// any data we need to access in JavaScript.
		$data = array(
			"site_url" => __(site_url()),
			"wp_version" => $GLOBALS["wp_version"],
		);
		wp_localize_script("editor_pages", "php_data", $data);
	}	
}

?>
