<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Channel Images
 *
 * Fieldtype Channel Images
 *
 * @author DevDemon
 * @copyright Copyright (c) DevDemon
 **/
class Channel_images_ft extends EE_Fieldtype {

	var $info = array(
		'name' 		=> 'Channel Images',
		'version'	=> '2.1.5'
	);

	function Channel_images_ft()
	{
		parent::EE_Fieldtype();

		if ($this->EE->input->cookie('cp_last_site_id')) $this->site_id = $this->EE->input->cookie('cp_last_site_id');
		else if ($this->EE->input->get_post('site_id')) $this->site_id = $this->EE->input->get_post('site_id');
		else $this->site_id = $this->EE->config->item('site_id');


	}

	// ********************************************************************************* //

	function display_field($data)
	{
		// Load Models & Libraries & Helpers
		$this->EE->lang->loadfile('channel_images');
		$this->EE->load->library('channel_images_helper');
		//$this->EE->load->model('tagger_model');

		$this->EE->channel_images_helper->define_theme_url();

		$vData = array();
		$vData['dupe_field'] = FALSE;
		$vData['missing_settings'] = FALSE;
		$vData['field_name'] = $this->field_name;
		$vData['field_id'] = $this->field_id;
		$vData['site_id'] = $this->site_id;
		$vData['temp_key'] = $this->EE->localize->now;
		$vData['channel_id'] = ($this->EE->input->get_post('channel_id') != FALSE) ? $this->EE->input->get_post('channel_id') : 0;
		$vData['entry_id'] = ($this->EE->input->get_post('entry_id') != FALSE) ? $this->EE->input->get_post('entry_id') : FALSE;
		$vData['num_images'] = 0;
		$vData['assigned_images'] = '';

		// Settings
		$this->EE->db->select('settings');
		$this->EE->db->where('module_name', 'Channel_images');
		$query = $this->EE->db->get('exp_modules');
		$vData['settings'] = unserialize( $query->row('settings') );
		$vData['settings'] = (isset($vData['settings']['site_id:'.$this->site_id]) == TRUE) ? $vData['settings']['site_id:'.$this->site_id] : array( 'channels' => array() );
		$vData['settings'] = (isset($vData['settings']['channels'][$vData['channel_id']]) == TRUE) ? $vData['settings']['channels'][$vData['channel_id']] : array();

		// Settings SET?
		if (isset($vData['settings']['sizes']) == FALSE OR empty($vData['settings']['sizes']) == TRUE)
		{
			$vData['missing_settings'] = TRUE;
			return $this->EE->load->view('pbf_field', $vData, TRUE);
		}

		// We only want 1 channel_images field (for now)
		if (isset( $this->EE->session->cache['ChannelImages']['Dupe_Field'] ) == FALSE)
		{
			$this->EE->session->cache['ChannelImages']['Dupe_Field'] = TRUE;
		}
		else
		{
			// It's a dupe field, show a message
			$vData['dupe_field'] = TRUE;
			return $this->EE->load->view('pbf_field', $vData, TRUE);
		}

		// Post DATA?
		if (isset($_POST[$this->field_name])) {
			$data = $_POST[$this->field_name];
		}

		// Add Global JS & CSS & JS Scripts
		$this->EE->channel_images_helper->mcp_meta_parser('gjs', '', 'ChannelImages');
		$this->EE->channel_images_helper->mcp_meta_parser('css', DEVDEMON_THEME_URL . 'images/global.css', 'devdemon-global');
		$this->EE->channel_images_helper->mcp_meta_parser('css', DEVDEMON_THEME_URL . 'channel_images_module/ci_pbf.css', 'ci-pbf');
		$this->EE->channel_images_helper->mcp_meta_parser('js', DEVDEMON_THEME_URL . 'js/jquery.editable.js', 'jquery.editable', 'jquery');
		$this->EE->channel_images_helper->mcp_meta_parser('js', DEVDEMON_THEME_URL . 'js/jquery.base64.js', 'jquery.base64', 'jquery');
		$this->EE->channel_images_helper->mcp_meta_parser('js', DEVDEMON_THEME_URL . 'js/swfupload.js', 'swfupload', 'swfupload');
		$this->EE->channel_images_helper->mcp_meta_parser('js', DEVDEMON_THEME_URL . 'js/swfupload.queue.js', 'swfupload.queue', 'swfupload');
		$this->EE->channel_images_helper->mcp_meta_parser('js', DEVDEMON_THEME_URL . 'js/swfupload.speed.js', 'swfupload.speed', 'swfupload');
		$this->EE->channel_images_helper->mcp_meta_parser('js', DEVDEMON_THEME_URL . 'channel_images_module/ci_pbf.js', 'ci-pbf');
		$this->EE->cp->add_js_script(array('plugin' => 'fancybox'));
		$this->EE->cp->add_to_head('<link type="text/css" rel="stylesheet" href="'.BASE.AMP.'C=css'.AMP.'M=fancybox" />');

		// Add categories to JS
		$cats = array();
		foreach ($vData['settings']['categories'] as $cat) $cats[$cat] = $cat;
		$this->EE->cp->add_to_head("<script type='text/javascript'> ChannelImages.Categories = " . $this->EE->javascript->generate_json($cats) . "; </script>");

		// Grab our image sizes!
		$this->EE->cp->add_to_head('<script type="text/javascript">' . $this->EE->channel_images_helper->image_sizes_js($this->site_id, $vData['channel_id'], $vData['settings']) . '</script>');

		// Grab assigned images
		if ($this->EE->input->get_post('entry_id') != FALSE)
		{
			$entry_id = $this->EE->input->get_post('entry_id');

			$this->EE->db->select('*');
			$this->EE->db->from('exp_channel_images');
			$this->EE->db->where('entry_id', $this->EE->input->get_post('entry_id'));
			$this->EE->db->order_by('image_order');
			$query = $this->EE->db->get();

			// We need to calculate all the image sizes, And get the smallest one
			$sizes = array();
			foreach ($vData['settings']['sizes'] as $name => $v)
			{
				$sizes[ $v['w']*$v['h'] ] = array('name'=>$name, 'height'=>$v['h'], 'width'=>$v['w']);
			}

			ksort($sizes);
			$small_size = reset($sizes);
			$bigest_size = end($sizes);

			foreach ($query->result() as $row)
			{
				// Small Image URL
				//$extension = '.' . substr( strrchr($row->filename, '.'), 1);
				$extension = '.jpg';
				$row->small_img_url = $vData['settings']['location_url'].$entry_id.'/'.str_replace($extension, '__'.strtolower($small_size['name']).$extension, $row->filename);
				$row->big_img_url = $vData['settings']['location_url'].$entry_id.'/'.str_replace($extension, '__'.strtolower($bigest_size['name']).$extension, $row->filename);

				// ReAssign Field ID
				$row->field_id = $this->field_id;

				$vData['assigned_images'] .= $this->EE->load->view('pbf_field_single_image', $row, TRUE);
			}

			$vData['num_images'] = $query->num_rows();
		}

		return $this->EE->load->view('pbf_field', $vData, TRUE);
	}

