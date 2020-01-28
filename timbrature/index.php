<?php
	session_start();	

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
    
    if(!isset($_SESSION['user_timbrature'])&&isset($_REQUEST['password'])) {
    	$res = $db->ql('SELECT Username u FROM ts_users WHERE Pwd = ?', [$_REQUEST['password']]);
    	if(count($res) > 0)
    		$_SESSION['user_timbrature'] = $res[0]['u'];
    }
    
    if(isset($_REQUEST['cambia_user']))
    	unset($_SESSION['user_timbrature']);    	
    
    if(
    		(isset($_REQUEST['da']) && isset($_REQUEST['a'])&&isset($_SESSION['user_timbrature'])) && 
    		($_SESSION['user_timbrature'] != 'admin' || 
    				($_SESSION['user_timbrature'] == 'admin' && isset($_REQUEST['user'])))) {
        
    	$username = ($_SESSION['user_timbrature'] == 'admin' ? $_REQUEST['user'] : $_SESSION['user_timbrature']);
    	
        $a = new DateTime($_REQUEST['a']);

        $user = $db->ql(
            'SELECT *
            FROM ts_users
            WHERE   Username = ?',
            [$username]);

        if(count($user) == 1) {

            $user = $user[0];

            //orari settimanali
            /**
             * 044 00510        044 00510   044 00540   044 00300   044 00300   000 00000   000 00000
             * cod min lavoro
             */
            //pause settimanali
            /**
             * 074 00070        074 00070    07400100   00000000000000000000000000000000
             * cod min pausa
             */

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
            	[':u'=>$username, ':da'=>$_REQUEST['da'], ':a'=>$_REQUEST['a']]);
            
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
            		[':u'=>$username, ':da'=>$_REQUEST['da'], ':a'=>$_REQUEST['a']]);
            
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
            $tot = (int)0;
            $totAssenze = (int)0;
            $totTeorico = (int)0;
            $totSecondsDiurniFestivi = (int)0;
            $totSecondsNotturniFestivi = (int)0;
            $totSecondsDiurniFeriali = (int)0;
            $totSecondsNotturniFeriali = (int)0;
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

                    if(!isFestivo($da_cpy)) $totTeorico += $orariSettimanali[dayOfWeek($da_cpy)]*60;

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
	                $totSecondsDiurniFestivi += $secondsDiurniFestivi;
	                $totSecondsNotturniFestivi += $secondsNotturniFestivi;
	                $totSecondsDiurniFeriali += $secondsDiurniFeriali;
	                $totSecondsNotturniFeriali += $secondsNotturniFeriali;
	                
	                //Inserimento giornata
	                $days[$date]['timbrature'][] = ['in' => $in, 'out' => $out];
	                
		           	$days[$date]['totSeconds'] += $diff;
	                
	                $days[$date]['totSecondsDiurniFeriali'] += $secondsDiurniFeriali;
	                $days[$date]['totSecondsNotturniFeriali'] += $secondsNotturniFeriali;
	                $days[$date]['totSecondsDiurniFestivi'] += $secondsDiurniFestivi;
	                $days[$date]['totSecondsNotturniFestivi'] += $secondsNotturniFestivi;
	                
	                //Stats globali
               		$tot += $diff;
                }       
            }
            

            //Inserimento assenze (giornate intere)
            foreach ($assenze as $assenza) {
                $dataAssenza = new DateTime($assenza['dayStart']);
                $secondiTeorici = $orariSettimanali[dayOfWeek($dataAssenza)]*60;
                $days[date_format($dataAssenza, "d/m/Y")]['totSecondsAssenza'] += $secondiTeorici;
                $days[date_format($dataAssenza, "d/m/Y")]['giustificazione'] .= $assenza['exWhy'];
                $totAssenze += $secondiTeorici;
            }
            
            //Conteggio giorni lavoorati
            foreach($days as $day)
            	if(count($day['timbrature']) > 0)
            		$giorniLavorati++;
            	
            //Saldo straordinari diurni feriali
            $totSecondsSDiurniFeriali = (int)0;
            foreach (array_keys($days) as $date)
            	if(count($days[$date]['timbrature']) > 0) {
	            	$day = date_create_from_format ('d/m/Y', $date);
	            	$teorico = (isFestivo($day)?0:($orariSettimanali[dayOfWeek($day)]*60));
	            	$secondsSDiurniFeriali = $days[$date]['totSecondsDiurniFeriali'] - $teorico;
	            	$totSecondsSDiurniFeriali += $secondsSDiurniFeriali;
	            	$days[$date]['totSecondsSDiurniFeriali']  = $secondsSDiurniFeriali;
	            }
            
            //Export
            if($_REQUEST['export']??'' == 'csv') {
            	header('Content-type: text/csv');
            	header("Content-Disposition: attachment; filename=\"$_REQUEST[user]$_REQUEST[da] - $_REQUEST[a].csv\"");
            	
            	echo 'Data'.CSV_SEP.'Timbrature'.CSV_SEP.'Ore lavorate'.CSV_SEP.'Diurni feriali'.CSV_SEP.'S. Diurni feriali'.CSV_SEP.' S. Notturni feriali'.CSV_SEP.'S. Diurni festivi'.CSV_SEP.'S. Notturni festivi'.CSV_SEP.'Saldo giornaliero'.CSV_SEP.'Da orario'.CSV_SEP.'Ore assenza giustificate'.CSV_SEP.'Giustificazione assenza';
            	echo "\r\n";
            	foreach($days as $date => $day) {
            		echo $date.' ('.$giorniSettimana[dayOfWeek(date_create_from_format ('d/m/Y', $date))].')';
            		echo CSV_SEP;
            		$tmp = [];
            		foreach ($day['timbrature'] as $timbratura)
            			$tmp[] = date_format($timbratura['in'],"H:i").' - '.date_format($timbratura['out'],"H:i");
            		echo implode(', ', $tmp);
            		echo CSV_SEP;
            		echo secondsToHMS($day['totSeconds']);
            		echo CSV_SEP;
            		echo secondsToHMS($day['totSecondsDiurniFeriali']);
            		echo CSV_SEP;
            		echo secondsToHMS($day['totSecondsSDiurniFeriali']);
            		echo CSV_SEP;
            		echo secondsToHMS($day['totSecondsNotturniFeriali']);
            		echo CSV_SEP;
            		echo secondsToHMS($day['totSecondsDiurniFestivi']);
            		echo CSV_SEP;
            		echo secondsToHMS($day['totSecondsNotturniFestivi']);
            		echo CSV_SEP;
            		$teorico = (isFestivo(date_create_from_format ('d/m/Y', $date))?0:($orariSettimanali[dayOfWeek(date_create_from_format ('d/m/Y', $date))]*60));
            		$saldo = ($day['totSeconds'] + $day['totSecondsAssenza']) - $teorico;
            		echo ($saldo < 0?'-':'').secondsToHMS(abs($saldo));
            		echo CSV_SEP;
            		echo secondsToHMS($teorico);
            		echo CSV_SEP;
            		echo secondsToHMS($day['totSecondsAssenza']);
            		echo CSV_SEP;
            		$tmp = $day['giustificazione']?[$day['giustificazione']]:[];
            		foreach ($day['workcodes'] as $workcode)
            			$tmp[] =
            			$workcodes[$workcode['workcode']].' - h'.
            			secondsToHMS($workcode['diff']).
            			' ('.date_format($timbratura['in'],"H:i").' - '.date_format($timbratura['out'],"H:i").')';
            		echo implode('<br>', $tmp);
            		echo "\r\n";
            	}
            	
            	echo count($days);
            	echo CSV_SEP;
            	echo $giorniLavorati;
            	echo CSV_SEP;
            	echo secondsToHMS($tot);
            	echo CSV_SEP;
            	echo secondsToHMS($totSecondsDiurniFeriali);
            	echo CSV_SEP;
            	echo secondsToHMS($totSecondsSDiurniFeriali);
            	echo CSV_SEP;
            	echo secondsToHMS($totSecondsNotturniFeriali);
            	echo CSV_SEP;
            	echo secondsToHMS($totSecondsDiurniFestivi);
            	echo CSV_SEP;
            	echo secondsToHMS($totSecondsNotturniFestivi);
            	echo CSV_SEP;
            	$saldo = ($tot + $totAssenze) - $totTeorico;
            	echo ($saldo < 0?'-':'').secondsToHMS(abs($saldo));
            	echo CSV_SEP;
            	echo secondsToHMS($totTeorico);
            	echo CSV_SEP;
            	echo secondsToHMS($totAssenze);
            	echo CSV_SEP;
            	echo count($assenze);
            	
            	echo "\r\n\r\n";
            	echo 'Tipo di assenza'.CSV_SEP."Occorrenze\r\n";
            	foreach($assenzeIntereStats as $reason => $t)
            		echo $reason.CSV_SEP.$t."\r\n";
            	echo "\r\n";
            	
            	echo 'Tipo di assenza'.CSV_SEP."Ore\r\n";
            	foreach($assenzeParzialiStats as $reason => $t)
            		echo $reason.CSV_SEP.secondsToHMS($t)."\r\n";
            	echo "\r\n";
            	
            	echo $_REQUEST['user'].' '.$_REQUEST['da'].' - '.$_REQUEST['a'];
            	
            	exit();
            }

        }else {
            echo '<pre>Utente non identificato:\n';
            print_r($user);
            echo '</pre>';
        }

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

    echo '<pre>';
   	//print_r($_REQUEST);
    //print_r($user);
    echo '</pre>';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Timbrature</title>
    <script src="../lib/jquery-3.4.1.min.js"></script>
    <script src="../lib/jquery.qtip.min.js"></script>
    
    <script type="text/javascript">
	    $('#tabellona').on('mouseover', 'td[title]', function() {
	        var target = $(this);
	        if (target.data('qtip')) { return false; }
	
	        target.qtip({
	            overwrite: false, // Make sure another tooltip can't overwrite this one without it being explicitly destroyed
	            show: {
	                ready: true // Needed to make it show on first mouseover event
	            },
	            content : {url :$(this).attr('title')},
	            position : {
	                corner : {
	                    tooltip : 'leftBottom',
	                    target : 'rightBottom'
	                }
	            },
	            style : {
	                border : {
	                width : 5,
	                radius : 10
	            },
	            padding : 10,
	            textAlign : 'center',
	            tip : true, 
	            name : 'cream' 
	        }});
	
	        target.trigger('mouseover');
	    });
	    window.onbeforeprint = function(){ $("#menu").css("display", "none"); }
	    window.onafterprint = function(){ $("#menu").css("display", "block"); }
	</script>
    <style>
        table {
        	text-align: center;
            width: 100%;
            font-size: 13px;
			border-collapse:collapse;
			border:1px solid #FF0000;
        }
		table td{
			border:1px solid #FF0000;
		}
		.festivo {
			background-color: #DDDDDD;
		}
		table td:nth-child(even) {
			background-color: #EEEEEE;
		}
    </style>
