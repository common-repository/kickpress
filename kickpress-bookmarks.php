<?php

add_filter( 'comments_clauses', 'kickpress_comments_clauses', 10, 2 );

function kickpress_comments_clauses( $clauses, $query ) {
	$states = array( 'private', 'feedback' );
	$status = $query->query_vars['status'];

	if ( in_array( $status, $states ) ) {
		$pattern = "/\(.+\)/Ui";
		$subpatt = "/comment_approved = \'(.+)\'/Ui";
		$replace = "comment_approved = '{$status}'";
		$subject = $clauses['where'];

		if ( preg_match_all( $pattern, $subject, $matches ) ) {
			if ( preg_match_all( $subpatt, $matches[0][0], $submatches ) )
				$clauses['where'] = preg_replace( $pattern, $replace, $subject, 1 );
		} else {
			$clauses['where'] = preg_replace( $subpatt, $replace, $subject, 1 );
		}
	}

	return $clauses;
}

function kickpress_get_private_comment_args( $type, $args = array() ) {
	$types = array(
		'private'  => array( 'bookmark', 'note', 'task' ),
		'feedback' => array( 'vote', 'rating' )
	);

	if ( in_array( $type, $types['private'] ) ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$args['user_id'] = get_current_user_id();
	}

	$args['status'] = 'private';
	$args['type']   = $type;

	return $args;
}

function kickpress_get_private_comments( $type, $args = array() ) {
	if ( ! $args = kickpress_get_private_comment_args( $type, $args ) ) {
		return false;
	}

	$comments = get_comments( $args );

	foreach ( $comments as $comment ) {
		// Built-in meta field
		$title = get_comment_meta( $comment->comment_ID, '_title', true );

		if ( ! empty( $title ) ) $comment->comment_title = $title;

		// Built-in meta fields
		$modified     = get_comment_meta( $comment->comment_ID, '_modified',     true );
		$modified_gmt = get_comment_meta( $comment->comment_ID, '_modified_gmt', true );

		if ( ! empty( $modified ) && ! empty( $modified_gmt ) ) {
			$comment->comment_modified     = $modified;
			$comment->comment_modified_gmt = $modified_gmt;
		} else {
			$comment->comment_modified     = $comment->comment_date;
			$comment->comment_modified_gmt = $comment->comment_date_gmt;
		}

		$comment->comment_meta = array();

		$meta = get_comment_meta( $comment->comment_ID );

		unset( $meta['_title'],
			$meta['_modified'],
			$meta['_modified_gmt'] );

		foreach ( $meta as $key => $values ) {
			$comment->comment_meta[ $key ] = $values[0];
		}
	}

	return $comments;
}

function kickpress_count_private_comments( $type, $args = array() ) {
	if ( ! $args = kickpress_get_private_comment_args( $type, $args ) ) {
		return false;
	}

	$args['count'] = true;

	return get_comments( $args );
}

function kickpress_get_bookmarks( $args = array() ) {
	return kickpress_get_private_comments( 'bookmark', $args );
}

function kickpress_get_notes( $args = array() ) {
	return kickpress_get_private_comments( 'note', $args );
}

function kickpress_get_tasks( $args = array() ) {
	return kickpress_get_private_comments( 'task', $args );
}

function kickpress_get_votes( $args = array() ) {
	$comments = kickpress_get_private_comments( 'vote', $args );

	$votes = array_fill( 0, 2, 0 );
	$posts = array();

	foreach ( $comments as $comment ) {
		$post_id = $comment->comment_post_ID;
		$user_id = $comment->user_id;

		if ( ! isset( $posts[$post_id] ) )
			$posts[$post_id] = array();

		if ( ! in_array( $user_id, $posts[$post_id] ) ) {
			$votes[$comment->comment_karma]++;
			$posts[$post_id][] = $user_id;
		}
	}

	return $votes;
}

