<?php
/**
 * This file holds all of the query filters that modify the global wp_query
 */

add_filter( 'posts_request', 'kickpress_query_request', 10, 2 );

function kickpress_query_request( $request, $query ) {
	if ( kickpress_alter_query( $query ) && ( WP_DEBUG || isset( $_REQUEST['sql'] ) ) ) {
		echo '<pre style="white-space: pre-wrap;">' . trim( $request ) . '</pre>';
	}

	return $request;
}

function kickpress_alter_query( $query = null ) {
	global $kickpress_api;
	if ( empty( $kickpress_api->params['post_type'] ) )
		return false;
	return ! is_admin() && $query->is_main_query();
}

function kickpress_post_vars() {
	return array(
		'enter_attributes',
		'ID',
		'post_author',
		'post_date',
		'post_date_gmt',
		'post_content',
		'post_title',
		'_thumbnail_id',
		'post_category',
		'post_excerpt',
		'post_status',
		'comment_status',
		'ping_status',
		'post_password',
		'post_name',
		'to_ping',
		'pinged',
		'post_modified',
		'post_modified_gmt',
		'post_content_filtered',
		'post_parent',
		'guid',
		'menu_order',
		'post_type',
		'post_mime_type',
		'comment_count',
		'tags_input',
		'categories_input'
	);
}

