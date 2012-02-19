<?PHP
	require_once(dirname(__FILE__) . '/config.php');

	if (!checkOkCode(false)) {
		$response = getAccessCode('inbound');
	} else {
		$response = new Services_Twilio_Twiml();
		$message = createTTS('Access Granted. You can now enter a call back ID followed by the star key to hear the associated problems, or a 0 followed by the star key to hear any current problems.');
		$response->gather(array('action' => getURL('getAlertData'), 'method' => 'GET', 'finishOnKey' => '*'))->say($message);
		$response->redirect(getURL('getAlertData', array('Digits' => 0)));
	}

	header('Content-type: text/xml');
	die((String)$response);
?>