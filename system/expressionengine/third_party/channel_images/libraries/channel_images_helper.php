<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Tagger
 *
 * Tagger Helper Library File
 *
 * @author DevDemon
 * @copyright Copyright (c) DevDemon
 **/
class Channel_images_helper
{

	function Channel_images_helper()
	{
		// Creat EE Instance
		$this->EE =& get_instance();

		$this->site_id = $this->EE->config->item('site_id');
	}

	// ********************************************************************************* //

	function define_theme_url()
	{
		$theme_url = $this->EE->config->item('theme_folder_url');

		// Are we working on SSL?
		if (isset($_SERVER['HTTP_REFERER']) == TRUE AND strpos($_SERVER['HTTP_REFERER'], 'https://') !== FALSE)
		{
			$theme_url = str_replace('http://', 'https://', $theme_url);
		}

		if (! defined('DEVDEMON_THEME_URL')) define('DEVDEMON_THEME_URL', $theme_url . 'devdemon_themes/');
	}

	// ********************************************************************************* //

	/**
	 * Grab File Module Settings
	 * @return array
	 */
	function grab_settings($site_id=FALSE)
	{

		$settings = array();

		if (isset($this->EE->session->cache['Channel_Images_Settings']) == TRUE)
		{
			$settings = $this->EE->session->cache['Channel_Images_Settings'];
		}
		else
		{
			$this->EE->db->select('settings');
			$this->EE->db->where('module_name', 'Channel_images');
			$query = $this->EE->db->get('exp_modules');
			if ($query->num_rows() > 0) $settings = unserialize($query->row('settings'));
		}

		$this->EE->session->cache['Channel_Images_Settings'] = $settings;

		if ($site_id)
		{
			$settings = isset($settings['site_id:'.$site_id]) ? $settings['site_id:'.$site_id] : array();
		}

		return $settings;
	}

	// ********************************************************************************* //

	function get_router_url($type='url')
	{
		// Do we have a cached version of our ACT_ID?
		if (isset($this->EE->session->cache['Channel_Images']['Router_Url']['ACT_ID']) == FALSE)
		{
			$this->EE->db->select('action_id');
			$this->EE->db->where('class', 'Channel_Images');
			$this->EE->db->where('method', 'channel_images_router');
			$query = $this->EE->db->get('actions');
			$ACT_ID = $query->row('action_id');
		}
		else $ACT_ID = $this->EE->session->cache['Channel_Images']['Router_Url']['ACT_ID'];

		// RETURN: Full Action URL
		if ($type == 'url')
		{
			if (isset($this->EE->session->cache['Channel_Images']['Router_Url']['URL']) == TRUE) return $this->EE->session->cache['Channel_Images']['Router_Url']['URL'];
			$this->EE->session->cache['Channel_Images']['Router_Url']['URL'] = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT=' . $ACT_ID;
			return $this->EE->session->cache['Channel_Images']['Router_Url']['URL'];
		}

		// RETURN: ACT_ID Only
		if ($type == 'act_id') return $ACT_ID;
	}

	// ********************************************************************************* //

	/**
	 * Generate new XID
	 *
	 * @return string the_xid
	 */
	function xid_generator()
	{
		// Maybe it's already been made by EE
		if (defined('XID_SECURE_HASH') == TRUE) return XID_SECURE_HASH;

		// First is secure_forum enabled?
		if ($this->EE->config->item('secure_forms') == 'y')
		{
			// Did we already cache it?
			if (isset($this->EE->session->cache['XID']) == TRUE && $this->EE->session->cache['XID'] != FALSE)
			{
				return $this->EE->session->cache['XID'];
			}

			// Is there one already made that i can use?
			$this->EE->db->select('hash');
			$this->EE->db->from('exp_security_hashes');
			$this->EE->db->where('ip_address', $this->EE->input->ip_address());
			$this->EE->db->where('`date` > UNIX_TIMESTAMP()-3600');
			$this->EE->db->limit(1);
			$query = $this->EE->db->get();

			if ($query->num_rows() > 0)
			{
				$row = $query->row();
				$this->EE->session->cache['XID'] = $row->hash;
				return $this->EE->session->cache['XID'];
			}

			// Lets make one then!
			$XID	= $this->EE->functions->random('encrypt');
			$this->EE->db->insert('exp_security_hashes', array('date' => $this->EE->localize->now, 'ip_address' => $this->EE->input->ip_address(), 'hash' => $XID));

			// Remove Old
			//$DB->query("DELETE FROM exp_security_hashes WHERE date < UNIX_TIMESTAMP()-7200"); // helps garbage collection for old hashes

			$this->EE->session->cache['XID'] = $XID;
			return $XID;
		}
	}
	// ********************************************************************************* //

