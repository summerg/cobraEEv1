/*
function clickclear(thisfield, defaulttext) {
	if (thisfield.value == defaulttext) {
		thisfield.value = "";
	}
}
function clickrecall(thisfield, defaulttext) {
	if (thisfield.value == "") {
		thisfield.value = defaulttext;
	}
}
function LoadContentInLightWindow(strUrl, strContentType) {
	var pnlLightbox = $('#lightbox');
	if(strContentType == "image")	{
		pnlLightbox.html("<img src='" + strUrl +"' alt='' />");
	} else {
	  var so = new SWFObject(strUrl, "so", "500", "375", "9");
	  so.addParam("quality", "best");
	  so.addParam("wmode", "opaque");
	  so.write("lightbox");
	  //if(pnlLightbox.html().indexOf('embed') < 0 || pnlLightbox.html().indexOf('object') < 0)
	   // pnlLightbox.html("<a href='http://get.adobe.com/flashplayer/' target='_blank'>Please install the latest version of the Adobe Flash Player to view this video</a>");
	}
}

$(document).ready(function() {
	// activate fancy zoom
	$('.video_link').fancyZoom({width:500, height:375});
	$('.image_link').fancyZoom({width:500, height:375});

	//save the url to the swf and video
	// use hash to lightbox panel for fancy zoom to work
	$('.video_link').each(function(i) {
		$(this).data('url', $(this).attr('href'));
		$(this).attr('href', '#lightbox');
	});

	// create swf object on video link click
	$('.video_link').click(function() {
		var pnlLightbox = $('#lightbox');
		pnlLightbox.html("");
		LoadContentInLightWindow($(this).data('url'), 'video');
		var strHtml = pnlLightbox.html().toLowerCase();
	});

	$('.image_link').each(function(i) {
		$(this).data('url', $(this).attr('href'));
		$(this).attr('href', '#lightbox');
	});

	$('.image_link').click(function() {	
		var pnlLightbox = $('#lightbox');
		pnlLightbox.html("");
		LoadContentInLightWindow($(this).data('url'), 'image');
		var strHtml = pnlLightbox.html().toLowerCase();
	});	


	var gallery = PaginatedGallery($('#right_col_gallery'), 1, 304);
		// remove swf object html when closing
		$('#zoom_close').click(function() {
			var pnlLightbox = $('#lightbox');		
			if(pnlLightbox.html().indexOf('embed') > -1 || pnlLightbox.html().indexOf('object') > -1 || pnlLightbox.html().indexOf('img') > -1)
				pnlLightbox.html("");
		}
	);

});
*/