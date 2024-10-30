<?php

function kickpress_create_curation_tables() {
	global $wpdb;
	
	$query = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}term_ratings` (\n"
		   . "\t`user_id` bigint(20) unsigned NOT NULL,\n"
		   . "\t`term_taxonomy_id` bigint(20) unsigned NOT NULL,\n"
		   . "\t`term_rating` tinyint(3) unsigned NOT NULL,\n"
		   . "\tPRIMARY KEY (`user_id`, `term_taxonomy_id`),\n"
		   . "\tKEY `term_rating` (`term_rating`)\n"
		   . ");";
	
	$wpdb->query( $query );
	
	$query = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}post_relationships` (\n"
		   . "\t`source_id` bigint(20) unsigned NOT NULL,\n"
		   . "\t`object_id` bigint(20) unsigned NOT NULL,\n"
		   . "\t`term_count` smallint(5) unsigned NOT NULL,\n"
		   . "\t`term_total` smallint(5) unsigned NOT NULL,\n"
		   . "\t`term_updated` datetime NOT NULL,\n"
		   . "\tPRIMARY KEY (`source_id`, `object_id`)\n"
		   . ");";
	
	$wpdb->query( $query );
	
	$query = "INSERT INTO `{$wpdb->prefix}post_relationships` "
		   . "(`source_id`, `object_id`, `term_count`, `term_total`, `term_updated`)\n"
		   . "SELECT `tr`.`object_id` AS `source_id`, `tr2`.`object_id`,\n"
		   . "\tCOUNT(DISTINCT `tr2`.`term_taxonomy_id`) AS `term_count`,\n"
		   . "\tCOUNT(DISTINCT `tr3`.`term_taxonomy_id`) AS `term_union`,\n"
		   . "\tNOW() AS `term_updated`\n"
		   . "FROM `{$wpdb->term_relationships}` AS `tr`\n"
		   . "INNER JOIN `{$wpdb->term_relationships}` AS `tr2`\n"
		   . "\tON `tr`.`term_taxonomy_id` = `tr2`.`term_taxonomy_id`\n"
		   . "\tAND `tr`.`object_id` != `tr2`.`object_id`\n"
		   . "INNER JOIN `wp_term_relationships` AS `tr3`\n"
		   . "\tON `tr3`.`object_id` IN (`tr`.`object_id`, `tr2`.`object_id`)\n"
		   . "WHERE `tr`.`object_id` < `tr2`.`object_id`\n"
		   . "GROUP BY `tr`.`object_id`, `tr2`.`object_id`\n"
		   . "HAVING `term_count` > 1\n"
		   . "ON DUPLICATE KEY UPDATE `term_count` = VALUES(`term_count`), "
		   . "`term_total` = VALUES(`term_total`)";
	
	$wpdb->query( $query );
}

function kickpress_get_similar_posts( $post_id ) {
	global $wpdb;
	
	$query = "SELECT `p`.*, `term_count`, `term_total`,\n"
		   . "\t`term_count` / `term_total` AS `term_sim`\n"
		   . "FROM `{$wpdb->prefix}post_relationships` AS `pr`\n"
		   . "INNER JOIN `{$wpdb->posts}` AS `p`\n"
		   . "\tON `pr`.`object_id` = `p`.`ID`\n"
		   . "WHERE `pr`.`source_id` = {$post_id}\n"
		   . "ORDER BY `term_sim` DESC, `term_total` DESC";
	
	$posts = $wpdb->get_results( $query );
}

function kickpress_get_similarity_ratings() {
	global $wpdb;
	
	$query = "SELECT `p2`.*,
		`c`.`comment_karma` AS `post_rating`,
		COUNT(DISTINCT `tr2`.`term_taxonomy_id`) AS `term_count`,
		COUNT(DISTINCT `tr3`.`term_taxonomy_id`) AS `term_total`,
		COUNT(DISTINCT `tr2`.`term_taxonomy_id`) /
		COUNT(DISTINCT `tr3`.`term_taxonomy_id`) AS `term_sim`
	FROM `{$wpdb->term_relationships}` AS `tr`
	INNER JOIN `{$wpdb->term_relationships}` AS `tr2`
		ON `tr`.`term_taxonomy_id` = `tr2`.`term_taxonomy_id`
		AND `tr`.`object_id` != `tr2`.`object_id`
	INNER JOIN `{$wpdb->term_relationships}` AS `tr3`
		ON `tr3`.`object_id` IN (`tr`.`object_id`, `tr2`.`object_id`)
	INNER JOIN `$wpdb->posts` AS `p` ON `tr`.`object_id` = `p`.`ID`
	INNER JOIN `$wpdb->posts` AS `p2` ON `tr2`.`object_id` = `p2`.`ID`
	INNER JOIN `$wpdb->comments` AS `c` ON `p`.`ID` = `c`.`comment_post_ID`
	WHERE `tr`.`object_id` != `tr2`.`object_id`
	AND `c`.`comment_approved` = 'private'
	AND `c`.`comment_type` = 'rating'
	GROUP BY `tr`.`object_id`, `tr2`.`object_id`
	HAVING `term_count` > 1
	ORDER BY `post_rating` DESC, `term_sim` DESC, `term_total` DESC";
	
	$results = $wpdb->get_results( $query );
	
	$posts = array();
	
	foreach ( $results as $row ) {
		if ( ! isset( $posts[$row->ID] ) ) {
			$post = clone $row;
			
			unset( $post->post_rating,
				$post->term_count,
				$post->term_total,
				$post->term_sim );
			
			$posts[$row->ID] = $post;
		}
		
		$posts[$row->ID]->ratings[] = array(
			'rating' => $row->post_rating,
			'weight' => $row->term_sim
		);
	}
	
	foreach ( $posts as $post ) {
		$total = $weight = 0;
		
		foreach ( $post->ratings as $rating) {
			$total  += $rating['rating'] * $rating['weight'];
			$weight += $rating['weight'];
		}
		
		$post->post_sim_rating = $total / $weight;
		
		unset( $post->ratings );
	}
	
	uasort( $posts, 'kickpress_compare_rating' );
	
	return array_reverse( $posts );
}

function kickpress_get_preference_ratings() {
	global $wpdb;
	
	$query = "SELECT `p`.*, AVG(`term_rating`) AS `post_pre_rating`
	FROM `{$wpdb->posts}` AS `p`
	INNER JOIN `{$wpdb->term_relationships}` AS `tr`
	ON `p`.`ID` = `tr`.`object_id`
	INNER JOIN `{$wpdb->prefix}term_ratings` AS `tra`
	ON `tr`.`term_taxonomy_id` = `tra`.`term_taxonomy_id`
	WHERE `tra`.`user_id` = 1
	GROUP BY `p`.`ID`
	ORDER BY `post_pre_rating` DESC, `post_date` DESC";
	
	return $wpdb->get_results( $query );
}

function kickpress_compare_rating( $post1, $post2 ) {
	if ( $post1->post_sim_rating > $post2->post_sim_rating ) return 1;
	elseif ( $post1->post_sim_rating < $post2->post_sim_rating ) return -1;
	else return 0;
}

// TODO user-to-user similarity rating

// TODO highest-rated posts by similar users

?>