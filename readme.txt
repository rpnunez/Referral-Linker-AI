=== Referral Link Manager ===
Contributors: yourname
Tags: affiliate, referral, links, ai, automation
Requires at least: 6.3
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered plugin for automatically inserting referral and affiliate links into your WordPress posts.

== Description ==

Referral Link Manager uses AI (via Meow Apps AI Engine) to intelligently insert your referral and affiliate links into existing blog posts. Configure "Link Makers" to target specific categories, tags, or authors, and let AI place links where they fit naturally in your content.

= Features =

* **Referral Links** - Create and manage your affiliate/referral links as a custom post type
* **Link Groups** - Organize links into groups (custom taxonomy) for easy management
* **Link Makers** - Configure rules for AI-powered link insertion
* **Approval Workflow** - Review AI changes before publishing
* **Scheduled Processing** - Automatic runs via WordPress Cron
* **Usage Tracking** - Monitor how often each link is used

= Requirements =

* WordPress 6.3 or higher
* PHP 7.4 or higher
* [Meow Apps AI Engine](https://wordpress.org/plugins/ai-engine/) plugin (free or pro)
* AI API key (OpenAI, Anthropic, etc.)

== Installation ==

1. Upload the `referral-link-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Install and configure Meow Apps AI Engine with your preferred AI provider
4. Go to Referral Links menu to start creating links and link makers

== Frequently Asked Questions ==

= Do I need an AI API key? =

Yes, this plugin uses Meow Apps AI Engine which requires an API key from OpenAI, Anthropic, or another supported AI provider.

= How does the approval process work? =

When a Link Maker processes posts, it creates "pending approval" entries. You can review the proposed changes and either approve (which updates the post) or reject them.

= Can I undo approved changes? =

The plugin stores the original content, so you can manually revert changes if needed. Consider using a revision plugin for additional safety.

== Changelog ==

= 1.0.0 =
* Initial release
