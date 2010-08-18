<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Channel Images
 *
 * Module Control Panel
 *
 * @author DevDemon
 * @copyright Copyright (c) DevDemon
 **/

class Channel_images_mcp
{
	/*
	 * Constructor
	 */
	function Channel_images_mcp()
	{
		// Creat EE Instance
		$this->EE =& get_instance();

		// Load Models & Libraries & Helpers
		$this->EE->load->library('channel_images_helper');
		//$this->EE->load->model('tagger_model', 'tagger');

		// Some Globals
		$this->base = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=channel_images';
		$this->vData = array('base_url'	=> $this->base); // Global Views Data Array

		$this->EE->channel_images_helper->define_theme_url();

		$this->mcp_globals();

		// Add Right Top Menu
		$this->EE->cp->set_right_nav(array(
			'ci:settings' 			=> $this->base,
			'ci:docs' 				=> $this->EE->cp->masked_url('http://www.devdemon.com/docs/'),
		));

		$this->site_id = $this->EE->config->item('site_id');

		// Debug
		//$this->EE->db->save_queries = TRUE;
		//$this->EE->output->enable_profiler(TRUE);
	}

	// ********************************************************************************* //

	function index()
	{
		// Page Title & BreadCumbs
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('ci:settings'));


		$this->EE->load->helper('path');

		// Channels
		$this->vData['channels'] = array();
		$this->EE->db->select('channel_id, channel_title');
		$this->EE->db->where('site_id', $this->site_id);
		$query = $this->EE->db->get('exp_channels');
		foreach ($query->result() as $row) $this->vData['channels'][$row->channel_id] = $row->channel_title;

		// Settings
		$this->EE->db->select('settings');
		$this->EE->db->where('module_name', 'Channel_images');
		$query = $this->EE->db->get('exp_modules');
		$this->vData['settings'] = unserialize( $query->row('settings') );
		$this->vData['settings'] = (isset($this->vData['settings']['site_id:'.$this->site_id]) == TRUE) ? $this->vData['settings']['site_id:'.$this->site_id] : array( 'channels' => array() );

		// Any Assigned Weblogs?
		$this->vData['aChannels'] = '';
		foreach ($this->vData['settings']['channels'] as $cId => $sChannel)
		{
			// Check for deleted weblogs
			if (isset($this->vData['channels'][$cId]) == FALSE) continue;

			$sChannel['channel_name'] =  $this->vData['channels'][$cId];
			$sChannel['cId'] = $cId;

			// Images Sizes
			$sChannel['image_sizes'] = '';
			foreach ($sChannel['sizes'] as $name => $sizes)
			{
				$sData = array( 'name' => $name, 'sizes' => $sizes );
				$sChannel['image_sizes'] .= $this->EE->load->view('mcp_images_sizes_single', array_merge($sChannel, $sData), TRUE);
			}

			$this->vData['aChannels'] .= $this->EE->load->view('mcp_weblog_section', $sChannel, TRUE);
		}

		// Default Location URL/PATH
		$this->vData['default_locpath'] =  set_realpath('../');
		$this->vData['default_locurl'] = $this->EE->config->item('site_url');

		// DEFAULT Images Sizes
		$this->vData['image_sizes'] = '';
		$DefImgSizes = array(	$this->EE->lang->line('ci:small') => array('w'=>100, 'h'=>100, 'q'=>80),	$this->EE->lang->line('ci:medium') => array('w'=>450, 'h'=>300, 'q'=>75),	$this->EE->lang->line('ci:large') => array('w'=>800, 'h'=>600, 'q'=>75),);
		foreach ($DefImgSizes as $name => $sizes)
		{
			$sData = array( 'name' => $name, 'sizes' => $sizes, 'cId' => '');
			$this->vData['image_sizes'] .= $this->EE->load->view('mcp_images_sizes_single', $sData, TRUE);
		}

		// Blank Weblog
		$this->vData['BlankWeblog'] = $this->EE->load->view('mcp_weblog_section', $this->vData, TRUE);


		return $this->EE->load->view('mcp_index', $this->vData, TRUE);
	}

	// ********************************************************************************* //

	function mcp_globals()
	{
		$this->EE->cp->set_breadcrumb($this->base, $this->EE->lang->line('channel_images'));

		// Add Global JS & CSS & JS Scripts
		$this->EE->channel_images_helper->mcp_meta_parser('gjs', '', 'ChannelImages');
		$this->EE->channel_images_helper->mcp_meta_parser('css', DEVDEMON_THEME_URL . 'images/global.css', 'devdemon-global');
		$this->EE->channel_images_helper->mcp_meta_parser('css', DEVDEMON_THEME_URL . 'channel_images_module/ci_mcp.css', 'ci-pbf');
		$this->EE->channel_images_helper->mcp_meta_parser('js', DEVDEMON_THEME_URL . 'js/jquery.execute.js', 'jquery.autocomplete', 'jquery');
		$this->EE->channel_images_helper->mcp_meta_parser('js', DEVDEMON_THEME_URL . 'js/jquery.form.js', 'jquery.form', 'jquery');
		$this->EE->channel_images_helper->mcp_meta_parser('js', DEVDEMON_THEME_URL . 'channel_images_module/ci_mcp.js', 'ci-pbf');
	}


} // END CLASS

/* End of file mcp.channel_images.php */
/* Location: ./system/expressionengine/third_party/tagger/mcp.channel_images.php */