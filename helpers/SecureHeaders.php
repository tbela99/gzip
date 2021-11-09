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

use Exception;
use Gzip\GZipHelper;
use JProfiler;
use function bin2hex;

class SecureHeadersHelper
{

	/**
	 * perform url rewriting, distribute resources across cdn domains, generate HTTP push headers
	 * @param string $html
	 * @param array $options
	 * @return string
	 * @since 1.0
	 */
	public function postProcessHTML(array $options, $html)
	{

		$headers = [];

		if (!empty($options['cspenabled'])) {

			$path = $options['config_path']. 'config.php';

			if (is_file($path)) {

				include $path;
			}

			if (!empty($php_config['headers'])) {

				$headers = $php_config['headers'];
			}

			$links = [];
			$tags = [];

			$sections = array_filter([
				'default',
				'script',
				'style',
				'connect',
				'font',
				'child',
				'frame',
				'img',
				'manifest',
				'media',
				'prefetch',
				'object',
				'worker'
			], function ($section) use ($options) {

				if (isset($options['csp'][$section])) {

					return $options['csp'][$section] != 'ignore';
				}

				return false;
			});

			if (
				!empty($options['csp_inlinestyle']) ||
				(isset($options['csp']['style']) && in_array($options['csp']['style'], ['dynamic', 'mixed']))
			) {

				$tags['link'] = 'link';
				$tags['style'] = 'style';
			}

			if (
				!empty($options['csp_inlinescript']) ||
				(isset($options['csp']['script']) && in_array($options['csp']['script'], ['dynamic', 'mixed']))
			) {

				$tags['script'] = 'script';
				$tags['link'] = 'link';
			}

			if (
			(isset($options['csp']['font']) && in_array($options['csp']['font'], ['dynamic', 'mixed']))
			) {

				$tags['link'] = 'link';
			}

			if (
			(isset($options['csp']['frame']) && in_array($options['csp']['frame'], ['dynamic', 'mixed']))
			) {

				$tags['frame'] = 'frame';
				$tags['iframe'] = 'iframe';
			}

			if (
			(isset($options['csp']['img']) && in_array($options['csp']['img'], ['dynamic', 'mixed']))
			) {

				$tags['img'] = 'img';
			}

			if (!empty($tags)) {

				$url_attr = isset($options['parse_url_attr']) ? array_keys($options['parse_url_attr']) : ['href', 'src', 'srcset'];

				$html = preg_replace_callback('#<((' . implode(')|(', $tags) . '))(\s([^>]*))?>#is', function ($matches) use ($tags, $options, &$links, $url_attr) {

					$tag = strtolower($matches[1]);

					if (in_array($tag, ['link', 'script', 'style', 'img', 'frame', 'iframe'])) {

						// capture urls if mode set to dynamic
						$attributes = [];
						$tagName = $tag;

						if ($tag == 'link') {

							$tagName = 'style';
						} else if ($tag == 'iframe') {

							$tagName = 'frame';
						}

						if (isset($matches[2 + count($tags)]) && preg_match_all(GZipHelper::regexAttr, $matches[2 + count($tags)], $attrib)) {

							foreach ($attrib[2] as $key => $value) {

								$attributes[$value] = $attrib[6][$key];
							}
						}

						// style disabled but script enabled
						if ($tag == 'link' && isset($attributes['as'])) {

							$tagName = $attributes['as'];
						}

						// disabled or invalid
						if (empty($options['csp'][$tagName]) || $options['csp'][$tagName] == 'ignore') {

							return $matches[0];
						}

						// yada yada yada ...
						if (isset($options['csp'][$tagName]) && in_array($options['csp'][$tagName], ['mixed', 'dynamic'])) {

							$sectionName = $tagName;

							if ($tag == 'link') {

								if (isset($attributes['as'])) {

									$sectionName = $attributes['as'];
								}
							}

							foreach ($url_attr as $prop) {

								if (!empty($attributes[$prop])) {

									switch ($attributes[$prop]) {

										case 'srcset':

											foreach (explode(',', $attributes[$prop]) as $attr) {

												foreach (explode(' ', trim($attr), 2) as $at) {

													$name = GZipHelper::parseUrl($at);
													$links[$sectionName][$name] = $name;
												}
											}

											break;
										default:

											$name = GZipHelper::parseUrl($attributes[$prop]);
											$links[$sectionName][$name] = $name;
											break;
									}
								}
							}
						}

						if (in_array($tag, ['style', 'link', 'script'])) {

							$nonce = (!empty($options['csp_inlinescript']) && ($tag == 'script' ||
										(isset($attributes['rel']) && isset($attributes['as']) &&
											$attributes['rel'] == 'preload' && $attributes['as'] == 'script')
									)) ||
								(!empty($options['csp_inlinestyle'] && $tag == 'link' &&
									isset($attributes['rel']) &&
									($attributes['rel'] == 'stylesheet' ||
										(isset($attributes['as']) && $attributes['rel'] == 'preload' && $attributes['as'] == 'style'))

								)) ||
								(!empty($options['csp_inlinestyle']) && $tag == 'style') ||

								array_filter(['font', 'manifest'], function ($entry) use ($tag, $attributes, $options) {

									return $tag == 'link' && !empty($options['csp'][$entry]) &&
										!in_array($options['csp'][$entry], ['ignore', 'block']) &&
										isset($attributes['rel']) &&
										(
											$attributes['rel'] == $entry ||
											(isset($attributes['as']) &&
												$attributes['rel'] == 'preload' && $attributes['as'] == $entry)
										);
								});

							if ($nonce) {

								$attributes['nonce'] = $this->nonce();
							}

							$string = '<' . $matches[1];

							foreach ($attributes as $key => $value) {

								$string .= ' ' . $key . '="' . $value . '"';
							}

							return $string . '>';
						}
					}

					return $matches[0];

				}, $html);
			}

			$csp = [];

			if (!empty($options['upgrade_insecure_requests'])) {

				$csp[] = 'upgrade-insecure-requests';
			}

			if (!empty($options['csp_baseuri'])) {

				$csp[] = 'base-uri \'' . $options['csp_baseuri'] . "'";
			}

			foreach ($sections as $section) {

				$rule = $this->parseCSPData($section, $options['csp'][$section], $options['csp'][$section . '_custom'], isset($links[$section]) ? $links[$section] : [], $options);

				if (!empty($rule)) {

					$csp[] = $rule;
				}
			}

			if (!empty($csp)) {

				if (!empty($options['csp_report_uri'])) {

					$csp[] = 'report-uri ' . $options['csp_report_uri'];
				}

				$headers[$options['cspenabled'] == 'enforce' ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only'] = implode('; ', $csp);
			}
		}

		foreach ($headers as $name => $value) {

			GZipHelper::setHeader($name, $value);
		}

		return $html;
	}

	protected function parseCSPData($section, $directive, $custom_rules, $links = [], $options = [])
	{

		$value = '';

		switch ($directive) {

			case 'ignore':

				return '';

			case 'dynamic':
			case 'mixed':

				if (!empty($links)) {

					$value .= ' ' . implode(' ', $links);
				}

				if ($directive == 'mixed' && !empty($custom_rules)) {

					$value .= ' ' . $custom_rules;
				}

				break;

			case 'none':

				return $section . "-src 'none'";

			case 'custom':

				if (!empty($custom_rules)) {

					$value .= ' ' . $custom_rules;
				}

				break;
		}

		if (!empty($options['csp_inline' . $section])) {

			if ($section == 'script') {

				$value .= " 'strict-dynamic'";
			}

			if ($options['csp_inline' . $section] == 'legacy') {

				$value .= " 'unsafe-inline'";
			}

			$value .= " 'nonce-" . $this->nonce() . "'";
		}

		if ($section == 'script') {

			if (!empty($options['csp_eval'])) {

				$value .= " 'unsafe-eval'";
			}
		}

		if ($value === '') {

			return '';
		}

		$value = explode(' ', trim($value));

		return $section . '-src ' . implode(' ', array_unique($value));
	}

	protected function nonce()
	{

		static $nonce = null;

		if (is_null($nonce)) {

			try {
				$nonce = bin2hex(random_bytes(16));
			} catch (Exception $e) {
			}
		}

		return $nonce;
	}
}