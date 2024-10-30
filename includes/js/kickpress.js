jQuery(document).ready(
	function($){

/*
$('form').submit(function() {
$.get(this.action, $(this).find('input').serialize(),
function(data)
{
alert(data);
});
return false;
});
*/

	$('div.categories-toolbar select').live('change', function(e) {
		var wrapper = $('div#' + $(this).attr('title'));
		var selected_value = this[this.selectedIndex].value;
		if(wrapper.length > 0 && selected_value.length > 0) {
	    var targetOffset = wrapper.offset().top;
			var targetTop = $(window).scrollTop();
	  	var ts = new Date().getTime();

	    //wrapper.load(this.href + '?' + ts);
	    wrapper.animate({opacity: 0}, 500);
	    $('div.dynamic-updates').addClass('dynamic-loader');
			if((targetOffset <= targetTop)) {
	    	$('html,body').animate({scrollTop: targetOffset}, 500);
			}

	    wrapper.load(selected_value + '?' + ts, function() {
	      wrapper.animate({opacity: 1}, 500, function() {
	        $('div.dynamic-updates').removeClass('dynamic-loader');
	        $(this).css('filter', 'none');
	      });
	    });

	  	this.blur();
	  	e.preventDefault();
	  	return false;
	  }
	});

	$('div.categories-checkboxes input:checkbox').live('change', function(e) {
		var wrapper = $('div#' + $(this).attr('title'));
		var selected_value = $(this).val();

		if($(this).is(':checked'))
		{
		  // Not sure what needs to be here yet.
		}
		else
		{
			var replace_string = $(this).attr('name')+'/'+$(this).attr('class')+'/';
			var replace_string_parts = selected_value.split(replace_string);
			selected_value = replace_string_parts.join('');
		}

		if(wrapper.length > 0 && selected_value.length > 0) {
	    var targetOffset = wrapper.offset().top;
			var targetTop = $(window).scrollTop();
	  	var ts = new Date().getTime();

	    //wrapper.load(this.href + '?' + ts);
	    wrapper.animate({opacity: 0}, 500);
	    $('div.dynamic-updates').addClass('dynamic-loader');
			if((targetOffset <= targetTop)) {
	    	$('html,body').animate({scrollTop: targetOffset}, 500);
			}

	    wrapper.load(selected_value + '?' + ts, function() {
	      wrapper.animate({opacity: 1}, 500, function() {
	        $('div.dynamic-updates').removeClass('dynamic-loader');
	        $(this).css('filter', 'none');
	      });
	    });
	  }
	});

		$('select#nav-lookup').live('change', navLookup);
		$('input.swap-focus').live('blur', checkBlur);
		$('input.swap-focus').live('focus', checkFocus);
		$('input.toggle-select-column').live('click', togglePermissionColumns);
		$('input.toggle-select-row').live('click', togglePermissionRows);
		
		if ($('input.datepicker').length) {
		  $.datepicker.setDefaults({changeMonth: true, changeYear: true, showOn: 'both', buttonImageOnly: true, buttonImage: '/wp-content/plugins/kickpress/includes/images/icons/calendar.png', buttonText: 'Calendar'});
    	}
		
		if ($('div#map_canvas').length) {
    		map = new google.maps.Map(document.getElementById("map_canvas"), { mapTypeId: google.maps.MapTypeId.ROADMAP });
    	}
		
		$('div.site-main a.close-window').live('click', closeWindow);

		$('input#people_new_first_name').live('keyup', getDisplayName);
		$('input#people_new_middle_name').live('keyup', getDisplayName);
		$('input#people_new_maiden_name').live('keyup', getDisplayName);
		$('input#people_new_last_name').live('keyup', getDisplayName);

		$('a.swatch').live('click', colorSwatch);
		$('a.close-window').live('click', closeWindow);
		//$('a.open-form').live('click', openForm);
		//$('a.close-form').live('click', closeForm);
		//$('form.site-form').live('submit', saveForm);
		//$('button.reload').live('click', reloadSection);
		$('a.reloadup').live('click', reloadup);


		$('a.load-more').live('click', loadMore);
/*
    $("li.page_item a,li.cat-item a").each(function(){
    	if($(this).next('ul').length){
    		$(this).before('<a class="toggle_list expandable" href="#">&nbsp;&nbsp;&nbsp;</a>');
    	}
    });
*/
    //$("li.page_item > ul,li.cat-item > ul").addClass('hidden');
    $('a.toggle_list').addClass('collapse');
		$('a.toggle_list').live('click', toogleList);
	}
);

