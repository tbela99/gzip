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
		$html = '';

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

		$readonly = (string) $this->element['readonly'];
		$readonly = $readonly == 'readonly' || $readonly == 'true';

		$disabled = (string) $this->element['disabled'];
		$disabled = $disabled == 'disabled' || $disabled == 'true';

		$class = trim($this->element['class']);

		if ($class !== '') {

			$class = ' '.$class;
		}

		$class = ' class="form-control'.$class.'"';

		if ($disabled) {

			$attributes .= ' disabled';
		}

		$options = [];

		foreach($this->element->children() as $option) {

			if ($option->getName() != 'option') {

				continue;
			}

			$option['value'] = intval((string) $option['value']);
			$option['text'] = (string) $option;

			$options[] = $option;
		}

		$html .= '<select id="'.$this->id.'_0"'.($disabled || $readonly ? ' disabled' : '').$class.' onchange="var n=document.getElementById(\''.$this->id.'_1\');document.getElementById(\''.$this->id.'\').value=this.options[this.selectedIndex].value+n.options[n.selectedIndex].value">';

		foreach ($options as $option) {

			$html .= '<option value="'.htmlspecialchars($option['value']).'"'.(intval($option['value']) == intval($this->value) ? ' selected' : '').'>'.JText::_($option['text']).'</option>';
		}

		preg_match('#([+-]?\d+)(.+)#', $this->value, $matches);

		if (!isset($matches[1])) {

			$matches[1] = 0;
		}

		if (!isset($matches[2])) {

			$matches[2] = 'months';
		}

		for ($i = $first; $i <= $last; $i += $step) {

			$html .= '<option value="'.($i == 0 ? '' : $i).'"'.($matches[1] == $i ? ' selected' : '').'>'.JText::_('JOPTION_MAXAGE_'.$i).'</option>';
		}

		$html .= '</select>';

		$html .= ' <select id="'.$this->id.'_1"'.($disabled || $readonly ? ' disabled' : '').$class.' onchange="var n=document.getElementById(\''.$this->id.'_0\');document.getElementById(\''.$this->id.'\').value=n.options[n.selectedIndex].value+this.options[this.selectedIndex].value">';

		$html .= '<option value="minutes"'.($matches[2] == 'minutes' ? ' selected' : '').'>'.JText::_('JOPTION_MAXAGE_UNIT_MINUTES').'</option>
		<option value="hours"'.($matches[2] == 'hours' ? ' selected' : '').'>'.JText::_('JOPTION_MAXAGE_UNIT_HOURS').'</option>
		<option value="days"'.($matches[2] == 'days' ? ' selected' : '').'>'.JText::_('JOPTION_MAXAGE_UNIT_DAYS').'</option>
		<option value="weeks"'.($matches[2] == 'weeks' ? ' selected' : '').'>'.JText::_('JOPTION_MAXAGE_UNIT_WEEKS').'</option>
		<option value="months"'.($matches[2] == 'months' ? ' selected' : '').'>'.JText::_('JOPTION_MAXAGE_UNIT_MONTHS').'</option></select>';

		$html .= '<input type="hidden" value="'.htmlspecialchars($this->value).'" id="'.htmlspecialchars($this->id).'" name="'.$this->name.'"'.$attributes.'>';

		return '<div class="btn-group">'.$html.'</div>';
	}
}
