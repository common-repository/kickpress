<?php

class kickpress_post_terms extends kickpress_form_elements {
	public function input( $params ) {
		extract( $params, EXTR_SKIP );
		
		$params['name'] = $name = sprintf(
			'data[%s][%d][tax_input][%s][]',
			$post_type,
			$post_id,
			$taxonomy
		);
		
		if ( empty( $control ) ) $control = 'ajax';
		
		$tax = get_taxonomy( $taxonomy );
		
		if ( 'list' == $control ) {
			$terms = get_terms( $taxonomy, array(
				'hide_empty' => false
			) );
			
			$post_terms = wp_get_post_terms( $post_id, $taxonomy, array(
				'fields' => 'ids'
			) );
		} else {
			$terms = wp_get_post_terms( $post_id, $taxonomy );
		}
		
		$params['name'] = "tax_input[{$taxonomy}][]";
		
		$conf = esc_attr( __( 'Are you sure you want to remove this post?', 'kickpress' ) );
		
		$html = self::_html_input( 'hidden', null, $name, array(
			'class' => 'ajax_search_name'
		) );
		
		$html .= self::_html_input( 'hidden', null, $conf, array(
			'class' => 'ajax_search_conf'
		) );
		
		$html .= self::_html_input( 'hidden', $name, '0' );
		
		$html .= '<ul id="' . $taxonomy . '_list" class="ajax_search_list">';
		
		if ( empty( $terms ) ) {
			$html .= self::_html_tag( 'li', 'none', array(
				'class' => 'empty'
			) );
		}
		
		foreach ( $terms as $term ) {
			$input  = self::_html_input( 'checkbox', $name, $term->term_id, array(
				'id'      => $taxonomy . '_list_' . $term->term_id,
				'checked' => in_array( $term->term_id, $post_terms ) || 'ajax' == $control
			) );
			
			$input .= self::_html_label( $term->name, array(
				'for' => $taxonomy . '_list_' . $term->term_id
			) );
			
			$html .= self::_html_tag( 'li', $input );
		}
		
		$html .= '</ul>';
		
		if ( 'ajax' == $control ) {
			$html .= self::_html_input( 'text', null, null, array(
				'id'    => $taxonomy,
				'class' => 'ajax_search_input'
			) );
			
			$cancel = self::_html_tag( 'a', 'X', array(
				'href'  => kickpress_api_url( array(
					'page'      => 1,
					'post_type' => $post_type,
					'sort'      => array( 'title' => 'asc' ),
					'view'      => $taxonomy
				) ),
				'class' => 'ajax_search_cancel'
			) );
			
			$search = self::_html_tag( 'div', '&nbsp;', array(
				'id'    => $taxonomy . '_result',
				'class' => 'ajax_search_result'
			) );
			
			$html .= self::_html_tag( 'div', $cancel . $search, array(
				'id'    => $taxonomy . '_window',
				'class' => 'ajax_search_window'
			) );
			
			wp_enqueue_script( 'kickpress_ajax_search',
				plugins_url( 'kickpress/includes/js/kickpress-ajax-search.js' ) );
		}
		
		return $html;
	}
}

?>