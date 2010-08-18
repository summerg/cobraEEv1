<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Tagger
 *
 * Tagger AJAX Library File
 *
 * @author DevDemon
 * @copyright Copyright (c) DevDemon
 **/
class Channel_images_ajax
{

	function Channel_images_ajax()
	{
		$this->EE =& get_instance();
		$this->EE->load->library('channel_images_helper');
		$this->EE->lang->loadfile('channel_images');

		if ($this->EE->input->cookie('cp_last_site_id')) $this->site_id = $this->EE->input->cookie('cp_last_site_id');
		else if ($this->EE->input->get_post('site_id')) $this->site_id = $this->EE->input->get_post('site_id');
		else $this->site_id = $this->EE->config->item('site_id');
	}

	// ********************************************************************************* //

	function save_settings()
	{
 		$nSettings = array();
 		$nSettings['channels'] = array();

 		$this->EE->load->helper('url');

 		// -----------------------------------------
		// Channels
		// -----------------------------------------

 		if (is_array($this->EE->input->post('channels')))
 		{
 			$nSettings['channels'] = $this->EE->input->post('channels');

 			foreach ($nSettings['channels'] as $cid => $channel)
 			{
 				// Last Slash
        		if (substr($channel['location_path'], -1, 1) != '/') $channel['location_path'] .= '/';
				if (substr($channel['location_url'], -1, 1) != '/') $channel['location_url'] .= '/';

				// Parse categories
 				$channel['categories'] = explode(',', $channel['categories']);
				foreach ($channel['categories'] as $key => $val)
				{
					if (trim($val) != false ) $channel['categories'][$key] = trim($val);
					else unset($channel['categories'][$key]);
				}

				// Check fort tab_name
				if (isset($channel['tab_name']) == FALSE OR trim($channel['tab_name']) == FALSE) $channel['tab_name'] = 'Channel Images';

				// Sizes
				$sizes = array();
				if (isset($channel['image_size']) == TRUE)
				{
					foreach($channel['image_size'] as $size)
					{
						if (trim($size['name']) == false) continue;
						if (isset($size['grey']) == FALSE) $size['grey'] = 'n';
						$sizes[ url_title($size['name'], TRUE) ] = array( 'w'=>$size['width'], 'h'=>$size['height'], 'q'=>$size['quality'], 'g'=>$size['grey'] );
					}

					unset($channel['image_size']);
				}
				$channel['sizes'] = $sizes;

				$nSettings['channels'][$cid] = $channel;
 			}
 		}

 		// Save the Settings
 		$this->EE->db->select('settings');
		$this->EE->db->where('module_name', 'Channel_images');
		$query = $this->EE->db->get('exp_modules');
		$settings = unserialize( $query->row('settings') );
		$settings['site_id:'.$this->site_id] = $nSettings;

		$this->EE->db->set('settings', serialize($settings));
		$this->EE->db->where('module_name', 'Channel_images');
		$this->EE->db->update('exp_modules');

		exit('Success!?');
	}

	// ********************************************************************************* //

