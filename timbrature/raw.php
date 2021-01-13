<?php
session_start();

include_once '../lib/db.php';
include_once 'lib.php';
$ini = parse_ini_file("../../PE_ini/DB.ini", TRUE)['timbrature'];
$db = new DB(
    ['db'=>$ini['db'], 
    'host'=>$ini['host'],
    'dbName'=>$ini['dbName'],
    'port'=>$ini['port'],
    'user'=>$ini['user'],
    'pass'=>$ini['pass']]);

if((isset($_REQUEST['da']) && isset($_REQUEST['a'])&&isset($_SESSION['user_timbrature'])) && 
    ($_SESSION['user_timbrature'] != 'admin' || 
    ($_SESSION['user_timbrature'] == 'admin' && isset($_REQUEST['user'])))) {
    
    $username = ($_SESSION['user_timbrature'] == 'admin' ? $_REQUEST['user'] : $_SESSION['user_timbrature']);
    
    $a = new DateTime($_REQUEST['a']);

    $user = $db->ql(
        'SELECT *
        FROM ts_users
        WHERE Username = ?',
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
        $orario = $db->ql('SELECT segments FROM ts_timetables WHERE SUBSTR(timeName, 11) LIKE ? AND LENGTH(timeShortName) >= 2', [$user['Username'].'%']);
        
        if(count($orario) <= 0) Lib::exitWithMessage('Orario non trovato');

        $orario = json_decode($orario[0]['segments'], true)[0];
    
        for($i=1,$n=0; $n<count($orario['Days']); $n++,$i=(($i+1)%count($orario['Days'])))
            $orariSettimanali[] = HMStoMinutes($orario['Days'][$i]['ServiceChunk']['Duration']);

        $orariSettimanali = [
            'Luned&igrave;' => $orariSettimanali[0],
            'Marted&igrave;' => $orariSettimanali[1],
            'Mercoled&igrave;' => $orariSettimanali[2],
            'Gioved&igrave;' => $orariSettimanali[3],
            'Venerd&igrave;' => $orariSettimanali[4],
            'Sabato' => $orariSettimanali[5],
            'Domenica' => $orariSettimanali[6]
        ];
        	
        //Assenze
        $assenze = $db->ql(
            'SELECT dayStart, exWhy, exLen_days
            FROM ts_schedules_ex
            WHERE   idDeptUser = :user
                AND DATE(dayStart) BETWEEN :da AND :a
            ORDER BY dayStart',
            [':user' => $user['id'], ':da'=>$_REQUEST['da'], ':a'=>$_REQUEST['a']]);

        //Timbrature
        $timbrature = $db->ql(
            'SELECT e.logNote AS note, r.*
            FROM ts_records r
            JOIN ts_users u ON u.idUser = r.idUser
            LEFT JOIN ts_records_extra e ON r.idLog = e.idLog
            WHERE   u.Username LIKE :u
                AND DATE(r.logTime) BETWEEN :da AND :a
                AND r.valid = 1
            ORDER BY r.logTime',
            [':u'=>$username, ':da'=>$_REQUEST['da'], ':a'=>$_REQUEST['a']]);
    }
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

function secondsToHMS($seconds) {
    $seconds = round($seconds);
    $s = abs((int)($seconds/ 60 % 60));
    return (int)($seconds/ 3600).':'.(strlen($s) < 2?'0'.$s:$s);//.(int)($seconds % 60).'s';
}

function HMStoMinutes($hms){
    $hms = explode(':', $hms);
    return ($hms[0]*60) + ($hms[1]) + ($hms[2]/60);
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Timbrature</title>
    <style>
        .flex {
            display: flex;
            justify-content: center;
        }
        h3,p {
            text-align: center;
        }
    </style>
</head>
<body>
    <h3>Orario settimanale</h3>
    <div><?php foreach ($orariSettimanali as $giorno => $minuti) echo '<p>'.$giorno.': '.secondsToHMS($minuti*60).'</p>'; ?></div>

    <h3>Assenze</h3>
    <div><?php foreach ($assenze as $assenza) echo "<div class=\"flex\">$assenza[dayStart] $assenza[exWhy] $assenza[exLen_days](giorni)</div>"; ?></div>

    <h3>Timbrature</h3>
    <div><?php foreach ($timbrature as $timbratura) echo "<div class=\"flex\"><p>$timbratura[logTime]         <strong>".($timbratura['logAction']==0?'Entrata':'Uscita')."</strong>$timbratura[note]</p></div>"; ?></div>
<!--Array ( [note] => [idLog] => 95179 [idUser] => 7 [logTime] => 2019-10-01 06:46:00 [logAction] => 0 [logCode] => 0 [logMode] => 0 [deviceNum] => 1 [valid] => 1 ) -->
</body>