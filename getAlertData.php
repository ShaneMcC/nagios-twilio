<?PHP
	require_once(dirname(__FILE__) . '/config.php');

	checkOKCode();

	$callbackid = isset($_REQUEST['callbackid']) ? $_REQUEST['callbackid'] : null;
	if ($callbackid == null) {
		$callbackid = isset($_REQUEST['Digits']) ? $_REQUEST['Digits'] : null;
	}

	$response = new Services_Twilio_Twiml();
	$response->say(createTTS('There has been an unknown error with callback id: ' . $callbackid));
	$data = (String)$response;

	if ($callbackid == "0") {
		list($problems, $pids) = getProblems(false);
		$data = getProblemResponse($problems, false);
	} else if ($callbackid != null) {
		$res = $db->query('SELECT data FROM callbacks WHERE id = \'' . sqlite_escape_string($callbackid) . '\'');

		if ($res->numRows() > 0) {
			$row = $res->fetch();
			$data = $row['data'];
		}
	}

	header('Content-type: text/xml');
	die($data);
?>