=== Multiple Domain Mapping on Single Site ===
Contributors:  matthias.wagner
Donate link: https://www.matthias-wagner.at
Tags: multidomain, landingpage, redirect, domainmapping, mapping, multiple, domain, single, site, seo, marketing, mirror
Requires at least: 4.5
Tested up to: 6.8
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Show content of specific posts, pages, ... within their own, additional domains. Useful for SEO: different domains for landingpages.

== Description ==

= What does this plugin do? =
This lightweight plugin maps domains to specific URIs in your blog or website. It allows you to add as many mappings, e.g. for landingpages, as you want. 
Just let the domains point to your WordPress installation (see the installation tab for details) and decide which URI the plugin should map them to.

= What is this plugin for? =
The main purpose of the plugin is to have specific domains show the content of specific pages from a bigger website. This is especially needed for marketing and SEO purposes (landingpages). Think of a site-structure like

* www.mainsite.com
* www.mainsite.com/productA
* www.mainsite.com/productB

With the plugin you can use an additional domain like www.productA.com to point to your site's www.mainsite.com/productA.
It is **not a redirection**, instead the additional domain will display the content from the specified page (the additional domain will be visible in the browsers address bar). 

= What is this plugin not for? =
It is not our goal to mirror complete websites to additional domains. You can try to do this, but keep the aspects of duplicate content in mind. In the help section of the installed plugin we provide links to other plugins who may be better at this task.

= Which Pages, Post Types, ... does the plugin support? =
The plugin lets you define URIs to map to, so it works for pages, posts, custom post types, archives and so on. The plugin also changes hyperlink destination of the links inside your website. For example: navigation, pagination, archive links and so on. (Note: This only works if your theme and other link-generating plugins use standard WordPress functions like get_permalink).

So you will not have to select posts to map to your domains, but instead enter URIs. See the screenshots for examples.

= Is it hard to set up? =
The plugin requires additional steps in setting up your domains and hosting environment (see the installation-tab for details). If you are not familiar with these settings, it can happen that you have troubles with reaching your website. Therefore you should only set up the plugin in a testing environment if you are not sure if you can deal with these external settings.

We give our best to support you, but if you have troubles with correct DNS records and hosting environment settings, you should talk to your hosting provider or your web developer first.

= Troubles? =
Please see installation-tab, FAQ and the already answered support threads for more information or if you have troubles setting up the plugin.

