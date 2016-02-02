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

		// Allow the URL base to be customized.
		$base = apply_filters( 'simple_graphql_api_url_base', 'graph/v1' );

		register_rest_route( $base, '/any/', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'any_endpoint' ),
		) );

		register_rest_route( $base, '/posts/(?P<ids>\d+(,\d+)*)?$', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'posts_endpoint' ),
		) );

		register_rest_route( $base, '/terms/(?P<ids>\d+(,\d+)*)?$', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'terms_endpoint' ),
		) );

		register_rest_route( $base, '/comments/(?P<ids>\d+(,\d+)*)?$', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'comments_endpoint' ),
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

		return apply_filters( 'simple_graphql_api_response', $response, $request, $params );
	}

	/**
	 * Build and return the API response for the /posts/ endpoint.
	 *
	 * @since   1.0.0
	 *
	 * @param   object  $request  The request object.
	 * @return  object            The response object.
	 */
	public function posts_endpoint( WP_REST_Request $request ) {

		$params         = $request->get_params();
		$ids            = array();
		$fields         = array();
		$term_fields    = array();
		$comment_fields = array();

		if ( isset( $params['ids'] ) ) {
			$ids = ( is_array( $params['ids'] ) ) ? $params['ids'] : explode( ',', $params['ids'] );
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

		foreach ( $ids as $id ) {
			$post = $this->get_post( (int)$id, $fields );

			if ( $post ) {
				if ( is_object( $post ) ) {
					$posts[]     = $post;

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

		return apply_filters( 'simple_graphql_api_response', $response, $request, $params );
	}

	/**
	 * Build and return the API response for the /terms/ endpoint.
	 *
	 * @since   1.0.0
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

		return apply_filters( 'simple_graphql_api_response', $response, $request, $params );
	}

	/**
	 * Build and return the API response for the /comments/ endpoint.
	 *
	 * @since   1.0.0
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

		return apply_filters( 'simple_graphql_api_response', $response, $request, $params );
	}

	/**
	 * Build and return the API response for a post.
	 *
	 * @since   1.0.0
	 *
	 * @param   int     $id              The post ID.
	 * @param   array   $fields          The fields to include.
	 * @return  object                   The response object.
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

				// If we are returning post_title or post_content, run it through the right filters
				// and return both a raw and rendered version to match the REST API.
				if ( 'post_content' === $field ) {
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

				// If 'terms' was specified as the field, generate a comma-separated string
				// that contains the IDs for any terms attached to the post.
				$response->terms = $this->get_term_ids_for_post( $post );

			} elseif ( 'comments' === $field ) {

				// If 'comments' was specified as the field, generate a comma-separated string
				// that contains the IDs for any comments attached to the post.
				$response->comments = $this->get_comment_ids_for_post( $post );

			} else {
				$meta = get_post_meta( $post->ID, $field, true );
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

		return apply_filters( 'simple_graphql_api_post', $response, $post, $id );
	}

	/**
	 * Build and return the API response for a term.
	 *
	 * @since   1.0.0
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
				$id
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
	 * @since   1.0.0
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
	 * @since   1.0.0
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
				$id
			);
		} elseif ( '1' !== $comment->comment_approved ) {
			return sprintf(
				__( 'Comment with ID %s has not been approved', 'simple-graphql-api' ),
				$id
			);
		} elseif ( ! in_array( $comment->comment_type, $comment_types ) ) {
			return sprintf(
				__( 'Disallowed comment type for comment with ID %s', 'simple-graphql-api' ),
				$id
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
	 * @since   1.0.0
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
	 * @since   1.0.0
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
	 * Return an array of default fields for Posts.
	 *
	 * @since   1.0.0
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
	 * @since   1.0.0
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
	 * @since   1.0.0
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