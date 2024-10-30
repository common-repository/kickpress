<?php

class kickpress_mailto extends kickpress_form_elements {
	public function element($params) {
		$html = sprintf('
			<a href="mailto:%2$s"%3$s%4$s>%2$s</a>',
			$params['name'],
			$params['value'],
			(isset($params['class'])?' class="'.$params['class'].'"':''),
			$params['properties']
		);
		return $html;
	}
}

?>