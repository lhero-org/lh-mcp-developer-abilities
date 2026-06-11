# LH MCP Developer Abilities

**Contributors:** shawfactor
**Tags:** mcp, developer, diagnostics, multisite
**Requires at least:** 6.9
**Tested up to:** 7.0
**Stable tag:** 1.7.7
**License:** GPL-2.0+
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

MCP abilities for developer diagnostics: plugin/theme files, DB inspection, hooks, options, queries, and more.

Registers MCP abilities for developer and diagnostic workflows on the LocalHero platform. Provides plugin/theme file reading, database inspection, hook exploration, option reading, select queries, and runtime diagnostics — all accessible via the WordPress Abilities API and surfaced through the MCP adapter.

## Installation

1. Upload the `lh-mcp-developer-abilities` folder to `/wp-content/plugins/`.
2. Network-activate the plugin from the Network Admin → Plugins screen.
3. Abilities will be discoverable via `mcp-adapter-discover-abilities` once active.

## Abilities

All abilities require the `install_plugins` capability unless noted. All have `mcp.public = true` set so they appear via `discover-abilities`.

| # | Ability slug | Capability | Description |
|---|---|---|---|
| 1 | `lh-mcp-developer-abilities/get-active-network-plugins` | `install_plugins` | Lists all network-activated plugins with name, version, author. |
| 2 | `lh-mcp-developer-abilities/get-active-local-plugins` | `install_plugins` | Lists site-activated plugins for a given site_id (defaults to main site). |
| 3 | `lh-mcp-developer-abilities/get-must-use-plugins` | `install_plugins` | Lists all must-use (mu-plugins) plugins with name, version, author. |
| 4 | `lh-mcp-developer-abilities/list-sites` | `install_plugins` | Lists sites on the network with blog_id, domain, path, name, and status fields. Supports search, filtering, pagination, and ordering. |
| 5 | `lh-mcp-developer-abilities/get-autoloaded-options` | `install_plugins` | Returns autoloaded options sorted by size. Useful for identifying options bloat. |
| 6 | `lh-mcp-developer-abilities/get-cron-jobs` | `install_plugins` | Returns all scheduled WP-Cron events with next run time and overdue status. |
| 7 | `lh-mcp-developer-abilities/get-db-table-info` | `install_plugins` | Returns size, row count, engine, and indexes for all (or one) WordPress tables. |
| 8 | `lh-mcp-developer-abilities/get-transients` | `install_plugins` | Returns WordPress transients with expiry times and sizes. Flags expired ones. |
| 9 | `lh-mcp-developer-abilities/read-debug-log` | `install_plugins` | Reads the WordPress debug log. Supports tailing N lines and keyword filtering. |
| 10 | `lh-mcp-developer-abilities/get-environment-info` | `install_plugins` | Returns PHP version, DB server info, WP version, and environment type. |
| 11 | `lh-mcp-developer-abilities/get-installed-themes` | `install_plugins` | Returns all installed themes with slug, name, version, author. |
| 12 | `lh-mcp-developer-abilities/get-site-config` | `install_plugins` | Returns full site config including admin email and WP version. |
| 13 | `lh-mcp-developer-abilities/get-user-info` | `install_plugins` | Returns current authenticated user: ID, login, roles, locale. Accepts site_id to resolve site-specific roles and locale. |
| 14 | `lh-mcp-developer-abilities/list-plugin-files` | `install_plugins` | Lists all readable files in a plugin directory (relative paths, sizes, modified times). |
| 15 | `lh-mcp-developer-abilities/read-plugin-file` | `install_plugins` | Returns the content of a single plugin file. Max 100 KB; larger files are truncated. |
| 16 | `lh-mcp-developer-abilities/list-theme-files` | `install_plugins` | Lists all readable files in a theme directory (relative paths, sizes, modified times). |
| 17 | `lh-mcp-developer-abilities/read-theme-file` | `install_plugins` | Returns the content of a single theme file. Max 100 KB; larger files are truncated. |
| 18 | `lh-mcp-developer-abilities/get-table-schema` | `install_plugins` | Returns full column definitions (DESCRIBE) for a named database table. |
| 19 | `lh-mcp-developer-abilities/run-select-query` | `install_plugins` | Queries a table by name. Columns, WHERE, ORDER BY, GROUP BY are optional parameters. SELECT...FROM built server-side. Max 500 rows. |
| 20 | `lh-mcp-developer-abilities/get-option` | `install_plugins` | Returns a single WordPress option value. Supports network options. Blocks sensitive names. |
| 21 | `lh-mcp-developer-abilities/list-hooks` | `install_plugins` | Returns all currently registered hook names, optionally filtered by substring. |
| 22 | `lh-mcp-developer-abilities/get-hook-registrations` | `install_plugins` | Returns all callbacks registered on a specific hook, grouped by priority. |
| 23 | `lh-mcp-developer-abilities/get-rewrite-rules` | `install_plugins` | Returns rewrite rules for a given site_id (defaults to main site), optionally filtered. |
| 24 | `lh-mcp-developer-abilities/get-php-ini` | `install_plugins` | Returns PHP ini settings. Optionally scoped to an extension or filtered by substring. |
| 25 | `lh-mcp-developer-abilities/get-network-options` | `install_plugins` | Lists network-level sitemeta options with sizes. Use get-option with network:true to read values. |
| 26 | `lh-mcp-developer-abilities/get-object-cache-info` | `install_plugins` | Returns object cache backend, connection status, hit/miss stats, and memory usage. |
| 27 | `lh-mcp-developer-abilities/get-action-scheduler-status` | `install_plugins` | Returns Action Scheduler job counts by status and hook for a given site_id. |
| 28 | `lh-mcp-developer-abilities/get-site-health` | `install_plugins` | Runs WordPress Site Health direct tests and returns results with status and description. |
| 29 | `lh-mcp-developer-abilities/run-plugin-check` | `install_plugins` | Runs Plugin Check (PCP) static checks against a plugin. Returns errors and warnings. Returns available:false cleanly if Plugin Check is not active. |

