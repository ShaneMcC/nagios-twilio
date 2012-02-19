<?php
	// You don't want to edit this, you want to edit config.local.php
	// This just shows the defaults.

	// How many seconds downtime required for a service before we consider
	// it worthy of a phone call?
	$requiredDowntime = (15 * 60);

	// What statuses are worthy of a phone call?
	$requiredStatus = array('Critical', 'Warning');

	// What is the publically accessible URL for this install?
	$baseURL = 'http://twilio.yourdomain.com/';

	// Company name, used in messages.
	$companyName = 'My Company';

	// Nagios Object File
	$objectFile = '/var/cache/nagios3/objects.cache';

	// Nagios Status File
	$statusFile = '/var/cache/nagios3/status.dat';

	// Number to call.
	$callnumber = '+441234567890';

	// Number to call from.
	$fromnumber = '+441234567890';

	// Twilio AC Number
	$accountSID = 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

	// Twilio Auth Token
	$accountAuthCode = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

	// Access Code for inbound calls.
	$accessCode = '123';

	// Code passed in to call handling scripts to confirm access is allowed
	// to prevent anyone from just accessing your twiml files and obtaining
	// possibly sensitive information.
	//
	// This should be changed.
	$okCode = 'es58howdxTR45';

	// Array of numbers that are treated as having an okCode set at all
	// times
	$adminNumbers = array();

	// Some word-replacements for TTS.
	$tts['search'] = array();
	$tts['replace'] = array();

	// Pronounce Nagios correctly.
	$tts['search'][] = 'nagios';
	$tts['replace'][] = 'nag eos';

	// Pronounce protocols correctly
	$tts['search'][] = 'ssh';
	$tts['replace'][] = 'S S H';
	$tts['search'][] = 'rdp';
	$tts['replace'][] = 'R D P';
	$tts['search'][] = 'dpu';
	$tts['replace'][] = 'C P U';
	$tts['search'][] = 'dns';
	$tts['replace'][] = 'D N S';
	$tts['search'][] = 'imap';
	$tts['replace'][] = 'i map';
	$tts['search'][] = 'pop3';
	$tts['replace'][] = 'pop three';
	$tts['search'][] = 'smtp';
	$tts['replace'][] = 'S M T P';
	$tts['search'][] = 'ssl';
	$tts['replace'][] = 'S S L';
	$tts['search'][] = 'https';
	$tts['replace'][] = 'H T T P S';
	$tts['search'][] = 'http';
	$tts['replace'][] = 'H T T P';

	// bit more complex, but dots should be "dot" not a pause.
	$tts['regexsearch'][] = '/\.([^\s])/';
	$tts['regexreplace'][] = ' dot \1';

	// Here downwards, just loads other things.
	if (file_exists(dirname(__FILE__) . '/config.local.php')) {
		require_once(dirname(__FILE__) . '/config.local.php');
	}

	require_once(dirname(__FILE__) . '/nagios-status/Formats.php');
	require_once(dirname(__FILE__) . '/nagios-status/ObjectFileParser.php');
	require_once(dirname(__FILE__) . '/nagios-status/StatusFileParser.php');
	require_once(dirname(__FILE__) . '/twilio-php/Services/Twilio.php');
	require_once(dirname(__FILE__) . '/functions.php');
	loadDB();
?>