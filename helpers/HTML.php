<?php

/**
 * @package     GZip Plugin
 * @subpackage  System.Gzip *
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

namespace Gzip\Helpers;

use function preg_replace_callback;
use function strlen;

class HTMLHelper {

	/**
	 * @param array $options
	 * @param string $html
	 * @return string
	 * @since
	 */
	public function preProcessHTML(array $options, $html) {

		$debug = empty($options['debug']) ? '.min' : '';

		// quick test
		$hasScript = stripos($html, '<script') !== false || stripos($html, '<link ') !== false;

		$script = '';
		$css = '';

		if ($hasScript ||
			(!empty($options['cssenabled']) && !empty($options['criticalcssenabled'])) ||
			!empty($options['imagesvgplaceholder']) ||
			!empty($options['inlineimageconvert'])) {

			$script .= file_get_contents(__DIR__.'/../js/dist/lib.'.(!empty($options['imagesvgplaceholder']) ? 'images' : 'ready').$debug.'.js');
			$script .= file_get_contents(__DIR__.'/../loader'.$debug.'.js');

			if (!empty($options['imagesvgplaceholder'])) {

				$script .=  file_get_contents(__DIR__.'/../imagesloader'.$debug.'.js');
				$css .= '<style type="text/css" data-position="head">'.file_get_contents(__DIR__.'/../css/images.css').'</style>'."\n";
			}

			if (!empty($options['inlineimageconvert'])) {

				$script .=  file_get_contents(__DIR__.'/../bgstyles'.$debug.'.js');
			}
		}

		if(!empty($script)) {

			$html = str_replace('</head>', $css.'<script data-ignore="true">'.$script.'</script>'."\n".'</head>', $html);
		}

		$script = '';

		if (!empty($options['instant_loading_enabled'])) {

			$script .= file_get_contents(__DIR__.'/../worker/dist/browser.prefetch'.$debug.'.js');

			$path = $options['config_path'].'config.php';

			if (is_file($path)) {

				include $path;

				if (!empty($php_config['instantloading'])) {

					$html = str_replace('<body', '<body data-instant-'.implode(' data-instant-', $php_config['instantloading']), $html);
				}
			}
		}

		if(!empty($script)) {

			$html = str_replace('</body>','<script>'.$script.'</script></body>', $html);
		}

		return $html;
	}

	/**
	 * html minification
	 * @param array $options
	 * @param string $html
	 * @return string
	 * @since 1.0
	 */
	public function postProcessHTML (array $options, $html) {

		if (!empty($options['fix_invalid_html'])) {

			/*
			 * attempt to fix invalidHTML - missing space between attributes -  before minifying
			 * <div id="foo"class="bar"> => <div id="foo" class="bar">
			 */
			$html = preg_replace_callback('#<([^\s>]+)([^>]+)>#s', function ($matches) {

				$result = '<'.$matches[1];

				if (trim($matches[2]) !== '') {

					$in_str = false;
					$quote = '';

					$j = strlen($matches[2]);

					for ($i = 0; $i < $j; $i++) {

						$result .= $matches[2][$i];

						if ($in_str) {

							if ($matches[2][$i] == $quote) {

								$in_str = false;
								$result .= ' ';
								$quote = '';
							}
						}

						else if (in_array($matches[2][$i], ['"', "'"])) {

							$in_str = true;
							$quote = $matches[2][$i];
						}
					}
				}

				return rtrim($result).'>';
			}, $html);
		}

		if (empty($options['minifyhtml'])) {

			return $html;
		}

		$scripts = [];

		$self = [

			'meta',
			'link',
			'br',
			'base',
			'input'
		];

		if (!empty($options['preserve_ie_comments'])) {

			$html = preg_replace_callback('#<!--(.*?)-->#s', function ($matches) use (&$scripts) {

				if (preg_match('#(\[if .*?\]>)(.*?)<!\[endif\]\s*#s', $matches[1], $m)) {

						$key = '~~!'.md5($m[0]).'!~~';
						$scripts[$key] = '<!--'.$m[1].trim($m[2]).'<![endif]-->';

						return $key;
				}

				return '';

			}, $html);

			if (!empty($scripts)) {

				$html = str_replace(array_keys($scripts), array_values($scripts), $html);
				$scripts = [];
			}
		}

		else {

			$html = preg_replace('#<!--.*?-->#s', '', $html);
		}

		$html = preg_replace_callback('#<html(\s[^>]+)?>(.*?)</head>#si', function ($matches) {

			return '<html'.$matches[1].'>'. preg_replace('#>[\r\n\t ]+<#s', '><', $matches[2]).'</head>';
		}, $html, 1);

		//remove optional ending tags (see http://www.w3.org/TR/html5/syntax.html#syntax-tag-omission )
		$remove = [
			'</rt>', '</rp>', '</caption>',
			'</option>', '</optgroup>', '</li>', '</dt>', '</dd>', '</tr>', '</th>', '</td>', '</thead>', '</tbody>', '</tfoot>', '</colgroup>'
		];

		if(stripos($html, '<!DOCTYPE html>') !== false) {

			$remove = array_merge($remove, [

				'<head>',
				'</head>',
				'<body>',
				'</body>',
				'<html>',
				'</html>'
			]);
		}

		$html = str_ireplace($remove, '', $html);

		// minify html
		//remove redundant (white-space) characters
		$replace = [

			'#<(('.implode(')|(', $self).'))(\s[^>]*?)?/>#si' => '<$1$'.(count($self) + 2).'>',
			'/\>[^\S ]+/s' => '> ',
			'/[^\S ]+\</s' => ' <',
			//shorten multiple whitespace sequences; keep new-line characters because they matter in JS!!!
			'/([\t\r\n ])+/s' => ' ',
			//remove leading and trailing spaces
			'/(^([\t ])+)|(([\t ])+$)/m' => '',
			//remove empty lines (sequence of line-end and white-space characters)
			'/[\r\n]+([\t ]?[\r\n]+)+/s' => ''
		];

		$root = $options['webroot'];

		// remove quotes from HTML attributes that does not contain spaces; keep quotes around URLs!
		$html = preg_replace_callback('~([\r\n\t ])?([a-zA-Z0-9:]+)=(["\'])([^\s="\'`]*)\3([\r\n\t ])?~', function ($matches) use ($options, $root) {

			if ($matches[2] == 'style') {

				// remove empty style attributes which are invalid
				if (trim($matches[4]) === '') {

					return ' ';
				}

				return $matches[0];
			}

			$result = $matches[1].$matches[2].'=';

			if (!empty($options['parse_url_attr']) && array_key_exists($matches[2], $options['parse_url_attr'])) {

				$value = (!preg_match('#^([a-z]+:)?//#', $matches[4]) && is_file($matches[4]) ? $root : '').$matches[4];
				$result .= str_replace($options['scheme'].'://', '//', $value);
			}

			else {

				$result .= $matches[4];
			}

			if (isset($matches[5])) {

				return $result.$matches[5];
			}

			return $result;
		}, $html);


		$html = preg_replace('#<!DOCTYPE ([^>]+)>[\n\s]+#si', '<!DOCTYPE $1>', $html, 1);
		$html = preg_replace(array_keys($replace), array_values($replace), $html);

		if (!empty($scripts)) {
			$html = str_replace(array_keys($scripts), array_values($scripts), $html);
		}

		return preg_replace_callback('#<([^>]+)>#s', function ($matches) {

			return '<'.rtrim($matches[1]).'>';
		}, $html);
	}
}