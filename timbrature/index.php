<?php
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
            $orariSettimanali = [];
            $res = $db->ql('SELECT weekTime FROM ts_timetables WHERE SUBSTR(timeName, 11) LIKE ?', [$user['Username'].'%'])[0]['weekTime'];
            for($i = 8; $i < strlen($res); $i+=8)
                $orariSettimanali[] = (int)substr($res, $i+4, 4);

            $assenze = $db->ql(
                'SELECT dayStart, exWhy
                FROM ts_schedules_ex
                WHERE   idDeptUser = :user
                    AND dayStart BETWEEN :da AND :a
                ORDER BY dayStart',
                [':user' => $user['id'], ':da'=>$_POST['da'], ':a'=>$_POST['a']]);

            //Tabella numero assenze per tipo
            $assenzeStats = $db->ql(
                'SELECT exWhy reason, COUNT(*) tot
                FROM ts_schedules_ex
                WHERE   idDeptUser = :user
                    AND dayStart BETWEEN :da AND :a
                GROUP BY exWhy',
                [':user' => $user['id'], ':da'=>$_POST['da'], ':a'=>$_POST['a']]);

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

            //Calcolo tot. ore teoriche
            $totTeorico = (int)0;
            $da = new DateTime($_POST['da']);
            $a = new DateTime($_POST['a']);
            while(date_format($da, "Y-m-d") < date_format($a, "Y-m-d")) {
                $totTeorico += $orariSettimanali[dayOfWeek($da)]*60;
                $da->modify('+1 day');
            }
            
            $days = [];
            $tot = (int)0;
            $totAssenze = (int)0;
            for ($i = 0, $iAssenze = 0; $i < count($results); $i+=2) {

            	$in = new DateTime($results[$i]['logTime']);
            	$out = new DateTime($results[$i+1]['logTime']);
            	$diff = $in->diff($out);
            	$daysIndex = date_format($in, "d/m/Y");
                
                //Controlli
            	if(date_format($in, "Y-m-d") != date_format($out, "Y-m-d")) echo 'Entrata ed uscita su giorni diversi<br>';
            	if($diff->d > 0) echo 'Piu\' di un giorno di differenza<br>';
            	
                //Inserimento assenze
                do{
                    $assenza = NULL;
                    if($iAssenze < count($assenze)) {

                        $assenza = $assenze[$iAssenze];
                        $dataAssenza = new DateTime($assenza['dayStart']);

                        if(date_format($dataAssenza, "Y-m-d") <= date_format($in, "Y-m-d")) {

                            $secondiTeorici = $orariSettimanali[dayOfWeek($dataAssenza)]*60;

                            if(isset($days[date_format($dataAssenza, "m/d/Y")])) echo 'Segnalate multiple ferie per il giorno '.date_format($dataAssenza, "m/d/Y").': i risultati saranno errati<br>';
                            
                            $days[date_format($dataAssenza, "m/d/Y")] = ['timbrature' => [],
                                                                        'totSeconds' => (int)0,
                                                                        'totSecondsAssenza' => $secondiTeorici,
                                                                        'giustificazione' => $assenza['exWhy']];
                            $totAssenze += $secondiTeorici;
                            $iAssenze++;

                        }else
                            $assenza = NULL;
                    }
                }while($assenza);
            	
                //Inserimento giornata
                if(!isset($days[$daysIndex]))
                    $days[$daysIndex] = ['timbrature' => [], 'totSeconds' => (int)0, 'totSecondsAssenza' => (int)0, 'giustificazione' => ''];
            	$days[$daysIndex]['timbrature'][] = ['in' => $in, 'out' => $out];
            	$duration = (int)((($diff->h)*60*60) + (($diff->m)*60) + (($diff->s)));
            	$days[$daysIndex]['totSeconds'] += $duration;
                $tot += $duration;
            }

            //Inserimento eventuali ultime assenze
            while($iAssenze < count($assenze)) {

                $assenza = $assenze[$iAssenze];
                $dataAssenza = new DateTime($assenza['dayStart']);
                $secondiTeorici = $orariSettimanali[dayOfWeek($dataAssenza)]*60;
                $days[date_format($dataAssenza, "Y-m-d")] = ['timbrature' => [],
                                                            'totSeconds' => (int)0,
                                                            'totSecondsAssenza' => $secondiTeorici,
                                                            'giustificazione' => $assenza['exWhy']];
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
        return (int)($seconds/ 3600).'h '.(int)($seconds/ 60 % 60).'m '.(int)($seconds % 60).'s';
    }

    function dayOfWeek(DateTime $date) {
        return ((int)date('N', strtotime(date_format($date, "Y-m-d")))-1);
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
                <td>Ore assenza giustificate</td>
                <td>Giustificazione assenza</td>
            </tr>
    <?php foreach($days as $date => $day) { ?>
            <tr>
				<td><?= $date ?></td>
            	<td>
            		<?php foreach ($day['timbrature'] as $timbratura)
		             	echo '<p>'.date_format($timbratura['in'],"H:i").' - '.date_format($timbratura['out'],"H:i").'</p>'; ?>
            	</td>
                <td><?= secondsToHMS($day['totSeconds']) ?></td>
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
        
        <p>Tot. ore lavorate: <?= secondsToHMS($tot) ?></p>
        <p>Tot. ore assenza: <?= secondsToHMS($totAssenze) ?></p>
        <p>Tot. ore teoriche: <?= secondsToHMS($totTeorico) ?></p>
        <p>Bilancio: 
            <?php 
            $bilancio = ($tot + $totAssenze) - $totTeorico;
            echo ($bilancio < 0?'-':'').secondsToHMS(abs($bilancio));
            ?>
        </p>

    <?php 
    } 
    ?>

</body>
</html>