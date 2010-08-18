<?php if (! defined('BASEPATH')) exit('No direct script access allowed');


if (! defined('PLAYA_VER'))
{
	// get the version from config.php
	require PATH_THIRD.'playa/config.php';
	define('PLAYA_VER',  $config['version']);
}


/**
 * Playa Fieldtype Class for ExpressionEngine 2
 *
 * @package   Playa
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2010 Pixel & Tonic, LLC
 */
class Playa_ft extends EE_Fieldtype {

	var $info = array(
		'name'    => 'Playa',
		'version' => PLAYA_VER
	);

	var $has_array_data = TRUE;

	/**
	 * Fieldtype Constructor
	 */
	function Playa_ft()
	{
		parent::EE_Fieldtype();

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
	 * Install
	 */
	function install()
	{
		// -------------------------------------------
		//  EE1 Conversion
		// -------------------------------------------

		if (! class_exists('FF2EE2')) require 'includes/ff2ee2/ff2ee2.php';

		$converter = new FF2EE2('playa', array(&$this, '_update_field_settings'));
		return $converter->global_settings;
	}

	/**
	 * Update Field Settings
	 */
	function _update_field_settings($field_settings, $field)
	{
		if (isset($field_settings['show_filters']))
		{
			if ($field_settings['show_filters'] == 'n') $field_settings['ui_mode'] = 'drop';
			unset($field_settings['show_filters']);
		}

		if (isset($field_settings['blogs']))
		{
			$field_settings['channels'] = $field_settings['blogs'];
			unset($field_settings['blogs']);
		}

		return $field_settings;
	}

	// --------------------------------------------------------------------

	/**
	 * Theme URL
	 */
	private function _theme_url()
	{
		if (! isset($this->cache['theme_url']))
		{
			$theme_folder_url = $this->EE->config->item('theme_folder_url');
			if (substr($theme_folder_url, -1) != '/') $theme_folder_url .= '/';
			$this->cache['theme_url'] = $theme_folder_url.'playa/';
		}

		return $this->cache['theme_url'];
	}

	/**
	 * Include Theme CSS
	 */
	private function _include_theme_css($file)
	{
		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->_theme_url().$file.'" />');
	}