### Ability notes

**`list-plugin-files` / `read-plugin-file`** — Use the list→get pattern: call `list-plugin-files` first to discover paths, then `read-plugin-file` for individual file content. Allowed extensions: `php, js, json, md, txt, css, html, xml, yaml, yml, svg`. Plugin slug must match the folder name exactly.

**`list-theme-files` / `read-theme-file`** — Same pattern as plugin file abilities. Theme slug is the stylesheet directory name (e.g. `twentytwentyfour`).

**`get-table-schema`** — Accepts the full table name including prefix (e.g. `wp_posts`). Rejects tables outside the WordPress base prefix. On error (missing table_name, invalid characters, table not found, or wrong prefix), returns `table_name`, an empty `columns` array, and an `error` field with a message that echoes the submitted name and, where relevant, this install's actual base table prefix and a suggested corrected name.

**`run-select-query`** — Accepts `table` (required, full name including prefix), `columns` (optional, comma-separated, defaults to `*`), `where` (optional, without the WHERE keyword), `order_by` (optional), `group_by` (optional), `limit` (max 500), and `offset`. The `SELECT ... FROM` query is built server-side. The `query` field in the response shows the exact SQL executed. Table must start with the WordPress base prefix and must exist. On any error (missing table, invalid characters, wrong prefix, table not found, disallowed WHERE clause, or query failure), returns `row_count: 0`, `offset`, `limit`, an empty `rows` array, and an `error` field with a message that echoes the submitted table name and, where relevant, this install's actual base table prefix and a suggested corrected name.

