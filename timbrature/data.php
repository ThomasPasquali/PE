<?php
	define('FESTE', ['01-01','01-06','04-25','05-01','06-02','08-15','11-01','12-08','12-25','12-26']);
	define('DIURNO_START', 6);
	define('DIURNO_END', 22);
	define('WORKCODES_BL', [0,2]);
	define('CSV_SEP', ';');
	
	include_once '../lib/db.php';
	$ini = parse_ini_file("../../PE_ini/DB.ini", TRUE)['timbrature'];
	$db = new DB(
			['db'=>$ini['db'],
					'host'=>$ini['host'],
					'dbName'=>$ini['dbName'],
					'port'=>$ini['port'],
					'user'=>$ini['user'],
					'pass'=>$ini['pass']]);
			
		if(!(isset($_REQUEST['user'])&&isset($_REQUEST['da'])&&isset($_REQUEST['a']))) {
			echo json_encode(NULL);
			exit();
		}
				
		$a = new DateTime($_REQUEST['a']);
				
		$user = $db->ql(
				'SELECT *
	            FROM ts_users
	            WHERE   Username = ?',
				[$_REQUEST['user']]);
				
		if(count($user) != 1) {
			echo json_encode(NULL);
			exit();
		}
					
		$user = $user[0];
		/*--------------QUERY----------------*/
		//Orari settimanali
		$orariSettimanali = [];
		$res = $db->ql('SELECT weekTime FROM ts_timetables WHERE SUBSTR(timeName, 11) LIKE ?', [$user['Username'].'%'])[0]['weekTime'];
		for($i = 0; $i < strlen($res); $i+=8)
			$orariSettimanali[] = (int)substr($res, $i+4, 4);
			
		//Workcodes
		$workcodes = [];
		$res = $db->ql('SELECT * FROM ts_workcodes');
		foreach ($res as $workcode)
			$workcodes[$workcode['WorkCode_Number']] = $workcode['WorkCode_Desc'];
						
		//Assenze
		$assenze = $db->ql(
				'SELECT dayStart, exWhy, exLen_days
                FROM ts_schedules_ex
                WHERE   idDeptUser = :user
                    AND DATE(dayStart) BETWEEN :da AND :a
                ORDER BY dayStart',
				[':user' => $user['id'], ':da'=>$_REQUEST['da'], ':a'=>$_REQUEST['a']]);
						
		//Numero assenze per tipo
		$assenzeIntereStats = [];
		$tmp = [];
		foreach($assenze as $assenza) {
			$day = new DateTime($assenza['dayStart']);
			
			//"Scompatto" i giorni di assenza multipli
			while(dateDiff($day, $a) >= 0 && $assenza['exLen_days'] > 0) {
				//Controllo se il giorno � lavorativo
				if(!(isFestivo($day) || dayOfWeek($day) == 5)) {
					$tmp[] = ['dayStart' => date_format($day, 'Y-m-d'), 'exWhy' => $assenza['exWhy']];
					
					if(!isset($assenzeIntereStats[$assenza['exWhy']]))
						$assenzeIntereStats[$assenza['exWhy']] = (int)0;
						
						//Statistiche sui giorni "scompattati"
						$assenzeIntereStats[$assenza['exWhy']]++;
						
						$assenza['exLen_days']--;
				}
				
				$day->modify('+1 day');
			}
		}
		$assenze = $tmp;
						
		//Timbrature
		$results = $db->ql(
				'SELECT d.devName, r.*
                FROM ts_records r
                JOIN ts_users u ON u.idUser = r.idUser
                LEFT JOIN ts_devices d ON d.devNum = r.deviceNum
                WHERE   u.Username LIKE :u
                    AND DATE(r.logTime) BETWEEN :da AND :a
					AND r.valid = 1
                ORDER BY r.logTime',
				[':u'=>$_REQUEST['user'], ':da'=>$_REQUEST['da'], ':a'=>$_REQUEST['a']]);
						
		/*--------------FINE QUERY----------------*/
		
		/*--------------CONTROLLI VALIDITA' DATI----------------*/
						
		$erroriDisparitaTimbrature = $db->ql(
				'SELECT DATE(r.logTime) Data, COUNT(*) n
				FROM ts_records r
					JOIN ts_users u ON u.idUser = r.idUser
				WHERE u.Username LIKE :u
					AND DATE(r.logTime) BETWEEN :da AND :a
					AND r.valid = 1
				GROUP BY DATE(r.logTime)
				HAVING COUNT(*)%2 <> 0',
				[':u'=>$_REQUEST['user'], ':da'=>$_REQUEST['da'], ':a'=>$_REQUEST['a']]);
						
		if(count($erroriDisparitaTimbrature) > 0) {
			header('Content-type: text/txt');
			foreach ($erroriDisparitaTimbrature as $disp)
				echo "Timbrature dispari ($disp[n]) il giorno $disp[Data]\r\n";
				exit();
		}
		
		$erroriInOutTimbrature = [];
		for ($i = 0; $i < count($results); $i+=2)
			if($results[$i]['logAction'] != 0 || $results[$i+1]['logAction'] != 1)
				$erroriInOutTimbrature[] = new DateTime($results[$i]['logTime']);
				
				if(count($erroriInOutTimbrature) > 0) {
					header('Content-type: text/txt');
					foreach ($erroriInOutTimbrature as $err)
						echo "Entrate/Uscite non coerenti il giorno $err\r\n";
						exit();
				}
				/*--------------FINE CONTROLLI VALIDITA' DATI----------------*/
								
		//Variabili globali
		$days = [];
		$giorniSettimana = array('Lun','Mar','Mer','Gio','Ven','Sab','Dom');
		$giorniLavorati = (int)0;
		$assenzeParzialiStats = [];
		
		//Calcolo tot. ore teoriche ed inizializzazione giorni
		//$da = new DateTime($_REQUEST['da']);
		
		//Inserimento date da prendere in considerazione
		$da_cpy = new DateTime($_REQUEST['da']);
		while(date_format($da_cpy, "Y-m-d") <= date_format($a, "Y-m-d")) {
			$days[date_format($da_cpy, "d/m/Y")] =
			['timbrature' => [],
					'workcodes' => [],
					'totSeconds' => (int)0,
					'totSecondsAssenza' => (int)0,
					'giustificazione' => '',
					'totSecondsDiurniFeriali' => (int)0,
					'totSecondsNotturniFeriali' => (int)0,
					'totSecondsDiurniFestivi' => (int)0,
					'totSecondsNotturniFestivi' => (int)0,
					'totSecondsSDiurniFeriali' => (int)0];
			
			$da_cpy->modify('+1 day');
		}
		
		//Iterazione su tutte le timbrature del periodo prese a coppie
		for ($i = 0; $i < count($results); $i+=2) {
			
			$in = new DateTime($results[$i]['logTime']);
			$out = new DateTime($results[$i+1]['logTime']);
			$diff = dateDiff($in, $out);
			$date = date_format($in, "d/m/Y");
			$teorico = (isFestivo($in)?0:($orariSettimanali[dayOfWeek($in)]*60));
			
			//Controlli
			if(date_format($in, "Y-m-d") != date_format($out, "Y-m-d")) echo '<br>Entrata ed uscita su giorni diversi<br>';
			
			//Statistiche sulle ore lavorate
			$hIn = intval($in->format('H'));
			$hOut = intval($out->format('H'));
			$secondsDiurniFestivi = 0;
			$secondsNotturniFestivi = 0;
			$secondsDiurniFeriali = 0;
			$secondsNotturniFeriali = 0;
			
			//Assenza giustificata parziale
			if(!in_array($results[$i]['logCode'], WORKCODES_BL) && $results[$i]['logCode'] == $results[($i+1)]['logCode']) {
				
				$days[$date]['workcodes'][] = ['in' => $in, 'out' => $out, 'diff' => $diff, 'workcode' => $results[$i]['logCode']];
				if(!isset($assenzeParzialiStats[$workcodes[$results[$i]['logCode']]]))
					$assenzeParzialiStats[$workcodes[$results[$i]['logCode']]] = (int)0;
				$assenzeParzialiStats[$workcodes[$results[$i]['logCode']]] += $diff;
					
			}else {
				//Tutto il resto
				//Se festivo
				if(isFestivo($in)) {
					//Se la prima timbratura è "diurna"
					if($hIn >= DIURNO_START && $hIn < DIURNO_END){
						//Se la prima e la seconda timbratura sono "diurne"
						if($hOut >= DIURNO_START && $hOut < DIURNO_END){
							$secondsDiurniFestivi += dateDiff($in, $out);
							//Se la prima timbratura è "diurna" e la seconda "notturna"
						}else {
							//Aggingo l'intervallo in -> DIURNO END
							$secondsDiurniFestivi += dateDiff($in, new DateTime($in->format('Y-m-d '.DIURNO_END.':0:0')));
							//Aggingo l'intervallo DIURNO END -> out
							$secondsNotturniFestivi += dateDiff(new DateTime($out->format('Y-m-d '.DIURNO_END.':0:0')), $out);
						}
						//Se la prima timbratura è "notturna"
					}else {
						//Se la prima timbratura è "notturna" e la seconda "diurna"
						if($hOut >= DIURNO_START && $hOut < DIURNO_END){
							//Aggingo l'intervallo in -> DIURNO START
							$secondsNotturniFestivi += dateDiff($in, new DateTime($in->format('Y-m-d '.DIURNO_START.':0:0')));
							//Aggingo l'intervallo DIURNO END -> out
							$secondsDiurniFestivi += dateDiff(new DateTime($out->format('Y-m-d '.DIURNO_START.':0:0')), $out);
							//Se la prima e la seconda timbratura sono "notturne"
						}else {
							$secondsNotturniFestivi += dateDiff($in, $out);
						}
					}
					//Se feriale
				}else {
					//Se la prima timbratura è "diurna"
					if($hIn >= DIURNO_START && $hIn < DIURNO_END){
						//Se la prima e la seconda timbratura sono "diurne"
						if($hOut >= DIURNO_START && $hOut < DIURNO_END){
							$secondsDiurniFeriali += dateDiff($in, $out);
							//Se la prima timbratura è "diurna" e la seconda "notturna"
						}else {
							//Aggingo l'intervallo in -> DIURNO END
							$secondsDiurniFeriali += dateDiff($in, new DateTime($in->format('Y-m-d '.DIURNO_END.':0:0')));
							//Aggingo l'intervallo DIURNO END -> out
							$secondsNotturniFeriali += dateDiff(new DateTime($out->format('Y-m-d '.DIURNO_END.':0:0')), $out);
						}
						//Se la prima timbratura è "notturna"
					}else {
						//Se la prima timbratura è "notturna" e la seconda "diurna"
						if($hOut >= DIURNO_START && $hOut < DIURNO_END){
							//Aggingo l'intervallo in -> DIURNO START
							$secondsNotturniFeriali += dateDiff($in, new DateTime($in->format('Y-m-d '.DIURNO_START.':0:0')));
							//Aggingo l'intervallo DIURNO END -> out
							$secondsDiurniFeriali += dateDiff(new DateTime($out->format('Y-m-d '.DIURNO_START.':0:0')), $out);
							//Se la prima e la seconda timbratura sono "notturne"
						}else {
							$secondsNotturniFeriali += dateDiff($in, $out);
						}
					}
				}
				
				//Inserimento giornata
				$days[$date]['timbrature'][] = ['in' => $in, 'out' => $out];
				
				$days[$date]['totSeconds'] += $diff;
				
				$days[$date]['totSecondsDiurniFeriali'] += $secondsDiurniFeriali;
				$days[$date]['totSecondsNotturniFeriali'] += $secondsNotturniFeriali;
				$days[$date]['totSecondsDiurniFestivi'] += $secondsDiurniFestivi;
				$days[$date]['totSecondsNotturniFestivi'] += $secondsNotturniFestivi;
			}
		}
								
		//Inserimento assenze (giornate intere)
		foreach ($assenze as $assenza) {
			$dataAssenza = new DateTime($assenza['dayStart']);
			$secondiTeorici = $orariSettimanali[dayOfWeek($dataAssenza)]*60;
			$days[date_format($dataAssenza, "d/m/Y")]['totSecondsAssenza'] += $secondiTeorici;
			$days[date_format($dataAssenza, "d/m/Y")]['giustificazione'] .= $assenza['exWhy'];
		}
		
		//Conteggio giorni lavoorati
		foreach($days as $day)
			if(count($day['timbrature']) > 0)
				$giorniLavorati++;
				
		//Saldo straordinari diurni feriali
		foreach (array_keys($days) as $date)
			if(count($days[$date]['timbrature']) > 0) {
				$day = date_create_from_format ('d/m/Y', $date);
				$teorico = (isFestivo($day)?0:($orariSettimanali[dayOfWeek($day)]*60));
				$secondsSDiurniFeriali = $days[$date]['totSecondsDiurniFeriali'] - $teorico;
				$days[$date]['totSecondsSDiurniFeriali']  = $secondsSDiurniFeriali;
			}
	
	function secondsToHMS($seconds) {
		$seconds = round($seconds);
		$s = abs((int)($seconds/ 60 % 60));
		return (int)($seconds/ 3600).':'.(strlen($s) < 2?'0'.$s:$s);//.(int)($seconds % 60).'s';
	}
	
	/**
	 * 0 = Mon
	 * .....
	 * 6 = Sun
	 */
	function dayOfWeek(DateTime $date) {
		return ((int)date('N', strtotime(date_format($date, "Y-m-d")))-1);
	}
	
	/**
	 * @return int Differenza in secondi
	 */
	function dateDiff(DateTime $from, DateTime $to) {
		return (getTimeUnixTruncatedToMinute($to) - getTimeUnixTruncatedToMinute($from));
	}
	
	function getTimeUnixTruncatedToMinute(DateTime $date) {
		return ($date->getTimestamp() - ($date->getTimestamp()%60));
	}
	
	/**
	 *
	 * @return bool
	 */
	function isFestivo(DateTime $day) {
		return ((dayOfWeek($day) == 6) || (in_array($day->format('m-d'), FESTE)));
	}
	
	/*--------------CONVERSIONE DATI PER JSON----------------*/
	$datiTabella = [];
	foreach ($days as $date => $day) {
		
		$timbrature = [];
		foreach ($day['timbrature'] as $timbratura)
			$timbrature[] = date_format($timbratura['in'],"H:i").' - '.date_format($timbratura['out'],"H:i");
		$timbrature = implode(' / ', $timbrature);
		
		$teorico = (isFestivo(date_create_from_format ('d/m/Y', $date))?0:($orariSettimanali[dayOfWeek(date_create_from_format ('d/m/Y', $date))]*60));
		$saldo = ($day['totSeconds'] + $day['totSecondsAssenza']) - $teorico;
		$saldo = ($saldo < 0?'-':'').secondsToHMS(abs($saldo));
		
		$giustificazioni = $day['giustificazione']?[$day['giustificazione']]:[];
		foreach ($day['workcodes'] as $workcode)
			$giustificazioni[] = $workcodes[$workcode['workcode']].' - h'.
										secondsToHMS($workcode['diff']).
										' ('.date_format($workcode['in'],"H:i").' - '.date_format($workcode['out'],"H:i").')';
		$giustificazioni = implode(' / ', $giustificazioni);
		
		$datiTabella[] = [
				'data' => $date.' ('.$giorniSettimana[dayOfWeek(date_create_from_format ('d/m/Y', $date))].')',
				'timbrature' => $timbrature,
				'lavorate' => secondsToHMS($day['totSeconds']),
				'diu_fer' => secondsToHMS($day['totSecondsDiurniFeriali']),
				's_diu_fer' => secondsToHMS($day['totSecondsSDiurniFeriali']),
				's_not_fer' => secondsToHMS($day['totSecondsNotturniFeriali']),
				's_diu_fes' => secondsToHMS($day['totSecondsDiurniFestivi']),
				's_not_fes' => secondsToHMS($day['totSecondsNotturniFestivi']),
				'saldo' => $saldo,
				'orario' => secondsToHMS($teorico),
				'assenza' => secondsToHMS($day['totSecondsAssenza']),
				'giust_ass' => $giustificazioni
		];
	}
	
	$statsOre = [];
	foreach ($assenzeParzialiStats as $key => $val)
		$statsOre[] = ['tipo' => $key, 'durata' => secondsToHMS($val)];
	
	$statsConteggi = [];
	foreach ($assenzeIntereStats as $key => $val)
		$statsConteggi[] = ['tipo' => $key, 'ricorrenze' => $val];
		
	header('Content-type: application/json');
	$data = [
			'datiTabella' => $datiTabella,
			'statsOre' => $statsOre,
			'statsConteggi' => $statsConteggi,
			'misc' => [
					'title' => "Piano di lavoro di $user[Username] dal ".date_format(date_create($_REQUEST['da']),"d/m/Y")." al ".date_format(date_create($_REQUEST['a']),"d/m/Y")
			]
	];
	echo json_encode($data);