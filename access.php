<?PHP
	require_once(dirname(__FILE__) . '/config.php');

	$response = new Services_Twilio_Twiml();

	$digits = isset($_REQUEST['Digits']) ? $_REQUEST['Digits'] : null;
	$service = isset($_REQUEST['service']) ? $_REQUEST['service'] : null;

	if ($digits == $accessCode && $service != null) {
		$response->redirect(getURL($service));
	} else {
		$response->say(createTTS('Sorry, we were unable to verify that access code ('.$digits.'). Good Bye.'));
	}

	header('Content-type: text/xml');
	die((String)$response);
?>