**`get-option`** — Blocked option name patterns: `auth_key`, `secure_auth_key`, `logged_in_key`, `nonce_key`, `auth_salt`, `secure_auth_salt`, `logged_in_salt`, `nonce_salt`, `db_password`, `db_user`, `db_name`, `db_host`, `admin_password`, `user_pass`. Pass `network: true` for `get_site_option()` lookups.

**`list-hooks`** — WordPress does not distinguish actions from filters at runtime; both are stored in `$wp_filter`. The `type` parameter is accepted but has no filtering effect — all hooks are returned regardless. This is a WordPress architecture constraint.

**`get-hook-registrations`** — Closures are identified by file path and line number. Invokable objects are identified by class name. Static and instance method arrays are shown as `ClassName::method`.

**`read-debug-log`** — Reads from the path defined by `WP_DEBUG_LOG`. Falls back to `wp-content/debug.log`. Only reads the main site log; subsite logs are not accessible via this ability.

## Constants

This plugin defines `LH_MCP_Developer_Abilities_Plugin::VERSION` (class constant), kept in sync with the plugin header.

## Hooks

This plugin defines no custom hooks.

## Cron jobs

This plugin registers no cron jobs.

## Database tables

This plugin creates no database tables.

## Changelog

### 1.7.7 — 2026-06-11
- Added `Requires at least: 6.9` to the plugin file header (and synced `readme.md`), since `wp_register_ability_category()` and `wp_register_ability()` require WP 6.9. This clears 30 PCP errors (`wp_function_not_compatible_with_requires_wp`) that were false positives caused by the previously-undeclared minimum version.
- Shortened the plugin header `Description` field to under 150 characters, clearing the PCP `readme_parser_warnings_trimmed_short_description` warning.

### 1.7.6 — 2026-06-11
- Fixed `output_schema` for the seven abilities updated in 1.7.5: each now declares an `error` string property in `output_schema.properties` (`list-plugin-files`, `list-theme-files`, `read-plugin-file`, `read-theme-file`, `get-hook-registrations`, `get-php-ini`, `get-option`). Previously this property was missing, so with `additionalProperties: false` the MCP adapter rejected the new error-shaped responses introduced in 1.7.5 as invalid output. This also fixes a pre-existing issue on `get-option`'s blocked-option-name error path, which had the same problem.

### 1.7.5 — 2026-06-11
- Fixed seven more abilities affected by the same drift pattern: error-return paths now include all required `output_schema` fields, matching the fixes applied to `get-table-schema` and `run-select-query` in 1.7.4. Each affected ability's `output_schema.properties` now also declares an `error` string field (previously missing, which caused `additionalProperties: false` to reject error responses as invalid output).
  - `list-plugin-files` / `list-theme-files`: error returns now include `plugin_slug`/`theme_slug`, `plugin_dir`/`theme_dir`, `file_count: 0`, and `files: []`. The "directory not found" message now suggests using `get-active-network-plugins`/`get-active-local-plugins` (or `list-themes`) to find valid slugs.
  - `read-plugin-file` / `read-theme-file`: error returns (missing parameters, path traversal, disallowed extension, base directory not found, file not found) now include `plugin_slug`/`theme_slug`, `relative_path`, `extension`, `size: 0`, `modified: ''`, `truncated: false`, and `content: ''`.
  - Shared helper `read_file_from_dir()` refactored to return the full required-shape error array directly; callers now `array_merge()` their identifying field (`plugin_slug`/`theme_slug`) onto both success and error results.
  - `get-hook-registrations`: missing `hook_name` error now includes `hook_name`, `registered: false`, `callback_count: 0`, and `priorities: []`.
  - `get-php-ini`: the rare `ini_get_all()` failure path now includes `total: 0` and `settings: []`.
  - `get-option`: missing `option_name` error now includes `option_name`, `exists: false`, and `network` (correctly reflecting the parsed `network` parameter).

