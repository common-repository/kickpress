jQuery(document).ready(function($) {
	function updatePostTable() {
		$('.wp-list-table.series-posts tbody tr:even').addClass('alternate');
		$('.wp-list-table.series-posts tbody tr:odd').removeClass('alternate');

		$('.wp-list-table.series-term-order .row-actions up').show();
		$('.wp-list-table.series-term-order .row-actions down').show();

		$('.wp-list-table.series-term-order tbody .term-order-up').removeClass('disabled');
		$('.wp-list-table.series-term-order tbody .term-order-down').removeClass('disabled')

		$('.wp-list-table.series-term-order tbody tr:first .term-order-up').addClass('disabled');
		$('.wp-list-table.series-term-order tbody tr:last .term-order-down').addClass('disabled');

		$('.wp-list-table.series-term-order tbody .term-order').each(function(index) {
			$(this).text(index + 1);
			$(this).next('input').attr('name', $('#series-term-order-name').attr('name'));
		});

		$('.wp-list-table.series-term-order .term-order-up').unbind('click');
		$('.wp-list-table.series-term-order .term-order-up').click(function(event) {
			if (!$(this).hasClass('disabled')) {
				var row = $(this).parents('tr').first();
				row.after(row.prev().first());
				updatePostTable();
			}

			return false;
		});

		$('.wp-list-table.series-term-order .term-order-down').unbind('click');
		$('.wp-list-table.series-term-order .term-order-down').click(function(event) {
			if (!$(this).hasClass('disabled')) {
				var row = $(this).parents('tr').first();
				row.before(row.next().first());
				updatePostTable();
			}

			return false;
		});

		$('.wp-list-table.series-term-order .remove-post').unbind('click');
		$('.wp-list-table.series-term-order .remove-post').click(function(event) {
			if (confirm('Are you sure you want to remove this post?')) {
				$(this).parents('tr').first().remove();
			}

			return false;
		});

		updateTaskList();
	}

	function updateTaskList() {
		$('.series-term-order .task-preview')
		.unbind('mouseenter').mouseenter(function(event) {
			$(this).children('.task-actions').css('visibility', 'visible');
		}).unbind('mouseleave').mouseleave(function(event) {
			$(this).children('.task-actions').css('visibility', 'hidden');
		});

		$('.series-term-order .edit-task')
		.unbind('click').click(function(event) {
			var preview = $(this).parents('.task-preview').first();
			var field   = preview.next('.task-field');

			preview.hide();
			field.show();
			field.children('.task-content').focus();

			return false;
		});

		$('.series-term-order .done-task')
		.unbind('click').click(function(event) {
			var field   = $(this).parents('.task-field').first();
			var preview = field.prev('.task-preview');
			var content = field.children('.task-content').val();

			if (content == '') {
				alert('Please enter a task description.');
				field.children('.task-content').focus();
				return false;
			}

			preview.children('.task-content').html(content);

			preview.show();
			field.hide();

			return false;
		});

		$('.series-term-order .cancel-task')
		.unbind('click').click(function(event) {
			var field   = $(this).parents('.task-field').first();
			var preview = field.prev('.task-preview');

			var content = preview.children('.task-content').html();

			if (content == '') {
				$(this).parents('.task-wrap').first().remove();
			} else {
				field.children('.task-content').val(content);

				preview.show();
				field.hide();
			}

			return false;
		});

		$('.series-term-order .remove-task')
		.unbind('click').click(function(event) {
			if (confirm('Are you sure you want to remove this task?')) {
				$(this).parents('.task-wrap').first().remove();
			}

			return false;
		});

		$('.series-term-order .add-task')
		.unbind('click').click(function(event) {
			var blank = $(this).prev('.blank-task');
			var clone = blank.clone();

			clone.removeClass('blank-task');
			clone.insertBefore(blank);
			clone.children('.task-preview').hide();
			clone.children('.task-field').show();
			clone.children('.task-field .task-content').focus();

			updateTaskList();

			return false;
		});
	}

	function sendSearch(args) {
		if (typeof args == 'string')
			args = parseQuery(args);
		else if (typeof args == 'undefined')
			args = {};

		args['action'] = 'search_posts';
		args['s'] = $('#add-series-post-search').val();
		args['type'] = $('#add-series-post-type').val();

		$('#add-series-post-search').attr('disabled', 'disabled');
		$('#add-series-post-button').attr('disabled', 'disabled');
		$('#add-series-post-clear').attr('disabled', 'disabled');

		$('#add-series-post-spinner').show();

		console.log(ajaxurl, args);

		$.get(ajaxurl, args, function(response) {
			$('#add-series-post-search').attr('disabled', false);
			$('#add-series-post-button').attr('disabled', false);
			$('#add-series-post-clear').attr('disabled', false);

			$('#add-series-post-spinner').hide();

			$('#add-series-post-results').html(response);

			$('#add-series-post-results .remove-post').html('&#x2714; Add')
				.toggleClass('add-post remove-post').click(function(event) {
				var row = $(this).parents('tr').first();

				$('.wp-list-table.series-term-order tbody').first().append(row);
				$('.wp-list-table.series-term-order tr.no-items').remove();

				var prefix = $('#series-post-task-prefix').val();

				var content = row.find('textarea.task-content');

				content.attr('name', prefix + content.attr('name'));

				$(this).html('&#x2718; Remove')
					.toggleClass('add-post remove-post');

				updatePostTable();

				return false;
			});

			$('#add-series-post-results .pagination-links a').click(function(event) {
				if (!$(this).hasClass('disabled'))
					sendSearch(this.search.substr(1));

				return false;
			});
		});
	}

	function parseQuery(query) {
		var params = {};

		var pairs = query.split('&');

		for ( var index in pairs ) {
			var pair = pairs[index].split('=');
			params[pair[0]] = pair[1];
		}

		return params;
	}

	$('#add-series-post-search').keypress(function(event) {
		if (event.which == 13) {
			sendSearch();
			return false;
		}
	});

	$('#add-series-post-button').click(function(event) {
		sendSearch();
	});

	$('#add-series-post-clear').click(function(event) {
		$('#add-series-post-search').val('');
		$('#add-series-post-results').html('');
	});

	updatePostTable();
});