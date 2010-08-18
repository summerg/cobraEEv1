<?php if (! defined('BASEPATH')) exit('Invalid file request');


/**
 * Playa Module CP Class
 *
 * @package   Playa
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2010 Pixel & Tonic, LLC
 */
class Playa_mcp {

	/**
	 * Constructor
	 */
	function Playa_mcp()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Filter Entries
	 */
	function filter_entries()
	{
		if (! isset($_POST['field_id']) || ! isset($_POST['field_name'])) exit('Invalid input data');

		if (! class_exists('Playa_ft'))
		{
			if (! class_exists('EE_Fieldtype'))
			{
				require_once APPPATH.'fieldtypes/EE_Fieldtype.php';
			}

			require_once PATH_THIRD.'playa/ft.playa.php';
		}

		// -------------------------------------------
		//  Main params
		// -------------------------------------------

		$params = array();
		if (isset($_POST['channel']))  $params['channel_id'] = $this->EE->input->post('channel');
		if (isset($_POST['category'])) $params['category']   = $this->EE->input->post('category');
		if (isset($_POST['author']))   $params['author_id']  = $this->EE->input->post('author');
		if (isset($_POST['status']))   $params['status']     = $this->EE->input->post('status');

		// -------------------------------------------
		//  Keywords Search
		// -------------------------------------------

		if (isset($_POST['keywords']) && $_POST['keywords'])
		{
			// import the Search module
			if ( ! class_exists('Search'))
			{
	        	require PATH_MOD.'search/mod.search'.EXT;
			}

			$params['entry_id'] = array();
			$Search = new Search();
			if (isset($_POST['channel']))
			{
				$_POST['channel_id'] = explode('|', $this->EE->input->post('channel'));

				// unset $_POST['channel'] to avoid confusing Search
				unset($_POST['channel']);
			}
			$_POST['search_in'] = 'entries';
			$_POST['where'] = 'all';
			$_POST['show_future_entries'] = 'yes';
			$_POST['show_expired'] = 'yes';

			// send the keywords to Search
			$this->EE->load->helper('search');
			$Search->keywords = sanitize_search_terms($_POST['keywords']);

			// build the query
			$sql = $Search->build_standard_query();

			// run the query and add the result entry ids to $params
			if ($sql && ($query = $this->EE->db->query($sql)) && $query->num_rows())
			{
				foreach($query->result_array() as $row)
				{
					$params['entry_id'][] = $row['entry_id'];
				}
			}
			else
			{
				exit();
			}
		}

		// -------------------------------------------
		//  Limit or Order
		// -------------------------------------------

		if (isset($_POST['limit']) && $_POST['limit'])
		{
			$params['orderby'] = 'entry_date';
			$params['sort'] = $_POST['limitby'] == 'newest' ? 'DESC' : 'ASC';
			$params['limit'] = $this->EE->input->post('limit');
		}
		else
		{
			if (isset($_POST['orderby'])) $params['orderby'] = $this->EE->input->post('orderby');
			if (isset($_POST['sort'])) $params['sort'] = $this->EE->input->post('sort');
		}

		// -------------------------------------------
		//  Get the entries
		// -------------------------------------------

		$entries = Playa_ft::entries_query($params, array('select' => 'ct.entry_date, ct.status'));

		if ($entries)
		{
			// -------------------------------------------
			//  post-query ordering
			// -------------------------------------------

			if (isset($_POST['limit']))
			{
				usort($entries, create_function('$a, $b',
					'return '.($_POST['sort'] == 'DESC' ? '-1 * ' : '').'strcmp($a[\''.$_POST['orderby'].'\'], $b[\''.$_POST['orderby'].'\']);'
				));
			}

			// -------------------------------------------
			//  Create the list and return
			// -------------------------------------------

			$field_id = $_POST['field_id'];
			$field_name = $_POST['field_name'];

			$selected_entry_ids = isset($_POST['selected_entry_ids']) && is_array($_POST['selected_entry_ids'])
				? $_POST['selected_entry_ids']
				: array();

			$r = Playa_ft::items_list($field_id, $field_name, $entries, $selected_entry_ids);

			exit($r);
		}
	}

}
