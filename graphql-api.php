<?php
/**
 * GraphQL API
 *
 * @package             GraphQL_API
 * @author              Braad Martin
 * @license             GPL-3.0+
 *
 * @wordpress-plugin
 * Plugin Name:         GraphQL API
 * Plugin URI:          https://github.com/BraadMartin/graphql-api
 * Description:         Adds a GraphQL-style read-only interface for interacting with the REST API.
 * Version:             1.0.0
 * Author:              Braad Martin
 * Author URI:          http://braadmartin.com
 * License:             GPL-3.0+
 * License URI:         http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:         graphql-api
 * Domain Path:         /languages
 */

define( 'GRAPHQL_API_VERSION', '1.0.0' );
define( 'GRAPHQL_API_PATH', plugin_dir_path( __FILE__ ) );
define( 'GRAPHQL_API_URL', plugin_dir_url( __FILE__ ) );

// Include the main plugin class.
require_once GRAPHQL_API_PATH . 'classes/class-graphql-api.php';

global $wp_version;

// Only initialize the main plugin class if the API infrastructure is there.
if ( version_compare( $wp_version, '4.4', '>' ) ) {
	$graphql_api = new GraphQL_API();
	$graphql_api->init();
}