var toogleList = function(e) {
	var targetContent = jQuery('ul:first', this.parentNode);

	if (targetContent.css('display') == 'none') {
    jQuery(this).addClass('collapse');
		targetContent.slideDown();
	} else {
    jQuery(this).removeClass('collapse');
		targetContent.slideUp();
	}

  this.blur();
	e.preventDefault();
  return false;
};

var reloadup = function(e) {
  $("add_media").href = "/wp-admin/media-upload.php";
  /*$("add_media").location.reload();*/
  $("add_media").src = "/wp-admin/media-upload.php";
  window.frames["add_media"].window.location.reload(true);
	return false;
};

var togglePermissionColumns = function(e) {
	var checked_status = this.checked;
	var checked_class = 'input.'+this.id;
	jQuery(checked_class).each(function(){
		this.checked = checked_status;
	});
};

var togglePermissionRows = function(e) {
	var checked_status = this.checked;
	var checked_class = 'input.'+this.id;
	jQuery(checked_class).each(function(){
		this.checked = checked_status;
	});
};

var openForm = function(e) {
  // edit-module
  // data-people-view
	var wrapper = jQuery('div#' + this.rel);
	wrapper.load(this.href).slideDown('slow');
  return false;
};

var closeForm = function(e) {
	var wrapper = jQuery('div#' + this.rel);
	wrapper.html('').slideUp('slow');
  return false;
};

var saveForm = function(e) {
	var wrapper = jQuery('div#' + this.rel);
	var formObject = jQuery(this).parents('form:first');
	var formAction = formObject.attr('action');
  jQuery.post(formAction,jQuery(formObject).serialize(),function(responseText){wrapper.html(responseText);});
	e.preventDefault();
	return false;
};

var toggleDropDown = function(e) {
	jQuery(this).parent().append('<select><option>select something</option></select>').end().hide();
};

var navLookup = function(e) {
	window.location.href = this[this.selectedIndex].value;
	//var wrapper = jQuery('div#' + jQuery(this).attr('rel') + '-wrapper');
	//wrapper.load(this[this.selectedIndex].value + jQuery('div#selected-date').html());
};

var loadMore = function(e) {
	var wrapper = jQuery('div#' + this.rel);
	var load_more_div = jQuery(this.parentNode);
	load_more_div.addClass('load-more-loader');
	jQuery(this).fadeOut("slow");
	if(wrapper.length > 0) {
  	var ts = new Date().getTime();
    jQuery.get(this.href + '?ts=' + ts, function(data){
    	load_more_div.css({'display':'none'});
      wrapper.append(data).resize();
    });
  	this.blur();
  	e.preventDefault();
  	return false;
  }
};

var addInPlace = function(e) {
		var wrapper = jQuery('div#' + this.rel);
		wrapper.load(this.href);
		if (wrapper.css('display') == 'none') {
			wrapper.show('slow', updateKickColumns);
		}

		var formObject = jQuery(this).parents('form:first');
		var formAction = formObject.attr('action');

		if (jQuery(this).parents().hasClass("module-wrapper")) {
			var wrapper = "div#" + jQuery(this).parents('form:first').attr('rel') + "-wrapper";
      jQuery.post(formAction,jQuery(formObject).serialize(),function(responseText){jQuery(wrapper).html(responseText);});
			tb_remove();
		} else {
			jQuery.post(formAction,jQuery(formObject).serialize());
			window.location.reload();
		}

		e.preventDefault();
		return false;
};

