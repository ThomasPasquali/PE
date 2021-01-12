<?php
    session_start();

    include_once './lib.php';
    $lib = new Lib($_GET['user']??NULL,NULL,NULL);

    if(!$lib->reportReady) $lib->exitWithMessage('Formato richiesta errato');

    header('Content-type: text/csv');
    header("Content-Disposition: attachment; filename=\"$_REQUEST[user]$_REQUEST[da]_$_REQUEST[a].csv\"");

    $file = [];
    $file[] = ['Data', 'Timbrature', 'Ore lavorate', 'Diurni feriali', 'S. Diurni feriali', ' S. Notturni feriali', 'S. Diurni festivi', 'S. Notturni festivi', 'Saldo giornaliero', 'Da orario', 'Ore assenza giustificate', 'Giustificazione assenza'];

    foreach($lib->days as $date => $day) {
        $tmp = [];
        foreach ($day['timbrature'] as $timbratura)
            $tmp[] = date_format($timbratura['in'],"H:i").' - '.date_format($timbratura['out'],"H:i");
        $teorico = ($lib->isFestivo(date_create_from_format ('d/m/Y', $date))?0:($lib->orarioSettimanale['orario'][$lib->dayOfWeek(date_create_from_format ('d/m/Y', $date))]*60));
        $saldo = ($day['totSeconds'] + $day['totSecondsAssenza']) - $teorico;

        $tmp1 = $day['giustificazione']?[str_replace(CSV_SEP, '', $day['giustificazione'])]:[];
        foreach ($day['workcodes'] as $workcode)
            $tmp1[] = $lib->workcodes[$workcode['workcode']].' - h'.
                            $lib->secondsToHMS($workcode['diff']).
                            ' ('.date_format($timbratura['in'],"H:i").' - '.date_format($timbratura['out'],"H:i").')';

        $file[] = [
            $date.' ('.$lib->giorniSettimana[$lib->dayOfWeek(date_create_from_format ('d/m/Y', $date))].')',
            implode(', ', $tmp),
            $lib->secondsToHMS($day['totSeconds']),
            $lib->secondsToHMS($day['totSecondsDiurniFeriali']),
            $lib->secondsToHMS($day['totSecondsDiurniFeriali']),
            $lib->secondsToHMS($day['totSecondsNotturniFeriali']),
            $lib->secondsToHMS($day['totSecondsDiurniFestivi']),
            $lib->secondsToHMS($day['totSecondsNotturniFestivi']),
            ($saldo < 0?'-':'').$lib->secondsToHMS(abs($saldo)),
            $lib->secondsToHMS($teorico),
            $lib->secondsToHMS($day['totSecondsAssenza']),
            implode(', ', $tmp1)
        ];
    }

    $saldo = ($lib->tot + $lib->totAssenze) - $lib->totTeorico;

    $file[] = [
        count($lib->days),
        $lib->giorniLavorati,
        $lib->secondsToHMS($lib->tot),
        $lib->secondsToHMS($lib->totSecondsDiurniFeriali),
        $lib->secondsToHMS($lib->totSecondsDiurniFeriali),
        $lib->secondsToHMS($lib->totSecondsNotturniFeriali),
        $lib->secondsToHMS($lib->totSecondsDiurniFestivi),
        $lib->secondsToHMS($lib->totSecondsNotturniFestivi),
        ($saldo < 0?'-':'').$lib->secondsToHMS(abs($saldo)),
        $lib->secondsToHMS($lib->totTeorico),
        $lib->secondsToHMS($lib->totAssenze),
        count($lib->assenze)
    ];

    $file[] = [];

    $file[] = ['Tipo di assenza', 'Occorrenze'];
    foreach($lib->assenzeIntereStats as $reason => $t)
        $file[] = [$reason, $t];
    $file[] = [];

    $file[] = ['Tipo di assenza', 'Ore'];
    foreach($lib->assenzeParzialiStats as $reason => $t)
        $file[] = [$reason, $lib->secondsToHMS($t)];
    $file[] = [];

    $file[] = [$_REQUEST['user'].' '.$_REQUEST['da'].' - '.$_REQUEST['a']];

    for ($i=0; $i < count($file); $i++) { 
        echo implode(CSV_SEP, $file[$i]);
        echo "\r\n";
    }

    exit();