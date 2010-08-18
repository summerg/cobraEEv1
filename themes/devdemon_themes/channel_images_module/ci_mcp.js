// ********************************************************************************* //

var ChannelImages = ChannelImages ? ChannelImages : new Object();

// ********************************************************************************* //

jQuery(document).ready(function(){	
	
	var DevDemonMCP = jQuery('#mainContent');
	DevDemonMCP.find('.TopStrip input').click(ChannelImages.ToggleChannel); // Channel/Weblog Selection
	DevDemonMCP.find('.WeblogSection .VerifyPath').live('click', ChannelImages.VerifyPath); // Verify File
	DevDemonMCP.find('button.SaveSettings').click(ChannelImages.SaveSettings); // Save Settings
	
	DevDemonMCP.find('.ImageSizes .AddSize').live('click', ChannelImages.AddSize);
	DevDemonMCP.find('.ImageSizes .DelSize').live('click', ChannelImages.DelSize);

	ChannelImages.SyncSizes();
	// jQuery('#Settings .TestIt').click(ChannelImages.TestIt);

});

// ********************************************************************************* //

ChannelImages.ToggleChannel = function(event){
	var Target = jQuery(event.target);
	
	// Are removing?
	var AddMode = true;
	if ( Target.parent().hasClass('Assigned') ) AddMode = false;
	
	// Get Parent && Toggle Class
	Target.parent().toggleClass('Assigned');
	
	// Should we add a blank one?
	if (AddMode == true){
		var Cloned = jQuery('#BlankWeblog .WeblogSection').clone();
		Cloned.find('input').each(function(){
			Attr = jQuery(this).attr('name').replace(/\[\]/, '[' + Target.val() + ']');
			jQuery(this).attr('name', Attr);			
		});
		Cloned.attr('rel', Target.val()).find('.sHead').html( Target.siblings('label').html() );
		jQuery('#WeblogsCollector').append(Cloned);
		ChannelImages.SyncSizes();
	}
	
	// Else we remove it ofcourse!
	else {
		jQuery('#WeblogsCollector .WeblogSection[rel=' + Target.val() + ']').hide('slow', function(){jQuery(this).remove();});
	}
	
	return;
};

// ********************************************************************************* //

ChannelImages.VerifyPath = function(event){
	var Target = jQuery(event.target);
	Target.removeClass('DiscIcon').addClass('LoadingIcon');
	
	jQuery.post(ChannelImages.AJAX_URL, {XID:ChannelImages.XID, ajax_method:'verify_path', path:Target.closest('.Elem').find('input').val()}, function(Response){
		Target.removeClass('LoadingIcon').addClass('DiscIcon');
		alert(Response);
	});
	
	return false;
};


// ********************************************************************************* //

ChannelImages.SaveSettings = function(event){
	jQuery(event.target).children('.LoadingIcon').show();
	
	jQuery('#mainContent form').ajaxSubmit({
		url:ChannelImages.AJAX_URL, data: {XID:ChannelImages.XID, ajax_method:'save_settings'},
		success:function(Response){
			jQuery(event.target).children('.LoadingIcon').hide();		
			jQuery('#mainContent .SettingsSaved').show();
	}});
	
	
	return false;
};

// ********************************************************************************* //

ChannelImages.TestIt = function(){
	
	var oParent = jQuery(this).closest('.Section');
	oParent.find('.Loading').show();
	oParent.find('.TestItResult').empty();
	
	jQuery.post(ChannelImages.AJAX_URL,
		{XID:ChannelImages.XID, ajax_method:'test_location', server_path: oParent.find('.ServerPath').val() },
		function(data){
			oParent.find('.TestItResult').html(data);
			oParent.find('.Loading').hide();
	});
	
	return false;
};

//********************************************************************************* //

ChannelImages.AddSize = function(event){
	WeblogID = jQuery(event.target).attr('rel');

	jQuery('#BaseSize').clone().attr('id', '').removeClass('hidden').insertBefore(jQuery(event.target)).find('input').each(function(){
		attr = jQuery(this).attr('name').replace(/channels\[ID\]/, 'channels['+WeblogID+']');
		jQuery(this).attr('name', attr);
	}).execute(ChannelImages.SyncSizes);

	return false;
};

//********************************************************************************* //

ChannelImages.DelSize = function(){	
	jQuery(this).parent().hide('slow', function(){ jQuery(this).remove(); ChannelImages.SyncSizes(); });
	return false;
};

// ********************************************************************************* //

ChannelImages.SyncSizes = function(){	
	jQuery('#WeblogsCollector .ImageSizes .ImageSizesResult .size').each(function(index){
		jQuery(this).find('input, textarea, select').each(function(){
			attr = jQuery(this).attr('name').replace(/\[image_size\]\[.*?\]/, '[image_size][' + (index+1) + ']');
			jQuery(this).attr('name', attr);
		});
	});
};
