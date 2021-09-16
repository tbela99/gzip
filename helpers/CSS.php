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

use Gzip\GZipHelper;
//use TBela\CSS\Compiler;
use TBela\CSS\Element;
use TBela\CSS\Element\Stylesheet;
use TBela\CSS\Interfaces\ElementInterface;
use TBela\CSS\Interfaces\RuleListInterface;
use TBela\CSS\Parser;
use TBela\CSS\Renderer;
use TBela\CSS\Value;
use function file_get_contents;
use function file_put_contents;
use function getimagesize;
use function preg_replace_callback;

class CSSHelper
{

	/**
	 * @param string $html
	 * @param array $options
	 *
	 * @return string
	 *
	 * @throws Parser\SyntaxError
	 * @throws \Exception
	 * @since version
	 */
	public function processHTML($html, array $options = [])
	{

		$path = $options['css_path'];

		$fetch_remote = !empty($options['fetchcss']);

		$links = [];
		$ignore = !empty($options['cssignore']) ? $options['cssignore'] : [];
		$remove = !empty($options['cssremove']) ? $options['cssremove'] : [];

		$async = !empty($options['asynccss']) || !empty($options['criticalcssenabled']);

		$css_options = isset($options['css_options']) ? $options['css_options'] : [
			'compress' => !empty($options['minifycss'])
		];

		$hashFile = GZipHelper::getHashMethod($options);
		$cssParser = new Parser();
		$cssRenderer = new Renderer($css_options);
		$headStyle = new Stylesheet();

		$fetchFonts = function ($node) use ($options, $headStyle) {

			/**
			 * @var Element\AtRule $node
			 */

			if ((string)$node['name'] == 'font-face') {

				$query = $node->query('./[@name=src]');

				/**
				 * @var ElementInterface $query
				 */

				$query = end($query);

				if ($query) {

					foreach ($query as $q) {

						$q->getValue()->map(function ($value) {

							/**
							 * @var Value $value
							 */

							if ($value->type == 'css-url') {

								$name = GZipHelper::getName(preg_replace('#(^["\'])([^\1]+)\1#', '$2', trim($value->arguments->{0})));

								if (GZipHelper::isFile($name)) {

									return Value::getInstance((object)[
										'name' => 'url',
										'type' => 'css-url',
										'arguments' => new Value\Set([
											Value::getInstance((object)[
												'type' => 'css-string',
												'value' => GZipHelper::url($name)
											])
										])
									]);
								}
							}

							return $value;
						});
					}

					$copy = $query->copy();

					$declaration = new Element\Declaration();
					$declaration->setName('font-display');
					$declaration->setValue($options['fontdisplay']);

					/**
					 * @var RuleListInterface
					 */
					$copy->getParent()->insert($declaration, 0);

					$headStyle->append($copy->getRoot());
				}
			}
		};
		$parseUrls = function ($html) use ($path) {

			return preg_replace_callback('~url\(([^)]+)\)~', function ($matches) use ($path) {

				$url = preg_replace('~^(["\'])?([^\1]+)\1~', '$2', trim($matches[1]));

				if (strpos($matches[0], 'data:') !== false) {

					return $matches[0];
				}

				if (preg_match_all('~^(https?:)?//~', $url)) {

					$parts = explode('/', parse_url($url)['path']);
					$localName = $path . GZipHelper::shorten(crc32($url)) . '-' . GZipHelper::sanitizeFileName(end($parts));

					if (!is_file($localName)) {

						$data = Parser\Helper::fetchContent($url);

						if ($data !== false) {

							file_put_contents($localName, $data);
						}
					}

					if (is_file($localName)) {

						return 'url(' . GZipHelper::url($localName) . ')';
					}
				}

				if (substr($url, 0, 1) != '/') {

					return 'url(' . GZipHelper::url($url) . ')';
				}

				return $matches[0];
			}, $html);
		};

		$profiler = \JProfiler::getInstance('Application');
		$profiler->mark('CssInit');

		$html = preg_replace_callback('#<(' . (empty($options['parseinlinecss']) ? 'link' : '[^\s>]+') . ')([^>]*)>#', function ($matches) use ($css_options, $cssRenderer, $parseUrls, &$links, $ignore, $remove, $cssParser, $path, $fetch_remote, $options) {

			$attributes = [];

			if (preg_match_all(GZipHelper::regexAttr, $matches[2], $attr)) {

				foreach ($attr[2] as $k => $att) {

					$attributes[$att] = $attr[6][$k];
				}
			}

			if ($matches[1] != 'link') {

				// parsing css can be expansive
				// let do it only when needed
				if (!empty($options['parseinlinecss']) &&
					!empty($attributes['style']) &&
					strpos($attributes['style'], 'background') !== false &&
					strpos($attributes['style'], 'url(') !== false &&
					!empty($attributes['style'])) {

					$attributes['style'] = str_replace("\n", '', trim(preg_replace('~^\.foo\s*\{([^}]+)\}~s', '$1', $cssRenderer->render((new Parser('.foo { ' . preg_replace_callback('~url\(([^)]+)\)~', function ($matches) use ($options) {

							$name = GZipHelper::getName(ImagesHelper::fetchRemoteImage(preg_replace('#(^["\'])([^\1]+)\1#', '$2', trim($matches[1])), $options));

							if (strpos($matches[0], 'data:') !== false || !GZipHelper::isFile($name)) {

								return $matches[0];
							}

							return 'url(' . GZipHelper::url($name) . ')';

						}, $attributes['style']) . ' }', $css_options))->parse()))));

					$result = '<' . $matches[1] . ' ';

					foreach ($attributes as $key => $value) {

						$result .= $key . '="' . $value . '" ';
					}

					return rtrim($result) . '>';
				}

				return $matches[0];
			}

			if (!empty($attributes)) {

				if (isset($attributes['rel']) && $attributes['rel'] == 'stylesheet' && isset($attributes['href'])) {

					$name = GZipHelper::getName($attributes['href']);

					$position = isset($attributes['data-position']) && $attributes['data-position'] == 'head' ? 'head' : 'body';

					unset($attributes['data-position']);

					foreach ($remove as $r) {

						if (strpos($name, $r) !== false) {

							return '';
						}
					}

					foreach ($ignore as $i) {

						if (strpos($name, $i) !== false) {

							return $matches[0];
						}
					}

					if ($fetch_remote && preg_match('#^((https?:)?)//#', $name)) {

						$remote = $attributes['href'];

						if (strpos($name, '//') === 0) {

							$remote = $options['scheme'] . ':' . $name;
						}

						$parts = parse_url($remote);
						$local = $path . GZipHelper::shorten(crc32($remote)) . '-' . GZipHelper::sanitizeFileName(basename($parts['path'])) . '.css';

						if (!is_file($local)) {

							$clone = clone $cssParser;
							$clone->load($remote);

							file_put_contents($local, $parseUrls($clone->parse()));
						}

						if (is_file($local)) {

							$name = $local;
						}
					}

					if (GZipHelper::isFile($name)) {

						$attributes['href'] = $name;
						$links[$position]['links'][$name] = $attributes;
						return '';
					}
				}
			}

			return $matches[0];
		}, $html);

		$profiler->mark('CssParseHTML');

		if (!empty($options['mergecss'])) {

			foreach ($links as $position => $blob) {

				$hash = '';

				if (!empty($blob['links'])) {

					foreach ($blob['links'] as $attr) {

						$hash .= $hashFile($attr['href']);
						$media = isset($attr['media']) && $attr['media'] != 'all' ? $attr['media'] : '';

						$hash .= $media;
					}

					$file = $path . GZipHelper::shorten(crc32($hash)) . '-main' . (!empty($css_options['compress']) ? '.min' : '') . '.css';

					if (!is_file($file)) {

						foreach ($blob['links'] as $key => $attr) {

							$cssParser->append($attr['href']);
						}

						file_put_contents($file, $parseUrls($cssRenderer->render($this->parseBackgroundImages($cssParser->parse()->traverse($fetchFonts, 'enter'), $options))));
					}

					$links[$position]['links'] = [
						[
							'href' => $file,
							'rel' => 'stylesheet'
						]
					];
				}
			}
		}

		$profiler->mark('CssMergeFile');

		if (empty($options['mergecss']) && !empty($css_options['compress'])) {

			foreach ($links as $position => $blob) {

				if (!empty($blob['links'])) {

					$file = '';

					foreach ($blob['links'] as $attr) {

						$hash = $hashFile($attr['href']) . (isset($attr['media']) && $attr['media'] != 'all' ? $attr['media'] : '');

						$file = $path . GZipHelper::shorten(crc32($hash)) . '-' .
							GZipHelper::sanitizeFileName(pathinfo($attr['href'], PATHINFO_BASENAME)) .
							(!empty($css_options['compress']) ? '.min' : '') . '.css';

						if (!is_file($file)) {

							$cssParser->load($attr['href']);
							file_put_contents($file, $parseUrls($cssRenderer->render($this->parseBackgroundImages($cssParser->parse()->traverse($fetchFonts, 'enter'), $options))));
						}
					}

					$links[$position] = [
						[
							'href' => $file
						]
					];
				}
			}
		}

		$profiler->mark('CssMergeStyle');
		$cssParser->setContent('');

		$css_hash = '';
		$stylesheets = [];

		foreach ($links as $values) {

			foreach ($values['links'] as $value) {

				if (isset($value['href']) && GZipHelper::isFile($value['href']) && isset($value['rel']) && $value['rel'] == 'stylesheet') {

					$media = !empty($link['media']) && $link['media'] != 'all' ? $link['media'] : '';
					$css_hash .= $value['href'] . ($media ? '-' . $media : '');
					$stylesheets[] = [$value['href'], $media];
				}
			}
		}

		$html = preg_replace_callback('#(<style[^>]*>)(.*?)</style>#si', function ($matches) use (&$links, $css_options) {

			$attributes = [];

			if (preg_match_all(GZipHelper::regexAttr, $matches[1], $attr)) {

				foreach ($attr[2] as $k => $att) {

					$attributes[$att] = $attr[6][$k];
				}
			}

			if (isset($attributes['type']) && $attributes['type'] != 'text/css') {

				return $matches[0];
			}

			$position = isset($attributes['data-position']) && $attributes['data-position'] == 'head' ? 'head' : 'body';
			$links[$position]['style'][] = !empty($css_options['compress']) ? (new Renderer($css_options))->renderAst(new Parser($matches[2])) : $matches[2];

			return '';
		}, $html);


		$profiler->mark('CssParseStyle');

		$parseCritical = !empty($options['criticalcssenabled']);
		$parseCssResize = !empty($options['imagecssresize']) && !empty($options['css_sizes']);
		$parseWebFonts = !empty($options['fontpreload']) || !isset($options);

		$criticalCss = null;
		$cssResize = null;
		$webFont = null;

		if ($parseWebFonts || $parseCritical || $parseCssResize) {

			if ($parseWebFonts) {

				$css_hash .= '|parseWebFonts';
			}

			if ($parseCritical) {

				$css_hash .= '|parseCritical' . $options['criticalcssclass'] . $options['criticalcssclass'];
			}

			if ($parseCssResize) {

				$css_hash .= '|parseCssResize' . json_encode($options['css_sizes']);
			}

			$css_hash = $path . GZipHelper::shorten(crc32($css_hash)) . (empty($options['compress']) ? '' : '.min') . '.css';

			if (!is_file($css_hash) && !empty($stylesheets)) {

				foreach ($stylesheets as $stylesheet) {

					$cssParser->append($stylesheet[0], $stylesheet[1]);
				}

				$cssRoot = $cssParser->parse();
				$profiler->mark('CssParseObject');

				if ($parseCritical && !empty($options['criticalcssclass'])) {

					$headStyle->appendCss($options['criticalcssclass']);
				}

				$profiler->mark('CssQCriticalClass');

				$query = [];

				if ($parseCritical && !empty($options['criticalcss'])) {

					$query[] = 'html, body';
					$query[] = implode(',', preg_split('#\n#s', $options['criticalcss'], -1, PREG_SPLIT_NO_EMPTY));
				}

				$nodes = $cssRoot->queryByClassNames(implode(',', $query));
				$profiler->mark('CssQueryByName');

				if (!empty($nodes)) {

					foreach ($nodes as $node) {

						$headStyle->append($node->copy()->getRoot());
					}
				}

				$profiler->mark('CssQueryAppend');

				$headStyle->deduplicate();

				file_put_contents($css_hash, $cssRenderer->render($this->parseBackgroundImages($headStyle, $options)));

				$profiler->mark('CssDedup');
			}

			if (is_file($css_hash)) {

				if (!isset($links['head']['style'])) {

					$links['head']['style'] = [];
				}

				array_unshift($links['head']['style'], file_get_contents($css_hash));
			}
		}

		$profiler->mark('CssCritical');

		$headStyle->removeChildren();
		$style = '';

		foreach ($links as $key => $blob) {

			if (!empty($blob['style'])) {

				$style .= implode('', $blob['style']);
				unset($links[$key]['style']);
			}
		}

		$search = [];
		$replace = [];

		$head_string = '';
		$body_string = '';
		$noscript = '';

		if ($style !== '') {

			$headStyle->append($this->parseBackgroundImages((new Parser($style, $css_options))->parse(), $options));

			if ($headStyle->hasChildren()) {

				$profiler->mark('CssStyle');
				$headStyle->deduplicate();
				$profiler->mark('CssRender');
				$head_string .= '<style>' . $cssRenderer->render($headStyle) . '</style>';
			}
		}

		$profiler->mark('CssHead');

		foreach ($links as $position => $blob) {

			if (isset($blob['links'])) {

				foreach ($blob['links'] as $key => $link) {

					if ($async) {

						$link['data-media'] = isset($link['media']) ? $link['media'] : 'all';
						$link['media'] = 'print';
					}

					//
					$css = '<link';

					reset($link);

					foreach ($link as $attr => $value) {

						$css .= ' ' . $attr . '="' . $value . '"';
					}

					$css .= '>';

					if ($async) {

						if (isset($link['media'])) {

							$noscript .= str_replace([' media="print"', 'data-media'], ['', 'media'], $css);
						} else {

							$noscript .= $css;
						}
					}

					$links[$position]['links'][$key] = $css;
				}

				${$position . '_string'} .= implode('', $links[$position]['links']);
			}

			if (!empty($blob['style'])) {

				$style = trim(implode('', $blob['style']));

				if ($style !== '') {

					${$position . '_string'} .= '<style>' . $style . '</style>';
				}
			}
		}

		if ($head_string !== '' || $noscript != '') {

			if ($noscript != '') {

				$head_string .= '<noscript>' . $noscript . '</noscript>';
			}

			$search[] = '</head>';
			$replace[] = $head_string . '</head>';
		}

		if ($body_string !== '') {

			$search[] = '</body>';
			$replace[] = $body_string . '</body>';
		}

		if (!empty($search)) {

			$html = str_replace($search, $replace, $html);
		}

		$profiler->mark('CssWrapUp');
		return $html;
	}

