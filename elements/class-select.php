<?php

class kickpress_select extends kickpress_form_elements {
	public function element($params) {
		//if ( ! $stored_value = get_option($params['id']) )
			//$stored_value = $params['default'];
		if ( ! isset($params['options']) )
			$params['options'] = array();

		if ( isset($params['list']) ) {
			// options are in an xml list
			$file_path = sprintf("%s/lists/%s.xml", dirname(__FILE__), $params['list']);
			if ( file_exists($file_path) ) {
				$select_array = simplexml_load_file($file_path);
	
				foreach ( $select_array->option as $list_value )
					$params['options'][(string) $list_value['value']] = (string) $list_value;
			}
		} elseif ( isset($params['builtin']) ) {
			$params['options'] = kickpress_get_builtin_options($params['builtin']);
		} elseif ( isset($params['post_types']) ) {
			global $kickpress_post_types;
			foreach ( $kickpress_post_types as $key=>$value ) {
				$post_type_name = (string) $value['post_type'];
				//if ( in_array($post_type_name, $_SESSION['project']['addable_modules']) ) {
				/*
					if ( isset($_SESSION['permissions'][$post_type_name]) && in_array('edit', $_SESSION['permissions'][$post_type_name]) ) {
						$params['options'] = array_merge($params['options'], array($post_type_name=>$post_type_name));
					}
				*/
				//}
			}
			// add options that are in the modules directory
			$dir = opendir($_SESSION['path']['post_type']);

			while ( $post_type = readdir($dir) ) {
				if ( preg_match("/[.]php$/", $post_type) )
					$params['options'] = array_merge($params['options'], array(substr($post_type, 0, -4)=>ucwords(str_replace(array("_",".php"), array(" ",""), $post_type))));
			}
			closedir($dir);
		} elseif ( isset($params['templates']) ) {
			//$sql = "SELECT name, description FROM templates";
			//$field['options'] = $this->sql_select_element($sql, 'description', 'name');
			
			// options are templates in a directory
			$dir = opendir($_SESSION['path']['project']."/templates/");
			while ( $template = readdir($dir) ) {
				if ( preg_match("/[.]xml$/", $template) ) {
					$template_name = str_replace(array("_",".xml"), array(" ",""), $template);
					$params['options'] = array_merge($params['options'], array($template_name=>$template_name));
				}
			}
			closedir($dir);
		}

		if ( count($params['options']) ) {
			$select_html = $this->option_list($params);

			$html = sprintf('
				<tr valign="top" class="form-group">
					<th scope="row"><label for="%1$s"> %2$s</label></th>
					<td>
						%3$s
						%4$s
					</td>
				</tr>',
				$params['id'],
				$params['caption'],
				$select_html,
				(isset($params['notes'])?'<p class="help-block">'.$params['notes'].'</p>':'')
			);
			return $html;
		}
	}
}

?>