var getDisplayName = function(e) {
	var firstName = jQuery('input#people_new_first_name').val() + ' ';
	var middleInitial = (jQuery("input#people_new_middle_name").length > 0 && jQuery("input#people_new_middle_name").val()!='')?jQuery('input#people_new_middle_name').val().slice(0,1) + '. ':'';
	var lastName = jQuery('input#people_new_last_name').val();
	var maidenName = (jQuery("input#people_new_maiden_name").length > 0 && jQuery("input#people_new_maiden_name").val()!='')?(' (' + jQuery("input#people_new_maiden_name").val() + ')'):'';
	jQuery('input#people_new_post_title').val(firstName + middleInitial + lastName + maidenName);
};

// Creates a marker whose info window displays the letter corresponding
// to the given index.
var createMarker = function(latitude, longitude, index, message, descrip) {
	position = new google.maps.LatLng(latitude, longitude);

	bounds.extend(position);

	// Create a lettered icon for this point using our icon class
	var letter = String.fromCharCode("A".charCodeAt(0) + index );

	var marker = new google.maps.Marker({
		map: map,
		icon: "http://www.google.com/mapfiles/marker" + letter + ".png",
		title: message,
		position: position,
		anchorPoint: baseAnchor
	});
	
	google.maps.event.addListener(marker, 'click', function() {
		if (typeof baseWindow != 'undefined')
			baseWindow.close();
		
		baseWindow = new google.maps.InfoWindow({
			content: '<strong>' + message + '</strong><p>' + descrip + '</p>'
		});
		
		baseWindow.open(map, marker);
		
		google.maps.event.addListener(map, 'click', function() {
			baseWindow.close();
		});
	});

	jQuery('a.map-label-' + letter).live('click', function() {
		google.maps.event.trigger(marker, 'click');
	});

	return marker;
};

var colorSwatch = function(e) {
  if(this.rel) {
		newBackground = 'transparent url(/images/bg' + jQuery(this).text() + '.jpg) repeat 50% 0';
  } else {
  	newBackground = jQuery(this).attr('title');
	}
	jQuery('div.tier-1').css('background-color', newBackground);
	jQuery('div.color-swatchs input').val(newBackground);
	this.blur();
	return false;
};

var showSection = function(e){
	if(this.rel) {
		var wrapper = jQuery('div#' + this.rel);
		wrapper.load(this.href);
		if (wrapper.css('display') == 'none') {
			wrapper.show('slow', updateKickColumns);
		}
		e.preventDefault();
	}
};

var closeWindow = function(e){
	tb_remove();
	e.preventDefault();
	return false;
};

var reloadSection = function(e){
	var formObject = jQuery(this).parents('form:first');
	var formAction = formObject.attr('action');

	if (jQuery(this).parents().hasClass("module-wrapper")) {
		var wrapper = "div#" + jQuery(this).parents('form:first').attr('rel') + "-wrapper";
		alert(wrapper);
    jQuery.post(formAction,jQuery(formObject).serialize(),function(responseText){jQuery(wrapper).html(responseText);});
		tb_remove();
	} else {
		jQuery.post(formAction,jQuery(formObject).serialize());
		window.location.reload();
	}

	e.preventDefault();
	return false;
};

var reloadNavigation = function(e){
	var formObject = jQuery(this).parents('form:first');
	var formAction = formObject.attr('action');

	if (jQuery(this).parents().hasClass("module-wrapper")) {
		var wrapper = "div#" + jQuery(this).parents('form:first').attr('rel') + "-wrapper";
		alert(wrapper);
    jQuery.post(formAction,jQuery(formObject).serialize(),function(responseText){jQuery(wrapper).html(responseText);});
		tb_remove();
	} else {
		jQuery.post(formAction,jQuery(formObject).serialize());
		window.location.reload();
	}

	e.preventDefault();
	return false;
};

