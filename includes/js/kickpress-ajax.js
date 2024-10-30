jQuery(document).ready( function($) {
	$('a.ajax-reload').on('click', function(e) {
		var wrapper = $('div#' + this.rel);
		
		if (wrapper.length > 0) {
			wrapper.animate({
				opacity: 0
			}, 500);
			
			wrapper.addClass('ajax-loading');
			
			var ts = new Date().getTime();
			
			wrapper.load(this.href + '?' + ts, function() {
				wrapper.animate({
					opacity: 1
				}, 500, function() {
					wrapper.removeClass('ajax-loading');
					$(this).css('filter', 'none');
				});
			});
			
			this.blur();
		}
		
		e.preventDefault();
		
		return false;
	});
	
	$('a.ajax-append').live('click', function(e) {
		var wrapper = $('div#' + this.rel);
		
		if (wrapper.length > 0) {
			var container = $(this).parent();
			var ts = new Date().getTime();
			
			container.addClass('ajax-loading');
			$(this).fadeOut("slow");

			$.get(this.href + '?ts=' + ts, function(html) {
				//container.removeClass('ajax-loading');
				container.css('display', 'none');
				wrapper.append(html).resize();
			});
			
			this.blur();
		}
		
		e.preventDefault();
		return false;
	});
	
	$('a.masonry-append').live('click', function(e) {
		var wrapper = $('div#' + this.rel);
		
		if (wrapper.length > 0) {
			var container = $(this).parent();
			var ts = new Date().getTime();

			container.addClass('ajax-loading');
			$(this).fadeOut("slow");

			$.get(this.href + '?ts=' + ts, function(html) {
				container.removeClass('ajax-loading');
				wrapper.append(html).masonry( 'reload' ); //.masonry( 'appended', html );
			});
			
			this.blur();
		}
		
		e.preventDefault();
		return false;
	});
});