function kickpress_get_ratings( $args = array() ) {
	$comments = kickpress_get_private_comments( 'rating', $args );

	$ratings = array_fill( 1, 5, 0 );
	$posts   = array();

	foreach ( $comments as $comment ) {
		$post_id = $comment->comment_post_ID;
		$user_id = $comment->user_id;

		if ( ! isset( $posts[$post_id] ) )
			$posts[$post_id] = array();

		if ( ! in_array( $user_id, $posts[$post_id] ) ) {
			$ratings[$comment->comment_karma]++;
			$posts[$post_id][] = $user_id;
		}
	}

	return $ratings;
}

function kickpress_insert_private_comment( $post_id, $data = array() ) {
	if ( is_user_logged_in() && $post = get_post( $post_id ) ) {
		extract( $data, EXTR_SKIP );

		$comment_post_ID  = $post->ID;
		$comment_approved = 'private';

		if ( ! isset( $comment_karma ) )
			$comment_karma = 0;

		$user    = wp_get_current_user();
		$user_id = $user->ID;

		if ( empty( $user->display_name ) )
			$user->display_name = $user->user_login;

		global $wpdb;

		$comment_author       = $wpdb->escape( $user->display_name );
		$comment_author_email = $wpdb->escape( $user->user_email );
		$comment_author_url   = $wpdb->escape( $user->user_url );

		$comment_author_IP    = preg_replace( '/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR'] );
		$comment_agent        = substr( $_SERVER['HTTP_USER_AGENT'], 0, 254 );

		if ( ! isset ( $comment_date ) )
			$comment_date = current_time( 'mysql' );

		$comment_date_gmt = get_gmt_from_date( $comment_date );

		$comment_data = compact(
			'comment_post_ID',
			'comment_author',
			'comment_author_email',
			'comment_author_url',
			'comment_author_IP',
			'comment_date',
			'comment_date_gmt',
			'comment_content',
			'comment_karma',
			'comment_approved',
			'comment_agent',
			'comment_type',
			'user_id'
		);

		$comment_id = wp_insert_comment( $comment_data );

		if ( isset( $comment_title ) )
			update_comment_meta( $comment_id, '_title', $comment_title );

		if ( isset( $comment_meta ) && is_array( $comment_meta ) ) {
			// Ignore built-in meta fields
			unset( $comment_meta['_title'],
				$comment_meta['_modified'],
				$comment_meta['_modified_gmt'] );

			foreach ( $comment_meta as $meta_key => $meta_value )
				update_comment_meta( $comment_id, $meta_key, $meta_value );
		}

		return $comment_ID;
	}

	return 0;
}

function kickpress_update_private_comment( $comment_id, $data = array() ) {
	if ( is_user_logged_in() && $comment = get_comment( $comment_id ) ) {
		if ( get_current_user_id() == $comment->user_id ) {
			extract( $data, EXTR_SKIP );

			$comment_data = array();

			if ( isset( $comment_content ) )
				$comment_data['comment_content'] = $comment_content;

			if ( isset( $comment_karma ) )
				$comment_data['comment_karma'] = $comment_karma;

			if ( ! empty( $comment_data ) ) {
				$comment_data['comment_ID'] = $comment_id;
				wp_update_comment( $comment_data );
			}

			if ( isset( $comment_title ) )
				update_comment_meta( $comment_id, '_title', $comment_title );

			if ( ! isset( $comment_modified ) )
				$comment_modified = current_time( 'mysql' );

			$comment_modified_gmt = get_gmt_from_date( $comment_modified );

			update_comment_meta( $comment_id, '_modified',     $comment_modified );
			update_comment_meta( $comment_id, '_modified_gmt', $comment_modified_gmt );

			if ( isset( $comment_meta ) && is_array( $comment_meta ) ) {
				// Ignore built-in meta fields
				unset( $comment_meta['_title'],
					$comment_meta['_modified'],
					$comment_meta['_modified_gmt'] );

				foreach ( $comment_meta as $meta_key => $meta_value )
					update_comment_meta( $comment_id, $meta_key, $meta_value );
			}
		}
	}
}