	/** generate responsive background images
	 * @param Element $headStyle
	 * @param array $options
	 *
	 * @return Element|RuleListInterface
	 *
	 * @throws Parser\SyntaxError
	 * @since version
	 */
	public function parseBackgroundImages(Element $headStyle, array $options = [])
	{

		if (empty($options['imagecssresize']) || empty($options['css_sizes'])) {

			return $headStyle;
		}

		$stylesheet = new Stylesheet();

		/**
		 * @var Element\Declaration $property
		 */
		foreach ($headStyle->query('[@name="background"]|[@name="background-image"]') as $property) {

			$images = [];
			$property->getValue()->map(function ($value) use (&$images) {

				/**
				 * @var Value $value
				 */

				if ($value->type == 'css-url') {

					$name = GZipHelper::getName(preg_replace('#(^["\'])([^\1]+)\1#', '$2', trim($value->arguments->{0})));

					// honor the "ignore image" setting
					if ((empty($options['imageignore']) ||
						strpos($name, $options['imageignore']) === false) &&
						in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), ['jpg', 'png', 'webp'])) {

						$images[] = $name;
					}

					if (GZipHelper::isFile($name)) {

						return Value::getInstance((object)[
							'name' => 'url',
							'type' => 'css-url',
							'arguments' => new Value\Set([
								Value::getInstance((object)[
									'type' => 'css-string',
									'value' => GZipHelper::url($name)
								])
							])
						]);
					}
				}

				return $value;
			});

			// ignore multiple backgrounds for now
			if (count($images) == 1) {

				foreach ($images as $file) {

					$set = array_reverse(ImagesHelper::generateSrcSet($file, $options['css_sizes'], $options), true);

					$keys = array_keys($set);
					$values = array_values($set);
					$property->setValue('url('.array_shift($values).')');

					while ($value = array_shift($values)) {

						$rule = $stylesheet->addAtRule('media', Element\AtRule::ELEMENT_AT_RULE_LIST);

						$prop = $property->copy();
						$prop->setValue('url('.$value.')');
						$rule->setValue('(min-width: '.(array_shift($keys) + 1).'px)');
						$rule->append($prop->getRoot());
					}
				}

				/**
				 * @var RuleListInterface $headStyle
				 */
				$headStyle->append($stylesheet);
			}
		}

		$headStyle->append($stylesheet);
		return $headStyle;
	}
}