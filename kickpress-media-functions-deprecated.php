<?php
	if ( ! function_exists('get_space_allowed') ) {
		function get_space_allowed() {
			$space_allowed = get_option("blog_upload_space");
			if ( $space_allowed == false )
				$space_allowed = get_site_option("blog_upload_space");
			if ( empty($space_allowed) || ! is_numeric($space_allowed) )
				$space_allowed = 50;
	
			return $space_allowed;
		}
	}

	if ( ! function_exists('wp_load_image') ) {
		function wp_load_image($file) {
			if ( is_numeric($file) )
				$file = get_attached_file($file);
		
			if ( ! file_exists($file) )
				return sprintf(__("File '%s' doesn't exist?", 'kickpress'), $file);
		
			if ( ! function_exists('imagecreatefromstring') )
				return __('The GD image library is not installed.', 'kickpress');
		
			// Set artificially high because GD uses uncompressed images in memory
			@ini_set('memory_limit', '256M');
			$image = imagecreatefromstring(file_get_contents($file));
		
			if ( ! is_resource($image) )
				return sprintf(__("File '%s' is not an image.", 'kickpress'), $file);
		
			return $image;
		}
	}
	
	function kickpress_video_player($video) {
		$extra = '';
		// Break out the URL passed to this function (so $vid_info[0]=http, [1]=domain, etc
		$vid_info = split('/', $video['video_link']);
		if ( ! isset($video['autoplay']) )
			$video['autoplay'] = true;
		if ( ! isset($video['wrapper']) )
			$video['wrapper'] = true;
		
		// We're building a temporary string called vidstring. 
		// vidstring will hold the HTML as we build it based on the service
		// being used.	The next few lines contains items which are common
		// to all the services, or at least ignored if not directly used. 
 
		if ( strstr($vid_info[2], 'youtube.com') ) {
			// YouTube (Use browser URL, autoplays)
			preg_match('/v=.+$/', $video['video_link'], $vid_results);
			$vid_id = str_replace("v=", "", $vid_results[0]);
			$height = 385;
			$width = 480;
			if ( $video['autoplay'] )
				$autoplay = '1';
			else
				$autoplay = '0';
			$src = 'http://www.youtube.com/v/'.$vid_id.'&hl=en&fs=1&rel=0&color1=0x3a3a3a&color2=0x999999&autoplay='.$autoplay;
		} elseif ( strstr($vid_info[2], 'vimeo.com') ) {
			// Vimeo (Use browser URL)
			$vid_id = $vid_info[3];
			$height = 385;
			$width = 480;

			$html = sprintf('
				<div%5$s>
					<div class="video" style="height:%1$spx;width:%2$spx;margin:0 auto;">
						<iframe src="http://player.vimeo.com/video/%3$s" width="%2$s" height="%1$s" frameborder="0"></iframe>
					</div>
				</div><!-- /video-wrapper -->',
				$height,
				$width,
				$vid_id,
				$extra,
				($video['wrapper']?' class="video-wrapper"':'')
			);

			return $html;
		} elseif ( strstr($vid_info[2], 'video.google.com') ) {
			// Google (Use browser URL, autoplays)
			preg_match('/docid=.+$/', $video['video_link'], $vid_results);
			$vid_id = str_replace("docid=", "", $vid_results[0]);
			$height = 350;
			$width = 425;
			$src = 'http://video.google.com/googleplayer.swf?docId='.$vid_id.'&autoplay=1';
		} elseif ( strstr($vid_info[2], 'metacafe.com') ) {
			// MetaCafe (Use browser URL, autoplays)
			preg_match('/watch.+$/', $video['video_link'], $vid_results);
			$vid_id = $vid_results[0];
			preg_replace('/watch./', '', $vid_id);
			preg_replace('/.$/', '', $vid_id);
			$height = 345;
			$width = 400;
			$src = 'http://www.metacafe.com/fplayer/'.$vid_id.'.swf';
			$extra = 'flashVars="playerVars=autoPlay=yes"';
		} elseif ( strstr($vid_info[2], 'ifilm.com') ) {
			// iFilm (Use browser URL, autoplays)
			preg_match('/video.+$/', $video['video_link'], $vid_results);
			$vid_id = $vid_results[0];
			preg_replace('/video./', '', $vid_id);
			$height = 350;
			$width = 425;
			$src = 'http://ifilm.com/efp';
			$extra = 'flashVars="flvbaseclip='.$vid_id.'&ip=true" quality="high" name="efp" align="middle"'; 
		} elseif ( strstr($vid_info[2], 'dailymotion.com') ) {
			// Daily Motion (Use EMBED URL, autoplays)
			preg_match('/video.+$/', $video['video_link'], $vid_results);
			preg_match('/_.+$/', $video['video_link'], $vid_results2);
			$vid_id = $vid_results[0];
			$vid_id2 = $vid_results2[0];
			preg_replace('/video./', '', $vid_id);
			preg_replace($vid_id2, '', $vid_id);
			$height = 350;
			$width = 425;
			$src = 'http://www.dailymotion.com/swf/'.$vid_id.'&related=1';
			$extra = 'flashVars="autoStart=1 allowFullScreen="true"';
		} elseif ( strstr($vid_info[2], 'break.com') ) {
			// Break (use EMBED URL, autostarts)
			$height = 350;
			$width = 425;
			$src = $video['video_link'].'&autoplay=1';
		} elseif ( strstr($vid_info[2], 'shoutfile.com') ) {
			// Shoutfile (use EMBED URL, does not autostart)
			$height = 300;
			$width = 400;
			$src = $video['video_link'];
		} elseif ( strstr($vid_info[2], 'soapbox.msn.com') ) {
			// MSN Soapbox (use the LINK, autostarts)
			
			preg_match('/vid=.+$/', $video['video_link'], $vid_results);
			$vid_id = str_replace("vid=", "", $vid_results[0]);
			$height = 360;
			$width = 412;
			$src = 'http://images.soapbox.msn.com/flash/soapbox1_1.swf';
			$extra = 'flashvars="c=v&ap=true&v='.$vid_id.'"';
		} elseif ( ! empty($video['video_link']) ) {
			// Failed, unknown video service. 
			ob_clean();
			header('Location: '.$video['video_link']);
			exit();
		}

		if ( ! empty($video['video_width']) )
			$width = $video['video_width'];

		if ( ! empty($video['video_height']) )
			$height = $video['video_height'];

		$html = sprintf('
			<div%5$s>
				<div class="video" style="height:%1$spx;width:%2$spx;margin:0 auto;">
					<embed 
						enableJavascript="false" 
						allowScriptAccess="never" 
						allownetworking="internal" 
						type="application/x-shockwave-flash" 
						pluginspage="http://www.macromedia.com/go/getflashplayer" 
						src="%3$s"
						%3$s
						height="%1$s" width="%2$s">
					</embed>
				</div>
			</div><!-- /video-wrapper -->',
			$height,
			$width,
			$src,
			$extra,
			($video['wrapper']?' class="video-wrapper"':'')
		);
		
		return $html;
	}
?>