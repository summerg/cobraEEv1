// ********************************************************************************* //

var ChannelImages = ChannelImages ? ChannelImages : new Object();

// ********************************************************************************* //

jQuery(document).ready(function(){	
	
	// Initialize SWFUpload
	ChannelImages.SwfUploadInitialize();
	
	ChannelImages.PBFBox = jQuery('#CImagesField');
	ChannelImages.PBFBox.find('.TopBar .UploadBtn').click(function(){delete ChannelImages.CurrentFile; ChannelImages.StartUpload(); });
	ChannelImages.PBFBox.find('.Images').sortable({axis: 'y', cursor: 'move', opacity: 0.6, handle: '.ImageMove', update:ChannelImages.SyncOrderNumbers});
	ChannelImages.PBFBox.find('.ImageCover').live('click', ChannelImages.TogglePrimaryImage);
	ChannelImages.PBFBox.find('.ImageDel').live('click', ChannelImages.DeleteImage);
	
	ChannelImages.SyncOrderNumbers();
	
	// Editable
	ChannelImages.PBFBox.find('.Image').each(function(){
		jQuery(this).find('.iFour').editable({type:'text', submit:'Save', onSubmit:ChannelImages.EditImageDetails});
		jQuery(this).find('.iFive').editable({type:'textarea', submit:'Save', onSubmit:ChannelImages.EditImageDetails});
		jQuery(this).find('.iSix').editable({type:'select', options: ChannelImages.Categories, submit:'Save', onSubmit:ChannelImages.EditImageDetails});
	});
	
	ChannelImages.PBFBox.find('.ImgUrl').fancybox();
});

//********************************************************************************* //

ChannelImages.EditImageDetails = function(content){
	InputClass = jQuery(this).attr('rel');

	if (InputClass == 'desc'){
		jQuery(this).closest('.Image').find('.inputs .' + InputClass).html(content.current);
	}
	else { //if (InputClass == 'title') {
		jQuery(this).closest('.Image').find('.inputs .' + InputClass).attr('value', content.current);
	}
	
};

//********************************************************************************* //

ChannelImages.SyncOrderNumbers = function(){
	ChannelImages.PBFBox.find('.Image').each(function(index){
		jQuery(this).find('input, textarea, select').each(function(){
			attr = jQuery(this).attr('name').replace(/\[images\]\[.*?\]/, '[images][' + (index+1) + ']');
			jQuery(this).attr('name', attr);
		});
	});
	
	ChannelImages.PBFBox.find('.Image').removeClass('Odd');
	ChannelImages.PBFBox.find('.Image:odd').addClass('Odd');
	
	ChannelImages.PBFBox.find('.Assigned .bar strong span').html( ChannelImages.PBFBox.find('.Image').length );
};

//********************************************************************************* //

ChannelImages.TogglePrimaryImage = function(event){
	
	var DivParent = jQuery(this).parent().parent();
	
	
	// Find all images and remove the StarClass & Cover Value
	ChannelImages.PBFBox.find('.Image').each(function(){
		jQuery(this).removeClass('PrimaryImage')
		.find('.StarIcon').removeClass('StarIcon').addClass('StarGreyIcon')
		.closest('.Image').find('.inputs .cover').attr('value', 0);
	});	
	
	// Add the star status to the clicked image
	DivParent.addClass('PrimaryImage').find('.StarGreyIcon').removeClass('StarGreyIcon').addClass('StarIcon')
	.closest('.Image').find('.inputs .cover').attr('value', 1);
	
	return false;
};

//********************************************************************************* //

ChannelImages.DeleteImage = function(event){
	confirm_delete = confirm('Are you sure you want to delete this image?');	
	if (confirm_delete == false) return false;
	
	PostParams = {XID: ChannelImages.XID, ajax_method:'delete_image'};
	PostParams.entry_id = jQuery('input[name=entry_id]').val();
	PostParams.channel_id = EE.publish.channel_id;
	PostParams.site_id = jQuery('#CISiteID').val();
	PostParams.key = jQuery('#CITempKey').val();
	
	ItemObj = jQuery(this).closest('.Image');	
	PostParams.image_id = ItemObj.find('.imageid').val();
	PostParams.filename = ItemObj.find('.filename').val();	
	
	jQuery.post(ChannelImages.AJAX_URL, PostParams);
	
	ItemObj.hide('slow', function(){ jQuery(this).remove(); ChannelImages.SyncOrderNumbers(); } );
	return false;
};