</head>
<body>
	<?php if(!isset($_SESSION['user_timbrature'])) { ?>
	
		<form action="" method="POST">
            <label>Password utente: </label>
            <input type="password" name="password">
        </form>
        
    <?php }else if(!isset($results)&&$_SESSION['user_timbrature'] == 'admin') { ?>

        <form action="" method="POST">
            <select name="user">
                <?php
                $users = $db->ql('SELECT DISTINCT Username FROM ts_users WHERE Username <> \'admin\' ORDER BY Username');
                foreach($users as $u) echo "<option value=\"$u[Username]\">$u[Username]</option>";
                ?>
            </select>
            <label>Da: </label>
            <input type="date" name="da">
            <label>A: </label>
            <input type="date" name="a">
            <input type="submit">
            <input type="submit" name="cambia_user" value="Cambia persona">
        </form>

    <?php }else if(!isset($results)){ ?>
    	
    	<h3><?= $_SESSION['user_timbrature'] ?></h3>
    	<form action="" method="POST">
            <label>Da: </label>
            <input type="date" name="da">
            <label>A: </label>
            <input type="date" name="a">
            <input type="submit">
            <input type="submit" name="cambia_user" value="Cambia persona">
        </form>
        
    <?php }else {?>
		
        <h3>Piano di lavoro di <?= $user['Username'] ?> dal <?= date_format(date_create($_REQUEST['da']),"d/m/Y"); ?> al <?= date_format(date_create($_REQUEST['a']),"d/m/Y"); ?></h3>
        <?php 
        $url = '?export=csv';
        foreach ($_REQUEST as $key => $val)
        	$url .= "&$key=$val";
        ?>
        
        <div id="menu">
        	<a href="<?= $url ?>">Esporta in CSV</a>
        	<a href="" style="margin-left: 50px;">Indietro</a>
        </div>
        
        <table id="tabellona">
            <tr>
                <td>Data</td>
                <td>Timbrature</td>
                <td>Ore lavorate</td>
                <td>Diurni feriali</td>
                <td>S. diurni feriali</td>
                <td>S. notturni feriali</td>
                <td>S. diurni festivi</td>
                <td>S. notturni festivi</td>
                <td>Saldo giornaliero</td>
                <td>Da orario</td>
                <td>Ore assenza giustificate</td>
                <td>Giustificazione assenza</td>
            </tr>
    <?php  foreach($days as $date => $day) { 
    				$teorico = (isFestivo(date_create_from_format ('d/m/Y', $date))?0:($orariSettimanali[dayOfWeek(date_create_from_format ('d/m/Y', $date))]*60));
    		?>
            <tr <?= ((isFestivo(date_create_from_format('d/m/Y', $date)) || dayOfWeek(date_create_from_format('d/m/Y', $date)) == 5)?'class="festivo"':'') ?>>
				<td><?= $date.' ('.$giorniSettimana[dayOfWeek(date_create_from_format ('d/m/Y', $date))].')' ?></td>
                
                <td>
            		<?php foreach ($day['timbrature'] as $timbratura)
		             	echo '<p>'.date_format($timbratura['in'],"H:i").' - '.date_format($timbratura['out'],"H:i").'</p>'; ?>
                </td>

                <td title="Ore e minuti lavorati"><?= secondsToHMS($day['totSeconds']) ?></td>

                <td title="Ore e minuti lavorati Diurni Feriali"><?= secondsToHMS($day['totSecondsDiurniFeriali']) ?></td>
                <td title="Straordinari ore e minuti lavorati Diurni Feriali"><?= secondsToHMS($day['totSecondsSDiurniFeriali']) ?></td>
                <td title="Straordinari ore e minuti lavorati Notturni Feriali"><?= secondsToHMS($day['totSecondsNotturniFeriali']) ?></td>
                <td title="Straordinari ore e minuti lavorati Diurni Festivi"><?= secondsToHMS($day['totSecondsDiurniFestivi']) ?></td>
                <td title="Straordinari ore e minuti lavorati Notturni Festivi"><?= secondsToHMS($day['totSecondsNotturniFestivi']) ?></td>

                <td title="Saldo ore e minuti">
                    <?php
                    $saldo = ($day['totSeconds'] + $day['totSecondsAssenza']) - $teorico;
                    echo ($saldo < 0?'-':'').secondsToHMS(abs($saldo));
                    ?>
                </td>

                <td title="Ore e minuti da orario"><?= secondsToHMS($teorico) ?></td>
                
                <td title="Ore e minuti assenza giustificata"><?= secondsToHMS($day['totSecondsAssenza']) ?></td>

                <td title="Giustificazione assenza"><?php 
                		$tmp = $day['giustificazione']?[$day['giustificazione']]:[];
                		foreach ($day['workcodes'] as $workcode)
                			$tmp[] = 
                				$workcodes[$workcode['workcode']].' - h'.
                				secondsToHMS($workcode['diff']).
                				' ('.date_format($workcode['in'],"H:i").' - '.date_format($workcode['out'],"H:i").')';
                		echo implode('<br>', $tmp);
                	  ?>
                </td>

            </tr>
            <?php 
        	}
        	
            echo '<pre>';
            //print_r($_REQUEST);
            //print_r($user);
            echo '</pre>';
            ?>
    		<tr>
	        	<td title="Totale giorni periodo"><?= count($days) ?></td>
	        	<td title="Totale giorni con timbrature"><?= $giorniLavorati ?></td>
                <td title="Totale ore e minuti lavorate"><?= secondsToHMS($tot) ?></td>
                <td title="Totale ore e minuti Diurne Feriali"><?= secondsToHMS($totSecondsDiurniFeriali) ?></td>
                <td title="Totale straordinari ore e minuti Diurne Feriali"><?= secondsToHMS($totSecondsSDiurniFeriali) ?></td>
                <td title="Totale straordinari ore e minuti Notturne Feriali"><?= secondsToHMS($totSecondsNotturniFeriali) ?></td>
                <td title="Totale straordinari ore e minuti Diurne Festive"><?= secondsToHMS($totSecondsDiurniFestivi) ?></td>
                <td title="Totale straordinari ore e minuti Notturne Festive"><?= secondsToHMS($totSecondsNotturniFestivi) ?></td>
                <td title="Saldo ore e minuti">
                	<?php 
                    $saldo = ($tot + $totAssenze) - $totTeorico;
                    echo '<strong style="font-size:1.2em;">'.($saldo < 0?'-':'').secondsToHMS(abs($saldo)).'</strong>';
                    ?>
                </td>
                <td title="Totale ore e minuti teoriche"><?= secondsToHMS($totTeorico) ?></td>
                <td title="Totale assenze ore e minuti come se lavorate"><?= secondsToHMS($totAssenze) ?></td>
                <td title="Totale giorni di assenze giustificate come se lavorate"><?= count($assenze) ?></td>
        	</tr>
        </table>
        
        <h2>Statistiche assenze</h2>
        <table>
        <?php 
        foreach($assenzeIntereStats as $reason => $t) { ?>
            <tr>
                <td><?= $reason ?></td>
                <td><?= $t ?></td>
            </tr>
        <?php
        }
        ?>
        </table>
        <table>
        <?php 
        foreach($assenzeParzialiStats as $reason => $t) { ?>
            <tr>
                <td><?= $reason ?></td>
                <td><?= secondsToHMS($t) ?></td>
            </tr>
        <?php
        }
        ?>
        </table>
        
        <h2>Statistiche globali</h2>

        <h3>Conteggio giorni</h3>
        <p>Lavorativi: <?= $giorniLavorati ?></p>
        <p>Assenza: <?= count($assenze) ?></p>
        <p></p>

        <h3>Ore totali</h3>
        <p>Lavorate: <?= secondsToHMS($tot) ?></p>
        <p>Assenza: <?= secondsToHMS($totAssenze) ?></p>
        <p>Teoriche: <?= secondsToHMS($totTeorico) ?></p>
        <p>Saldo: <strong style="font-size:1.2em;">
            <?php 
            $saldo = ($tot + $totAssenze) - $totTeorico;
            echo ($saldo < 0?'-':'').secondsToHMS(abs($saldo));
            ?>
        </strong></p>

    <?php 
    } 
    ?>

	
</body>
</html>
