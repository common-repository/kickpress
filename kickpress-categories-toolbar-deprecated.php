<?php

/** 
 * This file holds all of the toolbar logic for adding toobars to themes
 */

function kickpress_categories_output($post_type_args=array()) {
/*
(
		[0] => stdClass Object
				(
						[term_id] => 60
						[name] => Activities
						[slug] => activities
						[term_group] => 0
						[term_taxonomy_id] => 67
						[taxonomy] => custom-post-type-categories
						[description] =>
						[parent] => 0
						[count] => 1
						[filter_type] => tabs
				)
)
*/
	$html = '';

	if ( isset($post_type_args['terms']) && is_array($post_type_args['terms']) ) {
		foreach ( $post_type_args['terms'] as $term_key=>$term_value ) {
			if ( $has_terms = get_terms($term_value->slug, array('hierarchical'=>true)) ) {
				$default_args = array(
					'category_list'      => array(),
					'categories_toolbar' => (string) $term_value->filter_type,
					'term_name'          => (string) $term_value->name,
					'term_slug'          => ( 'default' == $term_value->slug ? 'category': (string) $term_value->slug )
				);
				$term_args = array_merge($default_args, $post_type_args);

				if ( empty($term_value->filter_type) || 'false' == $term_value->filter_type ) {
					$html .= kickpress_term_description($has_terms, $term_args);
				} else {
					$toolbar_type = 'kickpress_categories_toolbar_'.$term_value->filter_type;
					if ( function_exists($toolbar_type) )
						$html .= $toolbar_type($has_terms, $term_args, $post_type_args);
					else
						$html .= kickpress_categories_toolbar_default($has_terms, $term_args, $post_type_args);
				}
			}
		}
	}
	return $html;
}

function kickpress_term_description($term_list=object, $args=array()) {
	global $wp_query;
	extract($args);
	$term_description = '';

	if ( isset($exclude) )
		$exclude = explode(',', $exclude);
	else
		$exclude = array();

	foreach ( $term_list as $key=>$this_term ) {
		if ( ! is_object($this_term) )
			continue;

		if ( ! in_array((integer) $this_term->term_id, $exclude) ) {
			if ( ! in_array((integer) $this_term->parent, $exclude) ) {
				if ( isset($term[$term_slug]) && $term[$term_slug] == $this_term->slug ) {
					if ( ! empty($this_term->description) && ! empty($this_term->name) ) {
						$term_description .= sprintf('
							<div class="term-%1$s">
								<h3>%2$s</h3>
								<p>%3$s</p>
							</div>',
							$this_term->slug,
							$this_term->name,
							$this_term->description
						);
					} else {
						if ( ! empty($this_term->name) ) {
							$term_description .= sprintf('
								<h3>%1$s</h3>',
								$this_term->name
							);
						}
					}
				}
			} else {
				$exclude[] = (integer) $this_term->term_id;
			}
		}
	}
/*
	if ( ! empty($term_description) )
		$term_description = '<h3>All '.($term_name=='Category'?'Categories':$term_name).'</h3>';
*/
	if ( ! empty($term_description) ) {
		$term_description = sprintf('
			<div class="term-description-tier-1">
				%1$s
			</div>',
			$term_description
		);
	}

	return $term_description;
}

function kickpress_categories_toolbar_default($term_list, $args=array(), $post_type_args=array()) {
	global $wp_query;
	extract($args);

	if ( isset($exclude) )
		$exclude = explode(',', $exclude);
	else
		$exclude = array();
		
	$options = '';
	$found_match = false;
	$term_description = '';

	foreach ( $term_list as $key=>$this_term ) {
		if ( ! in_array( (integer) $this_term->term_id, $exclude) ) {
			if ( ! in_array( (integer) $this_term->parent, $exclude) ) {
				$filter_pairs = kickpress_filter_pairs();
				$filter_pairs['term['.$term_slug.']'] = $this_term->slug;
				$query_string = kickpress_query_pairs($post_type_args, $args, $filter_pairs, $path, (empty($path) ? true : false));

				if ( $term[$term_slug] == $this_term->slug ) {
					$selected = ' selected="selected"';
					$found_match = true;
					
					if ( ! empty($this_term->description) ) {
						$term_description .= sprintf('
							<div class="term-%1$s">
								<h3>%2$s</h3>
								<p>%3$s</p>
							</div>',
							$this_term->slug,
							$this_term->name,
							$this_term->description
						);
					}
				} else {
					$selected = '';
				}

				$options .= sprintf('
					<option value="%1$s"%2$s>%3$s</option>',
					$query_string,
					$selected,
					$this_term->name
				);
			} else {
				$exclude[] = (integer) $this_term->term_id;
			}
		}
	/*
			$max_num_pages = ($posts_per_page != "-1"?ceil($found_posts / $posts_per_page):1);

			if ($max_num_pages > 1 && $page < $max_num_pages ) {

			}
	*/
	}

	if ( ! empty($term_description) ) {
		$term_description = sprintf('
			<div class="term-description-tier-2">
				%1$s
			</div>',
			$term_description
		);
	}

	$html = sprintf('
		<div class="categories-toolbar toolbar">
			<label for="%1$s-%2$s-select">Filter results by %3$s: </label>
			<select id="%1$s-%2$s-select" name="%1$s_%2$s_select" class="%1$s-category" title="%1$s-wrapper">
				<option value="all"%4$s>Show All</option>%5$s
			</select>
		</div>%6$s',
		$post_type,
		$term_slug,
		$term_name,
		($found_match?'':' selected="selected"'),
		$options,
		$term_description
	);

	return $html;
}

