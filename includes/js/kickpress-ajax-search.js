jQuery(document).ready(function($) {
	$('.ajax_search_window')
		.css('position', 'absolute')
		.css('visibility', 'hidden');
	
	$('.ajax_search_input').keyup( function(e) {
		var search = $(this).val();
		
		var ajax_window = $(this).siblings('.ajax_search_window');
		var ajax_cancel = ajax_window.children('.ajax_search_cancel');
		var ajax_result = ajax_window.children('.ajax_search_result');
		
		if ( '' == search ) {
			ajax_window.css('visibility', 'hidden');
		} else {
			var url = $(ajax_cancel).attr('href') + '?s=' + escape(search);
			
			$.get(url, function(data) {
				ajax_result.html(data);
				ajax_window.css('visibility', 'visible');
			});
		}
		
		// ajax_window.append($('<div>').text(url));
	});
	
	$('.ajax_search_list a').on('click', function(e) {
		var ajax_list = $(this).parents('.ajax_search_list');
		var conf = ajax_list.siblings('.ajax_search_conf').val();
		
		if (confirm(conf))
			$(this).parent().remove();
		
		e.preventDefault();
		return false;
	});
	
	$('.ajax_search_window a').live('click', function(e) {
		var ajax_window = $(this).parents('.ajax_search_window');
		
		if ($(this).hasClass('ajax_search_cancel')) {
			ajax_window.css('visibility', 'hidden');
		} else {
			var post_id = this.rel;
			 
			if (0 < post_id) {
				var ajax_list  = ajax_window.siblings('.ajax_search_list');
				var ajax_input = ajax_window.siblings('.ajax_search_input');
		
				var input_id = ajax_list.attr('id') + '_' + post_id;
				var input_name = ajax_window.siblings('.ajax_search_name').val();
				
				ajax_list.append(
					$('<li>').append($('<input>', {
						'id':    input_id,
						'type':  'checkbox',
						'name':  input_name,
						'value': post_id,
						'checked' : 'checked'
					})).append($('<label>', {
						'for': input_id
					}).text($(this).text()))
				).children('.empty').remove();
				
				ajax_input.val('');
			}
		}
		
		ajax_window.css('visibility', 'hidden');
		
		e.preventDefault();
		return false;
	});
});