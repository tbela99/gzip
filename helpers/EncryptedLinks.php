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

class EncryptedLinksHelper {

	public function processHTMLAttributes ($attributes, array $options = []) {

		$url_attr = isset($options['parse_url_attr']) ? array_keys($options['parse_url_attr']) : ['href', 'src', 'srcset'];

		foreach ($url_attr as $value) {

			if (!empty($attributes[$value])) {

				if ($value == 'srcset' || $value == 'data-srcset') {

					$values = explode(',', $attributes[$value]);

					$isSupported = false;

					foreach ($values as $key => $val) {

						$data = explode (' ', $val, 2);

						if (count ($data) == 2) {

							$fName = GZipHelper::getName($data[0]);

							if(GZipHelper::isFile($fName) && in_array(strtolower(pathinfo($fName, PATHINFO_EXTENSION)), $options['expiring_links']['file_type'])) {

								$data[0] = $this->encrypt($fName, $options);
								$values[$key] = implode(' ', $data);

								$isSupported = true;
							}
						}
					}

					if ($isSupported) {

						$attributes[$value] = implode(',', $values);
					}
				}

				else {

					$fName = GZipHelper::getName($attributes[$value]);

					if(GZipHelper::isFile($fName) && in_array(strtolower(pathinfo($fName, PATHINFO_EXTENSION)), $options['expiring_links']['file_type'])) {

						$attributes[$value] = $this->encrypt($fName, $options);
					}
				}
			}

		}

		return $attributes;
	}

	protected  function encrypt($file, array $options = []) {

	//	if(GZipHelper::isFile($fName)) {

	//		if (in_array(strtolower(pathinfo($fName, PATHINFO_EXTENSION)), $options['expiring_links']['file_type'])) {

				$dt = new \DateTime();

				$dt->modify($options['expiring_links']['duration']);

				$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

				$secret = bin2hex($nonce.sodium_crypto_secretbox(json_encode([
						'method' => $options['expiring_links']['method'],
						'path' => $file,
						'duration' => $dt->getTimestamp()
					]), $nonce, hex2bin($options['expiring_links']['secret'])));

				return GZipHelper::getHost(\JURI::root(true).'/'.GZipHelper::$route.GZipHelper::$pwa_network_strategy.'e/'.$secret.'/' . basename($file));
	//		}
	//	}

		return $file;
	}
}
