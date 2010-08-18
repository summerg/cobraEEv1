<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Channel Images
 *
 * Module Tag File
 *
 * @author DevDemon
 * @copyright Copyright (c) DevDemon
 **/
class Channel_images
{

	function Channel_images()
	{
		$this->EE =& get_instance();
		$this->site_id = $this->EE->config->item('site_id');
		$this->EE->load->library('channel_images_helper');

		$this->settings = $this->EE->channel_images_helper->grab_settings($this->site_id);
	}

	// ********************************************************************************* //

	function images()
	{
		// Variable prefix
		$this->prefix = $this->EE->TMPL->fetch_param('prefix', 'image') . ':';

		// Entry ID
		$this->entry_id = $this->EE->channel_images_helper->get_entry_id_from_param();

		// We need an entry_id
		if ($this->entry_id == FALSE)
		{
			$this->EE->TMPL->log_item('CHANNEL IMAGES: Entry ID could not be resolved');
			return $this->EE->channel_images_helper->custom_no_results_conditional($this->prefix.'no_images', $this->EE->TMPL->tagdata);
		}

		// Temp vars
		$final = '';

		// Group By Category?
		if ($this->EE->TMPL->fetch_param('group_by_category') != false) return $this->_group_by_category();

		// Limit Results?
		$limit = ($this->EE->channel_images_helper->is_natural_number($this->EE->TMPL->fetch_param('limit')) != FALSE) ? $this->EE->TMPL->fetch_param('limit') : 30;
		$this->EE->db->limit($limit);

		// Order by?
		if ($this->EE->TMPL->fetch_param('orderby') == 'title') $this->EE->db->order_by('title');
		else $this->EE->db->order_by('image_order');

		// Do we have an category?
		if ($this->EE->TMPL->fetch_param('category') != FALSE) $this->EE->db->where('category', $this->EE->TMPL->fetch_param('category'));

		// Do we need to offset?
		if ($this->EE->TMPL->fetch_param('offset') != FALSE && $this->EE->channel_images_helper->is_natural_number($this->EE->TMPL->fetch_param('offset')) != FALSE)
		{
			$this->EE->db->limit($limit, $this->EE->TMPL->fetch_param('offset'));
		}

		// Do we need to skip the cover image?
        if ($this->EE->TMPL->fetch_param('skip_cover') != FALSE)
        {
        	$this->EE->db->where('cover', 0);
        }

		// Cover Image?
		if ($this->EE->TMPL->fetch_param('cover_only') != FALSE)
		{
			$this->EE->db->limit(1);
			$this->EE->db->where('cover', 1);
		}

		// Image ID?
		if ($this->EE->TMPL->fetch_param('image_id') != FALSE)
		{
			$this->EE->db->limit(1);
			$this->EE->db->where('image_id', $this->EE->TMPL->fetch_param('image_id'));
		}

		// Shoot the query
		$this->EE->db->select('*');
		$this->EE->db->from('exp_channel_images');
		$this->EE->db->where('entry_id', $this->entry_id);
		$query = $this->EE->db->get();

		if ($query->num_rows() == 0)
		{
			$this->EE->TMPL->log_item("CHANNEL IMAGES: No images found. (Entry_ID:{$this->entry_id})");
			return $this->EE->channel_images_helper->custom_no_results_conditional($this->prefix.'no_images', $this->EE->TMPL->tagdata);
		}
		$images = $query->result();
		$query->free_result();

		$final = $this->_file_parse($images, $this->EE->TMPL->tagdata);

		return $final;
	}


	// ********************************************************************************* //

