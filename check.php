<?PHP
	require_once(dirname(__FILE__) . '/config.php');

	list($problems, $pids) = getProblems();
	$data = getProblemResponse($problems);
	$url = '';

	if (count($pids) > 0) {
		$db->queryExec('INSERT INTO callbacks (data) VALUES (\'' . sqlite_escape_string($data) . '\');');
		$callbackid = $db->lastInsertRowid();

		$url = getURL('getAlertData', array('callbackid' => $callbackid));
		foreach ($pids as $id) {
			$pid = sqlite_escape_string($id);
			@$db->queryExec('INSERT INTO alerts (alertid, callback) VALUES ("' . $pid . '", ' . $callbackid . ');');
		}
	}

	if ($url != '') {
		$client = new Services_Twilio($accountSID, $accountAuthCode);
		$client->account->calls->create($fromnumber, $callnumber, $url);
	}
?>