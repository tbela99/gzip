<?php 

defined('JPATH_PLATFORM') or die;

/**
 * Color Form Field class for the Joomla Platform.
 * This implementation is designed to be compatible with HTML5's `<input type="color">`
 *
 * @link   http://www.w3.org/TR/html-markup/input.color.html
 * @since  11.3
 */
class JFormFieldPushButton extends JFormField
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

		foreach ($this->element->attributes() as $attr => $value) {

		//	if(!$this->element->attributes())

			switch ($attr) {

				case 'hint':

					$attributes .= ' placeholder="'.htmlspecialchars($value);
					break;
			}
		}

		foreach(['name', 'id'] as $attr) {

			$value = $this->{$attr};

			if($value === '') {

				continue;
			}

			switch($attr) {
				

				case 'name':
				case 'id':

					$attributes .= ' '.$attr.'="'.htmlspecialchars($value).'"';
					break;
			}
		}

		$datalist = '';

		foreach($this->element->children() as $option) {

			if ($option->getName() != 'option') {

				continue;
			}

			$datalist .= '<option value="'.htmlspecialchars((string) $option['value']).'">'.htmlspecialchars($option['text']).'</option>';
		}

		if ($datalist !== '') {

			$attributes .= ' list="'.$this->id.'_datalist"';
			$datalist = '<datalist id="'.$this->id.'_datalist">'.$datalist.'</datalist>';
		}

		// Trim the trailing line in the layout file
        return '<span class="input-group clearfix">
        <input type="text" class="form-control"'.$attributes.'>'.$datalist.'
        <span class="input-group-btn"><button class="btn btn-primary" type="button">Send</button></span>
      </span>';
	}
}
