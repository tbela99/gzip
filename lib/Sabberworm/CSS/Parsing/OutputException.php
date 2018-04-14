<?php

namespace Sabberworm\CSS\Parsing;

defined('_JEXEC') or die;

/**
* Thrown if the CSS parsers attempts to print something invalid
*/
class OutputException extends SourceException {
	public function __construct($sMessage, $iLineNo = 0) {
		parent::__construct($sMessage, $iLineNo);
	}
}