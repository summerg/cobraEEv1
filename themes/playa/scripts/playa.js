var PlayaDropPanes;

(function($) {


// -------------------------------------------
//  Utility Functions
// -------------------------------------------

/**
 * Get distance between two coordinates
 */
var getDist = function(x1, y1, x2, y2) {
	return Math.sqrt(Math.pow(x1-x2, 2) + Math.pow(y1-y2, 2));
}

/**
 * Check if cursor is over an element
 */
var isCursorOver = function(event, $element) {
	return hitTest(event.pageX, event.pageY, $element);
}

/**
 * Hit Test
 */
var hitTest = function(x0, y0, $element){
	var offset = $element.offset(),
		x1 = offset.left,
		y1 = offset.top,
		x2 = x1 + $element.outerWidth(),
		y2 = y1 + $element.outerHeight();

	return (x0 >= x1 && x0 < x2 && y0 >= y1 && y0 < y2);
}

/**
 * Get the closest element to the cursor
 */
var getClosestElement = function(event, $elements){
	var closestElement, closestXDist, closestYDist;

	$elements.each( function(){
		var $element = $(this),
			offset = $element.offset(),
			xDist = Math.abs(offset.left - event.pageX),
			yDist = Math.abs(offset.top - event.pageY);

		if (!closestElement || (yDist < closestYDist) || (yDist == closestYDist && xDist < closestXDist)) {
			closestElement = this;
			closestXDist = xDist;
			closestYDist = yDist;
		}
	});

	return closestElement;
}

// --------------------------------------------------------------------


/**
 * Multi-select
 */
var MultiSelect = function(id, $pane, $btn){

	var obj = this;
	obj.namespace = 'pdp-'+id;
	obj.$pane = $pane;
	obj.$btn = $btn;
	obj.$scrollpane = $('> div', obj.$pane);
	obj.$items;
	obj.mouseUpTimeout;

	var $first, first = false,
		$last, last = false,
		totalSelected = 0;

	// --------------------------------------------------------------------

	/**
	 * Get Item Index
	 */
	obj.getItemIndex = function($item) {
		return obj.$items.index($item[0]);
	};

	/**
	 * Is Selected?
	 */
	obj.isSelected = function($item) {
		return $item.hasClass('pdp-active');
	};

	/**
	 * Select Item
	 */
	obj.selectItem = function($item){
		$item.addClass('pdp-active');

		$first = $last = $item;
		first = last = obj.getItemIndex($item);

		obj.totalSelected++;
		if (obj.totalSelected == 1) obj.$btn.removeClass('disabled');
	};

	/**
	 * Select Range
	 */
	obj.selectRange = function($item){
		obj.deselectAll();

		$last = $item;
		last = obj.getItemIndex($item);

		// prepare params for $.slice()
		if (first < last) {
			var sliceFrom = first,
				sliceTo = last + 1;
		} else { 
			var sliceFrom = last,
				sliceTo = first + 1;
		}

		obj.$items.slice(sliceFrom, sliceTo).addClass('pdp-active');

		obj.totalSelected = sliceTo - sliceFrom;
		obj.$btn.removeClass('disabled');
	};

	/**
	 * Deselect Item
	 */
	obj.deselectItem = function($item){
		$item.removeClass('pdp-active');

		var index = obj.getItemIndex($item);
		if (first === index) $first = first = false;
		if (last === index) $last = last = false;

		obj.totalSelected--;
		if (! obj.totalSelected) obj.$btn.addClass('disabled');
	};

	/**
	 * Deselect All
	 */
	obj.deselectAll = function(clearFirst){
		obj.$items.removeClass('pdp-active');

		if (clearFirst) {
			$first = first = $last = last = false;
		}

		obj.totalSelected = 0;
		obj.$btn.addClass('disabled');
	};

	/**
	 * Deselect Others
	 */
	obj.deselectOthers = function($item){
		obj.deselectAll();
		obj.selectItem($item);
	};

	/**
	 * Toggle Item
	 */
	obj.toggleItem = function($item){
		if (! obj.isSelected($item)) {
			obj.selectItem($item);
		} else {
			obj.deselectItem($item);
		}
	}

	// --------------------------------------------------------------------

	var mousedown_x, mousedown_y;

	/**
	 * On Mouse Down
	 */
	var onMouseDown = function(event){
		mousedown_x = event.pageX;
		mousedown_y = event.pageY;

		var $item = $(this);

		// validate that this function should have even been called
		if ($item.parent().parent().parent().attr('className') != obj.$pane.attr('className')) return;

		if (event.metaKey) {
			obj.toggleItem($item);
		}
		else if (first !== false && event.shiftKey) {
			obj.selectRange($item);
		}
		else if (! obj.isSelected($item)) {
			obj.deselectAll();
			obj.selectItem($item);
		}

		obj.$pane.focus();
	};

	/**
	 * On Mouse Up
	 */
	var onMouseUp = function(event){
		var $item = $(this);

		// validate that this function should have even been called
		if ($item.parent().parent().parent().attr('className') != obj.$pane.attr('className')) return;

		// was this a click?
		if (! event.metaKey && getDist(mousedown_x, mousedown_y, event.pageX, event.pageY) < 1) {

			obj.selectItem($item);

			// wait a moment before deselecting others
			// to give the user a chance to double-click
			clearTimeout(obj.mouseUpTimeout);
			obj.mouseUpTimeout = setTimeout(function(){
				obj.mouseUpTimeout = false;

				// deselect others?
				if (! event.metaKey && ! event.shiftKey) {
					obj.deselectOthers($item);
				}
			}, 300);
		}
	};

	// --------------------------------------------------------------------

	/**
	 * On Click
	 */
	obj.$pane.click(function(event){
		obj.deselectAll(true);
	});

	// --------------------------------------------------------------------

	/**
	 * On Key Down
	 */
	obj.$pane.keydown(function(event){
		// ignore if this pane doesn't have focus
		if (event.target != obj.$pane[0]) return;

		// ignore if there are no items
		if (! obj.$items.length) return;

		var anchor = event.shiftKey ? last : first;

		switch (event.keyCode) {
			case 40: // Down
				event.preventDefault();

				if (first === false) {
					// select the first item
					$item = $(obj.$items[0]);
				}
				else if (obj.$items.length >= anchor + 2) {
					// select the item after the last selected item
					$item = $(obj.$items[anchor+1]);
				}

				break;

			case 38: // up
				event.preventDefault();

				if (first === false) {
					// select the last item
					$item = $(obj.$items[obj.$items.length-1]);
				}
				else if (anchor > 0) {
					$item = $(obj.$items[anchor-1]);
				}

				break;

			case 27: // esc
				obj.deselectAll(true);

			default: return;
		};

		if (! $item || ! $item.length) return;

		// -------------------------------------------
		//  Scroll to the item
		// -------------------------------------------

		var scrollTop = obj.$scrollpane.attr('scrollTop'),
			itemOffset = $item.offset().top,
			scrollpaneOffset = obj.$scrollpane.offset().top,
			offsetDiff = itemOffset - scrollpaneOffset;

		if (offsetDiff < 0) {
			obj.$scrollpane.attr('scrollTop', scrollTop + offsetDiff);
		}
		else {
			var itemHeight = $item.outerHeight(),
				scrollpaneHeight = obj.$scrollpane.outerHeight();

			if (offsetDiff > scrollpaneHeight - itemHeight) {
				obj.$scrollpane.attr('scrollTop', scrollTop + (offsetDiff - (scrollpaneHeight - itemHeight)));
			}
		}

		// -------------------------------------------
		//  Select the item
		// -------------------------------------------

		if (first !== false && event.shiftKey) {
			obj.selectRange($item);
		}
		else {
			obj.deselectAll();
			obj.selectItem($item);
		}
	});

	// --------------------------------------------------------------------

	/**
	 * Update Items
	 */
	obj.updateItems = function(){
		if (obj.$items) {
			// unbind previous listeners
			obj.$items.unbind('.'+obj.namespace);
			delete(obj.$items);
		}

		// events don't get bound correctly without this...
		setTimeout(function(){
			// get new items
			obj.$items = $('> div > ul > li:not(.pdp-placeholder):not(.pdp-caboose)', obj.$pane);

			// bind listeners
			obj.$items.bind('mousedown.'+obj.namespace, onMouseDown);
			obj.$items.bind('mouseup.'+obj.namespace, onMouseUp);

			obj.$items.bind('click.'+obj.namespace, function(event){
				event.stopPropagation();
			});

			obj.totalSelected = obj.$items.filter('.pdp-active').length;
			if (! obj.totalSelected) obj.$btn.addClass('disabled');

			if (first !== false) {
				// does $first still exist in $items?
				first = last = obj.getItemIndex($first);
				if (first == -1) $first = first = $last = last = false;
			}
		}, 1);
	}

	obj.updateItems();

	/**
	 * Get Active Items
	 */
	obj.activeItems = function(){
		return obj.$items.filter('.pdp-active');
	};

};


/**
 * Drop Panes
 */
PlayaDropPanes = function($field, opts){

	var obj = this;

	obj.opts = opts;

	// -------------------------------------------
	//  Gather the main DOM elements
	// -------------------------------------------

	obj.dom = {
		$document: $(document),
		$field: $field
	};

	obj.dom.$tds = $('tr:first > td', obj.dom.$field);

	obj.dom.$btns = $('a', obj.dom.$tds.filter('.pdp-btns'));
	obj.dom.$selectBtn   = obj.dom.$btns.filter('.select');
	obj.dom.$deselectBtn = obj.dom.$btns.filter('.deselect');

	obj.dom.$droppanes = obj.dom.$tds.filter('.pdp');
	obj.dom.$optionsContainer    = obj.dom.$droppanes.filter('.pdp-options');
	obj.dom.$selectionsContainer = obj.dom.$droppanes.filter('.pdp-selections');

	obj.dom.$items;
	obj.dom.$caboose;

	obj.optionsSelect    = new MultiSelect('options', obj.dom.$optionsContainer, obj.dom.$selectBtn);
	obj.selectionsSelect = new MultiSelect('selections', obj.dom.$selectionsContainer, obj.dom.$deselectBtn);

	// --------------------------------------------------------------------

	var onLeaveOptions = function(){
		cursorOverOptions = false;
		obj.dom.$optionsContainer.removeClass('pdp-hover');
	};

	var onLeaveSelections = function(){
		cursorOverSelections = false;
		obj.dom.$selectionsContainer.removeClass('pdp-hover');

		if (closestSelection) {
			closestSelection = null;
			$insertion.remove();
		}

		redrawContainerIfSafari();
	};

	var redrawContainerIfSafari = function(){
		if ($.browser.safari) obj.dom.$field.css('opacity', (obj.dom.$field.css('opacity') == 1 ? .999 : 1));
	};

	var getMouseDist = function(event){
		return getDist(mousedown_x, mousedown_y, event.pageX, event.pageY);
	};

	// --------------------------------------------------------------------

	// state vars, etc.
	var mousedown_x, mousedown_y,
		mouse_x,     mouse_y,
		mousediff_x, mousediff_y,
		dragging = false,
		cursorOverSelections = false,
		cursorOverOptions = false,
		updateHelperPosInterval,
		$target,
		$draggee, draggeeOffsets,
		$handle,
		$helper,
		tempMarginBottom,
		$selections,
		closestSelection,
		$insertion = $('<li class="pdp-insertion" />'),
		callAfterMouseUp = false;

	// --------------------------------------------------------------------

	/**
	 * Select Items
	 */
	var selectItems = function($items){
		$items.each(function(i){
			var $item = $(this).removeClass('pdp-active');

			// new selection?
			if (! $item.hasClass('pdp-selected')) {
				$item.addClass('pdp-selected');

				// hold the option's position with a placeholder
				$('<li />').attr('id', $item.attr('id')+'-placeholder').addClass('pdp-placeholder').insertAfter($item);

				// enable inputs
				$('*[name]', $item).removeAttr('disabled');
			}
		});

		// replace insertion with all items
		$insertion.replaceWith($items);

		obj.optionsSelect.updateItems();
		obj.selectionsSelect.updateItems();
	};

	/**
	 * Deselect Items
	 */
	var deselectItems = function($items){
		var removeItems = [];


		$items.each(function(i){
			var $item = $(this).removeClass('pdp-active');

			// previously selected?
			if ($item.hasClass('pdp-selected')) {
				$item.removeClass('pdp-selected');

				// look for a placeholder for this item in the options
				var $placeholder = $('#'+$item.attr('id')+'-placeholder', obj.dom.$optionsContainer);

				if ($placeholder.length) {
					// replace placeholder with this item
					$placeholder.replaceWith($item);

					// disable inputs
					$('*[name]', $item).attr('disabled', true);
				}
				else {
					$item.remove();
					removeItems.push(i);
				}
			}
		});

		obj.optionsSelect.updateItems();
		obj.selectionsSelect.updateItems();

		return removeItems;
	};

	obj.dom.$selectBtn.click(function(){
		// ignore if disabled
		if ($(this).hasClass('disabled')) return;

		// place the insertion at the end of the selections
		$insertion.insertBefore(obj.dom.$caboose);

		selectItems(obj.optionsSelect.activeItems());
	});

	obj.dom.$deselectBtn.click(function(){
		// ignore if disabled
		if ($(this).hasClass('disabled')) return;

		deselectItems(obj.selectionsSelect.activeItems());
	});

	obj.dom.$optionsContainer.keydown(function(event){
		// is this a space or right arrow?
		if (event.keyCode == 32 || event.keyCode == 39) {
			event.preventDefault();
			obj.dom.$selectBtn.click();
		}
	});

	obj.dom.$selectionsContainer.keydown(function(event){
		// is this a space or left arrow?
		if (event.keyCode == 32 || event.keyCode == 37) {
			event.preventDefault();
			obj.dom.$deselectBtn.click();
		}
	});

	// --------------------------------------------------------------------

	/**
	 * Mouse Down Handler
	 */
	var onMouseDown = function(event){
		event.preventDefault();
		event.stopPropagation();

		// close out the last drag?
		if (callAfterMouseUp) {
			callAfterMouseUp = false;
			afterMouseUp();
		}

		// capture mouse coords to determine when to start dragging
		mousedown_x = mouse_x = event.pageX;
		mousedown_y = mouse_y = event.pageY;

		$target = $(this);

		obj.dom.$document.bind('mousemove.playaDropPanes', onMouseMove);
		obj.dom.$document.bind('mouseup.playaDropPanes', onMouseUp);

	};

	// --------------------------------------------------------------------

	/**
	 * Mouse Move Handler
	 */
	var onMouseMove = function(event){
		event.preventDefault();
		event.stopPropagation();

		// save mouse position
		mouse_x = event.pageX;
		mouse_y = event.pageY;

		var mouseDist = getMouseDist(event);

		if (! $helper && mouseDist > 1) {

			//var $droppane = $target.parent().parent().parent();

			$draggee = $target.add($target.siblings('.pdp-active'));

			// center helper on target's handle
			var $handle = $('a', $target);
			mousediff_x = $handle.outerWidth() / 2;
			mousediff_y = $handle.outerHeight() / 2;

			// create the helper
			$helper = $('<ul />').appendTo($(document.body))
				.addClass('playa-droppanes pdp-helper')
				.css({ position: 'static', margin: 0, padding: 0 });

			// get the draggee offsets
			draggeeOffsets = {};
			$draggee.each(function(i){
				var $item = $(this);

				draggeeOffsets[i] = $item.offset();

				// clone item into helper
				$item.clone(true).appendTo($helper)
					.css({
						position: 'absolute',
						zIndex: (100 + $draggee.length - i),
						margin: 0,
						padding: 0
					});
			});

			// hide $draggee
			$draggee.css('visibility', 'hidden');

			updateHelperPos();
			updateHelperPosInterval = setInterval(updateHelperPos, 25);

		}

		// is it time to start dragging?
		if (! dragging && getMouseDist(event) > 20) {

			dragging = true;
			$helper.addClass('pdp-dragging');

			// get the latest list of selections
			$selections = $('> div > ul > li', obj.dom.$selectionsContainer).not($draggee);

			$draggee.animate({
				marginBottom: (tempMarginBottom = -$draggee.outerHeight())
			}, 'fast');
		}

		// dragging state might have just changed,
		// so a simple `else` won't do
		if (dragging) {
			// cursor over selections?
			var _cursorOverSelections = isCursorOver(event, obj.dom.$selectionsContainer);

			if (_cursorOverSelections && !cursorOverSelections) {
				// just rolled over selections
				cursorOverSelections = true;
				obj.dom.$selectionsContainer.addClass('pdp-hover');
			}
			else if (!_cursorOverSelections && cursorOverSelections) {
				// just rolled off selections
				onLeaveSelections();
			}

			if (cursorOverSelections) {
				// find and place the insertion point
				var _closestSelection = getClosestElement(event, $selections);
				if (_closestSelection != closestSelection) {
					closestSelection = _closestSelection;
					$insertion.insertBefore(closestSelection);
					redrawContainerIfSafari();
				}
			}

			// cursor over options?
			var _cursorOverOptions = (!cursorOverSelections && isCursorOver(event, obj.dom.$optionsContainer));
			if (_cursorOverOptions && !cursorOverOptions) {
				// just rolled over options
				cursorOverOptions = true;
				obj.dom.$optionsContainer.addClass('pdp-hover');
			}
			else if (!_cursorOverOptions && cursorOverOptions) {
				// just rolled off options
				onLeaveOptions();
			}

			redrawContainerIfSafari();
		}
	};

	// --------------------------------------------------------------------

	/**
	 * Update Helper Position
	 */
	var updateHelperPos = function(){
		if (! dragging) {

			// nudge the helper items toward the cursor
			$helper.children().each(function(i){
				$(this).css({
					left: draggeeOffsets[i].left + Math.round((mouse_x - mousedown_x) / 6),
					top:  draggeeOffsets[i].top  + Math.round((mouse_y - mousedown_y) / 6)
				});
			});
		}
		else {

			// slide the helper items toward the cursor
			$helper.children().each(function(i) {
				var $item = $(this),
					target_x = mouse_x - mousediff_x + (i * 3),
					target_y = mouse_y - mousediff_y + (i * 3),
					left = parseInt($item.css('left')),
					top  = parseInt($item.css('top'));

				$item.css({
					left: left + (target_x - left) / 2,
					top:  top  + (target_y - top) / 2
				});
			});
		}
	};

	// --------------------------------------------------------------------

	/**
	 * Mouse Up Handler
	 */
	var onMouseUp = function(event){
		event.preventDefault();
		event.stopPropagation();

		clearInterval(updateHelperPosInterval);
		obj.dom.$document.unbind('.playaDropPanes');

		// ignore if this was just a plain click
		if (! $helper) return;

		if (dragging) {

			// clear state
			dragging = false;

			// -------------------------------------------
			//  Remove the items' active state
			// -------------------------------------------

			var $originalPane = $draggee.parent().parent().parent();

			if ($originalPane.hasClass('pdp-options')) {
				var originalPane = 'options';
				obj.optionsSelect.deselectAll();
			} else {
				var originalPane = 'selections';
				obj.selectionsSelect.deselectAll();
			}

			// -------------------------------------------
			//  Select / Deselect them
			// -------------------------------------------

			if (cursorOverOptions) {
				var removeItems = deselectItems($draggee);
				onLeaveOptions();
			}
			else if (cursorOverSelections) {
				selectItems($draggee);
				onLeaveSelections();
			}

			// -------------------------------------------
			//  Get the new item offsets
			// -------------------------------------------

			// temporarily bring back the draggee margins
			// for the sake of getting the correct offsets
			$draggee.css({ marginBottom: 0 });

			// get the new offsets
			$draggee.each(function(i){
				draggeeOffsets[i] = $(this).offset();
			});

			// revert margins
			$draggee.css({ marginBottom: tempMarginBottom });

			// -------------------------------------------
			//  Animate them back to their normal dimensions
			// -------------------------------------------

			// re-show draggee item's gap
			$draggee.animate({
				marginBottom: 0
			}, 'fast');
		}

		// -------------------------------------------
		//  Bring things back to normal
		// -------------------------------------------

		// there's a chance the next drag will have initiated before this animation is over,
		// so we'll ensure that afterMouseUp() is still necessary with this callAfterMouseUp var
		callAfterMouseUp = true;

		$helper.children().each(function(i){
			var $item = $(this);

			if (typeof removeItems != 'undefined' && $.inArray(i, removeItems) != -1) {
				// there's no associated item in the options list,
				// so just fade the helper item out
				$item.animate({ opacity: 0 }, 'fast');
			}
			else {
				// slide the helper to the draggee
				$item.animate({ left: draggeeOffsets[i].left, top: draggeeOffsets[i].top }, 'fast', function(){
					// still necessary to call afterMouseUp()?
					if (callAfterMouseUp) {
						callAfterMouseUp = false;
						afterMouseUp();
					}
				});
			}
		});
	};

	/**
	 * After Mouse Up
	 */
	var afterMouseUp = function() {
		if ($draggee) {
			$draggee.css('visibility', 'visible');
		}

		$helper.remove();
		$helper = null;
	};

	// --------------------------------------------------------------------

	var onDblClick = function(){
		var $item = $(this);

		if ($item.parent().parent().parent().hasClass('pdp-options')) {
			clearTimeout(obj.optionsSelect.mouseUpTimeout);
			obj.dom.$selectBtn.click();
		} else {
			clearTimeout(obj.selectionsSelect.mouseUpTimeout);
			obj.dom.$deselectBtn.click();
		}
	};

	// --------------------------------------------------------------------

	var updateItems = function(){
		// get the full list of LIs
		obj.dom.$items = $('> div > ul > li', obj.dom.$droppanes);

		// add event listeners
		obj.dom.$items.bind('mousedown.playaDropPanes', onMouseDown);
		obj.dom.$items.bind('dblclick.playaDropPanes', onDblClick);
	};

	updateItems();

	// find the caboose
	obj.dom.$caboose = obj.dom.$items.filter('.pdp-caboose');

	// --------------------------------------------------------------------



	// -------------------------------------------
	//  Filters
	// -------------------------------------------

	if (obj.opts && typeof PlayaDropPanes.filterUrl != 'undefined')
	{
		var $filtersContainer = $('.filters', obj.dom.$field),
			$searchFilter = $('.filter.search', $filtersContainer),
			$searchInput = $('input', $searchFilter).val(''),
			$limit = $('.limit', obj.dom.$field),
			$limitInput = $('select', $limit),
			$ul = $('ul:first', obj.dom.$optionsContainer);

		PlayaDropPanesFilter($searchFilter, obj)

		/**
		 * Apply filters
		 */
		var applyFilters = function(){
			// kill all mouse events within the field
			obj.dom.$items.unbind('.playaDropPanes');
			obj.optionsSelect.deselectAll();

			// dim the options
			$ul.css('opacity', 0.5);

			var data = {
				field_id:   obj.dom.$field.attr('id'),
				field_name: opts.fieldName,
				keywords:   $searchInput.val(),
				limit:      $limitInput.val()
			};

			// select filters
			$('.filter', $filtersContainer).not($searchFilter).each(function(){
				var $selects = $('select:not([disabled])', this),
					filterName = $($selects[0]).val(),
					filterVal  = $($selects[1]).val();

				if (filterVal != 'any') {
					if (typeof data[filterName] == 'undefined') {
						data[filterName] = filterVal;
					} else {
						data[filterName] += '|'+filterVal;
					}
				}
			});

			// defaults
			for (i in opts.defaults) {
				if (typeof data[i] == 'undefined') {
					data[i] = opts.defaults[i];
				}
			}

			// get the current selected entry ids
			$('*[name]', obj.dom.$selectionsContainer).each(function(key){
				data['selected_entry_ids['+key+']'] = this.value;
			});

			// run the ajax post request
			$.post(PlayaDropPanes.filterUrl+'&'+Math.floor(Math.random()*999999), data, function(data, textStatus){
				if (textStatus == 'success') {
					// update the options
					$ul.html(data).css('opacity', 1);

					// toggle the Limit bar
					if ($ul.attr('childElementCount') < 25) {
						$limit.hide();
					} else {
						$limit.show();
					}

					// reset $items
					updateItems();
					obj.optionsSelect.updateItems();
				}
			});
		};

		obj.dom.$field.bind('applyFilters', applyFilters);

		// -------------------------------------------
		//  Show/hide Search label
		// -------------------------------------------

		var $searchLabel = $('span span', $searchFilter),
			$eraseBtn = $('a.erase', $searchFilter);

		$searchLabel.mousedown(function(){
			$searchInput.focus();
		});

		$searchInput.focus(function(){
			$searchLabel.hide();
		});

		$searchInput.blur(function(){
			if (! $searchInput.val()) {
				$searchLabel.show();
			}
		});

		// -------------------------------------------
		//  Search listener
		// -------------------------------------------

		var searchVal = '',
			searchTimeout;

		var checkKeywordVal = function(){
			// has the value changed?
			var _searchVal = $searchInput.val();
			if (_searchVal == searchVal) return;

			searchVal = _searchVal;

			applyFilters();
		};

		$searchInput.keydown(function(event) {
			event.stopPropagation();

			// clear the last timeout
			clearTimeout(searchTimeout);

			setTimeout(function(){
				switch (event.keyCode) {
					case 13: // return
						event.preventDefault();
						checkKeywordVal();
						break;

					case 27: // esc
						event.preventDefault();
						$searchInput.val('');
						checkKeywordVal();
						break;

					default:
						searchTimeout = setTimeout(checkKeywordVal, 500);
				}

				// show/hide the escape button
				if ($searchInput.val()) {
					$eraseBtn.show();
				} else {
					$eraseBtn.hide();
				}
			}, 0);
		});

		$eraseBtn.click(function(event){
			$searchInput.val('');
			checkKeywordVal();
			$eraseBtn.hide();
			$searchLabel.show();
		})

		// -------------------------------------------
		//  Limit
		// -------------------------------------------

		$limitInput.change(function(){
			applyFilters();
		});

	}

};

// --------------------------------------------------------------------

/**
 * Playa Filter
 */
var PlayaDropPanesFilter = function($filter, obj){

	var $remove = $('a.remove', $filter),
		$add    = $('a.add', $filter);

	$add.click(function(){
		var $newFilter = $('<div class="filter" />').insertAfter($filter),
			$newRemove = $remove.clone().removeClass('disabled').appendTo($newFilter);
			$newAdd    = $add.clone().removeClass('disabled').appendTo($newFilter);

		var $filtersSelect = $('<select />').appendTo($newFilter);
		for (i in obj.opts.filters) {
			$('<option value="'+i+'">'+obj.opts.filters[i][0]+'</option>').appendTo($filtersSelect);
		}

		$newFilter.append(' &nbsp;'+PlayaDropPanes.lang.is+'&nbsp; ');
		var $optionsSelect = $('<select />').appendTo($newFilter);

		var setOptions = function(){
			$optionsSelect.html(obj.opts.filters[$filtersSelect.val()][1]);
		};
		setOptions();

		$filtersSelect.change(function(){
			setOptions();
			obj.dom.$field.trigger('applyFilters');
		});

		$optionsSelect.change(function(){
			obj.dom.$field.trigger('applyFilters');
		})

		var filterHeight = $newFilter.outerHeight();

		// center buttons
		var top = Math.floor((filterHeight - 20) / 2);
		$newRemove.css('top', top);
		$newAdd.css('top', top);

		// slide down
		$filter.css('zIndex', 1);
		$newFilter.css('marginTop', -filterHeight);
		$newFilter.animate({ marginTop: 1 }, 'fast', function(){
			$filter.css('zindex', 0);
		});

		$newRemove.click(function(){
			// disable these selects
			$filtersSelect.attr('disabled', 'disabled');
			$optionsSelect.attr('disabled', 'disabled');

			// slide up and remove
			var $prevFilter = $newFilter.prev().css('zIndex', 1);
			$newFilter.animate({ marginTop: -filterHeight }, 'fast', function(){
				$newFilter.remove();
				$prevFilter.css('zIndex', 0);
				obj.dom.$field.trigger('applyFilters');
			})
		});

		new PlayaDropPanesFilter($newFilter, obj);
	});
};


})(jQuery);
