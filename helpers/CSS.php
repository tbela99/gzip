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
use TBela\CSS\Element;
use TBela\CSS\Element\Stylesheet;
use TBela\CSS\Interfaces\ElementInterface;
use TBela\CSS\Interfaces\RuleListInterface;
use TBela\CSS\Parser;
use TBela\CSS\Renderer;
use TBela\CSS\Value;
use function file_get_contents;
use function file_put_contents;
use function preg_replace_callback;

class CSSHelper
{
	protected $algo = 'sha1';

	// 64 hex ->
	protected function pad($key, $len = 64)
	{


		$kl = strlen($key) - 1;

		if ($kl < $len - 1) {

			$pad = md5($key);
			$key .= str_repeat($pad, ceil(($len - $kl - 1) / strlen($pad)));
		}

		return substr($key, 0, $len);
	}


	/**
	 * @throws \SodiumException
	 * @throws \Exception
	 * @since
	 */
	public function preProcessHTML(array $options, $html)
	{

		if (empty($options['criticalcssenabled']) || trim($options['criticalcssviewports']) === '') {

			return $html;
		}


		$profiler = \JProfiler::getInstance('Application');

		$replace = '';
		$css_min = !empty($options['minifycss']) ? '.min' : '';
		$jsMin = !empty($options['minifyjs']) ? '.min' : '';

		$data = [
			'url' => $options['request_uri'],
			'dimensions' => preg_split('#\s+#s', $options['criticalcssviewports'], -1, PREG_SPLIT_NO_EMPTY)
		];

		usort($data['dimensions'], function ($a, $b) {

			$a = +explode('x', $a)[0];
			$b = +explode('x', $b)[0];

			return $b - $a;
		});

		$path = $options['cri_path'];

		$profiler->mark('preProcessPad');
		$key = $this->pad(hash_hmac($this->algo, $options['template'] . json_encode($data), $options['expiring_links']['secret']));
		$root = $path . $key . '_';

		$matched = [];

		$data['dimensions'] = array_values(array_filter($data['dimensions'], function ($dimension) use (&$matched, $root) {

			if (is_file($root . $dimension . '.css')) {

				$matched[] = $dimension;
				return false;
			}

			return true;
		}));

		if (empty($data['dimensions'])) {

			// all breakpoints processed
			if (is_file($root . 'all' . $css_min . '.css')) {

				$html = preg_replace('#<head>#i', '<style data-ignore="true">' . file_get_contents($path . $key . '_all' . $css_min . '.css') . '</style>' . "\n", $html, 1);

//				$replace = '<style data-ignore="true">' . file_get_contents($path . $key . '_all' . $css_min . '.css') . '</style>' . "\n";
			}
		} else {

			$hash = $root . md5(json_encode($matched));

			if (!empty($matched)) {

				$cssParser = new Parser('', ['capture_errors' => false]);

				if (!is_file($hash . $css_min . '.css')) {

					foreach (array_reverse($matched) as $dimension) {

						$file = $root . $dimension . $css_min . '.css';

						if (is_file($file)) {

							$cssParser->append($file);
						}
					}

					file_put_contents($hash . '.css', $cssParser);
					file_put_contents($hash . '.min.css', (new Renderer(['compress' => true]))->renderAst($cssParser));
				}

				if (is_file($hash . $css_min . '.css')) {

					$html = preg_replace('#<head>#i', '<style data-ignore="true">' . file_get_contents($hash . $css_min . '.css') . '</style>' . "\n", $html, 1);

//				$replace .= '<style data-ignore="true">'.file_get_contents($hash.$css_min.'.css').'</style>'."\n";
				}
			}

			$dt = new \DateTime();
			$dt->modify('+40s');

			$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
			$secret = bin2hex($nonce . sodium_crypto_secretbox(json_encode([
					'key' => $key,
					'duration' => $dt->getTimestamp()
				]), $nonce, hex2bin($key)));

			$data['hash'] = $secret;

			$profiler = \JProfiler::getInstance('Application');
			$profiler->mark('criticalCssSetup');

			$matched = array_values(array_filter($matched, function ($dimension) use ($data) {

				$dimension = intval($dimension);

				foreach ($data['dimensions'] as $dim) {

					if (intval($dim) > $dimension) {

						return false;
					}

					return true;
				}
			}));

			$script = '<script>' . file_get_contents(__DIR__ . '/../worker/dist/critical' . $jsMin . '.js') . '</script>' .
				'<script>' . str_replace(
					['"{ALGO}"', '"{CRITICAL_URL}"', '"{CRITICAL_POST_URL}"', '"{CRITICAL_DIMENSIONS}"', '"{CRITICAL_MATCHED_VIEWPORTS}"', '"{CRITICAL_HASH}"'],
					[strtoupper(str_replace('sha', 'sha-', $this->algo)), json_encode($data['url']), json_encode(GZipHelper::CRITICAL_PATH_URL), json_encode($data['dimensions']), json_encode($matched), json_encode($data['hash'])],
					file_get_contents(__DIR__ . '/../worker/dist/critical-extract' . $jsMin . '.js')) . '
</script>';

			$replace .= $script . "\n";
		}


		if (!empty($replace)) {

			if (preg_match('#(<meta\s*charset=[^>]+>)#', $html, $match)) {

				return str_replace($match[1], $match[1] . $replace, $html);
			}

			return str_replace('</head>', $replace . '</head>', $html);
		}

		return $html;
	}

