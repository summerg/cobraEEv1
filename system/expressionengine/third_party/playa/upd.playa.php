<?php if (! defined('BASEPATH')) exit('Invalid file request');


if (! defined('PLAYA_VER'))
{
	// get the version from config.php
	if (defined('PATH_THIRD'))
	{
		require PATH_THIRD.'playa/config.php';
	}
	else
	{
		// this must be the EE installer app
		require EE_APPPATH.'third_party/playa/config.php';
	}

	define('PLAYA_VER',  $config['version']);
}


/**
 * Playa Update Class
 *
 * @package   Playa
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2010 Pixel & Tonic, LLC
 */
class Playa_upd {

	var $version = PLAYA_VER;

	/**
	 * Constructor
	 */
	function Playa_upd()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Install
	 */
	function install()
	{
		$this->EE->load->dbforge();

		$this->EE->db->insert('modules', array(
			'module_name'        => 'Playa',
			'module_version'     => $this->version,
			'has_cp_backend'     => 'n',
			'has_publish_fields' => 'n'
		));

		$this->EE->db->insert('actions', array(
			'class'  => 'Playa_mcp',
			'method' => 'filter_entries'
		));

		return TRUE;
	}

	/**
	 * Uninstall
	 */
	function uninstall()
	{
		$this->EE->db->query('DELETE FROM exp_modules WHERE module_name = "Playa"');
		$this->EE->db->query('DELETE FROM exp_actions WHERE class = "Playa_mcp"');

		return TRUE;
	}

}