### 1.7.4 — 2026-06-11
- Fixed `run-select-query`: corrected the misleading "Did you mean" suggestion in the wrong-prefix error. A submitted table name with a recognised foreign prefix (e.g. `wp_comments`) now suggests the correctly-rebased name (`lhero_comments`) instead of naively concatenating the base prefix onto the original name (`lhero_wp_comments`).

### 1.7.3 — 2026-06-11
- Fixed `get-table-schema`: output_schema now includes an `error` string property. Every error-return path (missing table_name, invalid characters, table not found, wrong prefix) now returns `table_name` and `columns` as required, plus a specific `error` message that echoes the submitted name and, where relevant, this install's actual base table prefix and a suggested corrected table name.
- Fixed `run-select-query`: output_schema now includes an `error` string property. Every error-return path (missing table, invalid characters, wrong prefix, table not found, disallowed WHERE clause, query failure) now returns `row_count: 0`, `offset`, `limit`, and `rows: []` as required, plus a specific `error` message that echoes the submitted table name and, where relevant, this install's actual base table prefix and a suggested corrected table name.
- Added `LH_MCP_Developer_Abilities_Plugin::VERSION` class constant, kept in sync with the plugin header.

### 1.7.2 — 2026-06-07
- Fixed readme.md: added missing WordPress.org header fields (Tested up to, Stable tag, License, License URI) and shortened short description to under 150 characters.

### 1.7.1 — 2026-06-07
- Fixed `run-plugin-check`: Abstract_Check_Runner::__construct() is final — removed custom constructor from anonymous subclass and switched to set_plugin(), set_categories(), set_experimental_flag() setters after construction.

### 1.7.0 — 2026-06-07
- Added `run-plugin-check` ability — runs Plugin Check (PCP) static checks against an installed plugin. Uses an inline anonymous subclass of Abstract_Check_Runner so prepare() is never called and runtime checks are never triggered. Guards all Plugin Check class dependencies with class_exists() checks and returns available:false cleanly if the plugin is inactive.

### 1.6.2 — 2026-06-01
- Fixed `get-site-health`: added missing require_once for wp-admin/includes/template.php (remove_meta_box) and wp-admin/includes/update.php (wp_check_php_version).

### 1.6.1 — 2026-06-01
- Fixed `get-site-health`: require screen functions and call set_current_screen() before instantiating WP_Site_Health to avoid fatal in REST context. Added sentinel parameter.
- Fixed `get-php-ini`: fall back to full ini list with extension name as filter when ini_get_all(extension) returns empty, rather than returning an error.

### 1.6.0 — 2026-06-01
- Added `get-php-ini` ability — returns PHP ini settings via ini_get_all(), optionally scoped to an extension or filtered by substring.
- Added `get-network-options` ability — lists network sitemeta keys with sizes; use get-option with network:true to read individual values.
- Added `get-object-cache-info` ability — returns cache backend type, connection status, hit/miss ratio, and memory usage.
- Added `get-action-scheduler-status` ability — returns Action Scheduler job counts grouped by status and hook for a given site_id; uses ActionScheduler_Store API where available.
- Added `get-site-health` ability — runs WordPress Site Health synchronous direct tests and returns results with status, badge, and plain-text description.

### 1.5.4 — 2026-06-01
- Changed `get-site-config` capability from `manage_options` to `install_plugins` for uniform gating across all abilities.
- Removed now-unused `check_permissions_site_config` method.

### 1.5.3 — 2026-06-01
- Fixed readme.md: corrected scrambled ability table row numbers to sequential 1–23.

### 1.5.2 — 2026-06-01
- Changed `get-user-info` capability from `promote_users` to `install_plugins` for consistency with all other abilities.
- Added `site_id` parameter to `get-user-info` — uses `switch_to_blog` to resolve site-specific roles and locale for the specified site. Defaults to main site.
- Removed now-unused `check_permissions_user_info` method.

