<?php

	// only Ajax requests come through here
	if (!session_id()) {
		session_start();
	}

	require_once("../../../wp-load.php");
	$settings = get_option("settings_activecampaign");

	$api_url = $settings["api_url"];
	$api_key = $settings["api_key"];
	$sync = $_GET["sync"];

	require_once(dirname(__FILE__) . "/activecampaign-api-php/ActiveCampaign.class.php");
	$ac = new ActiveCampaignWordPress($api_url, $api_key);

	$form_process = $ac->api("form/process?sync={$sync}");

	if ($form_process) {
		// form submitted via ajax
		echo $form_process;
		exit;
	}

?>