function kickpress_insert_bookmark( $post_id ) {
	if ( is_user_logged_in() && $post = get_post( $post_id ) ) {
		$bookmarks = kickpress_get_bookmarks( array( 'post_id' => $post_id ) );
		if ( ! empty( $bookmarks ) ) {
			return $bookmarks[0]->comment_ID;
		}

		return kickpress_insert_private_comment( $post_id, array(
			'comment_content' => 'Bookmark: ' . $post->post_title,
			'comment_type'    => 'bookmark'
		) );
	}
}

function kickpress_delete_bookmark( $post_id ) {
	if ( is_user_logged_in() ) {
		$bookmarks = kickpress_get_bookmarks( array(
			'post_id' => $post_id
		) );

		foreach ( $bookmarks as $bookmark ) {
			wp_delete_comment( $bookmark->comment_ID, true );
		}
	}
}

function kickpress_toggle_bookmark( $post_id ) {
	$bookmarks = kickpress_get_bookmarks( array(
		'post_id' => $post_id
	) );

	if ( empty( $bookmarks ) )
		kickpress_insert_bookmark( $post_id );
	else
		kickpress_delete_bookmark( $post_id );
}

function kickpress_insert_note( $post_id, $title, $content ) {
	if ( is_user_logged_in() && $post = get_post( $post_id ) ) {
		return kickpress_insert_private_comment( $post_id, array(
			'comment_title'   => $title,
			'comment_content' => $content,
			'comment_type'    => 'note'
		) );
	}
}

function kickpress_update_note( $comment_id, $title, $content ) {
	if ( is_user_logged_in() && $comment = get_comment( $comment_id ) ) {
		kickpress_update_private_comment( $comment_id, array(
			'comment_title'   => $title,
			'comment_content' => $content,
			'comment_type'    => 'note'
		) );
	}
}

function kickpress_delete_note( $comment_id ) {
	if ( is_user_logged_in() && $note = get_comment( $comment_id ) ) {
		$user_id = get_current_user_id();

		if ( 'private' == $note->comment_approved && 'note' == $note->comment_type && $user_id == $note->user_id ) {
			wp_delete_comment( $note->comment_ID, true );
		}
	}
}

function kickpress_insert_task( $post_id, $content, $karma = 0, $meta = array() ) {
	if ( is_user_logged_in() && $post = get_post( $post_id ) ) {
		$args = array(
			'comment_content' => $content,
			'comment_type'    => 'task'
		);

		if ( 0 <= $karma && 1 >= $karma )
			$args['comment_karma'] = intval( round( $karma ) );

		if ( ! empty( $meta ) )
			$args['comment_meta'] = $meta;

		return kickpress_insert_private_comment( $post_id, $args );
	}
}

function kickpress_update_task( $comment_id, $content = null, $karma = -1, $meta = array() ) {
	if ( is_user_logged_in() && $task = get_comment( $comment_id ) ) {
		$args = array();

		if ( ! empty( $content ) )
			$args['comment_content'] = $content;

		if ( 0 <= $karma && 1 >= $karma )
			$args['comment_karma'] = intval( round( $karma ) );

		if ( ! empty( $meta ) )
			$args['comment_meta'] = $meta;

		if ( ! empty( $args ) )
			return kickpress_update_private_comment( $comment_id, $args );
	}
}

function kickpress_delete_task( $comment_id ) {
	if ( is_user_logged_in() && $task = get_comment( $comment_id ) ) {
		if ( 'private' == $task->comment_approved &&
			'task' == $task->comment_type &&
			get_current_user_id() == $task->user_id ) {
			wp_delete_comment( $task->comment_ID, true );
		}
	}
}