//********************************************************************************* //
//Swfupload Methods
//********************************************************************************* //

/**
* SwfUpload File Queue handler
* @param {Object} File
* @return {Void}
*/
ChannelImages.SwfUploadFileQueHandler = function(File) {

	var File = jQuery('<div class="File Queued" id="' + File.id + '">' + File.name + '</div>'); //' <a href="#" class="DeleteIcon">&nbsp;</a><a href="#" class="EditIcon">&nbsp;</a></div>');
	ChannelImages.PBFBox.find('.TopBar .Files').append(File);
	
};

//********************************************************************************* //

/**
* SwfUpload File Dialog Complete
* @param {Object} FilesSelected - number of files selected 
* @param {Object} FilesQueued - number of files queued
* @param {Object} TotalFilesQueued - absolute total number of files in the queued
* @return {Void}
*/
ChannelImages.SwfUploadFileDialogComplete = function(FilesSelected, FilesQueued, TotalFilesQueued){
	ChannelImages.PBFBoxProgress = ChannelImages.PBFBox.find('.TopBar .Buttons .UploadProgress'); // Cache the Progress Handler
	ChannelImages.PBFBoxProgress.show();
	// ChannelImages.PBFBoxProgress.children('.progress').css('width', '100%'); // For now (no progress on resize upload)
	ChannelImages.PBFBox.find('.TopBar .Files .File').click(); // IE is slow with parsing. clicking it makes it parse!
};

//********************************************************************************* //

ChannelImages.SwfUploadResizeStart = function(file, width, height, encoding, quality) {
	ChannelImages.PBFBoxProgress.find('.progress span strong').html('Resizing to Size: ' + ChannelImages.ImageSizes[ChannelImages.CurrentFile.data('size')].n + ' (' + width + 'x' + height + ')' );
	//ChannelImages.Debug('Resizing to Size: ' + ChannelImages.ImageSizes[ChannelImages.CurrentFile.data('size')].n + ' (' + width + 'x' + height + ') ' + file.name);
};

//********************************************************************************* //

/**
 * SwfUpload Upload Start Handler
 * @param {Object} file
 * @return {Void}
 */
ChannelImages.SwfUploadUploadStartHandler = function(file){
	if (ChannelImages.CurrentFile.data('OriginalUpload') == 'yes'){
		ChannelImages.PBFBoxProgress.find('.progress span strong').html('Uploading Original File');
		ChannelImages.Debug('Uploading Original File (ID:' + ChannelImages.Identifier + ') ');
	}
	else {
		ChannelImages.PBFBoxProgress.find('.progress span strong').html('Uploading Size: ' + ChannelImages.ImageSizes[ChannelImages.CurrentFile.data('size')].n);
		ChannelImages.Debug('Uploading Size: ' + ChannelImages.ImageSizes[ChannelImages.CurrentFile.data('size')].n + ' (ID:' + ChannelImages.Identifier + ') ');
	}
	
};

//********************************************************************************* //