### 1.5.1 — 2026-06-01
- Added `list-sites` ability — returns sites on the network with blog_id, domain, path, blogname, and status fields. Supports search, public/archived/deleted/spam filtering, limit/offset pagination, and orderby/order. Use returned blog_id values with `get-active-local-plugins` and `get-rewrite-rules`.

### 1.5.0 — 2026-06-01
- All 22 abilities are now gated to the main site via `is_main_site()` in all permission callbacks. On subsite connectors these abilities will return a 403. On single-site installs `is_main_site()` always returns true so behaviour is unchanged.
- Redesigned `get-rewrite-rules`: added `site_id` parameter (defaults to main site), uses `switch_to_blog` to query any subsite's rewrite rules, returns `site_id` in response.

### 1.4.1 — 2026-06-01
- Added `get-must-use-plugins` ability — returns all mu-plugins with name, version, and author.

### 1.4.0 — 2026-06-01
- Split `get-active-plugins` into `get-active-network-plugins` (no parameters, returns network-activated plugins) and `get-active-local-plugins` (accepts `site_id`, uses `switch_to_blog` to query any site's locally-activated plugins). Both require `install_plugins`.

### 1.3.6 — 2026-06-01
- Fixed readme.md: corrected `get-user-info` capability from `edit_posts` to `promote_users` in the abilities table.

### 1.3.5 — 2026-06-01
- Fixed PHP parse error: unescaped single quote in WHERE parameter description string.

### 1.3.4 — 2026-06-01
- Changed `run-select-query` from a free-form `sql` parameter to structured `table`/`columns`/`where`/`order_by`/`group_by` parameters. The FROM clause is now built server-side, working around WAF rules that block SQL keywords in POST request bodies.

### 1.3.3 — 2026-06-01
- Security: raise `get-user-info` permission from `edit_posts` to `promote_users` (Editor and above).

### 1.3.2 — 2026-06-01
- Security: block `SELECT INTO OUTFILE/DUMPFILE` in `run-select-query` to prevent file-write disguised as a read query.

### 1.3.1 — 2026-06-01
- Security: sanitise `table_name` parameter in `get-table-schema` to alphanumerics and underscores only before any database use, preventing backtick-escape injection in the DESCRIBE statement.

### 1.3.0 — 2026-06-01
- Added `list-theme-files` ability — directory listing for installed themes.
- Added `read-theme-file` ability — single theme file content reader.
- Added `get-table-schema` ability — full column definitions (DESCRIBE) for a database table.
- Added `run-select-query` ability — read-only SELECT execution with limit/offset pagination.
- Added `get-option` ability — retrieves a single option value; supports network options; blocks sensitive names.
- Added `list-hooks` ability — returns all currently registered hook names from `$wp_filter`.
- Added `get-hook-registrations` ability — returns callbacks for a specific hook grouped by priority.
- Added `get-rewrite-rules` ability — returns stored WordPress rewrite rules with optional filter.
- Refactored `list_directory_files` and `read_file_from_dir` into shared private helpers to eliminate duplication between plugin and theme file abilities.

### 1.2.0 — 2026-05-30
- Renamed plugin from `lh-mcp-developer` to `lh-mcp-developer-abilities` for naming consistency.
- All ability slugs updated from `lh-mcp-developer/*` to `lh-mcp-developer-abilities/*`.

### 1.1.4 — 2026-04-16
- Fixed `get-ability-info` failures on `get-user-info` and `get-environment-info` by adding explicit `input_schema` with empty properties.

### 1.1.3 — 2026-04-16
- Cloned `get-site-info`, `get-user-info`, `get-environment-info` from WordPress core into `lh-mcp-performance` namespace with `install_plugins` enforcement.

### 1.1.2 — 2026-04-16
- Fixed empty `properties` serialising as `[]` instead of `{}` with `(object) array()` casts.

### 1.1.1 — 2026-04-16
- Fixed zip double-nesting packaging bug.

### 1.1.0 — 2026-04-16
- Initial release as `lh-mcp-performance`.
