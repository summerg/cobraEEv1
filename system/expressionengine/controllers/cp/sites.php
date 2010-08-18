<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Sites CP Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Sites extends Controller {

	var $version 			= '2.0';
	var $build_number		= '20100805';
	var $allow_new_sites 	= FALSE;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Sites()
	{
		parent::Controller();
		
		$this->lang->loadfile('sites');
		
		/** --------------------------------
		/**  Is the MSM enabled?
		/** --------------------------------*/
		
		if ($this->config->item('multiple_sites_enabled') !== 'y')
        {
			show_error($this->lang->line('unauthorized_access'));
        }

		/** --------------------------------
		/**  Are they trying to switch?
		/** --------------------------------*/
		
		$site_id = $this->input->get_post('site_id');
		
		if ($this->router->fetch_method() == 'index' && $site_id && is_numeric($site_id))
		{
			$this->_switch_site($site_id);
			return;
		}
		
		if ($this->router->fetch_method() != 'index')
		{
			$this->load->library('sites');
			$this->lang->loadfile('sites_cp');
			$this->lang->loadfile('admin_content');
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */	
	function index($message = '')
	{
		if ( count($this->session->userdata('assigned_sites')) == 0 )
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->load->library('sites');
		$this->load->library('table');
		$this->lang->loadfile('sites_cp');
		
		$this->javascript->compile();
		
		$vars['sites'] = $this->session->userdata('assigned_sites');

		$this->cp->set_variable('cp_page_title', $this->lang->line('switch_site'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=sites', $this->lang->line('site_management'));
				
		$vars['can_admin_sites'] = $this->cp->allowed_group('can_admin_sites');

		$vars['message'] = $message;

		$this->javascript->compile();

		$this->cp->set_right_nav(array('edit_sites' => BASE.AMP.'C=sites'.AMP.'M=manage_sites'));
		
		$this->load->view('sites/switch', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Site Switching Logic
	 *
	 * @access	private
	 * @return	mixed
	 */	
	function _switch_site($site_id)
	{
		if ($this->session->userdata['group_id'] != 1)
		{
			$this->db->select('can_access_cp');
			$this->db->where('site_id', $site_id);
			$this->db->where('group_id', $this->session->userdata['group_id']);
			
			$query = $this->db->get('member_groups');

			if ($query->num_rows() == 0 OR $query->row('can_access_cp') !== 'y')
			{
				return $this->index('unauthorized_access');
			}
		}
		
		if ($this->input->get_post('page') !== FALSE && preg_match('/^[a-z0-9\_]+$/iD', $this->input->get_post('page')))
		{
			$parts = explode('|', base64_decode(str_replace('_', '=', $this->input->get_post('page'))));
			
			$page = BASE;
			
			foreach($parts as $part)
			{
				if ($part == '' OR ! preg_match('/^[a-z0-9\_\=]+$/iD', $part))
				{
					continue;
				}
				
				$page .= AMP.$part;
			}
		}
		else
		{
			$page = BASE;
		}
		
		// This is just way too simple.
		
		$this->config->site_prefs('', $site_id);
		$this->functions->set_cookie('cp_last_site_id', $site_id, 0);
		
		$this->functions->redirect($page);
	}

	// --------------------------------------------------------------------

	// ===========================
	// = Administative Functions =
	// ===========================
	
	// --------------------------------------------------------------------

	/**
	 * Site Overview
	 *
	 * Displays the Site Management page
	 *
	 * @access	public
	 * @return	void
	 */
	function manage_sites($message = '')
	{
		if ( ! $this->cp->allowed_group('can_admin_sites'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->library('table');
		$this->load->model('site_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('site_management'));

		$vars['msm_version'] = $this->version;
		$vars['msm_build_number'] = $this->build_number;

		$this->jquery->tablesorter('.mainTable', '{
			widgets: ["zebra"]
		}');

		$this->javascript->compile();

		if ($created_id = $this->input->get('created_id'))
		{
			$this->db->select('site_label');
			$this->db->where('site_id', $created_id);
			
			$query = $this->db->get('sites');
			$message = $this->lang->line('site_created').': &nbsp;'.$query->row('site_label');
		}
		elseif ($updated_id = $this->input->get('updated_id'))
		{
			$this->db->select('site_label');
			$this->db->where('site_id', $updated_id);
			
			$query = $this->db->get('sites');
			$message = $this->lang->line('site_updated').': &nbsp;'.$query->row('site_label');
		}
		
		$vars['site_data'] = $this->site_model->get_site();
		$vars['message'] = $message;
		
		$this->javascript->compile();
		
		$this->cp->set_right_nav(array('create_new_site' => BASE.AMP.'C=sites'.AMP.'M=add_edit_site'));
		
		$this->load->view('sites/list_sites', $vars);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Create / Update Site
	 *
	 * Create or Update Form
	 *
	 * @access	public
	 * @return	void
	 */
	function add_edit_site()
	{
		if ( ! $this->cp->allowed_group('can_admin_sites'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$site_id = $this->input->get('site_id');
		
		$title = ($site_id) ? $this->lang->line('edit_site') : $this->lang->line('create_new_site');
		$this->cp->set_variable('cp_page_title', $title);
		
		$this->load->model('site_model');
		$this->load->helper(array('form', 'snippets'));
		
		$values = array('site_id'					=> '',
						'site_label'				=> '',
						'site_name'					=> '',
						'site_description'			=> '');

		if ($site_id)
		{
			$query = $this->site_model->get_site($site_id);
			
			if ($query->num_rows() == 0)
			{
				return FALSE;
			}
			
			$values = array_merge($values, $query->row_array());
		}
		
		if ($values['site_id'] == '')
		{
				$this->lang->loadfile('content');
				$this->lang->loadfile('design');
				
				$vars['channels'] = $this->db->query("SELECT channel_title, channel_id, site_label FROM exp_channels, exp_sites
									 				WHERE exp_sites.site_id = exp_channels.site_id
									 				ORDER by site_label, channel_title");
									
				$vars['channel_options'] = array(
												'nothing'		=> $this->lang->line('do_nothing'),
												'move'			=> $this->lang->line('move_channel_move_data'),
												'duplicate'		=> $this->lang->line('duplicate_channel_no_data'),
												'duplicate_all'	=> $this->lang->line('duplicate_channel_all_data')
												);


				$vars['upload_directories'] = 	$this->db->query("SELECT name, id, site_label FROM exp_upload_prefs, exp_sites
										 						WHERE exp_sites.site_id = exp_upload_prefs.site_id
										 						ORDER by site_label, exp_upload_prefs.name");

				$vars['upload_directory_options'] = array(
												'nothing'		=> $this->lang->line('do_nothing'),
												'move'			=> $this->lang->line('move_upload_destination'),
												'duplicate'		=> $this->lang->line('duplicate_upload_destination')
												);


				$vars['template_groups'] = $this->db->query("SELECT group_name, group_id, site_label FROM exp_template_groups, exp_sites
									 						WHERE exp_sites.site_id = exp_template_groups.site_id
									 						ORDER by site_label, group_name");

				$vars['template_group_options'] = array(
												'nothing'		=> $this->lang->line('do_nothing'),
												'move'			=> $this->lang->line('move_template_group'),
												'duplicate'		=> $this->lang->line('duplicate_template_group')
												);

				$vars['global_variables'] = $this->db->query("SELECT site_id, site_label FROM exp_sites ORDER by site_label");
				
				
				$vars['global_variable_options'] = array(
												'nothing'		=> $this->lang->line('do_nothing'),
												'move'			=> $this->lang->line('move_global_variables'),
												'duplicate'		=> $this->lang->line('duplicate_global_variables')
												);
		}
		
		$vars['values'] = $values;
		$vars['form_hidden'] = $site_id ? array('site_id' => $site_id) : NULL;
		$vars['form_url'] = 'C=sites'.AMP.'M=update_site';
		
		if ($site_id)
		{
				$vars['form_url'] .= AMP.'site_id='.$site_id;
		}

		$this->javascript->compile();
		$this->load->view('sites/edit_form', $vars);		
	}
	

	function _add_edit_validation()
	{
		$edit = ($this->input->post('site_id') && is_numeric($_POST['site_id'])) ? TRUE : FALSE;
		// Check for required fields

		
		$this->load->library('form_validation');
		
		$config = array(
					   array(
							 'field'   => 'site_name',
							 'label'   => 'lang:site_name',
							 'rules'   => 'required|callback__valid_shortname|callback__duplicate_shortname'
							),					
					   array(
							 'field'   => 'site_label',
							 'label'   => 'lang:site_label',
							 'rules'   => 'required'
							)
					);
		
		$this->form_validation->set_error_delimiters('<span class="notice">', '</span>');
		$this->form_validation->set_rules($config);
		
		if ($edit == FALSE)
		{
			$this->form_validation->set_rules('general_error', 'lang:general_error', 'callback__general_error');	
		}		
		
	}

	function _valid_shortname($str)
	{
		if (preg_match('/[^a-z0-9\-\_]/i', $str))
		{
			$this->form_validation->set_message('_valid_shortname', $this->lang->line('invalid_short_name'));
			return FALSE;
		}
		
		return TRUE;
	}
	
	function _duplicate_shortname($str)
	{
		// Short Name Taken Already?
		
		$sql = "SELECT COUNT(*) AS count FROM exp_sites WHERE site_name = '".$this->db->escape_str($_POST['site_name'])."'";
		
		if ($this->input->get('site_id') !== FALSE)
		{
			$sql .= " AND site_id != '".$this->db->escape_str($this->input->get('site_id'))."'";
		} 
		
		$query = $this->db->query($sql);		
		
		if ($query->row('count')  > 0)
		{
			$this->form_validation->set_message('_duplicate_shortname', $this->lang->line('site_name_taken'));
			return FALSE;			
		}
		
		return TRUE;
	}
	
	function _general_error($str)
	{
		if ( ! file_exists(APPPATH.'language/'.$this->config->item('deft_lang').'/email_data'.EXT))
		{
			$this->form_validation->set_message('_general_error', $this->lang->line('unable_to_locate_specialty'));
			return FALSE;
		}
			
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Update or create a site
	 *
	 * Inserts or updates the site settings
	 *
	 * @access	public
	 * @return	void
	 */
	function update_site()
	{
		if ( ! $this->cp->allowed_group('can_admin_sites'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->_add_edit_validation();
		
		if ($this->form_validation->run() == FALSE)
		{		
			return $this->add_edit_site();
		}

		// If the $site_id variable is present we are editing
		
		$edit = ($this->input->post('site_id') && is_numeric($_POST['site_id'])) ? TRUE : FALSE;
		$do_comments = $this->db->table_exists('comments');
		
		$error = array();
		if ($edit == FALSE)
		{
			if ( ! file_exists(APPPATH.'language/'.$this->config->item('deft_lang').'/email_data'.EXT))
			{
				$error[] = $this->lang->line('unable_to_locate_specialty');
			}
			else
			{
				require(APPPATH.'language/'.$this->config->item('deft_lang').'/email_data'.EXT);
			}
		}
  
		
		/** -----------------------------------------
		/**  Create/Update Site
		/** -----------------------------------------*/
		
		$data = array('site_name'			=> $_POST['site_name'],
					  'site_label'			=> $_POST['site_label'],
					  'site_description'	=> $_POST['site_description'],
					  'site_bootstrap_checksums'	=> ''
					);

		if ($edit == FALSE)
		{
			// This is ugly, but the proper defaults are done by the config lib below
			$others = array('system_preferences', 'mailinglist_preferences', 'member_preferences', 'template_preferences', 'channel_preferences');
			
			$this->load->model('addons_model');
			
			if ($this->addons_model->module_installed('pages'))
			{
				$others[] = 'pages';
			}
			
			
			foreach($others as $field)
			{
				$data['site_'.$field] = '';
			}
			
			$this->db->query($this->db->insert_string('exp_sites', $data));
			
			$insert_id = $this->db->insert_id();
			$site_id = $insert_id;
			
			$success_msg = $this->lang->line('site_created');		 
		}
		else
		{			
			// Grab old data
			$old = $this->db->get_where('sites', array('site_id' => $this->input->post('site_id')));
			
			// Short name change, possibly need to update the template file folder
			if ($old->row('site_name') == $this->input->post('site_name'))
			{
				$prefs = $old->row('site_template_preferences');
				$prefs = unserialize(base64_decode($prefs));
				
				if ($basepath = $prefs['tmpl_file_basepath'])
				{
					$basepath = preg_replace("#([^/])/*$#", "\\1/", $basepath);		// trailing slash
					
					if (@is_dir($basepath.$old->row('site_name')))
					{
						@rename($basepath.$old->row('site_name'), $basepath.$_POST['site_name']);
					}
				}
			}
			
			$this->db->query($this->db->update_string('exp_sites', $data, 'site_id='.$this->db->escape_str($_POST['site_id'])));
			
			$site_id = $_POST['site_id'];

			$success_msg = $this->lang->line('site_updated');
		}
		
		$this->logger->log_action($success_msg.NBS.NBS.$_POST['site_label']);
		
		/** -----------------------------------------
		/**  Site Specific Stats Created
		/** -----------------------------------------*/
		
		if ($edit === FALSE)
		{
			$query = $this->db->get_where('stats', array('site_id' => '1'));
		
			foreach ($query->result_array() as $row)
			{				
				$data = $row;
				$data['site_id'] = $site_id;
				$data['last_entry_date'] = 0;
				$data['last_cache_clear'] = 0;
				
				unset($data['stat_id']);
				
				$this->db->query($this->db->insert_string('exp_stats', $data));
			}
		}
		
		/** -----------------------------------------
		/**  New Prefs Creation
		/** -----------------------------------------*/
		
		if ($edit === FALSE)
		{
			foreach(array('system', 'channel', 'template', 'mailinglist', 'member') as $type)
			{
				$prefs = array();
				
				foreach($this->config->divination($type) as $value)
				{
					$prefs[$value] = $this->config->item($value);
					
					$prefs['save_tmpl_files']	 = 'n';
					$prefs['tmpl_file_basepath'] = '';
				}
				
				$this->config->update_site_prefs($prefs, $site_id);
			}
		}
		
		/** -----------------------------------------
		/**  Create HTML Buttons for New Site
		/** -----------------------------------------*/
		if ($edit == FALSE)
		{
			$query = $this->db->get_where('html_buttons', array('site_id' => $this->config->item('site_id'), 'member_id' => 0));
			
			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					unset($row['id']);
					$row['site_id'] = $site_id;
					$this->db->query($this->db->insert_string('exp_html_buttons', $row));
				}
			}
		}
		
		/** -----------------------------------------
		/**  Create Specialty Templates for New Site
		/** -----------------------------------------*/
		if ($edit == FALSE)
		{
			$Q = array();
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'offline_template', '', '".addslashes(offline_template())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'message_template', '', '".addslashes(message_template())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'admin_notify_reg', '".addslashes(trim(admin_notify_reg_title()))."', '".addslashes(admin_notify_reg())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'admin_notify_entry', '".addslashes(trim(admin_notify_entry_title()))."', '".addslashes(admin_notify_entry())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'admin_notify_mailinglist', '".addslashes(trim(admin_notify_mailinglist_title()))."', '".addslashes(admin_notify_mailinglist())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'admin_notify_comment', '".addslashes(trim(admin_notify_comment_title()))."', '".addslashes(admin_notify_comment())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'admin_notify_gallery_comment', '".addslashes(trim(admin_notify_gallery_comment_title()))."', '".addslashes(admin_notify_gallery_comment())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'mbr_activation_instructions', '".addslashes(trim(mbr_activation_instructions_title()))."', '".addslashes(mbr_activation_instructions())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'forgot_password_instructions', '".addslashes(trim(forgot_password_instructions_title()))."', '".addslashes(forgot_password_instructions())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'reset_password_notification', '".addslashes(trim(reset_password_notification_title()))."', '".addslashes(reset_password_notification())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'validated_member_notify', '".addslashes(trim(validated_member_notify_title()))."', '".addslashes(validated_member_notify())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'decline_member_validation', '".addslashes(trim(decline_member_validation_title()))."', '".addslashes(decline_member_validation())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'mailinglist_activation_instructions', '".addslashes(trim(mailinglist_activation_instructions_title()))."', '".addslashes(mailinglist_activation_instructions())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'comment_notification', '".addslashes(trim(comment_notification_title()))."', '".addslashes(comment_notification())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'gallery_comment_notification', '".addslashes(trim(gallery_comment_notification_title()))."', '".addslashes(gallery_comment_notification())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'private_message_notification', '".addslashes(trim(private_message_notification_title()))."', '".addslashes(private_message_notification())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'pm_inbox_full', '".addslashes(trim(pm_inbox_full_title()))."', '".addslashes(pm_inbox_full())."')";

			foreach($Q as $sql)
			{
				$this->db->query($sql);
			}
		}
		
		/** -----------------------------------------
		/**  New Member Groups
		/** -----------------------------------------*/
		if ($edit == FALSE)
		{
			$query = $this->db->get_where('member_groups', array('site_id' => $this->config->item('site_id')));
		
			foreach ($query->result_array() as $row)
			{				
				$data = $row;
				$data['site_id'] = $site_id;
				
				$this->db->query($this->db->insert_string('exp_member_groups', $data, TRUE));
			}
		}
		
		/** -----------------------------------------
		/**  Moving of Data?
		/** -----------------------------------------*/
		
		if ($edit == FALSE)
		{
			$channel_ids = array();
			$moved	= array();
			$entries	= array();
		
			foreach($_POST as $key => $value)
			{
				/** -----------------------------------------
				/**  Channels Moving
				/** -----------------------------------------*/
			
				if (substr($key, 0, strlen('channel_')) == 'channel_' && $value != 'nothing' && is_numeric(substr($key, strlen('channel_'))))
				{
					$old_channel_id = substr($key, strlen('channel_'));
					
					if ($value == 'move')
					{
						$moved[$old_channel_id] = '';

						$this->db->query($this->db->update_string('exp_channels', 
													  array('site_id' => $site_id), 
													  "channel_id = '".$this->db->escape_str($old_channel_id)."'"));
													  
						$this->db->query($this->db->update_string('exp_channel_titles', 
													  array('site_id' => $site_id), 
													  "channel_id = '".$this->db->escape_str($old_channel_id)."'"));
													  
						$this->db->query($this->db->update_string('exp_channel_data', 
													  array('site_id' => $site_id), 
													  "channel_id = '".$this->db->escape_str($old_channel_id)."'"));

						if ($do_comments == TRUE)
						{
							$this->db->query($this->db->update_string('exp_comments',
													  array('site_id' => $site_id), 
													  "channel_id = '".$this->db->escape_str($old_channel_id)."'"));

						}

						$channel_ids[$old_channel_id] = $old_channel_id; // Stats, Groups, For Later
					}
					elseif($value == 'duplicate' OR $value == 'duplicate_all')
					{
						$query = $this->db->query("SELECT * FROM exp_channels WHERE channel_id = '".$this->db->escape_str($old_channel_id)."'");
						
						if ($query->num_rows() == 0)
						{
							continue;
						}
	
						$row = $query->row_array();

						// Uniqueness checks
						
						foreach(array('channel_name', 'channel_title') AS $check)
						{
							$count_query = $this->db->query("SELECT COUNT(*) AS count FROM exp_channels 
														WHERE site_id = '".$this->db->escape_str($site_id)."' 
														AND `".$check."` LIKE '".$this->db->escape_like_str($row[$check])."%'");
														
							if ($count_query->row('count')  > 0)
							{
								$row[$check] = $row[$check].'-'.($count_query->row('count')  + 1);
							}
						}
						
						$row['site_id']   = $site_id;
						unset($row['channel_id']);
					
						$this->db->query($this->db->insert_string('exp_channels', $row, TRUE));
						$channel_ids[$old_channel_id] = $this->db->insert_id();
						
						// exp_channel_member_groups
						
						$query = $this->db->query("SELECT group_id FROM exp_channel_member_groups WHERE channel_id = '".$this->db->escape_str($old_channel_id)."'");
						
						if ($query->num_rows() > 0)
						{
							foreach($query->result_array() as $row)
							{
								$this->db->query($this->db->insert_string('exp_channel_member_groups', 
															  array('channel_id' => $channel_ids[$old_channel_id],
															  		'group_id'	=> $row['group_id']),
															  TRUE));
							}
						}
						
						/** -----------------------------------------
						/**  Duplicating Entries Too
						/**  - Duplicates, Entries, Data.
						/**  - We try to reassigen relationships further down during $moved processing
						/**  - Forum Topics and Pages are NOT duplicated
						/** -----------------------------------------*/
						
						if ($value == 'duplicate_all')
						{
							$moved[$old_channel_id] = '';
							
							$query = $this->db->query("SELECT * FROM exp_channel_titles WHERE channel_id = '".$this->db->escape_str($old_channel_id)."'");
							
							$entries[$old_channel_id] = array();
							
							foreach($query->result_array() as $row)
							{
								$old_entry_id		= $row['entry_id'];
								$row['site_id']		= $site_id;
								unset($row['entry_id']);
								$row['channel_id']	= $channel_ids[$old_channel_id];
								
								$this->db->query($this->db->insert_string('exp_channel_titles', $row, TRUE));
								$entries[$old_channel_id][$old_entry_id] = $this->db->insert_id();
							}
							
							$query = $this->db->query("SELECT * FROM exp_channel_data WHERE channel_id = '".$this->db->escape_str($old_channel_id)."'");
							
							foreach($query->result_array() as $row)
							{
								$row['site_id']		= $site_id;
								$row['entry_id']	= $entries[$old_channel_id][$row['entry_id']];
								$row['channel_id']	= $channel_ids[$old_channel_id];
								
								$this->db->query($this->db->insert_string('exp_channel_data', $row, TRUE));
							}
							
							if ($do_comments == TRUE)
							{
								$query = $this->db->query("SELECT * FROM exp_comments WHERE channel_id = '".$this->db->escape_str($old_channel_id)."'");
							}
							
							if ($do_comments == TRUE && $query->num_rows() > 0)
							{
								$comment_queries = array();
								unset($query->result[0]['comment_id']);
								$fields = array_keys($query->row_array(0));
								unset($fields['0']);
								
								foreach ($query->result_array() as $row)
								{
									unset($row['comment_id']);
									$row['site_id']		= $site_id;
									$row['entry_id']	= $entries[$old_channel_id][$row['entry_id']];
									$row['channel_id']	= $channel_ids[$old_channel_id];
									$row['edit_date']	= ($row['edit_date'] == '') ? 0 : $row['edit_date'];

									$comment_queries[] = '("'.implode('","', $this->db->escape_str($row)).'")';
								}
								
								// do inserts in batches so the data movement isn't _completely_ insane...
								for ($i = 0, $total = count($comment_queries); $i < $total; $i = $i + 100)
								{
									$this->db->query("INSERT INTO exp_comments (`".implode('`, `', $fields)."`) VALUES ".implode(', ', array_slice($comment_queries, $i, 100)));
								}
								
								unset($comment_queries);						
							}
							
							$query = $this->db->query("SELECT * FROM exp_category_posts WHERE entry_id IN ('".implode("','", $this->db->escape_str(array_flip($entries[$old_channel_id])))."')");
							
							foreach($query->result_array() as $row)
							{
								$row['entry_id']	= $entries[$old_channel_id][$row['entry_id']];
								
								$this->db->query($this->db->insert_string('exp_category_posts', $row, TRUE));
							}
						}
					}
				}
				
				/** -----------------------------------------
				/**  Upload Directory Moving
				/** -----------------------------------------*/
				
				if (substr($key, 0, strlen('upload_')) == 'upload_' && $value != 'nothing' && is_numeric(substr($key, strlen('upload_'))))
				{
					$upload_id = substr($key, strlen('upload_'));
					
					if ($value == 'move')
					{
						$this->db->query($this->db->update_string('exp_upload_prefs', 
													  array('site_id' => $site_id), 
													  "id = '".$this->db->escape_str($upload_id)."'"));
					}
					else
					{
						$query = $this->db->query("SELECT * FROM exp_upload_prefs WHERE id = '".$this->db->escape_str($upload_id)."'");
						
						if ($query->num_rows() == 0)
						{
							continue;
						}

						$row = $query->row_array();
						
						// Uniqueness checks
						
						foreach(array('name') AS $check)
						{
							$count_query = $this->db->query("SELECT COUNT(*) AS count FROM exp_upload_prefs 
														WHERE site_id = '".$this->db->escape_str($site_id)."' 
														AND `".$check."` LIKE '".$this->db->escape_like_str($row[$check])."%'");
														
							if ($count_query->row('count')  > 0)
							{
								$row[$check] = $row[$check].'-'.($count_query->row('count')  + 1);
							}
						}
						
						$row['site_id']  = $site_id;
						unset($row['id']);
					
						$this->db->query($this->db->insert_string('exp_upload_prefs', $row, TRUE));
						
						$new_upload_id = $this->db->insert_id();						
						
						$disallowed_query = $this->db->query("SELECT member_group, upload_loc FROM exp_upload_no_access WHERE upload_id = '".$this->db->escape_str($upload_id)."'");
						
						if ($disallowed_query->num_rows() > 0)
						{
							foreach($disallowed_query->result_array() as $row)
							{
								$this->db->query($this->db->insert_string('exp_upload_no_access', array('upload_id' => $new_upload_id, 'upload_loc' => $row['upload_loc'], 'member_group' => $row['member_group'])));
							}
						}
					}
				}
				
				/** -----------------------------------------
				/**  Global Template Variables
				/** -----------------------------------------*/
				
				if (substr($key, 0, strlen('global_variables_')) == 'global_variables_' && $value != 'nothing' && is_numeric(substr($key, strlen('global_variables_'))))
				{
					$move_site_id = substr($key, strlen('global_variables_'));
					
					if ($value == 'move')
					{
						$this->db->query($this->db->update_string('exp_global_variables', 
													  array('site_id' => $site_id), 
													  "site_id = '".$this->db->escape_str($move_site_id)."'"));
					}
					else
					{
						$query = $this->db->query("SELECT * FROM exp_global_variables WHERE site_id = '".$this->db->escape_str($move_site_id)."'");
						
						if ($query->num_rows() == 0)
						{
							continue;
						}

						$row = $query->row_array();
						
						foreach($query->result_array() as $row)
						{
							// Uniqueness checks
						
							foreach(array('variable_name') AS $check)
							{
								$count_query = $this->db->query("SELECT COUNT(*) AS count FROM exp_global_variables 
															WHERE site_id = '".$this->db->escape_str($site_id)."' 
															AND `".$check."` LIKE '".$this->db->escape_like_str($row[$check])."%'");
															
								if ($count_query->row('count')  > 0)
								{
									$row[$check] = $row[$check].'-'.($count_query->row('count')  + 1);
								}
							}
						
							$row['site_id']		= $site_id;
							unset($row['variable_id']);
						
							$this->db->query($this->db->insert_string('exp_global_variables', $row, TRUE));
						}
					}
				}
				
				/** -----------------------------------------
				/**  Template Group and Template Moving
				/** -----------------------------------------*/
				
				if (substr($key, 0, strlen('template_group_')) == 'template_group_' && $value != 'nothing' && is_numeric(substr($key, strlen('template_group_'))))
				{
					$group_id = substr($key, strlen('template_group_'));
					
					if ($value == 'move')
					{
						$this->db->query($this->db->update_string('exp_template_groups', 
													  array('site_id' => $site_id), 
													  "group_id = '".$this->db->escape_str($group_id)."'"));
													  
						$this->db->query($this->db->update_string('exp_templates', 
													  array('site_id' => $site_id), 
												  "group_id = '".$this->db->escape_str($group_id)."'"));

					}
					else
					{
						$query = $this->db->query("SELECT * FROM exp_template_groups WHERE group_id = '".$this->db->escape_str($group_id)."'");
						
						if ($query->num_rows() == 0)
						{
							continue;
						}

						$row = $query->row_array();
						
						// Uniqueness checks
						
						foreach(array('group_name') AS $check)
						{
							$count_query = $this->db->query("SELECT COUNT(*) AS count FROM exp_template_groups 
														WHERE site_id = '".$this->db->escape_str($site_id)."' 
														AND `".$check."` LIKE '".$this->db->escape_like_str($row[$check])."%'");
														
							if ($count_query->row('count')  > 0)
							{
								$row[$check] = $row[$check].'-'.($count_query->row('count')  + 1);
							}
						}
						
						// Create New Group
						
						$row['site_id'] 		= $site_id;
						unset($row['group_id']);
					
						$this->db->query($this->db->insert_string('exp_template_groups', $row, TRUE));
						
						$new_group_id = $this->db->insert_id();
						
						/** -----------------------------------------
						/**  Member Group Access to Template Groups
						/** -----------------------------------------*/
						
						$query = $this->db->query("SELECT * FROM exp_template_member_groups WHERE template_group_id = '".$this->db->escape_str($query->row('group_id') )."'");
						
						if ($query->num_rows() > 0)
						{
							foreach($query->result_array() as $row)
							{
								$this->db->query($this->db->insert_string('exp_template_member_groups', 
															  array('template_group_id'	=> $new_group_id, 
															  		'group_id' 			=> $row['group_id']), 
															  TRUE));
							}
						}
						
						
						/** -----------------------------------------
						/**  Create Templates for New Template Group
						/** -----------------------------------------*/
						
						$query = $this->db->query("SELECT * FROM exp_templates WHERE group_id = '".$this->db->escape_str($group_id)."'");
						
						if ($query->num_rows() == 0)
						{
							continue;
						}
						
						foreach($query->result_array() as $row)
						{
							$original_id		= $row['template_id'];
							$row['site_id']		= $site_id;
							$row['group_id']	= $new_group_id;
							unset($row['template_id']);
						
							$this->db->query($this->db->insert_string('exp_templates', $row, TRUE));
							
							$new_template_id = $this->db->insert_id();
							
							/** -----------------------------------------
							/**  Template/Page Access
							/** -----------------------------------------*/
							
							$access_query = $this->db->query("SELECT * FROM exp_template_no_access WHERE template_id = '".$this->db->escape_str($original_id)."'");
							
							if ($query->num_rows() > 0)
							{
								foreach($access_query->result_array() as $access_row)
								{
									$this->db->query($this->db->insert_string('exp_template_no_access', 
																  array('template_id'	=> $new_template_id, 
																		'member_group' 	=> $access_row['member_group']), 
																  TRUE));
								}
							}
						}
					}
				}
			}
			
			/** -----------------------------------------
			/**  Additional Channel Moving Work - Stats/Groups
			/** -----------------------------------------*/
			
			if (count($channel_ids) > 0)
			{
				$status			 = array();
				$fields			 = array();
				$categories		 = array();
				$category_groups = array();
				
				$field_match	 = array();
				$cat_field_match	 = array();

				foreach($channel_ids as $old_channel => $new_channel)
				{
					$query = $this->db->query("SELECT cat_group, status_group, field_group FROM exp_channels WHERE channel_id = '".$this->db->escape_str($new_channel)."'");

					$row = $query->row_array();
					
					/** -----------------------------------------
					/**  Duplicate Status Group
					/** -----------------------------------------*/
					
					$status_group = $query->row('status_group');
					
					if ( ! empty($status_group))
					{					
						if ( ! isset($status[$query->row('status_group') ]))
						{
							$this->db->select('group_name');
							$squery = $this->db->get_where('status_groups', array('group_id' => $query->row('status_group')));
			
							$row = $squery->row_array();
							
							// Uniqueness checks
						
							foreach(array('group_name') AS $check)
							{
								$count_query = $this->db->query("SELECT COUNT(*) AS count FROM exp_status_groups 
															WHERE site_id = '".$this->db->escape_str($site_id)."' 
															AND `".$check."` LIKE '".$this->db->escape_like_str($row[$check])."%'");
															
															
															
								if ($count_query->row('count')  > 0)
								{
									$row[$check] = $row[$check].'-'.($count_query->row('count')  + 1);

								}
							}
							
							$this->db->query($this->db->insert_string('exp_status_groups', 
														  array('site_id'		=> $site_id, 
																'group_name' 	=> $row['group_name']), 
														  TRUE));
							
							$status[$query->row('status_group') ] = $this->db->insert_id();
							
							$squery = $this->db->query("SELECT * FROM exp_statuses WHERE group_id = '".$this->db->escape_str($query->row('status_group') )."'");
							
							if ($squery->num_rows() > 0)
							{
								foreach($squery->result_array() as $row)
								{
									$row['site_id'] 	= $site_id;
									unset($row['status_id']);
									$row['group_id']	= $status[$query->row('status_group') ];
								
									$this->db->query($this->db->insert_string('exp_statuses', $row, TRUE));
								}
							}
						}
						
						/** -----------------------------------------
						/**  Update Channel With New Group ID
						/** -----------------------------------------*/
						
						$this->db->query($this->db->update_string(	'exp_channels', 
														array('status_group' => $status[$query->row('status_group') ]), 
														"channel_id = '".$this->db->escape_str($new_channel)."'"));
					}
					
					
					/** -----------------------------------------
					/**  Duplicate Field Group
					/** -----------------------------------------*/
					
					$field_group = $query->row('field_group');
					
					if ( ! empty($field_group))
					{					
						if ( ! isset($fields[$query->row('field_group') ]))
						{
							$fquery = $this->db->query("SELECT group_name FROM exp_field_groups WHERE group_id = '".$this->db->escape_str($query->row('field_group') )."'");
							
							$fq_group_name = $fquery->row('group_name');
							
							// Uniqueness checks
						
							foreach(array('group_name') AS $check)
							{
								$count_query = $this->db->query("SELECT COUNT(*) AS count FROM exp_field_groups 
															WHERE site_id = '".$this->db->escape_str($site_id)."' 
															AND `".$check."` LIKE '".$this->db->escape_like_str($fquery->row($check))."%'");
															
								if ($count_query->row('count')  > 0)
								{
									$fq_group_name = $fquery->row($check).'-'.($count_query->row('count')  + 1);
								}
							}
							
							$this->db->query($this->db->insert_string('exp_field_groups', 
														  array('site_id'		=> $site_id, 
																'group_name' 	=> $fq_group_name ), 
														  TRUE));
							
							$fields[$query->row('field_group') ] = $this->db->insert_id();
							
							/** -----------------------------------------
							/**  New Fields Created for New Field Group
							/** -----------------------------------------*/
							
							$fquery = $this->db->query("SELECT * FROM exp_channel_fields WHERE group_id = '".$this->db->escape_str($query->row('field_group') )."'");
							
							if ($fquery->num_rows() > 0)
							{
								foreach($fquery->result_array() as $row)
								{
									$format_query = $this->db->query("SELECT field_fmt FROM exp_field_formatting 
																WHERE field_id = '".$this->db->escape_str($row['field_id'])."'");
																
									$old_field_id 		= $row['field_id'];
								
									$row['site_id'] 	= $site_id;
									unset($row['field_id']);
									$row['group_id']	= $fields[$query->row('field_group') ];
									
									// Uniqueness checks
						
									foreach(array('field_name', 'field_label') AS $check)
									{
										$count_query = $this->db->query("SELECT COUNT(*) AS count FROM exp_channel_fields 
																	WHERE site_id = '".$this->db->escape_str($site_id)."' 
																	AND `".$check."` LIKE '".$this->db->escape_like_str($row[$check])."%'");
																	
										if ($count_query->row('count')  > 0)
										{
											$row[$check] = $row[$check].'-'.($count_query->row('count')  + 1);
										}
									}
								
									$this->db->query($this->db->insert_string('exp_channel_fields', $row, TRUE));
									
									$field_id = $this->db->insert_id();
									
									$field_match[$old_field_id] = $field_id;
									
									/** -----------------------------------------
									/**  Channel Data Field Creation, Whee!
									/** -----------------------------------------*/
									
									switch($row['field_type'])
									{
										case 'date'	:
											$this->db->query("ALTER TABLE exp_channel_data ADD COLUMN `field_id_".$this->db->escape_str($field_id)."` int(10) NOT NULL DEFAULT 0");		
											$this->db->query("ALTER TABLE exp_channel_data ADD COLUMN `field_ft_".$this->db->escape_str($field_id)."` tinytext NULL");
											$this->db->query("ALTER TABLE exp_channel_data ADD COLUMN `field_dt_".$this->db->escape_str($field_id)."` varchar(8) AFTER field_ft_".$this->db->escape_str($field_id).""); 
										break;
										case 'rel'	:
											$this->db->query("ALTER TABLE exp_channel_data ADD COLUMN `field_id_".$this->db->escape_str($field_id)."` int(10) NOT NULL DEFAULT 0");		
											$this->db->query("ALTER TABLE exp_channel_data ADD COLUMN `field_ft_".$this->db->escape_str($field_id)."` tinytext NULL");
										break;
										default		:
											$this->db->query("ALTER TABLE exp_channel_data ADD COLUMN `field_id_".$this->db->escape_str($field_id)."` text");		
											$this->db->query("ALTER TABLE exp_channel_data ADD COLUMN `field_ft_".$this->db->escape_str($field_id)."` tinytext NULL");
										break;
									}
									
									/** -----------------------------------------
									/**  Duplicate Each Fields Formatting Options Too
									/** -----------------------------------------*/
									
									if ($format_query->result_array() > 0)
									{
										foreach($format_query->result_array() as $format_row)
										{
											$this->db->query($this->db->insert_string('exp_field_formatting', 
																		  array('field_id'  => $field_id,
																		  		'field_fmt' => $format_row['field_fmt']),
																		  TRUE));
										}
									}
								}
							}
						}
						
						/** -----------------------------------------
						/**  Update Channel With New Group ID
						/** -----------------------------------------*/
						
						//  Synce up a few new fields in the channel table
						$channel_results = $this->db->query("SELECT search_excerpt FROM exp_channels WHERE channel_id = '". $this->db->escape_str($old_channel)."'");

						$channel_data['search_excerpt'] = '';

						if ($channel_results->num_rows() > 0)
						{
							$channel_row = $channel_results->row_array(); 
							
							if (isset($field_match[$channel_row['search_excerpt']]))
							{
								$channel_data['search_excerpt'] = $field_match[$channel_row['search_excerpt']];
							}
						}
						
						$this->db->query($this->db->update_string('exp_channels', 
														array('field_group' => $fields[$query->row('field_group') ], 
														 'search_excerpt' => (int) $channel_data['search_excerpt']), 
														"channel_id = '".$this->db->escape_str($new_channel)."'"));
														
						/** -----------------------------------------
						/**  Moveed Channel?  Need Old Field Group
						/** -----------------------------------------*/

						if (isset($moved[$old_channel]))
						{
							$moved[$old_channel] = $query->row('field_group') ;
						}
					}
					
					
					/** -----------------------------------------
					/**  Duplicate Category Group(s)
					/** -----------------------------------------*/
					
					$cat_group = $query->row('cat_group');
					
					if ( ! empty($cat_group))
					{			
						$new_insert_group = array();
					
						foreach(explode('|', $query->row('cat_group') ) as $cat_group)
						{
							if (isset($category_groups[$cat_group]))
							{
								$new_insert_group[] = $category_groups[$cat_group];
								
								continue;
							}
						
							$gquery = $this->db->query("SELECT group_name FROM exp_category_groups WHERE group_id = '".$this->db->escape_str($cat_group)."'");
							
							if ($gquery->num_rows() == 0)
							{
								continue;
							}
							
							$gquery_row = $gquery->row();
							
							// Uniqueness checks
						
							foreach(array('group_name') AS $check)
							{
								$count_query = $this->db->query("SELECT COUNT(*) AS count FROM exp_category_groups 
															WHERE site_id = '".$this->db->escape_str($site_id)."' 
															AND `".$check."` LIKE '".$this->db->escape_like_str($gquery->row($check))."%'");
															
								if ($count_query->row('count')  > 0)
								{
									$gquery_row->$check = $gquery->row($check).'-'.($count_query->row('count')  + 1);
								}
							}
							
							$gquery_row->site_id   = $site_id;
							unset($gquery_row->group_id);
							
							$this->db->query($this->db->insert_string('exp_category_groups', $gquery_row, TRUE));
							
							$category_groups[$cat_group] = $this->db->insert_id();
							
							$new_insert_group[] = $category_groups[$cat_group];
							
							/** -----------------------------------------
							/**  Custom Category Fields
							/** -----------------------------------------*/
							
							$fquery = $this->db->query("SELECT * FROM exp_category_fields WHERE group_id = '".$this->db->escape_str($cat_group)."'");
							
							if ($fquery->num_rows() > 0)
							{
								foreach($fquery->result_array() as $row)
								{
									// Uniqueness checks
						
									foreach(array('field_name') AS $check)
									{
										$count_query = $this->db->query("SELECT COUNT(*) AS count FROM exp_category_fields 
																	WHERE site_id = '".$this->db->escape_str($site_id)."' 
																	AND `".$check."` LIKE '".$this->db->escape_like_str($row[$check])."%'");
																	
										if ($count_query->row('count')  > 0)
										{
											$row[$check] = $row[$check].'-'.($count_query->row('count')  + 1);
										}
									}

									$old_field_id = $row['field_id'];

									$row['site_id'] 	= $site_id;
									unset($row['field_id']);
									$row['group_id']	= $category_groups[$cat_group];

									$this->db->query($this->db->insert_string('exp_category_fields', $row, TRUE));

									$field_id = $this->db->insert_id();

									$cat_field_match[$old_field_id] = $field_id;

									/** ---------------------------------------------
									/**  Custom Catagory Field Data Creation, Whee!
									/** ---------------------------------------------*/
									$this->db->query("ALTER TABLE `exp_category_field_data` ADD COLUMN `field_id_{$field_id}` text NOT NULL");
									$this->db->query("ALTER TABLE `exp_category_field_data` ADD COLUMN `field_ft_{$field_id}` varchar(40) NOT NULL default 'none'");
									$this->db->query("UPDATE `exp_category_field_data` SET `field_ft_{$field_id}` = '".$this->db->escape_str($row['field_default_fmt'])."'");
								}
							}
							
							/** -----------------------------------------
							/**  New Categories Created for New Category Group
							/** -----------------------------------------*/
							
							$cquery = $this->db->query("SELECT * FROM exp_categories WHERE group_id = '".$this->db->escape_str($cat_group)."' ORDER BY parent_id");
							
							if ($cquery->num_rows() > 0)
							{
								foreach($cquery->result_array() as $row)
								{
									$fields_query = $this->db->query("SELECT * FROM exp_category_field_data 
																WHERE cat_id = '".$this->db->escape_str($row['cat_id'])."'");
																
									// Uniqueness checks
						
									foreach(array('cat_url_title') AS $check)
									{
										$count_query = $this->db->query("SELECT COUNT(*) AS count FROM exp_categories 
																	WHERE site_id = '".$this->db->escape_str($site_id)."' 
																	AND `".$check."` LIKE '".$this->db->escape_like_str($row[$check])."%'");
																	
										if ($count_query->row('count')  > 0)
										{
											$row[$check] = $row[$check].'-'.($count_query->row('count')  + 1);
										}
									}
																
									$old_cat_id 		= $row['cat_id'];
								
									$row['site_id'] 	= $site_id;
									unset($row['cat_id']);
									$row['group_id']	= $category_groups[$cat_group];
									$row['parent_id']	= ($row['parent_id'] == '0' OR ! isset($categories[$row['parent_id']])) ? '0' : $categories[$row['parent_id']];
								
									$this->db->query($this->db->insert_string('exp_categories', $row, TRUE));
									
									$cat_id = $this->db->insert_id();
									
									$categories[$old_cat_id] = $cat_id;
									
									/** -----------------------------------------
									/**  Duplicate Field Data Too
									/** -----------------------------------------*/

									if ($fields_query->num_rows() > 0)
									{
										$fields_query_row = $fields_query->row_array();
										
										$fields_query_row['site_id']	= $site_id;
										$fields_query_row['group_id']	= $category_groups[$cat_group];
										$fields_query_row['cat_id']		= $cat_id;

										foreach ($fquery->result_array() as $fq_row)
										{
											if ($fields_query_row["field_id_{$fq_row['field_id']}"] != '')
											{
												$fields_query_row['field_id_'.$cat_field_match[$fq_row['field_id']]] = $fields_query_row["field_id_{$fq_row['field_id']}"];
												$fields_query_row["field_id_{$fq_row['field_id']}"] = '';
											}
										}
										
										$this->db->query($this->db->insert_string('exp_category_field_data', $fields_query_row, TRUE));
									}
								}
							}
						}
						
						$new_insert_group = implode('|', $new_insert_group);
					}
					else
					{
						$new_insert_group = '';
					}
						
					/** -----------------------------------------
					/**  Update Channel With New Group ID
					/** -----------------------------------------*/
				
					$this->db->query($this->db->update_string( 'exp_channels', array('cat_group' => $new_insert_group), "channel_id = '".$this->db->escape_str($new_channel)."'"));
				}
				
				
				/** -----------------------------------------
				/**  Move Data Over For Moveed Channels/Entries
				/**  - Find Old Fields from Old Site Field Group, Move Data to New Fields, Zero Old Fields
				/**  - Reassign Categories for New Channels Based On $categories array
				/** -----------------------------------------*/
									
				if (count($moved) > 0)
				{
					$moved_relationships = array();
				
					/** -----------------------------------------
					/**  Relationship Field Checking? - For 'duplicate_all' for channels, NOT enabled
					/** -----------------------------------------*/
					
					if (count($entries) > 0)
					{
						$complete_entries = array();
						
						foreach($entries as $old_channel => $its_entries)
						{
							$complete_entries = array_merge($complete_entries, $its_entries);
						}
						
						// Find Relationships for Old Entry IDs That Have Been Moveed
						$query = $this->db->query("SELECT * FROM exp_relationships WHERE rel_parent_id IN ('".implode("','", $this->db->escape_str(array_flip($complete_entries)))."')");
						
						if ($query->num_rows() > 0)
						{
							foreach($query->result_array() as $row)
							{
								// Only If Child Moveed As Well...
								
								if (isset($complete_entries[$row['rel_child_id']]))
								{
									$old_rel_id 		  = $row['rel_id'];
									unset($row['rel_id']);
									$row['rel_child_id']  = $complete_entries[$row['rel_child_id']];
									$row['rel_parent_id'] = $complete_entries[$row['rel_parent_id']];
									
									$this->db->query($this->db->insert_string('exp_relationships', $row, TRUE));
									
									$moved_relationships[$old_rel_id] = $this->db->insert_id();
								}
							}
						}
					}
				
					/** -----------------------------------------
					/**  Moving Field Data for Moved Entries
					/** -----------------------------------------*/
				
					foreach($moved as $channel_id => $field_group)
					{
						$query = $this->db->query("SELECT field_id, field_type, field_related_to FROM exp_channel_fields WHERE group_id = '".$this->db->escape_str($field_group)."'");
						
						if (isset($entries[$channel_id]))
						{
							$channel_id = $channel_ids[$channel_id]; // Moved Entries, New Channel ID Used
						}
						
						if ($query->num_rows() > 0)
						{
							$related_fields = array();
						
							foreach($query->result_array() as $row)
							{
								if ( ! isset($field_match[$row['field_id']])) continue;
								
								$this->db->query("UPDATE exp_channel_data 
											SET `field_id_".$this->db->escape_str($field_match[$row['field_id']])."` = `field_id_".$this->db->escape_str($row['field_id'])."` 
											WHERE channel_id = '".$this->db->escape_str($channel_id)."'");
											
								$this->db->query("UPDATE exp_channel_data 
											SET `field_ft_".$this->db->escape_str($field_match[$row['field_id']])."` = `field_ft_".$this->db->escape_str($row['field_id'])."` 
											WHERE channel_id = '".$this->db->escape_str($channel_id)."'");
											
								if ($row['field_type'] == 'date')
								{
									$this->db->query("UPDATE exp_channel_data 
												SET `field_dt_".$this->db->escape_str($field_match[$row['field_id']])."` = `field_dt_".$this->db->escape_str($row['field_id'])."` 
												WHERE channel_id = '".$this->db->escape_str($channel_id)."'");
								}
								
								if ($row['field_type'] == 'rel' && $row['field_related_to'] == 'channel')
								{
									$related_fields[] = 'field_ft_'.$field_match[$row['field_id']];  // We used this for moved relationships, see above
								}
								
								if ($row['field_type'] == 'date' OR $row['field_type'] == 'rel')
								{
									$this->db->query("UPDATE exp_channel_data 
											SET `field_id_".$this->db->escape_str($row['field_id'])."` = 0
											WHERE channel_id = '".$this->db->escape_str($channel_id)."'");
									
								}
								else
								{
									$this->db->query("UPDATE exp_channel_data 
											SET `field_id_".$this->db->escape_str($row['field_id'])."` = ''
											WHERE channel_id = '".$this->db->escape_str($channel_id)."'");
								}
								
							}
							
							/** -----------------------------------------
							/**  Modifying Field Data for Related Entries
							/** -----------------------------------------*/
							
							if (count($related_fields) > 0 && count($moved_relationships) > 0)
							{
								$query = $this->db->query('SELECT '.implode(',', $related_fields).' FROM exp_channel_data
													 WHERE ('.implode(" != 0 OR ", $related_fields).')');
								
								if ($query->num_rows() > 0)
								{
									foreach($query->result_array() as $row)
									{
										foreach($row as $key => $value)
										{
											if ($value != '0' && isset($moved_relationships[$value]))
											{
												$this->db->query("UPDATE exp_channel_data 
															SET `{$key}` = '".$this->db->escape_str($moved_relationships[$value])."'
															WHERE `{$key}` = '".$this->db->escape_str($value)."'");
											}
										}
									}
								}
							}
						}
						
						/** -----------------------------------------
						/**  Category Reassignment
						/** -----------------------------------------*/
						
						$query = $this->db->query("SELECT cp.entry_id FROM exp_category_posts cp, exp_channel_titles wt 
											 WHERE wt.channel_id = '".$this->db->escape_str($channel_id)."' 
											 AND wt.entry_id = cp.entry_id");
											 
						if ($query->num_rows() > 0)
						{
							$entry_ids = array();
							
							foreach($query->result_array() as $row)
							{
								$entry_ids[] = $row['entry_id'];
							}
							
							foreach($categories as $old_cat => $new_cat)
							{
								$this->db->query("UPDATE exp_category_posts SET cat_id = '".$this->db->escape_str($new_cat)."'
											WHERE cat_id = '".$this->db->escape_str($old_cat)."'
											AND entry_id IN (".implode(',', $entry_ids).")");
							}
						}
					}
				}
			}
		}
		
		/** -----------------------------------------
		/**  Refresh Sites List
		/** -----------------------------------------*/
			
		$assigned_sites = array();
		
		if ($this->session->userdata['group_id'] == 1)
		{
			$result = $this->db->query("SELECT site_id, site_label FROM exp_sites ORDER BY site_label");
		}
		elseif ($this->session->userdata['assigned_sites'] != '')
		{
			$this->db->select('site_id, site_label');
			$this->db->where_in('site_id', explode('|', $this->session->userdata['assigned_sites']));
			$this->db->order_by('site_label');
			$result = $this->db->get('sites');
			
		//	$result = $this->db->query("SELECT site_id, site_label FROM exp_sites WHERE site_id IN (".$this->db->escape_str(explode('|', $this->session->userdata['assigned_sites'])).") ORDER BY site_label");
		}
		
		if (($this->session->userdata['group_id'] == 1 OR $this->session->userdata['assigned_sites'] != '') && $result->num_rows() > 0)
		{
			foreach ($result->result_array() as $row)
			{
				$assigned_sites[$row['site_id']] = $row['site_label'];
			}
		}
		
		$this->session->userdata['assigned_sites'] = $assigned_sites;

		// Update site stats
		$original_site_id = $this->config->item('site_id');
		
		$this->config->set_item('site_id', $site_id);
		
		if ($do_comments === TRUE)
		{
			$this->stats->update_comment_stats();			
		}

		$this->stats->update_member_stats();
		$this->stats->update_channel_stats();
        
		$this->config->set_item('site_id', $original_site_id);

		/** -----------------------------------------
		/**  View Sites List
		/** -----------------------------------------*/
		
		if ($edit === TRUE)
		{
			$this->functions->redirect(BASE.AMP.'C=sites'.AMP.'M=manage_sites&updated_id='.$site_id);
		}
		else
		{
			$this->functions->redirect(BASE.AMP.'C=sites'.AMP.'M=manage_sites&created_id='.$site_id);
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Site Delete Confirmation
	 * 
	 *
	 * @access	public
	 * @return	mixed
	 */
	function site_delete_confirm()
	{
		if ( ! $this->cp->allowed_group('can_admin_sites'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		if ( ! $site_id = $this->input->get_post('site_id'))
		{
			return FALSE;
		}
		
		if ($site_id == 1)
		{
			return FALSE;
		}
		
		$this->load->helper('form');
		
		$this->db->select('site_label');
		$query = $this->db->get_where('sites', array('site_id' => $site_id));
		
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
		
		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_site'));
		
		$vars['site_id'] = $site_id;
		$vars['message'] = $this->lang->line('delete_site_confirmation');
		
		$this->javascript->compile();
		$this->load->view('sites/delete_confirm', $vars);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Site Delete Confirmation
	 * 
	 *
	 * @access	public
	 * @return	mixed
	 */
	function delete_site()
	{
		if ( ! $this->cp->allowed_group('can_admin_sites'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
	
		if ( ! $site_id = $this->input->post('site_id'))
		{
			return FALSE;
		}
		
		if ( ! is_numeric($site_id))
		{
			return FALSE;
		}
		
		if ($site_id == 1)
		{
			return FALSE;
		}
		
		$query = $this->db->query("SELECT site_label FROM exp_sites WHERE site_id = '".$this->db->escape_str($site_id)."'");
		
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
		
		$this->logger->log_action($this->lang->line('site_deleted').':'.NBS.NBS.$query->row('site_label') );

		$this->db->select('entry_id');
		$this->db->where('site_id', $site_id);
		$query = $this->db->get('channel_titles');

		$entries = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$entries[] = $row->entry_id;
			}
		}

		// Just like a gossipy so-and-so, we will now destroy relationships! Category post is also toast.
		if (count($entries) > 0)
		{
			// delete leftovers in category_posts
			$this->db->where_in('entry_id', $entries);
			$this->db->delete('category_posts');

			// delete parents
			$this->db->where_in('rel_parent_id', $entries);
			$this->db->delete('relationships');
			
			// are there children?
			$this->db->select('rel_id');
			$this->db->where_in('rel_child_id', $entries);
			$child_results = $this->db->get('relationships');

			if ($child_results->num_rows() > 0)
			{
				// gather related fields
				$this->db->select('field_id');
				$this->db->where('field_type', 'rel');
				$fquery = $this->db->get('channel_fields');

				// We have children, so we need to do a bit of housekeeping
				// so parent entries don't continue to try to reference them
				$cids = array();

				foreach ($child_results->result_array() as $row)
				{
					$cids[] = $row['rel_id'];
				}

				foreach($fquery->result_array() as $row)
				{
					$this->db->where_in('field_id_'.$row['field_id'], $cids);
					$this->db->update('channel_data', array('field_id_'.$row['field_id'] => 0));
				}
			}

			// aaaand delete
			$this->db->where_in('rel_child_id', $entries);
			$this->db->delete('relationships');
		}


		/** -----------------------------------------
		/**  Delete Channel Custom Field Columns for Site
		/** -----------------------------------------*/
		
		$query = $this->db->query("SELECT field_id, field_type FROM exp_channel_fields 
							 WHERE site_id = '".$this->db->escape_str($site_id)."'");
		
		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$this->db->query("ALTER TABLE exp_channel_data DROP COLUMN field_id_".$row['field_id']);
                
				if ($row['field_type'] == 'date')
				{
					$this->db->query("ALTER TABLE exp_channel_data DROP COLUMN field_dt_".$row['field_id']);
				}
			}
		}
		
		/** -----------------------------------------
		/**  Delete Category Custom Field Columns for Site
		/** -----------------------------------------*/
		
		$query = $this->db->query("SELECT field_id FROM exp_category_fields 
							 WHERE site_id = '".$this->db->escape_str($site_id)."'");
		
		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$field_id = $row['field_id'];
				$this->db->query("ALTER TABLE exp_category_field_data DROP COLUMN field_id_{$field_id}");
        		$this->db->query("ALTER TABLE exp_category_field_data DROP COLUMN field_ft_{$field_id}");
			}
		}
		

		/** -----------------------------------------
		/**  Delete Upload Permissions for Site
		/** -----------------------------------------*/
		
		$query = $this->db->query("SELECT id FROM `exp_upload_prefs` WHERE site_id = '".$this->db->escape_str($site_id)."'");
		$upload_ids = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$upload_ids[] = $row['id'];
			}

			$this->db->query("DELETE FROM `exp_upload_no_access` WHERE upload_id IN (".implode(',', $upload_ids).")");
		}
		
		/** -----------------------------------------
		/**  Delete Everything Having to Do with the Site
		/** -----------------------------------------*/
		
		$tables = array('exp_categories',
						'exp_category_fields',
						'exp_category_field_data',
						'exp_category_groups',
						'exp_comments',
						'exp_cp_log',
						'exp_field_groups',
						'exp_global_variables',
						'exp_html_buttons',
						'exp_member_groups',
						'exp_member_search',
						'exp_online_users',
						'exp_ping_servers',
						'exp_referrers',
						'exp_search',
						'exp_search_log',
						'exp_sessions',
						'exp_sites',
						'exp_specialty_templates',
						'exp_stats',
						'exp_statuses',
						'exp_status_groups',
						'exp_templates',
						'exp_template_groups',
						'exp_upload_prefs',
						'exp_channels',
						'exp_channel_data',
						'exp_channel_fields',
						'exp_channel_titles',
						);
		
		foreach($tables as $table)
		{
			if ($this->db->table_exists($table) === FALSE) continue;  // For a few modules that can be uninstalled
		
			$this->db->query("DELETE FROM `$table` WHERE site_id = {$site_id}");
		}

		/** -----------------------------------------
		/**  Refresh Sites List
		/** -----------------------------------------*/
			
		$assigned_sites = array();
		
		if ($this->session->userdata['group_id'] == 1)
		{
			$result = $this->db->query("SELECT site_id, site_label FROM exp_sites ORDER BY site_label");
		}
		elseif ($this->session->userdata['assigned_sites'] != '')
		{
			$result = $this->db->query("SELECT site_id, site_label FROM exp_sites WHERE site_id IN (".$this->db->escape_str(explode('|', $this->session->userdata['assigned_sites'])).") ORDER BY site_label");
		}
		
		if (($this->session->userdata['group_id'] == 1 OR $this->session->userdata['assigned_sites'] != '') && $result->num_rows() > 0)
		{
			foreach ($result->result_array() as $row)
			{
				$assigned_sites[$row['site_id']] = $row['site_label'];
			}
		}
		
		$this->session->userdata['assigned_sites'] = $assigned_sites;
		
		/** -----------------------------------------
		/**  Reload to Site Admin
		/** -----------------------------------------*/
		
		$this->functions->redirect(BASE.AMP.'C=sites'.AMP.'M=manage_sites');
	}
}

/* End of file sites.php */
/* Location: ./system/expressionengine/controllers/cp/sites.php */