ChannelImages.StartUpload = function(){
	
	ChannelImages.Error = false;
	
	// First check if we are working on another file first.
	if (typeof ChannelImages.CurrentFile == 'undefined'){
		
		// Grab the next queued item
		ChannelImages.CurrentFile = ChannelImages.PBFBox.find('.TopBar .Queued:first');
		
		// Grab our Identifier
		ChannelImages.Identifier = ChannelImages.CurrentFile.attr('id');
	}
	
		
	// If Identifier is undefined then there are no more to upload
	if (typeof ChannelImages.Identifier == 'undefined') return false;
	
	// Add the UploadingClass
	ChannelImages.CurrentFile.removeClass('Queued').addClass('Uploading');
	
	
	// Are we going to upload the original file?
	if (ChannelImages.CurrentFile.data('OriginalUpload') != 'yes'){

		// Is this a new image?
		if ( typeof ChannelImages.CurrentFile.data('size') != 'number' ) {

			// Set which size we already did
			ChannelImages.CurrentFile.data('size', 0);
			
			// Add image size name & Greyscale
			ChannelImages.SwfUploadInstance.addFileParam(ChannelImages.Identifier, 'size_name', ChannelImages.ImageSizes[0].n);
			ChannelImages.SwfUploadInstance.addFileParam(ChannelImages.Identifier, 'grey', ChannelImages.ImageSizes[0].g);

			// Upload the FIRST size 
			ChannelImages.SwfUploadInstance.startResizedUpload(ChannelImages.Identifier, ChannelImages.ImageSizes[0].w, ChannelImages.ImageSizes[0].h, SWFUpload.RESIZE_ENCODING.JPEG, ChannelImages.ImageSizes[0].q);
		}
		else {
			
			NextSize = Number(ChannelImages.CurrentFile.data('size')) + 1;
			
			// Set which size we already did
			ChannelImages.CurrentFile.data('size', NextSize);
			
			// Remove old image size name from param & add new one
			ChannelImages.SwfUploadInstance.removeFileParam(ChannelImages.Identifier, 'size_name');
			ChannelImages.SwfUploadInstance.addFileParam(ChannelImages.Identifier, 'size_name', ChannelImages.ImageSizes[NextSize].n);	
			
			// Remove old greyscale settings
			ChannelImages.SwfUploadInstance.removeFileParam(ChannelImages.Identifier, 'grey');
			ChannelImages.SwfUploadInstance.addFileParam(ChannelImages.Identifier, 'grey', ChannelImages.ImageSizes[NextSize].g);
			
			// Upload the NEXT size
			ChannelImages.SwfUploadInstance.startResizedUpload(ChannelImages.Identifier, ChannelImages.ImageSizes[NextSize].w, ChannelImages.ImageSizes[NextSize].h, SWFUpload.RESIZE_ENCODING.JPEG, ChannelImages.ImageSizes[NextSize].q);
		}		
	}
	else {
		ChannelImages.CurrentFile.data('OriginalUpload', 'yes');
		ChannelImages.SwfUploadInstance.removeFileParam(ChannelImages.Identifier, 'grey');
		ChannelImages.SwfUploadInstance.removeFileParam(ChannelImages.Identifier, 'size_name');
		ChannelImages.SwfUploadInstance.addFileParam(ChannelImages.Identifier, 'original', 'yes');
		ChannelImages.SwfUploadInstance.startUpload(ChannelImages.Identifier);
	}
	
	
	
	//if (ChannelImages.TEMPCOUNT == 2) ChannelImages.SwfUploadInstance.startUpload(ChannelImages.Identifier); // Start the upload!

};

//********************************************************************************* //

/**
 * SwfUpload File Progress Handler
 * @param {Object} file
 * @param {Object} bytesLoaded
 * @param {Object} bytesTotal
 * @return {Void}
 */
ChannelImages.SwfUploadFileUploadProgressHandler = function(file, bytesLoaded, bytesTotal){
	if (ChannelImages.CurrentFile.data('OriginalUpload') == 'yes'){
		ChannelImages.PBFBoxProgress.children('.progress').css('width', file.percentUploaded+'%');
	}
	//ChannelImages.PBFBoxProgress.children('.progress').css('width', file.percentUploaded+'%');
	//ChannelImages.PBFBoxProgress.find('.progress span strong').html(SWFUpload.speed.formatPercent(file.percentUploaded));
	//ChannelImages.PBFBoxProgress.find('.progress span em').html(SWFUpload.speed.formatBPS(file.averageSpeed / 10));
	// Files.Temp.FileProgress.find('.time span').html(SWFUpload.speed.formatTime(file.timeRemaining));
	
	//SWFUpload.speed.formatPercent(file.percentUploaded)
	//console.log(SWFUpload.speed.formatBPS(file.averageSpeed));
	//SWFUpload.speed.formatBPS(file.currentSpeed);
	//SWFUpload.speed.formatBPS(file.averageSpeed);
	//SWFUpload.speed.formatBPS(file.movingAverageSpeed);
	//SWFUpload.speed.formatTime(file.timeRemaining);
	//SWFUpload.speed.formatTime(file.timeElapsed);
	//SWFUpload.speed.formatPercent(file.percentUploaded);
	//SWFUpload.speed.formatBytes(file.sizeUploaded);
};

