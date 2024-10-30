<?php

class kickpress_form_fields extends kickpress_form_elements {
	public function element($params) {
		if ( empty($params['value']) ) {
			$params['value'] = "{
				'form_field_count':'1',
				'fields':[{
					'name':'name',
					'caption':'Caption',
					'default':'',
					'class':'',
					'notes':''
				}]
			}";
		}

		$current_form_fields = html_entity_decode($params['value']);

		$element_html = sprintf('
			<tr valign="top" class="form-group">
				<th scope="row"><label for="%1$s"> %4$s</label></th>
				<td>
					<textarea id="%1$s" name="%2$s" cols="80" %5$s%6$s>%3$s"</textarea>
					<input type="hidden" id="form_field_id" name="form_field_id" value="%1$s" />
				</td>
			</tr>',
			$params['id'],
			$params['name'],
			stripslashes($current_form_fields),
			$params['caption'],
			(isset($params['class'])?' class="'.$params['class'].'"':''),
			$params['properties'],
			(isset($params['notes'])?'<p class="help-block">'.$params['notes'].'</p>':'')
		);

		$html = sprintf('
			<tr valign="top" class="form-group">
				<td colspan="2">
					<style type="text/css">
						#dynamic-form-fields a.remove-form-field, #dynamic-form-fields a.remove-option {font-size:9px;color:#E6584D;text-shadow:none;border:1px solid #E6584D;margin:0;padding:0 5px 2px 5px;background-color:#FAB9AD;}
						#dynamic-form-fields div.new-form-field a, #dynamic-form-fields div.new-option a{color:#6FB758;text-shadow:none;}
						#dynamic-form-fields div.new-form-field, #dynamic-form-fields div.new-option{border:1px solid #6FB758;background-color:#E6EFC2;margin:3px;padding:3px;}

						#dynamic-form-fields a{text-decoration:none;}
						#dynamic-form-fields a img{vertical-align: middle;}
						#dynamic-form-fields .handle {cursor: move;}
						#dynamic-form-fields .toggle-form {-moz-background-clip:border;-moz-background-inline-policy:continuous;-moz-background-origin:padding;background:transparent url(/wp-admin/images/fav-arrow.gif) no-repeat scroll 0 -1px;float:right;height:26px;width:26px;}
						#dynamic-form-fields .options .toggle-form {background:transparent url(images/menu-bits.gif) no-repeat 0 -110px;height:26px;width:23px;}

						#dynamic-form-fields .form-field {margin:0 0 20px 0;}
						#dynamic-form-fields .form-field-name {-moz-border-radius-topleft:8px;-moz-border-radius-topright:8px;cursor:pointer;font-size:13px;background:#636363 url(/wp-admin/images/fav.png) repeat-x 0 0;border:1px solid #636363;color:#FFFFFF;}
						#dynamic-form-fields .form-field-name h3 {font-size:13px;height:19px;margin:0;overflow:hidden;padding:5px 12px;white-space:nowrap;}
						#dynamic-form-fields .options {-moz-border-radius-bottomleft:8px;-moz-border-radius-bottomright:8px;padding:5px 5px 0 5px;border-style:none solid solid;border-width:0 1px 1px;background-color:#F1F1F1;border-color:#DDDDDD;min-height:90px;}

						#dynamic-form-fields .option {-moz-border-radius-bottomleft:6px;-moz-border-radius-bottomright:6px;-moz-border-radius-topleft:6px;-moz-border-radius-topright:6px;margin:5px;overflow:hidden;background-color:#FFFFFF;border:1px solid #DFDFDF;line-height:1;}
						#dynamic-form-fields .option-name {-moz-user-select:none;font-size:12px;font-weight:bold;overflow:hidden;-moz-background-clip:border;-moz-background-inline-policy:continuous;-moz-background-origin:padding;background:#DFDFDF url(/wp-admin/images/gray-grad.png) repeat-x left top;}
						#dynamic-form-fields .option-name h4 {padding:5px 12px;margin:0;overflow:hidden;white-space:nowrap;font-size:13px;display:block;font-weight:bold;color:#000000;}

						#dynamic-form-fields .option-settings {padding:10px;}

					</style>
					<script type="text/javascript">
						jQuery(document).ready(
							function($){
								var form = %1$s;
								parseFormFields(form);
							}
						);
					</script>
					<div id="dynamic-form-fields"></div>
				</td>
			</tr>
			%2$s',
			stripslashes($current_form_fields),
			$element_html
		);

		return $html;
	}
}

?>