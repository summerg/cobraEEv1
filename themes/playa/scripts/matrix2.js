var PlayaColOpts = {};


(function($) {


Matrix.bind('playa', 'display', function(cell){

	var $field = $('.playa-droppanes', this),
		fieldName = cell.field.id+'[rows]['+cell.row.id+']['+cell.col.id+']',
		opts = $.extend({}, PlayaColOpts[cell.col.id], { fieldName: fieldName });

	new PlayaDropPanes($field, opts);

	return;

	if ($field.length) {
		var inputName = $('input:first', this).attr('name'),
			inputName = inputName.substr(0, inputName.length-5),
			id = $field.attr('id');
		var opts = PlayaCellOpts[id] || PlayaCellOpts[id+'_new'];
		new PlayaDropPanes($field, opts)
	}
});


})(jQuery);