jQuery.fn.createSortable = function(options) {
	this.sortable({
		items: options.items,
		handle: options.handle,
		cursor: 'move',
		//cursorAt: { top: 2, left: 2 },
		//opacity: 0.8,
		//helper: 'clone',
		appendTo: 'body',
		//placeholder: 'clone',
		//placeholder: 'placeholder',
		connectWith: this,
		start: sortableStart,
		change: sortableChange,
		update: options.callback
	});
};

jQuery.fn.log = function(msg) {
	if( console ) {
		console.log("%s: %o", msg, this);
		return this;
	}
	else
	{
		alert(msg);
	}
};

// sample use
// jQuery(root).find('li.source > input:checkbox').log("sources to uncheck").removeAttr("checked");

var checkBlur = function(e) {
	if(jQuery(this).val()=="" || jQuery(this).val()==null) {
		if(jQuery(this).attr('id')=='pass') {
		  jQuery('input#pass').css('display','none');
		  jQuery('input#fake-pass').css('display','');
		} else {
			jQuery(this).val(jQuery(this).attr('title'));
		}
	}
};

var checkFocus = function(e) {
	if(jQuery(this).val()==jQuery(this).attr('title')) {
		if(jQuery(this).attr('id')=='fake-pass') {
		  jQuery('input#fake-pass').css('display','none');
		  jQuery('input#pass').css('display','').focus();
		} else {
			jQuery(this).val('');
		}
	}
};

var getTwitter = function(data){
  jQuery('#layout').append('<table><tr><th scope="col">Name</th><th scope="col">Project</th><th scope="col">Completed</th></tr></table><p>Updates from: <strong></strong></p>');
  jQuery.each(data, function(index, item){
      if (item.text.match(/^\~\d?\d% of /)) {
        jQuery('#layout table').append('<tr><th scope="col"><img src="'+ item.user.profile_image_url + '" alt="" >' + item.user.name + '</th><td>' + item.text.substring(8, item.text.lastIndexOf('done'))+ "</td><td>"+ item.text.substr(1, 2) +"%</td></tr>");
        jQuery('#layout p strong').append(item.user.name+', ');
      }
    });
};

var showJSON = function(json){
	jQuery('#layout').append('<table style="margin:1px;background-color:#000;padding:5px;"><tr><th style="background-color:#ccc;padding:5px;">First Name</th><th style="background-color:#ccc;padding:5px;">Last Name</th><th style="background-color:#ccc;padding:5px;">Display Name</th></tr></table>');
  jQuery.each(json, function(index, item){
        jQuery('#layout table').append('<tr><td style="background-color:#fff;padding:5px;">' + item.first_name + '</td><td style="background-color:#fff;padding:5px;">' + item.last_name + '</td><td style="background-color:#fff;padding:5px;">' + item.post_title + '</td></tr>');
    });
};

var getRSS = function(feed_url, feed_column){
  jQuery.getFeed({
    url: feed_url,
    success: function(feed) {
      jQuery('#cell-'+feed_column).append('<h2>'
        + '<a href="'
        + feed.link
        + '">'
        + feed.title
        + '</a>'
        + '</h2>');

      var html = '';

      for(var i = 0; i < feed.items.length && i < 5; i++) {
        var item = feed.items[i];

        html += '<h3>'
          + '<a href="'
          + item.link
          + '">'
          + item.title
          + '</a>'
          + '</h3>';

        html += '<div class="updated">'
          + item.updated
          + '</div>';

        html += '<div>'
          + item.description
          + '</div>';
      }
      jQuery('#cell-'+feed_column).append(html);
    }
  });
};

var sortableStart = function(e, ui){
	ui.helper.css("width", ui.item.width());
};

var sortableChange = function(e, ui){
	if(ui.sender){
		var w = ui.element.width();
		ui.placeholder.width(w);
		ui.helper.css("width",ui.element.children().width());
	}
};

var sortableUpdate = function(e, ui){
	console.log(jQuery(this).sortable('serialize'));
};