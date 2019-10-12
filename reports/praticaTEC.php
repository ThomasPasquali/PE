<?php
    include_once '../controls.php';
    $c = new Controls();

    if(!($c->logged()&&$c->check(['id'], $_REQUEST))){
        header('Location: ../index.php?err=Utente non loggato');
        exit();
    }

    if($c->check(['id'], $_REQUEST)||$c->check(['tipo', 'anno', 'numero'], $_REQUEST)){

        $datiGenericiPraticaID = $_REQUEST['id']??'';
        if(!$datiGenericiPraticaID)
            $datiGenericiPraticaID = $c->getPraticaID($_REQUEST['tipo']??'', $_REQUEST['anno']??'', $_REQUEST['numero']??'', $_REQUEST['barrato']??'', 'tec');

            $datiGenericiPratica = NULL;
            if($datiGenericiPraticaID)
                $datiGenericiPratica = $c->db->ql('SELECT * FROM tec_pratiche_view WHERE ID = ?',[$datiGenericiPraticaID])[0];

                if($datiGenericiPratica !== NULL){

                    $edifici = $c->db->ql(
                        'SELECT e.ID, e.Mappali
                        FROM tec_edifici_pratiche ep
                        JOIN edifici_view e ON e.ID = ep.Edificio
                        WHERE Pratica = ?',
                        [$datiGenericiPratica['ID']]);

                    $intestatariPersone = $c->db->ql(
                        'SELECT ip.ID, ip.Nome, ip.Cognome
                        FROM tec_intestatari_persone_pratiche ipp
                        JOIN intestatari_persone ip ON ip.ID = ipp.Persona
                        WHERE ipp.Pratica = ?',
                        [$datiGenericiPratica['ID']]);

                    $intestatariSocieta = $c->db->ql(
                        'SELECT i.ID, i.Intestazione
                        FROM tec_intestatari_societa_pratiche isp
                        JOIN intestatari_societa i ON i.ID = isp.Societa
                        WHERE isp.Pratica = ?',
                        [$datiGenericiPratica['ID']]);
                    
                    $tecnici = $c->db->ql(
                    	'SELECT t.ID, t.Nome, t.Cognome, t.Codice_fiscale, t.Partita_iva
                        FROM tec_tecnici_pratiche tp
                        JOIN tecnici t ON t.ID = tp.Tecnico
                        WHERE tp.Pratica = ?',
                    		[$datiGenericiPratica['ID']]);

                }else{
                    echo '<span class="errorTitle">Nessun risultato con parametri:
                                                                ID: '.($_REQUEST['id']??'').'
                                                                Tipo: '.($_REQUEST['tipo']??'').'
                                                                Anno: '.($_REQUEST['anno']??'').'
                                                                Numero: '.($_REQUEST['numero']??'').'
                                                                Barrato: '.($_REQUEST['barrato']??'').'</span>';
                    exit();
                }
    }else{
        echo '<span class="errorTitle">Richiesta non valida</span>';
        exit();
    }