function kickpress_query_filter( &$query ) {
	global $wpdb, $kickpress_api, $kickpress_meta_map;

	if ( kickpress_alter_query( $query ) ) {
		// populate empty sort with default parameters
		if ( ! isset( $kickpress_api->params['sort'] ) ) {
			$fields = explode( ',', $kickpress_api->params['default_sort_field'] );
			$orders = $kickpress_api->params['default_sort_direction'];

			if ( is_string( $orders ) )
				$orders = array_fill( 0, 3, $orders );

			for ( $i = 0; $i < 3; $i++ ) {
				if ( ! empty( $fields[$i] ) && ! empty( $orders[$i] ) ) {
					$field = trim( $fields[$i] );
					$order = trim( $orders[$i] );

					if ( ! empty( $field ) )
						$kickpress_api->params['sort'][$field] = $order;
				}
			}
		}

		$custom_fields  = $kickpress_api->get_custom_fields();
		$default_fields = kickpress_post_vars();

		$kickpress_meta_map = array();

		if ( isset( $kickpress_api->params['status'] ) ) {
			$status = $kickpress_api->params['status'];

			if ( is_string( $status ) )
				$status = explode( ',', $status );

			if ( is_array( $status ) ) {
				$kickpress_api->params['status'] = $status;
				$query->query_vars['post_status'] = $status;
			}
		}

		if ( isset( $kickpress_api->params['filter'] ) && is_array( $kickpress_api->params['filter'] ) ) {
			$meta_query = array();
			$meta_count = intval( 'meta_value' == $query->query_vars['orderby'] );

			$operators = array(
				// generic comparison
				'eq'     => '=',
				'not-eq' => '!=',
				'lt'     => '<',
				'lt-eq'  => '<=',
				'not-lt' => '>=',
				'gt'     => '>',
				'gt-eq'  => '>=',
				'not-gt' => '<=',

				// string comparison
				'like' => 'LIKE',

				// set comparison
				'in'  => 'IN',
				'btw' => 'BETWEEN',
			);

			foreach ( $kickpress_api->params['filter'] as $field_name => $filter_data ) {
				if ( is_string( $filter_data ) )
					$kickpress_api->params['filter'][$field_name] = $filter_data = array( 'eq' => $filter_data );

				if ( @$custom_fields[$field_name]['filterable'] ) {
					if ( ! in_array( $field_name, $default_fields ) ) {
						foreach ( $filter_data as $filter_operator => $filter_string ) {
							if ( stripos( $filter_operator, 'like' ) !== false ) {
								$compare = $operators['like'];
								$value   = $filter_string;

								if ( stripos( $filter_operator, 'not' ) !== false )
									$compare = 'NOT ' . $compare;

								/**
								 * WordPress surrounds value with '%...%'
								 * skip this filter for now and handle it in
								 * JOIN and WHERE clauses
								 */
								if ( stripos( $filter_operator, 'lead' ) !== false ||
									stripos( $filter_operator, 'trail' ) !== false ) {
									$kickpress_meta_map[$field_name] = array(
										'meta_key'     => $custom_fields[$field_name]['name'],
										'meta_join'    => true,
										'meta_compare' => $filter_operator,
										'meta_value'   => $filter_string,
										'table_alias'  => 'meta_like_' . intval( $meta_like_count ),
										'field_alias'  => 'meta_value'
									);

									$meta_like_count++;

									continue;
								}
							} elseif ( stripos( $filter_operator, 'in' ) !== false ||
								stripos( $filter_operator, 'btw' ) !== false ) {
								$operator_key = str_replace( 'not-', '', $filter_operator );

								$compare = $operators[$operator_key];
								$value   = explode( ',', $filter_string );

								if ( stripos( $filter_operator, 'not' ) !== false )
									$compare = 'NOT ' . $compare;
							} elseif ( isset( $operators[$filter_operator] ) ) {
								$compare = $operators[$filter_operator];
								$value   = $filter_string;
							} else {
								continue;
							}

							$meta_query[] = array(
								'key'     => $custom_fields[$field_name]['name'],
								'value'   => $value,
								'compare' => $compare
							);

							$kickpress_meta_map[$field_name] = array(
								'meta_key'    => $custom_fields[$field_name]['name'],
								'meta_join'   => false,
								'table_alias' => 0 == $meta_count ? $wpdb->postmeta : 'mt' . $meta_count,
								'field_alias' => 'meta_value'
							);

							$meta_count++;
						}
					}
				}
			}

			if ( ! empty( $meta_query ) ) {
				$meta_query['relation'] = 'OR';

				$query->query_vars['meta_query'] = $meta_query;
				$query->is_archive = true;
			}
		}

		if ( isset( $kickpress_api->params['term'] ) && is_array( $kickpress_api->params['term'] ) ) {
			$tax_query = array( 'relation' => 'AND' );

			foreach ( $kickpress_api->params['term'] as $taxonomy => $term_data ) {
				if ( is_string( $term_data ) )
					$kickpress_api->params['term'][$taxonomy] = $term_data = array( 'in' => $term_data );

				foreach ( $term_data as $term_operator => $term_string ) {
					$operator = strtoupper( str_replace( '-', ' ', $term_operator ) );

					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'operator' => $operator,
						'field'    => 'slug',
						'terms'    => explode( ',', $term_string )
					);
				}

				if ( isset( $kickpress_api->params['post_format'] ) ) {
					$post_format = $kickpress_api->params['post_format'];

					$tax_query[] = array(
						'taxonomy' => 'post_format',
						'operator' => 'in',
						'field'    => 'slug',
						'terms'    => explode( ',', $post_format )
					);
				}
			}

			$query->query_vars['tax_query'] = $tax_query;
			$query->is_archive = true;
		} elseif ( isset( $kickpress_api->params['post_format'] ) ) {
			$post_format = $kickpress_api->params['post_format'];

			$tax_query = array( 'relation' => 'AND' );

			$tax_query[] = array(
				'taxonomy' => 'post_format',
				'operator' => 'in',
				'field'    => 'slug',
				'terms'    => explode( ',', $post_format )
			);

			$query->query_vars['tax_query'] = $tax_query;
			$query->is_archive = true;
		}

		if ( isset( $kickpress_api->params['sort'] ) && is_array( $kickpress_api->params['sort'] ) ) {
			$sort_count = 0;

			foreach ( $kickpress_api->params['sort'] as $field_name => $order ) {
				if ( @$custom_fields[$field_name]['sortable'] ) {
					if ( ! in_array( $field_name, $default_fields ) ) {
						if ( ! isset( $kickpress_meta_map[$field_name] ) ) {
							$kickpress_meta_map[$field_name] = array(
								'meta_key'    => $custom_fields[$field_name]['name'],
								'meta_join'   => true,
								'join_type'   => 'LEFT',
								'table_alias' => 'meta_sort_' . $sort_count,
								'field_alias' => 'meta_value'
							);

							$sort_count++;
						}

						$kickpress_meta_map[$field_name]['orderby']   = true;
						$kickpress_meta_map[$field_name]['order']     = $order;

						$query->is_archive = true;
					}
				}
			}
		}

		if ( isset( $kickpress_api->params['author'] ) ) {
			$authors = explode( ',', $kickpress_api->params['author'] );
			$author_id = array();

			foreach ( $authors as $author_name ) {
				$author_name = sanitize_title_for_query( $author_name );

				if ( $author = get_user_by( 'slug', $author_name ) )
					$author_id[] = $author->ID;
			}

			$query->query_vars['author'] = implode( ',', $author_id );
			$query->is_author = true;
			$query->is_home   = false;
		}

		if ( isset( $kickpress_api->params['first_letter'] ) ) {
			$alpha_count = 0;
			$alpha_field = $kickpress_api->params['alphabar'];

			if ( @$custom_fields[$alpha_field]['filterable'] ) {
				if ( ! in_array( $alpha_field, $default_fields ) ) {
					if ( ! isset( $kickpress_meta_map[$alpha_field] ) ) {
						$kickpress_meta_map[$alpha_field] = array(
							'meta_key'    => $custom_fields[$alpha_field]['name'],
							'meta_join'   => true,
							'join_type'   => 'INNER',
							'table_alias' => 'meta_alpha',
							'field_alias' => 'meta_value',
							'alpha'       => $kickpress_api->params['first_letter']
						);

						$query->is_archive = true;
					}
				}
			}
		}

		if ( is_search() ) {
			$search_count = 0;

			foreach ( $custom_fields as $field_name => $meta ) {
				if ( @$meta['searchable'] ) {
					if ( ! in_array( $field_name, $default_fields ) ) {
						if ( ! isset( $kickpress_meta_map[$field_name] ) ) {
							$kickpress_meta_map[$field_name] = array(
								'meta_key'    => $meta['name'],
								'meta_join'   => true,
								'join_type'   => 'LEFT',
								'table_alias' => 'meta_search_' . $search_count,
								'field_alias' => 'meta_value'
							);

							$search_count++;
						}
					}
				}
			}
		}
	}
}

