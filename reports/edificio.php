<?php 
    include_once '../controls.php';
    $c = new Controls();
    
    if(!$c->logged()){
        header('Location: index.php?err=Utente non loggato');
        exit();
    }
    
    if($c->check(['edificio'], $_REQUEST)||$c->check(['foglio', 'mappale'], $_REQUEST)){
        
        $edificioID = $_REQUEST['edificio']??'';
        if(!$edificioID) 
            $edificioID = $c->getEdificioID($_REQUEST['foglio']??'', $_REQUEST['mappale']??'');
        
        $datiGenericiEdificio = NULL;
        if($edificioID)
            $datiGenericiEdificio = $c->db->ql('SELECT * FROM edifici_view WHERE ID = ?',[$edificioID])[0];
        
        if($datiGenericiEdificio !== NULL){
            
            $intest_persone = [];
            $intest_societa = [];
            
            $rubriche = [];
            $condoni = [];
            
            $scia = [];
            $dia = [];
            $cila = [];
            $cil = [];
            $varie = [];
            
            /*----------------------------------------------*/
            
            //TODO tec con UNION
            $intest_persone = $c->db->ql(
                "SELECT DISTINCT ip.*
                FROM pe_edifici_pratiche ep
                JOIN pe_intestatari_persone_pratiche ipp ON ipp.Pratica = ep.Pratica
                JOIN intestatari_persone ip ON ip.ID = ipp.Persona
                WHERE ep.Edificio = ?",
                [$edificioID]);
                            
            /*----------------------------------------------*/
                            
            $intest_societa = $c->db->ql(
                "SELECT DISTINCT soc.ID, soc.Intestazione
                FROM pe_pratiche p
                JOIN pe_intestatari_societa_pratiche i ON i.Pratica = p.ID
                JOIN intestatari_societa soc ON i.Societa = soc.ID
                WHERE p.Edificio = ?
                UNION
                SELECT DISTINCT soc.ID, soc.Intestazione
                FROM tec_pratiche p
                JOIN tec_intestatari_societa_pratiche i ON i.Pratica = p.ID
                JOIN intestatari_societa soc ON i.Societa = soc.ID
                WHERE p.Edificio = ?",
                [$edificioID, $edificioID]);
                            
            /*----------------------------------------------*/
            
            $res = $c->db->ql(
                "SELECT *
                FROM pe_pratiche
                WHERE Edificio = ?",
                [$edificioID]);
                            
            foreach ($res as $pratica)
                switch ($pratica['TIPO']) {
                    case 'DIA':
                        $dia[] = $pratica;
                        break;
                        
                    case 'CIL':
                        $cil[] = $pratica;
                        break;
                        
                    case 'CILA':
                        $cila[] = $pratica;
                        break;
                        
                    case 'SCIA':
                        $scia[] = $pratica;
                        break;
                        
                    case 'VARIE':
                        $varie[] = $pratica;
                        break;
                        
                    default:
                        ;
                        break;
                }
            
            /*----------------------------------------------*/
            
            $rubriche = $c->db->ql("SELECT r.ID, r.Anno, r.Numero, r.Barrato, i.Cognome, i.Nome
                                                    FROM pe_rubrica r
                                                    JOIN pe_intestatari_rubrica i ON r.ID = i.Rubrica
                                                    WHERE Edificio = ?",
                                                    [$edificioID]);
                            
            /*----------------------------------------------*/
            
            $condoni = $c->db->ql("SELECT ID, Mappali, Anno, Numero, Cognome, Nome, Codice_fiscale cf
                                                FROM pe_condoni
                                                WHERE Edificio = ?",
                                                [$edificioID]);
                            
            /*----------------------------------------------*/
            
            //TODO pratiche ed intestatari TEC
            $aut = [];
            $perm = [];
            $conc = [];
            $san = [];
            $opere = [];
            
            /*----------------------------------------------*/
                            
            $res = $c->db->ql("SELECT *
                FROM tec_pratiche
                WHERE Edificio = ?",
                [$edificioID]);
            
            foreach ($res as $pratica){
                $tipo = substr($pratica['ID'], 0, 1);
                switch ($tipo) {
                    case 'A':
                        $aut[] = $pratica;
                        break;
                        
                    case 'S':
                        $san[] = $pratica;
                        break;
                        
                    case 'P':
                        $perm[] = $pratica;
                        break;
                        
                    case 'C':
                        $conc[] = $pratica;
                        break;
                        
                    case 'I':
                        $opere[] = $pratica;
                        break;
                        
                    default:
                        ;
                        break;
                }
            }
            
        } else {
            echo '<span class="errorTitle">Nessun risultato con parametri:
                                                                N° edificio: '.($_REQUEST['edificio']??'').'
                                                                Foglio: '.($_REQUEST['foglio']??'').'
                                                                Mappale: '.($_REQUEST['mappale']??'').'</span>';
            exit();
        }
            
    }else {
        echo '<span class="errorTitle">Richiesta non valida</span>';
        exit();
    }
