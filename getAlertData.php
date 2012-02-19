<?PHP
	$baseURL = 'http://twilio.hostinguk.net/';
	require_once(dirname(__FILE__) . '/config.php');

	$callbackid = empty($_REQUEST['callbackid']) ? null : $_REQUEST['callbackid'];

	header('Content-type: text/xml');

	if ($callbackid != null) {
		$res = $db->query('SELECT data FROM callbacks WHERE id = \'' . sqlite_escape_string($callbackid) . '\'');

		if ($res->numRows() > 0) {
			$row = $res->fetch();
			die($row['data']);
		}
	}

	$response = new Services_Twilio_Twiml();
	$response->say(createTTS('There has been an unknown error with callback id: ' . $callbackid));
	echo (String)$response;
?>

