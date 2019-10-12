<?php
include_once '../lib/db.php';
	//$db = new DB(['db'=>'mysql', 'host'=>'127.0.0.1', 'dbName'=>'timbrature', 'port'=>'3306', 'user'=>'pe-webapp', 'pass'=>'waa']);
	$db = new DB(['db'=>'mysql', 'host'=>'127.0.0.1', 'dbName'=>'iaccess_ts', 'port'=>'3306', 'user'=>'root', 'pass'=>'']);
	if(isset($_POST['nome'])&&isset($_POST['cognome'])&&isset($_POST['da'])&&isset($_POST['a'])) {

        $user = $db->ql(
            'SELECT *
            FROM ts_users
            WHERE   Name_First = ?
                AND Name_Last = ?',
            [$_POST['nome'], $_POST['cognome']]);

        if(count($user) == 1) {

            $user = $user[0];
            $results = $db->ql(
                'SELECT d.devName, r.*
                FROM ts_records r
                JOIN ts_users u ON u.idUser = r.idUser
                LEFT JOIN ts_devices d ON d.devNum = r.deviceNum 
                WHERE   u.Name_First LIKE :n
                    AND u.Name_Last LIKE :c
                    AND r.logTime BETWEEN :da AND :a
					AND r.valid = 1
                ORDER BY r.logTime',
                [':n'=>$_POST['nome'], ':c'=>$_POST['cognome'], ':da'=>$_POST['da'], ':a'=>$_POST['a']]);
            
            $days = [];
            $tot = (int)0;
            for ($i = 0; $i < count($results); $i+=2) {
            	
            	$in = new DateTime($results[$i]['logTime']);
            	$out = new DateTime($results[$i+1]['logTime']);
            	$diff = $in->diff($out);
            	$daysIndex = date_format($in, "d/m/Y");
            	
            	if(date_format($in, "Y-m-d") != date_format($out, "Y-m-d")) echo "Entrata ed uscita su giorni diversi";
            	if($diff->d > 0) echo 'Piu\' di un giorno di differenza';
            	
            	if(!isset($days[$daysIndex]))
            		$days[$daysIndex] = ['timbrature' => [], 'totSeconds' => (int)0];
            	
            	$days[$daysIndex]['timbrature'][] = ['in' => $in, 'out' => $out];
            	$duration = (int)((($diff->h)*60*60) + (($diff->m)*60) + (($diff->s)));
            	$days[$daysIndex]['totSeconds'] += $duration;
            	$tot += $duration;
            }
            
        }else {
            echo '<pre>Utente non identificato:\n';
            print_r($user);
            echo '</pre>';
        }

        function secondsToHMS($seconds) {
        	$seconds = round($seconds);
        	return (int)($seconds/ 3600).'h '.(int)($seconds/ 60 % 60).'m '.(int)($seconds % 60).'s';
        }
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
            <input type="text" name="nome" placeholder="Nome...">
            <input type="text" name="cognome" placeholder="Cognome...">
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
                <td>Totale</td>
            </tr>
    <?php foreach($days as $date => $day) { ?>
            <tr>
				<td><?= $date ?></td>
            	<td>
            		<?php foreach ($day['timbrature'] as $timbratura)
		             	echo '<p>'.date_format($timbratura['in'],"H:i").' - '.date_format($timbratura['out'],"H:i").'</p>'; ?>
            	</td>
            	<td><?= secondsToHMS($day['totSeconds']) ?></td>
            </tr>
            <?php 
        	}
        	
        echo '<pre>';
        //print_r($_POST);
        //print_r($user);
        //print_r($days);
        echo '</pre>';
    ?>
    		<tr>
	        	<td>Tot. giorni: <?= count($days) ?></td>
	        	<td></td>
	        	<td>Tot. ore: <?= secondsToHMS($tot) ?></td>
        	</tr>
        </table>
    <?php } ?>

</body>
</html>