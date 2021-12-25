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

use DateTime;
use Gzip\GZipHelper;
use JURI;

class EncryptedLinksHelper
{

	/**
	 * @param array $options
	 * @param string $html
	 * @return string
	 * @since
	 */

	public function postProcessHTML(array $options, $html)
	{

		if (!empty($options['expiring_links']['mimetypes_expiring_links'])) {

			if (preg_match_all('#^\s*(\S+)#ms', $options['expiring_links']['mimetypes_expiring_links'], $matches)) {

				array_splice($options['expiring_links']['file_type'], count($options['expiring_links']['file_type']), 0, $matches[1]);
			}
		}

		$url_attr = isset($options['parse_url_attr']) ? array_keys($options['parse_url_attr']) : ['href', 'src', 'srcset', 'data-src', 'data-srcset'];

		return preg_replace_callback('#<([a-zA-Z0-9:-]+)\s([^>]+)>#is', function ($matches) use($options, $url_attr) {

			$tag = $matches[1];
			$attributes = [];

			if (preg_match_all(GZipHelper::regexAttr, $matches[2], $attrib)) {

				foreach ($attrib[2] as $key => $value) {

					$attributes[$value] = $attrib[6][$key];
				}
			}

			foreach ($url_attr as $value) {

				if (!empty($attributes[$value])) {

					if ($value == 'srcset' || $value == 'data-srcset') {

						$values = explode(',', $attributes[$value]);

						$isSupported = false;

						foreach ($values as $key => $val) {

							$data = explode(' ', $val, 2);

							if (count($data) == 2) {

								$fName = GZipHelper::getName($data[0]);

								if (GZipHelper::isFile($fName) && in_array(strtolower(pathinfo($fName, PATHINFO_EXTENSION)), $options['expiring_links']['file_type'])) {

									$data[0] = $this->encrypt($fName, $options);
									$values[$key] = implode(' ', $data);

									$isSupported = true;
								}
							}
						}

						if ($isSupported) {

							$attributes[$value] = implode(',', $values);
						}

					} else {

						$fName = GZipHelper::getName($attributes[$value]);

						if (GZipHelper::isFile($fName) && in_array(strtolower(pathinfo($fName, PATHINFO_EXTENSION)), $options['expiring_links']['file_type'])) {

							$attributes[$value] = $this->encrypt($fName, $options);
						}
					}
				}
			}

			$html = '<'.$tag;

			foreach ($attributes as $key => $attribute) {

				$html .= ' '.$key.'="'.$attribute.'"';
			}

			return $html.'>';
		}, $html);
	}

	protected function encrypt($file, array $options = [])
	{

		$cache = $options['e_path'].'e'.crc32($file).'.php';

		$secret = '';
		$hash = sha1(hex2bin($options['expiring_links']['secret']));

		$dt = new DateTime();

		if (is_file($cache)) {

			require $cache;

			// renew when $data reaches 80% of its lifetime
			if (isset($data['duration']) &&
				isset($data['renew']) &&
				isset($data['hash']) &&
				$dt->getTimestamp() < $data['renew'] &&
				$data['duration'] == $options['expiring_links']['duration'] &&
				$data['hash'] == $hash) {

				$secret = $data['secret'];
			}
		}

		if (empty($secret)) {

			$dt->modify($options['expiring_links']['duration']);

			$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

			$secret = bin2hex($nonce . sodium_crypto_secretbox(json_encode([
					'method' => $options['expiring_links']['method'],
					'path' => $file,
					'duration' => $dt->getTimestamp()
				]), $nonce, hex2bin($options['expiring_links']['secret'])));

			$data = [

				'renew' => intval($dt->getTimestamp() + ($dt->getTimestamp() - time()) * .2),
				'duration' => $options['expiring_links']['duration'],
				'secret' => $secret,
				'hash' => $hash
			];

			file_put_contents($cache, '<?php'."\n
defined('_JEXEC') or die;
\$data = ".var_export($data, true).";");
		}

		return GZipHelper::getHost(JURI::root(true) . '/' . GZipHelper::$route . GZipHelper::$pwa_network_strategy . 'e/' . $secret . '/' . basename($file));
	}
}
