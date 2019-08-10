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

class JFormFieldCSP extends JFormField
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
		$attributes = '';
		$html = '';

		$readonly = (string) $this->element['readonly'];
		$readonly = $readonly == 'readonly' || $readonly == 'true';

		$disabled = (string) $this->element['disabled'];
		$disabled = $disabled == 'disabled' || $disabled == 'true';

		$class = trim($this->element['class']);

		if ($class !== '') {
			
			$class = ' class="'.$class.'"';
		}

		if ($disabled) {

			$attributes .= ' disabled';
		}

		$value = explode('##', $this->value);

		$html .= '<select id="'.$this->id.'_0"'.($disabled || $readonly ? ' disabled' : '').$class.' onchange="document.getElementById(\''.$this->id.'\').value=this.options[this.selectedIndex].value+\'##\'+document.getElementById(\''.$this->id.'_1\').value">';

		foreach($this->element->children() as $option) {

			if ($option->getName() != 'option') {

				continue;
			}

			$html .= '<option value="'.htmlspecialchars((string) $option['value']).'"'.((string) $option['value'] == $value[0] ? ' selected' : '').'>'.JText::_(((string) $option->attributes ('text')) ? $option['text'] : $option).'</option>';
		}

		$html .= '</select><br><input type="textarea" rows="15" value="'.htmlspecialchars(isset($value[1]) ? $value[1] : '').'" id="'.$this->id.'_1">';

		$html .= '<input type="hidden" value="'.htmlspecialchars($this->value).'" id="'.htmlspecialchars($this->id).'" name="'.$this->name.'"'.$attributes.'>';
 
        return $html;
	}
}
