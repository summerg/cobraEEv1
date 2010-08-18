<?php if ( ! defined('APP_VER')) exit('No direct script access allowed');


/**
 * Wygwam Helper Class
*/
class Wygwam_Helper {

	/**
	 * Toolbar button groupings, based on CKEditor's default "Full" toolbar
	 */
	function tb_groups()
	{
		return array(
			array('Source'),
			array('Save', 'NewPage', 'Preview'),
			array('Templates'),
			array('Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord'),
			array('Print', 'SpellChecker', 'Scayt'),
			array('Undo', 'Redo'),
			array('Find', 'Replace'),
			array('SelectAll', 'RemoveFormat'),
			array('Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'),
			array('Bold', 'Italic', 'Underline', 'Strike'),
			array('Subscript', 'Superscript'),
			array('NumberedList', 'BulletedList'),
			array('Outdent', 'Indent', 'Blockquote'),
			array('JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'),
			array('Link', 'Unlink', 'Anchor'),
			array('Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'MediaEmbed'),
			array('Styles'),
			array('Format'),
			array('Font'),
			array('FontSize'),
			array('TextColor', 'BGColor'),
			array('Maximize', 'ShowBlocks'),
			array('About')
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Record of which toolbar items are selects
	 */
	function tb_selects()
	{
		return array('Styles', 'Format', 'Font', 'FontSize');
	}

	/**
	 * Record of which toolbar buttons' class names differ from their input value
	 */
	function tb_class_overrides()
	{
		return array(
			'SpellChecker'   => 'checkspell',
			'SelectAll'      => 'selectAll',
			'RemoveFormat'   => 'removeFormat'
		);
	}

	/**
	 * The real toolbar button names
	 */
	function tb_label_overrides()
	{
		return array(
			'NewPage'        => 'New Page',
			'PasteText'      => 'Paste As Plain Text',
			'PasteFromWord'  => 'Paste from Word',
			'SpellChecker'   => 'Check Spelling',
			'Scayt'          => 'Spell Check As You Type',
			'SelectAll'      => 'Select All',
			'RemoveFormat'   => 'Remove Format',
			'Radio'          => 'Radio Button',
			'TextField'      => 'Text Field',
			'Select'         => 'Selection Field',
			'ImageButton'    => 'Image Button',
			'HiddenField'    => 'Hidden Field',
			'Strike'         => 'Strike Through',
			'NumberedList'   => 'Insert/Remove Numbered List',
			'BulletedList'   => 'Insert/Remove Bulleted List',
			'Outdent'        => 'Decrease Indent',
			'Indent'         => 'Increase Indent',
			'JustifyLeft'    => 'Left Justify',
			'JustifyRight'   => 'Right Justify',
			'JustifyCenter'  => 'Center Justify',
			'JustifyBlock'   => 'Block Justify',
			'HorizontalRule' => 'Insert Horizontal Line',
			'SpecialChar'    => 'Insert Special Character',
			'PageBreak'      => 'Insert Page Break for Printing',
			'Format'         => 'Format',
			'Font'           => 'Font',
			'FontSize'       => 'Size',
			'TextColor'      => 'Text Color',
			'BGColor'        => 'Background Color',
			'ShowBlocks'     => 'Show Blocks',
			'About'          => 'About CKEditor',
			'MediaEmbed'     => 'Embed Media'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Mapping of EE languages to CKEditor language codes
	 */
	function lang_map()
	{
		return array(
			'arabic'              => 'ar',
			'arabic-utf8'         => 'ar',
			'arabic-windows-1256' => 'ar',
			'chinese'             => 'zh',
			'chinese_traditional' => 'zh',
			'chinese_simplified'  => 'zh',
			'czech'               => 'cs',
			'cesky'               => 'cs',
			'danish'              => 'da',
			'deutsch'             => 'de',
			'dutch'               => 'nl',
			'german'              => 'de',
			'english'             => 'en',
			'finnish'             => 'fi',
			'french'              => 'fr',
			'hungarian'           => 'hu',
			'italian'             => 'it',
			'japanese'            => 'ja',
			'korean'              => 'ko',
			'norwegian'           => 'no',
			'polish'              => 'pl',
			'russian'             => 'ru',
			'russian_utf8'        => 'ru',
			'russian_win1251'     => 'ru',
			'slovak'              => 'sk',
			'swedish'             => 'sv',
			'turkish'             => 'tr',
			'ukrainian'           => 'uk'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Default Global Settings
	 */
	function default_global_settings()
	{
		return array(
			'toolbars' => array(
				'Basic' => array('Bold','Italic','Underline','Strike','NumberedList','BulletedList','Link','Unlink','Anchor','About'),
				'Full' => array('Source','Save','NewPage','Preview','Templates','Cut','Copy','Paste','PasteText','PasteFromWord','Print','SpellChecker','Scayt','Undo','Redo','Find','Replace','SelectAll','RemoveFormat','Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField','/',
				                'Bold','Italic','Underline','Strike','Subscript','Superscript','NumberedList','BulletedList','Outdent','Indent','Blockquote','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','Link','Unlink','Anchor','Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak','MediaEmbed','/',
				                'Styles','Format','Font','FontSize','TextColor','BGColor','Maximize','ShowBlocks','About')
			),
			'license_key' => ''
		);
	}

	/**
	 * Default Field Settings
	 */
	function default_field_settings()
	{
		return array(
			'toolbar' => 'Full',
			'upload_dir' => '',
			'config' => array()
		);
	}

	/**
	 * Default Cell Settings
	 */
	function default_cell_settings()
	{
		return array(
			'toolbar' => 'Basic',
			'upload_dir' => '',
			'config' => array(
				'resize_enabled' => 'n',
				'height' => 100
			)
		);
	}

	function base_config()
	{
		return array(
			'skin'               => 'wygwam2',
			'extraPlugins'       => 'MediaEmbed',
			'toolbarCanCollapse' => 'n' // buggy in CKEditor
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Wygwam Custom Toolbar
	 * 
	 * Converts flat array of buttons into multi-dimentional
	 * array of toolgroups and their buttons
	 */
	function custom_toolbar($buttons, $include_missing = FALSE)
	{
		$toolbar = array();

		//foreach ($buttons as $button)
		//{
		//	$toolbar[] = ($button == '/' ? '/' : array($button));
		//}
		//return $toolbar;

		// group buttons by toolgroup
		$tb_groups = $this->tb_groups();
		foreach($tb_groups as $group_index => &$group)
		{
			$group_selection_index = NULL;
			$missing = array();
			foreach($group as $button_index => &$button)
			{
				// selected?
				if (($button_selection_index = array_search($button, $buttons)) !== FALSE)
				{
					if ($group_selection_index === NULL) $group_selection_index = $button_selection_index;
					if ( ! isset($toolbar[$group_selection_index])) $toolbar[$group_selection_index] = array();
					$toolbar[$group_selection_index]['b'.$button_index] = $button;
				}
				else if ($include_missing)
				{
					$missing['b'.$button_index] = '!'.$button;
				}
			}
			if ($group_selection_index !== NULL)
			{
				if ($include_missing) $toolbar[$group_selection_index] = array_merge($missing, $toolbar[$group_selection_index]);
				ksort($toolbar[$group_selection_index]);
				$toolbar[$group_selection_index] = array_values($toolbar[$group_selection_index]);
			}
		}

		// add newlines
		foreach(array_keys($buttons, '/') as $key) $toolbar[$key] = '/';

		// sort by keys and remove them
		ksort($toolbar);
		$r = array();
		foreach($toolbar as $toolgroup) array_push($r, $toolgroup);
		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Config Booleans
	 */
	function config_booleans()
	{
		return array(
			'colorButton_enableMore',
			'disableObjectResizing',
			'editingBlock',
			'entities',
			'entities_greek',
			'entities_latin',
			'entities_processNumerical',
			'forcePasteAsPlainText',
			'forceSimpleAmpersand',
			'fullPage',
			'htmlEncodeOutput',
			'ignoreEmptyParagraph',
			'image_removeLinkByEmptyURL',
			'pasteFromWordNumberedHeadingToList',
			'pasteFromWordPromptCleanup',
			'pasteFromWordRemoveFontStyles',
			'pasteFromWordRemoveStyles',
			'resize_enabled',
			'startupFocus',
			'startupOutlineBlocks',
			'templates_replaceContent',
			'toolbarCanCollapse',
			'toolbarStartupExpanded'
		);
	}

	/**
	 * Config Lists
	 */
	function config_lists()
	{
		return array(
			'contentsCss',
			'templates_files'
		);
	}

	/**
	 * Config Literals
	 */
	function config_literals()
	{
		return array(
			'enterMode'
		);
	}

}


// load the appropriate fieldtype file
if (version_compare(APP_VER, '2', '<'))
{
	require_once 'ft.wygwam.ee1.php';
}
else
{
	require_once 'ft.wygwam.ee2.php';
}
