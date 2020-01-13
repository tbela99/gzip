<?php 

defined('JPATH_PLATFORM') or die;

/**
 * @package     GZip Plugin
 * @subpackage  System.Gzip *
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 * @since       V2.1
 */

class JFormFieldWebManifestPreview extends JFormFieldTextArea
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.3
	 */
//	protected $type = 'Color';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.3
	 */
	protected function getInput()
	{
		JFactory::getDocument()->addStyleDeclaration('.input-block2 { width: calc(100% - 40px); min-height: 80px; }');
		
		$html = parent::getInput();

		$html .= '<br><button type="button" class="btn btn-success" onclick="this.form.task.value=\'\';const body = new FormData(this.form);body.append(\'manifest_preview\',1);fetch(\'index.php?option=com_plugins&view=plugin&_=\' + Date.now(), {method: \'post\', body: body}).then(function (e) { e.json().then(function (e) { document.getElementById(\''.$this->id.'\').value=JSON.stringify(e,\'\\t\', 1);}) }).catch (function (e) { }); return false">'.JText::_('PLG_GZIP_FIELD_WEBMANIFESTPREVIEW_BUTTON_LABEL').'</button>';

        return $html;
	}
}
