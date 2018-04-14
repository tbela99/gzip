<?php

namespace Sabberworm\CSS\Value;

defined('_JEXEC') or die;

class RuleValueList extends ValueList {
	public function __construct($sSeparator = ',', $iLineNo = 0) {
		parent::__construct(array(), $sSeparator, $iLineNo);
	}
}