<?php

class kickpress_http extends kickpress_form_elements {
	public function element($params) {
		$html = sprintf('
			<tr valign="top" class="form-group">
				<th scope="row"><label>%4$s</label></th>
				<td>
					<a href="%3$s"%5$s%6$s rel="external" target="_blank">%3$s</a>
					<!-- input type="hidden" id="%1$s" name="%2$s" value="%3$s" / -->
				</td>
			</tr>',
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