function kickpress_toggle_task( $comment_id ) {
	if ( is_user_logged_in() && $task = get_comment( $comment_id ) ) {
		return kickpress_update_task( $comment_id, null,
			( $task->comment_karma + 1 ) % 2 );
	}
}

function kickpress_insert_vote( $post_id, $karma ) {
	if ( 0 > $karma || 1 < $karma ) return false;

	if ( is_user_logged_in() && $post = get_post( $post_id ) ) {
		kickpress_delete_vote( $post_id );
		kickpress_insert_private_comment( $post_id, array(
			'comment_content' => 'Vote (' . round( $karma ) . '): ' . $post->post_title,
			'comment_type'    => 'vote',
			'comment_karma'   => intval( round( $karma ) )
		) );
	}
}

function kickpress_delete_vote( $post_id ) {
	if ( is_user_logged_in() ) {
		$votes = get_comments( array(
			'post_id' => $post_id,
			'user_id' => get_current_user_id(),
			'status'  => 'private',
			'type'    => 'vote'
		) );

		foreach ( $votes as $vote ) {
			wp_delete_comment( $vote->comment_ID, true );
		}
	}
}

function kickpress_insert_rating( $post_id, $karma ) {
	if ( 1 > $karma || 5 < $karma ) return false;

	if ( is_user_logged_in() && $post = get_post( $post_id ) ) {
		kickpress_delete_rating( $post_id );
		kickpress_insert_private_comment( $post_id, array(
			'comment_content' => 'Rating (' . round( $karma ) . '): ' . $post->post_title,
			'comment_type'    => 'rating',
			'comment_karma'   => intval( round( $karma ) )
		) );
	}
}

function kickpress_delete_rating( $post_id ) {
	if ( is_user_logged_in() ) {
		$ratings = get_comments( array(
			'post_id' => $post_id,
			'user_id' => get_current_user_id(),
			'status'  => 'private',
			'type'    => 'rating'
		) );

		foreach ( $ratings as $rating ) {
			wp_delete_comment( $rating->comment_ID, true );
		}
	}
}

function kickpress_bookmark_form( $post_id = null ) {
	if ( is_null( $post_id ) )
		$post_id = get_the_ID();

	if ( is_user_logged_in() ) {
		$url = kickpress_api_url( $post_id, array(
			'action' => 'toggle-bookmark'
		) );

		$bookmarks = kickpress_get_bookmarks( array(
			'post_id' => $post_id
		) );

		if ( empty( $bookmarks ) ) {
			$class = 'add-bookmark toggle-bookmark';
			$label = 'Add Bookmark';
		} else {
			$class = 'remove-bookmark toggle-bookmark';
			$label = 'Remove Bookmark';
		}

		printf( '<a href="%s" class="%s">%s</a>',
			$url,
			$class,
			$label
		);
	}
}

function kickpress_bookmark_list( $args = array(), $bookmarks = null ) {
	echo kickpress_get_bookmark_list( $args, $bookmarks );
}

function kickpress_get_bookmark_list( $args = array(), $bookmarks = null ) {
	if ( is_null( $bookmarks ) ) $bookmarks = kickpress_get_bookmarks( );

	$defaults = array(
		'style' => 'ul'
	);

	$args = wp_parse_args( $args, $defaults );

	extract( $args );

	if ( in_array( $style, array( 'ol', 'ul' ) ) ) {
		$container = $style;
		$element   = 'li';
	} else {
		$container = 'div';
		$element   = 'div';
	}

	$html = sprintf( '<%s class="bookmarks-list">', $container );

	if ( empty( $bookmarks ) ) {
		$html .= sprintf( '<%1$s class="bookmark no-bookmarks">%2$s</%1$s>', $element, __( 'No bookmarks were found', 'kickpress' ) );
	} else {
		foreach ( $bookmarks as $bookmark ) {
            $post = get_post( $bookmark->comment_post_ID );
            $date = date( 'F jS', strtotime( $post->post_date ) );

			$html .= sprintf( '<%s id="bookmark-%s" class="bookmark">', $element, $bookmark->comment_ID );

			$html .= sprintf(
				'<span class="bookmark-title"><a href="%s" target="_blank">%s</a><span> <span class="bookmark-date">%s</span>',
				get_permalink( $post->ID ),
				$post->post_title,
				$date
			);

			$html .= sprintf( '</%s><!-- .bookmark -->', $element );
		}
	}

	$html .= sprintf( '</%s><!-- .bookmark-list -->', $container );
	return $html;
}

