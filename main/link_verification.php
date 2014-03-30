<?php
	require 'pass.php';
	function verify_link($url, $encoded=false){
		global $safe_browsing_key;
		if ($encoded)
			$url = urldecode(stripslashes(nl2br($url)));

		/*
		$header = @get_headers($url, 1);
		if ($header !== false){
			$code = substr($header[0], 9, 3);
			if ($code != 200)
				return 1;
		} else {
			//not a valid url
			return 2;
		}
		*/
		$path = 'https://sb-ssl.google.com/safebrowsing/api/lookup?client=api&apikey='. $safe_browsing_key .'&appver=1.0&pver=3.0&url='. urlencode($url);
		$contents = @file_get_contents($path);
		if ($contents === false || $contents == "phishing" || $contents ==  "malware" || $contents ==  "phishing,malware")
			return 3;
		// More rigorous testing in the future;
		return 0;
	}
?>