$.fn.ffMatrix.playaCellOpts = {};

$.fn.ffMatrix.onDisplayCell.playa = function(cell, FFM) {
	var $field = $('.playa-droppanes', cell);

	if ($field.length) {
		var inputName = $('input:first', cell).attr('name'),
			inputName = inputName.substr(0, inputName.length-5),
			id = $field.attr('id');
		var opts = $.fn.ffMatrix.playaCellOpts[id] || $.fn.ffMatrix.playaCellOpts[id+'_new'];
		new PlayaDropPanes($field, opts)
	}
};
