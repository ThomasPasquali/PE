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
                $datiGenericiPratica = $c->db->ql('SELECT * FROM pe_pratiche_view WHERE ID = ?',[$datiGenericiPraticaID])[0];

                if($datiGenericiPratica !== NULL){

                    $fogliMappali = $c->db->ql(
                        "SELECT Edificio, CONCAT('F.', Foglio, 'm.', Mappale) FoglioMappale
                        FROM pe_fogli_mappali_pratiche
                        WHERE Pratica = ?",
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
<html>
  <head>
    <title>Pratica <?= $datiGenericiPratica['Sigla'] ?></title>
    <link rel="stylesheet" type="text/css" href="../css/report_pratica.css">
    <link rel="stylesheet" href="../lib/fontawesome/css/all.css">
    <script type="text/javascript">
    function copyStringToClipboard (str) {
    	   // Create new element
    	   var el = document.createElement('textarea');
    	   // Set value (string to be copied)
    	   el.value = str;
    	   // Set non-editable to avoid focus and move outside of view
    	   el.setAttribute('readonly', '');
    	   el.style = {position: 'absolute', left: '-9999px'};
    	   document.body.appendChild(el);
    	   // Select text inside element
    	   el.select();
    	   // Copy text to clipboard
    	   document.execCommand('copy');
    	   // Remove temporary element
    	   document.body.removeChild(el);
    }
    </script>
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
        foreach ($fogliMappali as $foglioMappale){
          $sep = $i > 0 ? ' - ' : '';
          echo "$sep$foglioMappale[FoglioMappale]<a href=\"edificio.php?edificio=$foglioMappale[Edificio]\"> (Edficio $foglioMappale[Edificio])</a>";
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
        
        <p><span>Tecnico:</span>
          <?php
            $tecnico = $c->getDatiTecnico($datiGenericiPratica['Tecnico']);
            if($tecnico)
              echo "<a href=\"anagrafica.php?tecnico=$tecnico[ID]\">$tecnico[Cognome] $tecnico[Nome] ($tecnico[Codice_fiscale] - $tecnico[Partita_iva])</a>"
          ?>
        </p>
        <p><span>Direttore lavori:</span>
        <?php
          $direttoreLavori = $c->getDatiTecnico($datiGenericiPratica['Direzione_lavori']);
          if($direttoreLavori)
            echo "<a href=\"anagrafica.php?tecnico=$direttoreLavori[ID]\">$direttoreLavori[Cognome] $direttoreLavori[Nome] ($direttoreLavori[Codice_fiscale] - $direttoreLavori[Partita_iva])</a>"
        ?>
        </p>
        <p><span>Impresa:</span>
        <?php
          $impresa = $c->getDatiImpresa($datiGenericiPratica['Impresa']);
          if($impresa)
            echo "<a href=\"anagrafica.php?impresa=$impresa[ID]\">$impresa[Intestazione] ($impresa[Codice_fiscale] - $impresa[Partita_iva])</a>"
        ?>
        </p>
    </div>
    <p class="sottotitolo">Date:</p>
    <div id="date">
        <p><span>Data presentazione:</span> <?= $datiGenericiPratica['Data'] ?></p>
        <p><span>Data inizio lavori:</span> <?= $datiGenericiPratica['Data_inizio_lavori'] ?></p>
    </div>

    <div style="margin-top:20px;"><p><span style="text-decoration:underline;">Note:</span> <?= $datiGenericiPratica['Note'] ?></p><div>

    <p class="sottotitolo">Archivi:</p>
    <div id="ultInfo">
    	<p id="docElettronico"><span>Documento elettronico:</span> <?= $datiGenericiPratica['Documento_elettronico'] ?></p>
      <p id="suap"><span>Codice SUAP:</span> <?= $datiGenericiPratica['SUAP'] ?></p>
    </div>
  </body>
</html>
