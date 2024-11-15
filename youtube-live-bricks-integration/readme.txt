=== YouTube Live Bricks Integration ===
Contributors: markszymanski
Tags: youtube, live streaming, bricks builder
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrates YouTube Live streaming status with Bricks Builder for dynamic content based on channel live status.

== Description ==

YouTube Live Bricks Integration allows you to create dynamic content in Bricks Builder based on your YouTube channel's live streaming status. Show or hide elements based on whether your channel is currently live, and automatically display live stream URLs using dynamic tags.

Features:

* Custom Bricks condition for YouTube live status
* Dynamic tags for live stream URL and video ID
* Automatic status checking (5, 10, or 30-minute intervals)
* Manual status check button
* Secure API key storage
* Easy-to-use admin interface
* Error logging and notifications

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/youtube-live-bricks-integration`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the YouTube Live menu item to configure the plugin
4. Enter your YouTube Channel ID and API Key
5. Select your preferred check frequency
6. Start using the new conditions and dynamic tags in Bricks Builder

== Frequently Asked Questions ==

= How do I get a YouTube API Key? =

1. Go to the Google Cloud Console
2. Create a new project or select an existing one
3. Enable the YouTube Data API v3
4. Create credentials (API Key)
5. Copy the API key into the plugin settings

= How do I find my YouTube Channel ID? =

1. Go to your YouTube channel
2. Click on your profile picture and select "YouTube Studio"
3. Click on "Settings" in the left menu
4. Click on "Channel" in the left menu
5. Your channel ID will be listed under "Basic info"

== Screenshots ==

1. Admin settings page
2. Bricks condition interface
3. Dynamic tags in action

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of the plugin.
