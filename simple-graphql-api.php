<?php
/**
 * Simple GraphQL API
 *
 * @package             Simple GraphQL_API
 * @author              Braad Martin
 * @license             GPL-3.0+
 *
 * @wordpress-plugin
 * Plugin Name:         Simple GraphQL API
 * Plugin URI:          https://github.com/BraadMartin/simple-graphql-api
 * Description:         Adds a GraphQL-style read-only interface for interacting with the REST API.
 * Version:             1.0.0
 * Author:              Braad Martin
 * Author URI:          http://braadmartin.com
 * License:             GPL-3.0+
 * License URI:         http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:         simple-graphql-api
 * Domain Path:         /languages
 */

define( 'SIMPLE_GRAPHQL_API_VERSION', '1.0.0' );
define( 'SIMPLE_GRAPHQL_API_PATH', plugin_dir_path( __FILE__ ) );
define( 'SIMPLE_GRAPHQL_API_URL', plugin_dir_url( __FILE__ ) );

// Include the main plugin class.
require_once SIMPLE_GRAPHQL_API_PATH . 'classes/class-simple-graphql-api.php';

global $wp_version;

// Only initialize the main plugin class if the API infrastructure is there.
if ( version_compare( $wp_version, '4.4', '>' ) ) {
	$simple_graphql_api = new Simple_GraphQL_API();
	$simple_graphql_api->init();
}