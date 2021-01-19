<?php
	session_start();

    include_once './lib.php';

    $lib = new Lib(
        isset($_REQUEST['user']) ? $_REQUEST['user'] : $_SESSION['user_timbrature']??NULL,
        $_POST['password']??NULL,
        $_REQUEST['cambia_user']??NULL
    );
?>
<html>
<head>
    <script src="../lib/jquery-3.5.1.min.js"></script>
    <script src="../lib/jquery.qtip.min.js"></script>
    <script src="./index.js" type="text/javascript" assert></script>

    <link rel="stylesheet" type="text/css" href="table.css">
    <?= (!$lib->reportReady || !isset($_SESSION['user_timbrature'])) ? '<link rel="stylesheet" href="./index.css">' : ''; ?>

    <title>Report personale</title>
</head>
<body>
    <p id="made-by">Made by Thomas P.</p>


    <?php /***********LOGIN************/
        if(!isset($_SESSION['user_timbrature'])) { ?>
	
            <form action="" method="POST">
                <label>Password utente: </label>
                <center><input type="password" name="password" style="width:10%;margin-top:30px;" autofocus></center>
            </form>
        
    <?php /********END LOGIN***********/
        /***********REPORT SELECTION************/
        }else if(!$lib->reportReady) {
    ?>
            <div class="container">
                <form action="" method="POST" target="_blank">
                    <label>Dipendente <?= ($_SESSION['user_timbrature'] != 'admin' ? '<br>'.$_SESSION['user_timbrature'] : '') ?></label>
                    <?php
                    /***********SELECTION DIPENDENTE/I************/
                    if($_SESSION['user_timbrature'] == 'admin') {
                    ?>
                        <select name="user">
                            <option default>--seleziona--</option>
                            <?php foreach($lib->getUsers() as $u) echo "<option value=\"$u[Username]\">$u[Username]</option>"; ?>
                        </select>
                        <label>Dipendenti selezionati</label>
                    <?php
                    /***********END SELECTION DIPENDENTE/I************/
                    }
                    ?>
                    <div id="dipendentiSelezionati">
                        <?= $_SESSION['user_timbrature'] == 'admin' ? '' : "<input style=\"display:none;\" name=\"dipendente_0\" value=\"$_SESSION[user_timbrature]\" readonly>" ?>                    </div>
                    <label>Da</label>
                    <input type="date" name="da" required autofocus>
                    <label>A</label>
                    <input type="date" name="a" required>
                    <button type="button" onclick="submitFormForReport();">Crea report</button>
                    <button type="button" onclick="submitFormForRaw();">Visualizza dati</button>
                    <button type="button" onclick="this.form.action='?cambia_user'; this.form.target = '_self'; this.form.submit();">Esci</button>
                </form>
            </div>
                          
    <?php /*********END REPORT SELECTION**********/  
        }else {
          /***********SELEZIONE ORARIO************/

          if(count($lib->orariSettimanali) > 1) { ?>
            <form id="formOrari" action="" method="POST">
                <label>Orario settimanale: </label>
                <input type="hidden" name="da" value="<?= $_REQUEST['da'] ?>">
                <input type="hidden" name="a" value="<?= $_REQUEST['a'] ?>">
                <input type="hidden" name="user" value="<?= $_REQUEST['user'] ?>">
                <select name="orario" onchange="$('#formOrari').submit();">
                <?php
                foreach ($lib->orariSettimanali as $id => $orario) {
                    echo '<option value="'.$id.'" '.(isset($_REQUEST['orario'])&&$_REQUEST['orario']==$id?'selected="selected"':'').'>'.$orario['nome'].' ';
                    $i = 0;
                    foreach ($orario['orario'] as $ore)
                        echo LETTERE_SETTIMANA[$i++].' '.$lib->secondsToHMS($ore*60).($i<7?' | ':'');
                    echo '</option>';
                }
                ?>
                </select>
            </form>
    <?php /***********END SELEZIONE ORARIO************/
        } 
          /***********REPORT************/
    ?>

        <h3>Piano di lavoro di <?= $lib->user['Username'] ?> dal <?= date_format(date_create($_REQUEST['da']),"d/m/Y"); ?> al <?= date_format(date_create($_REQUEST['a']),"d/m/Y"); ?></h3>
        <?php 
        $urlExport = 'csv.php?export=csv&user='.$lib->username;
        foreach ($_REQUEST as $key => $val)
            $urlExport .= "&$key=$val";
        ?>
        
        <div id="menu">
            <a href="<?= $urlExport ?>">Esporta in CSV</a>
        	<a href="#" onclick="window.close();">Chiudi pagina</a>
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
    <?php  foreach($lib->days as $dateStr => $day) {
                $date = date_create_from_format('d/m/Y', $dateStr);
    		?>
            <tr <?= (($lib->isFestivo($date) || $lib->isNonLavorativo($date))?'class="festivo"':'') ?>>
				<td><?= $dateStr.' ('.$lib->giorniSettimana[$lib->dayOfWeek($date)].')' ?></td>
                
                <td>
            		<?php 
            		foreach ($day['timbrature'] as $timbratura)
            			echo '<p>'.
              				date_format($timbratura['in'],"H:i").($timbratura['note_in'] ? " ($timbratura[note_in])<br>" : '').
              				' - '.
            				date_format($timbratura['out'],"H:i").($timbratura['note_out'] ? " ($timbratura[note_out])<br>": '').
            				'</p>';
            		?>
                </td>

                <td title="Ore e minuti lavorati"><?= $lib->secondsToHMS($day['totSeconds']) ?></td>

                <td title="Ore e minuti lavorati Diurni Feriali"><?= $lib->secondsToHMS($day['totSecondsDiurniFeriali']) ?></td>
                <td title="Straordinari ore e minuti lavorati Diurni Feriali"><?= $lib->secondsToHMS($day['totSecondsSDiurniFeriali']) ?></td>
                <td title="Straordinari ore e minuti lavorati Notturni Feriali"><?= $lib->secondsToHMS($day['totSecondsNotturniFeriali']) ?></td>
                <td title="Straordinari ore e minuti lavorati Diurni Festivi"><?= $lib->secondsToHMS($day['totSecondsDiurniFestivi']) ?></td>
                <td title="Straordinari ore e minuti lavorati Notturni Festivi"><?= $lib->secondsToHMS($day['totSecondsNotturniFestivi']) ?></td>

                <td title="Saldo ore e minuti">
                    <?php
                    $saldo = ($day['totSeconds'] + $day['totSecondsAssenza']) - $day['teorico'];
                    echo ($saldo < 0?'-':'').$lib->secondsToHMS(abs($saldo));
                    ?>
                </td>

                <td title="Ore e minuti da orario"><?= $lib->secondsToHMS($day['teorico']) ?></td>
                
                <td title="Ore e minuti assenza giustificata"><?= $lib->secondsToHMS($day['totSecondsAssenza']) ?></td>

                <td title="Giustificazione assenza"><?php 
                		$tmp = $day['giustificazione']?[$day['giustificazione']]:[];
                		foreach ($day['workcodes'] as $workcode)
                			$tmp[] = 
                				$lib->workcodes[$workcode['workcode']].' - h'.
                				$lib->secondsToHMS($workcode['diff']).
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
	        	<td title="Totale giorni periodo"><?= count($lib->days) ?></td>
	        	<td title="Totale giorni con timbrature"><?= $lib->giorniLavorati ?></td>
                <td title="Totale ore e minuti lavorate"><?= $lib->secondsToHMS($lib->tot) ?></td>
                <td title="Totale ore e minuti Diurne Feriali"><?= $lib->secondsToHMS($lib->totSecondsDiurniFeriali) ?></td>
                <td title="Totale straordinari ore e minuti Diurne Feriali"><?= $lib->secondsToHMS($lib->totSecondsSDiurniFeriali) ?></td>
                <td title="Totale straordinari ore e minuti Notturne Feriali"><?= $lib->secondsToHMS($lib->totSecondsNotturniFeriali) ?></td>
                <td title="Totale straordinari ore e minuti Diurne Festive"><?= $lib->secondsToHMS($lib->totSecondsDiurniFestivi) ?></td>
                <td title="Totale straordinari ore e minuti Notturne Festive"><?= $lib->secondsToHMS($lib->totSecondsNotturniFestivi) ?></td>
                <td title="Saldo ore e minuti">
                	<?php 
                    $saldo = ($lib->tot + $lib->totAssenze) - $lib->totTeorico;
                    echo '<strong style="font-size:1.2em;">'.($saldo < 0?'-':'').$lib->secondsToHMS(abs($saldo)).'</strong>';
                    ?>
                </td>
                <td title="Totale ore e minuti teoriche"><?= $lib->secondsToHMS($lib->totTeorico) ?></td>
                <td title="Totale assenze ore e minuti come se lavorate"><?= $lib->secondsToHMS($lib->totAssenze) ?></td>
                <td title="Totale giorni di assenze giustificate come se lavorate"><?= count($lib->assenze) ?></td>
        	</tr>
        </table>
        
        <h2>Statistiche assenze</h2>
        <table>
            <tr>
                <td>Tipo assenza (intera)</td>
                <td>Giorni</td>
            </tr>
        <?php 
        foreach($lib->assenzeIntereStats as $reason => $t) { ?>
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
        foreach($lib->assenzeParzialiStats as $reason => $t) { ?>
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
        <p>Lavorativi: <?= $lib->giorniLavorati ?></p>
        <p>Assenza: <?= count($lib->assenze) ?></p>
        <p></p>

        <h3>Ore totali</h3>
        <p>Lavorate: <?= $lib->secondsToHMS($lib->tot) ?></p>
        <p>Assenza: <?= $lib->secondsToHMS($lib->totAssenze) ?></p>
        <p>Teoriche: <?= $lib->secondsToHMS($lib->totTeorico) ?></p>
        <p>Saldo: <strong style="font-size:1.2em;">
            <?php 
            $saldo = ($lib->tot + $lib->totAssenze) - $lib->totTeorico;
            echo ($saldo < 0?'-':'').$lib->secondsToHMS(abs($saldo));
            ?>
        </strong></p>

    <?php 
    } 
    ?>

</body>
</html>
