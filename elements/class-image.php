<?php

class kickpress_image extends kickpress_form_elements {
	public function element($params) {
		//(file_exists($src))?$this->src=$src:die('Invalid parameter '.$src);

		$html = sprintf('
			<input type="image" id="%1$s" name="%2$s" value="%3$s" src="%4$s"%5$s%6$s alt="%4$s" />',
			$params['id'],
			$params['name'],
			$params['value'],
			$params['caption'],
			(isset($params['class'])?' class="'.$params['class'].'"':''),
			$params['properties']
		);
		return $html;
	}
}

?>