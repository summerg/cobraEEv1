$(document).ready
(
	function() 
	{
		// activate fancy zoom
		$('.video_link').fancyZoom({width:470, height:300});
		$('.image_link').fancyZoom({width:550, height:450});
		$('.flash_link').fancyZoom({width:760, height:550});
				
		//save the url to the swf and video
		// use hash to lightbox panel for fancy zoom to work
		function ReplaceFancyZoomLink(elem)
		{
			$(elem).data('url', $(elem).attr('href'));
			$(elem).attr('href', '#lightbox');
		}

		$('.video_link').each
		(
			function(i)
			{
				ReplaceFancyZoomLink(this);
			}
		);
		
		// create swf object on video link click
		$('.video_link').click
		(
			function()
			{	
				var pnlLightbox = $('#lightbox');
				pnlLightbox.html("");
				LoadContentInLightWindow(this, $(this).data('url'), 'video');
			}
		);
		
		$('.flash_link').each
		(
			function(i)
			{
				ReplaceFancyZoomLink(this);
			}
		);
		
		// create swf object on video link click
		$('.flash_link').click
		(
			function()
			{	
				var pnlLightbox = $('#lightbox');
				pnlLightbox.html("");
				LoadContentInLightWindow(this, $(this).data('url'), 'flash');
			}
		);
		
    $('.image_link').each
		(
			function(i)
			{
				ReplaceFancyZoomLink(this);
			}
		);
    
    $('.image_link').click
		(
			function()
			{	
				var pnlLightbox = $('#lightbox');
				pnlLightbox.html("");
				LoadContentInLightWindow(this, $(this).data('url'), 'image');
			}
		);	
		

	var gallery = PaginatedGallery($('#right_col #gallery'), 1, 297);
		// remove swf object html when closing
		$('#zoom_close').click
		(
			function()
			{
				var pnlLightbox = $('#lightbox');
				
				if(pnlLightbox.html().indexOf('embed') > -1 || pnlLightbox.html().indexOf('object') > -1 || pnlLightbox.html().indexOf('img') > -1)
					pnlLightbox.html("");
			}
		);		
		
	}
);
function loadVideo(flv, preview_img, autoplay, width, height)
{
    var params = {
            allowfullscreen: "true",
            allowscriptaccess: "sameDomain",
            wmode: "opaque"
    };

    var flashvars = {
            file: flv,
            autostart: autoplay,
            controlbar : "bottom",
            icons: "false",
            dock: "true",
            image: preview_img
    };
    var attributes = {};   
    $('#zoom_content2').prepend("<h2 class='stratum' style='text-align:left;'>Video</h2>");
    swfobject.embedSWF("/images/flash/player.swf", "zoom_video", width, height, "9.0.0", "/images/flash/expressInstall.swf", flashvars, params, attributes);
} 

function loadFlash(swf)
{
    var params = {
            allowfullscreen: "true",
            allowscriptaccess: "sameDomain",
            wmode: "opaque"
    };

    var flashvars = {};
    var attributes = {};   
    $('#zoom_content2').prepend("<h2 class='stratum' style='text-align:left;'>360 View</h2>");
    swfobject.embedSWF(swf, "zoom_video", "747", "530", "9.0.0", "/images/flash/expressInstall.swf", flashvars, params, attributes);
} 

function LoadContentInLightWindow(element, strUrl, strContentType)
{
  var pnlLightbox = $('#zoom_content'); 

  if(strContentType == "image")
  {
		imageHTML = "<h2 class='stratum' style='text-align:left;'>Gallery</h2><div id='image_box'><img id='zoom_image' src='" + strUrl +"' alt='' /><div class='image_controls'>"
		imageHTML += "</div></div>";		
		pnlLightbox.html(imageHTML);
		SetUpGalleryImageNav($(element).parent().find('> a').index(element), GetGalleryImageCount()); 
  }
  else if(strContentType == "flash")
  {
		loadFlash(strUrl);
		//loadVideo(strUrl, "", true, 448, 380);
  }
  else
  {
		loadVideo(strUrl, "", true, 448, 272);
	}
	
}
  
 function ChangeGalleryImage(link)
 {	

	var image_count = GetGalleryImageCount();
	var image_number = parseInt($(link).attr('rel'));
	
	if(image_number < 0)
		image_number = image_count - 1;

	if( image_number > (image_count - 1) )
		image_number = 0;
	
	var image_url = $($('#right_col_gallery_scroller > a')[image_number]).data('url');
	
	$('#zoom #zoom_image').attr( 'src',  image_url );
	AdjustGalleryImageNav( image_number,  image_count );
	return false;
 } 

function SetUpGalleryImageNav(image_number, image_count)
{
	var controls_html = "";

	if(image_number < 0)
		image_number = image_count - 1;

	if( image_number > (image_count - 1) )
		image_number = 0;
		
	controls_html = "<a href='' rel='" + (image_number - 1) + "' class='zoom_prev' onClick='return ChangeGalleryImage(this)'>previous</a>";
		
	controls_html += "<a href='' rel='" + (image_number + 1) + "' class='zoom_next' onClick='return ChangeGalleryImage(this)'>next</a>";

	$('.image_controls').html(controls_html);
}

function AdjustGalleryImageNav(image_number, image_count)
{
	var controls_html = "";
	var prev = image_number - 1;
	var next = image_number + 1;
	
	$('.zoom_prev').attr('rel',  prev);
	$('.zoom_next').attr('rel', next);

}

function GetGalleryImageCount()
{
	return $('#right_col_gallery_scroller > a').length;
}