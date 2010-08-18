/**
 * @param mixed target
 * @param object options
 */
(function($){
$.fn.execute = function(method) {

	if (typeof method === "function" && this.length > 0){
		method.apply(this);
	}
			
	return this;
};
})(jQuery);
