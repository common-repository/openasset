=== OpenAsset ===
Requires at least: 6.0
Requires PHP: 8.0
Tested up to: 6.5
Stable tag: 1.1.2
Tags: DAM, Digital Asset Management, Images, Projects, Team
Contributors: OpenAsset
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sync your AEC Project Portfolio, Employees and Images from OpenAsset to your Wordpress Website.

== Description ==

**This plugin requires you to write code in order to integrate with your website.  It is therefore advised that you do not install directly on your live website.  Install onto a development environment first.  Ensure your integration is fully tested before you deploy live.**

**Anyone can download this plugin but in order to install and configure, it requires you to be an OpenAsset customer and have a specific OpenAsset license. If you are interested in the obtaining the license please reach out to your OpenAsset Customer Success Manager or [submit this form](https://pages.openasset.com/integrations-contact-us.html)**

OpenAsset is a leading provider of Digital Asset Management solutions designed to meet the unique needs of the Architecture, Engineering, and Construction (AEC) industries. Our vision is to supercharge productivity of AEC marketing and business pursuit teams so they can win more business.

The OpenAsset Plugin for WordPress enables AEC companies to sync project and employee profiles with relevant details, experience, and marketing-ready images directly from OpenAsset to their public-facing website.  This eliminates data redundancy, ensuring that high-quality assets are maintained centrally, streamlining workflows and boosting efficiency.

**Effortless Project Showcase:** Showcase your AEC projects seamlessly on your website with a few clicks. The plugin enables display of approved and consistent project details, enhancing your online presence.

**Employee Profiles that Stand Out:** Highlight your team's expertise by effortlessly publishing employee profiles directly from the DAM. Keep your team information up-to-date and impress your clients with the talent behind your projects.

**Marketing-Ready Images:** Present your projects with stunning visuals. The plugin enables you to select and publish marketing-ready images directly from your DAM, ensuring consistency and professionalism across your web presence.

**Data Consistency and Centralization:** Say goodbye to inconsistency. The plugin synchronizes with your OpenAsset instance, ensuring that the information on your website is up-to-date and reflective of your latest projects and team members.

**Presentation and web design in your control:**  The plugin offers a simple UI template that you are free to modify or your web developer is able to integrate the data into your fully custom website UI.

== Links ==

* [openasset.com](https://www.openasset.com)
* [Installing and Configuring the OpenAsset Wordpress Plugin](https://success.openasset.com/en/articles/8970283-installing-and-configuring-the-openasset-wordpress-plugin)
* [Using the templates bundled with the OpenAsset Wordpress Plugin](https://success.openasset.com/en/articles/8971102-using-the-templates-bundled-with-the-openasset-wordpress-plugin)
* [Creating a fully custom UI with the OpenAsset Wordpress Plugin](https://success.openasset.com/en/articles/8971297-creating-a-fully-custom-ui-with-the-openasset-wordpress-plugin)

== Installation ==

**This plugin requires you to write code in order to integrate with your website.  It is therefore advised that you do not install directly on your live website.  Install onto a development environment first.  Ensure your integration is fully tested before you deploy live.**

**Anyone can download this plugin but in order to install and configure, it requires you to be an OpenAsset customer and have a specific OpenAsset license. If you are interested in the obtaining the license please reach out to your OpenAsset Customer Success Manager or [submit this form](https://pages.openasset.com/integrations-contact-us.html)**

**Plugin Installation**

To install, from your WordPress dashboard:

1. Visit Plugins > Add New
2. Search “OpenAsset”
3. Install and Activate OpenAsset Wordpress Connector from your plugins page.
4. Create an OpenAsset API token.
5. Add your API credentials to the plugin installation page.

[Read more here](https://success.openasset.com/en/articles/8970283-installing-and-configuring-the-openasset-wordpress-plugin)

**Website installation**

Installing the plugin into your website will require work from your web developer.  The plugin makes it easy to configure the data and images that you would like to sync from OpenAsset. There are a couple of options available to you when displaying this on your website:

* The plugin is bundled with some simple templates. These can either be used as a quick proof of concept, or your web developer is free to modify them to suit the look and branding of your website.  [Read more here](https://success.openasset.com/en/articles/8971102-using-the-templates-bundled-with-the-openasset-wordpress-plugin)
* Alternatively the data made available by the plugin can be built into a fully custom website frontend. [Read more here](https://success.openasset.com/en/articles/8971297-creating-a-fully-custom-ui-with-the-openasset-wordpress-plugin)

== Support ==

**Support for this plugin is provided directly from OpenAsset's support team.**
If you have questions pertaining to downloading, installing, configuring and syncing the plugin, please reach out to: [support@openasset.com](mailto:support@openasset.com)

**Note:** We do not offer support for modifying or customizing your web pages including issues relating to the presentation of your information or images. Please contact your web developer for this.

== 3rd Party Services ==

The OpenAsset WordPress plugin makes use of OpenAsset's API to retrieve and display data from your OpenAsset instance. By using this plugin you agree to OpenAsset's terms of service and privacy policy.

* [OpenAsset Terms & Conditions](https://openasset.com/terms-ltd)
* [OpenAsset Privacy Policy](https://www.iubenda.com/privacy-policy/69272435)

Using this plugin means that you do not need to interact with OpenAsset's API in code but for reference it is [documented here](https://developers.openasset.com)

== Screenshots ==

1. Connect to OpenAsset and choose to sync Projects, Employees or both.
2. Project Portfolio templates to get started or build your own.
3. Team templates to get started or build your own.

== Changelog ==

= 1.1.2 =
Fix - Project keywords now syncing when changed in OpenAsset.
Fix - Projects & Employees can now sync without any metadata selected.
Fix - Projects & Employees can no be sorted by options other than name.

= 1.1.1 =
* Fix - Projects and Employees can be synced fully independently of each other.
* Enhancement - Creation and deletion of oa-project and oa-employee custom post types are now linked to "Sync Projects" and "Sync Employees".
* Enhancement - Various code enhancements.

= 1.1.0 =
* Fix - Custom post types created by the plugin are now called oa-project and oa-employee rather than project and employee.
* Fix - File Description field not selected on new install.
* Fix - Navigation to admin pages via the wp-admin sidebar goes to correct pages.
* Fix - Template the "Meet the Team" and "Keywords" headings will not show if there's no information beneath.
* Fix - Template Employee primary photo display when there are no projects associated.
* Fix - Template Employee primary photo do not display incorrect image when there isn't one.
* Enhancement - Images directly replaced in OpenAsset will now re-sync to Wordpress.
* Enhancement - "Save" and "Save & Sync" buttons moved to the top of the admin pages for easier use.
* Enhancement - Project and Employee name fields are now locked as these always must be synced so that you can identify them.
* Enhancement - The default max image options changed to Projects - 4, Employees - 0 (note the hero images / primary photos sync by default).
* Enhancement - The instance URL field is now not editable once installed.  (To connect to another instance of OpenAsset, you need to uninstall the plugin first).
* Enhancement - Templates now support OpenAsset Demo theme.
* Enhancement - Template project keyword navigation simplified.
* Enhancement - Various code enhancements.

= 1.0.5 =
* Fix - Minor edits to readme.txt

= 1.0.4 =
* Fix - Minor edits to readme.txt

= 1.0.3 =
*General Availability - June 3rd 2024*

* Enhancement - Various code enhancements

= 1.0.2 =
* Fix - Incorrect headshot display on template when no headshot is set.
* Fix - Issue of missing headshots when no projects are associated.
* Fix - Image "Show on Website" is off issue.
* Enhancement - Various code enhancements.

= 1.0.1 =
* New - Record sync counter during sync process.
* Fix - Missing Copyright Holder and Photographer syncing.
* Enhancement - Various code enhancements.

= 1.0.0 =
*Beta release - April 8th 2024*

== Upgrade Notice ==

= 1.0.3 =
This is the first publicly available version, enjoy!