function kickpress_query_fields( $fields = '', &$query ) {
	global $wpdb, $kickpress_api, $kickpress_meta_map;

	if ( kickpress_alter_query( $query ) ) {
		foreach ( $kickpress_meta_map as $field_name => $meta ) {
			$fields .= sprintf(
				', %s.%s AS %s',
				$meta['table_alias'],
				$meta['field_alias'],
				$field_name
			);
		}
	}

	return $fields;
}

function kickpress_query_join( $join = '', &$query ) {
	global $wpdb, $kickpress_api, $kickpress_meta_map;

	if ( kickpress_alter_query( $query ) ) {
		if ( @is_array( $kickpress_api->params['rel'] ) ) {
			$post_alias = 'rel_posts';
			$meta_alias = 'rel_map';

			foreach ( $kickpress_api->params['rel'] as $rel_name => $slugs ) {
				$slugs = explode( ',', $slugs );

				$rel = kickpress_get_relationship( $rel_name );

				$post_type = $query->query_vars['post_type'];

				if ( $post_type == $rel->source_type ) {
					$mask = '(%3$s.relationship = \'%4$s\' '
					      . 'AND %3$s.source_id = %1$s.ID '
					      . 'AND %3$s.target_id = %2$s.ID '
					      . 'AND %2$s.post_name IN (\'%5$s\'))';
				} elseif ( $post_type == $rel->target_type ) {
					$mask = '(%3$s.relationship = \'%4$s\' '
					      . 'AND %3$s.source_id = %2$s.ID '
					      . 'AND %3$s.target_id = %1$s.ID '
					      . 'AND %2$s.post_name IN (\'%5$s\'))';
				} else {
					continue;
				}

				$cond[] = sprintf( $mask,
					$wpdb->posts,
					$post_alias,
					$meta_alias,
					$rel_name,
					implode( "', '", $slugs )
				);
			}

			if ( ! empty( $cond ) ) {
				$join .= $sql = sprintf( 'CROSS JOIN %1$s AS %3$s INNER JOIN %2$s AS %4$s ON (%5$s)',
					$wpdb->posts,
					$wpdb->post_relationships,
					$post_alias,
					$meta_alias,
					implode( ' OR ', $cond )
				);

				//echo $sql;
			}
		}

		if ( isset( $kickpress_api->params['sort']['author'] ) ) {
			$join .= sprintf(
				' INNER JOIN %2$s ON (%1$s.ID = %2$s.post_author)',
				$wpdb->posts,
				$wpdb->users
			);
		}

		foreach ( $kickpress_meta_map as $field_name => $meta ) {
			if ( $meta['meta_join'] ) {
				if ( ! isset( $meta['join_type'] ) ) $meta['join_type'] = 'INNER';

				$join .= sprintf(
					' %3$s JOIN %1$s AS %4$s ON (%2$s.ID = %4$s.post_id) AND (%4$s.meta_key = \'%5$s\')',
					$wpdb->postmeta,
					$wpdb->posts,
					$meta['join_type'],
					$meta['table_alias'],
					$meta['meta_key']
				);
			}
		}

		$join = $kickpress_api->join_filter( $join );
	}

	return $join;
}

/**
 * Update the main wordpress query with the Kickpress parameters
 * @param  string $where Existing Where clause to be modified
 * @param  obj $query Wordpress Query object
 * @return string        Updated Where clause
 */
