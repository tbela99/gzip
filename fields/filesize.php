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

class JFormFieldFileSize extends JFormField
{

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

		$unit = preg_replace('#^\d+#', '', $this->value);

		if ($class !== '') {

			$class = ' '.$class;
		}

		$class = ' class="form-control'.$class.'"';

		if ($disabled) {

			$attributes .= ' disabled';
		}

		$options = [];

		foreach($this->element->children() as $option) {

			if ($option->getName() != 'unit') {

				continue;
			}

			$option['value'] = (string) $option['value'];
			$option['text'] = (string) $option;

			$options[] = $option;
		}

		$html .= '<input type="number" required value="'.intval($this->value).'" min="0" step="1" id="'.$this->id.'_0" '.$attributes.$class.' onchange="var n=document.getElementById(\''.$this->id.'_1\');document.getElementById(\''.$this->id.'\').value=this.value+n.options[n.selectedIndex].value">';
		
		$html .= ' <select required id="'.$this->id.'_1"'.($disabled || $readonly ? ' disabled' : '').$class.' onchange="var n=document.getElementById(\''.$this->id.'_0\');document.getElementById(\''.$this->id.'\').value=n.value+this.options[this.selectedIndex].value">';

		foreach ($options as $option) {

			$html .= '<option value="'.htmlspecialchars($option['value']).'"'.($option['value'] == $unit ? ' selected' : '').'>'.JText::_($option['text']).'</option>';
		}

		$html .= '<input type="hidden" value="'.htmlspecialchars($this->value).'" id="'.htmlspecialchars($this->id).'" name="'.$this->name.'"'.$attributes.'>';
 
        return '<div class="btn-group">'.$html.'</div>';
	}
}