//********************************************************************************* //

/**
 * File Upload Success Handler
 * @param {Object} file object
 * @param {Object} server data
 * @param {Object} received response
 * @return {Void}
 */
ChannelImages.SwfUploadFileUploadResponseParser = function(file, serverData, response){
	
	// If evalJSON fails, probally a php error occured
	try {
		rData = JSON.parse(serverData);
		//ChannelImages.Debug('Received server response. (correctly parsed as JSON)');
	}
	catch(errorThrown) {
		ChannelImages.ErrorMSG('Server response was malformated, probally a PHP error');
		ChannelImages.Debug("Server response was malformated, probally a PHP error. \n LAST 3 CHARS: " + serverData.substr(-3,3) + "\n RETURNED RESPONSE: \n" + serverData);		
		ChannelImages.CurrentFile.removeClass('Uploading').addClass('Error');
		delete ChannelImages.CurrentFile;
		delete ChannelImages.Identifier;
		return false;
	}
	
	// Parse the server data
	ChannelImages.FileUploadSuccessHandler(rData);
		
	return;
};

//********************************************************************************* //

ChannelImages.FileUploadSuccessHandler = function(rData){
	
	if (rData.success == 'yes') {
		
		// Set the progress bar to 0 again
		ChannelImages.PBFBoxProgress.children('.progress').css('width', '0%');
		
		// What number is the next size?
		NextSize = Number(ChannelImages.CurrentFile.data('size')) + 1;
		
		// Does it exist?
		if (typeof ChannelImages.ImageSizes[NextSize] != 'undefined'){

			// ReQueue the file and Start the upload progress
			ChannelImages.SwfUploadInstance.requeueUpload(ChannelImages.Identifier);
			ChannelImages.StartUpload();
		}
		else {
			
			// Did we already uploaded the orginal file?
			if (ChannelImages.CurrentFile.data('OriginalUpload') == 'yes') {
				// Remove the uploading class and mark it DONE
				ChannelImages.CurrentFile.removeClass('Uploading').addClass('Done');
				
				// Unset the current file reference
				delete ChannelImages.CurrentFile;
			}
			else {
				// We are going to upload the original file.
				ChannelImages.CurrentFile.data('OriginalUpload', 'yes');
				ChannelImages.SwfUploadInstance.requeueUpload(ChannelImages.Identifier);
			}
			
			// Shoot the next upload
			ChannelImages.StartUpload();
		}		
		
		// Add to Images (if this is the last in the batch)
		if (rData.last == 'yes'){
			rData.body = rData.body.replace('#REPLACE#',
				"<input name='field_id_"+rData.field_id+"[images][0][title]' value='" + rData.title + "' class='title'> " +
				"<textarea name='field_id_"+rData.field_id+"[images][0][desc]' class='desc'></textarea> " +
				"<input name='field_id_"+rData.field_id+"[images][0][category]' value='' class='category'> " +
				"<input name='field_id_"+rData.field_id+"[images][0][imageid]' value='0' class='imageid'> " +
				"<input name='field_id_"+rData.field_id+"[images][0][filename]' value='" + rData.filename + "' class='filename'/> " +
				"<input name='field_id_"+rData.field_id+"[images][0][cover]' value='0' class='cover'> ");
			
			ChannelImages.PBFBox.find('.Assigned .Images').append(rData.body);
			ChannelImages.PBFBox.find('.Assigned .Headers').show();
			ChannelImages.PBFBox.find('.Assigned .NoImages').hide();
			
			// Get the appended div so we can activate stuff
			var Appended = ChannelImages.PBFBox.find('.Assigned .Images .Image:last');
			Appended.find('.iFour').editable({type:'text', submit:'Save', onSubmit:ChannelImages.EditImageDetails});
			Appended.find('.iFive').editable({type:'textarea', submit:'Save', onSubmit:ChannelImages.EditImageDetails});
			Appended.find('.iSix').editable({type:'select', options: ChannelImages.Categories, submit:'Save', onSubmit:ChannelImages.EditImageDetails});

			ChannelImages.SyncOrderNumbers();
			ChannelImages.PBFBox.find('.ImgUrl').fancybox();
		}
	}
	else {
		ChannelImages.CurrentFile.removeClass('Uploading').addClass('Error');	
		ChannelImages.ErrorMSG(rData.body);
		ChannelImages.Debug('ERROR: ' + rData.body);		
		delete ChannelImages.Identifier;
		//delete ChannelImages.CurrentFile;
	}


};