= PREMIUM =
We have partnered with the plugin "Domain Mapping System" to be able to provide plugins for different use cases. Since they offer a paid version, they will also be able to provide you professional support. You want to benefit from special deals and coupons for the other plugin? [Find more information here.](https://www.VONTMNTmedia.at/multiple--on-single-site-premium/)

== Installation ==

= 1. External Setup =
Make sure that the domains you want to use already point to your WordPress installation root. This requires two steps:

1. The A-Records of the used domains must have the same IP address assigned as your main domain. This is usually done in the DNS-Settings of your domain registrar.
2. In your hosting environment, you must configure all domains to point into the same directory as your main domain (usually your WordPress root directory). This is usually called virtual hosting, multidomain, domain mapping, domain alias, ...
3. Test
3.1 Place a test.txt file with content "test" in your root folder. You should be able to see that file in your browser when calling "yourmaindomain.com/test.txt".
3.2 Clear your browser cache
3.3 You should also be able to see that file in your browser when calling "youraddondomain.com/test.txt" without any redirections happening.
4. Only proceed, if that test works. Otherwise you still have to work on your server/hosting configuration (!)

= 2. Plugin Installation =
1. Upload plugin-folder to your "/wp-content/plugins/" directory.
2. Activate the plugin through the Plugins-menu in WordPress.

= 3. Plugin Setup =
If you are working on a nginx-Server, you should change the plugin-setting "PHP Server-Variable" to "HTTP_HOST" radio button.

After you have the "External Setup" complete, all your domains will redirect to your WordPress home page in the main url.

* If not, do NOT start to enter mappings in the plugin options - it will make troubleshooting worse.,
* Disable/Clear your browser cache and also website caching plugins while setting everything up
* Make sure to only use compatible server-side caching plugin and with the correct settings (see [FAQ](https://de.wordpress.org/plugins/multiple--on-single-site/#faq-header)), since some of those plugins do not work with our domain mapping plugin.
* Now begin to enter your domains and the URIs they should match in the settings, located in the Tools-Menu. See screenshots for examples.

== Frequently Asked Questions ==

= Does it work with caching plugins? =
So far we only know WP Fastest Cache to work out of the box. Also W3 Total Cache is able to work with our plugin, but it requires some settings:
* In "General Settings" from W3TC, you can enable the page cache
* In "Page Cache" W3TC, you need no enable the "Cache alias hostnames" checkbox and leave the "Additional home URLs" field empty!

For W3 Total Cache, keep in mind that CSS/JS combine and minify will only work for pages of your main domain but not for mapped pages. We recommend WP Fastest Cache ;)

= Does it work with pagebuilders? =
Yes. We use Elementor mostly and it works also on mapped pages or posts. If you use another page builder and have troubles, try to activate the enhanced compatibility mode in the settings page (see screenshot #2).

= What about duplicate content? =
You should always aim to have only one URI connected to one specific (landing-)page or post. If you use our plugin, the mapped content will be reached with the additional domain and the original URI as well. Therefore you should set up a 301-redirection from the original URI to the new domain or use the canonical meta-Tag. If you use Yoast SEO, it will generate the correct canonical-Tag for you right out of the box. For the redirection, there are many famous plugins out there...

= Does it support german "Umlaute" like ä/ö/ü inside domains? =

Yes. But you have to define these domains in the IDN format. For example the domain *www.küche.at* would need to be put in as *www.xn--kche-0ra.at*. You can use the [Verisign IDN Conversion Tool](https://www.verisign.com/en_US/channel-resources/domain-registry-products/idn/idn-conversion-tool/index.xhtml) to find out these representation for your domains.

= Does it support https - connections? =

Yes. But do not try to have a certificate only for an additional domain but not for the main domain or vice versa: Browsers will detect mixed content or non-existing certificates and display security warnings.

So you must have all or none of your domains SSL-secured. Mapped pages will be linked with the same protocol as the main domain.

= Is it compatible with Yoast SEO? =
Yes.

= Does it work with the WordPress-Sitemap or Yoast SEO Sitemap? =
Yes. Instead of those original URLs, the sitemap shows the mapped domains.

Sometimes it is necessary to disable and re-enable XML-Sitemap functionality in the Yoast SEO settings as well as flushing your permalinks in order to reflect the mappings.

BUT there is one disadvantage so far: We do not provide a new sitemap for each mapping. This would be better for SEO, so you can handle a new domain in a different Property in Google Search Console. We will try to find a solution for that problem.

= Is it compatible with WooCommerce? =
Unfortunately not - some parts work, some not. It is difficult to find out and by now we do not see a chance for us to take the time needed for good support and compatibility. WooCommerce seems to use a lot of link-generating functions that are not in WordPress core. This seems to be necessary for different product types and so on. On top, also the XML sitemaps from Yoast SEO display some links mapped and some not.

If you want to use custom domains for parts of your WooCommerce store, this plugin is probably not (yet) the right decision.

= Is it compatible with WPML/PolyLang/...? =

Generally we do not offer compatibility with these plugins yet, since they have their own functionality to use additional domains for specific languages. This is something we are going to test out in more detail for future versions.

If you have set up your multilanguage-plugin (like WPML or Polylang) to automatically forward users to the page that represents the browser-language, it is very likely that this will not work with the domain mapping plugin.

= Is it compatible with a Wordpress Multisite installation? =

So far this plugin is only developed to map domains on a single-site installation. Multisite has the ability to use different domains per website already built in. If you are not sure if you should use a single- or multisite installation, please refer to the WordPress Codex:
*If you plan on creating sites that are strongly interconnected, that share data, or share users, then a multisite network might NOT be the best solution.* from [WordPress Codex](https://codex.wordpress.org/Before_You_Create_A_Network)

= Why do my custom fonts or icons not show up? =
Although the plugin changes the include paths of scripts and styles to the mapped domains, it can happen that paths inside css-files still refer to your main domain. If your webserver is configured to disable scripts, fonts, ... from other domains, these resources will be blocked.

Here you can see how to setup CORS to enable cross-domain ressources: [CORS-Setup](http://enable-cors.org/server_apache.html)

= How can i track cross domains with only one Google Analytics property? =
This is pretty complex and we can not support you here. This article will help you: [Cross-Domain Tracking with Google Analytics](https://developers.google.com/analytics/devguides/collection/analyticsjs/linker).

= Why am i logged out when viewing pages/posts/... with mapped domains? =
If you are on the frontend of your site and logged in, it will happen that you do not see the admin bar and seem to be logged out when viewing URIs with mapped domains. This is because WordPress uses cookies to save the login-state. These cookies only exist on your main domain and so they are not able to recognize you when viewing URIs with mapped domains.

= Does it work on a nginx server? =
Yes. Please stick to [this support thread regarding nginx-setup](https://wordpress.org/support/topic/support-with-wordpress-running-on-nginx-server/) and be sure to change the "PHP Server-Variable" setting to the recommended option for nginx. Unfortunately we cannot provide support for nginx, since we are working with apache by default.

= I am a developer, how can i build solutions on top of the plugin? =
Great. We have built in some actions and filters so you can build your own solutions on top of our plugin. So far, we do not have a documentation online. Just search for "VONTMNT_mdma" to find our action hooks or "VONTMNT_mdmf" to find our filter hooks. We have tried to place good comments, so you will find out what you can achieve with them :)

= Is the plugin GDPR / DSGVO compliant? =
Yes. We do not store any user data - neither from you or your visitors. Only the settings are saved in your local database.


== Screenshots ==

1. The main settings page configured with two additional domains for landingpages. No need to add multiple lines with www/non-www and http/https since version 1.0
2. Additional settings page. Compatibility mode is useful for some page builders if they do not work out of the box.

== Changelog ==

= 1.1.1 =
* Bugfix: Missing file from 1.1 release

= 1.1 =
* New feature: Custom HTML-Head-Code per mapped domain
* New feature: Warning when amount of mappings reaches servers limits (max_input_vars)
* Compatibility check with WordPress 6
* Included some hints to our partnership with Domain Mapping System

= 1.0.4 =
* Bugfix causing problems with hyperlinks being altered which should not be altered

= 1.0.3 =
* Bugfix causing problems with sources from images or other included files when mapping subdomains

= 1.0.2 =
* Compatibility Fix for older PHP Versions

= 1.0.1 =
* Bugfix: Missing files in SVN

= 1.0 =
* Improved settings page: No need for up to 4 entries for only one domain from now on. http/https and www/non-www versions are automatically detected in the background. We will also combine these multiple settings in the upgrade process for you.
* Due to some bad data structure in the older versions, it can happen that lines are dropped by mistake - please check twice after updating!
* Complete refactored code for better performance, stability and improved user experience.
* http / https is all-or-nothing now. Since there have always been troubles with mixed websites, you will need to have all or none of your domains SSL-secured now.
* Added actions and filters for developers (see FAQ).
* Database entries are removed on plugin deletion now.
* Built the base for a premium-version, where we plan to offer more functionality on top of this plugin.

= 0.2.2 =
* Use new filter from elementor page builder so mapped sites can be edited with elementor again

= 0.2.1 =
* Fixed a problem with elementor support causing troubles

= 0.2 =
* Added support for Elementor Page Builder, so that Elementor loads up on mapped posts/pages

= 0.1.3.1 =
* removed feature: "Change various include paths (scripts, styles, images) to mapped domain" as it is more difficult then expected

= 0.1.3 =
* Change various include paths (scripts, styles, images) to mapped domain
* Removed some PHP Notices
* Added some FAQ

= 0.1.2 =
* Additional post type and archive link support
* Better support for Yoast SEO
* More information on installation and setup, FAQ, screenshots
* More information for setting up mappings on the backend
* Added compatibility settings for rare server incompatibilities
* Bugfix when having mapping-URIs which had the same characters in the beginning

= 0.1.1 =
* Removed a function that was used for testing automatic IDN conversion, but was incompatible with php 5.3

= 0.1 =
* Cleaned up code
* Cleaned up admin page
* Removed +/- Buttons to avoid the use of JavaScript
* Added text domain to strings
* Added support for german *Umlaute* as described in the FAQ
* Added Banner and Icon
* Moved the settings page into the Tools-section

= 0.0.1 =
* Initial release