	/**
	 * Include Theme JS
	 */
	private function _include_theme_js($file)
	{
		$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$this->_theme_url().$file.'"></script>');
	}

	// --------------------------------------------------------------------

	/** 
	 * Insert CSS
	 */
	private function _insert_css($css)
	{
		$this->EE->cp->add_to_head('<style type="text/css">'.$css.'</style>');
	}

	/**
	 * Insert JS
	 */
	private function _insert_js($js)
	{
		$this->EE->cp->add_to_foot('<script type="text/javascript">'.$js.'</script>');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Relationship IDs
	 */
	private function _get_rel_ids($data = '', $ignore_closed = FALSE)
	{
		$rel_ids = array();

		$lines = array_filter(preg_split("/[\r\n]+/", $data));
		if (count($lines))
		{
			foreach($lines as $line)
			{
				if (preg_match('/\[(\!)?(\d+)\]/', $line, $matches))
				{
					if (! $ignore_closed OR ! $matches[1])
					{
						$rel_id = $matches[2];
					}
					else
					{
						continue;
					}
				}
				else
				{
					$rel_id = $line;
				}

				if (is_numeric($rel_id))
				{
					$rel_ids[] = $rel_id;
				}
			}
		}

		return $rel_ids;
	}

	// --------------------------------------------------------------------

	/**
	 * Default Global Settings
	 */
	private function _default_global_settings()
	{
		return array(
			'license_key' => ''
		);
	}

	/**
	 * Default Field Settings
	 */
	private function _default_field_settings()
	{
		return array(
			'ui_mode'  => 'drop_filters',
			'channels' => array(),
			'cats'     => array(),
			'authors'  => array(),
			'statuses' => array(),
			'limit'    => '0',
			'limitby'  => '',
			'orderby'  => 'title',
			'sort'     => 'ASC'	
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Prepare Params
	 */
	private function _prep_params(&$params)
	{
		$params = array_merge(array(
			'author_id'           => '',
			'backspace'           => '0',
			'category'            => '',
			'category_group'      => '',
			'delimiter'           => '|',
			'entry_id'            => '',
			'fixed_order'         => '',
			'group_id'            => '',
			'limit'               => '100',
			'offset'              => '0',
			'orderby'             => '',
			'show_expired'        => 'no',
			'show_future_entries' => 'no',
			'sort'                => '',
			'status'              => 'not closed',
			'url_title'           => '',
			'weblog'              => ''
		), $params);
	}

	// --------------------------------------------------------------------

	/**
	 * Parameter => SQL
	 */
	function param2sql($param, &$not = NULL, $use_not = TRUE)
	{
		if (is_string($param))
		{
			if (strlen($param) > 4 && strtolower(substr($param, 0, 4)) == 'not ')
			{
				$not = TRUE;
				$param = substr($param, 4);
			}
			else
			{
				$not = FALSE;
			}

			$param = explode('|', $param);
		}
		else if ($not === NULL)
		{
			$not = FALSE;
		}

		if (count($param) == 1)
		{
			return ($not && $use_not ? '<>' : '=').' "'.$param[0].'"';
		}

		return ($not && $use_not ? 'NOT ' : '').'IN ("'.implode('","', $param).'")';
	}

	// --------------------------------------------------------------------

	/**
	 * Entries Query
	 */
	function entries_query($params, $add_to_sql = array())
	{
		// -------------------------------------------
		//  Param name mapping
		// -------------------------------------------

		$param_mapping = array(
			'author'      => 'author_id',
			'category_id' => 'category',
			'weblog'      => 'channel',
			'weblog_id'   => 'channel_id'
		);

		foreach($param_mapping as $old_name => $new_name)
		{
			if (isset($params[$old_name]) AND (! isset($params[$new_name]) || ! $params[$new_name]))
			{
				$params[$new_name] = $params[$old_name];
				unset($params[$old_name]);
			}
		}

		// -------------------------------------------
		//  Prepare the SQL
		// -------------------------------------------

		$sql = 'SELECT '.(isset($params['count']) ? 'COUNT(ct.entry_id) count' : 'ct.entry_id, ct.title')
		     . (isset($add_to_sql['select']) ? ', '.$add_to_sql['select'] : '')
		     . ' FROM exp_channel_titles ct'.(isset($add_to_sql['from']) ? ', '.$add_to_sql['from'] : '');

		$where = array();

			// -------------------------------------------
			//  Author
			// -------------------------------------------

			if (isset($params['author_id']) && $params['author_id'])
			{
				$where[] = 'ct.author_id '.Playa_ft::param2sql($params['author_id']);
			}

			// -------------------------------------------
			//  Author Group
			// -------------------------------------------

			if (isset($params['group_id']) && $params['group_id'])
			{
				// get filtered list of author ids
				$not = NULL;
				$query = $this->EE->db->query('SELECT member_id FROM exp_members
				                               WHERE group_id '.Playa_ft::param2sql($params['group_id'], $not, FALSE));

				if (! $query->num_rows())
				{
					if (! $not) return FALSE;
				}
				else
				{
					$author_ids = array();
					foreach($query->result_array() as $row)
					{
						$author_ids[] = $row['member_id'];
					}

					$where[] = 'ct.author_id '.Playa_ft::param2sql($author_ids, $not);
				}
			}

			// -------------------------------------------
			//  Category
			// -------------------------------------------

			if (isset($params['category']) && $params['category'])
			{
				// get filtered list of entry ids
				$not = NULL;
				$query = $this->EE->db->query('SELECT entry_id FROM exp_category_posts
				                               WHERE cat_id '.Playa_ft::param2sql($params['category'], $not, FALSE).'
				                               GROUP BY entry_id');

				if (! $query->num_rows())
				{
					if (! $not) return FALSE;
				}
				else
				{
					$entry_ids = array();
					foreach($query->result_array() as $row)
					{
						$entry_ids[] = $row['entry_id'];
					}

					$where[] = 'ct.entry_id '.Playa_ft::param2sql($entry_ids, $not);
				}
			}

			// -------------------------------------------
			//  Category Group
			// -------------------------------------------

			if (isset($params['category_group']) && $params['category_group'])
			{
				// get filtered list of entry ids
				$not = NULL;
				$query = $this->EE->db->query('SELECT cp.entry_id FROM exp_category_posts cp, exp_categories c
				                               WHERE cp.cat_id = c.cat_id
				                                     AND c.group_id '.Playa_ft::param2sql($params['category_group'], $not, FALSE).'
				                               GROUP BY entry_id');

				if (! $query->num_rows())
				{
					if (! $not) return FALSE;
				}
				else
				{
					$entry_ids = array();
					foreach($query->result_array() as $row)
					{
						$entry_ids[] = $row['entry_id'];
					}

					$where[] = 'ct.entry_id '.Playa_ft::param2sql($entry_ids, $not);
				}
			}

			// -------------------------------------------
			//  Dates
			// -------------------------------------------

			$timestamp = (isset($this->EE->TMPL) && $this->EE->TMPL->cache_timestamp) ? $this->EE->localize->set_gmt($this->EE->TMPL->cache_timestamp) : $this->EE->localize->now;

			if (isset($params['show_future_entries']) && $params['show_future_entries'] != 'yes')
			{
				$where[] = 'ct.entry_date < '.$timestamp;
			}

			if (isset($params['show_expired']) && $params['show_expired'] != 'yes')
			{
				$where[] = '(ct.expiration_date = 0 OR ct.expiration_date > '.$timestamp.')';
			}

			// -------------------------------------------
			//  Entry ID
			// -------------------------------------------

			if (isset($params['entry_id']) && $params['entry_id'])
			{
				$where[] = 'ct.entry_id '.Playa_ft::param2sql($params['entry_id']);
			}

			// -------------------------------------------
			//  Status
			// -------------------------------------------

			if (isset($params['status']) && $params['status'])
			{
				$where[] = 'ct.status '.Playa_ft::param2sql($params['status']);
			}

			// -------------------------------------------
			//  URL Title
			// -------------------------------------------

			if (isset($params['url_title']) && $params['url_title'])
			{
				$where[] = 'ct.url_title '.Playa_ft::param2sql($params['url_title']);
			}

			// -------------------------------------------
			//  Channel
			// -------------------------------------------

			if (isset($params['channel']) && $params['channel'])
			{
				// get channel IDs
				$not = NULL;
				$query = $this->EE->db->query('SELECT channel_id FROM exp_channels
				                               WHERE channel_name '.Playa_ft::param2sql($params['channel'], $not, FALSE));

				if (! $query->num_rows())
				{
					if (! $not) return FALSE;
				}
				else
				{
					$channel_ids = array();
					foreach($query->result_array() as $row)
					{
						$channel_ids[] = $row['channel_id'];
					}

					$where[] = 'ct.channel_id '.Playa_ft::param2sql($channel_ids, $not);
				}
			}

			// -------------------------------------------
			//  Channel ID
			// -------------------------------------------

			if (isset($params['channel_id']) && $params['channel_id'])
			{
				$where[] = 'ct.channel_id '.Playa_ft::param2sql($params['channel_id']);
			}

		// -------------------------------------------
		//  Add WHERE to SQL
		// -------------------------------------------

		if ($where || isset($add_to_sql['where']))
		{
			$sql .= ' WHERE '
			      . implode(' AND ', $where)
			      . (isset($add_to_sql['where']) ? ($where ? ' AND ' : '') . $add_to_sql['where'] : '');
		}

		// -------------------------------------------
		//  Orberby + Sort
		// -------------------------------------------

		if (isset($params['orderby']) && $params['orderby'])
		{
			$orderbys = (is_array($params['orderby'])) ? $params['orderby'] : explode('|', $params['orderby']);
			$sorts    = (isset($params['sort']) && $params['sort']) ? (is_array($params['sort']) ? $params['sort'] : explode('|', $params['sort'])) : array();

			$all_orderbys = array();
			foreach($orderbys as $i => $attr)
			{
				$sort = (isset($sorts[$i]) AND strtoupper($sorts[$i]) == 'DESC') ? 'DESC' : 'ASC';
				$all_orderbys[] = 'ct.'.$attr.' '.$sort;
			}

			$sql .=  ' ORDER BY '.implode(', ', $all_orderbys);
		}

		// -------------------------------------------
		//  Offset and Limit
		// -------------------------------------------

		if ((isset($params['limit']) && $params['limit']) || (isset($params['offset']) && $params['offset']))
		{
			$offset = (isset($params['offset']) && $params['offset']) ? $params['offset'] . ', ' : '';
			$limit  = (isset($params['limit']) && $params['limit']) ? $params['limit'] : 100;

			$sql .= ' LIMIT ' . $offset . $limit;
		}

		// -------------------------------------------
		//  Run and return
		// -------------------------------------------

		$query = $this->EE->db->query($sql);

		return isset($params['count']) ? $query->row('count') : ($query->num_rows() ? $query->result_array() : FALSE);
	}

	// --------------------------------------------------------------------

	/**
	 * Select Options
	 */
	private function _select_options($value, $options)
	{
		$r = '';
		foreach ($options as $option_value => $option_line)
		{
			if (is_array($option_line))
			{
				$r .= '<optgroup label="'.$option_value.'">'."\n"
				    .   $this->_select_options($value, $option_line)
				    . '</optgroup>'."\n";
			}
			else
			{
				$selected = is_array($value) ? in_array($option_value, $value) : ($option_value == $value);
				$r .= '<option value="'.$option_value.'"'.($selected ? ' selected="selected"' : '').'>'.$option_line.'</option>';
			}
		}
		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Display Global Settings
	 */
	function display_global_settings()
	{
		// $this->settings is just the global settings here
		$global_settings = array_merge($this->_default_global_settings(), $this->settings);

		// load the language file
		$this->EE->lang->loadfile('playa');

		// load the table lib
		$this->EE->load->library('table');

		// use the default template known as
		// $cp_pad_table_template in the views
		$this->EE->table->set_template(array(
			'table_open'    => '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">',
			'row_start'     => '<tr class="even">',
			'row_alt_start' => '<tr class="odd">'
		));

		$this->EE->table->set_heading(array('data' => lang('preference'), 'style' => 'width: 50%'), lang('setting'));

		$this->EE->table->add_row(
			lang('license_key', 'license_key'),
			form_input('license_key', $global_settings['license_key'], 'id="license_key" size="40"')
		);

		return $this->EE->table->generate();
	}

	/**
	 * Save Global Settings
	 */
	function save_global_settings()
	{
		$new_global_settings = $this->_default_global_settings();
		if (isset($_POST['license_key'])) $new_global_settings['license_key'] = $_POST['license_key'];

		return $new_global_settings;
	}

	// --------------------------------------------------------------------

	/**
	 * Update cell settings
	 * 
	 * Called from display_cell_settings() and display_cell()
	 */
	private function _update_cell_settings(&$cell_settings)
	{
		if (isset($cell_settings['multi']))
		{
			if ($cell_settings['multi'] == 'y') $cell_settings['ui_mode'] = 'multi';
			unset($cell_settings['multi']);
		}

		if (isset($field_settings['blogs']))
		{
			$field_settings['channels'] = $field_settings['blogs'];
			unset($field_settings['blogs']);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Display Field Settings
	 */
	function display_settings($data)
	{
		$rows = $this->_field_settings($data);

		foreach ($rows as $row)
		{
			$this->EE->table->add_row($row[0], $row[1]);
		}
	}

	/**
	 * Display Cell Settings
	 */
	function display_cell_settings($data)
	{
		$this->_update_cell_settings($data);

		return $this->_field_settings($data);
	}

	/**
	 * Field Settings
	 */
	private function _field_settings($data)
	{
		// load the language file
		$this->EE->lang->loadfile('playa');

		$data = array_merge($this->_default_field_settings(), $data);

		return array(
			// UI Mode
			array(
				lang('ui_mode', 'playa_uimode'),
				form_dropdown('playa[ui_mode]',
					array(
						'drop_filters' => lang('ui_mode_drop_filters'),
						'drop'         => lang('ui_mode_drop'),
						'multi'        => lang('ui_mode_multi'),
						'select'       => lang('ui_mode_select')
					),
					$data['ui_mode'],
					'id="playa_uimode"'
				)
			),

			// Channels
			array(
				lang('channels', 'playa_channels'),
				$this->_channels_select($data['channels'])
			),

			// Categories
			array(
				lang('cats', 'playa_cats'),
				$this->_cats_select($data['cats'])
			),

			// Authors
			array(
				lang('authors', 'playa_authors'),
				$this->_authors_select($data['authors'])
			),

			// Statuses
			array(
				lang('statuses', 'playa_statuses'),
				$this->_statuses_select($data['statuses'])
			),

			// Limit
			array(
				lang('limit_entries_to', 'playa_limit'),
				'<select name="playa[limit]" onchange="this.nextSibling.style.visibility=this.value==\'0\'?\'hidden\':\'visible\';">'
				.   $this->_select_options($data['limit'], array('0'=>lang('all'), '25'=>'25', '50'=>'50', '100'=>'100', '250'=>'250', '500'=>'500', '1000'=>'1000'))
				. '</select>'
				. '<select name="playa[limitby]" style="margin-left: 4px;'.($data['limitby'] ? '' : ' visibility:hidden;').'">'
				.   $this->_select_options($data['limitby'], array('newest'=>lang('newest_entries'), 'oldest'=>lang('oldest_entries')))
				. '</select>'
			),

			// Order
			array(
				lang('order_entries_by', 'playa_order'),
				'<select name="playa[orderby]">'
				.   $this->_select_options($data['orderby'], array('title'=>lang('entry_title'), 'entry_date'=>lang('entry_date')))
				. '</select>'
				. ' in '
				. '<select name="playa[sort]">'
				.   $this->_select_options($data['sort'], array('ASC'=>lang('asc_order'), 'DESC'=>lang('desc_order')))
				. '</select>'
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Channels Multi-select
	 */
	private function _channels_select($selected_channels)
	{
		$msm = ($this->EE->config->item('multiple_sites_enabled') == 'y');
		$site_id = $this->EE->config->item('site_id');

		$channels = $this->EE->db->query('SELECT c.channel_id AS `id`, c.channel_title AS `title`, s.site_label AS `group`
		                                  FROM exp_channels c, exp_sites s
		                                  WHERE s.site_id = c.site_id
		                                        '.($msm ? '' : 'AND s.site_id = "'.$site_id.'"').'
		                                  ORDER BY s.site_label, c.channel_title ASC');

		if ($channels->num_rows())
		{
			return $this->_field_settings_select('channels', $channels->result_array(), $selected_channels, TRUE, $msm);
		}

		return lang('no_channels');
	}

	/**
	 * Categories Select
	 */
	private function _cats_select($selected_cats)
	{
		$msm = ($this->EE->config->item('multiple_sites_enabled') == 'y');
		$site_id = $this->EE->config->item('site_id');

		$cats = $this->EE->db->query('SELECT c.cat_id AS `id`, c.cat_name AS `title`, c.parent_id, cg.group_name AS `group`
		                              FROM exp_categories c, exp_category_groups cg
		                              WHERE c.group_id = cg.group_id
		                                    '.($msm ? '' : 'AND c.site_id = "'.$site_id.'"').'
		                              ORDER BY cg.group_name, c.cat_order');

		if ($cats->num_rows())
		{
			// group cats by parent_id
			$cats_by_parent = $this->_cats_by_parent($cats->result_array());

			// flatten into sorted and indented options
			$this->_cats_select_options($cats_options, $cats_by_parent);

			return $this->_field_settings_select('cats', $cats_options, $selected_cats);
		}

		return lang('no_cats');
	}

		/**
		 * Group categories by parent_id
		 */
		private function _cats_by_parent($cats)
		{
			$cats_by_parent = array();

			foreach($cats as $cat)
			{
				if (! isset($cats_by_parent[$cat['parent_id']]))
				{
					$cats_by_parent[$cat['parent_id']] = array();
				}

				$cats_by_parent[$cat['parent_id']][] = $cat;
			}

			return $cats_by_parent;
		}

		/**
		 * Category Options
		 */
		private function _cats_select_options(&$cats=array(), &$cats_by_parent, $parent_id='0', $indent='')
		{
			foreach($cats_by_parent[$parent_id] as $cat)
			{
				$cat['title'] = $indent.$cat['title'];
				$cats[] = $cat;
				if (isset($cats_by_parent[$cat['id']]))
				{
					$this->_cats_select_options($cats, $cats_by_parent, (string)$cat['id'], $indent.NBS.NBS.NBS.NBS);
				}
			}
		}

	/**
	 * Authors Select
	 */
	private function _authors_select($selected_authors)
	{
		$msm = ($this->EE->config->item('multiple_sites_enabled') == 'y');
		$site_id = $this->EE->config->item('site_id');

		$authors = $this->EE->db->query('SELECT m.member_id AS `id`, m.screen_name AS `title`, mg.group_title AS `group`
		                                 FROM exp_members m, exp_member_groups mg
		                                 WHERE m.group_id = mg.group_id
		                                       AND mg.can_access_publish = "y"
		                                       '.($msm ? '' : 'AND mg.site_id = "'.$site_id.'"').'
		                                 GROUP BY m.member_id
		                                 ORDER BY mg.group_title, m.screen_name');

		return $this->_field_settings_select('authors', $authors->result_array(), $selected_authors);
	}

	/**
	 * Statuses Select
	 */
	private function _statuses_select($selected_statuses)
	{
		$msm = ($this->EE->config->item('multiple_sites_enabled') == 'y');
		$site_id = $this->EE->config->item('site_id');

		$statuses = $this->EE->db->query('SELECT s.status AS `id`, s.status AS `title`, sg.group_name AS `group`
		                                  FROM exp_statuses s, exp_status_groups sg
		                                  WHERE s.group_id = sg.group_id
		                                        AND s.status NOT IN ("open", "closed")
		                                        '.($msm ? '' : 'AND s.site_id = "'.$site_id.'"').'
		                                  ORDER BY sg.group_name, s.status_order');

		$rows = array_merge(array(
			array('id' => 'open', 'title' => 'Open'),
			array('id' => 'closed', 'title' => 'Closed')
		), $statuses->result_array());

		return $this->_field_settings_select('statuses', $rows, $selected_statuses);
	}

	/**
	 * Field Settings Select
	 */
	private function _field_settings_select($name, $rows, $selected_ids, $multi = TRUE, $optgroups = TRUE)
	{
		$options = $this->_field_settings_select_options($rows, $selected_ids, $optgroups, $row_count);

		return '<select name="playa['.$name.'][]" multiple="multiple" class="multiselect" size="'.($row_count < 10 ? $row_count : 10).'" style="width: 230px">'
		       . $options
		       . '</select>';
	}

	/**
	 * Select Options
	 */
	private function _field_settings_select_options($rows, $selected_ids = array(), $optgroups = TRUE, &$row_count = 0)
	{
		if ($optgroups) $optgroup = '';
		$options = '<option value="any"'.($selected_ids ? '' : ' selected="selected"').'>-- Any --</option>';
		$row_count = 1;

		foreach($rows as $row)
		{
			if ($optgroups && isset($row['group']) && $row['group'] != $optgroup)
			{
				if ($optgroup) $options .= '</optgroup>';
				$options .= '<optgroup label="'.$row['group'].'">';
				$optgroup = $row['group'];
				$row_count++;
			}

			$selected = in_array($row['id'], $selected_ids) ? 1 : 0;
			$options .= '<option value="'.$row['id'].'"'.($selected ? ' selected="selected"' : '').'>'.$row['title'].'</option>';
			$row_count++;
		}

		if ($optgroups && $optgroup) $options .= '</optgroup>';

		return $options;
	}

	// --------------------------------------------------------------------

	/**
	 * Save Field Settings
	 */
	function save_settings($data)
	{
		$settings = $this->EE->input->post('playa');

		$this->_validate_settings($settings);

		// cross the T's
		$settings['field_fmt'] = 'none';
		$settings['field_show_fmt'] = 'n';
		$settings['field_type'] = 'playa';

		return $settings;
	}

	/**
	 * Save Cell Settings
	 */
	function save_cell_settings($settings)
	{
		$settings = $settings['playa'];
		$this->_validate_settings($settings);

		return $settings;
	}

	/**
	 * Validate Field Settings
	 */
	private function _validate_settings(&$settings)
	{
		// remove any filters that have "Any" selected
		$filters = array('channels', 'cats', 'authors', 'statuses');

		foreach($filters as $filter)
		{
			if (isset($settings[$filter]) && in_array('any', $settings[$filter]))
			{
				unset($settings[$filter]);
			}
		}

		// remove Limit if set to "All"
		if (isset($settings['limit']) && ! $settings['limit'])
		{
			unset($settings['limit']);
		}

		// remove Limit By if there's no Limit
		if (isset($settings['limitby']) && ! isset($settings['limit']))
		{
			unset($settings['limitby']);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Display Field
	 */
	function display_field($data)
	{
		$all_settings = array_merge($this->_default_global_settings(), $this->_default_field_settings(), $this->settings);

		if (is_array($data) && ! isset($data['selections']))
		{
			$data = '';
		}

		// -------------------------------------------
		//  Error?
		// -------------------------------------------

		if (is_array($data))
		{
			$old_data = $data['old'];
			$selected_entry_ids = array_merge(array_filter($data['selections']));
		}
		else
		{
			$old_data = $data;

			// -------------------------------------------
			//  Get selected entry IDs
			// -------------------------------------------

			$selected_entry_ids = array();

			$rel_ids = $this->_get_rel_ids($data);

			if (count($rel_ids))
			{
				$rels = $this->EE->db->query('SELECT rel_id, rel_child_id
				                              FROM exp_relationships
				                              WHERE rel_id '.$this->param2sql($rel_ids));

				if ($rels->num_rows)
				{
					$selected_entry_ids_by_rel_id = array();

					foreach($rels->result_array() as $rel)
					{
						$selected_entry_ids_by_rel_id[$rel['rel_id']] = $rel['rel_child_id'];
					}

					foreach($rel_ids as $rel_id)
					{
						if (array_key_exists($rel_id, $selected_entry_ids_by_rel_id))
						{
							$selected_entry_ids[] = $selected_entry_ids_by_rel_id[$rel_id];
						}
					}
				}
			}
		}

		// -------------------------------------------
		//  Get the entries!
		// -------------------------------------------

		$params = array(
			'channel_id' => $all_settings['channels'],
			'category'   => $all_settings['cats'],
			'author_id'  => $all_settings['authors'],
			'status'     => $all_settings['statuses']
		);

		if ($all_settings['limit'])
		{
			$params['orderby'] = 'entry_date';
			$params['sort'] = $all_settings['limitby'] == 'newest' ? 'DESC' : 'ASC';
			$params['limit'] = $all_settings['limit'];
		}
		else
		{
			$params['orderby'] = $all_settings['orderby'];
			$params['sort'] = $all_settings['sort'];
		}

		// run the query
		$entries = $this->entries_query($params, array('select' => 'ct.entry_date, ct.status'));

		// no entries?
		if (! $entries)
		{
			$this->EE->lang->loadfile('content');
			return lang('no_related_entries');
		}

		// if we used ORDER BY for initial limiting,
		// manually sort the entries here
		if ($all_settings['limitby'])
		{
			usort($entries, create_function('$a, $b',
				'return '.($all_settings['sort'] == 'DESC' ? '-1 * ' : '').'strcmp($a[\''.$all_settings['orderby'].'\'], $b[\''.$all_settings['orderby'].'\']);'
			));
		}

		// -------------------------------------------
		//  Pass them on to the UI mode
		// -------------------------------------------

		// is this a cell?
		$cell = isset($this->cell_name) ? TRUE : FALSE;

		// use the appropriate field name
		$field_name = $cell ? $this->cell_name : $this->field_name;

		switch($all_settings['ui_mode'])
		{
			case 'drop_filters': case 'drop': return $this->_display_field_droppanes($data, $cell, $field_name, $all_settings, $entries, $old_data, $selected_entry_ids);
			case 'multi': case 'select':      return $this->_display_field_select($data, $cell, $field_name, $all_settings, $entries, $old_data, $selected_entry_ids);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Display Cell
	 */
	function display_cell($data)
	{
		return $this->display_field($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Display Field - Drop panes
	 */
	private function _display_field_droppanes($data, $cell, $field_name, $field_settings, $entries, $old_data, $selected_entry_ids)
	{
		// -------------------------------------------
		//  Include Dependencies
		// -------------------------------------------

		if (! isset($this->cache['included_dependencies']))
		{
			$css = $this->_status_css('open', '093')
			     . $this->_status_css('closed', '900');

			$statuses = $this->EE->db->query('SELECT status, highlight FROM exp_statuses
			                                  WHERE status NOT IN ("open", "closed")');
			foreach ($statuses->result_array() as $status)
			{
				$css .= $this->_status_css(str_replace(' ', '_', $status['status']), $status['highlight']);
			}

			$this->_insert_css($css);
			$this->_include_theme_css('styles/playa.css');
			$this->_include_theme_js('scripts/playa.js');

			$query = $this->EE->db->query('SELECT action_id FROM exp_actions WHERE class = "Playa_mcp" AND method = "filter_entries"');
			$this->cache['filter_action_id'] = $query->num_rows() ? $query->row('action_id') : FALSE;

			if ($this->cache['filter_action_id'])
			{
				$this->EE->lang->loadfile('playa');

				if (($site_index = $this->EE->config->item('playa_site_index')) === FALSE) $site_index = $this->EE->functions->fetch_site_index(0, 0);
				$this->_insert_js('PlayaDropPanes.filterUrl = "'.$site_index.QUERY_MARKER.'ACT='.$this->cache['filter_action_id'].'";' . NL
				                  . 'PlayaDropPanes.lang = { is: "'.lang('is').'" };');
			}

			$this->cache['included_dependencies'] = TRUE;
		}


		$msm = ($this->EE->config->item('multiple_sites_enabled') == 'y');
		$site_id = $this->EE->config->item('site_id');

		$field_id = str_replace(array('[', ']'), array('_', ''), $field_name);

		$height = count($entries) <= 10 ? (count($entries) * 19 + 7) : 194;

		// -------------------------------------------
		//  Filter Bar
		// -------------------------------------------

		if ($field_settings['ui_mode'] == 'drop_filters' && $this->cache['filter_action_id'])
		{
			$height += 28;

			if (! $cell)
			{
				$json['fieldName'] = $field_name;
			}

			if (! $cell || ! isset($this->cache['cols'][$this->col_id]))
			{
				if ($field_settings['channels']) $json['defaults']['channel']  = implode('|', $field_settings['channels']);
				if ($field_settings['cats'])     $json['defaults']['category'] = implode('|', $field_settings['cats']);
				if ($field_settings['authors'])  $json['defaults']['author']   = implode('|', $field_settings['authors']);
				if ($field_settings['statuses']) $json['defaults']['status']   = implode('|', $field_settings['statuses']);

				$json['defaults']['limit']   = $field_settings['limit'];
				$json['defaults']['limitby'] = $field_settings['limitby'];
				$json['defaults']['orderby'] = $field_settings['orderby'];
				$json['defaults']['sort']    = $field_settings['sort'];

				$cat_groups = array();
				$status_groups = array();

				// -------------------------------------------
				//  Channels
				// -------------------------------------------

				$channels = $this->EE->db->query('SELECT c.channel_id AS `id`, c.channel_title AS `title`, c.cat_group, c.status_group, s.site_label AS `group`
				                                  FROM exp_channels c, exp_sites s
				                                  WHERE s.site_id = c.site_id
				                                        '.($msm ? '' : 'AND s.site_id = "'.$site_id.'"').'
				                                        '.($field_settings['channels'] ? 'AND c.channel_id '.$this->param2sql($field_settings['channels']) : '').'
				                                  ORDER BY s.site_label, c.channel_title ASC')->result_array();

				// remember channel's category groups and status group for later
				foreach($channels as &$channel)
				{
					if ($channel['cat_group'])    $cat_groups    = array_merge($cat_groups,    explode('|', $channel['cat_group']));
					if ($channel['status_group']) $status_groups = array_merge($status_groups, array($channel['status_group']));

					unset($channel['cat_group']);
					unset($channel['status_group']);
				}

				if (count($channels) > 1)
				{
					$json['filters']['channel'] = array(lang('channel'), $this->_field_settings_select_options($channels));
				}

				// -------------------------------------------
				//  Categories
				// -------------------------------------------

				if ($cat_groups)
				{
					$cats = $this->EE->db->query('SELECT c.cat_id AS `id`, c.cat_name AS `title`, c.parent_id, cg.group_name AS `group`
					                              FROM exp_categories c, exp_category_groups cg
					                              WHERE c.group_id = cg.group_id
					                                    AND cg.group_id '.$this->param2sql($cat_groups).'
					                              ORDER BY cg.group_name, c.cat_order');

					if ($cats->num_rows() > 1)
					{
						// group cats by parent_id
						$cats_by_parent = $this->_cats_by_parent($cats->result_array());

						// flatten into sorted and indented options
						$this->_cats_select_options($cats_options, $cats_by_parent);

						$json['filters']['category'] = array(lang('category'), $this->_field_settings_select_options($cats_options));
					}
				}

				// -------------------------------------------
				//  Authors
				// -------------------------------------------

				$authors = $this->EE->db->query('SELECT m.member_id AS `id`, m.screen_name AS `title`, mg.group_title AS `group`
				                                 FROM exp_members m, exp_member_groups mg
				                                 WHERE m.group_id = mg.group_id
				                                       AND mg.can_access_publish = "y"
				                                       '.($msm ? '' : 'AND mg.site_id = "'.$site_id.'"').'
				                                       '.($field_settings['authors'] ? 'AND m.member_id '.$this->param2sql($field_settings['authors']) : '').'
				                                 GROUP BY m.member_id
				                                 ORDER BY mg.group_title, m.screen_name');

				if ($authors->num_rows())
				{
					$json['filters']['author'] = array(lang('author'), $this->_field_settings_select_options($authors->result_array()));
				}

				// -------------------------------------------
				//  Statuses
				// -------------------------------------------

				$statuses = $this->EE->db->query('SELECT s.status AS `id`, s.status AS `title`, sg.group_name AS `group`
				                                  FROM exp_statuses s, exp_status_groups sg
				                                  WHERE s.group_id = sg.group_id
				                                        AND s.status NOT IN ("open", "closed")
				                                        AND s.group_id '.$this->param2sql($status_groups).'
				                                        '.($msm ? '' : 'AND s.site_id = "'.$site_id.'"').'
				                                  ORDER BY sg.group_name, s.status_order');

				$statuses = array_merge(array(
					array('id' => 'open', 'title' => 'Open'),
					array('id' => 'closed', 'title' => 'Closed')
				), $statuses->result_array());

				$json['filters']['status'] = array(lang('status'), $this->_field_settings_select_options($statuses));


				// add json lib if < PHP 5.2
				include_once 'includes/jsonwrapper/jsonwrapper.php';

				$json = json_encode($json);

				if ($cell)
				{
					if (! isset($this->cache['displayed_cell_droppanes']))
					{
						$this->_include_theme_js('scripts/matrix2.js');
						$this->cache['displayed_cell_droppanes'] = TRUE;
					}

					$this->_insert_js('PlayaColOpts.col_id_'.$this->col_id.' = '.$json.';');
				}

			}

			$filters_html = '<div class="filters">'
			              .   '<div class="filter search">'
			              .     '<a class="remove disabled" title="'.lang('remove_filter').'"></a>'
			              .     '<a class="add" title="'.lang('add_filter').'"></a>'
			              .     '<label><span><span>'.lang('keywords_label').'</span></span><input type="text" /></label>'
			              .     '<a class="erase" title="'.lang('erase_keywords').'"></a>'
			              .   '</div>'
			              . '</div>';

			// -------------------------------------------
			//  Limit
			// -------------------------------------------

			if (! $cell || ! isset($this->cache['cols'][$this->col_id]['total']))
			{
				// get the total possible entries
				$total = $this->entries_query(array(
					'count'      => TRUE,
					'channel_id' => $field_settings['channels'],
					'category'   => $field_settings['cats'],
					'author_id'  => $field_settings['authors'],
					'status'     => $field_settings['statuses']
				));

				if ($cell)
				{
					$this->cache['cols'][$this->col_id]['total'] = $total;
				}
			}
			else
			{
				$total = $this->cache['cols'][$this->col_id]['total'];
			}

			// is there even a point in showing this?
			if ($total > 25)
			{
				$limit_html = '<div class="limit">'.lang('showing')
				            . '<select>'
				            .   '<option value="">All</option>';

				foreach (array(25,50,100,250,500,1000) as $limit)
				{
					if ($limit < $total)
					{
						$selected = $field_settings['limit'] == $limit;
						$limit_html .= '<option'.($selected ? ' selected="selected"' : '').'>'.$limit.'</option>';
					}
					else
					{
						break;
					}
				}

				$limit_html .= '</select> '.lang('of').' '.$total.' '.lang('entries').'</div>';
			}
			else
			{
				$limit_html = '';
			}
		}
		else
		{
			$json = '';
			$filters_html = '';
			$limit_html = '';
		}

		// -------------------------------------------
		//  Insert the JS
		// -------------------------------------------

		if (! $cell)
		{
			$this->_insert_js('new PlayaDropPanes(jQuery("#'.$field_id.'")'.($json ? ', '.$json : '').');');
		}

		// -------------------------------------------
		//  Items list
		// -------------------------------------------

		$items_list = $this->items_list($field_id, $field_name, $entries, $selected_entry_ids);

		// -------------------------------------------
		//  Selections list
		//   - Since the selections aren't necessarily in the options list,
		//     we need to run an additional query here
		// -------------------------------------------

		$selections_list = '';

		if ($selected_entry_ids
			&& ($query = $this->EE->db->query('SELECT entry_id, title, status FROM exp_channel_titles
		                                       WHERE entry_id '.$this->param2sql($selected_entry_ids)))
			&& $query->num_rows()
		)
		{
			// create indexed list of entries
			$selected_entries = array();
			foreach ($query->result_array() as $entry)
			{
				$selected_entries[$entry['entry_id']] = $entry;
			}

			// add the selections
			foreach($selected_entry_ids as $entry_id)
			{
				$entry = $selected_entries[$entry_id];
				$item_id = $field_id.'-option-'.$entry['entry_id'];
				$status = str_replace(' ', '_', $entry['status']);

				$selections_list .= '<li class="pdp-selected" id="'.$item_id.'" unselectable="on">'
				                  .   '<a><span class="'.$status.'">&bull;</span>'.$entry['title'].'</a>'
				                  .   '<input type="hidden" name="'.$field_name.'[selections][]" value="'.$entry['entry_id'].'" />'
				                  . '</li>';
			}
		}

		// -------------------------------------------
		//  Prepare HTML
		// -------------------------------------------

		if ($cell)
		{
			$margins = '2px -1px';
		}
		else if (version_compare(APP_VER, '2.0.2', '<'))
		{
			$margins = '5px 9px 0 11px';
		}
		else
		{
			$margins = '5px 3px 0 -1px';
		}

		$r = '<input type="hidden" name="'.$field_name.'[old]" value="'.addslashes($old_data).'"/>' . NL
		   . '<input type="hidden" name="'.$field_name.'[selections][]" value=""/>' . NL
		   . '<div id="'.$field_id.'" class="playa-droppanes" style="margin: '.$margins.'">'
		   .   '<table cellspacing="0" cellpadding="0" border="0">'
		   .     '<tr>'
		   .       '<td class="pdp pdp-options" tabindex="0">'
		   .         '<div class="pdp-scrollpane" style="height: '.$height.'px">'
		   .           $filters_html
		   .           '<ul'.($limit_html ? ' style="min-height: '.($height-59).'px"' : '').'>'.$items_list.'</ul>'
		   .           $limit_html
		   .         '</div>'
		   .       '</td>'
		   .       '<td class="pdp-btns">'
		   .         '<a class="select disabled" title="Select entry"></a>'
		   .         '<a class="deselect disabled" title="Deselect entry"></a>'
		   .       '</td>'
		   .       '<td class="pdp pdp-selections" tabindex="0">'
		   .         '<div class="pdp-scrollpane" style="height: '.$height.'px">'
		   .           '<ul>'.$selections_list.'<li class="pdp-caboose"></li></ul>'
		   .         '</div>'
		   .       '</td>'
		   .     '</tr>'
		   .   '</table>'
		   . '</div>';

		return $r;
	}

	/**
	 * Category Filter Snippet
	 */
	private function _cats_f(&$cats=array(), &$cats_by_parent, $parent_id='0', $indent='')
	{
		foreach($cats_by_parent[$parent_id] as $cat_id => $cat)
		{
			$cats[$cat_id] = $indent.$cat['cat_name'];
			if (isset($cats_by_parent[$cat_id]))
			{
				$this->_cats_f($cats, $cats_by_parent, "$cat_id", $indent.'    ');
			}
		}
	}

	/**
	 * Status CSS Snippet
	 */
	private function _status_css($status, $highlight)
	{
		return '  .playa-droppanes a span.'.$status.' { color: #'.$highlight.' !important; }' . NL;
	}

	/**
	 * Items List
	 */
	function items_list($field_id, $field_name, $entries, $selected_entry_ids = array())
	{
		$items_list = '';

		foreach ($entries as $entry)
		{
			$item_id = $field_id.'-option-'.$entry['entry_id'];

			// is this entry already selected?
			if (in_array($entry['entry_id'], $selected_entry_ids))
			{
				$items_list .= '<li class="pdp-placeholder" id="'.$item_id.'-placeholder"></li>';
			}
			else
			{
				$status = str_replace(' ', '_', $entry['status']);

				$items_list .= '<li id="'.$item_id.'" unselectable="on">'
				             .   '<a><span class="'.$status.'">&bull;</span>'.$entry['title'].'</a>'
				             .   '<input type="hidden" name="'.$field_name.'[selections][]" value="'.$entry['entry_id'].'" disabled="disabled" />'
				             . '</li>';
			}
		}

		return $items_list;
	}

	// --------------------------------------------------------------------

	/**
	 * Display Field - Select
	 */
	private function _display_field_select($data, $cell, $field_name, $field_settings, $entries, $old_data, $selected_entry_ids)
	{
		$options = '';

		foreach($entries as $entry)
		{
			$selected = in_array($entry['entry_id'], $selected_entry_ids) ? 1 : 0;
			$options .= '<option value="'.$entry['entry_id'].'"'.($selected ? ' selected="selected"' : '').'>'.$entry['title'].'</option>' . NL;
		}

		$r = '<input type="hidden" name="'.$field_name.'[old]" value="'.addslashes($old_data).'"/>' . NL
		   . '<select name="'.$field_name.'[selections][]"'.($field_settings['ui_mode'] == 'multi' ? ' class="multiselect" multiple="multiple" size="'.(count($entries) < 15 ? count($entries) : 15).'"' : '').'>' . NL

		     // add a blank option if this is just a drop-down select
		   . ($field_settings['ui_mode'] == 'multi' ? '' : '<option value="">--</option>' . NL)

		   . $options
		   . '</select>';

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Save Field
	 */
	function save($data)
	{
		// save the post data for later
		$this->cache['data'][$this->settings['field_id']] = $data;

		// just return 'y' if there are any selections
		// for the sake of Required field validation
		return (isset($data['selections']) && $data['selections']) ? 'y' : '';
	}

	/**
	 * Post Save
	 */
	function post_save($data)
	{
		// get the data from the cache
		$data = $this->cache['data'][$this->settings['field_id']];
		$r = $this->_save($data);

		// save the new playa data
		$this->EE->db->where('entry_id', $this->settings['entry_id']);
		$this->EE->db->update('channel_data', array('field_id_'.$this->settings['field_id'] => $r));
	}

	/**
	 * Save Cell
	 */
	function save_cell($data)
	{
		return $this->_save($data);
	}

	/**
	 * Save
	 */
	private function _save($data)
	{
		if (! isset($data['selections']))
		{
			$data['selections'] = array();
		}

		// remove empty elements
		$selections = array_merge(array_filter($data['selections']));

		$r = '';

		// -------------------------------------------
		//  Get existing rel IDs
		// -------------------------------------------

		$existing_rels_to_stay = array();

		if ($existing_rel_ids = $this->_get_rel_ids($data['old']))
		{
			$rels = $this->EE->db->query('SELECT rel_id, rel_child_id
			                              FROM exp_relationships
			                              WHERE rel_id '.$this->param2sql($existing_rel_ids));

			$existing_rels_to_delete = array();

			foreach($rels->result_array() as $rel)
			{
				if (in_array($rel['rel_child_id'], $selections))
				{
					$existing_rels_to_stay[$rel['rel_child_id']] = $rel['rel_id'];
				}
				else
				{
					$existing_rels_to_delete[] = $rel['rel_id'];
				}
			}

			// delete deselected rels
			if ($existing_rels_to_delete)
			{
				$this->EE->db->query('DELETE FROM exp_relationships
				                      WHERE rel_id '.$this->param2sql($existing_rels_to_delete));
			}
		}

		if ($selections)
		{
			// -------------------------------------------
			//  Get child titles
			// -------------------------------------------

			$child_titles = array();

			$query = $this->EE->db->query('SELECT entry_id, title
			                               FROM exp_channel_titles
			                               WHERE entry_id '.$this->param2sql($selections));
			foreach($query->result_array() as $row)
			{
				$child_titles[$row['entry_id']] = $row['title'];
			}

			// -------------------------------------------
			//  Build new Playa data
			// -------------------------------------------

			foreach($selections as $child_id)
			{
				if (array_key_exists($child_id, $existing_rels_to_stay))
				{
					// just grab the rel_id
					$rel_id = $existing_rels_to_stay[$child_id];
				}
				else
				{
					// Compile the new relationship
					$rel = array(
						'type'       => 'channel',
						'parent_id'  => $this->settings['entry_id'],
						'child_id'   => $child_id,
						'related_id' => $_POST['channel_id']
					);
					$rel_id = $this->EE->functions->compile_relationship($rel, TRUE);
				}

				// Add the rel_id to $r
				$r .= ($r ? "\r" : '')
				    . '['.$rel_id.'] '.str_replace('\'', '', $child_titles[$child_id]);
			}
		} // end if $selections

		//  Clear relationship caches where appropriate
		$this->EE->db->query('UPDATE exp_relationships
		                      SET rel_data = "", reverse_rel_data = ""
		                      WHERE rel_parent_id = "'.$this->settings['entry_id'].'"
		                            OR rel_child_id = "'.$this->settings['entry_id'].'"');

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Delete
	 */
	function delete($ids)
	{
		$ids_sql = $this->param2sql($ids);

		$this->EE->db->query('DELETE FROM exp_relationships
		                      WHERE rel_parent_id '.$ids_sql.'
		                            OR rel_child_id '.$ids_sql);
	}

	// --------------------------------------------------------------------

	/**
	 * Relationships Query
	 */
	private function _rel_query($params, $data, &$rel_ids=NULL)
	{
		if (! ($rel_ids = $this->_get_rel_ids($data, TRUE)))
		{
			return FALSE;
		}

		if (! (isset($params['orderby']) && $params['orderby']))
		{
			if (isset($params['offset'])) unset($params['offset']);
			if (isset($params['limit'])) unset($params['limit']);
		}

		return $this->entries_query($params, array(
			'select' => 'r.rel_id, r.rel_child_id',
			'from'   => 'exp_relationships r',
			'where'  => 'r.rel_id '.$this->param2sql($rel_ids).' AND ct.entry_id = r.rel_child_id'
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Replace Tag
	 */
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		// ignore if no related entries
		// or wasn't passed from Playa_ext
		if (! $data || ! isset($this->cache['Channel']))
		{
			return '';
		}

		// return :ul if single tag
		if (! $tagdata || ! $data)
		{
			return $this->replace_ul($data, $params, $tagdata);
		}


		$r = '';

		if (! is_array($params)) $params = array();

		$this->_prep_params($params);

		if ($entries = $this->_rel_query($params, $data, $rel_ids))
		{
			$this->cache['Channel']->rfields[$this->field_name] = $this->field_id;

			$mod_rel_ids = array();

			// -------------------------------------------
			//  Ordering
			// -------------------------------------------

			if ($params['orderby'])
			{
				// ordering and sorting was already done within _rel_query
				// so just flatten $entries
				foreach($entries as $entry)
				{
					$mod_rel_ids[] = $entry['rel_id'];
				}
			}
			else
			{
				if ($params['fixed_order'])
				{
					// use template-defined order
					foreach($entries as $entry)
					{
						$fixed_entry_ids = explode('|', $params['fixed_order']);
						if (($key = array_search($entry['rel_child_id'], $fixed_entry_ids)) !== FALSE)
						{
							$mod_rel_ids[$key] = $entry['rel_id'];
						}
					}
				}
				else
				{
					// retain original order
					foreach($entries as $entry)
					{
						$key = array_search($entry['rel_id'], $rel_ids);
						$mod_rel_ids[$key] = $entry['rel_id'];
					}
				}

				// remove gaps and sort by key
				$mod_rel_ids = array_filter($mod_rel_ids);

				if ($params['sort'] == 'random')
				{
					shuffle($mod_rel_ids);
				}
				else if (strtolower($params['sort']) == 'desc')
				{
					krsort($mod_rel_ids);
				}
				else
				{
					ksort($mod_rel_ids);
				}

				// -------------------------------------------
				//  Randomize, Offset and Limit
				// -------------------------------------------

				if ($params['offset'] || $params['limit'])
				{
					$offset = $params['offset'] ? $params['offset'] : 0;
					$limit = $params['limit'] ? $params['limit'] : count($mod_rel_ids);
					$mod_rel_ids = array_splice($mod_rel_ids, $offset, $limit);
				}
			}

			// -------------------------------------------
			//  Prep Iterators
			// -------------------------------------------

			$this->_switches = array();
			$tagdata = preg_replace_callback('/'.LD.'switch\s*=\s*([\'\"])([^\1]+)\1'.RD.'/sU', array(&$this, '_get_switch_options'), $tagdata);
			$iterator_count = 0;

			// -------------------------------------------
			//  Tagdata
			// -------------------------------------------

			$total_related_entries = count($mod_rel_ids);

			foreach($mod_rel_ids as $i => $rel_id)
			{
				// copy $tagdata
				$entry_tagdata = $tagdata;

				// -------------------------------------------
				//  Var Swaps
				// -------------------------------------------

				// {total_related_entries}
				$entry_tagdata = $this->EE->TMPL->swap_var_single('total_related_entries', $total_related_entries, $entry_tagdata);

				// {switch}
				foreach($this->_switches as $i => $switch)
				{
					$option = $iterator_count % count($switch['options']);
					$entry_tagdata = str_replace($switch['marker'], $switch['options'][$option], $entry_tagdata);
				}

				// update the count
				$iterator_count++;

				// {count}
				$entry_tagdata = $this->EE->TMPL->swap_var_single('count', $iterator_count, $entry_tagdata);

				// -------------------------------------------
				//  Tie into EE relationships
				// -------------------------------------------

				// wrap $tagdata with a {related_entries} tag pair
				$entry_tagdata = LD.'related_entries id="'.$this->field_name.'"'.RD
				               . $entry_tagdata
				               . LD.'/'.'related_entries'.RD;

				// convert tagdata into {REL[field_name]abcdefghREL}
				$this->EE->TMPL->assign_relationship_data($entry_tagdata);

				// get the random marker
				$marker = array_pop(array_keys($this->EE->TMPL->related_data));

				// tell the Weblog object about the new relationship
				$this->cache['Channel']->related_entries[] = $rel_id.'_'.$marker;

				// add {REL[rel_id][field_name]abcdefghREL} to $r
				$r .= LD.'REL['.$rel_id.']['.$this->field_name.']'.$marker.'REL'.RD;
			}

			if ($params['backspace'])
			{
				$this->EE->TMPL->related_data[$marker]['tagdata'] = substr($this->EE->TMPL->related_data[$marker]['tagdata'], 0, -$params['backspace']);
			}
		}

		return $r;
	}

	/**
	 * Get Switch Options
	 */
	private function _get_switch_options($match)
	{
		global $FNS;

		$marker = LD.'SWITCH['.$this->EE->functions->random('alpha', 8).']SWITCH'.RD;
		$this->_switches[] = array('marker' => $marker, 'options' => explode('|', $match[2]));
		return $marker;
	}

	// --------------------------------------------------------------------

	/**
	 * Unordered List
	 */
	function replace_ul($data, $params, $tagdata)
	{
		return "<ul>\n"
		     .   $this->replace_tag($data, $params, "  <li>{title}</li>\n")
		     . '</ul>';
	}

	/**
	 * Ordered List
	 */
	function replace_ol($data, $params, $tagdata)
	{
		return "<ol>\n"
		     .   $this->replace_tag($data, $params, "  <li>{title}</li>\n")
		     . '</ol>';
	}

	/**
	 * Total Related Entries
	 */
	function replace_total_related_entries($data, $params, $tagdata)
	{
		$this->_prep_params($params);

		if ($entries = $this->_rel_query($params, $data))
		{
			return count($entries);
		}

		return 0;
	}

	/**
	 * Entry IDs
	 */
	function replace_entry_ids($data, $params, $tagdata)
	{
		$this->_prep_params($params);

		$r = '';

		if ($entries = $this->_rel_query($params, $data))
		{
			foreach($entries as $entry)
			{
				$r .= ($r ? $params['delimiter'] : '')
				    . $entry['entry_id'];
			}
		}

		return $r;
	}

}
