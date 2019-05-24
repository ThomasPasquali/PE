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
            $datiGenericiPraticaID = $c->getPraticaID($_REQUEST['tipo']??'', $_REQUEST['anno']??'', $_REQUEST['numero']??'', $_REQUEST['barrato']??'');

            $datiGenericiPratica = NULL;
            if($datiGenericiPraticaID)
                $datiGenericiPratica = $c->db->ql('SELECT * FROM pratiche_view WHERE ID = ?',[$datiGenericiPraticaID])[0];

                if($datiGenericiPratica !== NULL){

                    //print_r($datiGenericiPratica);
                    //TODO edifici
                    $edifici = $c->db->ql(
                        'SELECT e.ID, e.Mappali
                        FROM pe_edifici_pratiche ep
                        JOIN edifici_view e ON e.ID = ep.Edificio
                        WHERE Pratica = ?',
                        [$datiGenericiPratica['ID']]);

                    $intestatariPersone = $c->db->ql(
                        'SELECT ip.ID, ip.Nome, ip.Cognome
                        FROM pe_intestatari_persone_pratiche ipp
                        JOIN intestatari_persone ip ON ip.ID = ipp.Persona
                        WHERE ipp.Pratica = ?',
                        [$datiGenericiPratica['ID']]);

                    $intestatariSocieta = $c->db->ql(
                        'SELECT i.ID, i.Intestazione
                        FROM pe_intestatari_societa_pratiche isp
                        JOIN intestatari_societa i ON i.ID = isp.Societa
                        WHERE isp.Pratica = ?',
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
       <img src="../imgs/logo.jpg" id="logo" alt="logo" align="right">
       <div id="titoli">
            <h1>Comune di Canale d'Agordo</h1>
            <h2>Ufficio tecnico</h2>
            <h3>Interrogazione edificio all'archivio pratiche edilizie</h3>
       		<h4>ID pratica: <?= $datiGenericiPratica['ID'] ?><br>Sigla: <?= $datiGenericiPratica['Sigla'] ?></h4>
       </div>
    </div>
    <p class="sottotitolo">Informazioni generali:</p>
    <div id="generalita">
    		<p>Anno: <?= $datiGenericiPratica['Anno'] ?></p>
            <p>Numero: <?= $datiGenericiPratica['Numero'] ?></p>
            <p>Barrato: <?= $datiGenericiPratica['Barrato'] ?></p>
            <p>Protocollo: <?= $datiGenericiPratica['Protocollo'] ?></p>
            <?php
            if(count($edifici) > 0){
                echo '<p>Edifici: ';
                $i = 0;
                foreach ($edifici as $edificio){
                  $sep = $i > 0 ? ', ' : '';
                  echo "$sep<a href=\"edificio.php?edificio=$edificio[ID]\">$edificio[ID]($edificio[Mappali])</a>";
                  $i++;
                }
                echo '</p>';
            }
            ?>
            <p>Fogli-mappali: <?= $datiGenericiPratica['FogliMappali'] ?></p>
            <p id="subalterno">Subalterni: <?= $datiGenericiPratica['Subalterni'] ?></p>
            <p id="localita">Localit&aacute;: <?= $datiGenericiPratica['Stradario'] ?></p>
            <p id="intervento">Intervento: <?= $datiGenericiPratica['Intervento'] ?></p>
    </div>
    <p class="sottotitolo">Persone:</p>
    <div id="persone">
        <?php
        if(count($intestatariPersone) > 0){
            echo '<p>Intestatari persone: ';
            $i = 0;
            foreach ($intestatariPersone as $intestatario){
              $sep = $i > 0 ? ', ' : '';
              echo "$sep<a href=\"anagrafica.php?persona=$intestatario[ID]\">$intestatario[Cognome] $intestatario[Nome]</a>";
              $i++;
            }
            echo '</p>';
        }
        if(count($intestatariSocieta) > 0){
            echo '<p>Intestatari societ&aacute: ';
            $i = 0;
            foreach ($intestatariSocieta as $intestatario){
              $sep = $i > 0 ? ', ' : '';
              echo "$sep<a href=\"anagrafica.php?societa=$intestatario[ID]\">$intestatario[Intestazione]</a>";
              $i++;
            }
            echo '</p>';
        }
        ?>
        <p>Tecnico:
          <?php
            $tecnico = $c->getDatiTecnico($datiGenericiPratica['Tecnico']);
            if($tecnico)
              echo "<a href=\"anagrafica.php?tecnico=$tecnico[ID]\">$tecnico[Cognome] $tecnico[Nome] ($tecnico[Codice_fiscale] - $tecnico[Partita_iva])</a>"
          ?>
        </p>
        <p>Direttore lavori:
        <?php
          $direttoreLavori = $c->getDatiTecnico($datiGenericiPratica['Direzione_lavori']);
          if($direttoreLavori)
            echo "<a href=\"anagrafica.php?tecnico=$direttoreLavori[ID]\">$direttoreLavori[Cognome] $direttoreLavori[Nome] ($direttoreLavori[Codice_fiscale] - $direttoreLavori[Partita_iva])</a>"
        ?>
        </p>
        <p id="impresa">Impresa:
        <?php
          $impresa = $c->getDatiImpresa($datiGenericiPratica['Impresa']);
          if($impresa)
            echo "<a href=\"anagrafica.php?impresa=$impresa[ID]\">$impresa[Intestazione] ($impresa[Codice_fiscale] - $impresa[Partita_iva])</a>"
        ?>
        </p>
    </div>
    <p class="sottotitolo">Date:</p>
    <div id="date">
        <p>Data presentazione: <?= $datiGenericiPratica['Data'] ?></p>
        <p>Data inizio lavori: <?= $datiGenericiPratica['Data_inizio_lavori'] ?></p>
    </div>
    <p class="sottotitolo">Ulteriori informazioni:</p>
    <div id="ultInfo">
    	<p id="docElettronico">Documento elettronico: <?= $datiGenericiPratica['Documento_elettronico'] ?></p>
    	<p id="note">Intervento: <?= $datiGenericiPratica['Intervento'] ?></p>
        <p id="note">Note: <?= $datiGenericiPratica['Note'] ?></p>
    </div>
  </body>
</html>
