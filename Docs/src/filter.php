#!/usr/bin/env php
<?php
#
# format the side bar
#
# TODO: Fix duplicate ids

$input = file_get_contents('php://stdin');

function process($node, $level = 1) {

	$node['class'] = 'nav bd-sidenav nav-level-'.$level;

	foreach($node->xpath('./li/a') as $link) {

		$link['class'] = 'bd-toc-link';

		foreach ($link->xpath('..') as $parent) {
			
			$parent['class'] = 'bd-toc-item';
		}
	
		foreach ($link->xpath('./following-sibling::ul') as $ul) {

			process($ul, $level + 1);
		}
	}

	return $node;
}

// add class to the toc elements
echo preg_replace_callback('#(<nav class="side-bar"[^>]+>)(.*?)(<\/nav>)#s', function ($matches) {

	$xml = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?>'.	$matches[2]);

	return $matches[1].str_replace('<?xml version="1.0" encoding="utf-8"?>', '', process($xml, 1)->asXML()).$matches[3];

}, $input);