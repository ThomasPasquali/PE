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
    </head>
    <body>
        <table id="tabellona">
            <tr>
                <td>Dipendente</td>
                <td>Ore lavorate</td>
                <td>Diurni feriali</td>
                <td>S. diurni feriali</td>
                <td>S. notturni feriali</td>
                <td>S. diurni festivi</td>
                <td>S. notturni festivi</td>
                <td>Saldo giornaliero</td>
                <td>Da orario</td>
                <td>Ore assenza giustificate</td>
            </tr>
            <?php
            foreach($libs as $dipendente => $lib) {
            ?>    
                <tr>
                    <td><?= $dipendente ?></td>
                    <td><?= $lib->secondsToHMS($lib->tot) ?></td>
                </tr>
            <?php
            }
            /**
             * Statistiche assenze (con parziali)
             * Anche tutto il resto
             */
            ?>
        </table>
    </body>
</html>

