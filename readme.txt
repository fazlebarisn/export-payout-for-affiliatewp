=== Export Payout For AffiliateWp ===
Contributors: fazlebarisn
Tags: affiliatewp, export, payouts, excel, pdf
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple and lightweight add-on for AffiliateWP that allows affiliates to export their payouts in Excel, PDF, or CSV formats.

== Description ==

**Export Payout For AffiliateWp** adds a convenient "Export Payouts" button directly to the frontend Payouts tab within the AffiliateWP dashboard. 

When clicked, a clean and responsive popup modal allows affiliates to select their desired duration (This Month, Last Month, or a Custom Date Range) and export their payout history in their preferred file format:

* **Excel (.xlsx)**
* **PDF (.pdf)**
* **CSV (.csv)**

This plugin is incredibly lightweight and leverages modern client-side generation techniques, meaning it won't bog down your server or require heavy PHP libraries to generate the reports!

### Features
* Seamlessly integrates into the AffiliateWP frontend Affiliate Area.
* Export payouts to CSV, Excel, or PDF.
* Filter exports by duration (This Month, Last Month, Custom Date Range).
* Fast, client-side document generation.
* Secure AJAX requests utilizing WordPress nonces.
* Native WordPress database queries ensuring security against SQL injections.

== Installation ==

1. Upload the `export-payout-for-affiliatewp` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure you have the **AffiliateWP** plugin installed and active.
4. Navigate to the Affiliate Area on the frontend, click the "Payouts" tab, and you will see the new "Export Payouts" button.

== Frequently Asked Questions ==

= Does this plugin require AffiliateWP to be installed? =
Yes, this plugin is an add-on for AffiliateWP. It requires the AffiliateWP plugin to be installed and activated.

= Will generating large exports slow down my server? =
No! The plugin simply fetches the raw payout data as JSON and generates the Excel, PDF, or CSV files entirely within the user's browser, meaning your server bears zero document generation load.

== Screenshots ==

1. The Export Payouts button integrated into the AffiliateWP Payouts tab.
2. The export modal allowing selection of duration and file type.

== Changelog ==

= 1.0.0 =
* Initial release.
