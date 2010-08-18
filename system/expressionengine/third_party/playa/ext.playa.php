<?php if (! defined('APP_VER')) exit('No direct script access allowed');


if (! defined('PLAYA_VER'))
{
	// get the version from config.php
	require PATH_THIRD.'playa/config.php';
	define('PLAYA_VER',  $config['version']);
}


/**
 * Playa Extension Class for ExpressionEngine 2
 *
 * @package   Playa
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2010 Pixel & Tonic, LLC
 */
class Playa_ext {

	var $name = 'Playa';
	var $version = PLAYA_VER;
	var $description = 'The proverbial multiple relationships field';
	var $settings_exist = 'n';
	var $docs_url = 'http://pixelandtonic.com/playa/docs';

	/**
	 * Extension Constructor
	 */
	function Playa_ext()
	{
		$this->EE =& get_instance();

		// -------------------------------------------
		//  Prepare Cache
		// -------------------------------------------

		if (! isset($this->EE->session->cache['playa']))
		{
			$this->EE->session->cache['playa'] = array();
		}
		$this->cache =& $this->EE->session->cache['playa'];
	}

	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 */
	function activate_extension()
	{
		$this->EE->db->insert('extensions', array(
			'class'    => 'Playa_ext',
			'hook'     => 'channel_entries_tagdata',
			'method'   => 'channel_entries_tagdata',
			'priority' => 9,
			'version'  => $this->version,
			'enabled'  => 'y'
		));
	}

	/**
	 * Update Extension
	 */
	function update_extension($current = FALSE)
	{
		if (! $current || $current == $this->version)
		{
			return FALSE;
		}

		if (version_compare($current, '3.0.4', '<'))
		{
			$this->EE->db->where('class', 'Playa_ext');
			$this->EE->db->where('hook', 'channel_entries_tagdata');
			$this->EE->db->update('extensions', array('priority' => 9));
		}

		$this->EE->db->where('class', 'Playa_ext');
		$this->EE->db->update('extensions', array('version' => $this->version));
	}

	/**
	 * Disable Extension
	 */
	function disable_extension()
	{
		$this->EE->db->query('DELETE FROM exp_extensions WHERE class = "Playa_ext"');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Fields
	 */
	private function _get_fields()
	{
		if (! isset($this->cache['fields']))
		{
			$this->EE->db->select('field_id, field_name, field_settings');
			$this->EE->db->where('field_type', 'playa');
			$query = $this->EE->db->get('channel_fields');

			$fields = $query->result_array();

			foreach ($fields as &$field)
			{
				$field['field_settings'] = unserialize(base64_decode($field['field_settings']));
			}

			$this->cache['fields'] = $fields;
		}

		return $this->cache['fields'];
	}

	// --------------------------------------------------------------------

	/**
	 * channel_entries_tagdata hook
	 */
	function channel_entries_tagdata($tagdata, $row, &$Channel)
	{
		// has this hook already been called?
		if ($this->EE->extensions->last_call)
		{
			$tagdata = $this->EE->extensions->last_call;
		}

		// save the Channel ref
		$this->cache['Channel'] = &$Channel;

		// iterate through each Playa field
		foreach($this->_get_fields() as $field)
		{
			// is the tag even being used here?
			if (strpos($tagdata, LD.$field['field_name']) !== FALSE)
			{
				$data = $row['field_id_'.$field['field_id']];
				$offset = 0;

				while (preg_match('/'.LD.$field['field_name'].'(:(\w+))?(\s+.*)?'.RD.'/sU', $tagdata, $matches, PREG_OFFSET_CAPTURE, $offset))
				{
					$tag_pos = $matches[0][1];
					$tag_len = strlen($matches[0][0]);
					$tagdata_pos = $tag_pos + $tag_len;
					$endtag = LD.'/'.$field['field_name'].(isset($matches[1][0]) ? $matches[1][0] : '').RD;
					$endtag_len = strlen($endtag);
					$endtag_pos = strpos($tagdata, $endtag, $tagdata_pos);
					$tag_func = (isset($matches[2][0]) && $matches[2][0]) ? 'replace_'.$matches[2][0] : '';

					// get the params
					$params = array();
					if (isset($matches[3][0]) && $matches[3][0] && preg_match_all('/\s+([\w-:]+)\s*=\s*([\'\"])([^\2]*)\2/sU', $matches[3][0], $param_matches))
					{
						for ($j = 0; $j < count($param_matches[0]); $j++)
						{
							$params[$param_matches[1][$j]] = $param_matches[3][$j];
						}
					}

					// is this a tag pair?
					$field_tagdata = ($endtag_pos !== FALSE)
					  ?  substr($tagdata, $tagdata_pos, $endtag_pos - $tagdata_pos)
					  :  '';

					// -------------------------------------------
					//  Call the tag's method
					// -------------------------------------------

					if (! class_exists('Playa_ft'))
					{
						include_once PATH_THIRD.'playa/ft.playa.php';
					}

					$Playa_ft = new Playa_ft();
					$Playa_ft->field_id = $field['field_id'];
					$Playa_ft->field_name = $field['field_name'];
					$Playa_ft->entry_id = $row['entry_id'];
					$Playa_ft->settings = array_merge($row, $field['field_settings']);

					if (! $tag_func || ! method_exists($Playa_ft, $tag_func))
					{
						$tag_func = 'replace_tag';
					}

					$new_tagdata = $Playa_ft->$tag_func($data, $params, $field_tagdata);

					// update tagdata
					$tagdata = substr($tagdata, 0, $tag_pos)
					         . $new_tagdata
					         . substr($tagdata, ($endtag_pos !== FALSE ? $endtag_pos+$endtag_len : $tagdata_pos));

					// update offset
					$offset = $tag_pos;
				}
			}
		}

		return $tagdata;
	}
}
