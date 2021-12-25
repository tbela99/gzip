<?php 

defined('JPATH_PLATFORM') or die;

/**
 * max age in minutes / hours / months. Negative maxage means ignore the setting. 0 means use default
 * 
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

class JFormFieldSoduimSecret extends JFormField
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
	//	$attributes = '';

		$readonly = (string) $this->element['readonly'];
		$readonly = $readonly == 'readonly' || $readonly == 'true';

		$disabled = (string) $this->element['disabled'];
		$disabled = $disabled == 'disabled' || $disabled == 'true';

		$class = trim($this->element['class']);

		if ($class !== '') {

			$class = ' '.$class;
		}

		$attributes = ' class="form-control'.$class.'"';

		if ($readonly) {

			$attributes .= ' readonly="readonly"';
		}

		if ($disabled) {

			$attributes .= ' disabled';
		}

		$html = '<input type="text" name="'.htmlspecialchars($this->name).'" value="'.htmlspecialchars($this->value).'" id="'.$this->id.'" '.$attributes.'> <button type="button" class="btn btn-success" onclick="this.previousElementSibling.value=\'\'">Reset</button>';

        return '<div class="btn-group">'.$html.'</div>';
	}
}
