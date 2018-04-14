<?php

namespace Sabberworm\CSS;

defined('_JEXEC') or die;

interface Renderable {
	public function __toString();
	public function render(\Sabberworm\CSS\OutputFormat $oOutputFormat);
	public function getLineNo();
}