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
 * Description:         Adds a GraphQL-style interface for interacting with the REST API.
 * Version:             1.0.0
 * Author:              Braad Martin
 * Author URI:          http://braadmartin.com
 * License:             GPL-3.0+
 * License URI:         http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:         graphql-api
 * Domain Path:         /languages
 */

add_action( 'rest_api_init', 'graphql_api_register_endpoints' );
/**
 * Register the /graph/ endpoints.
 *
 * @since  1.0.0
 */
function graphql_api_register_endpoints() {

	register_rest_route( 'graph/v1', '/post/(?P<id>\d+)', array(
		'methods' => 'GET',
		'callback' => 'graphql_api_get_post',
		'args' => array(
			'id' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_numeric( $param );
				}
			),
		),
	) );
}

/**
 * Build and return the API response for a post.
 *
 * @since   1.0.0
 *
 * @param   object  $request  The request object.
 * @return  object            The response object.
 */
function graphql_api_get_post( WP_REST_Request $request ) {

	$params   = $request->get_params();
	$post     = get_post( (int)$params['id'] );
	$fields   = explode( ',', $params['fields'] );
	$response = new stdClass();

	// Only allow certain post types to be accessed.
	$post_types = apply_filters( 'graphql_api_post_types', array(
		'post',
		'page',
	) );

	// Check for invalid conditions including no post object, no fields specified,
	// the post not having a status of published, and the post being of a type that
	// isn't enabled, and return an error object.
	if ( empty( $post ) ) {
		return new WP_Error(
			'graphql_no_post_found',
			__( 'No post found with the specified ID.', 'graphql-api' ),
			array( 'status' => 404 )
		);
	} elseif ( 'publish' !== $post->post_status ) {
		return new WP_Error(
			'graphql_access_not_permitted',
			__( 'Sorry, you don\'t have permission to view post ' . $post->ID, 'graphql-api' ),
			array( 'status' => 404 )
		);
	} elseif ( empty( $fields ) ) {
		return new WP_Error(
			'graphql_no_fields',
			__( 'No fields passed in.', 'graphql-api' ),
			array( 'status' => 404 )
		);
	} elseif ( ! in_array( $post->post_type, $post_types ) ) {
		return new WP_Error(
			'graphql_no_post_found',
			__( 'No post found with the specified ID.', 'graphql-api' ),
			array( 'status' => 404 )
		);
	}

	// First look for the field on the post object, then look in post meta.
	foreach ( $fields as $field ) {
		if ( isset( $post->{$field} ) ) {
			$response->{$field} = ( isset( $post->{$field} ) ) ? $post->{$field} : null;
		} else {
			$response->{$field} = get_post_meta( $post->ID, $field, true );
		}
	}

	// Remove the fields that should not be accessed without authentication.
	$private_fields = array(
		'post_password',
	);
	foreach ( $private_fields as $private_field ) {
		if ( isset( $response->{$private_field} ) ) {
			$response->{$private_field} = null;
		}
	}

	return apply_filters( 'graphql_api_post_response', $response );
}