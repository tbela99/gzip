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

namespace Onesignal;

defined('_JEXEC') or die;

class Onesignal {

	const API_ENDPOINT = 'https://onesignal.com/api/v1/';

	public static function sendArticlePushNotification($onesignal_app_id, $onesignal_rest_key, $test_user = null, $article_title, $article_link, $article_id, $category_id) {
		// Key & ID
		
		// API endpoint
	//	$url = 'notifications';
		// Header with basic authentication
	//	$header = ["Content-Type: application/json; charset=utf-8", 'Authorization: Basic ' . $onesignal_rest_key];
		// Notification's content 
		$contents = ['en' => $article_title];
		// Segments
		$segments = ["All"];
		// Custom data
		$additional_data = ['article_id' => $article_id, 'category_id' => $category_id];

		// HTTP request data
		$data = ['app_id' => $onesignal_app_id, 'contents' => $contents, 'url' => $article_link, 'data' => $additional_data];

		if (empty($test_user)) {

			$data['included_segments'] = $segments;
		}

		else {

			$data['include_player_ids'] = [$test_user];
		}		
		
		$options = [

			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => ["Content-Type: application/json; charset=utf-8", 'Authorization: Basic ' . $onesignal_rest_key],
		//    'http' => [
		//        'header'  => $header,
		    //    'method'  => 'POST',
		//        'content' => json_encode($data)
		//    ]
		];
	//	$context  = stream_context_create($options);
	//	$result = file_get_contents($url, false, $context);
	//	return $result;

		return static::sendRequest('notifications', json_encode($data), $options);
	}	

	public static function sendRequest($endpoint, $data, array $options = []) {

		$ch = curl_init(static::API_ENDPOINT.$endpoint);
		
		curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/../../cacert.pem');
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

		if (!empty($options[CURLOPT_POST]) && !empty($data)) {

			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
            
        if (!empty($options)) {

            curl_setopt_array($ch, $options);
        }

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);

		return ['success' => curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200, 'data' => gettype( $result) == 'string'? json_decode($result) : $result];
    }
}
