<?php

class kickpress_wysiwyg extends kickpress_form_elements {
	
	public function element( $params ) {
		$html = $this->_before_element
		      . '<td colspan="2">'
		      . $this->label( $params )
		      . '<td></tr><tr valign="top" class="form-group"><td colspan="2">'
		      . $this->input( $params )
		      . $this->notes( $params )
		      . '</td>'
		      . $this->_after_element;
		
		return $html;
	}
	
	public function input($params) {
		// http://soderlind.no/archives/2011/09/25/front-end-editor-in-wordpress-3-3/
		$settings = array(
			'textarea_name' => $params['name']
		);

		/*
		// default settings
		$settings =   array(
	    'wpautop' => true, // use wpautop?
	    'media_buttons' => true, // show insert/upload button(s)
	    'textarea_name' => $editor_id, // set the textarea name to something different, square brackets [] can be used here
	    'textarea_rows' => get_option('default_post_edit_rows', 10), // rows="..."
	    'tabindex' => '',
	    'editor_css' => '', // intended for extra styles for both visual and HTML editors buttons, needs to include the <style> tags, can use "scoped".
	    'editor_class' => '', // add extra class(es) to the editor textarea
	    'teeny' => false, // output the minimal editor config used in Press This
	    'dfw' => false, // replace the default fullscreen with DFW (needs specific css)
	    'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
	    'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
		);
		*/

		$editor_id = str_replace( array(' ', '_', '-'), '', $params['id'] );
		$value = html_entity_decode( $params['value'] );
		//$editor_id = $params['name'];

		ob_start();
		wp_editor( $value, $editor_id, $settings ); 
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	public function label( $params ) {
		return '<h3>' . $params['caption'] . '</h3>';
	}
}

?>