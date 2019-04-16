<?php 
    include_once '../controls.php';
    $controls = new Controls();
    
    if(!($controls->logged()&&$controls->check(['id'], $_REQUEST))){
        header('Location: ../index.php?err=Utente non loggato');
        exit();
    }
    
    if($controls->check(['id'], $_REQUEST)){
        $res = $controls->db->ql(
            'SELECT p.ID ID, 
                    p.Numero Numero, 
                    p.Anno Anno, 
                    p.Barrato Barrato, 
                    p.Protocollo Protocollo, 
                    p.Edificio Edificio, 
                    e.Foglio Foglio, 
                    p.Mappale Mappale,
                    s.Denominazione Stradario,
                    p.Subalterno Subalterno,
                    p.Intervento Intervento,
                    t.ID tID,
                    CONCAT(t.Cognome, \' \', t.Nome) tecnico,
                    dir.ID dirID,
                    CONCAT(dir.Cognome, \' \', dir.Nome) direttore,
                    imp.ID impID,
                    imp.Intestazione impresa,
                    p.Data Data, 
                    p.Data_inizio_lavori Datail, 
                    p.Documento_elettronico Doc,
                    p.Note Note
            FROM pe_pratiche p
                JOIN pe_edifici e ON p.Edificio = e.ID
                JOIN stradario s ON p.Stradario = s.Identificativo_nazionale
                LEFT JOIN tecnici t ON p.Tecnico = t.ID
                LEFT JOIN tecnici dir ON p.Direzione_lavori = t.ID
                LEFT JOIN imprese imp ON p.Impresa = imp.ID
            WHERE p.ID = ?',
            [$_REQUEST['id']]);
        
        
        if(count($res) == 0){
            echo '<h1 style="color:red; text-align:center;">Nessun risultato</h1></body></html>';
            exit();
        }
        
        $pratica = $res[0];
        $intestatariPersone = [];
        $intestatariSocieta = [];
        
        $intestatari = $controls->db->ql(
            'SELECT *
            FROM pe_intestatari_pratiche
            WHERE Pratica = ?',
            [$pratica['ID']]);
        
        foreach ($intestatari as $intestatario)
            if(!empty($intestatario['Persona']))
                $intestatariPersone[] = $intestatario['Persona'];
            else 
                $intestatariSocieta[] = $intestatario['Societa'];
            
        if(count($intestatariPersone) > 0)
            $intestatariPersone = $controls->db->ql(
                'SELECT ID, Nome, Cognome
                FROM intestatari_persone
                WHERE ID IN (?'.str_repeat(',?', count($intestatariPersone)-1).')',
                $intestatariPersone);
        
        if(count($intestatariSocieta) > 0)
            $intestatariSocieta = $controls->db->ql(
                'SELECT ID, Intestazione
            FROM intestatari_societa
            WHERE ID IN (?'.str_repeat(',?', count($intestatariSocieta)-1).')',
                $intestatariSocieta);
        
    }

    //TODO tut
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <meta charset="utf-8">
    <title>Report pratica</title>
    <style>
         body {
            width: 1000px;
        }
        
        #titoli{
            background-color: skyblue;
            height: 220px;
            text-align: center;
            padding-top: 1px;
        }
        
        #logo{
            float: right;
        }
        
        .sottotitolo{
            font-weight: bold;
            margin-bottom: 0px;
            margin-top: 20px;
        }
        
        #generalita{
            display: inline-grid;
            width: 1000px;
            grid-template-columns: auto auto auto auto;
            background-color: #2196F3;
            border-style: solid;
            border-width: thin;
        }
        
        #generalita > p{
            background-color: rgba(255, 255, 255, 0.8);
            text-align: center;
            padding: 10px;
            font-size: 15px;
        }
        
        #localita{
            grid-column-start: 1;
            grid-column-end: 3;
        }
        
         #subalterno{
            grid-column-start: 3;
            grid-column-end: 5;
        }
        
        #intervento{
            grid-column-start: 1;
            grid-column-end: 5;
        }
        
        #persone{
            display: inline-grid;
            width: 1000px;
            grid-template-columns: auto auto;
            background-color: #2196F3;
            border-style: solid;
            border-width: thin;
        }
        
        #persone > p{
            background-color: rgba(255, 255, 255, 0.8);
            text-align: center;
            padding: 10px;
            font-size: 15px;
        }
        
        #impresa{
            grid-column-start: 1;
            grid-column-end: 3;
        }
        
        #date{
            display: inline-grid;
            width: 1000px;
            grid-template-columns: auto auto;
            background-color: #2196F3;
            border-style: solid;
            border-width: thin;
        }
        
        #date > p{
            background-color: rgba(255, 255, 255, 0.8);
            text-align: center;
            padding: 10px;
            font-size: 15px;
        }
        
        #dataScadenza{
            grid-column-start: 1;
            grid-column-end: 3;
        }
        
        #ultInfo{
            display: inline-grid;
            width: 1000px;
            grid-template-columns: auto auto;
            background-color: #2196F3;
            border-style: solid;
            border-width: thin;
        }
        
        #ultInfo > p{
            background-color: rgba(255, 255, 255, 0.8);
            text-align: center;
            padding: 10px;
            font-size: 15px;
        }
        
        #note{
            grid-column-start: 1;
            grid-column-end: 3;
        }
        
         #docElettronico{
            grid-column-start: 1;
            grid-column-end: 3;
        }
        
    </style>
  </head>
  <body>
     <div id="intestazione">
       <img src="../imgs/logo.jpg" id="logo" alt="logo" align="right">
       <div id="titoli">
            <h1>Comune di Canale d'Agordo</h1>
            <h2>Ufficio tecnico</h2>
            <h3>Interrogazione edificio all'archivio pratiche edilizie</h3>
       		<h4>ID pratica: <?= $pratica['ID'] ?></h4>
       </div>
    </div>
    <p class="sottotitolo">Informazioni generali:</p>
    <div id="generalita">
            <p>Numero: <?= $pratica['Numero'] ?></p>
            <p>Anno: <?= $pratica['Anno'] ?></p>
            <p>Barrato: <?= $pratica['Barrato'] ?></p>
            <p>Protocollo: <?= $pratica['Protocollo'] ?></p>
            <p>ID edificio: <a href="edificio.php?id=<?= $pratica['Edificio'] ?>"><?= $pratica['Edificio'] ?></a></p>
            <p>Foglio edificio: <?= $pratica['Foglio'] ?></p>
            <p>Mappale pratica: <?= $pratica['Mappale'] ?></p>
            <p id="localita">Localit&aacute;: <?= $pratica['Stradario'] ?>, Civico?</p>
            <p id="subalterno">Subalterno: <?= $pratica['Subalterno'] ?></p>
            <p id="intervento">Intervento: <?= $pratica['Intervento'] ?></p>
    </div>
    <p class="sottotitolo">Persone:</p>
    <div id="persone">
        <?php 
        if(count($intestatariPersone) > 0){
            echo '<p>Intestatari persone: ';
            foreach ($intestatariPersone as $intestatario)
                echo "<a href=\"anagrafica.php?persona=$intestatario[ID]\">$intestatario[Cognome] $intestatario[Nome]</a>";
            echo '</p>';
        }
        if(count($intestatariSocieta) > 0){
            echo '<p>Intestatari societ&aacute: ';
            foreach ($intestatariSocieta as $intestatario)
                echo "<a href=\"anagrafica.php?societa=$intestatario[ID]\">$intestatario[Intestazione]</a>";
                echo '</p>';
        }
        ?>
        <p>Tecnico: <a href="<?= "anagrafica.php?tecnico=$pratica[tID]" ?>"><?= $pratica['tecnico'] ?></a></p>
        <p>Direttore lavori: <a href="<?= "anagrafica.php?tecnico=$pratica[dirID]" ?>"><?= $pratica['direttore'] ?></a></p>
        <p id="impresa">Impresa: <a href="<?= "anagrafica.php?impresa=$pratica[impID]" ?>"><?= $pratica['impresa'] ?></a></p>
    </div>
    <p class="sottotitolo">Date:</p>
    <div id="date">
        <p>Data presentazione: <?= $pratica['Data'] ?></p>
        <p>Data inizio lavori: <?= $pratica['Datail'] ?></p>
    </div>
    <p class="sottotitolo">Ulteriori informazioni:</p>
    <div id="ultInfo">
    	<p id="docElettronico">Documento elettronico: <?= $pratica['Doc'] ?></p>
        <p id="note">Note: <?= $pratica['Note'] ?></p>
    </div>
  </body>
</html>