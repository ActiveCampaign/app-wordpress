=== ActiveCampaign ===
Contributors: activecampaign
Tags: activecampaign, email-marketing, newsletter, marketing-automation, subscribe, forms, emails, automation
Requires at least: 2
Tested up to: 4.2
Stable tag: trunk

Add ActiveCampaign contact forms to any post, page, or sidebar. Also enable ActiveCampaign site tracking for your WordPress blog.

== Description ==

Connect your WordPress blog with your ActiveCampaign email marketing platform. Embed subscription forms designed in ActiveCampaign directly into any post, page, or sidebar. Also allows you to enable [ActiveCampaign site tracking](http://www.activecampaign.com/help/site-event-tracking/) with your Wordpress blog.

After installing go to Settings > ActiveCampaign to activate this plugin. You will need your [ActiveCampaign API URL and key](http://www.activecampaign.com/help/using-the-api/), and at least one integration form created in your ActiveCampaign account.

[Sign up for your free account](http://www.activecampaign.com/free/) to get started.

== Installation ==

This section describes how to install the plugin and get it working. Please see [our additional help documentation](http://www.activecampaign.com/help/integrating-subscription-forms-with-wordpress/) for more detailed information.

1. Upload the entire "activecampaign" zip file to the Plugins section of WordPress, or "Add New" plugin and search for "activecampaign."
2. Visit the Settings > ActiveCampaign section in WordPress.
3. Fill in your ActiveCampaign connection details, then hit Update.
4. Choose which subscription forms to cache locally, any optional sub-settings for each, then hit Update again.
5. Use `[activecampaign form=ID]` shortcode to display a form anywhere on your site where shortcode syntax is supported.
6. Embed multiple forms onto a single page!
7. Enable site tracking to have page visits tracked to ActiveCampaign.

== Frequently Asked Questions ==

= How do I create ActiveCampaign subscription forms to use in WordPress? =

You need to be using [ActiveCampaign email marketing platform](http://www.activecampaign.com/) to use this widget. Create new subscription forms in the platform by going to the "Integration" section, then they will be available through this plugin.

= How does this plugin differ from copying and pasting the subscription form onto my site manually? =

This plugin makes it much easier to do without requiring you to know which theme (or core WordPress) files to modify. Also, copying and pasting HTML into WordPress can often cause odd display issues (depending on your WordPress theme).

= What happens after someone submits the subscription form on my WordPress site? =

The same thing that would happen if they submitted it from another site: it redirects back to the ActiveCampaign confirmation message, or a custom URL if you have that set up for the subscription form in ActiveCampaign (modify your forms under the "Integration" section).

If you enable the "Ajax" option of the plugin settings, you can have the form submitted without the page reloading (so the viewer never leaves the page).

= Can my form require an opt-in email confirmation be sent? =

Yes, you would just make sure that your form settings (in ActiveCampaign) have the Opt-in confirmation setting checked.

= I get a "Connection failed" message. What does this mean? =

Please make sure that your login information is correct, and that you have at least one Integration form already created in the ActiveCampaign system.

== Screenshots ==

1. Settings page for ActiveCampaign plugin
2. Configuring your form settings
3. Using the [activecampaign] shortcode in a blog post
4. Viewing the output of the [activecampaign] shortcode
5. Adding a form to the sidebar

== Changelog ==

= 1.0 =
* Initial release.

= 1.1 =
* Verified this works with latest versions of WordPress and ActiveCampaign.
* Updated installation instructions.

= 2.0 =
* Re-configured to work with ActiveCampaign version 5.4.
* Improved some areas.

= 2.1 =
* Changed internal API requests to use only API URL and Key instead of Username and Password.
* Provided option to remove style blocks from embedded form code, and converting `input type="button"` into `input type="submit"`.

= 3.0 =
* Re-wrote widget backend to use most recent WordPress Widget structure.
* Streamlined code and API usage.
* Ability to reset or refresh your forms.
* Better form width detection.

= 3.5 =
* You can now use a shortcode to display your subscription form.

= 4.0 =
* Added many additional settings to control how your form is displayed and submitted.

= 4.5 =
* Added ActiveCampaign to the Settings menu so you can use the shortcode independent of the widget.

= 5.0 =
* Added support for multiple forms. Removed widget entirely.	

= 5.1 =
* Added button to TinyMCE toolbar to more easily choose and embed the form shortcode into the post body.

= 5.2 =
* Default form behavior is now "sync." This coincided with WordPress version 3.9 release.

= 5.5 =
* Added site tracking (optional setting).

= 5.6 =
* Patched major security bug.

= 5.7 =
* Removed ability to add custom form "action" URL.

= 5.8 =
* Security fix.

= 5.9 =
* Use current user's email for site tracking.

= 5.91 =
* Updates to avoid conflicts with other plugins using the ActiveCampaign PHP API wrapper.

= 5.92 =
* Support for captcha validation when using the 'Submit form without refreshing page' (Ajax) option.
* Added success or error CSS classes to the Ajax response div.

== Upgrade Notice ==

= 1.1 =
* Installation instructions updated if you are having trouble installing it.

= 2.0 =
* Version 2.0 will NOT work with ActiveCampaign versions < 5.4.

= 2.1 =
* This version requires the use of API URL and Key instead of Username and Password.

= 4.0 =
* If you use the Ajax option, you will need jQuery enabled for your WordPress site.

= 5.0 =
* The widget is removed entirely (in favor of the shortcode) so if you have a form in a sidebar, you'll now need to add a basic text widget with the shortcode in it. You choose your forms under the ActiveCampaign settings section.

= 5.2 =
* Default behavior of the form is now "sync" (add or update). If you had "Add Subscriber" chosen it will now perform "add or update." If you had "Sync Subscriber" chosen the behavior will remain the same.

= 5.5 =
* If site tracking is enabled it will embed some JavaScript code on each of your front-end pages.

= 5.6 =
* UPGRADE IMMEDIATELY - security bug patched (involving the API key being exposed). After upgrading go to ActiveCampaign settings and click "Update" again so it reloads the form code.

= 5.7 =
* Custom form "action" URL's will no longer work.

= 5.8 =
* UPGRADE IMMEDIATELY - security fix. After upgrading go to ActiveCampaign settings and click "Update" again so it reloads the form code.

= 5.9 =
* Site tracking users: the current logged-in user will now be associated with each page visit.