function kickpress_note_list( $args = array(), $notes = null ) {
	echo kickpress_get_note_list( $args, $notes );
}

function kickpress_get_note_list( $args = array(), $notes = null ) {
	if ( is_null( $notes ) ) $notes = kickpress_get_notes();

	$defaults = array(
		'style'       => 'ul',
		'show_edit'   => false,
		'show_remove' => false
	);

	$args = wp_parse_args( $args, $defaults );

	extract( $args );

	if ( in_array( $style, array( 'ol', 'ul' ) ) ) {
		$container = $style;
		$element   = 'li';
	} else {
		$container = 'div';
		$element   = 'div';
	}

	$html = sprintf( '<%s class="notes-list">', $container );

	if ( empty( $notes ) ) {
		$html .= sprintf( '<%1$s class="note no-notes">%2$s</%1$s>', $element, __( 'No notes were found', 'kickpress' ) );
	} else {
		foreach ( $notes as $note ) {
	        $post = get_post( $note->comment_post_ID );
			$date = date( 'F j, Y', strtotime( $note->comment_date_gmt ) );

			$html .= sprintf( '<%s id="note-%s" class="note">', $element, $note->comment_ID );

			$html .= sprintf( '
				<span class="note-title">"%s"</span><span class="post-title"> on <a href="%s#addendum" target="_blank">%s</a></span> <span class="note-date">%s</span>',
				$note->comment_title,
				get_permalink( $post->ID ),
				$post->post_title,
				$date
			);

			// Note Meta
			$html .= sprintf( '<div class="note-meta">' );

			if ( $show_edit ) {
				$html .= sprintf( '<a rel="%d" href="#note-form" class="edit-note">%s</a> ', $note->comment_ID, __( 'Edit Note', 'kickpress' ) );
			}

			if ( $show_remove ) {
				$url = kickpress_api_url( $note->comment_post_ID, array(
					'action' => 'remove-note',
					'action_key' => $note->comment_ID
				) );

				$html .= sprintf( '<a href="%s" class="remove-note">%s</a> ', $url, __( 'Remove Note', 'kickpress' ) );
			}

			$html .= '</div><!-- .note-meta -->';

			$html .= sprintf( '</%s><!-- .note -->', $element );
		}
	}

	$html .= sprintf( '</%s><!-- .note-list -->', $container );
	return $html;
}

function kickpress_note_form( $args = array(), $post_id = null ) {
	if ( is_null( $post_id ) )
		$post_id = get_the_ID();

	if ( is_user_logged_in() ) {
		$url = kickpress_api_url( $post_id, array(
			'action' => 'add-note'
		) );
?>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('a.edit-note').click(function() {
			var id = $(this).attr('rel');
			var title = $(this).parents('.note').find('.note-title').text();
			var content = $(this).parents('.note').find('.note-content').html();

			var tags = {
				'<p>': '',
				'</p>': "\n",
				'<br>': '',
				'<br />': ''
			};

			for ( var tag in tags ) {
				var replacement = tags[tag];

				while (content.search(tag) >= 0) {
					content = content.replace(tag, replacement);
				}
			}

			content = content.replace(/(\s*$)/i,'');

			var action = $('#note-form').attr('action').replace('/add-note/', '/update-note[' + id + ']/');

			$('#note-form').attr('action', action);
			$('#note-id').val(id);
			$('#note-title').val(title);
			$('#note-content').val(content);
		});

		$('#note-reset').click(function() {
			var action = $('#note-form').attr('action').replace(/update-note\[\d+\]/, 'add-note');

			$('#note-form').attr('action', action);
			$('#note-id').val('');
		});
	});
