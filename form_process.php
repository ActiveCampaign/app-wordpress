<?php

	// only Ajax requests come through here

	$api_url = (preg_match("/^https:\/\//", $_GET["api_url"])) ? $_GET["api_url"] : "https://" . $_GET["api_url"];
	$api_key = $_GET["api_key"];
	$sync = $_GET["sync"];

	define("ACTIVECAMPAIGN_URL", $api_url);
	define("ACTIVECAMPAIGN_API_KEY", $api_key);
	require_once "activecampaign-api-php/ActiveCampaign.class.php";
	$ac = new ActiveCampaign(ACTIVECAMPAIGN_URL, ACTIVECAMPAIGN_API_KEY);

	$form_process = $ac->api("form/process?sync={$sync}");

	if ($form_process) {
		// form submitted via ajax
		echo $form_process;
		exit;
	}

?>