?>
  <head>
    <title>Pratica <?= $datiGenericiPratica['Sigla'] ?></title>
    <link rel="stylesheet" type="text/css" href="../css/report_pratica.css">
  </head>
  <body>
    	<div id="intestazione">
           <div id="titoli">
                <h1>Comune di Canale d'Agordo</h1>
                <h2>Ufficio tecnico</h2>
                <h3>Interrogazione edificio all'archivio pratiche edilizie</h3>
           		<h4>ID pratica: <?= $datiGenericiPratica['ID'] ?><br>Sigla: <?= $datiGenericiPratica['Sigla'] ?></h4>
           </div>
            <a href="../"><img src="../imgs/logo.jpg" id="logo" alt="logo"></a>
        </div>
        
        <p class="sottotitolo">Informazioni generali:</p>
        <div id="generalita">
        	<div id="anno-numero-barrato">
        		<p><span>Anno:</span> <?= $datiGenericiPratica['Anno'] ?></p>
                <p><span>Numero:</span> <?= $datiGenericiPratica['Numero'] ?></p>
                <p><span>Barrato:</span> <?= $datiGenericiPratica['Barrato'] ?></p>
        	</div>
        	
    		<div id="localita-protocollo">
    			<p><span>Localit&aacute;:</span> <?= $datiGenericiPratica['Stradario'] ?></p>
                <p><span>Protocollo:</span> <?= $datiGenericiPratica['Protocollo'] ?></p>
    		</div>
            
            <p><span>Intervento:</span> <?= $datiGenericiPratica['Intervento'] ?></p>
            
            <p><span>Fogli-mappali: </span>
            <?php
            $i = 0;
            foreach ($edifici as $edificio){
              $sep = $i > 0 ? ' - ' : '';
              echo "$sep$edificio[Mappali]<a href=\"edificio.php?edificio=$edificio[ID]\">(Ed. $edificio[ID])</a>";
              $i++;
            }
            ?>
            </p>
            
            <p><span>Subalterni:</span> <?= $datiGenericiPratica['Subalterni'] ?></p>
        </div>
        <p class="sottotitolo">Persone:</p>
        <div id="persone">
        	<p><span>Intestatari persone:</span>
            <?php
            $i = 0;
            foreach ($intestatariPersone as $intestatario){
              $sep = $i > 0 ? ' - ' : '';
              echo "$sep<a href=\"anagrafica.php?persona=$intestatario[ID]\">$intestatario[Cognome] $intestatario[Nome]</a>";
              $i++;
            }
            ?>
            </p>
            
            <p><span>Intestatari societ&aacute;:</span>
            <?php
            $i = 0;
            foreach ($intestatariSocieta as $intestatario){
              $sep = $i > 0 ? ' - ' : '';
              echo "$sep<a href=\"anagrafica.php?societa=$intestatario[ID]\">$intestatario[Intestazione]</a>";
              $i++;
            }
            ?>
            </p>
            
            <p><span>Tecnici:</span>
              <?php
              $i = 0;
              foreach ($tecnici as $tecnico) {
              	  $sep = $i > 0 ? ' - ' : '';
                  echo "$sep<a href=\"anagrafica.php?tecnico=$tecnico[ID]\">$tecnico[Cognome] $tecnico[Nome] ($tecnico[Codice_fiscale] - $tecnico[Partita_iva])</a>";
                  $i++;
              }
              ?>
            </p>
            <!-- TODO <p><span>Direttore lavori:</span>
            <?php
              $direttoreLavori = $c->getDatiTecnico($datiGenericiPratica['Direzione_lavori']);
              if($direttoreLavori)
                echo "<a href=\"anagrafica.php?tecnico=$direttoreLavori[ID]\">$direttoreLavori[Cognome] $direttoreLavori[Nome] ($direttoreLavori[Codice_fiscale] - $direttoreLavori[Partita_iva])</a>"
            ?>
            </p> -->
        </div>
        <p class="sottotitolo">Date:</p>
        <div id="date" style="display: grid; grid-template-columns: auto auto auto;">
			<?php 
			$pratica = $c->db->ql('SELECT * FROM tec_pratiche WHERE ID = ?', [$datiGenericiPratica['ID']])[0];
			foreach ($pratica as $key => $value)
			    if(substr($key, 0, 5) == 'Data_')
			        echo '<p><span>'.str_replace('_', ' ', $key).":</span> $value</p>";
			?>
        </div>
        <p class="sottotitolo">Ulteriori informazioni:</p>
        <div id="ultInfo">
            <p><span>Approvata:</span> <?= $pratica['Approvata'] ?></p>
            <p><span>Onerosa:</span> <?= $pratica['Onerosa'] ?></p>
            <p><span>Beni ambientali:</span> <?= $pratica['Beni_Ambientali'] ?></p>
            <p><span>Verbale:</span> <?= $pratica['Verbale'] ?></p>
            <p><span>Prescrizioni:</span> <?= $pratica['Prescrizioni'] ?></p>
            <p><span>Parere:</span> <?= $pratica['Parere'] ?></p>
            <p><span>Note parere:</span> <?= $pratica['Parere_Note'] ?></p>
            <p><span>Note pratica:</span> <?= $pratica['Note_pratica'] ?></p>
            <p><span>Note pagamenti:</span> <?= $pratica['Note_pagamenti'] ?></p>
        </div>
  </body>
</html>
