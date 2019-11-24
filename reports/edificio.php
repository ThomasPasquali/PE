<?php
    include_once '../controls.php';
    $c = new Controls();

    if(!$c->logged()){
        header('Location: ../index.php?err=Utente non loggato');
        exit();
    }

    if($c->check(['edificio'], $_REQUEST)||$c->check(['foglio', 'mappale'], $_REQUEST)){

        $edificioID = $_REQUEST['edificio']??'';
        if(!$edificioID)
            $edificioID = $c->getEdificioID($_REQUEST['foglio']??'', $_REQUEST['mappale']??'');

        $datiGenericiEdificio = NULL;
        if($edificioID)
            $datiGenericiEdificio = $c->db->ql('SELECT * FROM edifici_view WHERE ID = ?',[$edificioID])[0]??NULL;

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

            $intest_persone = $c->db->ql(
                'SELECT ip.*
                FROM pe_edifici_pratiche ep
                JOIN pe_intestatari_persone_pratiche ipp ON ipp.Pratica = ep.Pratica
                JOIN intestatari_persone ip ON ip.ID = ipp.Persona
                WHERE ep.Edificio = :ed
                UNION
                SELECT ip1.*
                FROM tec_edifici_pratiche ep1
                JOIN tec_intestatari_persone_pratiche ipp1 ON ipp1.Pratica = ep1.Pratica
                JOIN intestatari_persone ip1 ON ip1.ID = ipp1.Persona
                WHERE ep1.Edificio = :ed',
                [':ed' => $edificioID]);

            /*----------------------------------------------*/

            $intest_societa = $c->db->ql(
                'SELECT i.*
                FROM pe_edifici_pratiche ep
                JOIN pe_intestatari_societa_pratiche isp ON isp.Pratica = ep.Pratica
                JOIN intestatari_societa i ON i.ID = isp.Societa
                WHERE ep.Edificio = :ed
                UNION
                SELECT i1.*
                FROM tec_edifici_pratiche ep1
                JOIN tec_intestatari_societa_pratiche isp1 ON isp1.Pratica = ep1.Pratica
                JOIN intestatari_societa i1 ON i1.ID = isp1.Societa
                WHERE ep1.Edificio = :ed',
                [':ed' => $edificioID]);

            /*----------------------------------------------*/

            $res = $c->db->ql(
                'SELECT p.*
                FROM pe_pratiche p
                JOIN pe_edifici_pratiche ep ON ep.Pratica = p.ID
                WHERE Edificio = ?',
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

            $rubriche = $c->db->ql('SELECT r.ID, r.Anno, r.Numero, r.Barrato, GROUP_CONCAT(CONCAT(i.Cognome, \' \', i.Nome) SEPARATOR \' - \') Intestatari
                                                FROM pe_rubrica r
                                                JOIN pe_intestatari_rubrica i ON r.ID = i.Rubrica
                                                WHERE Edificio = ?
GROUP BY r.ID',
                                                [$edificioID]);

            /*----------------------------------------------*/

            $condoni = $c->db->ql('SELECT ID, Mappali, Anno, Numero, Cognome, Nome, Codice_fiscale cf
                                                FROM pe_condoni
                                                WHERE Edificio = ?',
                                                [$edificioID]);

            /*----------------------------------------------*/

            //TODO pratiche TEC
            $aut = [];
            $perm = [];
            $conc = [];
            $san = [];
            $opere = [];

            /*----------------------------------------------*/

            $res = $c->db->ql("SELECT *
                              FROM tec_pratiche p
                              JOIN tec_edifici_pratiche ep ON ep.Pratica = p.ID
                              WHERE ep.Edificio = ?",
                              [$edificioID]);

            foreach ($res as $pratica){
                switch ($pratica['TIPO']) {
                    case 'Autorizzazione':
                        $aut[] = $pratica;
                        break;

                    case 'Sanatoria':
                        $san[] = $pratica;
                        break;

                    case 'Permesso':
                        $perm[] = $pratica;
                        break;

                    case 'Concessione':
                        $conc[] = $pratica;
                        break;

                    case 'Opera_interna':
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
<html>
  <head>
    <title>Storico edificio</title>
    <link rel="stylesheet" type="text/css" href="../css/report_edificio.css">
  </head>
  <body>
    <div id="intestazione">
       <div id="titoli">
            <h1>Comune di Canale d'Agordo</h1>
            <h2>Ufficio tecnico</h2>
            <h3>Interrogazione edificio n.<?= $datiGenericiEdificio['ID'] ?> all'archivio pratiche edilizie</h3>
       </div>
       <a href="../"><img src="../imgs/logo.jpg" id="logo" alt="logo"></a>
    </div>
    
    <div id="generalita">
        <p><span>Localit&agrave;:</span> <?= $datiGenericiEdificio['Stradario'] ?></p>
        <p><span>Fogli-mappali:</span> <?= $datiGenericiEdificio['Mappali'] ?></p>
        <p><span>Subalterni:</span> <?= $datiGenericiEdificio['Subalterni'] ?></p>
        <p><span>Note:</span> <?= str_replace("\r\n", '<br>', $datiGenericiEdificio['Note']) ?></p>
    </div>
    <div id="intestatari">
      <div>
        <p><span>Intestatari persone:</span></p>
        <p><?php
            $temp = [];
            foreach ($intest_persone as $pers)
            	$temp[] = "<a href=\"anagrafica.php?persona=$pers[ID]\" target=\"_blank\">$pers[Cognome] $pers[Nome]</a>";
            echo implode(' - ', $temp)
        ?></p>
      </div>

      <div>
        <p><span>Intestatari societ&aacute;:</span></p>
        <p><?php
            $temp = [];
            foreach ($intest_societa as $soc)
                $temp[] = "<a href=\"anagrafica.php?societa=$soc[ID]\" target=\"_blank\">$soc[Intestazione]</a>";
            echo implode(' - ', $temp)
        ?></p>
      </div>
    </div>

    <div id="condoni">
      <div>
        <p><span>Condoni:</span></p>
        <p><?php
            $temp = [];
            foreach ($condoni as $pra)
                $temp[] = "$pra[Numero]/$pra[Anno] Mapp. $pra[Mappali] ($pra[Nome] $pra[Cognome] - $pra[cf])";
            echo implode(' - ', $temp)
        ?></p>
      </div>
    </div>

    <div id="rubriche">
      <div>
        <p><span>Pratiche rubrica:</span></p>
        <p id="pratiche"><?php
            $temp = [];
            foreach ($rubriche as $pra)
                $temp[] = "$pra[Numero]/$pra[Anno]$pra[Barrato] ($pra[Intestatari])";
            echo implode(' - ', $temp)
        ?></p>
      </div>
    </div>

    <div id="pe">
      <div>
        <p><span>SCIA:</span></p>
        <p id="scia"><?php
            $temp = [];
            foreach ($scia as $pra)
                $temp[] = "<a href=\"praticaPE.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(' - ', $temp)
        ?></p>
      </div>

      <div>
        <p><span>DIA:</span></p>
        <p id="dia"><?php
            $temp = [];
            foreach ($dia as $pra)
                $temp[] = "<a href=\"praticaPE.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(' - ', $temp)
        ?></p>
      </div>

      <div>
        <p><span>CILA:</span></p>
        <p id="cila"><?php
            $temp = [];
            foreach ($cila as $pra)
                $temp[] = "<a href=\"praticaPE.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(' - ', $temp)
        ?></p>
      </div>

      <div>
        <p><span>CIL:</span></p>
        <p id="cil"><?php
            $temp = [];
            foreach ($cil as $pra)
                $temp[] = "<a href=\"praticaPE.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(' - ', $temp)
        ?></p>
      </div>

      <div>
        <p><span>Varie:</span></p>
        <p id="varie"><?php
            $temp = [];
            foreach ($varie as $pra)
                $temp[] = "<a href=\"praticaPE.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(' - ', $temp)
        ?></p>
      </div>
    </div>

    <div id="tec">
      <div>
        <p><span>Autorizzazioni:</span></p>
        <p id="autorizzazioni"><?php
            $temp = [];
            foreach ($aut as $pra)
                $temp[] = "<a href=\"praticaTEC.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(' - ', $temp)
        ?></p>
      </div>

      <div>
        <p><span>Permessi:</span></p>
        <p id="permessi"><?php
            $temp = [];
            foreach ($perm as $pra)
                $temp[] = "<a href=\"praticaTEC.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(' - ', $temp)
        ?></p>
      </div>

      <div>
        <p><span>Concessioni:</span></p>
        <p id="concessioni"><?php
            $temp = [];
            foreach ($conc as $pra)
                $temp[] = "<a href=\"praticaTEC.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(', ', $temp)
        ?></p>
      </div>

      <div>
        <p><span>Sanatorie:</span></p>
        <p id="sanatorie"><?php
            $temp = [];
            foreach ($san as $pra)
                $temp[] = "<a href=\"praticaTEC.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(', ', $temp)
        ?></p>
      </div>

      <div>
        <p><span>Opere interne:</span></p>
        <p id="opereInterne"><?php
            $temp = [];
            foreach ($opere as $pra)
                $temp[] = "<a href=\"praticaTEC.php?id=$pra[ID]\" target=\"_blank\">$pra[Numero]/$pra[Anno]$pra[Barrato]</a>";
            echo implode(', ', $temp)
        ?></p>
      </div>
    </div>

  </body>
</html>