	// ********************************************************************************* //

	function save($data)
	{
		return serialize($data);
	}

	// ********************************************************************************* //

	function post_save($data)
	{
		$this->EE->load->library('channel_images_helper');
		$data = unserialize($data);
		$entry_id = $this->settings['entry_id'];
		$channel_id = $this->EE->input->post('channel_id');
		$settings = $this->EE->channel_images_helper->grab_settings($this->site_id);

		// Do we need to skip?
		if (isset($data['skip']) == TRUE && $data['skip'] == 'y') return;
		if (isset($data['images']) == FALSE) return;

		// What is our location path?
		$location_path = $settings['channels'][$channel_id]['location_path'];

		// Our Key
		$key = $data['key'];

		// Temp dir?
		$temp_dir = $location_path.'temp_'.$key;

		// Does the destination folder already exist?
		if (@is_dir($location_path.$entry_id) == FALSE)
		{
			// Lets rename sthe temp_folder
			@rename($temp_dir, $location_path.$entry_id);
		}
		else
		{
			// Folder exists, lets copy over new files
			if (@is_dir($temp_dir) === TRUE)
			{
				// Loop over all files
				if ($handle = @opendir($temp_dir))
				{
					while ($file = @readdir($handle))
					{
						if (($file!='.') && ($file!='..'))
						{
							$srcm	= $temp_dir . '/' . $file;
							$dstm	= $location_path.$entry_id . '/' . $file;

							if (is_file($dstm) === FALSE) @copy($srcm,$dstm);
							@unlink($srcm);
						}
					}

					@closedir($handle);
					@rmdir($temp_dir);
				}
			}
		}

		// Grab all the files from the DB
		$this->EE->db->select('*');
		$this->EE->db->from('exp_channel_images');
		$this->EE->db->where('entry_id', $entry_id);
		$this->EE->db->where('site_id', $this->site_id);
		$query = $this->EE->db->get();


		if ($query->num_rows() > 0)
		{
			// Not fresh, lets see whats new.
			foreach ($data['images'] as $order => $file)
			{
				if ($this->EE->channel_images_helper->in_multi_array($file['filename'], $query->result_array()) === FALSE)
				{
					// New File
					$data = array(	'site_id'	=>	$this->site_id,
									'entry_id'	=>	$entry_id,
									'channel_id'=>	$channel_id,
									'image_order'	=>	$order,
									'filename'	=>	$file['filename'],
									'extension' =>	substr( strrchr($file['filename'], '.'), 1),
									'title'		=>	$file['title'],
									'description' => $file['desc'],
									'category' 	=>	(isset($file['category']) == true) ? $file['category'] : '',
									'cover'		=>	$file['cover'],
									'sizes'		=>	''
								);

					$this->EE->db->insert('exp_channel_images', $data);
				}
				else
				{
					// Old file
					$data = array(	'cover'		=>	$file['cover'],
									'channel_id'=>	$channel_id,
									'image_order'=>	$order,
									'title'		=>	$file['title'],
									'description' => $file['desc'],
									'category' 	=>	(isset($file['category']) == true) ? $file['category'] : '',
									'sizes'		=>	''
								);

					$this->EE->db->update('exp_channel_images', $data, array('image_id' =>$file['imageid']));
				}
			}
		}
		else
		{
			// No previous entries, fresh fresh
			foreach ($data['images'] as $order => $file)
			{
				// New File
				$data = array(	'site_id'	=>	$this->site_id,
								'entry_id'	=>	$entry_id,
								'channel_id'=>	$channel_id,
								'image_order'		=>	$order,
								'filename'	=>	$file['filename'],
								'extension' =>	substr( strrchr($file['filename'], '.'), 1),
								'title'		=>	$file['title'],
								'description' => $file['desc'],
								'category' 	=>	(isset($file['category']) == true) ? $file['category'] : '',
								'cover'		=>	$file['cover'],
								'sizes'		=>	''
							);

				$this->EE->db->insert('exp_channel_images', $data);
			}
		}


		return;
	}

	// ********************************************************************************* //

	function delete($ids)
	{
		$this->EE->load->library('channel_images_helper');
		$this->settings = $this->EE->channel_images_helper->grab_settings($this->site_id);

		foreach ($ids as $entry_id)
		{
			// Grab Channel ID
			$this->EE->db->select('channel_id');
			$this->EE->db->from('exp_channel_images');
			$this->EE->db->where('entry_id', $entry_id);
			$query = $this->EE->db->get();

			if ($query->num_rows() == 0) continue;

			$channel_id = $query->row('channel_id');

			$location_path = $this->settings['channels'][$channel_id]['location_path'];
			if (@is_dir($location_path.$entry_id) == true)
			{
				$this->EE->channel_images_helper->delete_files($location_path.$entry_id);
				@rmdir($location_path.$entry_id);
			}

			// Delete from db
			$this->EE->db->where('entry_id', $entry_id);
			$this->EE->db->delete('exp_channel_images');
		}
	}

}

/* End of file ft.channel_images.php */
/* Location: ./system/expressionengine/third_party/tagger/ft.channel_images.php */