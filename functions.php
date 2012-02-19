<?php
	function loadDB() {
		global $db;

		$dbFile = dirname(__FILE__) . '/twilioDB.sqlite';
		$exists = file_exists($dbFile);

		if ($db == null) {
			try {
				$db = new SQLiteDatabase($dbFile, 0700, $error);
			} catch (Exception $e) { die($error); }
		}

		if (!$exists) {
			$queries = array();
			$queries[] = 'CREATE TABLE callbacks (id INTEGER PRIMARY KEY, data TEXT);';
			$queries[] = 'CREATE TABLE alerts (alertid TEXT PRIMARY KEY, callback INTEGER);';

			foreach ($queries as $q) {
				if (!$db->queryExec($q, $error)) {
					unlink($dbFile);
					die("\n" . $error . "\n");
				}
			}
		}

		return $db;
	}

	function duration($seconds) {
		$time['days'] = (int) $seconds / 86400 % 86400;
		$time['hours'] = (int) $seconds / 3600 % 24;
		$time['minutes'] = (int) $seconds / 60 % 60;
		$time['seconds'] = (int) $seconds % 60;

		$string = '';
		if ($time['days'] > 0) {
			$string .= $time['days'] . (($time['days'] == 1) ? ' day ' : ' days ');
		}

		if ($time['hours'] > 0) {
			$string .= $time['hours'] . (($time['hours'] == 1) ? ' hour ' : ' hours ');
		}

		if ($time['minutes'] > 0) {
			$string .= $time['minutes'] . (($time['minutes'] == 1) ? ' minute ' : ' minutes ');
		}

		if ($time['seconds'] > 0) {
			$string .= $time['seconds'] . (($time['seconds'] == 1) ? ' second ' : ' seconds ');
		}

		$string = trim($string);
		return empty($string) ? '0 hours' : $string;
	}

	// Some TTS Corrections.
	function createTTS($text) {
		global $tts;

		if (isset($tts['search']) && isset($tts['replace']) && count($tts['search']) == count($tts['replace'])) {
			$text = str_ireplace($tts['search'], $tts['replace'], $text);
		}

		if (isset($tts['regexsearch']) && isset($tts['regexreplace']) && count($tts['regexsearch']) == count($tts['regexreplace'])) {
			$text = preg_replace($tts['regexsearch'], $tts['regexreplace'], $text);
		}

		return $text;
	}

	function getProblems($check = true) {
		global $objectFile, $statusFile, $requiredStatus, $requiredDowntime, $db;

		$op = new ObjectFileParser($objectFile);
		$sp = new StatusFileParser($statusFile);

		$sh = $sp->getHosts();
		$hosts = $op->getHosts();
		knatsort($hosts);

		$problems = array();

		$count = 0;

		$pids = array();

		foreach ($hosts as $hn => $host) {
			foreach ($host['services'] as $sn => $service) {
				$hostStatus = $sh[$hn];
				$services = $hostStatus['services'][$sn];
				$status = translateStatus($services['current_state']);

				$id = $host['host_name'] . '_' . str_replace(' ', '_', $service['service_description']) . '_' . $services['last_hard_state_change'];
				$status = translateStatus($services['current_state']);

				$critical = in_array($status, $requiredStatus);

				$downtime = time() - $services['last_state_change'];
				$notifications = $services['notifications_enabled'] == 1 && $host['notifications_enabled'] == 1;
				$checking = $services['active_checks_enabled'] == 1 || $services['passive_checks_enabled'] == 1;
				$acknowledged = $services['problem_has_been_acknowledged'] == 1;

				$scheduledDowntime = $hostStatus['scheduled_downtime_depth'] > 0 || $services['scheduled_downtime_depth'];

				$period = duration($downtime);

				if ($notifications && $critical && !$acknowledged && $checking && !$scheduledDowntime && $downtime >= $requiredDowntime) {
					$pid = sqlite_escape_string($id);
					$res = $db->query('SELECT * from alerts WHERE alertid = "'.$pid.'"');

					if (!$check || $res->numRows() == 0) {
						$pids[] = $id;
						$name = strtolower($host['host_name']);
						$desc = strtolower($host['alias']);

						$hostdesc = $name == $desc ? $name : $name . ' (' . $desc . ')';

						$problems[] = 'Problem Number ' . ++$count . ', ' . ucfirst(strtolower($service['service_description'])) . ' on ' . $hostdesc . ' has been ' . $status . ' for '. $period;
					}
				}
			}
		}

		return array($problems, $pids);
	}

	function getProblemResponse($problems, $greeting = true) {
		global $companyName;

		$count = count($problems);

		$response = new Services_Twilio_Twiml();
		if ($greeting) {
			$response->say(createTTS('This is the ' . $companyName . ' Monitoring service.'));
		}
		if ($count == 1) {
			$response->say(createTTS('There is currently ' . $count . ' problem in nagios that require attention.'));
		} else {
			$response->say(createTTS('There are currently ' . $count . ' problems in nagios that require attention.'));
		}
		$response->say(createTTS('Please note, you will only be notified once per problem.'));
		$response->pause();
		foreach ($problems as $p) {
			$response->say(createTTS($p));
			$response->pause();
		}
		$response->say(createTTS('End of problems. Good Bye.'));

		return (String)$response;
	}

	function checkOKCode($dieOnFail = true) {
		global $okCode, $adminNumbers;
		$result = isset($_REQUEST['OkCode']) && $_REQUEST['OkCode'] == $okCode;
		$fromNumber = isset($_REQUEST['From']) ? $_REQUEST['From'] : '';

		if (!empty($fromNumber) && in_array($fromNumber, $adminNumbers)) {
			return true;
		}

		if (!$result && $dieOnFail) {
			header('Content-type: text/xml');
			$response = new Services_Twilio_Twiml();
			$response->say(createTTS('Access Denied.'));
			die((String)$response);
		}

		return $result;
	}

	function getAccessCode($service) {
		global $companyName, $baseURL;
		$response = new Services_Twilio_Twiml();
		$greeting = createTTS('This is the ' . $companyName . ' Monitoring service. In order to access this service, please enter your access code, followed by the star key.');
		$response->gather(array('action' => getURL('access', array('service' => 'inbound'), false), 'method' => 'GET', 'finishOnKey' => '*'))->say($greeting);
		$response->say(createTTS('Sorry, we were unable to verify your access code. Good Bye.'));
		return $response;
	}

	function getURL($function, $params = array(), $ok = true) {
		global $okCode, $baseURL;
		$url = $baseURL;

		$url .= $function . '.php?';

		$bits = array();

		if ($ok) {
			$bits[] = urlencode('OkCode') . '=' . urlencode($okCode);
		}

		foreach ($params as $k => $v) {
			$bits[] = urlencode($k) . '=' . urlencode($v);
		}

		$url .= implode('&', $bits);

		return $url;
	}
?>