	function _group_by_category()
	{

		// Shoot the query
		$this->EE->db->select('*');
		$this->EE->db->from('exp_channel_images');
		$this->EE->db->where('entry_id', $this->entry_id);
		$query = $this->EE->db->get();

		if ($query->num_rows() == 0)
		{
			return $this->EE->channel_images_helper->custom_no_results_conditional($this->prefix.'no_images', $this->EE->TMPL->tagdata);
		}

		$images = $query->result();
		$query->free_result();


		// Grab the {images} var pair
		if (isset($this->EE->TMPL->var_pair['images']) == FALSE)
		{
			$this->EE->TMPL->log_item("CHANNEL IMAGES: No {images} var pair found.");
			return $this->EE->channel_images_helper->custom_no_results_conditional($this->prefix.'no_images', $this->EE->TMPL->tagdata);
		}

		$pair_data = $this->EE->channel_images_helper->fetch_data_between_var_pairs_params('images', 'images', $this->EE->TMPL->tagdata);

		// Loop over all files and make a new array
		$categories = array();
		foreach($images as $image)
		{
			if (trim($image->category) == FALSE) continue;
			$categories[ $image->category ][] = $image;
		}
		unset($images);

		// Empty category?
		if (empty($categories) == TRUE)
		{
			$this->EE->TMPL->log_item("CHANNEL IMAGES: Found images but no categories.");
			return $this->EE->channel_images_helper->custom_no_results_conditional($this->prefix.'no_images', $this->EE->TMPL->tagdata);
		}

		// Sort by category
		if (strtolower($this->EE->TMPL->fetch_param('category_sort')) != 'desc')
			ksort($categories);
		else krsort($categories);


		// Loop over the new array and parse
		$final = '';
		foreach ($categories as $cat => $images)
		{
			$temp = str_replace(LD.$this->prefix.'category'.RD, $cat, $this->EE->TMPL->tagdata);
			$tempf = $this->_file_parse($images, $pair_data);
			$temp = $this->EE->channel_images_helper->swap_var_pairs_params('images', 'images', $tempf, $temp);

			$final .= $temp;
		}

		return $final;
	}

	// ********************************************************************************* //

	function _file_parse($images, $tagdata, $allimage=false)
	{
		$final = '';
		$total = count($images);

		foreach ($images as $count => $image)
		{
			$filedir = $this->settings['channels'][$image->channel_id]['location_url'] . $image->entry_id  . '/' ;
			$temp = str_replace(	array(	LD.$this->prefix.'title'.RD,
											LD.$this->prefix.'description'.RD,
											LD.$this->prefix.'filename'.RD,
											LD.$this->prefix.'id'.RD,
											LD.$this->prefix.'url'.RD,
											LD.$this->prefix.'category'.RD,
											LD.$this->prefix.'count'.RD,
											LD.$this->prefix.'total'.RD ),
									array(	$image->title,
											$image->description,
											$image->filename,
											$image->image_id,
											$filedir . $image->filename,
											$image->category,
											$count+1,
											$total),
									$tagdata);

			$temp = $this->_parse_size_vars($image, $temp, $filedir);

			$final .= $temp;
		}

		return $final;
	}

	// ********************************************************************************* //

	function _parse_size_vars($image, $temp, $filedir)
	{
		// get the extensions
		$extension = '.' . substr( strrchr($image->filename, '.'), 1);

		// Generate size names and delete
		foreach ($this->settings['channels'][$image->channel_id]['sizes'] as $name => $values)
		{
			$name = strtolower($name);
			$newname = str_replace($extension, "__{$name}.jpg", $image->filename);
			$temp = str_replace(LD.$this->prefix.'url:'.$name.RD, $filedir.$newname, $temp);
		}


		return $temp;
	}

	// ********************************************************************************* //

	function channel_images_router()
	{

		// -----------------------------------------
		// Ajax Request?
		// -----------------------------------------
		if ($this->EE->input->get('channelimages_ajax') != FALSE)
		{
			// Load Library
			$this->EE->load->library('channel_images_ajax');

			// Shoot the requested method
			$method = $this->EE->input->get_post('ajax_method');
			echo $this->EE->channel_images_ajax->$method();
			exit();
		}
	}

} // END CLASS

/* End of file mod.channel_images.php */
/* Location: ./system/expressionengine/third_party/channel_images/mod.channel_images.php */