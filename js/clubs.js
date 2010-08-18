$(document).ready
(
	function() 
	{
		// activate fancy zoom	
		$('.technology_link').fancyZoom({width:550, height:460});
		$('.comparison_link').fancyZoom({width:620, height:450});
		$('.accessory_link').fancyZoom({width:620, height:620});
				
		// use hash to lightbox panel for fancy zoom to work
		function ReplaceFancyZoomLink(elem)
		{
			$(elem).data('url', $(elem).attr('href'));
			$(elem).data('text', $(elem).attr('rel'));
			$(elem).attr('href', '#lightbox');
		}
		
		$('.technology_link').each
			(
				function(i)
				{
					ReplaceFancyZoomLink(this);
				}
			);
	    
		$('.technology_link').click
		(	
			function()
			{					
				var pnlLightbox = $('#lightbox');
				pnlLightbox.html("");
				LoadContentInLightWindow(this, $(this).data('url'), $(this).data('text'), 'technology', $(this).parents("div:first").attr("id"), $(this).html());
			}
		);			
		
		$('.comparison_link').each
		(
			function(i)
			{
				ReplaceFancyZoomLink(this);
			}
		);
    
		$('.comparison_link').click
		(
			function()
			{	
				var pnlLightbox = $('#lightbox');
				pnlLightbox.html("");
				LoadContentInLightWindow(this, $(this).data('url'), '', 'comparison', '', $(this).html());
			}
		);	
		
		$('.accessory_link').each
			(
				function(i)
				{
					ReplaceFancyZoomLink(this);
				}
			);
	    
		$('.accessory_link').click
		(	
			function()
			{					
				var pnlLightbox = $('#lightbox');
				pnlLightbox.html("");
				LoadContentInLightWindow(this, $(this).data('url'), '', 'accessory', '', '');
			}
		);
		
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

function LoadContentInLightWindow(element, strUrl, strText,  strContentType, strProductType, strContentHeader)
{	
	var pnlLightbox = $('#lightbox');
	//alert(strProductType + strContentHeader);
	var imageHeaderSrc = "";
	if(strContentHeader == "Driver Technology")
		imageHeaderSrc = "driver_technology_txt.jpg";
	if(strContentHeader == "Fairway Technology")
		imageHeaderSrc = "fairway_technology_txt.jpg";
	if(strContentHeader == "Utilities Technology")
		imageHeaderSrc = "utilities_technology_txt.jpg";
	if(strContentHeader == "Iron Technology")
		imageHeaderSrc = "iron_technology_txt.jpg";
	if(strContentHeader == "Driver Comparison")
		imageHeaderSrc = "driver_comparison_txt.jpg";
	if(strContentHeader == "Iron Comparison")
		imageHeaderSrc = "iron_comparison_txt.jpg";
	
	imageHTML = "<div id='club_overview'>"
	if(strContentType=="technology")
	{
		var gallery = PaginatedGallery($('#driver_tech_gallery'), 1, 408);
		var gallery = PaginatedGallery($('#fairway_tech_gallery'), 1, 408);
		var gallery = PaginatedGallery($('#utility_tech_gallery'), 1, 408);
		var gallery = PaginatedGallery($('#iron_tech_gallery'), 1, 408);
		
		imageHTML += "<div id='image_header'><img src='/images/products/" + imageHeaderSrc +"' alt='" + strContentHeader + "' /></div><div id='image_box'><img id='zoom_image' src='" + strUrl +"' alt='' /></div><div id='zoom_image_text'>" + strText + "</div><div class='image_controls'>"
		imageHTML += "</div>";		
	}
	else if(strContentType=="comparison")
	{
		imageHTML += "<div id='image_header'><img src='/images/products/" + imageHeaderSrc +"' alt='" + strContentHeader + "' /></div><img id='zoom_image' src='" + strUrl +"' alt='' />"
	}
	else
	{
		imageHTML += "<img id='zoom_image' src='" + strUrl +"' alt='' />"
	}
	
	imageHTML += "</div>"
	pnlLightbox.html(imageHTML);	
	$('#zoom_content').html('test');
	SetUpGalleryImageNav($(element).parent().find('> a').index(element), GetGalleryImageCount(strProductType), strProductType); 
}
  
 function ChangeGalleryImage(link)
 {		
	var container = $(link).attr('title');
	var image_count = GetGalleryImageCount(container);
	var image_number = parseInt($(link).attr('rel'));
	
	if(image_number < 0)
		image_number = image_count - 1;

	if( image_number > (image_count - 1) )
		image_number = 0;
	
	var image_url = $($('#' + container + ' > a')[image_number]).data('url');
	var image_text = $($('#' + container + ' > a')[image_number]).data('text');
	
	$('#zoom #zoom_image').attr( 'src',  image_url );
	$('#zoom #zoom_image_text').html(image_text);
	AdjustGalleryImageNav( image_number,  image_count );
	return false;
 } 

function SetUpGalleryImageNav(image_number, image_count, container)
{
	if(image_count > 1)
	{
		var controls_html = "";

		if(image_number < 0)
			image_number = image_count - 1;

		if( image_number > (image_count - 1) )
			image_number = 0;
			
		controls_html = "<a href='' rel='" + (image_number - 1) + "' class='zoom_prev' title='" + container + "' onClick='return ChangeGalleryImage(this)'>previous</a>";
			
		controls_html += "<a href='' rel='" + (image_number + 1) + "' class='zoom_next'  title='" + container + "' onClick='return ChangeGalleryImage(this)'>next</a>";

		$('.image_controls').html(controls_html);
	}
}

function AdjustGalleryImageNav(image_number, image_count)
{
	var controls_html = "";
	var prev = image_number - 1;
	var next = image_number + 1;
	
	$('.zoom_prev').attr('rel',  prev);
	$('.zoom_next').attr('rel', next);

}

function GetGalleryImageCount(container)
{
	return $('#' + container + ' > a').length;
}