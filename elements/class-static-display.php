<?php

class kickpress_static_display extends kickpress_form_elements {
	public function element($params) {
		$html = sprintf('
			<tr valign="top" class="form-group">
				<th scope="row"> %4$s</th>
				<td>%3$s<input type="hidden" id="%1$s" name="%2$s" value="%3$s" /></td>
			</tr>',
			$params['id'],
			$params['name'],
			$params['value'],
			$params['caption']
		);

		return $html;
	}
}

?>