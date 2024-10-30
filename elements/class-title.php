<?php

class kickpress_title extends kickpress_form_elements {
	public function element($params) {
		$panel_class = ' in';
		$link_class = '';

		if ( !empty( $GLOBALS['quick_edit'] ) && $GLOBALS['quick_edit'] ) {
			$quick_edit = true;
		} else {
			$quick_edit = false;
		}

		if ( !empty ( $params['minmax'] ) && 'open' != strtolower( $params['minmax'] ) ) {
			$panel_class = '';
			$link_class = 'collapsed';
		}

		$html = '
							</table><!-- /.form-table -->';

		// If a previous panel has been opened, close it
		if ( $quick_edit ) {
			$html .= sprintf('
							<h3 class="quick-edit-title">%1$s</h3>
							<table class="form-table">
								%2$s',
				$params['caption'],
				(isset($params['notes'])?'<div class="alert alert-info">'.$params['notes'].'</div>':'')
			);
		} else {
			if ( ! is_admin() ) {
				$html .= '
						</div><!-- /.panel-body -->
					</div><!-- /.panel -->';
			}

			if ( ! is_admin() && ! empty( $params['column'] ) ) {
				$html .= '
				</div><!-- /.panel-group -->
				<div id="' . $params['column'] . '" class="panel-group">';

				$GLOBALS['column'] = $params['column'];
			}

			if ( ! is_admin() ) {
				$html .= sprintf('
					<div class="panel panel-defualt">
						<div class="panel-heading">
							<h3 class="panel-title"><a href="#collapse-%3$s" data-toggle="collapse" data-parent="#%1$s" class="%5$s">%2$s</a></h3>
						</div>
						<div id="collapse-%3$s" class="panel-body panel-collapse collapse%4$s">',
					$GLOBALS['column'],
					$params['caption'],
					sanitize_title_with_dashes( $params['caption'] ),
					$panel_class,
					$link_class
				);
			} else {
				$html .= sprintf('
					<h3 class="panel-title">%1$s</h3>',
					$params['caption']
				);
			}

			$html .= sprintf('
							<table class="form-table">
								%1$s',
				(isset($params['notes'])?'<div class="alert alert-info">'.$params['notes'].'</div>':'')
			);
		}

		// We are opening a new panel, we will have to close it at some point
		$GLOBALS['panel_opened'] = true;

		return $html;
	}
}

?>