</script>
<form id="note-form" action="<?php echo $url; ?>" method="post">
	<input id="note-id" type="hidden" name="comment_ID" value="">
	<p class="note-form-body">
		<label for="note-title">Title</label>
		<input id="note-title" name="note[title]" size="40">
	</p>
	<p class="note-form-body">
		<label for="note-content">Note</label>
		<textarea id="note-content" name="note[content]" cols="45" rows="8"></textarea>
	</p>
	<p class="form-submit">
		<input type="submit" value="Save Note">
		<input id="note-reset" type="reset" value="Reset">
	</p>
</form>
<?php
	}
}

function kickpress_task_list( $args = array(), $tasks = null ) {
	echo kickpress_get_bookmark_list( $args, $tasks );
}

function kickpress_get_task_list( $args = array(), $tasks = null ) {
	if ( is_null( $tasks ) ) $tasks = kickpress_get_tasks( );

	$defaults = array(
		'style' => 'ul'
	);

	$args = wp_parse_args( $args, $defaults );

	extract( $args );

	if ( in_array( $style, array( 'ol', 'ul' ) ) ) {
		$container = $style;
		$element   = 'li';
	} else {
		$container = 'div';
		$element   = 'div';
	}

	if ( empty( $tasks ) ) {
		$html = sprintf( '<%1$s class="tasks-list"><%2$s class="task no-tasks">%3$s</%2$s></%1$s>', $container, $element, __( 'No tasks were found', 'kickpress' ) );
	} else {
		$html = '';
		$task_status_groups = array();

		foreach ( $tasks as $task ) {
            $post = get_post( $task->comment_post_ID );
			// Only show published posts
			if ( 'publish' != $post->post_status ) {
				continue;
			}
			$post_type = $post->post_type;
			$task_group = $task->comment_task_group;
			$status_group = ( $task->comment_progress < 100 ? 'todo' : 'done' );

			if ( ! isset( $task_groups[$task_group] ) ) {
				$task_groups[$task_group] = array();
			}
			if ( ! isset( $task_groups[$task_group][$status_group] ) ) {
				$task_groups[$task_group][$status_group] = array();
			}
			if ( ! isset( $task_groups[$task_group][$status_group][$post_type] ) ) {
				$task_groups[$task_group][$status_group][$post_type] = '';
			}

			$task_groups[$task_group][$status_group][$post_type] .= sprintf( '<%s id="task-%s" class="task post-type-%s">', $element, $task->comment_ID, $post->post_type );

			$task_groups[$task_group][$status_group][$post_type] .= sprintf(
				'<span class="task-title task-%s"><a href="%s" target="_blank">%s</a></span> <span class="task-progress">%s%%</span>',
				$status_group,
				get_permalink( $post->ID ),
				$post->post_title,
				$task->comment_progress
			);

			$task_groups[$task_group][$status_group][$post_type] .= sprintf( '</%s><!-- .task -->', $element );
		}

		foreach ( $task_groups as $current_task_group => $current_status_groups ) {
			$html .= sprintf( '<%s class="tasks-list">', $container );
			foreach ( $current_status_groups as $current_status_group => $current_post_types ) {
				foreach ( $current_post_types as $current_post_type => $current_task ) {
					$html .= $current_task;
				}
			}
			$html .= sprintf( '</%s><!-- .tasks-list --><hr>', $container );
		}
	}

	return $html;
}

function kickpress_weighted_average( $vars ) {
	$count = array_sum( $vars );
	$total = 0;

	if ( $count ) {
		foreach ( $vars as $weight => $value ) {
			$total += $weight * $value;
		}

		return $total / $count;
	}

	return null;
}

?>
