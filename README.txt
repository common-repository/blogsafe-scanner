=== BlogSafe Scanner ===
Contributors: blogsafe.org
Donate link: https://www.blogsafe.org
Tags: malware, scanner, checksum
Requires at least: 5.0.0
Tested up to: 5.8.2
Stable tag: 1.1.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

BlogSafe Scanner is a lightweight file scanner designed to notify you when any files are modified or uploaded to your server.

== Description ==

BlogSafe Scanner is a lightweight file scanner designed to notify you when any files are modified or uploaded to your server.

It's features include:

* Creates a checksum of each file on your web server and compares them to official WordPress checksums. *1
* Detects new and modified files on your web server.
* Optionally ignore files that are changed often.
* Optionally checks plugins and themes against the U.S. NIST National Vulnerability Database for known issues. *2
* Optionally hecks plugins and themes for last known updates and alerts you when they haven't been updated in over 12 months. *2
* Sends e-mail alert to the server admin when it's been deactivated.
* Works with WP Multi-site (Activate on parent site only).
* Sends e-mail alearts when new or modified files are detected (Plus version).
* Scheduling of automatic scans (Plus version).

Notes regarding 3rd party services:
1. BlogSafe Scanner directly contacts various WordPress API's for checksum verification. 
   During this contact the following information may be sent:
    a. Plugin name and version.
    b. Theme name and version.
    c. WordPress version.
    
    The WordPress Privacy Policy can be found here: [WordPress](https://wordpress.org/about/privacy/  "WordPress Privacy")

2. BlogSafe.org monitors and mirrors a portion of the NIST National Vulnerability Database for vulnerabilities related to WordPress, plugins and themes. When opting-in, BlogSafe Scanner will poll the BlogSafe.org API for these potential vulnerabilities. At no time will the plugin contact the NIST database directly.  BlogSafe.org also maintains a database of known WordPress themes and plugins. This database is generated directly from the WordPress repository and verified via the WordPress API.  When opting-in, BlogSafe scanner will poll the BlogSafe.org API for this data. At no time does BlogSafe Scanner directly contact the WordPress SVN.
     During this opt-in contact the following information may be sent:
     a. A list of plugins and themes found on your website along with their versions.	
    
    The BlogSafe.org privacy policy can be found here: [BlogSafe] (https://blogsafe.org/privacy-policy/ "BlogSafe Privacy")

== Installation ==

1. Unzip and upload the entire directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==
1. BlogSafe Scanner showing the results of a scan.
2. Settings available in BlogSafe Scanner Plus.
3. The ignore list.

== Changelog ==

= 1.1.5 =
  * Security fix to 3rd party code.

= 1.1.4 =
  * Patch for WP ob_end_flush() bug.

= 1.1.3 =
  * PHP notices suppressed.
  * Fixed multi-site detection.

= 1.1.2 =
  * Suppressed buffer notices when not used.

= 1.1.1 =
  * Complete update of menuing system in preparation of potential addons.
  * Reworked buffered output during scans to better provide real-time scanning updates.

= 1.1.0 =
 * Added language files for en
 * Updated scan routines to produce a report instead of static output.
 * Removing files from the ignore list now triggers a full scan requirement.

= 1.0.3 =
* Initial public release.

== Upgrade Notice ==
