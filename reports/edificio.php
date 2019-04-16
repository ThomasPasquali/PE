<?php 
    include_once '../controls.php';
    $controls = new Controls();
    
    if(!$controls->logged()){
        header('Location: index.php?err=Utente non loggato');
        exit();
    }
    
    if($controls->check(['foglio', 'mappale'], $_REQUEST)||$controls->check(['id'], $_REQUEST)){
        
        if(isset($_REQUEST['id'])){
            $id = $_REQUEST['id'];
            
            $res = $controls->db->ql(
                'SELECT e.Foglio foglio, s.Denominazione strad, e.Note
                FROM pe_edifici e
                	JOIN stradario s ON e.Stradario = s.Identificativo_nazionale
                WHERE e.ID = ?',
                [$id]);
            
            if(count($res) == 0){
                echo '<h1 style="color:red; text-align:center;">Nessun risultato</h1>';
                exit();
            }
            
            $foglio = $res[0]['foglio'];
            $stradario = $res[0]['strad'];
            $note = $res[0]['Note'];
            
        }else{
            $foglio = $_REQUEST['foglio'];
            
            $res = $controls->db->ql(
                'SELECT e.ID id, s.Denominazione strad, e.Note
            FROM pe_edifici e
            	JOIN pe_mappali_edifici m ON e.ID = m.Edificio
            	JOIN stradario s ON e.Stradario = s.Identificativo_nazionale
            WHERE e.Foglio = ? AND m.Mappale = ?',
                [$foglio, $_REQUEST['mappale']]);
            
            if(count($res) == 0){
                echo '<h1 style="color:red; text-align:center;">Nessun risultato</h1>';
                exit();
            }
            
            $id = $res[0]['id'];
            $stradario = $res[0]['strad'];
            $note = $res[0]['Note'];
        }
        
        /*----------------------------------------------*/
        
        $res = $controls->db->ql("SELECT GROUP_CONCAT(Mappale SEPARATOR ', ') map
                                                    FROM pe_mappali_edifici
                                                    WHERE Edificio = $id AND EX IS NULL
                                                    GROUP BY Edificio");

        if(count($res) > 1){
            echo 'wait...what? mappali';
            exit();
        }
        
        if(count($res) > 0)
            $mappali = $res[0]['map'];
        else
        $mappali = '';
        
        $res = $controls->db->ql("SELECT GROUP_CONCAT(Mappale SEPARATOR ', ') map
                                                    FROM pe_mappali_edifici
                                                    WHERE Edificio = $id AND EX IS NOT NULL
                                                    GROUP BY Edificio");
        
        if(count($res) > 1){
            echo 'wait...what? mappali ex';
            exit();
        }
        
        if(count($res) > 0)
            $mappaliEx = $res[0]['map'];
        else
        $mappaliEx = '';
        
        /*----------------------------------------------*/
        
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
        
        $intest_persone = $controls->db->ql(
            "SELECT DISTINCT pers.ID, pers.Nome, pers.Cognome
                FROM pe_pratiche p
                JOIN pe_intestatari_persone_pratiche i ON i.Pratica = p.ID
                JOIN intestatari_persone pers ON i.Persona = pers.ID
                WHERE p.Edificio = $id
                UNION
                SELECT DISTINCT pers.ID, pers.Nome, pers.Cognome
                FROM tec_pratiche p
                JOIN tec_intestatari_persone_pratiche i ON i.Pratica = p.ID
                JOIN intestatari_persone pers ON i.Persona = pers.ID
                WHERE p.Edificio = $id");
        
        /*----------------------------------------------*/
        
        $intest_societa = $controls->db->ql(
            "SELECT DISTINCT soc.ID, soc.Intestazione
                FROM pe_pratiche p
                JOIN pe_intestatari_societa_pratiche i ON i.Pratica = p.ID
                JOIN intestatari_societa soc ON i.Societa = soc.ID
                WHERE p.Edificio = $id
                UNION
                SELECT DISTINCT soc.ID, soc.Intestazione
                FROM tec_pratiche p
                JOIN tec_intestatari_societa_pratiche i ON i.Pratica = p.ID
                JOIN intestatari_societa soc ON i.Societa = soc.ID
                WHERE p.Edificio = $id");
        
        /*----------------------------------------------*/
        
        $res = $controls->db->ql("SELECT *
                                                    FROM pe_pratiche
                                                    WHERE Edificio = $id");
        
        foreach ($res as $pratica){
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
        }
        
        /*----------------------------------------------*/
        
        $rubriche = $controls->db->ql("SELECT r.ID, r.Anno, r.Numero, r.Barrato, i.Cognome, i.Nome
                                                            FROM pe_rubrica r
                                                            JOIN pe_intestatari_rubrica i ON r.ID = i.Rubrica
                                                            WHERE Edificio = $id");
        
        /*----------------------------------------------*/
        
        $condoni = $controls->db->ql("SELECT ID, Mappali, Anno, Numero, Cognome, Nome, Codice_fiscale cf
                                                            FROM pe_condoni
                                                            WHERE Edificio = $id");
        
        /*----------------------------------------------*/
        
        //TODO pratiche ed intestatari TEC
        $aut = [];
        $perm = [];
        $conc = [];
        $san = [];
        $opere = [];
        
        /*----------------------------------------------*/
        
        $res = $controls->db->ql("SELECT *
                                FROM tec_pratiche
                                WHERE Edificio = $id");
        
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
        
        
        
    }else {
        echo '<h1 style="color: red;">Richiesta invalida!</h1>';
        exit();
    }
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <meta charset="utf-8"></meta>
    <title>Storico edificio</title>
    <style>
        body {
            width: 1000px;
        }
        
        #titoli{
            background-color: brown;
            height: 200px;
            text-align: center;
            padding-top: 30px;
        }
        
        #logo{
            float: right;
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
            grid-column-start: 3;
            grid-column-end: 5;
        }
        
        #mappaliCompleti{
            grid-column-start: 1;
            grid-column-end: 3;
        }
        
         #exMappali{
            grid-column-start: 3;
            grid-column-end: 5;
        }
        
        #intestatari{
            display: grid;
            grid-template-columns: auto auto;
            width: 1000px;
            background-color: #2196F3;
            background-color: #2196F3;
            border-style: solid;
            border-width: thin;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        
        #nomiIntestatariPersone{
            margin-left: 20px;
            border-style: solid;
            border-width: thin;
            width: 850px;
        }
        
        #nomiIntestatariSocieta{
            margin-left: 20px;
            border-style: solid;
            border-width: thin;
            width: 850px;
        }
        
        #pe{
            display: grid;
            grid-template-columns: auto auto;
            background-color: #2196F3;
            border-style: solid;
            border-width: thin;
            width: 1000px;
        }
        
        .listaPE{
            margin-left: 20px;
            border-style: solid;
            border-width: thin;
            width: 850px;
        }
        
        #finePagina{
            display: inline-flex;
            width: 1000px;
        }
        
        #note{
            margin-left: 20px;
            border-style: solid;
            border-width: thin;
            width: 1000px;
        }
        
        p {
            word-wrap: break-word;
        }
        
    </style>
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
            <p>Edificio n: <?= $id ?> </p>
            <p>Foglio: <?= $foglio ?></p>
            <p id="localita">Localit&aacute;: <?= $stradario ?></p>
            <p id="mappaliCompleti">Mappali: <?= $mappali ?></p>
            <p id="exMappali">Ex mappali: <?= $mappaliEx ?></p>
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
        <p id="note"><?= $note ?></p>
    </div>
  </body>
</html>