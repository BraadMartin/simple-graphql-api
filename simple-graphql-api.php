<?php
/**
 * Simple GraphQL API
 *
 * @package             Simple GraphQL API
 * @author              Braad Martin
 * @license             GPL-3.0+
 *
 * @wordpress-plugin
 * Plugin Name:         Simple GraphQL API
 * Plugin URI:          https://github.com/BraadMartin/simple-graphql-api
 * Description:         Adds a GraphQL-inpsired interface for the REST API that lets you design custom API responses for the core WordPress resources from both the client and server.
 * Version:             0.8.0
 * Author:              Braad Martin
 * Author URI:          http://braadmartin.com
 * License:             GPL-3.0+
 * License URI:         http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:         simple-graphql-api
 * Domain Path:         /languages
 */

define( 'SIMPLE_GRAPHQL_API_VERSION', '0.8.0' );
define( 'SIMPLE_GRAPHQL_API_PATH', plugin_dir_path( __FILE__ ) );
define( 'SIMPLE_GRAPHQL_API_URL', plugin_dir_url( __FILE__ ) );

global $wp_version;

// Only run the plugin if the API infrastructure is there.
if ( version_compare( $wp_version, '4.4', '>' ) ) {

	require_once SIMPLE_GRAPHQL_API_PATH . 'classes/class-simple-graphql-api.php';

	add_action( 'plugins_loaded', 'simple_graphql_api_load_plugin_textdomain' );

	$simple_graphql_api = new Simple_GraphQL_API();
	$simple_graphql_api->init();
}

/**
 * Load translation files.
 *
 * @since  0.8.0
 */
function simple_graphql_api_load_plugin_textdomain() {
	load_plugin_textdomain( 'simple-graphql-api', FALSE, SIMPLE_GRAPHQL_API_PATH . 'languages/' );
}