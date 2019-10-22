<?php

    define('FESTE', ['01-01','01-06','04-25','05-01','06-02','06-24','08-15','11-01','12-08','12-25','12-26']);
    define('DIURNO_START', 6);
    define('DIURNO_END', 22);

    include_once '../lib/db.php';
    $ini = parse_ini_file("../../PE_ini/DB.ini", TRUE)['timbrature'];
    $db = new DB(
        ['db'=>$ini['db'], 
        'host'=>$ini['host'],
        'dbName'=>$ini['dbName'],
        'port'=>$ini['port'],
        'user'=>$ini['user'],
        'pass'=>$ini['pass']]);
    
    if(isset($_POST['user'])&&isset($_POST['da'])&&isset($_POST['a'])) {

        $user = $db->ql(
            'SELECT *
            FROM ts_users
            WHERE   Username = ?',
            [$_POST['user']]);

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

            //Assenze
            $assenze = $db->ql(
                'SELECT dayStart, exWhy, exLen_days
                FROM ts_schedules_ex
                WHERE   idDeptUser = :user
                    AND DATE(dayStart) BETWEEN :da AND :a
                ORDER BY dayStart',
                [':user' => $user['id'], ':da'=>$_POST['da'], ':a'=>$_POST['a']]);

            $tmp = [];
            foreach($assenze as $assenza) {

                $day = new DateTime($assenza['dayStart']);

                while($assenza['exLen_days'] > 0) {
                    $tmp[] = ['dayStart' => date_format($day, 'Y-m-d'), 'exWhy' => $assenza['exWhy']];
                    $day->modify('+1 day');
                    $assenza['exLen_days']--;
                }
            }
            $assenze = $tmp;

            //Tabella numero assenze per tipo
            $assenzeStats = $db->ql(
                'SELECT exWhy reason, COUNT(*) tot
                FROM ts_schedules_ex
                WHERE   idDeptUser = :user
                    AND DATE(dayStart) BETWEEN :da AND :a
                GROUP BY exWhy',
                [':user' => $user['id'], ':da'=>$_POST['da'], ':a'=>$_POST['a']]);

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
                [':u'=>$_POST['user'], ':da'=>$_POST['da'], ':a'=>$_POST['a']]);
            
            /*--------------FINE QUERY----------------*/

            //Variabili globali
            $days = [];
            $tot = (int)0;
            $totAssenze = (int)0;
            $totTeorico = (int)0;
            $totSecondsDiurniFestivi = (int)0;
            $totSecondsNotturniFestivi = (int)0;
            $totSecondsDiurniFeriali = (int)0;
            $totSecondsNotturniFeriali = (int)0;
            $giorniSettimana = array('Lun','Mart','Merc','Giov','Ven','Sab','Dom');

            //Calcolo tot. ore teoriche ed inizializzazione giorni
            $da = new DateTime($_POST['da']);
            $a = new DateTime($_POST['a']);
            
            $da_cpy = new DateTime($_POST['da']);
            while(date_format($da_cpy, "Y-m-d") <= date_format($a, "Y-m-d")) {
                $days[date_format($da_cpy, "d/m/Y")] = 
                    ['timbrature' => [],
                    'totSeconds' => (int)0,
                    'totSecondsAssenza' => (int)0,
                    'giustificazione' => '',
                    'totSecondsDiurniFeriali' => (int)0,
                    'totSecondsNotturniFeriali' => (int)0,
                    'totSecondsDiurniFestivi' => (int)0,
                    'totSecondsNotturniFestivi' => (int)0,];

                $totTeorico += $orariSettimanali[dayOfWeek($da_cpy)]*60;

                $da_cpy->modify('+1 day');
            }
            
            //Iterazione su tutte le timbrature del periodo prese a coppie
            for ($i = 0, $iAssenze = 0; $i < count($results); $i+=2) {

            	$in = new DateTime($results[$i]['logTime']);
                $out = new DateTime($results[$i+1]['logTime']);
                $diff = dateDiff($in, $out);
                $date = date_format($in, "d/m/Y");
                
                //Controlli
            	if(date_format($in, "Y-m-d") != date_format($out, "Y-m-d")) echo '<br>Entrata ed uscita su giorni diversi<br>';
            	
                //Inserimento assenze
                do{
                    $assenza = NULL;
                    if($iAssenze < count($assenze)) {

                        $assenza = $assenze[$iAssenze];
                        $dataAssenza = new DateTime($assenza['dayStart']);

                        if(date_format($dataAssenza, "Y-m-d") <= date_format($in, "Y-m-d")) {

                            $secondiTeorici = $orariSettimanali[dayOfWeek($dataAssenza)]*60;

                            if($days[date_format($dataAssenza, "d/m/Y")]['totSecondsAssenza'] != 0) echo 'Segnalate multiple ferie per il giorno '.date_format($dataAssenza, "d/m/Y").': i risultati saranno errati<br>';
                            
                            $days[date_format($dataAssenza, "d/m/Y")]['totSecondsAssenza'] += $secondiTeorici;
                            $days[date_format($dataAssenza, "d/m/Y")]['giustificazione'] .= $assenza['exWhy'];
                            $totAssenze += $secondiTeorici;
                            $iAssenze++;

                        }else
                            $assenza = NULL;
                    }
                //Finchè l'assenza non supera il giorno della timbratura
                }while($assenza);
                
                //Statistiche sulle ore lavorate
                $hIn = intval($in->format('H'));
                $hOut = intval($out->format('H'));
                //Se festivo
                if(isFestivo($in)) {
                    $secondsDiurniFestivi = 0;
                    $secondsNotturniFestivi = 0;
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
                    $secondsDiurniFeriali = 0;
                    $secondsNotturniFeriali = 0;
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
                $days[$date]['totSecondsNotturniFeriali'] += $secondsDiurniFestivi;
                $days[$date]['totSecondsDiurniFestivi'] += $secondsDiurniFestivi;
                $days[$date]['totSecondsNotturniFestivi'] += $secondsNotturniFestivi;
                $tot += $diff;
            }

            //Inserimento eventuali ultime assenze
            while($iAssenze < count($assenze)) {

                $assenza = $assenze[$iAssenze];
                $dataAssenza = new DateTime($assenza['dayStart']);
                $secondiTeorici = $orariSettimanali[dayOfWeek($dataAssenza)]*60;
                $days[date_format($dataAssenza, "d/m/Y")]['totSecondsAssenza'] += $secondiTeorici;
                $days[date_format($dataAssenza, "d/m/Y")]['giustificazione'] += $assenza['exWhy'];
                $totAssenze += $secondiTeorici;
                $iAssenze++;
            }

        }else {
            echo '<pre>Utente non identificato:\n';
            print_r($user);
            echo '</pre>';
        }

    }

    function secondsToHMS($seconds) {
        $seconds = round($seconds);
        return (int)($seconds/ 3600).'h '.(int)($seconds/ 60 % 60).'m';//.(int)($seconds % 60).'s';
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
        return $to->getTimestamp() - $from->getTimestamp();
    }

    /**
     * 
     * @return bool
     */
    function isFestivo(DateTime $day) {
        return ((dayOfWeek($day) == 6) || (in_array($day->format('m-d'), FESTE)));
    }
    
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Timbrature</title>
    <style>
        table {
            width: 100%;
            font-size: 13px;
			border-collapse:collapse;
			border:1px solid #FF0000;
        }
		table td{
			border:1px solid #FF0000;
		}
    </style>