	function image_sizes_js($site_id, $channel_id, $settings)
	{
		$new = array();

		foreach ($settings['sizes'] as $name => $size)
		{
			$size['n'] = $name;
			$new[] = $size;
		}

		$js = " ChannelImages.ImageSizes = JSON.parse('" . $this->EE->javascript->generate_json($new) . "');";

		return $js;
	}

	// ********************************************************************************* //

	function generate_json($obj)
	{
		if (function_exists('json_encode') == false)
		{
			if (is_null($obj)) return 'null';
			if ($obj === false) return 'false';
			if ($obj === true) return 'true';

			if (is_scalar($obj))
			{
				if (is_float($obj))
				{
					// Always use "." for floats.
					return floatval(str_replace(",", ".", strval($obj)));
				}

				if (is_string($obj))
				{
					static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
					return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $obj) . '"';
				}
				else
				{
					return $obj;
				}
			}

			$isList = true;
			for ($i = 0, reset($obj); $i < count($obj); $i++, next($obj))
			{
				if (key($obj) !== $i)
				{
					$isList = false;
					break;
				}
			}

			$result = array();

			if ($isList)
			{
				foreach ($obj as $v)
				{
					$result[] = $this->generate_json($v);
				}

				return '[' . join(',', $result) . ']';
			}
			else
			{
				foreach ($obj as $k => $v)
				{
					$result[] = $this->generate_json($k).':'.$this->generate_json($v);
				}

				return '{' . join(',', $result) . '}';
			}
		}
		else
		{
			return json_encode($obj);
		}

		return;
	}

	// ********************************************************************************* //

	function greyscale_img($path)
	{
		// replace with your files
	    $originalFileName    = $path;
	    $destinationFileName = $path;

	    // create a copy of the original image
	    // works with jpg images
	    // fell free to adapt to other formats ;)
	    $fullPath = explode(".",$originalFileName);
	    $lastIndex = sizeof($fullPath) - 1;
	    $extension = $fullPath[$lastIndex];
	    if (preg_match("/jpg|jpeg|JPG|JPEG/", $extension))
	    {
	    	$filetype = 'jpg';
	        $sourceImage = imagecreatefromjpeg($originalFileName);
	    }
		else if (preg_match("/png|PNG/", $extension))
	    {
	    	$filetype = 'png';
	        $sourceImage = imagecreatefrompng($originalFileName);
	    }

	    // get image dimensions
	    $img_width  = imageSX($sourceImage);
	    $img_height = imageSY($sourceImage);

	    if (function_exists('imagefilter') == false)
	    {
		    // convert to grayscale
        	$palette = array();
   			for ($c=0;$c<256;$c++)
			{
			$palette[$c] = imagecolorallocate($sourceImage,$c,$c,$c);
			}

			for ($y=0;$y<$img_height;$y++)
			{
				for ($x=0;$x<$img_width;$x++)
				{
					$rgb = imagecolorat($sourceImage,$x,$y);
					$r = ($rgb >> 16) & 0xFF;
					$g = ($rgb >> 8) & 0xFF;
					$b = $rgb & 0xFF;
					$gs = (($r*0.299)+($g*0.587)+($b*0.114));
					imagesetpixel($sourceImage,$x,$y,$palette[$gs]);
				}
			}

	    }
	    else
	    {
	    	imagefilter($sourceImage, IMG_FILTER_GRAYSCALE);
	    }

	    // copy pixel values to new file buffer
	    $destinationImage = ImageCreateTrueColor($img_width, $img_height);
	    imagecopy($destinationImage, $sourceImage, 0, 0, 0, 0, $img_width, $img_height);

	    // create file on disk
	    if ($filetype == 'jpg') imagejpeg($destinationImage, $destinationFileName);
	    else if ($filetype == 'png') imagepng($destinationImage, $destinationFileName);

	    // destroy temp image buffers
	    imagedestroy($destinationImage);
	    imagedestroy($sourceImage);
	}

	// ********************************************************************************* //