function kickpress_query_where( $where = '', &$query ) {
	global $wpdb, $kickpress_api, $kickpress_meta_map;

	if ( kickpress_alter_query( $query ) ) {
		extract( $kickpress_api->params );

		// Append the first letter filter from the alphabar
		if ( isset( $first_letter ) && 'all' != $first_letter ) {
			if ( 'post_title' == $kickpress_api->params['alphabar'] ) {
				$field_alias = 'post_title';
				$table_alias = $wpdb->posts;
			} elseif (isset($kickpress_meta_map[$alphabar])) {
				$field_alias = $kickpress_meta_map[$alphabar]['field_alias'];
				$table_alias = $kickpress_meta_map[$alphabar]['table_alias'];
			}

			if ( $first_letter == '0-9' ) {
				$where .= sprintf( ' AND (%s.%s REGEX \'^[0-9]\')', $table_alias, $field_alias );
			} else {
				$alpha_value = stripslashes( trim( $first_letter ) );
				$alpha_value = str_replace( ' ', '%', urldecode( $alpha_value ) ) . '%';
				$alpha_value = $wpdb->prepare( '%s', $alpha_value );

				$where .= sprintf(
					' AND (%s.%s LIKE %s)',
					$table_alias,
					$field_alias,
					$alpha_value
				);
			}
		}

		// Append the specified date range
		if ( @is_array( $date ) ) {
			if ( isset( $date['min'] ) ) {
				$min_date = date( 'Y-m-d', strtotime( $date['min'] ) );

				if ( isset( $date['max'] ) ) {
					$max_date = date( 'Y-m-d', strtotime( $date['max'] ) );

					$where .= sprintf( " AND DATE(%s.post_date) BETWEEN '%s' AND '%s'", $wpdb->posts, $min_date, $max_date );
				} else {
					$where .= sprintf( " AND DATE(%s.post_date) >= '%s'", $wpdb->posts, $min_date );
				}
			} elseif ( isset( $date['max'] ) ) {
				$max_date = date( 'Y-m-d', strtotime( $date['max'] ) );

				$where .= sprintf( " AND %s.post_date <= '%s'", $wpdb->posts, $max_date );
			}
		}

		foreach ( $kickpress_meta_map as $field_name => $meta ) {
			if ( $meta['meta_join'] ) {
				// Append the first letter filter from the alphabar
				if ( isset( $meta['alpha'] ) && 'all' != $meta['alpha'] ) {
					$alpha_field = $kickpress_api->params['alphabar'];

					if ( isset( $kickpress_meta_map[$alpha_field] ) ) {
						$alpha_table = sprintf( '%s.meta_key = %s AND %1$s',
							$kickpress_meta_map[$alpha_field]['table_alias'],
							$wpdb->prepare( '%s', $kickpress_meta_map[$alpha_field]['meta_key'] )
						);

						$alpha_field = 'meta_value';
					} else {
						/* Replaced in JOIN clause
						$where .= sprintf(
							' AND (%s.meta_key = %s)',
							$meta['table_alias'],
							$wpdb->prepare( '%s', $meta['meta_key'] )
						); */

						continue;
					}

					if ( $first_letter == '0-9' ) {
						$where .= sprintf(
							" AND (%s.%s REGEXP '^[0-9]' )",
							$alpha_table,
							$alpha_field
						);
					} else {
						$alpha_value = stripslashes( trim( $meta['alpha'] ) );
						$alpha_value = str_replace( ' ', '%', urldecode( $alpha_value ) ) . '%';
						$alpha_value = $wpdb->prepare( '%s', $alpha_value );

						$where .= sprintf(
							' AND (%s.%s LIKE %s)',
							$alpha_table,
							$alpha_field,
							$alpha_value
						);
					}
				} elseif ( isset( $meta['meta_compare'] ) ) {
					$compare = 'LIKE';
					$value   = $meta['meta_value'];

					if ( stripos( $meta['meta_compare'], 'not' ) !== false )
						$compare = 'NOT LIKE';

					if ( stripos( $meta['meta_compare'], 'lead' ) !== false )
						$value = $value . '%';
					elseif ( stripos( $meta['meta_compare'], 'trail' ) !== false )
						$value = '%' . $value;

					$where .= sprintf(
						' AND (CAST(%s.%s AS CHAR) %s \'%s\')',
						$meta['table_alias'],
						$meta['field_alias'],
						$compare,
						$value
					);
				}
			}
		}

		$where = $kickpress_api->where_filter( $where );
	}

	return $where;
}

