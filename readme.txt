=== Plugin Name ===
Contributors: kreitje
Donate link: http://hitmyserver.com/
Tags: mailchimp, newsletters
Requires at least: 3.0.1
Tested up to: 3.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically import your MailChimp campaigns into your blog posts. Select an author and categories for your posts.


== Description ==

The MailChimp Importer is a WordPress plugin that can automatically import your MailChimp newsletters as WordPress posts. 
By using the WP Cron functionality it will automatically go out at the interval you specify and download the text version 
of your newsletters for you. Through the settings you can set the author and categories that get assigned to the post.

If you don’t want to import the newsletters but show links to the archives we offer a shortcode to do that. By placing [ mailchimp ] 
anywhere in your post or page it will use the settings and grab your newsletters. You can also override the settings by adding attributes 
to your shortcode.
    

== Installation ==

1. Upload the `hms-mailchimp` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. In Settings -> MailChimp Importer enter your API key. Click Save Changes and from there select what list you want to import.
4. If you want to show a listing of your newsletters add [mailchimp] to any post or page.

== Upgrade Notice ==

None at this time.

== Frequently Asked Questions ==

= Does this work with free MailChimp accounts? =

Yes it does.

= Can I set how frequently it checks? =

Yes, there is a setting to set the frequency

== Screenshots ==

1. Settings screen

== Changelog ==

= 1.0 =
* Plugin is released
