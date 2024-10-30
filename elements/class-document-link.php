<?php

class kickpress_document_link extends kickpress_form_elements {
	public function element($params) {
		$html = sprintf('
			<a href="%4$s" class="file%2$s"%3$s>
				%1$s
			</a>',
			$params['value'],
			(isset($params['class'])?' '.$params['class']:''),
			$params['properties'],
			$params['_wp_attached_file']
		);
		return $html;
	}
}

?>