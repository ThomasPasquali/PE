<?php
	session_start();

    include_once './lib.php';

    $dipendenti = [];
    foreach($_REQUEST as $key => $val)
        if(substr($key, 0, strlen('dipendente_')) === 'dipendente_')
            $dipendenti[] = $val;

    if(count($dipendenti) <= 0)
        Lib::exitWithMessage('Invalid request');

    $libs = [];
    foreach($dipendenti as $dipendente)
        $libs[$dipendente] = new Lib($dipendente,NULL,NULL);
?>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="table.css">
        <script src="./index.js" type="text/javascript" assert></script>
        <style>
            table tr:nth-child(odd) {
                background-color: #CCCCCC;
            }
        </style>
        <title>Report cumulativo</title>
    </head>
    <body>
        <h3>Piano di lavoro di <?= implode(', ', $dipendenti) ?> dal <?= date_format(date_create($_REQUEST['da']),"d/m/Y"); ?> al <?= date_format(date_create($_REQUEST['a']),"d/m/Y"); ?></h3>
        <table id="tabellona">
            <tr>
                <td>Dipendente</td>
                <td>Ore lavorate</td>
                <td>Diurni feriali</td>
                <td>S. diurni feriali</td>
                <td>S. notturni feriali</td>
                <td>S. diurni festivi</td>
                <td>S. notturni festivi</td>
                <td>Saldo periodo</td>
                <td>Da orario</td>
                <td>Ore assenza giustificate</td>
            </tr>
            <?php
            $tot=$totGiorniLavorati=$totGiorniAssenze=$totSecondsDiurniFeriali=$totSecondsSDiurniFeriali=$totSecondsNotturniFeriali=$totSecondsDiurniFestivi=$totSecondsNotturniFestivi=$totSaldo=$totTeorico=$totAssenze=0;
            $assenzeIntereStats = [];
            $assenzeParzialiStats = [];
            foreach($libs as $dipendente => $lib) {
                $saldo = ($lib->tot + $lib->totAssenze) - $lib->totTeorico;
                $tot += $lib->tot;
                $totSecondsDiurniFeriali += $lib->totSecondsDiurniFeriali;
                $totSecondsSDiurniFeriali += $lib->totSecondsSDiurniFeriali;
                $totSecondsNotturniFeriali += $lib->totSecondsNotturniFeriali;
                $totSecondsDiurniFestivi += $lib->totSecondsDiurniFestivi;
                $totSecondsNotturniFestivi += $lib->totSecondsNotturniFestivi;
                $totSaldo += $saldo;
                $totTeorico += $lib->totTeorico;
                $totAssenze += $lib->totAssenze;
                foreach($lib->assenzeIntereStats as $reason => $t) $assenzeIntereStats[$reason] = ($assenzeIntereStats[$reason]??0)+$t;
                foreach($lib->assenzeParzialiStats as $reason => $t) $assenzeParzialiStats[$reason] = ($assenzeParzialiStats[$reason]??0)+$t;
                $totGiorniLavorati += $lib->giorniLavorati;
                $totGiorniAssenze += count($lib->assenze);
            ?>    
                <tr>
                    <td title="Cognome Nome"><?= $dipendente ?></td>
                    <td title="Ore e minuti lavorate"><?= $lib->secondsToHMS($lib->tot) ?></td>
                    <td title="Ore e minuti Diurne Feriali"><?= $lib->secondsToHMS($lib->totSecondsDiurniFeriali) ?></td>
                    <td title="Straordinari ore e minuti Diurne Feriali"><?= $lib->secondsToHMS($lib->totSecondsSDiurniFeriali) ?></td>
                    <td title="Straordinari ore e minuti Notturne Feriali"><?= $lib->secondsToHMS($lib->totSecondsNotturniFeriali) ?></td>
                    <td title="Straordinari ore e minuti Diurne Festive"><?= $lib->secondsToHMS($lib->totSecondsDiurniFestivi) ?></td>
                    <td title="Straordinari ore e minuti Notturne Festive"><?= $lib->secondsToHMS($lib->totSecondsNotturniFestivi) ?></td>
                    <td title="Saldo ore e minuti">
                        <?php 
                        
                        echo '<strong style="font-size:1.2em;">'.($saldo < 0?'-':'').$lib->secondsToHMS(abs($saldo)).'</strong>';
                        ?>
                    </td>
                    <td title="Totale ore e minuti teoriche"><?= $lib->secondsToHMS($lib->totTeorico) ?></td>
                    <td title="Totale assenze ore e minuti come se lavorate"><?= $lib->secondsToHMS($lib->totAssenze) ?></td>
                </tr>
            <?php
            }
            /**
             * Statistiche assenze (con parziali)
             * Anche tutto il resto
             */
            ?>
            <tr>
                <td></td>
                <td title="Totale ore e minuti lavorate"><?= $lib->secondsToHMS($tot) ?></td>
                <td title="Totale ore e minuti Diurne Feriali"><?= $lib->secondsToHMS($totSecondsDiurniFeriali) ?></td>
                <td title="Totale straordinari ore e minuti Diurne Feriali"><?= $lib->secondsToHMS($totSecondsSDiurniFeriali) ?></td>
                <td title="Totale straordinari ore e minuti Notturne Feriali"><?= $lib->secondsToHMS($totSecondsNotturniFeriali) ?></td>
                <td title="Totale straordinari ore e minuti Diurne Festive"><?= $lib->secondsToHMS($totSecondsDiurniFestivi) ?></td>
                <td title="Totale straordinari ore e minuti Notturne Festive"><?= $lib->secondsToHMS($totSecondsNotturniFestivi) ?></td>
                <td title="Saldo totale ore e minuti"><strong style="font-size:1.2em;"><?= $lib->secondsToHMS($totSaldo) ?></strong></td>
                <td title="Totale ore e minuti teoriche"><?= $lib->secondsToHMS($totTeorico) ?></td>
                <td title="Totale assenze ore e minuti come se lavorate"><?= $lib->secondsToHMS($totAssenze) ?></td>
            </tr>
        </table>

        <h2>Statistiche assenze</h2>
        <table>
            <tr>
                <td>Tipo assenza (intera)</td>
                <td>Giorni</td>
            </tr>
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
        <br>
        <table>
            <tr>
                <td>Tipo assenza (parziale)</td>
                <td>Ore</td>
            </tr>
        <?php
        foreach($assenzeParzialiStats as $reason => $t) { ?>
            <tr>
                <td><?= $reason ?></td>
                <td><?= $lib->secondsToHMS($t) ?></td>
            </tr>
        <?php
        }
        ?>
        </table>
        
        <h2>Statistiche globali</h2>

        <h3>Conteggio giorni</h3>
        <p>Lavorati: <?= $totGiorniLavorati ?></p>
        <p>Assenze: <?= $totGiorniAssenze ?></p>
        <p></p>

    </body>
</html>