	function upload_file()
	{
		$o = array('success' => 'no', 'last' => 'no' ,'body' => '');

		$channel_id = $this->EE->input->post('channel_id');

		//$this->EE->firephp->fb($_FILES, 'FILES');
		//$this->EE->firephp->fb($_POST, 'POST');

		// Is our $_FILES empty? Commonly when EE does not like the mime-type
		if (isset($_FILES['channel_images_file']) == FALSE)
		{
			$o['body'] = $this->EE->lang->line('ci:file_arr_empty');
			exit( $this->EE->channel_images_helper->generate_json($o) );
		}

		// Lets check for the key first
		if ($this->EE->input->post('key') == FALSE)
		{
			$o['body'] = $this->EE->lang->line('ci:tempkey_missing');
			exit( $this->EE->channel_images_helper->generate_json($o) );
		}

		// Upload file too big (PHP.INI)
		if ($_FILES['channel_images_file']['error'] > 0)
		{
			$o['body'] = $this->EE->lang->line('ci:file_upload_error');
			exit( $this->EE->channel_images_helper->generate_json($o) );
		}

		// Settings
		$settings = $this->EE->channel_images_helper->grab_settings($this->site_id);
		if (isset($settings['channels'][$channel_id]) == false)
		{
			$o['body'] = $this->EE->lang->line('ci:no_settings');
			exit( $this->EE->channel_images_helper->generate_json($o) );
		}
		$settings = $settings['channels'][$channel_id];

		// Last check, does the target dir exist, and is writable
		if (is_really_writable($settings['location_path']) !== TRUE)
		{
			$o['body'] = $this->EE->lang->line('ci:targetdir_error');
			exit( $this->EE->channel_images_helper->generate_json($o) );
		}

		// Temp Dir
    	$temp_dir = 'temp_' . $this->EE->input->post('key');
    	if (@is_dir($settings['location_path'].$temp_dir) === FALSE)
   		{
   			@mkdir($settings['location_path'].$temp_dir, 0777);
   		}

   		// File name
    	$original_filename = strtolower($this->EE->security->sanitize_filename(str_replace(' ', '_', $_FILES['channel_images_file']['name'])));

    	// Are we uploading the original file?
    	$original = ($this->EE->input->post('original') == 'yes') ? TRUE : FALSE;

    	// Extension
    	$extension = '.' . substr( strrchr($original_filename, '.'), 1);

    	if ($original == FALSE)
    	{
    		$size_name = strtolower($this->EE->input->post('size_name'));

    		// New File Name (With Size in Name)
    		$filename = str_replace($extension, "__{$size_name}{$extension}", $original_filename);
    	}
    	else
    	{
    		// The original file stays with the same name
    		$filename = $original_filename;
    	}

    	// Destination Full Path
    	$dest = $settings['location_path'].$temp_dir.'/'. $filename;

		// Move file
		if (@move_uploaded_file($_FILES['channel_images_file']['tmp_name'], $dest) === FALSE)
    	{
    		$o['body'] = $this->EE->lang->line('ci:file_move_error');
	   		exit( $this->EE->channel_images_helper->generate_json($o) );
    	}
    	else
    	{
    		$o['success'] = 'yes';

    		// Is this a GRESCALE image?
    		if ( $this->EE->input->post('grey') == 'y') $this->EE->channel_images_helper->greyscale_img($dest);

    		// Is this the original size?
    		if ($original == TRUE)
    		{
	    		$image = array();

	    		// We need to calculate all the image sizes, And get the smallest one
				$sizes = array();
				foreach ($settings['sizes'] as $name => $v)
				{
					$sizes[ $v['w']*$v['h'] ] = array('name'=>$name, 'height'=>$v['h'], 'width'=>$v['w']);
				}

				ksort($sizes);
				$small_size = reset($sizes);
				$small_size['name'] = strtolower($small_size['name']);
				$bigest_size = end($sizes);
				$bigest_size['name'] = strtolower($bigest_size['name']);

	    		// Size Names (always .JPG)
	    		//$sName = str_replace($extension, "__{$small_size['name']}{$extension}", $original_filename);
	    		//$bName = str_replace($extension, "__{$bigest_size['name']}{$extension}", $original_filename);
	    		$sName = str_replace($extension, "__{$small_size['name']}.jpg", $original_filename);
	    		$bName = str_replace($extension, "__{$bigest_size['name']}.jpg", $original_filename);

	    		$image['small_img_url'] = $settings['location_url'] . $temp_dir . '/' . $sName;
	    		$image['big_img_url'] = $settings['location_url'] . $temp_dir . '/' . $bName;
	    		$image['title'] = str_replace($extension, '', $original_filename);
	    		$image['description'] = '';
	    		$image['image_id'] = 0;
	    		$image['category'] = '';
	    		$image['cover'] = 0;
	    		$image['filename'] = $original_filename;
	    		//$image['image_order'] = '';
	    		//$image['field_id'] = $this->EE->input->post('field_id');

	    		$o['title'] = $image['title'];
	    		$o['filename'] = $original_filename;
	    		$o['field_id'] = $this->EE->input->post('field_id');

				$o['body'] = $this->EE->load->view('pbf_field_single_image', $image, TRUE);
				$o['last'] = 'yes';
    		}
    	}

    	$out = trim($this->EE->channel_images_helper->generate_json($o));

		exit( $out );

	}

