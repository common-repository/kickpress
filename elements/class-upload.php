<?php

class kickpress_upload extends kickpress_form_elements {
	public function element($params) {
		if ( $params['post_id'] == 0 ) {
			$html = sprintf('
				<tr valign="top" class="form-group">
					<th scope="row"><label for="async-upload"> %1$s</label></th>
					<td>
						<input type="file" id="async-upload" name="async-upload"%2$s%3$s />
						<p class="help-block">Choose a file to upload, after a file has been uploaded, you can add a title and description.</p>
					</td>
				</tr>',
				$params['caption'],
				(isset($params['class'])?' class="'.$params['class'].'"':''),
				$params['properties']
			);
		} else {
			$html = sprintf('
				<tr valign="top" class="form-group">
					<th scope="row"><label> %2$s</label></th>
					<td>
						<a href="%1$s">%1$s</a>
					</td>
				</tr>',
				$params['value'],
				$params['caption']
			);
		}
		return $html;
	}
}

?>