function kickpress_get_sub_categories($category) {
	global $wpdb;

	$sql = "
		SELECT 
			{$wpdb->prefix}terms.*,
			count({$wpdb->prefix}term_relationships.term_taxonomy_id) AS post_count
		FROM
			{$wpdb->prefix}terms
		INNER JOIN {$wpdb->prefix}term_taxonomy
			ON {$wpdb->prefix}term_taxonomy.taxonomy = 'category'
			AND {$wpdb->prefix}term_taxonomy.term_id = {$wpdb->prefix}terms.term_id
		INNER JOIN {$wpdb->prefix}terms parent_terms
			ON parent_terms.slug = '$category'
			AND parent_terms.term_id = {$wpdb->prefix}term_taxonomy.parent
		INNER JOIN {$wpdb->prefix}term_relationships
			ON {$wpdb->prefix}term_relationships.term_taxonomy_id = {$wpdb->prefix}term_taxonomy.term_taxonomy_id
		GROUP BY
			{$wpdb->prefix}term_relationships.term_taxonomy_id";

//{$wpdb->prefix}terms.slug = '".$post_type."'

	if ( $results = $wpdb->get_results($sql) )
		return $results;
	else
		return false;
}

function kickpress_categories_toolbar_tabs($term_list, $args=array(), $post_type_args=array()) {
	extract($args);
/*
	global $wp_query;

	if ( $results = kickpress_get_sub_categories($post_type) ) {
		// sub categories as tabs in a menu
		$menus = sprintf('
			<li id="%2$s_all">
				<a href="/%1$s/#cat_%2$s" class="tab %3$s">
					All %4$s
				</a>
			</li>',
			$post_type,
			$post_type,
			($category == $post_type?' active':''),
			kickpress_make_readable($post_type)
		);
	
		foreach ( $results as $category_key=>$category_values ) {
			$menus .= sprintf('
				<li id="%2$s_%3$s">
					<a href="/%1$s/api/term[]/%3$s/#cat_%2$s" class="tab %4$s" title="%5$s (%6$s)">
						%5$s
					</a>
				</li>',
				$post_type,
				$post_type,
				$category_values->slug,
				((string) $category_values->slug == $category?' active' : '' ),
				kickpress_make_readable($category_values->name),
				$category_values->post_count
			);
		}
		
		$html .= sprintf('
			<div class="tiers tier-%1$s">
				<ul id="cat_%2$s">
					%3$s
				</ul>
			</div>',
			$tier++,
			$post_type,
			$menus
		);

		return $html;
	}
*/

	if ( isset($exclude) )
		$exclude = explode(',', $exclude);
	else
		$exclude = array();
/*
				<li id="tabs_edit">
					<a class="tab	active" href="%1$s edit/"></a>
				</li>
					<li id="tabs_locations">
						<a class="tab " href="%1$s edit/tab/locations/">
							Locations
						</a>
					</li>
					<li id="tabs_events">
						<a class="tab " href="%1$s edit/tab/events/">
							Events
						</a>
					</li>
					get_permalink($post_id)

*/
	$tabs = '';
	$found_match = false;

	foreach ( $term_list as $key=>$this_term ) {
		if ( ! in_array( (integer) $this_term->term_id, $exclude) ) {
			if ( ! in_array( (integer) $this_term->parent, $exclude) ) {
				$filter_pairs = kickpress_filter_pairs();
				$filter_pairs['term['.$term_slug.']'] = $this_term->slug;
				$query_string = kickpress_query_pairs($post_type_args, $filter_pairs, $path, ( empty($path) ? true : false ));

				if ( $term[$term_slug] == $this_term->slug ) {
					$selected = ' active';
					$found_match = true;

					if ( ! empty($this_term->description) ) {
						$term_description .= sprintf('
							<div class="term-%1$s">
								<p>%3$s</p>
							</div>',
							$this_term->slug,
							$this_term->name,
							$this_term->description
						);
					}
				} else {
					$selected = '';
				}

				$tabs .= sprintf('
					<li class="cat-item cat-item-%5$s" id="tabs-%2$s"><a class="tab%6$s%8$s" href="%1$s" title="%3$s (%4$s)" rel="%7$s-wrapper">%3$s</a></li>',
					$query_string,
					$this_term->slug,
					$this_term->name,
					$this_term->count,
					$this_term->term_id,
					$selected,
					$post_type,
					( isset($use_ajax) && kickpress_boolean($use_ajax, true) ? ' reload' : '' )
				);
			} else {
				$exclude[] = (integer) $this_term->term_id;
			}
		}
	}

	$filter_pairs = kickpress_filter_pairs();
	$filter_pairs['term['.$term_slug.']'] = NULL;
	$query_string = kickpress_query_pairs($post_type_args, $filter_pairs, $path, (empty($path) ? true : false));

	if ( ! empty($term_description) ) {
		$term_description = sprintf('
			<div class="term-description-tier-2">
				%1$s
			</div>',
			$term_description
		);
	}

	$html = sprintf('
		<div class="tiers tier-2">
			<ul class="tab-%1$s">
				<li class="cat-item cat-item-all"><a class="tab%2$s%7$s" href="%3$s" title="Show All" rel="%6$s-wrapper">All</a></li>%4$s
			</ul>
		</div>%5$s',
		$term_slug,
		( $found_match ? '' : ' active' ),
		$query_string,
		$tabs,
		$term_description,
		$post_type,
		( isset($use_ajax) && kickpress_boolean($use_ajax, true) ? ' reload' : '' )
	);

	return $html;
}

