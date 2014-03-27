<?php
	require 'pass.php';
	function verify_link($url){
		global $safe_browsing_key;
		$path = 'https://sb-ssl.google.com/safebrowsing/api/lookup?client=api&apikey='. $safe_browsing_key .'&appver=1.0&pver=3.0&url='. urlencode($url);
		$contents = @file_get_contents($path);
		if ($contents === false || $contents == "phishing" || $contents ==  "malware" || $contents ==  "phishing,malware")
			return 1;
		// More rigorous testing in the future;
		return 0;
	}
?>