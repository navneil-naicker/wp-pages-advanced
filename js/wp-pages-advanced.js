/*!
 * jQuery UI Sortable Animation 0.0.1
 *
 * Copyright 2015, Egor Sharapov
 * Licensed under the MIT license.
 *
 * Depends:
 *  jquery.ui.sortable.js
 */
(function(factory) {
  if (typeof define === "function" && define.amd) {
    // AMD. Register as an anonymous module.
    define(["jquery", "jquery-ui"], factory);
  } else {
    // Browser globals
    factory(jQuery);
  }
}(function($) {
  var supports = {},
      testProp = function (prefixes) {
        var test_el = document.createElement('div'), i, l;

        for (i = 0; l = prefixes.length, i < l; i++) {
          if (test_el.style[prefixes[i]] != undefined) {
            return prefixes[i];
          }
        }

        return '';
      },
      use_css_animation = false;

  // check for css-transforms support
  supports['transform'] = testProp(['transform', 'WebkitTransform', 'MozTransform', 'OTransform', 'msTransform']);

  // check for css-transitions support
  supports['transition'] = testProp(['transition', 'WebkitTransition', 'MozTransition', 'OTransition', 'msTransition']);

  use_css_animation = supports['transform'] && supports['transition'];

  $.widget("ui.sortable", $.ui.sortable, {
    options: {
      // adds the new `animation` option, turned off by default.
      animation: 0,
    },

    // called internally by sortable when sortable
    // items are rearranged.
    _rearrange: function (e, item) {
      var $item,
          props = {},
          reset_props = {},
          offset,
          axis = $.trim(this.options.axis);

      // just call the original implementation of _rearrange()
      // if option `animation` is turned off
      // `currentContainer` used for animating received items
      // from another sortable container (`connectWith` option)
      if (!parseInt(this.currentContainer.options.animation) ||
          !axis
      ) {
        return this._superApply(arguments);
      }

      $item = $(item.item[0]);
      // if moved up, then move item up to its height,
      // if moved down, then move item down
      offset = (this.direction == 'up' ? '' : '-') + ($item[axis == 'x' ? 'width' : 'height']()) + 'px';

      // call original _rearrange() at first
      this._superApply(arguments);

      // prepare starting css props
      if (use_css_animation) {
        props[supports['transform']] = (axis == 'x' ? 'translateX' : 'translateY') + '(' + offset + ')';
      } else {
        props = {
          position: 'relative',
        };
        props[axis == 'x' ? 'left' : 'top'] = offset;
      }

      // set starting css props on item
      $item.css(props);

      // if css animations are not supported
      // use jQuery animations
      if (use_css_animation) {
        props[supports['transition']] = supports['transform'] + ' ' + this.options.animation + 'ms';
        props[supports['transform']] = '';
        reset_props[supports['transform']] = '';
        reset_props[supports['transition']] = '';

        setTimeout(function () {
          $item.css(props);
        }, 0);
      } else {
        reset_props.top = '';
        reset_props.position = '';

        $item.animate({
          top: '',
          position: ''
        }, this.options.animation);
      }

      // after animation ends
      // clear changed for animation props
      setTimeout(function () {
        $item.css(reset_props);
      }, this.options.animation);
    }
  });
}));

(function($){		
	$('body.pages_page_wp-pages-advanced .dashicons-plus-link').on('click', function(){
		var child;
		child = $(this).data('child');
		$('body.pages_page_wp-pages-advanced #item-' + child).slideToggle('fast');
		if( $(this).find('span').hasClass('dashicons-plus') ){
			$(this).find('span').removeClass('dashicons-plus');
			$(this).find('span').addClass('dashicons-minus');
		} else {
			$(this).find('span').removeClass('dashicons-minus');
			$(this).find('span').addClass('dashicons-plus');
		}
		return false;
	});	
		
	$('body.pages_page_wp-pages-advanced .subsubsub .button-primary').on('click', function(){
			items = $('body.pages_page_wp-pages-advanced ul.wp-pages-advanced-parent').sortable('serialize');
			$('body.pages_page_wp-pages-advanced .subsubsub .button-primary').attr('disabled', true).text('Saving...');
			$.post(ajaxurl, {'action': 'AjaxAdvancedPageTreeUpdateSortOrder', 'items': items}, function( data ){
				$('body.pages_page_wp-pages-advanced .subsubsub .button-primary').removeAttr('disabled').text('Save Changes');
			});	
			return false;
	});

 	$('body.pages_page_wp-pages-advanced ul.wp-pages-advanced-parent').sortable({
		axis: "y",
		items: " li",
		containment: 'parent',
		placeholder: "sortable-placeholder",
		cursor: 'move',
		handle: '.wp-pages-advanced-handle-sort',
		forcePlaceholderSize: true,
		opacity: 1,
		animation: 100,
    cursorAt: { top: 30 },
    zIndex: 99999,
		sort: function(e){
			$('body.pages_page_wp-pages-advanced .subsubsub .button-primary').removeAttr('disabled');
		},
	}).disableSelection();
		
	$('body.pages_page_wp-pages-advanced .dashicons-trash-link').on('click', function(){
		var url, id;
		url = $(this).attr('href');
    id = $(this).attr('data-id');
    title = $(this).attr('data-title');
    if( confirm('You are about to move the page "' + title + '" to trash. Are you sure?') ){
      $('body.pages_page_wp-pages-advanced ul.wp-pages-advanced-parent li#item_' + id).slideUp('fast').remove();
      $.post( url );  
    }
		return false;
	});
	
	$('button#wp-pages-advanced-add-more-title').on('click', function(){
		$('#wp-pages-advanced-multiple-fat-group').append('<div class="wp-pages-advanced-multiple-fat-field-group"><input type="text" class="wp-pages-advanced-multiple-fat-field" name="page-titles[]" placeholder="Enter title"/></div>');
		return false;
	});		
	
	$('#page-tree-add-multiple form').on('submit', function(){
		var form, data;
		form = $(this).serializeArray();
		$('button#wp-pages-advanced-save-multiple').attr('disabled', true).html('Saving...');
		$.post(ajaxurl, form, function(){
			location.reload();
		});
		return false;
	});
	
	$('ul.wp-pages-advanced-parent .dashicons-menu-add-page-link').on('click', function(){
		var id;
		id = $(this).attr('data-id');
		console.log(id);
		$('#page-tree-add-multiple select[name=\'page-parent\']').val(id);
		$('body.pages_page_wp-pages-advanced #wp-pages-advanced-add-multiple').click();
		return false;
	});
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
			
})( jQuery );