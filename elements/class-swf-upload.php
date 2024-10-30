<?php

class kickpress_swf_upload extends kickpress_form_elements {
	public function element($params) {
		$_SESSION['javascript']['swfu'] = sprintf('
			var swfu;
			
			var init_uploader = function() {
				swfu = new SWFUpload({
					upload_script : "/media/upload/new/?sessionid=%1$s",
					target : "SWFUploadTarget",
					flash_path : "/js/SWFUpload.swf",
					allowed_filesize : 30720,	// 30 MB
					allowed_filetypes : "*.*",
					allowed_filetypes_description : "All files...",
					browse_link_innerhtml : "Browse",
					upload_link_innerhtml : "Upload queue",
					browse_link_class : "swfuploadbtn browsebtn",
					upload_link_class : "swfuploadbtn uploadbtn",
					flash_loaded_callback : "swfu.flashLoaded",
					upload_file_queued_callback : "fileQueued",
					upload_file_start_callback : "uploadFileStart",
					upload_progress_callback : "uploadProgress",
					upload_file_complete_callback : "uploadFileComplete",
					upload_file_cancel_callback : "uploadFileCancelled",
					upload_queue_complete_callback : "uploadQueueComplete",
					upload_error_callback : "uploadError",
					upload_cancel_callback : "uploadCancel",
					auto_upload : false			
				});
			};
			
			window.onload = init_uploader();
			',
			session_id()
		);
		
		$html = sprintf('
			<tr valign="top" class="form-group">
				<th scope="row"><label for="%1$s"> %2$s</label></th>
				<td>
					<input type="file" name="Filedata" id="Filedata" />
					<input type="submit" value="upload test" />
					%3$s
				</td>
			</tr>',
			$params['id'],
			$params['caption'],
			(isset($params['notes'])?'<p class="help-block">'.$params['notes'].'</p>':'')
		);
		return $html;
	}
}

?>