=== Post Affiliate Pro ===
Contributors: jurajsim
Tags: affiliate marketing, pap, post affiliate pro, qualityunit, affiliate
Requires at least: 3.0.0
Tested up to: 4.6
Stable tag: 1.8.2

This plugin integrates Post Affiliate Pro software into any WordPress installation. Post Affiliate Pro is the leading affiliate tracking tool with more than 27,000 active customers worldwide.

== Description ==

This plugin integrates Post Affiliate Pro - affiliate software into any Wordpress installation.
Post Affiliate Pro is an award winning affiliate software with complete set of affiliate marketing features.
You can rely on bullet-proof click/sale tracking technology, which combines multiple tracking methods into one powerful tracking system.

= Promotional video =
[vimeo http://vimeo.com/26098217]
You can find more info about PostAffiliatePro [here](http://www.postaffiliatepro.com/#wordpress "Affiliate software"). 

Supported features:

*	Integrates wordpress user signup with Post Affiliate Pro signup
*   Integrates Post Affiliate Pro click tracking into Wordpress
*   Includes Top affiliates widget with basic affiliate statistics
*   Shortcodes for affiliates
*   Integration with Contact form 7 (http://contactform7.com/) till version 3 (not included)
*   Integration with JotForm
*   Integration with Marketpress
*   Integration with MemberPress
*   Integration with Simple Pay Pro
*   Integration with WishList Member
*   Integration with WooCommerce
*   Integration with WooCommerce Subscriptions
*   Also works with S2Member

== Installation ==

1. Create directory postaffiliatepro in '/wp-content/plugins/'
2. Unzip `postaffiliatepro.zip` to the `/wp-content/plugins/postaffiliatepro` directory
3. Activate Post Affiliate Pro plugin in your admin panel
4. Set user credentials in plugin settings

Note: If the plugin does not show up, login to you Post Affiliate Pro merchant panel and navigate to Main menu> Tools> Integration> API Integration
From this window download your API file by clicking the 'Download PAP API' link
Upload PapApi.class.php file to your plugin directory /wp-content/plugins/postaffiliatepro
Refresh your WP admin panel

== Frequently Asked Questions ==

= Q: After update, all menus are gone and plugin is not working at all =
A: In situation like this you should check your PapApi.class.php file in your PostAffiliatePro plugin directory.
In most of the cases, this file was missing after update and that caused plugin malfunction.
Without PapApi.class.php file plugin can not operate correctly and because of thet it disable its self to prevent
damaging the main pages with error or warning messages etc.

= What is Post Affiliate Pro? =

Post Affiliate Pro is an award-winning affiliate tracking software designed to empower or establish in-house affiliate program.
For more info check out [this page](href='https://www.postaffiliatepro.com/#wordpress "Affiliate software")

= Can Post Affilate Pro user use same passowrd as in Wordpress? =

No. This is not possible at the moment. Passwords will be always different.

= How can I use affiliate shortcode? =

Here are few examples of usage:

[affiliate item="name"/] - prints name of currently loaded affiliate.

[affiliate item="loginurl"/] - prints link "Affiliate panel" that affiliate can use to login to his panel
 
[affiliate item="loginurl" caption="Log me in!"/] - prints link "Log me in!" that affiliate can use to login to his panel

[affiliate item="loginurl_raw"/] - prints raw url link: http://www.yoursite.com/affiliate/affiliates/panel.php?S=sessionid

[affiliate item="OTHER_ATTRIBUTES"/] - prints other affiliate attributes.  OTHER_ATTRIBUTES can be one of these items:

* userid - ID of user
* refid - user referral ID
* rstatus - user status
* minimumpayout - amount of the minimum payout for user
* payoutoptionid - ID of the payout option used by user
* note - user note
* photo - URL of user image
* username - username
* rpassword - user passwrod
* firstname - user first name
* lastname - user last name
* parentuserid - ID od parent user
* ip - user signup IP address
* notificationemail - user notification email
* data1 to data25 - user data fields

example of getting user notification email:

[affiliate item="notificationemail"]

You can get the same values of affiliate parent, instead of 'affiliate' shortcode call 'parent' and then the needed item. E.g. for parent name call this:
[parent item="name"/]

= Is it possible to integrate this plugin with s2Member? =
Yes it is. But keep in mind you should not use any mandatory fields in Post Affiliate Pro signup.
You have to use optional fields only.

= Is it possible to integrate this plugin with MagicMembers? =
Yes it is. But this feature is just experimental at this time.

= How to use the TopAffiliates widget? =
If you want to publicly display affiliate statistics in your WordPress, simply navigate to Appearance> Widgets section and add the Top Affiliates widget. When added, you can configure how many affiliates should be included in the result, set which value to use for ordering and you can also define the template of the result.
You can use these variables:
{$firstname}
{$lastname}
{$userid}
{$parentuserid}
{$clicksAll}
{$salesCount}
{$commissions}

== Screenshots ==

1. Plugin adds an extra menu to your WP installation
2. General options screen
3. Signup options screen
4. Click tracking options screen
5. Top affiliates widget config
6. You can also use shortcodes

== Changelog ==
= 1.8.2 =
* WishList Member tracking issue fixed

= 1.8.1 =
* function declaration warning fixed

= 1.8.0 =
* integration with Simple Pay Pro added
* WooCommerce product ID bug fixed
* refunding fixed
* external form library removed
* code revision to support PHP7
* minor bugfixes

= 1.7.0 =
* integration with MemberPress added
* integration with WishList Member added
* Options for product ID tracking in WooCommerce integration added

= 1.6.2 =
* added option to create an affiliate with a photo
* optimised WooCommerce refunds
* slightly redesigned

= 1.6.1 =
* IP address tracking feature added for WooCommerce PayPal orders

= 1.6.0 =
* integration with Marketpress added
* added a link to plugin to general setting
* menu changes
* wording corrections

= 1.5.7 =
* minor changes of code and wording

= 1.5.6 =
* WooCommerce with PayPal tracking fix

= 1.5.5 =
* WooCommerce sale tracking fix

= 1.5.4 =
* Campaign ID option added for WooCommerce click tracking
* Campaign ID option added for WooCommerce sale tracking
* WooCommerce Subscriptions recurrence tracking fix
* Missing account ID added to tracking codes
* Account ID setting moved to from click tracking section to general

= 1.5.3 =
* WooCommerce Subscriptions support added

= 1.5.2 =
* WooCommerce automatic protocol recognition added

= 1.5.1 =
* WooCommerce product tracking improved

= 1.5.0 =
* WooCommerce automatic sale tracking integration
* automatic parent affiliate recognition for signup tuned up

= 1.4.1 =
* set username of newly signed up WP user as Referral ID for his new affiliate account, unsupported characters are replaced with underscore

= 1.4.0 =
* Contact7 integration updated to the latest version
* JotForm configuration moved to a sub-page
* shortcodes for parent affiliates added

= 1.3.3 =
* a JotForm fields total cost bugfix

= 1.3.2 =
* TotalCost field added for JotForm
* a bugfix for dynamic JotForm fields

= 1.3.1 =
* TopAffiliates widget bug with hosted accounts fixed
* minor changes

= 1.3.0 =
* JotForm support with custom fields
* minor changes

= 1.2.33 =
* a bug fix for cases when buffering on server is disabled
* a plugin icon has been added
* tested WP compatibility up to 4.4.1

= 1.2.32 =
* add load of parentusreid in Top Affiliates, at least 5.3.28 version of Pap is needed

= 1.2.31 =
* fixed some PHP notifications

= 1.2.27 =
* fixed bug with duplicate mail if a setting is turn on in PAP and also in WP plugin
* fixed bug with Contact Form 7 when custom db prefix is used

= 1.2.26 =
* added item 'loginurl_raw' to affiliate shortcode for displaying url link

= 1.2.25 =
* fixed affiliate loading problem

= 1.2.24 =
* fixed some bugs dururing affiliate signup

= 1.2.22 =
* minor fixes and code refactoring

= 1.2.21 =
* fixed invisible shortcodes when affiliates do not use email names in PAP

= 1.2.20 =
* fixed bug with contact form 7: Call to a member function get_results() on a non-object in /home/bandi/public_html/sikeresemenyek.hu/wp-content/plugins/postaffiliatepro/Util/ContactForm7Helper.class.php on line 50

= 1.2.19 =
* fixed bug with shortcodes disappearing.

= 1.2.18 =
* tested compatibility with WP 3.5.1

= 1.2.17 =
* minor fixes

= 1.2.16 =
* fixed Contact form 7 form count handling 

= 1.2.15 =
* shortcodes descriptions fixing

= 1.2.14 =
* descriptions fixing

= 1.2.13 =
* shortcodes problmes fix

= 1.2.12 =
* just fixes typos in some texts
* minor code changes 

= 1.2.11 =
* change some texts

= 1.2.10 =
* just typos in some texts

= 1.2.9 =
* just typos in some texts

= 1.2.8 =
* bugfixes

= 1.2.7 =
* experimental support for Magic members Wordpress plugin

= 1.2.6 =
* readme.txt changed - small changes

= 1.2.5 =
* tested on WP 3.2.1

= 1.2.4 =
* screenshots update

= 1.2.3 =
* fixed some minor bugs
* just got report, that plugin works well with S2 member WordPress plugin

= 1.2.2 =
* add support for Contact form 7 integration

= 1.2.1 =
* small bugfixes 
* added chache for affialite login links urls

= 1.2.0 =
* add "affiliate" shortcode

= 1.1.5 =
* fixed critical error with broken shortcodes
* wp_content hook is not used anymore, plugin use wp_head instead

= 1.1.4 =
* fixed critical error with disappearing content

= 1.1.3 =
* fixed crash on plugin load: Warning: SimpleXMLElement::__construct() [simplexmlelement.--construct]: Entity: line 39: parser error : Opening and ending tag mismatch: ...

= 1.1.2 =
* minor bugfixes

= 1.1.1 =
* added possibility to insert newly created affiliate to private campaigns
* added support for click tracking integration
* added Top affiliates widget where you can see your top affiliates names, commissions, total costs etc. 
* signup and/or click tracking can now be enabled/disabled
* many internal chnages, code completly rewritten
* some minor bugs fixed

= 1.0.8 =
* corrected some spelling
* fixed non-functional signup dialog
* add option to send emails from pap when new affiliate signs-up

= 1.0.7 =
* bigfixes

= 1.0.6 =
* chnage menu possition from top to bottom

= 1.0.5 =
* added some more accurate descriptions to signup options form

= 1.0.4 =
* minor bugfixes

= 1.0.3 =
* Added suuport for default status for signing affiliates

= 1.0.2 =
* Fixed bug on signup option page when API file was not on place or out of date

= 1.0.1 =
* Add support to attach some concrete affiliate as parent for every new signed up user from wordpress.

== Upgrade Notice ==

* from 1.0.X to 1.1.X - you need to change path to your Post Afiliate Pro in general settings from http://www.yoursite.com/affiliate/scripts to http://www.yoursite.com/affiliate/ (remove directory 'script' at the end of url)
* other than that, there are no special requirements, just overwrite plugin files. All should work.

== Arbitrary section ==

If you have any thoughts how to make this plugin better, do not hasitate to leave your ideas in plugin forum, or write an email to support@qualityunit.com.
