<?php if (!defined('BASEPATH')) die('No direct script access allowed');

$lang = array(

// Required for MODULES page
'channel_images'					=>	'Channel Images',
'channel_images_module_name'		=>	'Channel Images',
'channel_images_module_description'	=>	'Enables images to be associated with an entry.',

//----------------------------------------
'ci:settings'		=>	'Settings',
'ci:docs' 			=>	'Documentation',

// MCP
'ci:choose_weblog'	=>	'Channel Images enabled weblogs/channels?',
'ci:tab_name'		=>	'Publish Tab Name',
'ci:location_path'	=>	'Server Location Path',
'ci:location_url'	=>	'Location URL',
'ci:verify_path'	=>	'Verify Path',
'ci:categories'	=>	'Categories',
'ci:categories_explain'=>	'Seperate each category with a comma.',
'ci:image_sizes'	=>	'Image Sizes',
'ci:name'			=>	'Name',
'ci:width_px'		=>	'Width (px)',
'ci:height_px'		=>	'Height (px)',
'ci:quality'		=>	'Quality',
'ci:add_size'		=>	'Add new image size',
'ci:small'			=>	'Small',
'ci:medium'			=>	'Medium',
'ci:large'			=>	'Large',
'ci:greyscale'		=>	'Grey?',
'ci:settings_saved' => 'Settings Saved!',

// PBF
'ci:upload_images'	=>	'Upload Images',
'ci:time_remaining'	=>	'Time Remaining',
'ci:dupe_field'		=>	'Only one Channel Images field can be used at once.',
'ci:missing_settings'=>	'Missing Channel Images settings for this channel.',
'ci:assigned_images'=>	'Assigned Images',
'ci:no_images'		=>	'No images have yet been uploaded.',

'ci:id'				=>	'ID',
'ci:image'			=>	'Image',
'ci:title'			=>	'Name',
'ci:desc'			=>	'Description',
'ci:category'		=>	'Category',
'ci:filename'		=>	'Filename',
'ci:actions'		=>	'Actions',
'ci:actions:edit'	=>	'Edit',
'ci:actions:cover'	=>	'Cover',
'ci:actions:move'	=>	'Move',
'ci:actions:del'	=>	'Delete',

// Errors
'ci:file_arr_empty'	=> 'No file was uploaded or file is not allowed by EE.(See EE Mime-type settings).',
'ci:tempkey_missing'	=> 'The temp key was not found',
'ci:file_upload_error'	=> 'No file was uploaded. (Maybe filesize was too big)',
'ci:no_settings'		=> 'No weblog settings exist for this channel/weblog.',
'ci:file_to_big'		=> 'The file is too big. (See module settings for max file size).',
'ci:extension_not_allow'=> 'The file extension is not allowed. (See module settings for file extensions)',
'ci:targetdir_error'	=> 'The target directory is either not writable or does not exist',
'ci:file_move_error'	=> 'Failed to move uploaded file to the temp directory, please check upload path permissions etc.',


// END
''=>''
);

/* End of file lang.channel_images.php */
/* Location: ./system/expressionengine/third_party/channel_images/lang.channel_images.php */