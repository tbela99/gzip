<?php

namespace Sabberworm\CSS\Value;

defined('_JEXEC') or die;

abstract class PrimitiveValue extends Value {
    public function __construct($iLineNo = 0) {
        parent::__construct($iLineNo);
    }

}