?>
  <head>
    <title>Storico edificio</title>
    <link rel="stylesheet" type="text/css" href="../css/report_edificio.css">
  </head>
  <body>
    <div id="intestazione">
       <img src="..\imgs\logo.jpg" id="logo" alt="logo" align="right"></img>
       <div id="titoli">
            <h1>Comune di Canale d'Agordo</h1>
            <h2>Ufficio tecnico</h2>
            <h3>Interrogazione edificio all'archivio pratiche edilizie</h3>
       </div>
    </div>
    <div id="generalita">
            <p>Edificio n: <?= $datiGenericiEdificio['ID'] ?> </p>
            <p id="localita">Localit&aacute;: <?= $datiGenericiEdificio['Stradario'] ?></p>
            <p id="mappaliCompleti">Fogli-mappali: <?= $datiGenericiEdificio['Mappali'] ?></p>
    </div>
    <div id="intestatari">
        <p>Intestatari persone: </p>
        <p id="nomiIntestatariPersone"><?php
            $temp = [];
            foreach ($intest_persone as $pers)
                $temp[] = "<a href=\"anagrafica.php?persona=$pers[ID]\" target=\"_blank\">$pers[Cognome] $pers[Nome]</a>";
            echo implode(', ', $temp)
        ?></p>
        <p>Intestatari societ&aacute;: </p>
        <p id="nomiIntestatariSocieta"><?php
            $temp = [];
            foreach ($intest_societa as $soc)
                $temp[] = "<a href=\"anagrafica.php?societa=$soc[ID]\" target=\"_blank\">$soc[Intestazione]</a>";
            echo implode(', ', $temp)
        ?></p>
    </div>
    <div id="pe">
        <p>Pratiche rubrica: </p>
        <p class="listaPE" id="pratiche"><?php
            $temp = [];
            foreach ($rubriche as $pra)
                $temp[] = "$pra[Numero]/$pra[Anno]$pra[Barrato] ($pra[Nome] $pra[Cognome])";
            echo implode(', ', $temp)
        ?></p>
        <p>Autorizzazioni: </p>
        <p class="listaPE" id="autorizzazioni"><?php
            $temp = [];
            foreach ($aut as $pra)
                $temp[] = "<a href=\"praticaTEC.php?id=$pra[ID]\" target=\"_blank\">$pra[ID]</a>";
            echo implode(', ', $temp)
        ?></p>
        <p>Permessi: </p>
        <p class="listaPE" id="permessi"><?php
            $temp = [];
            foreach ($perm as $pra)
                $temp[] = "<a href=\"praticaTEC.php?id=$pra[ID]\" target=\"_blank\">$pra[ID]</a>";
            echo implode(', ', $temp)
        ?></p>
        <p>Concessioni: </p>
        <p class="listaPE" id="concessioni"><?php
            $temp = [];
            foreach ($conc as $pra)
                $temp[] = "<a href=\"praticaTEC.php?id=$pra[ID]\" target=\"_blank\">$pra[ID]</a>";
            echo implode(', ', $temp)
        ?></p>
        <p>Sanatorie: </p>
        <p class="listaPE" id="sanatorie"><?php
            $temp = [];
            foreach ($san as $pra)
                $temp[] = "<a href=\"praticaTEC.php?id=$pra[ID]\" target=\"_blank\">$pra[ID]</a>";
            echo implode(', ', $temp)
        ?></p>
        <p>Opere interne: </p>
        <p class="listaPE" id="opereInterne"><?php
            $temp = [];
            foreach ($opere as $pra)
                $temp[] = "<a href=\"praticaTEC.php?id=$pra[ID]\" target=\"_blank\">$pra[ID]</a>";
            echo implode(', ', $temp)
        ?></p>
        <p>Condoni: </p>
        <p class="listaPE" id="condoni"><?php
            $temp = [];
            foreach ($condoni as $pra)
                $temp[] = "$pra[Numero]/$pra[Anno] Mapp. $pra[Mappali] ($pra[Nome] $pra[Cognome] - $pra[cf])";
            echo implode(', ', $temp)
        ?></p>
        <p>SCIA: </p>
        <p class="listaPE" id="scia"><?php
            $temp = [];
            foreach ($scia as $pra)
                $temp[] = "<a href=\"praticaPE.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(', ', $temp)
        ?></p>
        <p>DIA: </p>
        <p class="listaPE" id="dia"><?php
            $temp = [];
            foreach ($dia as $pra)
                $temp[] = "<a href=\"praticaPE.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(', ', $temp)
        ?></p>
        <p>CILA: </p>
        <p class="listaPE" id="cila"><?php
            $temp = [];
            foreach ($cila as $pra)
                $temp[] = "<a href=\"praticaPE.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(', ', $temp)
        ?></p>
        <p>CIL: </p>
        <p class="listaPE" id="cil"><?php
            $temp = [];
            foreach ($cil as $pra)
                $temp[] = "<a href=\"praticaPE.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(', ', $temp)
        ?></p>
        <p>Varie: </p>
        <p class="listaPE" id="varie"><?php
            $temp = [];
            foreach ($varie as $pra)
                $temp[] = "<a href=\"praticaPE.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(', ', $temp)
        ?></p>
    </div>
    <div id="finePagina">
        <p>Note: </p>
        <p id="note"><?= $datiGenericiEdificio['Note'] ?></p>
    </div>
  </body>
</html>