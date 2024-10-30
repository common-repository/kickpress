<?php

class kickpress_related_posts extends kickpress_form_elements {
	public function input( $params ) {
		extract( $params, EXTR_SKIP );
		
		$params['name'] = $name = sprintf(
			'data[%s][%d][rel_input][%s][]',
			$post_type,
			$post_id,
			$relationship
		);
		
		$rel = kickpress_get_relationship( $relationship );
		
		$search_type = $post_type == $rel->source_type
		             ? $rel->target_type : $rel->source_type;
		
		if ( 0 < $post_id )
			$posts = kickpress_get_related_posts( $post_id, $relationship );
		else
			$posts = array();
		
		if ( is_wp_error( $posts ) )
			$posts = array();
		
		$conf = esc_attr( __( 'Are you sure you want to remove this post?', 'kickpress' ) );
		
		$html = self::_html_input( 'hidden', null, $name, array(
			'class' => 'ajax_search_name'
		) );
		
		$html .= self::_html_input( 'hidden', null, $conf, array(
			'class' => 'ajax_search_conf'
		) );
		
		$html .= self::_html_input( 'hidden', $name, '0' );
		
		$html .= '<ul id="' . $relationship . '_list" class="ajax_search_list">';
		
		foreach ( (array) $posts as $post ) {
			$input  = self::_html_input( 'checkbox', $name, $post->ID, array(
				'id' => $relationship . '_list_' . $post->ID,
				'checked' => true
			) ) . self::_html_label( $post->post_title, array(
				'for' => $relationship . '_list_' . $post->ID
			) );
			
			$html .= self::_html_tag( 'li', $input );
		}
		
		if ( empty( $posts ) ) {
			$html .= self::_html_tag( 'li', 'none', array(
				'class' => 'empty'
			) );
		}
		
		$html .= '</ul>';
		
		$html .= self::_html_input( 'text', null, null, array(
			'id'    => $relationship,
			'class' => 'ajax_search_input'
		) );
		
		$cancel = self::_html_tag( 'a', 'X', array(
			'href'  => kickpress_api_url( array(
				'page'      => 1,
				'post_type' => $search_type,
				'sort'      => array( 'title' => 'asc' ),
				'view'      => 'ajax'
			) ),
			'class' => 'ajax_search_cancel'
		) );
		
		$search = self::_html_tag( 'div', '&nbsp;', array(
			'id'    => $relationship . '_result',
			'class' => 'ajax_search_result'
		) );
		
		$html .= self::_html_tag( 'div', $cancel . $search, array(
			'id'    => $relationship . '_window',
			'class' => 'ajax_search_window'
		) );
		
		wp_enqueue_script( 'kickpress_ajax_search',
			plugins_url( 'kickpress/includes/js/kickpress-ajax-search.js' ) );
		
		return $html;
	}
}

?>