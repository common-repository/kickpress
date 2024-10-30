<?php

class kickpress_hyperlink extends kickpress_form_elements {
	public function element($params) {

		if ( ! empty( $params['value'] ) )
			$content_link = '<span class="blue hyperlink"><a href="' . $params['value'] . '" target="_blank" class="button">Follow link &rarr;</a></span>';
		else
			$content_link = '';

		$html = sprintf('
			<tr valign="top" class="form-group">
				<th colspan="2" scope="row"><label for="%1$s"> %4$s</label></th>
			</tr>
			<tr valign="top" class="form-group">
				<td colspan="2">
					<span class="hyperlink-input">
						<input type="text" id="%1$s" name="%2$s" value="%3$s"%5$s%6$s />
						%7$s
						%8$s
					</span>
				</td>
			</tr>',
			$params['id'],
			$params['name'],
			$params['value'],
			$params['caption'],
			(isset($params['class'])?' class="'.$params['class'].'"':''),
			$params['properties'],
			$content_link,
			(isset($params['notes'])?'<p class="help-block">'.$params['notes'].'</p>':'')
		);
		return $html;
	}
}

?>