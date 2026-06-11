<?php
/**
 * Plugin Name: LH MCP Developer Abilities
 * Plugin URI:  https://shawfactor.com
 * Description: Registers MCP abilities for developer diagnostics: plugin/theme files, DB inspection, hooks, options, and select queries.
 * Version:     1.7.7
 * Requires at least: 6.9
 * Author:      Peter Shaw
 * Author URI:  https://shawfactor.com
 * Network:     true
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LH_MCP_Developer_Abilities_Plugin' ) ) {

class LH_MCP_Developer_Abilities_Plugin {

	const VERSION = '1.7.7';

	private static $instance;

	// -------------------------------------------------------------------------
	// Category registration
	// -------------------------------------------------------------------------

	public function ability_category_functions() {
		wp_register_ability_category( 'developer-tools', array(
			'label'       => __( 'Developer Tools', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Abilities for developer workflows: file reading, database inspection, hook exploration, and diagnostics.', 'lh-mcp-developer-abilities' ),
		) );
	}

	// -------------------------------------------------------------------------
	// Ability registration
	// -------------------------------------------------------------------------

	public function ability_registration_functions() {

		// 1a. Get Active Network Plugins
		wp_register_ability( 'lh-mcp-developer-abilities/get-active-network-plugins', array(
			'label'       => __( 'Get Active Network Plugins', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns all network-activated plugins on this Multisite installation, each with name, version, and author.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'_' => array(
						'type'        => 'string',
						'default'     => '',
						'description' => 'Unused sentinel — workaround for buggy adapter empty() check. Safe to omit with a fixed adapter.',
					),
				),
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'plugin_count'      => array( 'type' => 'integer', 'description' => 'Number of network-activated plugins.' ),
					'network_activated' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'file'    => array( 'type' => 'string' ),
								'name'    => array( 'type' => 'string' ),
								'version' => array( 'type' => 'string' ),
								'author'  => array( 'type' => 'string' ),
							),
						),
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_active_network_plugins' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array( 'mcp' => array( 'public' => true ) ),
		) );

		// 1b. Get Active Local Plugins
		wp_register_ability( 'lh-mcp-developer-abilities/get-active-local-plugins', array(
			'label'       => __( 'Get Active Local Plugins', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns site-activated (non-network) plugins for a specific site on this Multisite installation. Defaults to the main site if no site_id is provided.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'site_id' => array(
						'type'        => 'integer',
						'description' => 'The site ID to query. Defaults to the main site (site ID 1).',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'site_id'       => array( 'type' => 'integer', 'description' => 'The site ID queried.' ),
					'plugin_count'  => array( 'type' => 'integer', 'description' => 'Number of site-activated plugins.' ),
					'site_activated' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'file'    => array( 'type' => 'string' ),
								'name'    => array( 'type' => 'string' ),
								'version' => array( 'type' => 'string' ),
								'author'  => array( 'type' => 'string' ),
							),
						),
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_active_local_plugins' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array( 'mcp' => array( 'public' => true ) ),
		) );

		// 1c. Get Must-Use Plugins
		wp_register_ability( 'lh-mcp-developer-abilities/get-must-use-plugins', array(
			'label'       => __( 'Get Must-Use Plugins', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns all must-use plugins (mu-plugins) installed on this WordPress installation. These are always active and do not appear in the network or site plugin lists.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'_' => array(
						'type'        => 'string',
						'default'     => '',
						'description' => 'Unused sentinel — workaround for buggy adapter empty() check. Safe to omit with a fixed adapter.',
					),
				),
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'plugin_count' => array( 'type' => 'integer', 'description' => 'Number of must-use plugins.' ),
					'plugins'      => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'file'    => array( 'type' => 'string', 'description' => 'Plugin file path relative to mu-plugins directory.' ),
								'name'    => array( 'type' => 'string' ),
								'version' => array( 'type' => 'string' ),
								'author'  => array( 'type' => 'string' ),
							),
						),
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_must_use_plugins' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array( 'mcp' => array( 'public' => true ) ),
		) );

		// 1d. List Sites
		wp_register_ability( 'lh-mcp-developer-abilities/list-sites', array(
			'label'       => __( 'List Sites', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns sites on this Multisite network. Supports search, filtering by status, pagination, and ordering. Use the returned blog_id values with abilities that accept a site_id parameter.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'search' => array(
						'type'        => 'string',
						'description' => 'Search string to filter sites by domain or path.',
					),
					'limit' => array(
						'type'        => 'integer',
						'description' => 'Maximum number of sites to return. Default 50, max 500.',
						'default'     => 50,
						'minimum'     => 1,
						'maximum'     => 500,
					),
					'offset' => array(
						'type'        => 'integer',
						'description' => 'Number of sites to skip for pagination. Default 0.',
						'default'     => 0,
						'minimum'     => 0,
					),
					'public' => array(
						'type'        => 'integer',
						'description' => 'Filter by public status. 1 = public, 0 = private. Omit for all.',
						'enum'        => array( 0, 1 ),
					),
					'archived' => array(
						'type'        => 'integer',
						'description' => 'Filter by archived status. 1 = archived, 0 = not archived. Default 0.',
						'enum'        => array( 0, 1 ),
						'default'     => 0,
					),
					'deleted' => array(
						'type'        => 'integer',
						'description' => 'Filter by deleted status. 1 = deleted, 0 = not deleted. Default 0.',
						'enum'        => array( 0, 1 ),
						'default'     => 0,
					),
					'spam' => array(
						'type'        => 'integer',
						'description' => 'Filter by spam status. 1 = spam, 0 = not spam. Default 0.',
						'enum'        => array( 0, 1 ),
						'default'     => 0,
					),
					'orderby' => array(
						'type'        => 'string',
						'description' => 'Field to order results by. Default "id".',
						'enum'        => array( 'id', 'domain', 'path', 'registered', 'last_updated', 'blogname' ),
						'default'     => 'id',
					),
					'order' => array(
						'type'        => 'string',
						'description' => 'Sort direction. Default "ASC".',
						'enum'        => array( 'ASC', 'DESC' ),
						'default'     => 'ASC',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'required'   => array( 'site_count', 'sites' ),
				'properties' => array(
					'site_count' => array( 'type' => 'integer', 'description' => 'Number of sites returned.' ),
					'offset'     => array( 'type' => 'integer', 'description' => 'The offset used.' ),
					'limit'      => array( 'type' => 'integer', 'description' => 'The limit applied.' ),
					'sites'      => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'blog_id'      => array( 'type' => 'integer', 'description' => 'The site ID. Use this as site_id in other abilities.' ),
								'domain'       => array( 'type' => 'string',  'description' => 'The site domain.' ),
								'path'         => array( 'type' => 'string',  'description' => 'The site path.' ),
								'blogname'     => array( 'type' => 'string',  'description' => 'The site title.' ),
								'registered'   => array( 'type' => 'string',  'description' => 'Date the site was registered.' ),
								'last_updated' => array( 'type' => 'string',  'description' => 'Date the site was last updated.' ),
								'public'       => array( 'type' => 'integer', 'description' => '1 if public, 0 if private.' ),
								'archived'     => array( 'type' => 'integer', 'description' => '1 if archived.' ),
								'deleted'      => array( 'type' => 'integer', 'description' => '1 if deleted.' ),
								'spam'         => array( 'type' => 'integer', 'description' => '1 if marked as spam.' ),
							),
						),
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_list_sites' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 2. Get Autoloaded Options
		wp_register_ability( 'lh-mcp-developer-abilities/get-autoloaded-options', array(
			'label'       => __( 'Get Autoloaded Options', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns WordPress autoloaded options sorted by size descending, plus the total autoload footprint. Useful for identifying options bloat that slows every page load.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'limit' => array(
						'type'        => 'integer',
						'description' => 'Maximum number of options to return. Default 50, max 500.',
						'default'     => 50,
						'minimum'     => 1,
						'maximum'     => 500,
					),
				),
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'total_autoloaded_count'      => array( 'type' => 'integer' ),
					'total_autoloaded_size_bytes' => array( 'type' => 'integer' ),
					'total_autoloaded_size_human' => array( 'type' => 'string' ),
					'options' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'option_name' => array( 'type' => 'string' ),
								'size_bytes'  => array( 'type' => 'integer' ),
								'size_human'  => array( 'type' => 'string' ),
							),
						),
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_autoloaded_options' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array( 'mcp' => array( 'public' => true ) ),
		) );

		// 3. Get Cron Jobs
		wp_register_ability( 'lh-mcp-developer-abilities/get-cron-jobs', array(
			'label'       => __( 'Get Cron Jobs', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns all scheduled WP-Cron events with hook name, next run time, recurrence interval, and whether they are overdue. Overdue events are listed first.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'_' => array(
					'type'    => 'string',
					'default' => '',
					'description' => 'Unused sentinel — workaround for buggy adapter empty() check. Safe to omit with a fixed adapter.',
				),
				),
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'total'  => array( 'type' => 'integer' ),
					'events' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'hook'       => array( 'type' => 'string' ),
								'next_run'   => array( 'type' => 'string' ),
								'overdue'    => array( 'type' => 'boolean' ),
								'overdue_by' => array( 'type' => 'string' ),
								'interval'   => array( 'type' => 'string' ),
								'args_hash'  => array( 'type' => 'string' ),
							),
						),
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_cron_jobs' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array( 'mcp' => array( 'public' => true ) ),
		) );

		// 4. Get Database Table Info
		wp_register_ability( 'lh-mcp-developer-abilities/get-db-table-info', array(
			'label'       => __( 'Get Database Table Info', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns size, row count, engine, and index details for all WordPress database tables (or a specific table by name without prefix).', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'table' => array(
						'type'        => 'string',
						'description' => 'Optional table name without prefix (e.g. "posts"). If omitted, all tables are returned.',
					),
				),
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'tables' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'table_name' => array( 'type' => 'string' ),
								'rows'       => array( 'type' => 'integer' ),
								'data_size'  => array( 'type' => 'string' ),
								'index_size' => array( 'type' => 'string' ),
								'total_size' => array( 'type' => 'string' ),
								'engine'     => array( 'type' => 'string' ),
								'indexes'    => array(
									'type'  => 'array',
									'items' => array(
										'type'       => 'object',
										'properties' => array(
											'key_name' => array( 'type' => 'string' ),
											'column'   => array( 'type' => 'string' ),
											'unique'   => array( 'type' => 'boolean' ),
										),
									),
								),
							),
						),
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_db_table_info' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array( 'mcp' => array( 'public' => true ) ),
		) );

		// 5. Get Transients
		wp_register_ability( 'lh-mcp-developer-abilities/get-transients', array(
			'label'       => __( 'Get Transients', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns WordPress transients stored in the options table, with expiry times and sizes. Expired transients that have not been cleaned up are flagged.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'include_expired' => array(
						'type'        => 'boolean',
						'description' => 'Include already-expired transients in results. Default true.',
						'default'     => true,
					),
					'limit' => array(
						'type'        => 'integer',
						'description' => 'Maximum number of transients to return. Default 50, max 500.',
						'default'     => 50,
						'minimum'     => 1,
						'maximum'     => 500,
					),
				),
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'total'         => array( 'type' => 'integer' ),
					'expired_count' => array( 'type' => 'integer' ),
					'transients'    => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'name'       => array( 'type' => 'string' ),
								'expires_at' => array( 'type' => 'string' ),
								'expired'    => array( 'type' => 'boolean' ),
								'size_bytes' => array( 'type' => 'integer' ),
							),
						),
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_transients' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array( 'mcp' => array( 'public' => true ) ),
		) );

		// 6. Read Debug Log
		wp_register_ability( 'lh-mcp-developer-abilities/read-debug-log', array(
			'label'       => __( 'Read Debug Log', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Reads the WordPress debug log. Supports tailing the last N lines and/or filtering by a keyword. Auto-detects the log path from WP_DEBUG_LOG.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'lines' => array(
						'type'        => 'integer',
						'description' => 'Number of lines to return from the end of the log. Default 100, max 500.',
						'default'     => 100,
						'minimum'     => 1,
						'maximum'     => 500,
					),
					'search' => array(
						'type'        => 'string',
						'description' => 'Optional keyword to filter lines. Case-insensitive. If provided, only matching lines are returned (up to the lines limit).',
					),
				),
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'log_path'      => array( 'type' => 'string',  'description' => 'Resolved path to the debug log file.' ),
					'file_size'     => array( 'type' => 'string',  'description' => 'Total size of the log file.' ),
					'total_lines'   => array( 'type' => 'integer', 'description' => 'Total number of lines in the log.' ),
					'matched_lines' => array( 'type' => 'integer', 'description' => 'Number of lines returned after filtering.' ),
					'search'        => array( 'type' => 'string',  'description' => 'The search keyword used, if any.' ),
					'lines'         => array(
						'type'        => 'array',
						'items'       => array( 'type' => 'string' ),
						'description' => 'The returned log lines.',
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_read_debug_log' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array( 'mcp' => array( 'public' => true ) ),
		) );

		// 7. Get Environment Info
		wp_register_ability( 'lh-mcp-developer-abilities/get-environment-info', array(
			'label'               => __( 'Get Environment Info', 'lh-mcp-developer-abilities' ),
			'description'         => __( 'Returns core details about the site\'s runtime context for diagnostics and compatibility (environment, PHP runtime, database server info, WordPress version).', 'lh-mcp-developer-abilities' ),
			'category'            => 'developer-tools',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'_' => array(
					'type'    => 'string',
					'default' => '',
					'description' => 'Unused sentinel — workaround for buggy adapter empty() check. Safe to omit with a fixed adapter.',
				),
				),
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'required'             => array( 'environment', 'php_version', 'db_server_info', 'wp_version' ),
				'properties'           => array(
					'environment'    => array(
						'type'        => 'string',
						'description' => __( 'The site\'s runtime environment classification (can be one of these: production, staging, development, local).', 'lh-mcp-developer-abilities' ),
						'enum'        => array( 'production', 'staging', 'development', 'local' ),
					),
					'php_version'    => array(
						'type'        => 'string',
						'description' => __( 'The PHP runtime version executing WordPress.', 'lh-mcp-developer-abilities' ),
					),
					'db_server_info' => array(
						'type'        => 'string',
						'description' => __( 'The database server vendor and version string reported by the driver.', 'lh-mcp-developer-abilities' ),
						'examples'    => array( '8.0.34', '10.11.6-MariaDB' ),
					),
					'wp_version'     => array(
						'type'        => 'string',
						'description' => __( 'The WordPress core version running on this site.', 'lh-mcp-developer-abilities' ),
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_environment_info' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
				'show_in_rest' => true,
				'mcp'          => array( 'public' => true ),
			),
		) );

		// 8. Get Installed Themes
		wp_register_ability( 'lh-mcp-developer-abilities/get-installed-themes', array(
			'label'       => __( 'Get Installed Themes', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns all installed themes with name, version, author, and stylesheet slug. On Multisite, indicates which theme is network-enabled.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'_' => array(
						'type'        => 'string',
						'default'     => '',
						'description' => 'Unused sentinel — workaround for buggy adapter empty() check. Safe to omit with a fixed adapter.',
					),
				),
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'is_multisite'   => array( 'type' => 'boolean' ),
					'network_theme'  => array(
						'type'        => array( 'string', 'null' ),
						'description' => 'Stylesheet slug of the network-enabled theme, or null on single site.',
					),
					'themes' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'slug'           => array( 'type' => 'string' ),
								'name'           => array( 'type' => 'string' ),
								'version'        => array( 'type' => 'string' ),
								'author'         => array( 'type' => 'string' ),
								'network_enabled' => array( 'type' => 'boolean' ),
							),
						),
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_installed_themes' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array( 'mcp' => array( 'public' => true ) ),
		) );

		// 9. Get Site Config
		$site_config_fields = array( 'name', 'description', 'url', 'wpurl', 'admin_email', 'charset', 'language', 'version' );
		wp_register_ability( 'lh-mcp-developer-abilities/get-site-config', array(
			'label'               => __( 'Get Site Config', 'lh-mcp-developer-abilities' ),
			'description'         => __( 'Returns full site configuration including admin email, WordPress version, and installation URL. Requires install_plugins.', 'lh-mcp-developer-abilities' ),
			'category'            => 'developer-tools',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'fields' => array(
						'type'        => 'array',
						'description' => 'Optional subset of fields to return.',
						'items'       => array(
							'type' => 'string',
							'enum' => $site_config_fields,
						),
					),
				),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'name'        => array( 'type' => 'string', 'description' => 'The site title.' ),
					'description' => array( 'type' => 'string', 'description' => 'The site tagline.' ),
					'url'         => array( 'type' => 'string', 'description' => 'The site home URL.' ),
					'wpurl'       => array( 'type' => 'string', 'description' => 'The WordPress installation URL.' ),
					'admin_email' => array( 'type' => 'string', 'description' => 'The site administrator email address.' ),
					'charset'     => array( 'type' => 'string', 'description' => 'The site character encoding.' ),
					'language'    => array( 'type' => 'string', 'description' => 'The site language locale code.' ),
					'version'     => array( 'type' => 'string', 'description' => 'The WordPress version.' ),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_site_config' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
				'mcp' => array( 'public' => true ),
			),
		) );

		// 10. Get User Info
		wp_register_ability( 'lh-mcp-developer-abilities/get-user-info', array(
			'label'               => __( 'Get User Info', 'lh-mcp-developer-abilities' ),
			'description'         => __( 'Returns profile details for the current authenticated user: ID, display name, login, roles, and locale. Accepts an optional site_id to return roles and locale in the context of a specific site.', 'lh-mcp-developer-abilities' ),
			'category'            => 'developer-tools',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'site_id' => array(
						'type'        => 'integer',
						'description' => 'Optional site ID to return roles and locale in the context of a specific site. Defaults to the main site.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'required'             => array( 'id', 'display_name', 'user_nicename', 'user_login', 'site_id', 'roles', 'locale' ),
				'properties'           => array(
					'id'            => array( 'type' => 'integer', 'description' => 'The user ID.' ),
					'display_name'  => array( 'type' => 'string',  'description' => 'The display name of the user.' ),
					'user_nicename' => array( 'type' => 'string',  'description' => 'The URL-friendly name for the user.' ),
					'user_login'    => array( 'type' => 'string',  'description' => 'The login username for the user.' ),
					'site_id'       => array( 'type' => 'integer', 'description' => 'The site ID used for role and locale resolution.' ),
					'roles'         => array(
						'type'        => 'array',
						'description' => 'The roles assigned to the user on the specified site.',
						'items'       => array( 'type' => 'string' ),
					),
					'locale'        => array( 'type' => 'string',  'description' => 'The locale string for the user on the specified site.' ),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_user_info' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
				'mcp' => array( 'public' => true ),
			),
		) );

		// 11. List Plugin Files
		wp_register_ability( 'lh-mcp-developer-abilities/list-plugin-files', array(
			'label'               => __( 'List Plugin Files', 'lh-mcp-developer-abilities' ),
			'description'         => __( 'Returns a directory listing of all readable files in an installed plugin, with relative path, size in bytes, and last modified time. Use read-plugin-file to retrieve individual file contents.', 'lh-mcp-developer-abilities' ),
			'category'            => 'developer-tools',
			'input_schema'        => array(
				'type'       => 'object',
				'required'   => array( 'plugin_slug' ),
				'properties' => array(
					'plugin_slug' => array(
						'type'        => 'string',
						'description' => __( 'The plugin folder slug (e.g. "lh-mcp-developer-abilities").', 'lh-mcp-developer-abilities' ),
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'required'   => array( 'plugin_slug', 'plugin_dir', 'file_count', 'files' ),
				'properties' => array(
					'plugin_slug' => array( 'type' => 'string', 'description' => 'The plugin slug.' ),
					'plugin_dir'  => array( 'type' => 'string', 'description' => 'Absolute path to the plugin directory.' ),
					'error'       => array( 'type' => 'string', 'description' => 'Present only if the request could not be completed (e.g. missing plugin_slug or directory not found).' ),
					'file_count'  => array( 'type' => 'integer', 'description' => 'Total number of listed files.' ),
					'files'       => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'required'   => array( 'path', 'size', 'modified' ),
							'properties' => array(
								'path'     => array( 'type' => 'string',  'description' => 'Relative path from plugin root.' ),
								'size'     => array( 'type' => 'integer', 'description' => 'File size in bytes.' ),
								'modified' => array( 'type' => 'string',  'description' => 'Last modified time as ISO 8601 UTC string.' ),
							),
						),
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute_list_plugin_files' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 12. Read Plugin File
		wp_register_ability( 'lh-mcp-developer-abilities/read-plugin-file', array(
			'label'               => __( 'Read Plugin File', 'lh-mcp-developer-abilities' ),
			'description'         => __( 'Returns the content of a single file from an installed plugin. Allowed extensions: php, js, json, md, txt, css, html, xml, yaml, yml, svg. Files over 100 KB are truncated with a notice. Use list-plugin-files first to discover available paths.', 'lh-mcp-developer-abilities' ),
			'category'            => 'developer-tools',
			'input_schema'        => array(
				'type'       => 'object',
				'required'   => array( 'plugin_slug', 'relative_path' ),
				'properties' => array(
					'plugin_slug'   => array(
						'type'        => 'string',
						'description' => __( 'The plugin folder slug (e.g. "lh-mcp-developer-abilities").', 'lh-mcp-developer-abilities' ),
					),
					'relative_path' => array(
						'type'        => 'string',
						'description' => __( 'Path to the file relative to the plugin root (e.g. "lh-mcp-developer-abilities.php" or "includes/class-foo.php").', 'lh-mcp-developer-abilities' ),
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'required'   => array( 'plugin_slug', 'relative_path', 'extension', 'size', 'modified', 'truncated', 'content' ),
				'properties' => array(
					'plugin_slug'   => array( 'type' => 'string',  'description' => 'The plugin slug.' ),
					'relative_path' => array( 'type' => 'string',  'description' => 'The relative path of the file.' ),
					'error'         => array( 'type' => 'string',  'description' => 'Present only if the request could not be completed (e.g. missing parameters, path traversal, disallowed extension, or file not found).' ),
					'extension'     => array( 'type' => 'string',  'description' => 'The file extension.' ),
					'size'          => array( 'type' => 'integer', 'description' => 'Full file size in bytes.' ),
					'modified'      => array( 'type' => 'string',  'description' => 'Last modified time as ISO 8601 UTC string.' ),
					'truncated'     => array( 'type' => 'boolean', 'description' => 'True if the file was truncated due to size limit.' ),
					'content'       => array( 'type' => 'string',  'description' => 'File content, truncated to 100 KB if necessary.' ),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute_read_plugin_file' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 13. List Theme Files
		wp_register_ability( 'lh-mcp-developer-abilities/list-theme-files', array(
			'label'               => __( 'List Theme Files', 'lh-mcp-developer-abilities' ),
			'description'         => __( 'Returns a directory listing of all readable files in an installed theme, with relative path, size in bytes, and last modified time. Use read-theme-file to retrieve individual file contents.', 'lh-mcp-developer-abilities' ),
			'category'            => 'developer-tools',
			'input_schema'        => array(
				'type'       => 'object',
				'required'   => array( 'theme_slug' ),
				'properties' => array(
					'theme_slug' => array(
						'type'        => 'string',
						'description' => 'The theme stylesheet slug (folder name), e.g. "twentytwentyfour".',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'required'   => array( 'theme_slug', 'theme_dir', 'file_count', 'files' ),
				'properties' => array(
					'theme_slug' => array( 'type' => 'string',  'description' => 'The theme slug.' ),
					'theme_dir'  => array( 'type' => 'string',  'description' => 'Absolute path to the theme directory.' ),
					'error'      => array( 'type' => 'string',  'description' => 'Present only if the request could not be completed (e.g. missing theme_slug or directory not found).' ),
					'file_count' => array( 'type' => 'integer', 'description' => 'Total number of listed files.' ),
					'files'      => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'required'   => array( 'path', 'size', 'modified' ),
							'properties' => array(
								'path'     => array( 'type' => 'string',  'description' => 'Relative path from theme root.' ),
								'size'     => array( 'type' => 'integer', 'description' => 'File size in bytes.' ),
								'modified' => array( 'type' => 'string',  'description' => 'Last modified time as ISO 8601 UTC string.' ),
							),
						),
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute_list_theme_files' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 14. Read Theme File
		wp_register_ability( 'lh-mcp-developer-abilities/read-theme-file', array(
			'label'               => __( 'Read Theme File', 'lh-mcp-developer-abilities' ),
			'description'         => __( 'Returns the content of a single file from an installed theme. Allowed extensions: php, js, json, md, txt, css, html, xml, yaml, yml, svg. Files over 100 KB are truncated with a notice. Use list-theme-files first to discover available paths.', 'lh-mcp-developer-abilities' ),
			'category'            => 'developer-tools',
			'input_schema'        => array(
				'type'       => 'object',
				'required'   => array( 'theme_slug', 'relative_path' ),
				'properties' => array(
					'theme_slug'    => array(
						'type'        => 'string',
						'description' => 'The theme stylesheet slug (folder name), e.g. "twentytwentyfour".',
					),
					'relative_path' => array(
						'type'        => 'string',
						'description' => 'Path to the file relative to the theme root (e.g. "style.css" or "template-parts/header.php").',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'required'   => array( 'theme_slug', 'relative_path', 'extension', 'size', 'modified', 'truncated', 'content' ),
				'properties' => array(
					'theme_slug'    => array( 'type' => 'string',  'description' => 'The theme slug.' ),
					'relative_path' => array( 'type' => 'string',  'description' => 'The relative path of the file.' ),
					'error'         => array( 'type' => 'string',  'description' => 'Present only if the request could not be completed (e.g. missing parameters, path traversal, disallowed extension, or file not found).' ),
					'extension'     => array( 'type' => 'string',  'description' => 'The file extension.' ),
					'size'          => array( 'type' => 'integer', 'description' => 'Full file size in bytes.' ),
					'modified'      => array( 'type' => 'string',  'description' => 'Last modified time as ISO 8601 UTC string.' ),
					'truncated'     => array( 'type' => 'boolean', 'description' => 'True if the file was truncated due to size limit.' ),
					'content'       => array( 'type' => 'string',  'description' => 'File content, truncated to 100 KB if necessary.' ),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute_read_theme_file' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 15. Get Table Schema
		wp_register_ability( 'lh-mcp-developer-abilities/get-table-schema', array(
			'label'       => __( 'Get Table Schema', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns the full column definitions for a database table: column name, type, nullability, default, key, and extra. Pass the full table name including prefix.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'required'   => array( 'table_name' ),
				'properties' => array(
					'table_name' => array(
						'type'        => 'string',
						'description' => 'Full table name including prefix (e.g. "wp_posts" or "wp_lh_relationships").',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'required'   => array( 'table_name', 'columns' ),
				'properties' => array(
					'table_name' => array( 'type' => 'string', 'description' => 'The table name queried.' ),
					'error'      => array( 'type' => 'string', 'description' => 'Present only if the request could not be completed (e.g. table not found, invalid name, or wrong prefix).' ),
					'columns'    => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'field'   => array( 'type' => 'string', 'description' => 'Column name.' ),
								'type'    => array( 'type' => 'string', 'description' => 'Column data type.' ),
								'null'    => array( 'type' => 'string', 'description' => 'YES or NO.' ),
								'key'     => array( 'type' => 'string', 'description' => 'PRI, UNI, MUL, or empty.' ),
								'default' => array( 'type' => array( 'string', 'null' ), 'description' => 'Default value or null.' ),
								'extra'   => array( 'type' => 'string', 'description' => 'Extra info e.g. auto_increment.' ),
							),
						),
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_table_schema' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 16. Run Select Query
		wp_register_ability( 'lh-mcp-developer-abilities/run-select-query', array(
			'label'       => __( 'Run Select Query', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Queries a WordPress database table and returns results. Provide the table name (required) and optionally a comma-separated column list (defaults to *). A WHERE clause, ORDER BY, and GROUP BY can also be supplied as separate parameters. A LIMIT is enforced (max 500 rows). Paginate using the offset parameter.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'required'   => array( 'table' ),
				'properties' => array(
					'table' => array(
						'type'        => 'string',
						'description' => 'Full table name including prefix (e.g. "lhero_posts"). The query is built server-side as SELECT columns FROM table.',
					),
					'columns' => array(
						'type'        => 'string',
						'description' => 'Comma-separated list of columns to return. Defaults to * (all columns).',
						'default'     => '*',
					),
					'where' => array(
						'type'        => 'string',
						'description' => 'Optional WHERE clause without the WHERE keyword (e.g. post_status = publish).',
					),
					'order_by' => array(
						'type'        => 'string',
						'description' => 'Optional ORDER BY clause without the ORDER BY keywords (e.g. "post_date DESC").',
					),
					'group_by' => array(
						'type'        => 'string',
						'description' => 'Optional GROUP BY clause without the GROUP BY keywords (e.g. "post_type").',
					),
					'limit' => array(
						'type'        => 'integer',
						'description' => 'Maximum rows to return. Default 100, max 500.',
						'default'     => 100,
						'minimum'     => 1,
						'maximum'     => 500,
					),
					'offset' => array(
						'type'        => 'integer',
						'description' => 'Row offset for pagination. Default 0.',
						'default'     => 0,
						'minimum'     => 0,
					),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'required'   => array( 'row_count', 'rows' ),
				'properties' => array(
					'row_count'  => array( 'type' => 'integer', 'description' => 'Number of rows returned.' ),
					'error'      => array( 'type' => 'string', 'description' => 'Present only if the request could not be completed (e.g. table not found, invalid table/column/where, or wrong prefix).' ),
					'offset'     => array( 'type' => 'integer', 'description' => 'The offset used.' ),
					'limit'      => array( 'type' => 'integer', 'description' => 'The limit applied.' ),
					'query'      => array( 'type' => 'string',  'description' => 'The SQL query that was executed.' ),
					'rows'       => array(
						'type'        => 'array',
						'description' => 'Result rows as objects.',
						'items'       => array( 'type' => 'object' ),
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute_run_select_query' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 17. Get Option
		wp_register_ability( 'lh-mcp-developer-abilities/get-option', array(
			'label'       => __( 'Get Option', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns the value of a single WordPress option by name. Supports site options (network-level) via the network parameter. The value is returned serialised as JSON. Blocked for sensitive option names (auth keys, salts, DB credentials).', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'required'   => array( 'option_name' ),
				'properties' => array(
					'option_name' => array(
						'type'        => 'string',
						'description' => 'The option name to retrieve.',
					),
					'network' => array(
						'type'        => 'boolean',
						'description' => 'If true, retrieves a network-level site option via get_site_option(). Default false.',
						'default'     => false,
					),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'required'   => array( 'option_name', 'exists', 'network' ),
				'properties' => array(
					'option_name' => array( 'type' => 'string', 'description' => 'The option name queried.' ),
					'error'       => array( 'type' => 'string', 'description' => 'Present only if the request could not be completed (e.g. missing option_name or blocked option name).' ),
					'exists'      => array( 'type' => 'boolean', 'description' => 'Whether the option exists.' ),
					'network'     => array( 'type' => 'boolean', 'description' => 'Whether this was a network option lookup.' ),
					'value'       => array( 'description' => 'The option value (any JSON type). Absent if blocked or not found.' ),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_option' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 18. List Hooks
		wp_register_ability( 'lh-mcp-developer-abilities/list-hooks', array(
			'label'       => __( 'List Hooks', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns the names of all currently registered action and filter hooks, optionally filtered by a prefix or substring. Use get-hook-registrations to see the callbacks for a specific hook.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'filter'   => array(
						'type'        => 'string',
						'description' => 'Optional substring to filter hook names (case-insensitive). Returns only hooks whose name contains this string.',
					),
					'type' => array(
						'type'        => 'string',
						'description' => 'Optionally restrict to "action" or "filter" hooks. Default returns all.',
						'enum'        => array( 'action', 'filter', 'all' ),
						'default'     => 'all',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'required'   => array( 'hook_count', 'hooks' ),
				'properties' => array(
					'hook_count' => array( 'type' => 'integer', 'description' => 'Total number of hooks returned.' ),
					'hooks'      => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
						'description' => 'Sorted list of hook names.',
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute_list_hooks' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 19. Get Hook Registrations
		wp_register_ability( 'lh-mcp-developer-abilities/get-hook-registrations', array(
			'label'       => __( 'Get Hook Registrations', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns all registered callbacks for a specific action or filter hook, grouped by priority. Shows the callback name/class, accepted argument count, and priority.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'required'   => array( 'hook_name' ),
				'properties' => array(
					'hook_name' => array(
						'type'        => 'string',
						'description' => 'The exact hook name (e.g. "save_post" or "the_content").',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'required'   => array( 'hook_name', 'registered', 'callback_count', 'priorities' ),
				'properties' => array(
					'hook_name'      => array( 'type' => 'string',  'description' => 'The hook name queried.' ),
					'error'          => array( 'type' => 'string',  'description' => 'Present only if the request could not be completed (e.g. missing hook_name).' ),
					'registered'     => array( 'type' => 'boolean', 'description' => 'Whether this hook has any registered callbacks.' ),
					'callback_count' => array( 'type' => 'integer', 'description' => 'Total callbacks across all priorities.' ),
					'priorities'     => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'priority'  => array( 'type' => 'integer', 'description' => 'The priority level.' ),
								'callbacks' => array(
									'type'  => 'array',
									'items' => array(
										'type'       => 'object',
										'properties' => array(
											'callback'      => array( 'type' => 'string', 'description' => 'Human-readable callback identifier.' ),
											'accepted_args' => array( 'type' => 'integer', 'description' => 'Number of arguments the callback accepts.' ),
										),
									),
								),
							),
						),
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_hook_registrations' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 20. Get Rewrite Rules
		wp_register_ability( 'lh-mcp-developer-abilities/get-rewrite-rules', array(
			'label'       => __( 'Get Rewrite Rules', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns the rewrite rules for a specific site on this Multisite installation, with each regex mapped to its query string. Defaults to the main site if no site_id is provided. Optionally filter by a substring.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'site_id' => array(
						'type'        => 'integer',
						'description' => 'The site ID to query rewrite rules for. Defaults to the main site.',
					),
					'filter' => array(
						'type'        => 'string',
						'description' => 'Optional substring to filter rules (case-insensitive match against regex or query).',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'required'   => array( 'site_id', 'rule_count', 'rules' ),
				'properties' => array(
					'site_id'    => array( 'type' => 'integer', 'description' => 'The site ID queried.' ),
					'rule_count' => array( 'type' => 'integer', 'description' => 'Number of rules returned.' ),
					'rules'      => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'required'   => array( 'regex', 'query' ),
							'properties' => array(
								'regex' => array( 'type' => 'string', 'description' => 'The URL regex pattern.' ),
								'query' => array( 'type' => 'string', 'description' => 'The WordPress query string the regex maps to.' ),
							),
						),
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_rewrite_rules' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );
		// 24. Get PHP INI
		wp_register_ability( 'lh-mcp-developer-abilities/get-php-ini', array(
			'label'       => __( 'Get PHP INI', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns PHP configuration values from ini_get_all(). Optionally scope to a single extension (e.g. opcache, redis, mbstring) or filter by a substring. Returns global_value, local_value, and access level per entry.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'extension' => array(
						'type'        => 'string',
						'description' => 'Optional PHP extension name to scope results (e.g. "opcache", "redis", "mbstring"). If omitted, all extensions are returned.',
					),
					'filter' => array(
						'type'        => 'string',
						'description' => 'Optional substring to filter setting names (case-insensitive).',
					),
					'limit' => array(
						'type'        => 'integer',
						'description' => 'Maximum number of settings to return. Default 100, max 500.',
						'default'     => 100,
						'minimum'     => 1,
						'maximum'     => 500,
					),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'required'   => array( 'total', 'settings' ),
				'properties' => array(
					'error'    => array( 'type' => 'string',  'description' => 'Present only if the request could not be completed (e.g. ini_get_all() failed).' ),
					'total'    => array( 'type' => 'integer', 'description' => 'Total number of settings returned.' ),
					'settings' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'name'         => array( 'type' => 'string' ),
								'global_value' => array( 'type' => array( 'string', 'null' ) ),
								'local_value'  => array( 'type' => array( 'string', 'null' ) ),
								'access'       => array( 'type' => 'integer', 'description' => 'PHP INI access level constant.' ),
							),
						),
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_php_ini' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 25. Get Network Options
		wp_register_ability( 'lh-mcp-developer-abilities/get-network-options', array(
			'label'       => __( 'Get Network Options', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns network-level options (sitemeta) with optional substring filtering. Returns option name and size only — use get-option with network:true to retrieve a specific value.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'filter' => array(
						'type'        => 'string',
						'description' => 'Optional substring to filter option names (case-insensitive).',
					),
					'limit' => array(
						'type'        => 'integer',
						'description' => 'Maximum number of options to return. Default 50, max 500.',
						'default'     => 50,
						'minimum'     => 1,
						'maximum'     => 500,
					),
					'offset' => array(
						'type'        => 'integer',
						'description' => 'Offset for pagination. Default 0.',
						'default'     => 0,
						'minimum'     => 0,
					),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'required'   => array( 'total', 'options' ),
				'properties' => array(
					'total'   => array( 'type' => 'integer', 'description' => 'Total matching options before limit.' ),
					'limit'   => array( 'type' => 'integer' ),
					'offset'  => array( 'type' => 'integer' ),
					'options' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'option_name' => array( 'type' => 'string' ),
								'size_bytes'  => array( 'type' => 'integer' ),
								'size_human'  => array( 'type' => 'string' ),
							),
						),
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_network_options' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 26. Get Object Cache Info
		wp_register_ability( 'lh-mcp-developer-abilities/get-object-cache-info', array(
			'label'       => __( 'Get Object Cache Info', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns object cache status, hit/miss statistics, and memory usage. Detects Redis, Memcached, and APCu backends. Returns wp_cache_get_stats() data plus backend-specific info where available.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'_' => array(
						'type'        => 'string',
						'default'     => '',
						'description' => 'Unused sentinel — workaround for buggy adapter empty() check.',
					),
				),
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'backend'       => array( 'type' => 'string',  'description' => 'Detected cache backend (redis, memcached, apcu, or default).' ),
					'connected'     => array( 'type' => 'boolean', 'description' => 'Whether the cache backend is connected.' ),
					'hits'          => array( 'type' => 'integer', 'description' => 'Cache hits.' ),
					'misses'        => array( 'type' => 'integer', 'description' => 'Cache misses.' ),
					'hit_ratio'     => array( 'type' => 'string',  'description' => 'Hit ratio as a percentage string.' ),
					'memory_used'   => array( 'type' => 'string',  'description' => 'Memory used by the cache.' ),
					'memory_limit'  => array( 'type' => 'string',  'description' => 'Memory limit of the cache.' ),
					'uptime'        => array( 'type' => 'string',  'description' => 'Cache server uptime where available.' ),
					'raw_stats'     => array( 'type' => 'object',  'description' => 'Raw stats array from wp_cache_get_stats().' ),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_object_cache_info' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 27. Get Action Scheduler Status
		wp_register_ability( 'lh-mcp-developer-abilities/get-action-scheduler-status', array(
			'label'       => __( 'Get Action Scheduler Status', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns Action Scheduler job counts grouped by status and hook for a specific site. Uses ActionScheduler_Store::action_counts() where available. Defaults to the main site if no site_id is provided.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'site_id' => array(
						'type'        => 'integer',
						'description' => 'The site ID to query. Defaults to the main site.',
					),
					'status' => array(
						'type'        => 'string',
						'description' => 'Optional status filter: pending, running, complete, failed, cancelled. Omit for all.',
						'enum'        => array( 'pending', 'running', 'complete', 'failed', 'cancelled' ),
					),
					'hook_filter' => array(
						'type'        => 'string',
						'description' => 'Optional substring to filter hook names.',
					),
					'limit' => array(
						'type'        => 'integer',
						'description' => 'Maximum number of hook rows to return. Default 50, max 500.',
						'default'     => 50,
						'minimum'     => 1,
						'maximum'     => 500,
					),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'required'   => array( 'site_id', 'available', 'status_counts', 'hooks' ),
				'properties' => array(
					'site_id'       => array( 'type' => 'integer', 'description' => 'The site ID queried.' ),
					'available'     => array( 'type' => 'boolean', 'description' => 'Whether Action Scheduler is available on this site.' ),
					'status_counts' => array(
						'type'        => 'object',
						'description' => 'Total job counts by status.',
					),
					'hooks' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'hook'   => array( 'type' => 'string' ),
								'status' => array( 'type' => 'string' ),
								'count'  => array( 'type' => 'integer' ),
							),
						),
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_action_scheduler_status' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 29. Run Plugin Check
		wp_register_ability( 'lh-mcp-developer-abilities/run-plugin-check', array(
			'label'       => __( 'Run Plugin Check', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Runs the WordPress Plugin Check (PCP) static checks against an installed plugin and returns errors and warnings. Requires the Plugin Check plugin to be active. Runtime checks are excluded — only static file-based checks are run.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'required'   => array( 'plugin_slug' ),
				'properties' => array(
					'plugin_slug' => array(
						'type'        => 'string',
						'description' => 'The plugin folder slug to check (e.g. "lh-mcp-developer-abilities"). Must be an installed plugin.',
					),
					'categories' => array(
						'type'        => 'array',
						'description' => 'Optional array of check categories to limit results to (e.g. "general", "plugin_repo", "security", "performance", "accessibility"). Omit for all categories.',
						'items'       => array( 'type' => 'string' ),
					),
					'include_experimental' => array(
						'type'        => 'boolean',
						'description' => 'Whether to include experimental checks. Default false.',
						'default'     => false,
					),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'required'   => array( 'available', 'plugin_slug' ),
				'properties' => array(
					'available'     => array( 'type' => 'boolean', 'description' => 'Whether Plugin Check is available and the check could be run.' ),
					'plugin_slug'   => array( 'type' => 'string',  'description' => 'The plugin slug checked.' ),
					'error_count'   => array( 'type' => 'integer', 'description' => 'Total number of errors.' ),
					'warning_count' => array( 'type' => 'integer', 'description' => 'Total number of warnings.' ),
					'errors'        => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'file'    => array( 'type' => 'string',  'description' => 'File path relative to plugin root.' ),
								'line'    => array( 'type' => 'integer', 'description' => 'Line number.' ),
								'column'  => array( 'type' => 'integer', 'description' => 'Column number.' ),
								'code'    => array( 'type' => 'string',  'description' => 'Check or sniff code.' ),
								'message' => array( 'type' => 'string',  'description' => 'Human-readable error message.' ),
							),
						),
					),
					'warnings' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'file'    => array( 'type' => 'string' ),
								'line'    => array( 'type' => 'integer' ),
								'column'  => array( 'type' => 'integer' ),
								'code'    => array( 'type' => 'string' ),
								'message' => array( 'type' => 'string' ),
							),
						),
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_run_plugin_check' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => false ),
				'mcp'         => array( 'public' => true ),
			),
		) );

		// 28. Get Site Health
		wp_register_ability( 'lh-mcp-developer-abilities/get-site-health', array(
			'label'       => __( 'Get Site Health', 'lh-mcp-developer-abilities' ),
			'description' => __( 'Returns WordPress Site Health check results. Runs the available direct tests and returns pass/fail status with labels and descriptions. Does not run async tests.', 'lh-mcp-developer-abilities' ),
			'category'    => 'developer-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'status_filter' => array(
						'type'        => 'string',
						'description' => 'Optional filter: "good", "recommended", or "critical". Omit for all.',
						'enum'        => array( 'good', 'recommended', 'critical' ),
					),
					'_' => array(
						'type'        => 'string',
						'default'     => '',
						'description' => 'Unused sentinel — workaround for buggy adapter empty() check.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'required'   => array( 'total', 'good', 'recommended', 'critical', 'tests' ),
				'properties' => array(
					'total'       => array( 'type' => 'integer' ),
					'good'        => array( 'type' => 'integer' ),
					'recommended' => array( 'type' => 'integer' ),
					'critical'    => array( 'type' => 'integer' ),
					'tests'       => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'test'        => array( 'type' => 'string', 'description' => 'Test identifier.' ),
								'label'       => array( 'type' => 'string', 'description' => 'Human-readable label.' ),
								'status'      => array( 'type' => 'string', 'description' => 'good, recommended, or critical.' ),
								'badge'       => array( 'type' => 'string', 'description' => 'Category badge label.' ),
								'description' => array( 'type' => 'string', 'description' => 'Plain text description stripped of HTML.' ),
							),
						),
					),
				),
			),
			'execute_callback'    => array( __CLASS__, 'execute_get_site_health' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'meta'                => array(
				'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'mcp'         => array( 'public' => true ),
			),
		) );

	}

	// -------------------------------------------------------------------------
	// Permission callbacks
	// -------------------------------------------------------------------------

	public static function check_permissions( $input = null ) {
		if ( ! is_main_site() ) {
			return new WP_Error( 'forbidden', 'Developer abilities are only available on the main site.', array( 'status' => 403 ) );
		}
		if ( ! current_user_can( 'install_plugins' ) ) {
			return new WP_Error( 'forbidden', 'You do not have permission to access developer tools.', array( 'status' => 403 ) );
		}
		return true;
	}




	// -------------------------------------------------------------------------
	// Execute callbacks — existing abilities (unchanged)
	// -------------------------------------------------------------------------

	public static function execute_get_active_network_plugins( ?array $input = null ): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins    = get_plugins();
		$network_active = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );

		$build = function( $file ) use ( $all_plugins ) {
			$data = isset( $all_plugins[ $file ] ) ? $all_plugins[ $file ] : array();
			return array(
				'file'    => $file,
				'name'    => isset( $data['Name'] )    ? $data['Name']                        : $file,
				'version' => isset( $data['Version'] ) ? $data['Version']                     : '',
				'author'  => isset( $data['Author'] )  ? wp_strip_all_tags( $data['Author'] ) : '',
			);
		};

		$plugins = array_values( array_map( $build, $network_active ) );

		return array(
			'plugin_count'      => count( $plugins ),
			'network_activated' => $plugins,
		);
	}

	public static function execute_get_active_local_plugins( ?array $input = null ): array {
		$input   = is_array( $input ) ? $input : array();
		$site_id = isset( $input['site_id'] ) ? absint( $input['site_id'] ) : get_main_site_id();

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();

		// Switch to the requested site to read its active_plugins option.
		$switched = false;
		if ( is_multisite() && get_current_blog_id() !== $site_id ) {
			switch_to_blog( $site_id );
			$switched = true;
		}

		$site_active = (array) get_option( 'active_plugins', array() );

		if ( $switched ) {
			restore_current_blog();
		}

		$build = function( $file ) use ( $all_plugins ) {
			$data = isset( $all_plugins[ $file ] ) ? $all_plugins[ $file ] : array();
			return array(
				'file'    => $file,
				'name'    => isset( $data['Name'] )    ? $data['Name']                        : $file,
				'version' => isset( $data['Version'] ) ? $data['Version']                     : '',
				'author'  => isset( $data['Author'] )  ? wp_strip_all_tags( $data['Author'] ) : '',
			);
		};

		$plugins = array_values( array_map( $build, $site_active ) );

		return array(
			'site_id'        => $site_id,
			'plugin_count'   => count( $plugins ),
			'site_activated' => $plugins,
		);
	}

	public static function execute_get_must_use_plugins( ?array $input = null ): array {
		if ( ! function_exists( 'get_mu_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$mu_plugins = get_mu_plugins();
		$plugins    = array();

		foreach ( $mu_plugins as $file => $data ) {
			$plugins[] = array(
				'file'    => $file,
				'name'    => isset( $data['Name'] )    ? $data['Name']                        : $file,
				'version' => isset( $data['Version'] ) ? $data['Version']                     : '',
				'author'  => isset( $data['Author'] )  ? wp_strip_all_tags( $data['Author'] ) : '',
			);
		}

		return array(
			'plugin_count' => count( $plugins ),
			'plugins'      => $plugins,
		);
	}

	public static function execute_list_sites( ?array $input = null ): array {
		$input   = is_array( $input ) ? $input : array();
		$limit   = isset( $input['limit'] )   ? absint( $input['limit'] )   : 50;
		$limit   = max( 1, min( 500, $limit ) );
		$offset  = isset( $input['offset'] )  ? absint( $input['offset'] )  : 0;
		$orderby = isset( $input['orderby'] ) ? $input['orderby']           : 'id';
		$order   = isset( $input['order'] )   ? strtoupper( $input['order'] ) : 'ASC';

		$allowed_orderby = array( 'id', 'domain', 'path', 'registered', 'last_updated', 'blogname' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'id';
		}
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'ASC';
		}

		$args = array(
			'number'  => $limit,
			'offset'  => $offset,
			'orderby' => $orderby,
			'order'   => $order,
		);

		if ( isset( $input['search'] ) && '' !== trim( $input['search'] ) ) {
			$args['search'] = trim( $input['search'] );
		}
		if ( isset( $input['public'] ) ) {
			$args['public'] = (int) $input['public'];
		}
		if ( isset( $input['archived'] ) ) {
			$args['archived'] = (int) $input['archived'];
		} else {
			$args['archived'] = 0;
		}
		if ( isset( $input['deleted'] ) ) {
			$args['deleted'] = (int) $input['deleted'];
		} else {
			$args['deleted'] = 0;
		}
		if ( isset( $input['spam'] ) ) {
			$args['spam'] = (int) $input['spam'];
		} else {
			$args['spam'] = 0;
		}

		$sites     = get_sites( $args );
		$site_list = array();

		foreach ( $sites as $site ) {
			$site_list[] = array(
				'blog_id'      => (int) $site->blog_id,
				'domain'       => $site->domain,
				'path'         => $site->path,
				'blogname'     => get_blog_option( $site->blog_id, 'blogname', '' ),
				'registered'   => $site->registered,
				'last_updated' => $site->last_updated,
				'public'       => (int) $site->public,
				'archived'     => (int) $site->archived,
				'deleted'      => (int) $site->deleted,
				'spam'         => (int) $site->spam,
			);
		}

		return array(
			'site_count' => count( $site_list ),
			'offset'     => $offset,
			'limit'      => $limit,
			'sites'      => $site_list,
		);
	}

	public static function execute_get_autoloaded_options( ?array $input = null ) {
		global $wpdb;
		$input = is_array( $input ) ? $input : array();
		$limit = isset( $input['limit'] ) ? absint( $input['limit'] ) : 50;
		$limit = max( 1, min( 500, $limit ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			"SELECT option_name, LENGTH(option_value) AS size_bytes
			 FROM {$wpdb->options}
			 WHERE autoload IN ('yes','on','true','1')
			 ORDER BY size_bytes DESC",
			ARRAY_A
		);

		$total_count = count( $rows );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$total_size = (int) $wpdb->get_var(
			"SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN ('yes','on','true','1')"
		);

		$options = array();
		foreach ( array_slice( $rows, 0, $limit ) as $row ) {
			$bytes     = (int) $row['size_bytes'];
			$options[] = array(
				'option_name' => $row['option_name'],
				'size_bytes'  => $bytes,
				'size_human'  => self::format_bytes( $bytes ),
			);
		}

		return array(
			'total_autoloaded_count'      => $total_count,
			'total_autoloaded_size_bytes' => $total_size,
			'total_autoloaded_size_human' => self::format_bytes( $total_size ),
			'options'                     => $options,
		);
	}

	public static function execute_get_cron_jobs( ?array $input = null ) {
		$input = is_array( $input ) ? $input : array();
		$cron  = _get_cron_array();
		$now   = time();

		if ( ! is_array( $cron ) ) {
			return array( 'total' => 0, 'events' => array() );
		}

		$events    = array();
		$schedules = wp_get_schedules();

		foreach ( $cron as $timestamp => $hooks ) {
			foreach ( $hooks as $hook => $instances ) {
				foreach ( $instances as $args_hash => $instance ) {
					$schedule = isset( $instance['schedule'] ) ? $instance['schedule'] : false;
					$interval = 'single event';

					if ( $schedule && isset( $schedules[ $schedule ] ) ) {
						$interval = $schedules[ $schedule ]['display']
							. ' (' . $schedules[ $schedule ]['interval'] . 's)';
					} elseif ( $schedule ) {
						$interval = $schedule;
					}

					$overdue    = $timestamp < $now;
					$overdue_by = $overdue ? human_time_diff( $timestamp, $now ) . ' ago' : '';

					$events[] = array(
						'hook'       => $hook,
						'next_run'   => gmdate( 'Y-m-d H:i:s', $timestamp ) . ' UTC',
						'overdue'    => $overdue,
						'overdue_by' => $overdue_by,
						'interval'   => $interval,
						'args_hash'  => $args_hash,
					);
				}
			}
		}

		usort( $events, function( $a, $b ) {
			return (int) $b['overdue'] - (int) $a['overdue'];
		} );

		return array(
			'total'  => count( $events ),
			'events' => $events,
		);
	}

	public static function execute_get_db_table_info( ?array $input = null ) {
		global $wpdb;
		$input   = is_array( $input ) ? $input : array();
		$prefix  = $wpdb->prefix;
		$db_name = DB_NAME;

		$where_table = '';
		if ( ! empty( $input['table'] ) ) {
			$specific    = $prefix . sanitize_key( $input['table'] );
			$where_table = $wpdb->prepare( 'AND TABLE_NAME = %s', $specific );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH, ENGINE
				 FROM information_schema.TABLES
				 WHERE TABLE_SCHEMA = %s
				 AND TABLE_NAME LIKE %s
				 {$where_table}
				 ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC",
				$db_name,
				$wpdb->esc_like( $prefix ) . '%'
			),
			ARRAY_A
		);

		$tables = array();
		foreach ( (array) $rows as $row ) {
			$data_bytes  = (int) $row['DATA_LENGTH'];
			$index_bytes = (int) $row['INDEX_LENGTH'];
			$table_name  = $row['TABLE_NAME'];

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
			$index_rows = $wpdb->get_results( "SHOW INDEX FROM `{$table_name}`", ARRAY_A );

			$indexes = array();
			foreach ( (array) $index_rows as $idx ) {
				$indexes[] = array(
					'key_name' => $idx['Key_name'],
					'column'   => $idx['Column_name'],
					'unique'   => '0' === $idx['Non_unique'],
				);
			}

			$tables[] = array(
				'table_name' => $table_name,
				'rows'       => (int) $row['TABLE_ROWS'],
				'data_size'  => self::format_bytes( $data_bytes ),
				'index_size' => self::format_bytes( $index_bytes ),
				'total_size' => self::format_bytes( $data_bytes + $index_bytes ),
				'engine'     => $row['ENGINE'],
				'indexes'    => $indexes,
			);
		}

		return array( 'tables' => $tables );
	}

	public static function execute_get_transients( ?array $input = null ) {
		global $wpdb;
		$input           = is_array( $input ) ? $input : array();
		$include_expired = isset( $input['include_expired'] ) ? (bool) $input['include_expired'] : true;
		$limit           = isset( $input['limit'] ) ? absint( $input['limit'] ) : 50;
		$limit           = max( 1, min( 500, $limit ) );
		$now             = time();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$timeout_rows = $wpdb->get_results(
			"SELECT option_name, option_value
			 FROM {$wpdb->options}
			 WHERE option_name LIKE '_transient_timeout_%'
			 ORDER BY option_value ASC",
			ARRAY_A
		);

		$transients    = array();
		$expired_count = 0;

		foreach ( $timeout_rows as $row ) {
			$name    = str_replace( '_transient_timeout_', '', $row['option_name'] );
			$expires = (int) $row['option_value'];
			$expired = $expires > 0 && $expires < $now;

			if ( $expired ) {
				$expired_count++;
			}

			if ( ! $include_expired && $expired ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$size = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT LENGTH(option_value) FROM {$wpdb->options} WHERE option_name = %s",
					'_transient_' . $name
				)
			);

			$transients[] = array(
				'name'       => $name,
				'expires_at' => $expires > 0 ? gmdate( 'Y-m-d H:i:s', $expires ) . ' UTC' : 'never',
				'expired'    => $expired,
				'size_bytes' => $size,
			);
		}

		return array(
			'total'         => count( $timeout_rows ),
			'expired_count' => $expired_count,
			'transients'    => array_slice( $transients, 0, $limit ),
		);
	}

	public static function execute_read_debug_log( ?array $input = null ) {
		$input  = is_array( $input ) ? $input : array();
		$limit  = isset( $input['lines'] ) ? absint( $input['lines'] ) : 100;
		$limit  = max( 1, min( 500, $limit ) );
		$search = isset( $input['search'] ) ? trim( $input['search'] ) : '';

		if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) && WP_DEBUG_LOG ) {
			$log_path = WP_DEBUG_LOG;
		} else {
			$log_path = WP_CONTENT_DIR . '/debug.log';
		}

		if ( ! file_exists( $log_path ) ) {
			return new WP_Error( 'log_not_found', 'Debug log file not found at: ' . $log_path, array( 'status' => 404 ) );
		}

		if ( ! is_readable( $log_path ) ) {
			return new WP_Error( 'log_not_readable', 'Debug log file is not readable at: ' . $log_path, array( 'status' => 403 ) );
		}

		$file_size = self::format_bytes( filesize( $log_path ) );

		$all_lines = array();
		$spl       = new SplFileObject( $log_path, 'r' );
		$spl->setFlags( SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE );
		foreach ( $spl as $line ) {
			$all_lines[] = $line;
		}
		unset( $spl );

		$total_lines = count( $all_lines );

		if ( '' !== $search ) {
			$all_lines = array_values( array_filter( $all_lines, function( $line ) use ( $search ) {
				return false !== stripos( $line, $search );
			} ) );
		}

		$result_lines = array_slice( $all_lines, -$limit );

		return array(
			'log_path'      => $log_path,
			'file_size'     => $file_size,
			'total_lines'   => $total_lines,
			'matched_lines' => count( $result_lines ),
			'search'        => $search,
			'lines'         => $result_lines,
		);
	}

	public static function execute_get_environment_info( ?array $input = null ) {
		$input = is_array( $input ) ? $input : array();
		global $wpdb;

		$env            = wp_get_environment_type();
		$php_version    = phpversion();
		$db_server_info = '';
		if ( method_exists( $wpdb, 'db_server_info' ) ) {
			$db_server_info = $wpdb->db_server_info() ?? '';
		}
		$wp_version = get_bloginfo( 'version' );

		return array(
			'environment'    => $env,
			'php_version'    => $php_version,
			'db_server_info' => $db_server_info,
			'wp_version'     => $wp_version,
		);
	}

	public static function execute_get_installed_themes( ?array $input = null ) {
		$input        = is_array( $input ) ? $input : array();
		$is_multisite = is_multisite();
		$all_themes   = wp_get_themes();

		$network_theme = null;
		if ( $is_multisite ) {
			$allowed       = get_site_option( 'allowedthemes', array() );
			$enabled       = array_keys( array_filter( $allowed ) );
			$network_theme = ! empty( $enabled ) ? $enabled[0] : null;
		}

		$themes = array();
		foreach ( $all_themes as $slug => $theme ) {
			$themes[] = array(
				'slug'            => $slug,
				'name'            => $theme->get( 'Name' ),
				'version'         => $theme->get( 'Version' ),
				'author'          => wp_strip_all_tags( $theme->get( 'Author' ) ),
				'network_enabled' => $is_multisite && ( $slug === $network_theme ),
			);
		}

		return array(
			'is_multisite'  => $is_multisite,
			'network_theme' => $network_theme,
			'themes'        => $themes,
		);
	}

	public static function execute_get_site_config( ?array $input = null ): array {
		$input     = is_array( $input ) ? $input : array();
		$fields    = array( 'name', 'description', 'url', 'wpurl', 'admin_email', 'charset', 'language', 'version' );
		$requested = ! empty( $input['fields'] ) ? array_intersect( (array) $input['fields'], $fields ) : $fields;

		$result = array();
		foreach ( $requested as $field ) {
			$result[ $field ] = get_bloginfo( $field );
		}

		return $result;
	}

	public static function execute_get_user_info( ?array $input = null ): array {
		$input        = is_array( $input ) ? $input : array();
		$site_id      = isset( $input['site_id'] ) ? absint( $input['site_id'] ) : get_main_site_id();
		$current_user = wp_get_current_user();

		// Switch to the requested site to resolve site-specific roles and locale.
		$switched = false;
		if ( is_multisite() && get_current_blog_id() !== $site_id ) {
			switch_to_blog( $site_id );
			$switched = true;
		}

		// Re-fetch user in the switched context to get correct roles.
		$user   = new WP_User( $current_user->ID );
		$locale = get_user_locale( $user );

		if ( $switched ) {
			restore_current_blog();
		}

		return array(
			'id'            => $current_user->ID,
			'display_name'  => $current_user->display_name,
			'user_nicename' => $current_user->user_nicename,
			'user_login'    => $current_user->user_login,
			'site_id'       => $site_id,
			'roles'         => $user->roles,
			'locale'        => $locale,
		);
	}

	public static function execute_list_plugin_files( ?array $input = null ): array {
		$input       = is_array( $input ) ? $input : array();
		$plugin_slug = isset( $input['plugin_slug'] ) ? sanitize_file_name( $input['plugin_slug'] ) : '';

		if ( empty( $plugin_slug ) ) {
			return array(
				'error'       => 'plugin_slug is required.',
				'plugin_slug' => $plugin_slug,
				'plugin_dir'  => '',
				'file_count'  => 0,
				'files'       => array(),
			);
		}

		$plugin_dir = trailingslashit( WP_PLUGIN_DIR ) . $plugin_slug;

		if ( ! is_dir( $plugin_dir ) ) {
			return array(
				'error'       => "Plugin directory not found: {$plugin_slug}. Use get-active-network-plugins or get-active-local-plugins to see installed plugins.",
				'plugin_slug' => $plugin_slug,
				'plugin_dir'  => $plugin_dir,
				'file_count'  => 0,
				'files'       => array(),
			);
		}

		$files = self::list_directory_files( $plugin_dir );

		return array(
			'plugin_slug' => $plugin_slug,
			'plugin_dir'  => $plugin_dir,
			'file_count'  => count( $files ),
			'files'       => $files,
		);
	}

	public static function execute_read_plugin_file( ?array $input = null ): array {
		$input         = is_array( $input ) ? $input : array();
		$plugin_slug   = isset( $input['plugin_slug'] ) ? sanitize_file_name( $input['plugin_slug'] ) : '';
		$relative_path = isset( $input['relative_path'] ) ? $input['relative_path'] : '';

		if ( empty( $plugin_slug ) || empty( $relative_path ) ) {
			return array(
				'error'         => 'plugin_slug and relative_path are required.',
				'plugin_slug'   => $plugin_slug,
				'relative_path' => $relative_path,
				'extension'     => '',
				'size'          => 0,
				'modified'      => '',
				'truncated'     => false,
				'content'       => '',
			);
		}

		$base_dir = trailingslashit( WP_PLUGIN_DIR ) . $plugin_slug;
		return array_merge( array( 'plugin_slug' => $plugin_slug ), self::read_file_from_dir( $base_dir, $relative_path ) );
	}

	// -------------------------------------------------------------------------
	// Execute callbacks — new abilities
	// -------------------------------------------------------------------------

	public static function execute_list_theme_files( ?array $input = null ): array {
		$input      = is_array( $input ) ? $input : array();
		$theme_slug = isset( $input['theme_slug'] ) ? sanitize_file_name( $input['theme_slug'] ) : '';

		if ( empty( $theme_slug ) ) {
			return array(
				'error'      => 'theme_slug is required.',
				'theme_slug' => $theme_slug,
				'theme_dir'  => '',
				'file_count' => 0,
				'files'      => array(),
			);
		}

		$theme_dir = trailingslashit( get_theme_root() ) . $theme_slug;

		if ( ! is_dir( $theme_dir ) ) {
			return array(
				'error'      => "Theme directory not found: {$theme_slug}. Use list-themes to see available themes.",
				'theme_slug' => $theme_slug,
				'theme_dir'  => $theme_dir,
				'file_count' => 0,
				'files'      => array(),
			);
		}

		$files = self::list_directory_files( $theme_dir );

		return array(
			'theme_slug' => $theme_slug,
			'theme_dir'  => $theme_dir,
			'file_count' => count( $files ),
			'files'      => $files,
		);
	}

	public static function execute_read_theme_file( ?array $input = null ): array {
		$input         = is_array( $input ) ? $input : array();
		$theme_slug    = isset( $input['theme_slug'] ) ? sanitize_file_name( $input['theme_slug'] ) : '';
		$relative_path = isset( $input['relative_path'] ) ? $input['relative_path'] : '';

		if ( empty( $theme_slug ) || empty( $relative_path ) ) {
			return array(
				'error'         => 'theme_slug and relative_path are required.',
				'theme_slug'    => $theme_slug,
				'relative_path' => $relative_path,
				'extension'     => '',
				'size'          => 0,
				'modified'      => '',
				'truncated'     => false,
				'content'       => '',
			);
		}

		$base_dir = trailingslashit( get_theme_root() ) . $theme_slug;
		return array_merge( array( 'theme_slug' => $theme_slug ), self::read_file_from_dir( $base_dir, $relative_path ) );
	}

	public static function execute_get_table_schema( ?array $input = null ): array {
		global $wpdb;
		$input          = is_array( $input ) ? $input : array();
		$requested_name = isset( $input['table_name'] ) ? trim( $input['table_name'] ) : '';
		$table_name     = $requested_name;

		if ( empty( $table_name ) ) {
			return array(
				'error'      => 'table_name is required.',
				'table_name' => '',
				'columns'    => array(),
			);
		}

		// Sanitise table name to alphanumerics and underscores only before any DB use.
		// Prevents backtick-escape injection in the DESCRIBE statement.
		$table_name = preg_replace( '/[^a-zA-Z0-9_]/', '', $table_name );

		if ( empty( $table_name ) ) {
			return array(
				'error'      => "table_name '{$requested_name}' contains no valid characters (only letters, numbers, and underscores are permitted).",
				'table_name' => $requested_name,
				'columns'    => array(),
			);
		}

		// Validate table exists in this database and starts with the site prefix
		// to prevent arbitrary table snooping.
		$db_name = DB_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
				$db_name,
				$table_name
			)
		);

		if ( ! $exists ) {
			return array(
				'error'      => "Table not found: '{$table_name}'. The base table prefix on this install is '{$wpdb->base_prefix}' — check that the table name includes the correct prefix (e.g. '{$wpdb->base_prefix}posts', '{$wpdb->base_prefix}comments').",
				'table_name' => $table_name,
				'columns'    => array(),
			);
		}

		// Ensure table belongs to this WordPress install (must start with base prefix).
		if ( 0 !== strpos( $table_name, $wpdb->base_prefix ) ) {
			return array(
				'error'      => "Access to table '{$table_name}' is not permitted: it does not start with this install's base prefix '{$wpdb->base_prefix}'.",
				'table_name' => $table_name,
				'columns'    => array(),
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$columns_raw = $wpdb->get_results( "DESCRIBE `{$table_name}`", ARRAY_A );

		$columns = array();
		foreach ( (array) $columns_raw as $col ) {
			$columns[] = array(
				'field'   => $col['Field'],
				'type'    => $col['Type'],
				'null'    => $col['Null'],
				'key'     => $col['Key'],
				'default' => $col['Default'],
				'extra'   => $col['Extra'],
			);
		}

		return array(
			'table_name' => $table_name,
			'columns'    => $columns,
		);
	}

	public static function execute_run_select_query( ?array $input = null ): array {
		global $wpdb;
		$input           = is_array( $input ) ? $input : array();
		$requested_table = isset( $input['table'] )    ? trim( $input['table'] )    : '';
		$table           = $requested_table;
		$columns         = isset( $input['columns'] )  ? trim( $input['columns'] )  : '*';
		$where           = isset( $input['where'] )    ? trim( $input['where'] )    : '';
		$order_by        = isset( $input['order_by'] ) ? trim( $input['order_by'] ) : '';
		$group_by        = isset( $input['group_by'] ) ? trim( $input['group_by'] ) : '';
		$limit           = isset( $input['limit'] )    ? absint( $input['limit'] )  : 100;
		$limit           = max( 1, min( 500, $limit ) );
		$offset          = isset( $input['offset'] )   ? absint( $input['offset'] ) : 0;

		if ( empty( $table ) ) {
			return array(
				'error'     => 'table is required.',
				'row_count' => 0,
				'offset'    => $offset,
				'limit'     => $limit,
				'rows'      => array(),
			);
		}

		// Validate table name — alphanumerics and underscores only, must start with base prefix.
		$table = preg_replace( '/[^a-zA-Z0-9_]/', '', $table );
		if ( empty( $table ) ) {
			return array(
				'error'     => "table '{$requested_table}' contains no valid characters (only letters, numbers, and underscores are permitted).",
				'row_count' => 0,
				'offset'    => $offset,
				'limit'     => $limit,
				'rows'      => array(),
			);
		}
		if ( 0 !== strpos( $table, $wpdb->base_prefix ) ) {
			// If the submitted name itself looks like a WordPress table name with a
			// different prefix (e.g. "wp_comments"), strip that prefix before
			// suggesting a corrected name, so the suggestion is "lhero_comments"
			// rather than the nonsensical "lhero_wp_comments".
			$unprefixed = preg_replace( '/^[a-zA-Z0-9]+_/', '', $table, 1 );
			$suggestion = $wpdb->base_prefix . $unprefixed;

			return array(
				'error'     => "Access to table '{$table}' is not permitted: it does not start with this install's base prefix '{$wpdb->base_prefix}'. Did you mean '{$suggestion}'?",
				'row_count' => 0,
				'offset'    => $offset,
				'limit'     => $limit,
				'rows'      => array(),
			);
		}

		// Validate table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
				DB_NAME,
				$table
			)
		);
		if ( ! $exists ) {
			return array(
				'error'     => "Table not found: '{$table}'. The base table prefix on this install is '{$wpdb->base_prefix}' — check that the table name is correct and includes the right prefix (e.g. '{$wpdb->base_prefix}posts', '{$wpdb->base_prefix}comments').",
				'row_count' => 0,
				'offset'    => $offset,
				'limit'     => $limit,
				'rows'      => array(),
			);
		}

		// Sanitise columns — allow alphanumerics, underscores, commas, spaces, and * only.
		if ( '*' !== $columns ) {
			$columns = preg_replace( '/[^a-zA-Z0-9_,\s]/', '', $columns );
			if ( empty( trim( $columns ) ) ) {
				$columns = '*';
			}
		}

		// Build query server-side — FROM never appears in request parameters.
		$sql = "SELECT {$columns} FROM `{$table}`";

		if ( ! empty( $where ) ) {
			// Basic safety: block subqueries and stacked statements in WHERE.
			if ( preg_match( '/;|\/\*|\*\/|--/', $where ) ) {
				return array(
					'error'     => "WHERE clause '{$where}' contains disallowed characters (subqueries, comments, and stacked statements are not permitted).",
					'row_count' => 0,
					'offset'    => $offset,
					'limit'     => $limit,
					'rows'      => array(),
				);
			}
			$sql .= " WHERE {$where}";
		}

		if ( ! empty( $group_by ) ) {
			$group_by = preg_replace( '/[^a-zA-Z0-9_,\s]/', '', $group_by );
			$sql .= " GROUP BY {$group_by}";
		}

		if ( ! empty( $order_by ) ) {
			// Allow alphanumerics, underscores, spaces, commas, and ASC/DESC.
			$order_by = preg_replace( '/[^a-zA-Z0-9_,\s]/', '', $order_by );
			$sql .= " ORDER BY {$order_by}";
		}

		$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		if ( null === $rows ) {
			return array(
				'error'     => 'Query failed: ' . $wpdb->last_error,
				'row_count' => 0,
				'offset'    => $offset,
				'limit'     => $limit,
				'query'     => $sql,
				'rows'      => array(),
			);
		}

		return array(
			'row_count' => count( $rows ),
			'offset'    => $offset,
			'limit'     => $limit,
			'query'     => $sql,
			'rows'      => $rows,
		);
	}

	public static function execute_get_option( ?array $input = null ): array {
		$input       = is_array( $input ) ? $input : array();
		$option_name = isset( $input['option_name'] ) ? trim( $input['option_name'] ) : '';
		$network     = ! empty( $input['network'] );

		if ( empty( $option_name ) ) {
			return array(
				'error'       => 'option_name is required.',
				'option_name' => $option_name,
				'exists'      => false,
				'network'     => $network,
			);
		}

		// Block sensitive option names.
		$blocked_patterns = array(
			'auth_key', 'secure_auth_key', 'logged_in_key', 'nonce_key',
			'auth_salt', 'secure_auth_salt', 'logged_in_salt', 'nonce_salt',
			'db_password', 'db_user', 'db_name', 'db_host',
			'admin_password', 'user_pass',
		);
		foreach ( $blocked_patterns as $blocked ) {
			if ( false !== stripos( $option_name, $blocked ) ) {
				return array(
					'option_name' => $option_name,
					'exists'      => true,
					'network'     => $network,
					'error'       => 'This option name is blocked for security reasons.',
				);
			}
		}

		$not_found_sentinel = '__lh_mcp_not_found__';

		if ( $network ) {
			$value  = get_site_option( $option_name, $not_found_sentinel );
		} else {
			$value  = get_option( $option_name, $not_found_sentinel );
		}

		$exists = ( $value !== $not_found_sentinel );

		$result = array(
			'option_name' => $option_name,
			'exists'      => $exists,
			'network'     => $network,
		);

		if ( $exists ) {
			$result['value'] = $value;
		}

		return $result;
	}

	public static function execute_list_hooks( ?array $input = null ): array {
		global $wp_filter;
		$input  = is_array( $input ) ? $input : array();
		$filter = isset( $input['filter'] ) ? trim( $input['filter'] ) : '';
		$type   = isset( $input['type'] ) ? $input['type'] : 'all';

		// WordPress stores both actions and filters in $wp_filter.
		// We cannot distinguish actions from filters at runtime since WordPress
		// doesn't track which hooks were registered via add_action vs add_filter —
		// both end up in $wp_filter. We surface all hooks and note this in docs.
		$hooks = array_keys( (array) $wp_filter );

		if ( '' !== $filter ) {
			$hooks = array_values( array_filter( $hooks, function( $hook ) use ( $filter ) {
				return false !== stripos( $hook, $filter );
			} ) );
		}

		sort( $hooks );

		return array(
			'hook_count' => count( $hooks ),
			'hooks'      => $hooks,
		);
	}

	public static function execute_get_hook_registrations( ?array $input = null ): array {
		global $wp_filter;
		$input     = is_array( $input ) ? $input : array();
		$hook_name = isset( $input['hook_name'] ) ? trim( $input['hook_name'] ) : '';

		if ( empty( $hook_name ) ) {
			return array(
				'error'          => 'hook_name is required.',
				'hook_name'      => $hook_name,
				'registered'     => false,
				'callback_count' => 0,
				'priorities'     => array(),
			);
		}

		if ( ! isset( $wp_filter[ $hook_name ] ) ) {
			return array(
				'hook_name'      => $hook_name,
				'registered'     => false,
				'callback_count' => 0,
				'priorities'     => array(),
			);
		}

		$hook            = $wp_filter[ $hook_name ];
		$priorities_out  = array();
		$total_callbacks = 0;

		// WP_Hook stores callbacks in ->callbacks[ priority ][ id ] = [ function, accepted_args ].
		$callbacks_map = is_object( $hook ) ? $hook->callbacks : (array) $hook;

		foreach ( $callbacks_map as $priority => $callbacks ) {
			$cbs = array();
			foreach ( $callbacks as $cb_data ) {
				$fn            = $cb_data['function'];
				$accepted_args = (int) $cb_data['accepted_args'];
				$label         = self::describe_callback( $fn );
				$cbs[]         = array(
					'callback'      => $label,
					'accepted_args' => $accepted_args,
				);
				$total_callbacks++;
			}
			$priorities_out[] = array(
				'priority'  => (int) $priority,
				'callbacks' => $cbs,
			);
		}

		usort( $priorities_out, function( $a, $b ) {
			return $a['priority'] - $b['priority'];
		} );

		return array(
			'hook_name'      => $hook_name,
			'registered'     => true,
			'callback_count' => $total_callbacks,
			'priorities'     => $priorities_out,
		);
	}

	public static function execute_get_rewrite_rules( ?array $input = null ): array {
		$input   = is_array( $input ) ? $input : array();
		$site_id = isset( $input['site_id'] ) ? absint( $input['site_id'] ) : get_main_site_id();
		$filter  = isset( $input['filter'] ) ? trim( $input['filter'] ) : '';

		// Switch to the requested site to read its rewrite_rules option.
		$switched = false;
		if ( is_multisite() && get_current_blog_id() !== $site_id ) {
			switch_to_blog( $site_id );
			$switched = true;
		}

		$raw_rules = get_option( 'rewrite_rules', array() );

		if ( $switched ) {
			restore_current_blog();
		}

		if ( ! is_array( $raw_rules ) ) {
			$raw_rules = array();
		}

		$rules = array();
		foreach ( $raw_rules as $regex => $query ) {
			if ( '' !== $filter ) {
				if ( false === stripos( $regex, $filter ) && false === stripos( $query, $filter ) ) {
					continue;
				}
			}
			$rules[] = array(
				'regex' => $regex,
				'query' => $query,
			);
		}

		return array(
			'site_id'    => $site_id,
			'rule_count' => count( $rules ),
			'rules'      => $rules,
		);
	}

	// -------------------------------------------------------------------------
	// Execute callbacks — new abilities (24-29)
	// -------------------------------------------------------------------------

	public static function execute_run_plugin_check( ?array $input = null ): array {
		$input                = is_array( $input ) ? $input : array();
		$plugin_slug          = isset( $input['plugin_slug'] )          ? sanitize_file_name( trim( $input['plugin_slug'] ) ) : '';
		$categories           = isset( $input['categories'] )           ? (array) $input['categories']                        : array();
		$include_experimental = isset( $input['include_experimental'] ) ? (bool) $input['include_experimental']               : false;

		if ( empty( $plugin_slug ) ) {
			return array( 'available' => true, 'plugin_slug' => '', 'error' => 'plugin_slug is required.' );
		}

		// Guard: Plugin Check plugin must be active and its classes available.
		$required_classes = array(
			'WordPress\\Plugin_Check\\Checker\\Abstract_Check_Runner',
			'WordPress\\Plugin_Check\\Checker\\Check_Result',
			'WordPress\\Plugin_Check\\Checker\\Default_Check_Repository',
			'WordPress\\Plugin_Check\\Checker\\Runtime_Environment_Setup',
		);

		foreach ( $required_classes as $class ) {
			if ( ! class_exists( $class ) ) {
				return array(
					'available'   => false,
					'plugin_slug' => $plugin_slug,
					'message'     => 'Plugin Check (PCP) plugin is not active or its classes are not available. Activate the plugin-check plugin to use this ability.',
				);
			}
		}

		// Validate the plugin exists on disk.
		$plugin_dir = trailingslashit( WP_PLUGIN_DIR ) . $plugin_slug;
		if ( ! is_dir( $plugin_dir ) ) {
			return array( 'available' => true, 'plugin_slug' => $plugin_slug, 'error' => "Plugin directory not found: {$plugin_slug}" );
		}

		// Find the plugin's main file (slug/slug.php).
		$plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';
		if ( ! file_exists( trailingslashit( WP_PLUGIN_DIR ) . $plugin_file ) ) {
			// Fall back to scanning for any .php file in the root with a Plugin Name header.
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$all_plugins = get_plugins();
			$plugin_file = '';
			foreach ( $all_plugins as $file => $data ) {
				if ( 0 === strpos( $file, $plugin_slug . '/' ) ) {
					$plugin_file = $file;
					break;
				}
			}
			if ( empty( $plugin_file ) ) {
				return array( 'available' => true, 'plugin_slug' => $plugin_slug, 'error' => "Could not find main plugin file for: {$plugin_slug}" );
			}
		}

		// Build an inline runner subclass. Abstract_Check_Runner::__construct() is final
		// so we cannot override it — instead we use the provided set_*() methods after
		// construction. The abstract param getters return empty defaults; the setters
		// take precedence via internal state.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
		$runner = new class() extends \WordPress\Plugin_Check\Checker\Abstract_Check_Runner {
			public static function is_plugin_check(): bool { return false; }
			protected function get_plugin_param(): string  { return ''; }
			protected function get_check_slugs_param(): array { return array(); }
			protected function get_check_exclude_slugs_param(): array { return array(); }
			protected function get_include_experimental_param(): bool { return false; }
			protected function get_categories_param(): array { return array(); }
			protected function get_slug_param(): string { return ''; }
			protected function get_mode_param(): string { return 'new'; }
		};

		// Set params via the final setter methods — these override the abstract param getters.
		$runner->set_plugin( $plugin_file );
		$runner->set_experimental_flag( $include_experimental );
		if ( ! empty( $categories ) ) {
			$runner->set_categories( $categories );
		}

		try {
			// Do NOT call prepare() — that initialises the runtime environment.
			// run() defaults to TYPE_STATIC when allow_runtime_checks() returns false.
			$result = $runner->run();
		} catch ( Exception $e ) {
			return array(
				'available'   => true,
				'plugin_slug' => $plugin_slug,
				'error'       => 'Plugin Check run failed: ' . $e->getMessage(),
			);
		}

		// Flatten errors and warnings from Check_Result into simple arrays.
		$errors   = array();
		$warnings = array();

		foreach ( $result->get_errors() as $file => $file_errors ) {
			foreach ( $file_errors as $line => $line_errors ) {
				foreach ( $line_errors as $column => $column_errors ) {
					foreach ( $column_errors as $error ) {
						$errors[] = array(
							'file'    => $file,
							'line'    => (int) $line,
							'column'  => (int) $column,
							'code'    => isset( $error['code'] )    ? $error['code']    : '',
							'message' => isset( $error['message'] ) ? $error['message'] : '',
						);
					}
				}
			}
		}

		foreach ( $result->get_warnings() as $file => $file_warnings ) {
			foreach ( $file_warnings as $line => $line_warnings ) {
				foreach ( $line_warnings as $column => $column_warnings ) {
					foreach ( $column_warnings as $warning ) {
						$warnings[] = array(
							'file'    => $file,
							'line'    => (int) $line,
							'column'  => (int) $column,
							'code'    => isset( $warning['code'] )    ? $warning['code']    : '',
							'message' => isset( $warning['message'] ) ? $warning['message'] : '',
						);
					}
				}
			}
		}

		return array(
			'available'     => true,
			'plugin_slug'   => $plugin_slug,
			'error_count'   => $result->get_error_count(),
			'warning_count' => $result->get_warning_count(),
			'errors'        => $errors,
			'warnings'      => $warnings,
		);
	}

	// -------------------------------------------------------------------------
	// Execute callbacks — new abilities (24-28)
	// -------------------------------------------------------------------------

	public static function execute_get_php_ini( ?array $input = null ): array {
		$input     = is_array( $input ) ? $input : array();
		$extension = isset( $input['extension'] ) ? trim( $input['extension'] ) : null;
		$filter    = isset( $input['filter'] )    ? trim( $input['filter'] )    : '';
		$limit     = isset( $input['limit'] )     ? absint( $input['limit'] )   : 100;
		$limit     = max( 1, min( 500, $limit ) );

		$raw = $extension ? @ini_get_all( $extension ) : ini_get_all();

		if ( false === $raw || null === $raw || ( $extension && empty( $raw ) ) ) {
			// ini_get_all() returns false or empty array for unknown/inactive extensions.
			// Fall back to full list with filter when extension scope fails.
			if ( $extension ) {
				$raw    = ini_get_all();
				$filter = $filter ? $filter : $extension;
			} else {
				return array(
					'error'    => 'ini_get_all() failed.',
					'total'    => 0,
					'settings' => array(),
				);
			}
		}

		$settings = array();
		foreach ( $raw as $name => $values ) {
			if ( '' !== $filter && false === stripos( $name, $filter ) ) {
				continue;
			}
			$settings[] = array(
				'name'         => $name,
				'global_value' => isset( $values['global_value'] ) ? (string) $values['global_value'] : null,
				'local_value'  => isset( $values['local_value'] )  ? (string) $values['local_value']  : null,
				'access'       => isset( $values['access'] )       ? (int) $values['access']          : 0,
			);
		}

		return array(
			'total'    => count( $settings ),
			'settings' => array_slice( $settings, 0, $limit ),
		);
	}

	public static function execute_get_network_options( ?array $input = null ): array {
		global $wpdb;
		$input  = is_array( $input ) ? $input : array();
		$filter = isset( $input['filter'] ) ? trim( $input['filter'] ) : '';
		$limit  = isset( $input['limit'] )  ? absint( $input['limit'] ) : 50;
		$limit  = max( 1, min( 500, $limit ) );
		$offset = isset( $input['offset'] ) ? absint( $input['offset'] ) : 0;

		$where = '';
		if ( '' !== $filter ) {
			$where = $wpdb->prepare( 'WHERE meta_key LIKE %s', '%' . $wpdb->esc_like( $filter ) . '%' );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->sitemeta} {$where}" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, LENGTH(meta_value) AS size_bytes FROM {$wpdb->sitemeta} {$where} ORDER BY size_bytes DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			ARRAY_A
		);

		$options = array();
		foreach ( (array) $rows as $row ) {
			$bytes     = (int) $row['size_bytes'];
			$options[] = array(
				'option_name' => $row['meta_key'],
				'size_bytes'  => $bytes,
				'size_human'  => self::format_bytes( $bytes ),
			);
		}

		return array(
			'total'   => $total,
			'limit'   => $limit,
			'offset'  => $offset,
			'options' => $options,
		);
	}

	public static function execute_get_object_cache_info( ?array $input = null ): array {
		global $wp_object_cache;

		$backend   = 'default';
		$connected = false;
		$hits      = 0;
		$misses    = 0;
		$mem_used  = 'N/A';
		$mem_limit = 'N/A';
		$uptime    = 'N/A';
		$raw_stats = array();

		// Detect backend from object cache class name.
		$cache_class = is_object( $wp_object_cache ) ? get_class( $wp_object_cache ) : '';
		if ( false !== stripos( $cache_class, 'redis' ) ) {
			$backend = 'redis';
		} elseif ( false !== stripos( $cache_class, 'memcache' ) ) {
			$backend = 'memcached';
		} elseif ( false !== stripos( $cache_class, 'apcu' ) ) {
			$backend = 'apcu';
		}

		// Try wp_cache_get_stats() first.
		if ( function_exists( 'wp_cache_get_stats' ) ) {
			$stats     = wp_cache_get_stats();
			$raw_stats = is_array( $stats ) ? $stats : array();
		}

		// Try to get Redis-specific info via object cache object.
		if ( 'redis' === $backend && is_object( $wp_object_cache ) ) {
			$connected = method_exists( $wp_object_cache, 'is_connected' )
				? (bool) $wp_object_cache->is_connected()
				: ( property_exists( $wp_object_cache, 'connected' ) ? (bool) $wp_object_cache->connected : true );

			if ( method_exists( $wp_object_cache, 'info' ) ) {
				$info = $wp_object_cache->info();
				if ( is_object( $info ) || is_array( $info ) ) {
					$info = (array) $info;
					$hits      = isset( $info['hits'] )        ? (int) $info['hits']        : 0;
					$misses    = isset( $info['misses'] )      ? (int) $info['misses']      : 0;
					$mem_used  = isset( $info['used_memory'] ) ? $info['used_memory']       : 'N/A';
					$mem_limit = isset( $info['maxmemory'] )   ? $info['maxmemory']         : 'N/A';
					$uptime    = isset( $info['uptime'] )      ? $info['uptime'] . 's'      : 'N/A';
					$raw_stats = $info;
				}
			} else {
				// Fallback: read hits/misses from common property names.
				$hits   = property_exists( $wp_object_cache, 'cache_hits' )   ? (int) $wp_object_cache->cache_hits   : 0;
				$misses = property_exists( $wp_object_cache, 'cache_misses' ) ? (int) $wp_object_cache->cache_misses : 0;
			}
		} else {
			// Non-Redis: read from standard WP_Object_Cache properties.
			$connected = true;
			$hits      = is_object( $wp_object_cache ) && property_exists( $wp_object_cache, 'cache_hits' )   ? (int) $wp_object_cache->cache_hits   : 0;
			$misses    = is_object( $wp_object_cache ) && property_exists( $wp_object_cache, 'cache_misses' ) ? (int) $wp_object_cache->cache_misses : 0;
		}

		$total     = $hits + $misses;
		$hit_ratio = $total > 0 ? round( ( $hits / $total ) * 100, 2 ) . '%' : 'N/A';

		return array(
			'backend'      => $backend,
			'connected'    => $connected,
			'hits'         => $hits,
			'misses'       => $misses,
			'hit_ratio'    => $hit_ratio,
			'memory_used'  => $mem_used,
			'memory_limit' => $mem_limit,
			'uptime'       => $uptime,
			'raw_stats'    => $raw_stats,
		);
	}

	public static function execute_get_action_scheduler_status( ?array $input = null ): array {
		global $wpdb;
		$input       = is_array( $input ) ? $input : array();
		$site_id     = isset( $input['site_id'] )    ? absint( $input['site_id'] )    : get_main_site_id();
		$status      = isset( $input['status'] )     ? trim( $input['status'] )       : '';
		$hook_filter = isset( $input['hook_filter'] ) ? trim( $input['hook_filter'] ) : '';
		$limit       = isset( $input['limit'] )      ? absint( $input['limit'] )      : 50;
		$limit       = max( 1, min( 500, $limit ) );

		// Switch to the requested site.
		$switched = false;
		if ( is_multisite() && get_current_blog_id() !== $site_id ) {
			switch_to_blog( $site_id );
			$switched = true;
		}

		$available     = class_exists( 'ActionScheduler_Store' );
		$status_counts = array();
		$hooks         = array();

		if ( $available ) {
			// Get status counts via API.
			try {
				$store         = ActionScheduler_Store::instance();
				$status_counts = $store->action_counts();
			} catch ( Exception $e ) {
				$available = false;
			}
		}

		if ( $available ) {
			// Get per-hook breakdown via direct query on the site-prefixed table.
			$table      = $wpdb->prefix . 'actionscheduler_actions';
			$conditions = array( '1=1' );

			if ( '' !== $status ) {
				$conditions[] = $wpdb->prepare( 'status = %s', $status );
			}
			if ( '' !== $hook_filter ) {
				$conditions[] = $wpdb->prepare( 'hook LIKE %s', '%' . $wpdb->esc_like( $hook_filter ) . '%' );
			}

			$where = implode( ' AND ', $conditions );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT hook, status, COUNT(*) as count FROM `{$table}` WHERE {$where} GROUP BY hook, status ORDER BY count DESC LIMIT %d",
					$limit
				),
				ARRAY_A
			);

			foreach ( (array) $rows as $row ) {
				$hooks[] = array(
					'hook'   => $row['hook'],
					'status' => $row['status'],
					'count'  => (int) $row['count'],
				);
			}
		}

		if ( $switched ) {
			restore_current_blog();
		}

		return array(
			'site_id'       => $site_id,
			'available'     => $available,
			'status_counts' => $status_counts,
			'hooks'         => $hooks,
		);
	}

	public static function execute_get_site_health( ?array $input = null ): array {
		$input         = is_array( $input ) ? $input : array();
		$status_filter = isset( $input['status_filter'] ) ? trim( $input['status_filter'] ) : '';

		// WP_Site_Health requires several admin functions and a current screen context.
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! function_exists( 'got_mod_rewrite' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		if ( ! function_exists( 'set_current_screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
			require_once ABSPATH . 'wp-admin/includes/screen.php';
		}
		if ( ! function_exists( 'remove_meta_box' ) ) {
			require_once ABSPATH . 'wp-admin/includes/template.php';
		}
		if ( ! function_exists( 'wp_check_php_version' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}
		if ( ! class_exists( 'WP_Site_Health' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
		}
		// Set a screen context — required by some Site Health tests.
		set_current_screen( 'site-health' );

		$site_health = WP_Site_Health::get_instance();
		$tests       = WP_Site_Health::get_tests();
		$results     = array();

		// Run direct (synchronous) tests only.
		if ( isset( $tests['direct'] ) && is_array( $tests['direct'] ) ) {
			foreach ( $tests['direct'] as $test_name => $test ) {
				try {
					if ( is_string( $test['test'] ) && method_exists( $site_health, $test['test'] ) ) {
						$result = $site_health->{$test['test']}();
					} elseif ( is_callable( $test['test'] ) ) {
						$result = call_user_func( $test['test'] );
					} else {
						continue;
					}

					if ( ! is_array( $result ) || empty( $result['status'] ) ) {
						continue;
					}

					$status = $result['status'];
					if ( '' !== $status_filter && $status !== $status_filter ) {
						continue;
					}

					$badge = '';
					if ( isset( $result['badge']['label'] ) ) {
						$badge = $result['badge']['label'];
					} elseif ( isset( $result['badge'] ) && is_string( $result['badge'] ) ) {
						$badge = $result['badge'];
					}

					$results[] = array(
						'test'        => $test_name,
						'label'       => isset( $result['label'] )       ? wp_strip_all_tags( $result['label'] )       : '',
						'status'      => $status,
						'badge'       => $badge,
						'description' => isset( $result['description'] ) ? wp_strip_all_tags( $result['description'] ) : '',
					);
				} catch ( Exception $e ) {
					// Skip tests that throw.
					continue;
				}
			}
		}

		$counts = array( 'good' => 0, 'recommended' => 0, 'critical' => 0 );
		foreach ( $results as $r ) {
			if ( isset( $counts[ $r['status'] ] ) ) {
				$counts[ $r['status'] ]++;
			}
		}

		return array(
			'total'       => count( $results ),
			'good'        => $counts['good'],
			'recommended' => $counts['recommended'],
			'critical'    => $counts['critical'],
			'tests'       => $results,
		);
	}

	// -------------------------------------------------------------------------
	// Shared helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns a sorted list of allowed files inside a directory.
	 */
	private static function list_directory_files( string $base_dir ): array {
		$allowed_ext = array( 'php', 'js', 'json', 'md', 'txt', 'css', 'html', 'xml', 'yaml', 'yml', 'svg' );
		$files       = array();

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base_dir, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}
			$ext = strtolower( pathinfo( $file->getFilename(), PATHINFO_EXTENSION ) );
			if ( ! in_array( $ext, $allowed_ext, true ) ) {
				continue;
			}
			$abs_path = $file->getPathname();
			$relative = ltrim( str_replace( $base_dir, '', $abs_path ), DIRECTORY_SEPARATOR );
			$files[]  = array(
				'path'     => $relative,
				'size'     => (int) $file->getSize(),
				'modified' => gmdate( 'Y-m-d\TH:i:s\Z', $file->getMTime() ),
			);
		}

		usort( $files, function( $a, $b ) {
			return strcmp( $a['path'], $b['path'] );
		} );

		return $files;
	}

	/**
	 * Reads a single file from a base directory, enforcing path traversal and
	 * extension allow-list checks. Returns an array suitable for MCP response.
	 * Callers should array_merge() their own identifying fields (e.g. plugin_slug)
	 * onto the result, including on error paths.
	 */
	private static function read_file_from_dir( string $base_dir, string $relative_path ): array {
		$relative_path = ltrim( $relative_path, '/\\' );

		$error_shape = array(
			'relative_path' => $relative_path,
			'extension'     => '',
			'size'          => 0,
			'modified'      => '',
			'truncated'     => false,
			'content'       => '',
		);

		if ( false !== strpos( $relative_path, '..' ) ) {
			return array_merge( $error_shape, array( 'error' => 'Path traversal is not permitted.' ) );
		}

		$allowed_ext             = array( 'php', 'js', 'json', 'md', 'txt', 'css', 'html', 'xml', 'yaml', 'yml', 'svg' );
		$ext                     = strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) );
		$error_shape['extension'] = $ext;
		if ( ! in_array( $ext, $allowed_ext, true ) ) {
			return array_merge( $error_shape, array( 'error' => "File extension '.{$ext}' is not permitted." ) );
		}

		if ( ! is_dir( $base_dir ) ) {
			return array_merge( $error_shape, array( 'error' => 'Base directory not found.' ) );
		}

		$abs_path       = $base_dir . DIRECTORY_SEPARATOR . $relative_path;
		$real_base_dir  = realpath( $base_dir );
		$real_file      = realpath( $abs_path );

		if ( ! $real_base_dir || ! $real_file ) {
			return array_merge( $error_shape, array( 'error' => 'File not found.' ) );
		}

		if ( 0 !== strpos( $real_file, $real_base_dir . DIRECTORY_SEPARATOR ) ) {
			return array_merge( $error_shape, array( 'error' => 'Path traversal is not permitted.' ) );
		}

		$size      = (int) filesize( $real_file );
		$modified  = gmdate( 'Y-m-d\TH:i:s\Z', filemtime( $real_file ) );
		$max_bytes = 100 * 1024;
		$truncated = $size > $max_bytes;
		$content   = $truncated
			? file_get_contents( $real_file, false, null, 0, $max_bytes ) . "\n\n[TRUNCATED: file is {$size} bytes, only first 100 KB shown]"
			: file_get_contents( $real_file );

		return array(
			'relative_path' => $relative_path,
			'extension'     => $ext,
			'size'          => $size,
			'modified'      => $modified,
			'truncated'     => $truncated,
			'content'       => $content,
		);
	}

	/**
	 * Returns a human-readable label for a callback function/method.
	 */
	private static function describe_callback( $fn ): string {
		if ( is_string( $fn ) ) {
			return $fn;
		}
		if ( $fn instanceof Closure ) {
			$rf = new ReflectionFunction( $fn );
			return 'Closure in ' . $rf->getFileName() . ':' . $rf->getStartLine();
		}
		if ( is_array( $fn ) && 2 === count( $fn ) ) {
			$obj_or_class = $fn[0];
			$method       = $fn[1];
			if ( is_object( $obj_or_class ) ) {
				return get_class( $obj_or_class ) . '::' . $method;
			}
			return $obj_or_class . '::' . $method;
		}
		if ( is_object( $fn ) && method_exists( $fn, '__invoke' ) ) {
			return get_class( $fn ) . '::__invoke';
		}
		return 'unknown';
	}

	/**
	 * Formats bytes to a human-readable string.
	 */
	private static function format_bytes( $bytes ): string {
		$bytes = (int) $bytes;
		if ( $bytes >= 1048576 ) {
			return round( $bytes / 1048576, 2 ) . ' MB';
		}
		if ( $bytes >= 1024 ) {
			return round( $bytes / 1024, 2 ) . ' KB';
		}
		return $bytes . ' B';
	}

	// -------------------------------------------------------------------------
	// Bootstrap
	// -------------------------------------------------------------------------

	public function plugin_init() {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'ability_category_functions' ) );
		add_action( 'wp_abilities_api_init',            array( $this, 'ability_registration_functions' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugin_init' ) );
	}
}

$lh_mcp_developer_abilities_instance = LH_MCP_Developer_Abilities_Plugin::get_instance();

} // end class_exists check
