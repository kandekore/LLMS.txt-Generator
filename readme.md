# Simple LLMS.txt Generator

Create and manage an `llms.txt` file from your WordPress admin to guide LLM/AI crawlers (similar to `robots.txt`).
This plugin lets you:

* Choose which **AI user-agents** to target (GPTBot, Google-Extended, CCBot)
* Set high-level **AI usage policies** (Training, Summarization, Indexing, Attribution)
* Add **disallow rules** for directories or paths
* **Generate Now** even if settings didn’t change
* **Download** the current/preview `llms.txt` for manual upload
* Get clear **admin notices** confirming success or failure

---

## Table of Contents

* [Requirements](#requirements)
* [Installation](#installation)
* [Quick Start](#quick-start)
* [Settings & Options](#settings--options)

  * [1) AI User-Agents](#1-ai-user-agents)
  * [2) AI Usage Policies](#2-ai-usage-policies)
  * [3) Directory Disallow Rules](#3-directory-disallow-rules)
* [Actions (Generate & Download)](#actions-generate--download)
* [Where the File is Saved](#where-the-file-is-saved)
* [How the File is Built](#how-the-file-is-built)
* [Permissions & Filesystem Notes](#permissions--filesystem-notes)
* [Troubleshooting](#troubleshooting)
* [FAQ](#faq)
* [Uninstall / Cleanup](#uninstall--cleanup)
* [Changelog](#changelog)
* [License](#license)

---

## Requirements

* WordPress 6.0+ (works on older versions in most cases)
* PHP 7.4+ (PHP 8.x supported)
* Ability for your web server (or WP\_Filesystem method) to write to the WordPress **document root** (where `wp-config.php` lives)

---

## Installation

### From a ZIP

1. Download the plugin ZIP (or create one from the plugin folder).
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Choose the ZIP, click **Install Now**, then **Activate**.

### Manual (FTP/SFTP)

1. Copy the plugin folder into `/wp-content/plugins/`.
2. In **Plugins**, activate **Simple LLMS.txt Generator**.

---

## Quick Start

1. Go to **Settings → LLMS.txt Generator**.
2. Under **1. Choose AI User-Agents**, tick the crawlers you want to target.
3. Under **2. Set AI Usage Policies**, select **Allow** or **Disallow** for each policy.
4. Under **3. Set Directory Disallow Rules**, tick the standard paths and/or add any custom paths (one per line, starting with `/`).
5. Click **Save Settings & Generate File**.

You’ll see a success notice with a link to view `/llms.txt`.
Need to force a rewrite? Use **Generate Now** in the **Actions** area.
Want to manually upload? Use **Download** to get a copy.

---

## Settings & Options

### 1) AI User-Agents

* **GPTBot (OpenAI)**
* **Google-Extended (Google AI)**
* **CCBot (Common Crawl)**

> Rules & policies apply to all selected agents in a single `User-agent:` block.

### 2) AI Usage Policies

Each policy offers **Allow** or **Disallow**:

* **Training** — Whether AI can use your content for model training.
* **Summarization** — Whether AI may summarize your content.
* **Indexing** — Whether AI indexes content (separate from web search engines).
* **Attribution** — Whether AI must attribute content use.

> These are **advisory signals** to AI crawlers. Compliance depends on each crawler’s implementation.

### 3) Directory Disallow Rules

* **Disallow /wp-admin/**
* **Disallow /wp-includes/**
* **Custom Disallow Rules** — One path per line, e.g.:

  ```
  /private/
  /members-only/
  /downloads/file.pdf
  ```

---

## Actions (Generate & Download)

On **Settings → LLMS.txt Generator**, you’ll see an **Actions** section:

* **Generate Now**
  Immediately (re)creates `/llms.txt` using current settings — even if nothing changed.
  Useful after fixing permissions or when you want to ensure the latest rules are written.

* **Download Current / Preview**

  * If `/llms.txt` exists and is readable: downloads the actual file.
  * If it doesn’t exist yet: downloads a **preview** generated from your current settings.
    Use this to manually upload the file via FTP/SFTP if your host blocks writing to the root.

After generation, you’ll see a **success notice** confirming the file was uploaded, with a link to view it and the (approximate) file size when available.

---

## Where the File is Saved

* Path: **`[WordPress root]/llms.txt`**
  Example: `/var/www/html/llms.txt`
* Public URL: `https://yourdomain.com/llms.txt`

> If visiting `/llms.txt` returns 404 or forbidden, see **Troubleshooting** below.

---

## How the File is Built

The plugin composes a single block like:

```
User-agent: GPTBot, Google-Extended, CCBot
Training: Disallow
Summarization: Allow
Indexing: Disallow
Attribution: Allow
Disallow: /wp-admin/
Disallow: /wp-includes/
Disallow: /private/
```

* Only **selected** user-agents are included.
* Only chosen **policies** are printed (with `Allow`/`Disallow`).
* **Disallow** lines include your standard ticks + any custom paths.
* The file always ends with a **trailing newline**.

---

## Permissions & Filesystem Notes

The plugin uses **WP\_Filesystem** when available (supports direct, FTP, FTPS, SSH) and falls back to `file_put_contents()` if needed.
To succeed:

* Your WordPress installation must allow writing to the **document root**.
* Some managed hosts require enabling **FS\_METHOD** or providing FTP credentials in `wp-config.php`.
* Security plugins or server rules may block writing to the root or serving `.txt` files — adjust rules if necessary.

---

## Troubleshooting

**I clicked Save but no file was created.**

* Use **Generate Now** to force a rewrite.
* Check file permissions on the WordPress root (web server user must be able to write).
* Ensure no security plugin prevents writing to the root.
* Review `wp-config.php` for FS settings; try providing FTP/SSH creds if your host requires them.

**I get a success notice, but `/llms.txt` is 404.**

* Some hosts or security layers block serving root `.txt` files.
* Check `.htaccess`, Nginx rules, or security plugin settings.
* Try opening in an incognito window or bypassing page caches/CDN.
* As a fallback, click **Download** and manually upload `llms.txt` to the WordPress root via FTP/SFTP.

**The content looks HTML-escaped (e.g., `&amp;`).**

* The plugin deliberately **does not** HTML-escape file content.
* If you copy/paste from the admin screen into an editor, ensure your editor isn’t re-encoding characters.

**Nothing changed, but I want to refresh the file.**

* Use **Generate Now** — it writes even when options are unchanged.

**Which user-agents are supported?**

* Currently: **GPTBot**, **Google-Extended**, **CCBot**.
* More can be added in future versions.

---

## FAQ

**Does this replace `robots.txt`?**
No. `llms.txt` targets LLM/AI crawlers specifically; `robots.txt` remains for search engine crawling directives.

**Will AI crawlers obey this file?**
There’s no global standard. Many providers are beginning to honor these signals, but behavior varies by crawler and may evolve.

**Can I add per-user-agent rules?**
This version writes a **single block** including all selected agents. If you need per-agent blocks, open a feature request.

**Multisite support?**
Network-activated plugins work, but `llms.txt` is written once per server document root (shared). Manage from the main site for clarity.

**Caching/CDN considerations?**
If your CDN caches text files, purge or bypass caching for `/llms.txt` after updates.

---

## Uninstall / Cleanup

* Deactivating the plugin **does not** delete `/llms.txt`.
* If you want to remove it, delete the file from your site root via FTP/SFTP.
* Deleting the plugin will remove its options from the database (standard WordPress behavior if implemented via `uninstall.php`—not included by default).

---

## Changelog

**1.2.0**

* Added **Generate Now** (force write without option changes)
* Added **Download** (actual or preview file)
* Clear success/failure admin notices with file link and size
* Safer file writes via **WP\_Filesystem** with fallback
* Sanitization improvements (no HTML escaping in file content)
* Path handling improvements

**1.1.0**

* Initial public release of the settings UI
* User-agent selection, policies, and disallow rules
* Auto-generation on option update

---

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

---

### Support & Contributions

* Found a bug or want a feature (e.g., per-agent blocks, more user-agents, import/export)?
* Open an issue or PR in your repository, or describe the request and I’ll draft the code changes for you.

## Author

[Darren Kandekore](https://github.com/dkandekore)  
Built with care to keep your WordPress content clean, SEO-friendly, and efficient.