//********************************************************************************* //

ChannelImages.SwfUploadFileUploadCompleteHandler = function(){
	if (ChannelImages.Error == false) ChannelImages.PBFBoxProgress.find('.progress span strong').html('Done Uploading Images.');
};

//********************************************************************************* //

ChannelImages.SwfUploadFileUploadErrorHandler = function(file, error, message){
	ChannelImages.Debug('FileUploadError:' + error + ' MSG:' + message);
};

//********************************************************************************* //

ChannelImages.SwfUploadCheckFlash = function(){
	if (! this.support.loading) {
		alert("You need the Flash Player to use SWFUpload.");
		return false;
	} else if (! this.support.imageResize) {
		alert("You need Flash Player 10 to upload resized images.");
		return false;
	}
};

//********************************************************************************* //

/**
 * SwfUpload Settings Object
 * @return {Void}
 */
ChannelImages.SwfUploadInitialize = function(){
	
	ChannelImages.SwfUploadInstance = new SWFUpload({
		// Backend Settings
		flash_url : ChannelImages.ThemeURL + 'images/swfupload.swf',
		upload_url: ChannelImages.AJAX_URL,
		post_params: { XID:ChannelImages.XID, ajax_method:'upload_file', channel_id:EE.publish.channel_id, key:jQuery('#CITempKey').val(), field_id:jQuery('#CIFieldID').val() },
		file_post_name: 'channel_images_file',
		// prevent_swf_caching: true,
		assume_success_timeout: 0,
		// debug: true,
	
		// File Upload Settings
		file_size_limit : 10240,	// 10MB
		file_types : '*.jpg;*.jpeg;*.png',
		file_types_description : 'JPG Images; PNG Image',
		file_upload_limit : 0,
		file_queue_limit : 0,
	
		// Event Handler Settings
		swfupload_preload_handler : ChannelImages.SwfUploadCheckFlash,
		swfupload_load_failed_handler : function(){},
		file_dialog_start_handler : function(){},
		file_queued_handler : ChannelImages.SwfUploadFileQueHandler,
		file_queue_error_handler : function(){},
		file_dialog_complete_handler : ChannelImages.SwfUploadFileDialogComplete, 
		upload_resize_start_handler : ChannelImages.SwfUploadResizeStart,
		upload_start_handler : ChannelImages.SwfUploadUploadStartHandler,
		upload_progress_handler : ChannelImages.SwfUploadFileUploadProgressHandler,
		upload_error_handler : ChannelImages.SwfUploadFileUploadErrorHandler,
		upload_success_handler : ChannelImages.SwfUploadFileUploadResponseParser,
		upload_complete_handler : ChannelImages.SwfUploadFileUploadCompleteHandler,

	
		// Button Settings
		button_image_url : '', // Relative to the SWF file
		button_placeholder_id : 'ChannelImagesSelect',
		button_width: 200,
		button_height: 20,
		button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
		button_cursor: SWFUpload.CURSOR.HAND,
		button_action: SWFUpload.BUTTON_ACTION.SELECT_FILES, //SWFUpload.BUTTON_ACTION.SELECT_FILE for single files
		
		// Debug Settings
		debug: false
	
	});
};

//********************************************************************************* //

ChannelImages.ErrorMSG = function (Msg){	
	ChannelImages.Error = true;
	ChannelImages.PBFBoxProgress.find('.progress span strong').html('<span style="color:red">' + Msg + '</span>');
};

//********************************************************************************* //

ChannelImages.Debug = function(msg){
	try {
		console.log(msg);
	} 
	catch (e) {	}
};

//********************************************************************************* //