</head>
<body>
    <?php if(!isset($results)) { ?>

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
        </form>

    <?php }else { ?>

        <h1>Piano di lavoro di <?= $user['Username'] ?> dal <?= date_format(date_create($_POST['da']),"d/m/Y"); ?> al <?= date_format(date_create($_POST['a']),"d/m/Y"); ?></h1>
        <table>
            <tr>
                <td>Data</td>
                <td>Timbrature</td>
                <td>Ore lavorate</td>
                <td>Diurne feriali</td>
                <td>Notturne feriali</td>
                <td>Diurne festive</td>
                <td>Notturne festive</td>
                <td>Saldo giornaliero</td>
                <td>Da orario</td>
                <td>Ore assenza giustificate</td>
                <td>Giustificazione assenza</td>
            </tr>
    <?php foreach($days as $date => $day) { ?>
            <tr>
				<td><?= $date.' ('.$giorniSettimana[dayOfWeek(date_create_from_format ('d/m/Y', $date))].')' ?></td>
                
                <td>
            		<?php foreach ($day['timbrature'] as $timbratura)
		             	echo '<p>'.date_format($timbratura['in'],"H:i").' - '.date_format($timbratura['out'],"H:i").'</p>'; ?>
                </td>

                <td><?= secondsToHMS($day['totSeconds']) ?></td>

                <td><?= secondsToHMS($day['totSecondsDiurniFeriali']) ?></td>
                <td><?= secondsToHMS($day['totSecondsNotturniFeriali']) ?></td>
                <td><?= secondsToHMS($day['totSecondsDiurniFestivi']) ?></td>
                <td><?= secondsToHMS($day['totSecondsNotturniFestivi']) ?></td>

                <td>
                    <?php
                    $saldo = ($day['totSeconds'] + $day['totSecondsAssenza']) - ($orariSettimanali[dayOfWeek(date_create_from_format ('d/m/Y', $date))]*60);
                    echo ($saldo < 0?'-':'').secondsToHMS(abs($saldo));
                    ?>
                </td>

                <td><?= secondsToHMS($orariSettimanali[dayOfWeek(date_create_from_format ('d/m/Y', $date))]*60) ?></td>
                
                <td><?= secondsToHMS($day['totSecondsAssenza']) ?></td>

                <td><?= $day['giustificazione'] ?></td>

            </tr>
            <?php 
        	}
        	
            echo '<pre>';
            //print_r($_POST);
            //print_r($user);
            echo '</pre>';
            ?>
    		<tr>
	        	<td>Tot.: <?= count($days) ?></td>
	        	<td></td>
                <td>Tot.: <?= secondsToHMS($tot) ?></td>
                <td>Tot.: <?= secondsToHMS($totSecondsDiurniFeriali) ?></td>
                <td>Tot.: <?= secondsToHMS($totSecondsNotturniFeriali) ?></td>
                <td>Tot.: <?= secondsToHMS($totSecondsDiurniFestivi) ?></td>
                <td>Tot.: <?= secondsToHMS($totSecondsNotturniFestivi) ?></td>
                <td>Tot.: <?php 
                    $saldo = ($tot + $totAssenze) - $totTeorico;
                    echo ($saldo < 0?'-':'').secondsToHMS(abs($saldo));
                    ?>
                </td>
                <td>Tot.: <?= secondsToHMS($totTeorico) ?></td>
                <td>Tot.: <?= secondsToHMS($totAssenze) ?></td>
                <td>Tot.: <?= count($assenze) ?></td>
        	</tr>
        </table>
        
        <h2>Statistiche assenze</h2>
        <table>
        <?php 
        foreach($assenzeStats as $stat) { ?>
            <tr>
                <td><?= $stat['reason'] ?></td>
                <td><?= $stat['tot'] ?></td>
            </tr>
        <?php
        }
        ?>
        </table>
        
        <h2>Statistiche globali</h2>

        <h3>Conteggio giorni</h3>
        <p>Lavorativi: 
            <?php
                $c = 0;
                foreach($days as $day)
                    if(count($day['timbrature']) > 0)
                        $c++;
                echo $c;
            ?>
        </p>
        <p>Assenza: <?= count($assenze) ?></p>
        <p></p>

        <h3>Ore totali</h3>
        <p>Lavorate: <?= secondsToHMS($tot) ?></p>
        <p>Assenza: <?= secondsToHMS($totAssenze) ?></p>
        <p>Teoriche: <?= secondsToHMS($totTeorico) ?></p>
        <p>Saldo: 
            <?php 
            $saldo = ($tot + $totAssenze) - $totTeorico;
            echo ($saldo < 0?'-':'').secondsToHMS(abs($saldo));
            ?>
        </p>

    <?php 
    } 
    ?>

</body>
</html>