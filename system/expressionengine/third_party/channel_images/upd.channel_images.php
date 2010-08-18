<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Channel Images
 *
 * Install/Update/Remove Channel Images
 *
 * @author DevDemon
 * @copyright Copyright (c) DevDemon
 **/
class Channel_images_upd
{
	var $version	=	'2.1.5';
	var $module_name=	'channel_images';

	function Channel_images_upd()
	{
		$this->EE =& get_instance();
	}

	// ********************************************************************************* //

	function install()
	{
		// Load dbforge
		$this->EE->load->dbforge();

		//----------------------------------------
		// EXP_MODULES
		//----------------------------------------
		$module = array(	'module_name' => ucfirst($this->module_name),
							'module_version' => $this->version,
							'has_cp_backend' => 'y',
							'has_publish_fields' => 'n' );

		$this->EE->db->insert('modules', $module);

		//----------------------------------------
		// EXP_CHANNEL_IMAGES
		//----------------------------------------
		$ci = array(
			'image_id' 		=> array('type' => 'INT',		'unsigned' => TRUE,	'auto_increment' => TRUE),
			'site_id'		=> array('type' => 'TINYINT',	'unsigned' => TRUE,	'default' => 1),
			'entry_id'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 1),
			'field_id'		=> array('type' => 'MEDIUMINT',	'unsigned' => TRUE, 'default' => 1),
			'channel_id'	=> array('type' => 'TINYINT',	'unsigned' => TRUE, 'default' => 1),
			'cover'			=> array('type' => 'TINYINT',	'constraint' => '1', 'unsigned' => TRUE, 'default' => 0),
			'image_order'	=> array('type' => 'SMALLINT',	'unsigned' => TRUE, 'default' => 1),
			'filename'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'extension'		=> array('type' => 'VARCHAR',	'constraint' => '20', 'default' => ''),
			'title'			=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'description'	=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'category'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'sizes'			=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
		);

		$this->EE->dbforge->add_field($ci);
		$this->EE->dbforge->add_key('image_id', TRUE);
		$this->EE->dbforge->add_key('entry_id');
		$this->EE->dbforge->create_table('channel_images', TRUE);

		//----------------------------------------
		// EXP_ACTIONS
		//----------------------------------------
		$module = array(	'class' => ucfirst($this->module_name),
							'method' => $this->module_name . '_router' );

		$this->EE->db->insert('actions', $module);

		//----------------------------------------
		// EXP_MODULES
		// The settings column, Ellislab should have put this one in long ago.
		// No need for a seperate preferences table for each module.
		//----------------------------------------
		if ($this->EE->db->field_exists('settings', 'modules') == FALSE)
		{
			$this->EE->dbforge->add_column('modules', array('settings' => array('type' => 'TEXT') ) );
		}

		// Do we need to enable the extension
        //if ($this->uses_extension === TRUE) $this->extension_handler('enable');

		return TRUE;
	}

	// ********************************************************************************* //

	function uninstall()
	{
		// Load dbforge
		$this->EE->load->dbforge();

		// Remove
		$this->EE->dbforge->drop_table('channel_images');
		$this->EE->db->where('module_name', ucfirst($this->module_name));
		$this->EE->db->delete('modules');
		$this->EE->db->where('class', ucfirst($this->module_name));
		$this->EE->db->delete('actions');

		// $this->EE->cp->delete_layout_tabs($this->tabs(), 'tagger');

		return TRUE;
	}

	// ********************************************************************************* //

	function update($current = '')
	{
		// Are they the same?
		if ($current >= $this->version)
		{
			return FALSE;
		}

		// Load dbforge
		$this->EE->load->dbforge();

		// For Version < 2.0.0
    	if ($current < '2.0.0')
    	{
    		// Add the Fiel_id Column
    		if ($this->EE->db->field_exists('field_id', 'channel_images') == FALSE)
			{
				$fields = array( 'field_id'	=> array('type' => 'MEDIUMINT',	'unsigned' => TRUE, 'default' => 1) );
				$this->EE->dbforge->add_column('channel_images', $fields, 'entry_id');
			}

			// Rename: weblog_id=>channel_id, order=>image_order
			if ($this->EE->db->field_exists('channel_id', 'channel_images') == FALSE)
			{
				$fields = array( 'weblog_id' => array('name' => 'channel_id'),
								 '`order`' => array('name' => 'image_order')
								);
				$this->EE->dbforge->modify_column('channel_images', $fields);
			}
    	}

		// Upgrade The Module
		$this->EE->db->set('module_version', $this->version);
		$this->EE->db->where('module_name', ucfirst($this->module_name));
		$this->EE->db->update('exp_modules');

		return TRUE;
	}

} // END CLASS

/* End of file upd.channel_images.php */
/* Location: ./system/expressionengine/third_party/channel_images/upd.channel_images.php */