	// ********************************************************************************* //

	function delete_image()
	{
		//$this->EE->firephp->fb($_POST, 'POST');

		if ($this->EE->input->post('channel_id') == false) exit('Missing Channel_ID');

		$settings = $this->EE->channel_images_helper->grab_settings($this->EE->input->post('site_id'));
		if (isset($settings['channels'][$this->EE->input->post('channel_id')]) == false) exit('Missing Settings?');
		$settings = $settings['channels'][$this->EE->input->post('channel_id')];

		$path = $settings['location_path'];
		$filename = $this->EE->input->post('filename');
		$extension = '.' . substr( strrchr($filename, '.'), 1);

		// Generate size names and delete
		foreach ($settings['sizes'] as $name => $values)
		{
			$name = strtolower($name);
			$name = str_replace($extension, "__{$name}.jpg", $filename);

			// Delete from file system
			if ($this->EE->input->post('entry_id') > 0)
				@unlink($path.$this->EE->input->post('entry_id').'/'.$name);

			@unlink($path.'temp_'.$this->EE->input->post('key').'/'.$name);
		}

		// Delete original file from system
		if ($this->EE->input->post('entry_id') > 0)
			@unlink($path.$this->EE->input->post('entry_id').'/'.$filename);

		@unlink($path.'temp_'.$this->EE->input->post('key').'/'.$filename);

		// Delete from DB
		if ($this->EE->input->post('image_id') > 0)
		{
			$this->EE->db->from('exp_channel_images');
			$this->EE->db->where('image_id', $this->EE->input->post('image_id'));
			$this->EE->db->delete();
		}

		exit();
	}

// ********************************************************************************* //

	function verify_path()
	{
		$o = '';

		if ($this->EE->input->post('path') != false)
		{
			$dir = $this->EE->input->post('path');

			// Check for slash
			if (substr($dir, -1, 1) != '/')
			{
				$dir = $dir . '/';
			}

			// Is DIR?
			if (is_dir($dir) === TRUE)	$o .= "Is Dir: Passed \n";
			else $o .= "Is Dir: Failed \n";

			// Is READABLE?
			if (is_readable($dir) === TRUE) $o .= "Is Readable: Passed \n";
			else $o .= "Is Readable: Failed\n";

			// Is WRITABLE
			if (is_writable($dir) === TRUE) $o .= "Is Writable: Passed \n";
			else $o .= "Is Writable: Failed \n";

			// CREATE TEST FILE
			$file = uniqid(mt_rand()).'.tmp';
			if (@touch($dir.$file) === TRUE) $o .= "Create Test File: Passed \n";
			else $o .= "Create Test File: Failed \n";

			// DELETE TEST FILE
			if (@unlink($dir.$file) === TRUE) $o .= "Delete Test File: Passed \n";
			else $o .= "Delete Test File: Failed \n";

			// CREATE TEST DIR
			$tempdir = 'temp_' . $this->EE->localize->now;
			if (@mkdir($dir.$tempdir) === TRUE) $o .= "Create Test DIR: Passed \n";
			else $o .= "Create Test DIR: Failed \n";

			// RENAME TEST DIR
			if (@rename($dir.$tempdir, $dir.$tempdir.'temp') === TRUE) $o .= "Rename Test DIR: Passed \n";
			else $o .= "Rename Test DIR: Failed \n";

			// DELETE TEST DIR
			if (@rmdir($dir.$tempdir.'temp') === TRUE) $o .= "Delete Test DIR: Passed \n";
			else $o .= "Delete Test DIR: Failed \n";

		}
		else
		{
			$o .= 'Path is empty';
		}

		exit($o);
	}

	// ********************************************************************************* //

} // END CLASS

/* End of file channel_images_ajax.php  */
/* Location: ./system/expressionengine/third_party/tagger/modules/libraries/channel_images_ajax.php */