/**
	 * Delete Files
	 *
	 * Deletes all files contained in the supplied directory path.
	 * Files must be writable or owned by the system in order to be deleted.
	 * If the second parameter is set to TRUE, any directories contained
	 * within the supplied base directory will be nuked as well.
	 *
	 * @access	public
	 * @param	string	path to file
	 * @param	bool	whether to delete any directories found in the path
	 * @return	bool
	 */
	function delete_files($path, $del_dir = FALSE, $level = 0)
	{
		// Trim the trailing slash
		$path = preg_replace("|^(.+?)/*$|", "\\1", $path);

		if ( ! $current_dir = @opendir($path))
			return;

		while(FALSE !== ($filename = @readdir($current_dir)))
		{
			if ($filename != "." and $filename != "..")
			{
				if (is_dir($path.'/'.$filename))
				{
					// Ignore empty folders
					if (substr($filename, 0, 1) != '.')
					{
						delete_files($path.'/'.$filename, $del_dir, $level + 1);
					}
				}
				else
				{
					unlink($path.'/'.$filename);
				}
			}
		}
		@closedir($current_dir);

		if ($del_dir == TRUE AND $level > 0)
		{
			@rmdir($path);
		}
	}

	// ********************************************************************************* //

	/**
	 * Is a Natural number  (0,1,2,3, etc.)
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function is_natural_number($str)
	{
   		return (bool)preg_match( '/^[0-9]+$/', $str);
	}

	// ********************************************************************************* //

	/**
     * Function for looking for a value in a multi-dimensional array
     *
     * @param string $value
     * @param array $array
     * @return bool
     */
	function in_multi_array($value, $array)
	{
		foreach ($array as $key => $item)
		{
			// Item is not an array
			if (!is_array($item))
			{
				// Is this item our value?
				if ($item == $value) return TRUE;
			}

			// Item is an array
			else
			{
				// See if the array name matches our value
				//if ($key == $value) return true;

				// See if this array matches our value
				if (in_array($value, $item)) return TRUE;

				// Search this array
				else if ($this->in_multi_array($value, $item)) return TRUE;
			}
		}

		// Couldn't find the value in array
		return FALSE;
	}

	// ********************************************************************************* //

	/**
	 * Get Entry_ID from tag paramaters
	 *
	 * Supports: entry_id="", url_title="", channel=""
	 *
	 * @return mixed - INT or BOOL
	 */
	function get_entry_id_from_param($get_channel_id=FALSE)
	{
		$entry_id = FALSE;
		$channel_id = FALSE;

		$this->EE->load->helper('number');

		if ($this->EE->TMPL->fetch_param('entry_id') != FALSE && $this->is_natural_number($this->EE->TMPL->fetch_param('entry_id')) != FALSE)
		{
			$entry_id = $this->EE->TMPL->fetch_param('entry_id');
		}
		elseif ($this->EE->TMPL->fetch_param('url_title') != FALSE)
		{
			$channel = FALSE;
			$channel_id = FALSE;

			if ($this->EE->TMPL->fetch_param('channel') != FALSE)
			{
				$channel = $this->EE->TMPL->fetch_param('channel');
			}

			if ($this->EE->TMPL->fetch_param('channel_id') != FALSE && $this->is_natural_number($this->EE->TMPL->fetch_param('channel_id')))
			{
				$channel_id = $this->EE->TMPL->fetch_param('channel_id');
			}

			$this->EE->db->select('exp_channel_titles.entry_id');
			$this->EE->db->select('exp_channel_titles.channel_id');
			$this->EE->db->from('exp_channel_titles');
			if ($channel) $this->EE->db->join('exp_channels', 'exp_channel_titles.channel_id = exp_channels.channel_id', 'left');
			$this->EE->db->where('exp_channel_titles.url_title', $this->EE->TMPL->fetch_param('url_title'));
			if ($channel) $this->EE->db->where('exp_channels.blog_name', $channel);
			if ($channel_id) $this->EE->db->where('exp_channel_titles.channel_id', $channel_id);
			$this->EE->db->limit(1);
			$query = $this->EE->db->get();

			if ($query->num_rows() > 0)
			{
				$channel_id = $query->row('channel_id');
				$entry_id = $query->row('entry_id');
				$query->free_result();
			}
			else
			{
				return FALSE;
			}
		}

		if ($get_channel_id != FALSE)
		{
			if ($this->EE->TMPL->fetch_param('channel') != FALSE)
			{
				$channel_id = $this->EE->TMPL->fetch_param('channel_id');
			}

			if ($channel_id == FALSE)
			{
				$this->EE->db->select('channel_id');
				$this->EE->db->where('entry_id', $entry_id);
				$this->EE->db->limit(1);
				$query = $this->EE->db->get('exp_channel_titles');
				$channel_id = $query->row('channel_id');

				$query->free_result();
			}

			$entry_id = array( 'entry_id'=>$entry_id, 'channel_id'=>$channel_id );
		}



		return $entry_id;
	}

	// ********************************************************************************* //

	/**
	 * Fetch data between var pairs (including optional parameters)
	 *
	 * @param string $open - Open var (with optional parameters)
	 * @param string $close - Closing var
	 * @param string $source - Source
	 * @return string
	 */
    function fetch_data_between_var_pairs_params($open='', $close='', $source = '')
    {
    	if ( ! preg_match('/'.LD.preg_quote($open).'.*?'.RD.'(.*?)'.LD.'\/'.$close.RD.'/s', $source, $match))
               return;

        return $match['1'];
    }

	// ********************************************************************************* //

	/**
	 * Replace var_pair with final value (including optional parameters)
	 *
	 * @param string $open - Open var (with optional parameters)
	 * @param string $close - Closing var
	 * @param string $replacement - Replacement
	 * @param string $source - Source
	 * @return string
	 */
	function swap_var_pairs_params($open = '', $close = '', $replacement = '\\1', $source = '')
    {
    	return preg_replace("/".LD.preg_quote($open).RD."(.*?)".LD.'\/'.$close.RD."/s", $replacement, $source);
    }

	// ********************************************************************************* //

	/**
	 * Custom No_Result conditional
	 *
	 * Same as {if no_result} but with your own conditional.
	 *
	 * @param string $cond_name
	 * @param string $source
	 * @param string $return_source
	 * @return unknown
	 */
    function custom_no_results_conditional($cond_name, $source, $return_source=FALSE)
    {
   		if (strpos($source, LD."if {$cond_name}".RD) !== FALSE)
		{
			if (preg_match('/'.LD."if {$cond_name}".RD.'(.*?)'. LD.'\/if'.RD.'/s', $source, $cond))
			{
				return $cond[1];
			}

		}


		if ($return_source !== FALSE)
		{
			return $source;
		}

		return;
    }

	// ********************************************************************************* //

	function mcp_meta_parser($type='js', $url, $name, $package='')
	{
		// -----------------------------------------
		// CSS
		// -----------------------------------------
		if ($type == 'css')
		{
			if ( isset($this->EE->session->cache['DevDemon']['CSS'][$name]) == FALSE )
			{
				$this->EE->cp->add_to_head('<link rel="stylesheet" href="' . $url . '" type="text/css" media="print, projection, screen" />');
				$this->EE->session->cache['DevDemon']['CSS'][$name] = TRUE;
			}
		}

		// -----------------------------------------
		// Javascript
		// -----------------------------------------
		if ($type == 'js')
		{
			if ( isset($this->EE->session->cache['DevDemon']['JS'][$name]) == FALSE )
			{
				$this->EE->cp->add_to_head('<script src="' . $url . '" type="text/javascript"></script>');
				$this->EE->session->cache['DevDemon']['JS'][$name] = TRUE;
			}
		}

		// -----------------------------------------
		// Global Inline Javascript
		// -----------------------------------------
		if ($type == 'gjs')
		{
			if ( isset($this->EE->session->cache['DevDemon']['GJS'][$name]) == FALSE )
			{
				$xid = $this->xid_generator();
				$AJAX_url = $this->get_router_url();

				$js = "	var ChannelImages = ChannelImages ? ChannelImages : new Object();
						ChannelImages.XID = '{$xid}';
						ChannelImages.AJAX_URL = '{$AJAX_url}&channelimages_ajax=yes';
						ChannelImages.ThemeURL = '" . DEVDEMON_THEME_URL . "';
					";

				$this->EE->cp->add_to_head('<script type="text/javascript">' . $js . '</script>');
				$this->EE->session->cache['DevDemon']['GJS'][$name] = TRUE;
			}
		}
	}

} // END CLASS

/* End of file channel_images_helper.php  */
/* Location: ./system/expressionengine/third_party/tagger/modules/libraries/channel_images_helper.php */