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

class JFormFieldMaxAge extends JFormField
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
		$data_attr = '';
		$html = '';

		preg_match('#(\d+)(.+)#', $this->value, $matches);

		$step = (string) $this->element['step'];
		$first = (string) $this->element['first'];
		$last = (string) $this->element['last'];

		if ($step <= 0) {

			$step = 1;
		}

		if ($first === '') {

			$first = 0;
		}

		if ($last === '') {

			$last = 24;
		}

		$attributes = '';

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

		$html = '<select'.($disabled || $readonly ? ' disabled' : '').$class.' size="3" onchange="document.getElementById(\''.$this->id.'\').value=this.options[this.selectedIndex].value+this.nextElementSibling.options[this.nextElementSibling.selectedIndex].value">';

		for ($i = $first; $i <= $last; $i += $step) {

			$html .= '<option value="'.($i == 0 ? '' : $i).'"'.($matches[1] == $i ? ' selected' : '').'>'.JText::_('JOPTION_MAXAGE_'.$i).'</option>';
		}

		$html .= '</select>';

		$html .= ' <select'.($disabled || $readonly ? ' disabled' : '').$class.' onchange="document.getElementById(\''.$this->id.'\').value=this.previousElementSibling.options[this.selectedIndex].value+this.options[this.selectedIndex].value">';

		$html .= '<option value="hours"'.($matches[2] == 'hours' ? ' selected' : '').'>'.JText::_('JOPTION_MAXAGE_UNIT_HOURS').'</option>
		<option value="months"'.($matches[2] == 'months' ? ' selected' : '').'>'.JText::_('JOPTION_MAXAGE_UNIT_MONTHS').'</option></select>';

		$html .= '<input type="hidden" value="'.htmlspecialchars($this->value).'" id="'.htmlspecialchars($this->id).'"'.$attributes.'>';
 
        return $html;
	}
}
