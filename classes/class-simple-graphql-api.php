<?php
/**
 * The main Simple GraphQL API class.
 *
 * @since  1.0.0
 */
class Simple_GraphQL_API {

	/**
	 * The constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Initialize all the things.
	 *
	 * @since  1.0.0
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
	}

	/**
	 * Register the /graph/ endpoints.
	 *
	 * @since  1.0.0
	 */
	public function register_endpoints() {

		register_rest_route( 'graph/v1', '/any/', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'any_endpoint' ),
		) );

		register_rest_route( 'graph/v1', '/posts/(?P<ids>\d+(,\d+)*)?$', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'posts_endpoint' ),
		) );
	}

	/**
	 * Build and return the API response for multiple resources of any type.
	 *
	 * @since   1.0.0
	 *
	 * @param   object  $request  The request object.
	 * @return  object            The response object.
	 */
	public function any_endpoint( WP_REST_Request $request ) {

		$params   = $request->get_params();
		$response = new stdClass();

		if ( ! empty( $params['posts'] ) ) {

			$response->posts = array();
			$posts           = array();
			$post_ids        = ( is_array( $params['posts'] ) ) ? $params['posts'] : explode( ',', $params['posts'] );

			if ( ! empty( $params['post_fields'] ) ) {
				$post_fields = ( is_array( $params['post_fields'] ) ) ? $params['post_fields'] : explode( ',', $params['post_fields'] );
			} else {
				$post_fields = array();
			}

			foreach ( $post_ids as $post_id ) {
				$post = $this->get_post( $post_id, $post_fields );

				if ( is_object( $post ) ) {
					$posts[] = $post;
				} elseif ( is_string( $post ) ) {
					$response->errors = $post;
				}
			}

			if ( ! empty( $posts ) ) {
				$response->posts = $posts;
			}
		}

		return apply_filters( 'simple_graphql_api_response', $response );
	}

	/**
	 * Build and return the API response for the /posts/ endpoint.
	 *
	 * @since   1.0.0
	 *
	 * @param   int     $id      The post ID.
	 * @param   array   $fields  The fields to include.
	 * @return  object           The response object.
	 */
	public function posts_endpoint( WP_REST_Request $request ) {

		$params = $request->get_params();
		$ids    = ( isset( $params['ids'] ) && is_array( $params['ids'] ) ) ? $params['ids'] : explode( ',', $params['ids'] );
		$fields = ( isset( $params['fields'] ) && is_array( $params['fields'] ) ) ? $params['fields'] : explode( ',', $params['fields'] );

		if ( empty( $ids ) ) {
			return new WP_Error(
				'graphql_no_post_ids',
				__( 'No valid post ids specified', 'simple-graphql-api' ),
				array( 'status' => 404 )
			);
		} elseif ( empty( $fields ) ) {
			return new WP_Error(
				'graphql_no_fields',
				__( 'No valid fields specified', 'simple-graphql-api' ),
				array( 'status' => 404 )
			);
		}

		$posts    = array();
		$response = new stdClass();

		foreach ( $ids as $id ) {
			$post = $this->get_post( $id, $fields );

			if ( $post ) {
				if ( is_object( $post ) ) {
					$posts[] = $post;
				} elseif ( is_string( $post ) ) {
					$response->errors = $post;
				}
			}
		}

		if ( ! empty( $posts ) ) {
			$response->posts = $posts;
		}

		return apply_filters( 'simple_graphql_api_post_endpoint', $response );
	}

	/**
	 * Build and return the API response for a post.
	 *
	 * @since   1.0.0
	 *
	 * @param   int     $id      The post ID.
	 * @param   array   $fields  The fields to include.
	 * @return  object           The response object.
	 */
	public function get_post( $id, $fields = array() ) {

		$post = get_post( (int)$id );

		// Only allow certain post types to be accessed.
		$post_types = apply_filters( 'simple_graphql_api_post_types', array(
			'post',
			'page',
		) );

		// If the post doesn't exist, isn't published, or isn't a valid type,
		// return an error message.
		if ( is_wp_error( $post ) || ! is_object( $post ) ) {
			return sprintf(
				__( 'No post with ID %s found', 'simple-graphql-api' ),
				$id
			);
		} elseif ( 'publish' !== $post->post_status ) {
			return sprintf(
				__( 'Post with ID %s is not published', 'simple-graphql-api' ),
				$id
			);
		} elseif ( ! in_array( $post->post_type, $post_types ) ) {
			return sprintf(
				__( 'Disallowed post type for post with ID %s', 'simple-graphql-api' ),
				$id
			);
		}

		$response = new stdClass();

		// First look for the field on the post object, then look in post meta.
		foreach ( $fields as $field ) {
			if ( isset( $post->{$field} ) ) {
				$response->{$field} = ( isset( $post->{$field} ) ) ? $post->{$field} : null;
			} else {
				$response->{$field} = get_post_meta( $post->ID, $field, true );
			}
		}

		$private_fields = array(
			'post_password',
		);
		$private_fields = apply_filters( 'simple_graphql_api_disallowed_fields', $private_fields );

		// Remove the fields that should not be accessed without authentication.
		foreach ( $private_fields as $private_field ) {
			if ( isset( $response->{$private_field} ) ) {
				$response->{$private_field} = null;
			}
		}

		return apply_filters( 'simple_graphql_api_post', $response );
	}
}