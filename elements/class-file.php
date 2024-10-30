<?php

class kickpress_file extends kickpress_form_elements {
	public function element($params) {
	 	if ( has_post_thumbnail( $params['post_id'] ) )
			$html = '<tr valign="top" class="form-group"><th scope="row"></th><td>'.get_the_post_thumbnail( $params['post_id'], array( 100, 100 ) ).'</td></tr>';		
		else
			$html = '';

		if ( $attach_id = intval( $params['value'] ) ) {
			$file = get_post( $attach_id );
			$html .= sprintf('
				<tr valign="top" class="form-group">
					<th scope="row"><label for="%1$s">%3$s</label></th>
					<td>
						<div>
							<a href="%6$s" target="_blank">%7$s</a>
							<em>(%8$s)</em>
						</div>
						<input id="%1$s" type="file" name="%2$s"%4$s%5$s />
						%9$s
					</td>
				</tr>',
				$params['id'],
				$params['name'],
				$params['caption'],
				(isset($params['class'])?' class="'.$params['class'].'"':''),
				$params['properties'],
				$file->guid,
				basename( $file->guid ),
				$file->post_mime_type,
				(isset($params['notes'])?'<p class="help-block">'.$params['notes'].'</p>':'')
			);
		} else {
			$html .= sprintf('
				<tr valign="top" class="form-group">
					<th scope="row"><label for="%1$s">%3$s</label></th>
					<td>
						<input id="%1$s" type="file" name="%2$s"%4$s%5$s />
						%6$s
					</td>
				</tr>',
				$params['id'],
				$params['name'],
				$params['caption'],
				(isset($params['class'])?' class="'.$params['class'].'"':''),
				$params['properties'],
				(isset($params['notes'])?'<p class="help-block">'.$params['notes'].'</p>':'')
			);
		}
		
		return $html;
	}
}

?>