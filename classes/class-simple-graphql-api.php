<?php
/**
 * The main Simple GraphQL API class.
 *
 * @since  0.8.0
 */
class Simple_GraphQL_API {

	/**
	 * Allowed Post Types.
	 *
	 * This will hold post types that have show_in_rest set to true.
	 *
	 * @since  0.8.0
	 *
	 * @var    array
	 */
	private $allowed_post_types;

	/**
	 * Safe Meta Mode.
	 *
	 * Setting this to false will allow meta keys starting with _ to be
	 * included in the API response.
	 *
	 * @since  0.8.0
	 *
	 * @var    bool
	 */
	private $safe_meta_mode;

	/**
	 * The constructor.
	 *
	 * @since  0.8.0
	 */
	public function __construct() {
		// Silence is golden...
	}

	/**
	 * Initialize all the things.
	 *
	 * @since  0.8.0
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
	}

	/**
	 * Register the /graph/ endpoints.
	 *
	 * @since  0.8.0
	 */
	public function register_endpoints() {

		// Allow the URL base to be customized.
		$base = apply_filters( 'simple_graphql_api_url_base', 'graph/v1' );

		// Allow the args passed to register_rest_route to be customized.
		$any_args = apply_filters( 'simple_graphql_api_any_endpoint_args', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'any_endpoint' ),
		) );
		$posts_args = apply_filters( 'simple_graphql_api_posts_endpoint_args', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'posts_endpoint' ),
		) );
		$terms_args = apply_filters( 'simple_graphql_api_terms_endpoint_args', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'terms_endpoint' ),
		) );
		$comments_args = apply_filters( 'simple_graphql_api_comments_endpoint_args', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'comments_endpoint' ),
		) );

		// Register the endpoints.
		register_rest_route( $base, '/any/', $any_args );
		register_rest_route( $base, '/posts/(?P<ids>\w+(,\w+)*)?$', $posts_args );
		register_rest_route( $base, '/terms/(?P<ids>\w+(,\w+)*)?$', $terms_args );
		register_rest_route( $base, '/comments/(?P<ids>\w+(,\w+)*)?$', $comments_args );
	}

	/**
	 * Build and return the API response for multiple resources of any type.
	 *
	 * @since   0.8.0
	 *
	 * @param   object  $request  The request object.
	 * @return  object            The response object.
	 */
	public function any_endpoint( WP_REST_Request $request ) {

		$params   = $request->get_params();
		$response = new stdClass();

		// Process a request for posts if one is included.
		if ( ! empty( $params['posts'] ) ) {

			$response->posts = array();
			$posts           = array();
			$post_ids        = ( is_array( $params['posts'] ) ) ? $params['posts'] : explode( ',', $params['posts'] );

			if ( ! empty( $params['post_fields'] ) ) {
				$post_fields = ( is_array( $params['post_fields'] ) ) ? $params['post_fields'] : explode( ',', $params['post_fields'] );

				// If 'default' is present as a field, add the default fields.
				if ( in_array( 'default', $post_fields ) ) {

					// Remove the 'default' field from $post_fields.
					$post_fields = array_diff( $post_fields, array( 'default' ) );

					// Merge in the default fields.
					$post_fields = array_merge( $this->get_default_post_fields( $request ), $post_fields );
				}
			} else {
				$post_fields = array();
			}

			foreach ( $post_ids as $post_id ) {
				$post = $this->get_post( (int)$post_id, $post_fields );

				if ( is_object( $post ) ) {
					$posts[] = $post;
				} elseif ( is_string( $post ) ) {
					if ( isset( $response->errors ) && is_array( $response->errors ) ) {
						$response->errors[] = $post;
					} else {
						$response->errors = array();
						$response->errors[] = $post;
					}
				}
			}

			if ( ! empty( $posts ) ) {
				$response->posts = $posts;
			}
		}

		// Process a request for terms if one is included.
		if ( ! empty( $params['terms'] ) ) {

			$response->terms = array();
			$terms           = array();
			$term_ids        = ( is_array( $params['terms'] ) ) ? $params['terms'] : explode( ',', $params['terms'] );

			if ( ! empty( $params['term_fields'] ) ) {
				$term_fields = ( is_array( $params['term_fields'] ) ) ? $params['term_fields'] : explode( ',', $params['term_fields'] );

				// If 'default' is present as a field, add the default fields.
				if ( in_array( 'default', $term_fields ) ) {

					// Remove the 'default' field from $term_fields.
					$term_fields = array_diff( $term_fields, array( 'default' ) );

					// Merge in the default fields.
					$term_fields = array_merge( $this->get_default_term_fields( $request ), $term_fields );
				}
			} else {
				$term_fields = array();
			}

			foreach ( $term_ids as $term_id ) {
				$term = $this->get_term( (int)$term_id, $term_fields );

				if ( is_object( $term ) ) {
					$terms[] = $term;
				} elseif ( is_string( $term ) ) {
					if ( isset( $response->errors ) && is_array( $response->errors ) ) {
						$response->errors[] = $term;
					} else {
						$response->errors = array();
						$response->errors[] = $term;
					}
				}
			}

			if ( ! empty( $terms ) ) {
				$response->terms = $terms;
			}
		}

		// Process a request for comments if one is included.
		if ( ! empty( $params['comments'] ) ) {

			$response->comments = array();
			$comments           = array();
			$comment_ids        = ( is_array( $params['comments'] ) ) ? $params['comments'] : explode( ',', $params['comments'] );

			if ( ! empty( $params['comment_fields'] ) ) {
				$comment_fields = ( is_array( $params['comment_fields'] ) ) ? $params['comment_fields'] : explode( ',', $params['comment_fields'] );

				// If 'default' is present as a field, add the default fields.
				if ( in_array( 'default', $comment_fields ) ) {

					// Remove the 'default' field from $comment_fields.
					$comment_fields = array_diff( $comment_fields, array( 'default' ) );

					// Merge in the default fields.
					$comment_fields = array_merge( $this->get_default_comment_fields( $request ), $comment_fields );
				}
			} else {
				$comment_fields = array();
			}

			foreach ( $comment_ids as $comment_id ) {
				$comment = $this->get_comment( (int)$comment_id, $comment_fields );

				if ( is_object( $comment ) ) {
					$comments[] = $comment;
				} elseif ( is_string( $comment ) ) {
					if ( isset( $response->errors ) && is_array( $response->errors ) ) {
						$response->errors[] = $comment;
					} else {
						$response->errors = array();
						$response->errors[] = $comment;
					}
				}
			}

			if ( ! empty( $comments ) ) {
				$response->comments = $comments;
			}
		}

		// Make it into a proper WP_REST_Response object before passing it to our filter.
		$response = rest_ensure_response( $response );

		return apply_filters( 'simple_graphql_api_response', $response, $request, $params );
	}

	/**
	 * Build and return the API response for the /posts/ endpoint.
	 *
	 * @since   0.8.0
	 *
	 * @param   object  $request  The request object.
	 * @return  object            The response object.
	 */
	public function posts_endpoint( WP_REST_Request $request ) {

		$params         = $request->get_params();
		$ids            = array();
		$filters        = array();
		$fields         = array();
		$term_fields    = array();
		$comment_fields = array();

		if ( isset( $params['ids'] ) ) {
			$ids = ( is_array( $params['ids'] ) ) ? $params['ids'] : explode( ',', $params['ids'] );
		}
		if ( isset( $params['filter'] ) && is_array( $params['filter'] ) ) {
			$filters = $params['filter'];
		}
		if ( isset( $params['fields'] ) ) {
			$fields = ( is_array( $params['fields'] ) ) ? $params['fields'] : explode( ',', $params['fields'] );

			// If 'default' is present as a field, add the default fields.
			if ( in_array( 'default', $fields ) ) {

				// Remove the 'default' field from $fields.
				$fields = array_diff( $fields, array( 'default' ) );

				// Merge in the default fields.
				$fields = array_merge( $this->get_default_post_fields( $request ), $fields );
			}
		}
		if ( isset( $params['term_fields'] ) ) {
			$term_fields = ( is_array( $params['term_fields'] ) ) ? $params['term_fields'] : explode( ',', $params['term_fields'] );

			// If 'default' is present as a field, add the default fields.
			if ( in_array( 'default', $term_fields ) ) {

				// Remove the 'default' field from $term_fields.
				$term_fields = array_diff( $term_fields, array( 'default' ) );

				// Merge in the default fields.
				$term_fields = array_merge( $this->get_default_term_fields( $request ), $term_fields );
			}
		}
		if ( isset( $params['comment_fields'] ) ) {
			$comment_fields = ( is_array( $params['comment_fields'] ) ) ? $params['comment_fields'] : explode( ',', $params['comment_fields'] );

			// If 'default' is present as a field, add the default fields.
			if ( in_array( 'default', $comment_fields ) ) {

				// Remove the 'default' field from $comment_fields.
				$comment_fields = array_diff( $comment_fields, array( 'default' ) );

				// Merge in the default fields.
				$comment_fields = array_merge( $this->get_default_comment_fields( $request ), $comment_fields );
			}
		}

		if ( empty( $ids ) ) {
			return new WP_Error(
				'graphql_no_post_ids',
				__( 'No valid post ids specified', 'simple-graphql-api' ),
				array( 'status' => 404 )
			);
		} elseif ( empty( $fields ) ) {
			return new WP_Error(
				'graphql_no_post_fields',
				__( 'No valid post fields specified', 'simple-graphql-api' ),
				array( 'status' => 404 )
			);
		}

		$posts    = array();
		$terms    = array();
		$comments = array();
		$response = new stdClass();

		// If the keyword 'query' is in the IDs array and a 'filter' param is passed,
		// make a specific query add the matching post IDs to the array.
		if ( in_array( 'query', $ids ) ) {

			// Remove the 'query' keyword from the IDs array.
			$ids = array_diff( $ids, array( 'query' ) );

			// Only make a query if at least one filter was passed.
			if ( ! empty( $filters ) ) {

				// Make the query and get the matching IDs.
				$query_ids = $this->get_specific_post_ids( $filters, $params, $request );

				// If we have matching IDs, merge them in with any other passed IDs.
				if ( ! empty( $query_ids ) ) {
					$ids = array_merge( $ids, $query_ids );
				}
			}
		}

		// Remove any duplicate IDs before making our queries.
		$ids = array_unique( $ids );

		foreach ( $ids as $id ) {

			$post = $this->get_post( (int)$id, $fields );

			if ( $post ) {
				if ( is_object( $post ) ) {
					$posts[] = $post;

					// Add any terms if terms was a requested field and term_fields was a valid query param.
					if ( ! empty( $post->terms ) && ! empty( $term_fields ) ) {
						$term_ids = explode( ',', $post->terms );
						foreach ( $term_ids as $term_id ) {
							$term = $this->get_term( $term_id, $term_fields );

							if ( is_object( $term ) ) {
								$terms[] = $term;
							}
						}
					}

					// Add any comments if comments was a requested field and comment_fields was a valid query param.
					if ( ! empty( $post->comments ) && ! empty( $comment_fields ) ) {
						$comment_ids = explode( ',', $post->comments );
						foreach ( $comment_ids as $comment_id ) {
							$comment = $this->get_comment( $comment_id, $comment_fields );

							if ( is_object( $comment ) ) {
								$comments[] = $comment;
							}
						}
					}

				} elseif ( is_string( $post ) ) {
					if ( isset( $response->errors ) && is_array( $response->errors ) ) {
						$response->errors[] = $post;
					} else {
						$response->errors = array();
						$response->errors[] = $post;
					}
				}
			}
		}

		if ( ! empty( $posts ) ) {
			$response->posts = $posts;
		}
		if ( ! empty( $terms ) ) {
			$response->terms = $terms;
		}
		if ( ! empty( $comments ) ) {
			$response->comments = $comments;
		}

		// Make it into a proper WP_REST_Response object before passing it to our filter.
		$response = rest_ensure_response( $response );

		return apply_filters( 'simple_graphql_api_response', $response, $request, $params );
	}

	/**
	 * Build and return the API response for the /terms/ endpoint.
	 *
	 * @since   0.8.0
	 *
	 * @param   object  $request  The request object.
	 * @return  object            The response object.
	 */
	public function terms_endpoint( WP_REST_Request $request ) {

		$params = $request->get_params();
		$ids    = array();
		$fields = array();

		if ( isset( $params['ids'] ) ) {
			$ids = ( is_array( $params['ids'] ) ) ? $params['ids'] : explode( ',', $params['ids'] );
		}
		if ( isset( $params['fields'] ) ) {
			$fields = ( is_array( $params['fields'] ) ) ? $params['fields'] : explode( ',', $params['fields'] );
		}

		// If 'default' is present as a field, add the default fields.
		if ( in_array( 'default', $fields ) ) {

			// Remove the 'default' field from $fields.
			$fields = array_diff( $fields, array( 'default' ) );

			// Merge in the default fields.
			$fields = array_merge( $this->get_default_term_fields( $request ), $fields );
		}

		if ( empty( $ids ) ) {
			return new WP_Error(
				'graphql_no_term_ids',
				__( 'No valid term ids specified', 'simple-graphql-api' ),
				array( 'status' => 404 )
			);
		} elseif ( empty( $fields ) ) {
			return new WP_Error(
				'graphql_no_term_fields',
				__( 'No valid term fields specified', 'simple-graphql-api' ),
				array( 'status' => 404 )
			);
		}

		$posts    = array();
		$response = new stdClass();

		foreach ( $ids as $id ) {
			$term = $this->get_term( (int)$id, $fields );

			if ( $term ) {
				if ( is_object( $term ) ) {
					$terms[] = $term;
				} elseif ( is_string( $term ) ) {
					if ( isset( $response->errors ) && is_array( $response->errors ) ) {
						$response->errors[] = $term;
					} else {
						$response->errors = array();
						$response->errors[] = $term;
					}
				}
			}
		}

		if ( ! empty( $terms ) ) {
			$response->terms = $terms;
		}

		// Make it into a proper WP_REST_Response object before passing it to our filter.
		$response = rest_ensure_response( $response );

		return apply_filters( 'simple_graphql_api_response', $response, $request, $params );
	}

	/**
	 * Build and return the API response for the /comments/ endpoint.
	 *
	 * @since   0.8.0
	 *
	 * @param   object  $request  The request object.
	 * @return  object            The response object.
	 */
	public function comments_endpoint( WP_REST_Request $request ) {

		$params = $request->get_params();
		$ids    = array();
		$fields = array();

		if ( isset( $params['ids'] ) ) {
			$ids = ( is_array( $params['ids'] ) ) ? $params['ids'] : explode( ',', $params['ids'] );
		}
		if ( isset( $params['fields'] ) ) {
			$fields = ( is_array( $params['fields'] ) ) ? $params['fields'] : explode( ',', $params['fields'] );
		}

		// If 'default' is present as a field, add the default fields.
		if ( in_array( 'default', $fields ) ) {

			// Remove the 'default' field from $fields.
			$fields = array_diff( $fields, array( 'default' ) );

			// Merge in the default fields.
			$fields = array_merge( $this->get_default_comment_fields( $request ), $fields );
		}

		if ( empty( $ids ) ) {
			return new WP_Error(
				'graphql_no_comment_ids',
				__( 'No valid comment ids specified', 'simple-graphql-api' ),
				array( 'status' => 404 )
			);
		} elseif ( empty( $fields ) ) {
			return new WP_Error(
				'graphql_no_comment_fields',
				__( 'No valid comment fields specified', 'simple-graphql-api' ),
				array( 'status' => 404 )
			);
		}

		$comments = array();
		$response = new stdClass();

		foreach ( $ids as $id ) {
			$comment = $this->get_comment( (int)$id, $fields );

			if ( $comment ) {
				if ( is_object( $comment ) ) {
					$comments[] = $comment;
				} elseif ( is_string( $comment ) ) {
					if ( isset( $response->errors ) && is_array( $response->errors ) ) {
						$response->errors[] = $comment;
					} else {
						$response->errors = array();
						$response->errors[] = $comment;
					}
				}
			}
		}

		if ( ! empty( $comments ) ) {
			$response->comments = $comments;
		}

		// Make it into a proper WP_REST_Response object before passing it to our filter.
		$response = rest_ensure_response( $response );

		return apply_filters( 'simple_graphql_api_response', $response, $request, $params );
	}

	/**
	 * Check whether safe meta mode is enabled.
	 *
	 * @since   0.8.0
	 *
	 * @return  bool  Whether safe mode is enabled.
	 */
	public function check_safe_meta_mode() {

		if ( isset( $this->safe_meta_mode ) ) {
			return $this->safe_meta_mode;
		}

		/**
		 * I'll assume that if you're reading this you know what you're doing.
		 *
		 * Return false on this filter to allow all meta, including keys that start
		 * with _, into the response. Any fields that are specifically marked as
		 * private will still get stripped out.
		 */
		$this->safe_meta_mode = apply_filters( 'simple_graphql_api_safe_meta_mode', true );

		return $this->safe_meta_mode;
	}

	/**
	 * Return an array of post IDs that match a query made using the passed in filters.
	 *
	 * @since   0.8.0
	 *
	 * @param   array   $filters  The filter params.
	 * @param   array   $params   The request params.
	 * @param   object  $request  The request object.
	 * @return  array             The array of post IDs.
	 */
	public function get_specific_post_ids( $filters, $params, $request ) {

		// Allow custom logic to be used here before we do anything expensive.
		$custom_query = apply_filters( 'simple_graphql_api_query_post_ids', array(), $filters, $params, $request );

		if ( ! empty( $custom_query ) ) {
			return $custom_query;
		}

		$args = array();
		$posts = array();

		$allowed_args = array(
			'author',
			'author__in',
			'author__not_in',
			'author_name',
			'cat',
			'category__and',
			'category__in',
			'category__not_in',
			'category_name',
			'day',
			'hour',
			'ignore_sticky_posts',
			'm',
			'menu_order',
			'meta_compare',
			'meta_key',
			'meta_value',
			'meta_value_num',
			'minute',
			'monthnum',
			'name',
			'nopaging',
			'offset',
			'order',
			'orderby',
			'p',
			'page',
			'paged',
			'pagename',
			'post__in',
			'post__not_in',
			'post_name__in',
			'post_parent',
			'post_parent__in',
			'post_parent__not_in',
			'post_type',
			'posts_per_page',
			's',
			'second',
			'tag',
			'tag__and',
			'tag__in',
			'tag__not_in',
			'tag_id',
			'tag_slug__and',
			'tag_slug__in',
			'w',
			'year',
		);

		// Allow the allowed query args to be filtered.
		$allowed_args = apply_filters( 'simple_graphql_api_allowed_query_args', $allowed_args, $filters, $params, $request );

		// Copy only args that are allowed into the $args array.
		foreach ( $allowed_args as $allowed_arg ) {
			if ( array_key_exists( $allowed_arg, $filters ) ) {
				$args[ $allowed_arg ] = $filters[ $allowed_arg ];
			}
		}

		if ( ! empty( $args ) ) {
			$query = new WP_Query();
			$query_result = $query->query( $args );

			foreach ( $query_result as $post ) {
				if ( ! $this->check_read_permission( $post ) ) {
					continue;
				}

				$posts[] = $post->ID;
			}
		}

		return $posts;
	}

	/**
	 * Build and return the API response for a post.
	 *
	 * @since   0.8.0
	 *
	 * @param   int     $id              The post ID.
	 * @param   array   $fields          The fields to include.
	 * @return  object                   The response object.
	 */
	public function get_post( $id, $fields = array() ) {

		$post = get_post( (int)$id );

		// If the post doesn't exist, isn't published, or isn't a valid type,
		// return an error message.
		if ( is_wp_error( $post ) || ! is_object( $post ) ) {
			return sprintf(
				__( 'No post with ID %s found', 'simple-graphql-api' ),
				esc_attr( $id )
			);
		} elseif ( 'publish' !== $post->post_status ) {
			return sprintf(
				__( 'Post with ID %s is not published', 'simple-graphql-api' ),
				esc_attr( $id )
			);
		} elseif ( ! $this->check_read_permission( $post ) ) {
			return sprintf(
				__( 'Permission denied for post with ID %s', 'simple-graphql-api' ),
				esc_attr( $id )
			);
		}

		$response = new stdClass();

		// First look for the field on the post object, then look in post meta.
		foreach ( $fields as $field ) {
			if ( isset( $post->{$field} ) ) {

				// First check whether the field is marked as private with _ as the first character,
				// then if we are returning post_title or post_content, run it through the right
				// filters and return both a raw and rendered version to match the REST API.
				if ( '_' === substr( $field, 0, 1 ) ) {

					// We have a potentially private field, so only return it if safe meta mode is off.
					if ( $this->check_safe_meta_mode() ) {
						$response->{$field} = __( 'Sorry, this key is marked as private. Please see the readme for more information.', 'simple-graphql-api' );
					} else {
						$response->{$field} = $post->{$field};
					}

				} elseif ( 'post_content' === $field ) {
					$response->post_content             = array();
					$response->post_content['raw']      = $post->post_content;
					$response->post_content['rendered'] = apply_filters( 'the_content', $post->post_content );
				} elseif ( 'post_title' === $field ) {
					$response->post_title             = array();
					$response->post_title['raw']      = $post->post_title;
					$response->post_title['rendered'] = get_the_title( $post->ID );
				} else {
					$response->{$field} = ( isset( $post->{$field} ) ) ? $post->{$field} : null;
				}

			} elseif ( 'terms' === $field ) {

				// If 'terms' was specified as a field, generate a comma-separated string
				// that contains the IDs for any terms attached to the post.
				$response->terms = $this->get_term_ids_for_post( $post );

			} elseif ( 'comments' === $field ) {

				// If 'comments' was specified as a field, generate a comma-separated string
				// that contains the IDs for any comments attached to the post.
				$response->comments = $this->get_comment_ids_for_post( $post );

			} else {

				/* This logic only runs if the meta key wasn't already found on the $post object */

				// If the meta key starts with an underscore and safe meta mode is enabled,
				// return a message, otherwise let it through.
				if ( '_' === substr( $field, 0, 1 ) && $this->check_safe_meta_mode() ) {
					$response->{$field} = __( 'Sorry, this key is marked as private. Please see the readme for more information.', 'simple-graphql-api' );
				} else {
					$meta = get_post_meta( $post->ID, $field, true );
					$response->{$field} = ( ! empty( $meta ) ) ? $meta : '';
				}
			}
		}

		$private_fields = $this->get_private_fields();

		// Remove the fields that should not be accessed without authentication.
		foreach ( $private_fields as $private_field ) {
			if ( isset( $response->{$private_field} ) ) {
				$response->{$private_field} = null;
			}
		}

		return apply_filters( 'simple_graphql_api_post', $response, $post, $id );
	}

	/**
	 * Build and return the API response for a term.
	 *
	 * @since   0.8.0
	 *
	 * @param   int     $id      The term ID.
	 * @param   array   $fields  The fields to include.
	 * @return  object           The response object.
	 */
	public function get_term( $id, $fields = array() ) {

		$term = get_term( $id );

		// If the term doesn't exist, return an error.
		if ( is_wp_error( $term ) || ! is_object( $term ) ) {
			return sprintf(
				__( 'No term with ID %s found', 'simple-graphql-api' ),
				esc_attr( $id )
			);
		}

		$response = new stdClass();

		// First look for the field on the term object, then look in term meta.
		foreach ( $fields as $field ) {
			if ( isset( $term->{$field} ) ) {
				$response->{$field} = ( isset( $term->{$field} ) ) ? $term->{$field} : null;
			} else {
				$meta = get_term_meta( $id, $field, true );
				$response->{$field} = ( ! empty( $meta ) ) ? $meta : '';
			}
		}

		$private_fields = $this->get_private_fields();

		// Remove the fields that should not be accessed without authentication.
		foreach ( $private_fields as $private_field ) {
			if ( isset( $response->{$private_field} ) ) {
				$response->{$private_field} = null;
			}
		}

		return apply_filters( 'simple_graphql_api_term', $response, $term, $id );
	}

	/**
	 * Build and return a comma-separated list of term IDs for terms attached to a post.
	 *
	 * @since   0.8.0
	 *
	 * @param   int|object  $post  The post ID or object.
	 * @return  string             The string of term IDs or empty.
	 */
	public function get_term_ids_for_post( $post ) {

		// Handle $post being an ID or a post object.
		if ( is_object( $post ) ) {
			$post_id = $post->ID;
		} elseif ( is_int( $post ) ) {
			$post_id = $post;
		} else {
			return '';
		}

		$taxonomies = get_object_taxonomies( $post );

		// Return empty string if the post has no taxonomies.
		if ( empty( $taxonomies ) ) {
			return '';
		}

		$terms_array = array();

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post->ID, $taxonomy );

			if ( empty( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$terms_array[] = $term->term_id;
			}
		}

		// Return empty string if the post has no terms.
		if ( empty( $terms_array ) ) {
			return '';
		}

		sort( $terms_array );

		// Return a comma-separated string of the terms.
		return implode( ',', $terms_array );
	}

	/**
	 * Build and return the API response for a comment.
	 *
	 * @since   0.8.0
	 *
	 * @param   int     $id      The comment ID.
	 * @param   array   $fields  The fields to include.
	 * @return  object           The response object.
	 */
	public function get_comment( $id, $fields = array() ) {

		$comment_args = array(
			'comment__in' => $id,
			'status'      => '1',
		);

		$comments = get_comments( $comment_args );
		$comment = ( is_array( $comments ) ) ? $comments[0] : false;

		// Only allow the Core comment type to be accessed (the absence of a comment type is
		// the Core comment type)
		$comment_types = apply_filters( 'simple_graphql_api_comment_types', array(
			'',
		) );

		// If the comment doesn't exist, hasn't been approved, or isn't a valid type,
		// return an error message.
		if ( is_wp_error( $comment ) || ! is_object( $comment ) ) {
			return sprintf(
				__( 'No approved comment with ID %s found', 'simple-graphql-api' ),
				esc_attr( $id )
			);
		} elseif ( '1' !== $comment->comment_approved ) {
			return sprintf(
				__( 'Comment with ID %s has not been approved', 'simple-graphql-api' ),
				esc_attr( $id )
			);
		} elseif ( ! in_array( $comment->comment_type, $comment_types ) ) {
			return sprintf(
				__( 'Disallowed comment type for comment with ID %s', 'simple-graphql-api' ),
				esc_attr( $id )
			);
		}

		$response = new stdClass();

		// First look for the field on the comment object, then look in comment meta.
		foreach ( $fields as $field ) {
			if ( isset( $comment->{$field} ) ) {

				// If we're returning the comment_content, run it through the right filters.
				if ( 'comment_content' === $field ) {
					$response->comment_content             = array();
					$response->comment_content['raw']      = $comment->comment_content;
					$response->comment_content['rendered'] = apply_filters( 'comment_text', $comment->comment_content, $comment );
				} else {
					$response->{$field} = ( isset( $comment->{$field} ) ) ? $comment->{$field} : null;
				}

			} else {
				$meta = get_comment_meta( $comment->ID, $field, true );
				$response->{$field} = ( ! empty( $meta ) ) ? $meta : '';
			}
		}

		$private_fields = $this->get_private_fields();

		// Remove the fields that should not be accessed without authentication.
		foreach ( $private_fields as $private_field ) {
			if ( isset( $response->{$private_field} ) ) {
				$response->{$private_field} = null;
			}
		}

		return apply_filters( 'simple_graphql_api_comment', $response, $comment, $id );
	}

	/**
	 * Build and return a comma-separated list of comment IDs for comments attached to a post.
	 *
	 * @since   0.8.0
	 *
	 * @param   int|object  $post  The post ID or object.
	 * @return  string             The string of comment IDs or empty.
	 */
	public function get_comment_ids_for_post( $post ) {

		// Handle $post being an ID or a post object.
		if ( is_object( $post ) ) {
			$post_id = $post->ID;
		} elseif ( is_int( $post ) ) {
			$post_id = $post;
		} else {
			return '';
		}

		$comments = get_approved_comments( $post_id );

		// Return empty string if the post has no comments.
		if ( empty( $comments ) ) {
			return '';
		}

		$comments_array = array();

		foreach ( $comments as $comment ) {
			$comments_array[] = $comment->comment_ID;
		}

		sort( $comments_array );

		// Return a comma-separated string of comment IDs.
		return implode( ',', $comments_array );
	}

	/**
	 * Return an array of fields that are private and should only be accessed using
	 * authenticated requests.
	 *
	 * @since   0.8.0
	 *
	 * @return  array  The private fields.
	 */
	public function get_private_fields() {

		$private_fields = array(
			'post_password',
			'_edit_last',
			'_edit_lock',
			'comment_author_email',
			'comment_author_IP',
			'comment_agent',
			'user_id',
		);

		return apply_filters( 'simple_graphql_api_private_fields', $private_fields );
	}

	/**
	 * Check permissions on a post to determine if we can read it without authentication.
	 *
	 * This was taken from class-wp-rest-posts-controller.php in version 2 beta 11 of the REST API plugin,
	 * and modified slightly to remove checks related to updating a post. Correctly handles posts with
	 * the inherit status.
	 *
	 * @param   object   $post  Post object.
	 * @return  boolean         Can we read it?
	 */
	public function check_read_permission( $post ) {
		if ( ! empty( $post->post_password ) ) {
			return false;
		}

		$post_types = $this->get_allowed_post_types();
		if ( ! in_array( $post->post_type, $post_types ) ) {
			return false;
		}

		// Can we read the post?
		if ( 'publish' === $post->post_status || current_user_can( $post_type->cap->read_post, $post->ID ) ) {
			return true;
		}

		$post_status_obj = get_post_status_object( $post->post_status );
		if ( $post_status_obj && $post_status_obj->public ) {
			return true;
		}

		// Can we read the parent if we're inheriting?
		if ( 'inherit' === $post->post_status && $post->post_parent > 0 ) {
			$parent = get_post( $post->post_parent );
			return $this->check_read_permission( $parent );
		}

		// If we don't have a parent, but the status is set to inherit, assume
		// it's published (as per get_post_status()).
		if ( 'inherit' === $post->post_status ) {
			return true;
		}

		return false;
	}

	/**
	 * Return an array of allowed post types.
	 *
	 * @since   0.8.0
	 *
	 * @return  array  The array of post types.
	 */
	public function get_allowed_post_types() {

		// Return the array if we've built it already.
		if ( ! empty( $this->allowed_post_types ) ) {
			return $this->allowed_post_types;
		}

		$allowed_post_types = array();
		$post_type_objects  = get_post_types( array( 'public' => true ), 'objects' );

		// Copy the slugs of all post types that have show_in_rest set to true
		// into our $allowed_post_types array.
		foreach ( $post_type_objects as $post_type_object ) {
			if ( ! empty( $post_type_object->show_in_rest ) ) {
				$allowed_post_types[] = $post_type_object->name;
			}
		}

		// Allow the post types to be filtered (supports having different post types
		// visible in the REST API vs Simple GraphQL API).
		$allowed_post_types = apply_filters( 'simple_graphql_api_allowed_post_types', $allowed_post_types );

		// Save the post types as a class property.
		$this->allowed_post_types = $allowed_post_types;

		return $allowed_post_types;
	}

	/**
	 * Return an array of default fields for Posts.
	 *
	 * @since   0.8.0
	 *
	 * @param   object  $request  The request object.
	 * @return  array             The default fields for Posts.
	 */
	public function get_default_post_fields( WP_REST_Request $request ) {

		$fields = array(
			'ID',
			'post_title',
		);

		return apply_filters( 'simple_graphql_api_default_post_fields', $fields, $request );
	}

	/**
	 * Return an array of default fields for Terms.
	 *
	 * @since   0.8.0
	 *
	 * @param   object  $request  The request object.
	 * @return  array             The default fields for Terms.
	 */
	public function get_default_term_fields( WP_REST_Request $request ) {

		$fields = array(
			'term_id',
			'name',
		);

		return apply_filters( 'simple_graphql_api_default_term_fields', $fields, $request );
	}

	/**
	 * Return an array of default fields for Comments.
	 *
	 * @since   0.8.0
	 *
	 * @param   object  $request  The request object.
	 * @return  array             The default fields for Comments.
	 */
	public function get_default_comment_fields( WP_REST_Request $request ) {

		$fields = array(
			'comment_ID',
			'comment_author',
			'comment_content',
		);

		return apply_filters( 'simple_graphql_api_default_comment_fields', $fields, $request );
	}
}