function kickpress_categories_toolbar_links($term_list, $args=array(), $post_type_args=array()) {
	global $wp_query;
	extract($args);

	if ( isset($exclude) )
		$exclude = explode(',', $exclude);
	else
		$exclude = array();

	$links = array();
	$total_count = 0;

	foreach ( $term_list as $key=>$this_term ) {
		$found_match = false;

		if ( ! in_array((integer) $this_term->term_id, $exclude) ) {
			if ( ! in_array((integer) $this_term->parent, $exclude) ) {
				$filter_pairs = kickpress_filter_pairs();
				$filter_pairs['term['.$term_slug.']'] = $this_term->slug;
				$query_string = kickpress_query_pairs($post_type_args, $filter_pairs, $path, (empty($path) ? true : false));
				$total_count += $this_term->count;

				if ( $term[$term_slug] == $this_term->slug ) {
					$selected = ' class="active"';
					$found_match = true;
				} else {
					$selected = '';
				}

				$links[] = sprintf('
					<a href="%1$s"%2$s title="%3$s (%4$s)">%3$s&nbsp;(%4$s)</a>',
					$query_string,
					$selected,
					kickpress_make_readable($this_term->name),
					$this_term->count
				);
			} else {
				$exclude[] = (integer) $this_term->term_id;
			}
		}
	}

	$filter_pairs = kickpress_filter_pairs();
	$filter_pairs['term['.$term_slug.']'] = NULL;
	$query_string = kickpress_query_pairs($post_type_args, $filter_pairs, $path, (empty($path) ? true : false));

	$html = sprintf('
		<div class="category-links %2$s-links">
			<label>Filter results by %3$s: </label>
			<a href="%1$s"%4$s title="All %3$s (%6$s)">All %3$s (%6$s)</a>, %5$s
		</div>',
		$query_string,
		$term_slug,
		$term_name,
		($found_match?'':' class="active"'),
		implode(', ', $links),
		$total_count
	);

	return $html;
}

function kickpress_categories_toolbar_checkboxes($term_list, $args=array(), $post_type_args=array()) {
	global $wp_query;
	extract($args);

	if ( isset($exclude) )
		$exclude = explode(',', $exclude);
	else
		$exclude = array();

	$options = '';

	foreach ( $term_list as $key=>$this_term ) {
		if ( ! in_array( (integer) $this_term->term_id, $exclude) ) {
			if ( ! in_array( (integer) $this_term->parent, $exclude) ) {
				$filter_pairs = kickpress_filter_pairs();
				if ( isset($term[$term_slug][$key]) && $term[$term_slug][$key] == $this_term->slug )
					$selected = ' checked="checked"';
				else
					$selected = '';

				$filter_pairs['term['.$term_slug.']['.$key.']'] = $this_term->slug;
				$query_string = kickpress_query_pairs($post_type_args, $filter_pairs, $path, (empty($path) ? true : false));

				$options .= sprintf('
					<span style="white-space:nowrap;"><input type="checkbox" id="%2$s-%3$s-select" name="term[%6$s][%7$s]"%5$s value="%1$s" class="%3$s" title="%2$s-wrapper" />&nbsp;<label for="%2$s-%3$s-select">%4$s</label></span>',
					$query_string,
					$post_type,
					$this_term->slug,
					(str_replace(' ', '&nbsp;', $this_term->name)),
					$selected,
					$term_slug,
					$key
				);
			} else {
				$exclude[] = (integer) $this_term->term_id;
			}
		}
	/*
			$max_num_pages = ( $posts_per_page != "-1" ? ceil($found_posts / $posts_per_page) : 1 );

			if ( $max_num_pages > 1 && $page < $max_num_pages ) {

			}
	*/
	}

	$html = sprintf('
		<div class="categories-checkboxes">
			<label>Filter results by %1$s: </label>
			%2$s
		</div>',
		$term_name,
		$options
	);

	return $html;
}

?>