	/**
	 * @param array $options
	 * @param string $html
	 * @return string
	 *
	 * @throws Parser\SyntaxError
	 * @throws \Exception
	 * @since
	 */
	public function processHTML(array $options, $html)
	{

		$path = $options['css_path'];

		$fetch_remote = !empty($options['fetchcss']);

		$links = [];
		$ignore = !empty($options['cssignore']) ? $options['cssignore'] : [];
		$remove = !empty($options['cssremove']) ? $options['cssremove'] : [];

		$async = !empty($options['asynccss']) || !empty($options['criticalcssenabled']);

		$css_renderer_options['compress'] = !empty($options['minifycss']);
		$css_renderer_options = isset($options['css_renderer']) ? $options['css_renderer'] : [];
		$css_parser_options = isset($options['css_parser']) ? $options['css_parser'] : [];

		$hashFile = GZipHelper::getHashMethod($options);
		$cssParser = new Parser('', $css_parser_options);
		$cssRenderer = new Renderer($css_renderer_options);
		$headStyle = new Stylesheet();

		$parseUrls = function ($css) use ($path) {

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
			}, $css);
		};

		$profiler = \JProfiler::getInstance('Application');
		$profiler->mark('CssInit');

		$html = preg_replace_callback('#<(' . (empty($options['parseinlinecss']) ? 'link' : '[^\s>]+') . ')([^>]*)>#', function ($matches) use ($css_renderer_options, $cssRenderer, $parseUrls, &$links, $ignore, $remove, $cssParser, $path, $fetch_remote, $options) {

			$attributes = [];

			if (preg_match_all(GZipHelper::regexAttr, $matches[2], $attr)) {

				foreach ($attr[2] as $k => $att) {

					$attributes[$att] = $attr[6][$k];
				}
			}

			if ($matches[1] != 'link' && $matches[1] != 'style') {

				// parsing css can be expansive
				// lets do it only when needed
				if (!empty($options['parseinlinecss']) &&
					!empty($attributes['style']) &&
					strpos($attributes['style'], 'background') !== false &&
					strpos($attributes['style'], 'url(') !== false &&
					!empty($attributes['style'])) {

					$attributes['style'] = str_replace("\n", '', trim(preg_replace('~^\.foo\s*\{([^}]+)\}~s', '$1', $cssRenderer->renderAst(new Parser('.foo { ' . preg_replace_callback('~url\(([^)]+)\)~', function ($matches) use ($options) {

							$name = GZipHelper::getName(ImagesHelper::fetchRemoteImage(preg_replace('#(^["\'])([^\1]+)\1#', '$2', trim($matches[1])), $options));

							if (strpos($matches[0], 'data:') !== false || !GZipHelper::isFile($name)) {

								return $matches[0];
							}

							return 'url(' . GZipHelper::url($name) . ')';

						}, $attributes['style']) . ' }', $css_renderer_options)))));

					$result = '<' . $matches[1] . ' ';

					foreach ($attributes as $key => $value) {

						$result .= $key . '="' . $value . '" ';
					}

					return rtrim($result) . '>';
				}

				return $matches[0];
			}

			if (!empty($attributes)) {

				if (isset($attributes['rel']) && in_array($attributes['rel'], ['stylesheet', 'lazy-stylesheet']) && isset($attributes['href'])) {

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

							$remote = $options['scheme'] . ':' . $attributes['href'];
						}

						$parts = parse_url($remote);
						$local = $path . GZipHelper::shorten(crc32($remote)) . '-' . GZipHelper::sanitizeFileName(basename($parts['path'])) . '.css';

						if (!is_file($local)) {

							try {

								$clone = clone $cssParser;
								$clone->load(str_replace('&amp;', '&', $remote));

								file_put_contents($local, $parseUrls($cssRenderer->renderAst($clone->parse())));
							} catch (\Exception $e) {

								error_log($e);

								return $matches[0];
							}
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

					$file = $path . GZipHelper::shorten(crc32($hash)) . '-main' . (!empty($css_renderer_options['compress']) ? '.min' : '') . '.css';

					if (!is_file($file)) {

						foreach ($blob['links'] as $attr) {

							$cssParser->append($attr['href'], isset($attr['media']) && $attr['media'] != 'all' && $attr['media'] !== '' ? $attr['media'] : '');
						}

						file_put_contents($file, $parseUrls($cssRenderer->render($this->parseBackgroundImages($cssParser->parse(), $options))));
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

		if (empty($options['mergecss']) && !empty($css_renderer_options['compress'])) {

			foreach ($links as $position => $blob) {

				if (!empty($blob['links'])) {

					$file = '';

					foreach ($blob['links'] as $attr) {

						$hash = $hashFile($attr['href']) . (isset($attr['media']) && $attr['media'] != 'all' ? $attr['media'] : '');

						$file = $path . GZipHelper::shorten(crc32($hash)) . '-' .
							GZipHelper::sanitizeFileName(pathinfo($attr['href'], PATHINFO_BASENAME)) .
							(!empty($css_renderer_options['compress']) ? '.min' : '') . '.css';

						if (!is_file($file)) {

							$cssParser->load($attr['href'], isset($attr['media']) && $attr['media'] != 'all' && $attr['media'] !== '' ? $attr['media'] : '');
							file_put_contents($file, $parseUrls($cssRenderer->render($this->parseBackgroundImages($cssParser->parse(), $options))));
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

		$html = preg_replace_callback('#(<style[^>]*>)(.*?)</style>#si', function ($matches) use (&$links, $css_renderer_options, $parseUrls) {

			$attributes = [];

			if (preg_match_all(GZipHelper::regexAttr, $matches[1], $attr)) {

				foreach ($attr[2] as $k => $att) {

					$attributes[$att] = $attr[6][$k];
				}
			}

			if ((isset($attributes['type']) && $attributes['type'] != 'text/css')) {

				return $matches[0];
			}

			if (isset($attributes['data-ignore'])) {

				unset($attributes['style']);
				unset($attributes['data-ignore']);

				$result = '<style';

				foreach ($attributes as $key => $value) {

					$result .= " $key=\"$value\"";
				}

				return $result . '>' . $matches[2] . '</style>';
			}

			$position = isset($attributes['data-position']) && $attributes['data-position'] == 'head' ? 'head' : 'body';
			$links[$position]['style'][] = $parseUrls((new Renderer($css_renderer_options))->renderAst((new Parser($matches[2]))->parse()));

			return '';
		}, $html);

		$profiler->mark('CssParseStyle');

		// resize background images
		if (!empty($options['imagecssresize']) && !empty($options['css_sizes'])) {

			$css_hash .= '|parseCssResize' . json_encode($options['css_sizes']);

			$css_hash = $path . GZipHelper::shorten(crc32($css_hash)) . (empty($options['compress']) ? '' : '.min') . '.css';

			if (!is_file($css_hash) && !empty($stylesheets)) {

				foreach ($stylesheets as $stylesheet) {

					$cssParser->append($stylesheet[0], $stylesheet[1]);
				}

				$profiler->mark('CssParseObject');
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
			$profiler->mark('CssBgStyles');

			$headStyle->append($this->parseBackgroundImages((new Parser($style, $css_renderer_options))->parse(), $options));

			if ($headStyle->hasChildren()) {

				$profiler->mark('CssRender');
				$head_string .= '<style>' . $cssRenderer->render($headStyle) . '</style>' . "\n";
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

					$css .= '>' . "\n";

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

					${$position . '_string'} .= '<style>' . $style . '</style>' . "\n";
				}
			}
		}

		if ($head_string !== '' || $noscript != '') {

			if ($noscript != '') {

				$head_string .= '<noscript>' . $noscript . '</noscript>' . "\n";
			}

			$search[] = '</head>';
			$replace[] = $head_string . "\n" . '</head>';
		}

		if ($body_string !== '') {

			$search[] = '</body>';
			$replace[] = $body_string . '</body>';
		}

		if (!empty($search)) {

			$search[] = '<noscript></noscript>';
			$replace[] = '';

			$html = str_replace($search, $replace, $html);
		}

		$profiler->mark('CssWrapUp');
		return $html;
	}

	/**
	 * @param array $options
	 * @throws \SodiumException
	 * @throws \Exception
	 * @since 3.0
	 */
	public function afterInitialise(array $options)
	{

		if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['REQUEST_URI'] == GZipHelper::CRITICAL_PATH_URL && isset($_SERVER['HTTP_X_SIGNATURE'])) {

			$dimensions = preg_split('#\s+#s', $options['criticalcssviewports'], -1, PREG_SPLIT_NO_EMPTY);

			usort($dimensions, function ($a, $b) {

				$a = +explode('x', $a)[0];
				$b = +explode('x', $b)[0];

				return $b - $a;
			});

			$signatures = explode('.', $_SERVER['HTTP_X_SIGNATURE']);

			$raw = file_get_contents('php://input');
			$post = json_decode($raw, JSON_OBJECT_AS_ARRAY);

			if (count($signatures) != 2 ||
				!is_array($post) ||
				!isset($post['url']) ||
				!isset($post['css']) ||
//				!isset($post['fonts']) ||
//				!is_array($post['fonts']) ||
				!isset($_SERVER['HTTP_X_SIGNATURE']) ||
				!isset($post['dimension']) ||
				!in_array($post['dimension'], $dimensions)) {

				http_response_code(400);
				exit;
			}

			$sign = hash($this->algo, $signatures[0] . $raw);
			// compute the key used to sign data
			if ($_SERVER['HTTP_X_SIGNATURE'] != $signatures[0] . '.' . $sign) {

				http_response_code(400);
				exit;
			}

			$data = [

				'url' => $post['url'],
				'dimensions' => $dimensions
			];

			$hash = $this->pad(hash_hmac($this->algo, $options['template'] . json_encode($data), $options['expiring_links']['secret']));

			$raw_message = hex2bin($signatures[0]);
			$nonce = substr($raw_message, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
			$encrypted_message2 = substr($raw_message, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
			$decrypted_message = sodium_crypto_secretbox_open($encrypted_message2, $nonce, hex2bin($hash));

			$encrypted_data = json_decode($decrypted_message, JSON_OBJECT_AS_ARRAY);

			if (!is_array($encrypted_data) ||
				!isset($encrypted_data['key']) ||
				$encrypted_data['key'] != $hash ||
				!isset($encrypted_data['duration'])) {

				http_response_code(400);
				exit;
			}

			if ($encrypted_data['duration'] > time()) {

				http_response_code(410);
				exit;
			}

			$path = $options['cri_path'];

			if (!is_dir($path)) {

				$old_mask = umask();

				umask(022);
				mkdir($path, 0755, true);
				umask($old_mask);
			}

			header('Content-Type: application/json; charset=utf-8');

			$parser = new Parser($post['css']);
			file_put_contents($path . $hash . '_' . $post['dimension'] . '.css', $parser);
			file_put_contents($path . $hash . '_' . $post['dimension'] . '.min.css', (new Renderer(['compress' => true]))->renderAst($parser));

			$processed = true;

			foreach ($dimensions as $dimension) {

				if (!is_file($path . $hash . '_' . $dimension . '.css')) {

					$processed = false;
					break;
				}
			}

			if ($processed) {

				$parser = new Parser('', ['capture_errors' => false]);

				foreach (array_reverse($dimensions) as $dimension) {

					$parser->append($path . $hash . '_' . $dimension . '.css');
				}

				file_put_contents($path . $hash . '_all.css', $parser);
				file_put_contents($path . $hash . '_all.min.css', (new Renderer(['compress' => true]))->renderAst($parser));
			}

			echo 1;
			exit;
		}
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

		$images = [];

		/**
		 * @var Element\Declaration $property
		 */
		foreach ($headStyle->query('[@name=background][@value*="url("]|[@name=background-image][@value*="url("]') as $property) {

			$values = [];
			$hasChanged = false;

			foreach ($property->getRawValue() as $value) {

				if ($value->type == 'background-image' && isset($value->aguments[0]->value)) {

					$hasChanged = true;

					$name = GZipHelper::getName(preg_replace('#(^["\'])([^\1]+)\1#', '$2', trim($value->aguments[0]->value)));

					// honor the "ignore image" setting
					if ((empty($options['imageignore']) ||
							strpos($name, $options['imageignore']) === false) &&
						in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), ['jpg', 'png', 'webp'])) {

						$images[] = $name;
					}

					if (GZipHelper::isFile($name)) {

						$value->arguments = [
							[
								'type' => 'css-string',
								'value' => GZipHelper::url($name)
							]
						];
					}
				}

				$values[] = $value;
			}

			if ($hasChanged) {

				$property->setValue($values);
			}

			// ignore multiple backgrounds for now
			if (count($images) == 1) {

				foreach ($images as $file) {

					$set = array_reverse(ImagesHelper::generateSrcSet($file, $options['css_sizes'], $options), true);

					$keys = array_keys($set);
					$values = array_values($set);
					$property->setValue('url(' . array_shift($values) . ')');

					while ($value = array_shift($values)) {

						$rule = $stylesheet->addAtRule('media', Element\AtRule::ELEMENT_AT_RULE_LIST);

						$prop = $property->copy();
						$prop->setValue('url(' . $value . ')');
						$rule->setValue('(min-width: ' . (array_shift($keys) + 1) . 'px)');
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