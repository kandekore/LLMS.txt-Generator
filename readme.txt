=== Simple LLMS.txt Generator ===
Contributors: Darren Kandekore
Tags: llms.txt, llms, ai, SEO, robots.txt
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create and manage an llms.txt file from your WordPress admin to guide LLM/AI crawlers. Set policies and disallow rules.

== Description ==

Create and manage an `llms.txt` file from your WordPress admin to guide LLM/AI crawlers (similar to `robots.txt`).

This plugin lets you:

* Choose which **AI user-agents** to target (GPTBot, Google-Extended, CCBot).
* Set high-level **AI usage policies** (Training, Summarization, Indexing, Attribution).
* Add **disallow rules** for directories or paths.
* **Generate Now** even if settings didn’t change.
* **Download** the current/preview `llms.txt` for manual upload.
* Get clear **admin notices** confirming success or failure.

== Installation ==

### From a ZIP

1.  Download the plugin ZIP (or create one from the plugin folder).
2.  In your WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3.  Choose the ZIP, click **Install Now**, then **Activate**.

### Manual (FTP/SFTP)

1.  Copy the plugin folder into `/wp-content/plugins/`.
2.  In **Plugins**, activate **Simple LLMS.txt Generator**.

== Frequently Asked Questions ==

= Does this replace `robots.txt`? =
No. `llms.txt` targets LLM/AI crawlers specifically; `robots.txt` remains for search engine crawling directives.

= Will AI crawlers obey this file? =
There’s no global standard. Many providers are beginning to honor these signals, but behavior varies by crawler and may evolve.

= Can I add per-user-agent rules? =
This version writes a **single block** including all selected agents. If you need per-agent blocks, open a feature request.

= Multisite support? =
Network-activated plugins work, but `llms.txt` is written once per server document root (shared). Manage from the main site for clarity.

= Caching/CDN considerations? =
If your CDN caches text files, purge or bypass caching for `/llms.txt` after updates.

== Changelog ==

= 1.2.0 =
* Added **Generate Now** (force write without option changes).
* Added **Download** (actual or preview file).
* Clear success/failure admin notices with file link and size.
* Safer file writes via **WP_Filesystem** with fallback.
* Sanitization improvements (no HTML escaping in file content).
* Path handling improvements.

= 1.1.0 =
* Initial public release of the settings UI.
* User-agent selection, policies, and disallow rules.
* Auto-generation on option update.

=== Author ===
[Darren Kandekore](https://darrenk.uk/)