function kickpress_query_search( $search = '', &$query ) {
	global $wpdb, $kickpress_api, $kickpress_meta_map;

	if ( kickpress_alter_query( $query ) && is_search() ) {
		$keywords = preg_split( '/,|\s+/', $query->query_vars['s'] );

		$custom_fields = $kickpress_api->get_custom_fields();
		$default_fields = kickpress_post_vars();

		$filters = array();

		foreach ( $keywords as $keyword ) {
			$terms = array();

			$terms[] = sprintf( "(%s.post_title LIKE '%%%s%%')",   $wpdb->posts, $wpdb->escape( $keyword ) );
			$terms[] = sprintf( "(%s.post_content LIKE '%%%s%%')", $wpdb->posts, $wpdb->escape( $keyword ) );

			foreach ( $custom_fields as $field_name => $meta ) {
				if ( @$meta['searchable'] ) {
					if ( ! in_array( $field_name, $default_fields ) ) {
						$table = $meta['table_alias'];

						$terms[] = sprintf(
							"(%1\$s.meta_key = '%2\$s' AND %1\$s.meta_value LIKE '%%%3\$s%%')",
							$kickpress_meta_map[$field_name]['table_alias'],
							$kickpress_meta_map[$field_name]['meta_key'],
							$keyword
						);
					}
				}
			}

			$filters[] = '(' . implode( ' OR ', $terms ) . ')';
		}

		$search = ' AND (' . implode( ' OR ', $filters ) . ')';
	}

	return $search;
}

function kickpress_query_groupby( $groupby = '', &$query ) {
	global $wpdb;

	if ( kickpress_alter_query( $query ) ) {
		if ( empty( $groupby ) ) $groupby = sprintf( '%s.ID', $wpdb->posts );
	}

	return $groupby;
}

function kickpress_query_orderby( $orderby = '', &$query ) {
	global $wpdb, $kickpress_api, $kickpress_meta_map;

	if ( kickpress_alter_query( $query ) ) {
		$order_fields = array();

		if ( @is_array( $kickpress_api->params['sort'] ) ) {
			$sortable_fields = array(
				'date'           => 'post_date',
				'title'          => 'post_title',
				'status'         => 'post_status',
				'name'           => 'post_name',
				'modified'       => 'post_modified',
				'type'           => 'post_type',
				'menu_order'     => 'menu_order',
				'comment_status' => 'comment_status',
				'comment_count'  => 'comment_count'
			);

			foreach ( $kickpress_api->params['sort'] as $field_name => $order ) {
				/* var_dump( isset( $sortable_fields[$field_name] ),
					'author' == $field_name,
					isset( $kickpress_meta_map[$field_name] ) ); */

				$field_name = preg_replace( '/^post_/', '', $field_name );

				if ( $field_name && ! empty( $sortable_fields[ $field_name ] ) ) {
					$order_fields[] .= sprintf( '%s.%s %s',
						$wpdb->posts,
						$sortable_fields[$field_name],
						strtoupper( $order )
					);
				} elseif ( 'author' == $field_name ) {
					$order_fields[] .= sprintf( '%s.display_name %s',
						$wpdb->users,
						strtoupper( $order )
					);
				} elseif ( 'term_order' == $field_name ) {
					$order_fields[] = sprintf( '%s.term_order %s',
						$wpdb->term_relationships,
						strtoupper( $order )
					);
				} elseif ( isset( $kickpress_meta_map[$field_name] ) ) {
					$order_fields[] .= sprintf( '%s.%s %s',
						$kickpress_meta_map[$field_name]['table_alias'],
						$kickpress_meta_map[$field_name]['field_alias'],
						strtoupper( $order )
					);
				}
			}

			if ( ! empty( $order_fields ) ) $orderby = implode( ', ', $order_fields );
		}
	}

	return $orderby;
}

function kickpress_query_limits( $limits = '', &$query ) {
	global $wpdb, $kickpress_api;

	if ( kickpress_alter_query( $query ) && ! empty( $kickpress_api->params['posts_per_page'] ) ) {
		if ( preg_match( '/LIMIT (\d+), (\d+)/', $limits, $matches ) ) {
			$limit  = intval( $matches[2] );
			$offset = intval( $matches[1] );

			//if ( isset( $kickpress_api->params['posts_per_page'] ) ) {
				$page   = 0 == $limit ? 0 : floor( $offset / $limit );
				$limit  = intval( $kickpress_api->params['posts_per_page'] );
				$offset = intval( $page * $limit );
			//}

			$limits = sprintf( 'LIMIT %d, %d', $offset, $limit );
		}
	}

	return $limits;
}

?>