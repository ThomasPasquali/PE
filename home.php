<?php
    include_once 'controls.php';
    $GLOBALS['c'] = new Controls();

    if(!$GLOBALS['c']->logged()){
        header('Location: index.php?err=Utente non loggato');
        exit();
    }

   //$GLOBALS['c']->echoCode($_POST);

    if($GLOBALS['c']->check(['btn', 'tipo'], $_POST)){
        switch ($_POST['btn']) {
            case 'inserimentoAnagrafica':
                inserimentoAnagrafiche();
                break;

            default:
                ;
            break;
        }
    }

    function inserimentoAnagrafiche() {
        switch ($_POST['tipo']) {
            case 'persone':
            inserisciPersona();
            break;

            case 'societa':
            inserisciSocieta();
            break;

            case 'tecnici':
            inserisciTecnico();
            break;

            case 'impresa':
            inserisciImpresa();
            break;

            default:
                ;
            break;
        }
    }

    function inserisciPersona() {
        if($GLOBALS['c']->check(['nome', 'cognome', 'cf'], $_POST)){
            $stmt = $GLOBALS['c']->db->dml(
                'INSERT INTO intestatari_persone (Nome, Cognome, Codice_fiscale, Indirizzo, Citta, Provincia)
                VALUES (:n, :c, :cf, :ind, :citta, :prov)',
                [':n' => $_POST['nome'],
                ':c' => $_POST['cognome'],
                ':cf' => $_POST['cf'],
                ':ind' => getValueORNULL($_POST['indirizzo']),
                ':citta' => getValueORNULL($_POST['citta']),
                ':prov' => getValueORNULL($_POST['provincia'])]);

            if($stmt->errorInfo()[0] == 0)
                $GLOBALS['succ'] = 'Anagrafica inserita correttamente';
            else
            $GLOBALS['err'] = 'Impossibile inserire l\'anagrafica: '.$stmt->errorInfo()[2];
        }else
        $GLOBALS['err'] = 'Dati inseriti non corretti: valori mancanti';
    }

    function inserisciTecnico() {
        if($GLOBALS['c']->check(['nome', 'cognome', 'cf', 'piva'], $_POST)){
            $stmt = $GLOBALS['c']->db->dml(
                'INSERT INTO tecnici (Nome, Cognome, Codice_fiscale, Partita_iva, Albo, Numero_ordine, Provncia_albo, Indirizzo, Citta, Provincia, Note)
                VALUES (:n, :c, :cf, :piva, :albo, :ord, :alboP, :ind, :citta, :prov, :note)',
                [':n' => $_POST['nome'],
                ':c' => $_POST['cognome'],
                ':cf' => $_POST['cf'],
                ':piva' => getValueORNULL($_POST['piva']),
                ':albo' => getValueORNULL($_POST['albo']),
                ':ord' => getValueORNULL($_POST['numero_ordine']),
                ':alboP' => getValueORNULL($_POST['provincia_albo']),
                ':ind' => getValueORNULL($_POST['indirizzo']),
                ':citta' => getValueORNULL($_POST['citta']),
                ':prov' => getValueORNULL($_POST['provincia']),
                ':note' => getValueORNULL($_POST['note'])]);

            if($stmt->errorInfo()[0] == 0)
                $GLOBALS['succ'] = 'Tecnico inserito correttamente';
            else
            $GLOBALS['err'] = 'Impossibile inserire l\'anagrafica: '.$stmt->errorInfo()[2];
        }else
            $GLOBALS['err'] = 'Dati inseriti non corretti: valori mancanti';
    }

    function inserisciSocieta() {
        if($GLOBALS['c']->check(['intestazione', 'piva'], $_POST)){
            $stmt = $GLOBALS['c']->db->dml(
                'INSERT INTO intestatari_societa (Intestazione, Partita_iva, Indirizzo, Citta, Provincia)
                VALUES (:int, :piva, :ind, :citta, :prov)',
                [':int' => $_POST['intestazione'],
                ':piva' => $_POST['piva'],
                ':ind' => getValueORNULL($_POST['indirizzo']),
                ':citta' => getValueORNULL($_POST['citta']),
                ':prov' => getValueORNULL($_POST['provincia'])]);

            if($stmt->errorInfo()[0] == 0)
                $GLOBALS['succ'] = 'Societa inserita correttamente';
                else
                    $GLOBALS['err'] = 'Impossibile inserire l\'anagrafica: '.$stmt->errorInfo()[2];
        }else
            $GLOBALS['err'] = 'Dati inseriti non corretti: valori mancanti';
    }

    function inserisciImpresa() {
        if($GLOBALS['c']->check(['intestazione'], $_POST)){
            $stmt = $GLOBALS['c']->db->dml(
                'INSERT INTO imprese (Intestazione, Codice_fiscale, Partita_iva)
                 VALUES (:int, :cf, :piva)',
                [':int' => $_POST['intestazione'],
                ':piva' => getValueORNULL($_POST['piva']),
                ':cf' => getValueORNULL($_POST['cf'])]);

                if($stmt->errorInfo()[0] == 0)
                    $GLOBALS['succ'] = 'Impresa inserita correttamente';
                    else
                        $GLOBALS['err'] = 'Impossibile inserire l\'impresa: '.$stmt->errorInfo()[2];
        }else
            $GLOBALS['err'] = 'Dati inseriti non corretti: valori mancanti';
    }

    function getValueORNULL($var, $otherVal = NULL) {
        return (empty($var) ? $otherVal : $var);
    }

    function generaListEdifici($selected = NULL) {
        $res = $GLOBALS['c']->db->ql("SELECT e.ID,
                                                    		CONCAT(e.Foglio, ' -',
                                                    				GROUP_CONCAT(
                                                    					CONCAT(' ', m.Mappale, ' ', IF(m.EX IS NULL, '', m.EX))
                                                    					ORDER BY e.Foglio ASC, m.Mappale ASC, m.EX DESC)) tot
                                                            FROM pe_edifici e
                                                            	JOIN pe_mappali_edifici m ON e.ID = m.Edificio
                                                            GROUP BY e.ID");
        foreach ($res as $edificio)
            echo "  <option value=\"$edificio[ID]\"".($edificio['ID'] == $selected ? 'selected="selected"':'').">$edificio[tot]</option>";
    }

    function generaListIntestatariPersone($selected = NULL) {
        $res = $GLOBALS['c']->db->ql('SELECT ID, Cognome c, Nome n FROM intestatari_persone ORDER BY Cognome');
        foreach ($res as $persona)
            echo "  <option value=\"$persona[ID]\"".($persona['ID'] == $selected ? 'selected="selected"':'').">".str_replace('\'', '\\\'',$persona['c']).' '.str_replace('\'', '\\\'',$persona['n'])."</option>";
    }

    function generaListIntestatariSocieta($selected = NULL) {
        $res = $GLOBALS['c']->db->ql('SELECT ID, Intestazione i FROM intestatari_societa ORDER BY Intestazione');
        foreach ($res as $societa)
            echo "  <option value=\"$societa[ID]\"".($societa['ID'] == $selected ? 'selected="selected"':'').">".str_replace('\'', '\\\'',$societa['i'])."</option>";
    }

?>
<html lang="it">
	<head>
		<title>PE</title>

        <!-- AG-GRID -->
        <script src="https://unpkg.com/ag-grid-community/dist/ag-grid-community.min.js"></script>

		<!-- JQUERY -->
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"
  				integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0="
  				crossorigin="anonymous"></script>

		<link rel="stylesheet" href="lib/mini-default.min.css">
		<link rel="stylesheet" href="lib/fontawesome/css/all.css">
		<link rel="stylesheet" type="text/css" href="css/home.css">
    	<link rel="stylesheet" type="text/css" href="css/form.css">
		<style type="text/css">
    	   .hintBox{
    	       background-color: #272727;
    	       max-height: 200px;
    	       overflow-y: scroll;
    	   }
    	   .hintBox a{
    	       background-color: #272727;
    	       display: block;
    	       color: white;
    	   }
       </style>
       
        <script defer type="text/javascript" src="js/home.js"></script>

		<!-- OTHER -->
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" type="image/ico" href="favicon.ico">
	</head>
	<body>
        <p id="made-by">Made by Thomas P.</p>

		<div id="navbar" class="navbar sticky">
            <div class="dropdown active">
            	<button class="dropbtn">Interrogazioni<i class="fas fa-caret-down" style="margin-left: 10px;"></i></button>
                <div class="dropdown-content">
           		    <a onclick="changeContent('intEdificio');">Storico edificio</a>
                	<a onclick="changeContent('intAnag');">Anagrafiche</a>
                    <a onclick="changeContent('intPra');">Pratiche</a>
                    <a href="reports/pratiche.php">Pratiche da-a</a>
                </div>
            </div>

            <div class="dropdown active">
            	<button class="dropbtn">Inserimenti<i class="fas fa-caret-down"></i></button>
                <div class="dropdown-content">
                	<a href="inserimenti/praticaPE.php">Pratica</a>
                	<a href="inserimenti/praticaTEC.php">Pratica tec</a>
                	<a href="inserimenti/oneriEcostiTec.php">OU e CC Tec</a>
                	<a href="inserimenti/oneriEcostiPE.php">OU e CC PE</a>
                    <a onclick="changeContent('insAnagIntestPers');">Persona</a>
                    <a onclick="changeContent('insAnagTecnici');">Tecnico</a>
                    <a onclick="changeContent('insAnagSocieta');">Società</a>
                    <a onclick="changeContent('insAnagImprese');">Impresa</a>
                </div>
            </div>

 			<div class="dropdown active">
 				<button class="dropbtn">Altro<i class="fas fa-caret-down"></i></button>
                <div class="dropdown-content">
                	<a onclick="changeContent('modPra');">Modifica pratiche</a>
                	<a href="gestione/edifici.php">Gestione edifici</a>
                	<a href="gestione/pagamentiOneriEcostiTec.php">Gestione pagamenti CC e OU Tec</a>
                	<a href="gestione/pagamentiOneriEcostiPE.php">Gestione pagamenti CC e OU PE</a>
                	<a href="modulistica/">Modulistica</a>
            	</div>
 			</div>

 			<form action="index.php" method="post" id="logoutForm">
 				<button type="submit"  name="destroy" class="secondary" >Logout<i class="fas fa-sign-out-alt" style="margin-left: 10px;"></i></button>
 				<?=
 			        $GLOBALS['c']->isAdmin()?
 			        '<a target="_blank" href="gestione/utenti.php">Gestione utenti<i class="fas fa-cogs"></i></a>'
                    :'';
 				?>
 				<a>Impostazioni<i class="fas fa-cogs"></i>	</a>
 				<?=
 			        $GLOBALS['c']->isAdmin()?
 			        '<a target="_blank" href="/phpmyadmin/">Database<i class="fas fa-database"></i></a>'
                    :'';
 				?>
 			</form>
        </div>

        <h2 style="margin-top: 70px; text-align: center;">Comune di Canale d'Agordo - UTC</h2>

        <?php
        if(isset($GLOBALS['err'])&&!empty($GLOBALS['err']))
                 echo "<pre style=\"border-left-color: red;\" class=\"info\">$GLOBALS[err]</pre>";
         if($GLOBALS['c']->check(['succ'], $GLOBALS))
                echo "<pre class=\"info\">$GLOBALS[succ]</pre>";
            else if($GLOBALS['c']->check(['succ'], $_GET))
                echo "<pre class=\"info\">$_GET[succ]</pre>";
        ?>

        <div id="intAnag" class="content">
            <div class="header">
                <button data-target="gridpersone">Persone</button>
                <button data-target="gridsocieta">Societ&agrave;</button>
                <button data-target="gridtecnici">Tecnici</button>
                <button data-target="gridimprese">Imprese</button>
            </div>
            <div id="gridpersone" class="grid ag-theme-balham-dark"></div>
            <div id="gridsocieta" class="grid ag-theme-balham-dark"></div>
            <div id="gridtecnici" class="grid ag-theme-balham-dark"></div>
            <div id="gridimprese" class="grid ag-theme-balham-dark"></div>
        </div>

        <div id="intEdificio" class="content">
        	<div class="form">
        		<h1>Storico edificio</h1>

            	<form action="reports/edificio.php" method="post" target="_blank">
            		<div class="section">Dati</div>
            		<div class="inner-wrap">
            			<label>Foglio<input type="number" name="foglio" required="required" min="1" value="<?= $_POST['foglio']??'' ?>"></label>
            			<label>Mappale (qualsiasi)<input type="number" name="mappale" required="required" value="<?= $_POST['mappale']??'' ?>"></label>
            		</div>
            		<button type="submit">Genera report</button>
				</form>
			</div>
        </div>

        <div id="intPra" class="content">
            <div id="gridpratiche" class="ag-theme-balham-dark"></div>
        </div>

        <div id="insAnagIntestPers" class="content">
        	<div class="form">
        		<h1>Inserimento intestatari persone</h1>

            	<form action="" method="post">
            		<input type="hidden" name="tipo" value="persone">
            		<div class="section">Dati</div>
            		<div class="inner-wrap">
            			<label>Nome<input type="text" name="nome" required="required" value="<?= $_POST['nome']??'' ?>"></label>
            			<label>Cognome<input type="text" name="cognome" required="required" value="<?= $_POST['cognome']??'' ?>"></label>
            			<label>Codice fiscale<input type="text" name="cf" pattern="[A-Za-z]{6}[0-9]{2}[A-Za-z][0-9]{2}[A-Za-z][0-9]{3}[A-Za-z]" required="required" value="<?= $_POST['cf']??'' ?>"></label>
            			<label>Indirizzo<input type="text" name="indirizzo" value="<?= $_POST['indirizzo']??'' ?>"></label>
            			<label>Città<input type="text" name="citta" value="<?= $_POST['citta']??'' ?>"></label>
            			<label>Provincia (sigla)<input type="text" name="provincia" pattern="|[A-Z]{2}" value="<?= $_POST['provincia']??'' ?>"></label>
            		</div>
            		<button type="submit" name="btn" value="inserimentoAnagrafica">Inserisci</button>
				</form>
			</div>
        </div>

        <div id="insAnagTecnici" class="content">
        	<div class="form">
        		<h1>Inserimento Tecnici</h1>

            	<form action="" method="post">
            		<input type="hidden" name="tipo" value="tecnici">
            		<div class="section">Dati</div>
            		<div class="inner-wrap">
            			<label>Nome<input type="text" name="nome" required="required" value="<?= $_POST['nome']??'' ?>"></label>
            			<label>Cognome<input type="text" name="cognome" required="required" value="<?= $_POST['cognome']??'' ?>"></label>
            			<label>Codice fiscale<input type="text" name="cf" pattern="[A-Za-z]{6}[0-9]{2}[A-Za-z][0-9]{2}[A-Za-z][0-9]{3}[A-Za-z]" required="required" value="<?= $_POST['cf']??'' ?>"></label>
            			<label>Partita iva<input type="text" name="piva" pattern="\d{11}" value="<?= $_POST['piva']??'' ?>"></label>
            			<label>Albo<input type="text" name="albo" value="<?= $_POST['albo']??'' ?>"></label>
        				<label>Numero ordine<input type="number" name="numero_ordine" value="<?= $_POST['numero_ordine']??'' ?>"></label>
        				<label>Provincia albo (sigla)<input type="text" name="provincia_albo" pattern="|[A-Z]{2}" value="<?= $_POST['provincia_albo']??'' ?>"></label>
            			<label>Indirizzo<input type="text" name="indirizzo" value="<?= $_POST['indirizzo']??'' ?>"></label>
            			<label>Citt&agrave;<input type="text" name="citta" value="<?= $_POST['citta']??'' ?>"></label>
            			<label>Provincia (sigla)<input type="text" name="provincia" pattern="|[A-Z]{2}" value="<?= $_POST['provincia']??'' ?>"></label>
            			<label>Note<textarea rows="3" name="note"><?= $_POST['note']??'' ?></textarea></label>
            		</div>
            		<button type="submit" name="btn" value="inserimentoAnagrafica">Inserisci</button>
				</form>
			</div>
        </div>

         <div id="insAnagSocieta" class="content">
        	<div class="form">
        		<h1>Inserimento Societ&agrave;</h1>

            	<form action="" method="post">
            		<input type="hidden" name="tipo" value="societa">
            		<div class="section">Dati</div>
            		<div class="inner-wrap">
            			<label>Intestazione<input type="text" name="intestazione" required="required" value="<?= $_POST['intestazione']??'' ?>"></label>
            		    <label>Partita iva<input type="text" name="piva" required="required" pattern="\d{11}" value="<?= $_POST['piva']??'' ?>"></label>
            			<label>Indirizzo<input type="text" name="indirizzo" value="<?= $_POST['indirizzo']??'' ?>"></label>
            			<label>Citt&agrave;<input type="text" name="citta" value="<?= $_POST['citta']??'' ?>"></label>
            			<label>Provincia (sigla)<input type="text" name="provincia" pattern="|[A-Z]{2}" value="<?= $_POST['provincia']??'' ?>"></label>
            		</div>
            		<button type="submit" name="btn" value="inserimentoAnagrafica">Inserisci</button>
				</form>
			</div>
        </div>

        <div id="insAnagImprese" class="content">
        	<div class="form">
        		<h1>Inserimento Imprese</h1>

            	<form action="" method="post">
            		<input type="hidden" name="tipo" value="impresa">
            		<div class="section">Dati</div>
            		<div class="inner-wrap">
            			<label>Intestazione<input type="text" name="intestazione" required="required" value="<?= $_POST['intestazione']??'' ?>"></label>
            			<label>Codice fiscale<input type="text" name="cf" pattern="[A-Za-z]{6}[0-9]{2}[A-Za-z][0-9]{2}[A-Za-z][0-9]{3}[A-Za-z]" value="<?= $_POST['cf']??'' ?>"></label>
            		    <label>Partita iva<input type="text" name="piva" pattern="\d{11}" value="<?= $_POST['piva']??'' ?>"></label>
            		</div>
            		<button type="submit" name="btn" value="inserimentoAnagrafica">Inserisci</button>
				</form>
			</div>
        </div>
        
        <div id="modPra" class="content">
        	<div class="form">
            	<h1>Modifica pratiche</h1>

            	<form action="" method="post">
            		<input type="hidden" name="tipo" value="pratica">
            		<div class="inner-wrap">
                  <label>Tipologia di pratica</label>
                  <input type="radio" name="tipologia" value="pe"  <?= ($_REQUEST['tipologia']??'') == 'tec'?'':'checked' ?> onclick="$('#tipo-pratica-tec-modifiche').hide(); $('#tipo-pratica-pe-modifiche').show();"> PE
                  <?php 
                  $tmp = '';
                  if(($_REQUEST['tipologia']??'') == 'tec')
                      $tmp = 'checked';
                  ?>
                  <input type="radio" name="tipologia" value="tec" <?= $tmp ?> onclick="$('#tipo-pratica-pe-modifiche').hide(); $('#tipo-pratica-tec-modifiche').show();"> TEC
                  <label>Tipo</label>
                  <select id="tipo-pratica-pe-modifiche" name="tipo_pratica_pe">
                    <?php 
                    foreach ($GLOBALS['c']->getEnumValues('pe_pratiche', 'TIPO') as $tipo)  echo "<option value=\"$tipo\">$tipo</option>";
                    ?>
                  </select>
                  <select id="tipo-pratica-tec-modifiche" name="tipo_pratica_tec" style="display:none;">
                    <?php 
                    foreach ($GLOBALS['c']->getEnumValues('tec_pratiche', 'TIPO') as $tipo)  echo "<option value=\"$tipo\">$tipo</option>";
                    ?>
                  </select>
                  <?php 
                  if(($_REQUEST['tipologia']??'') == 'tec')
                    echo '<script>$(\'#tipo-pratica-pe-modifiche\').hide(); $(\'#tipo-pratica-tec-modifiche\').show();</script>';
                  ?>
                  <input type="checkbox" name="considerTipo" checked> Considera tipo
		               <label>Anno<input type="number" name="anno" pattern="\d{4}" value="<?= $_POST['anno']??date('Y') ?>"></label>
   	   				     <label>Numero<input type="number" name="numero" value="<?= $_POST['numero']??'' ?>"></label>
                   <label>Barrato<input type="text" name="barrato" value="<?= $_POST['barrato']??'' ?>"></label>
                   <label>Foglio<input type="text" maxlength="4" name="foglio" value="<?= $_POST['foglio']??'' ?>"></label>
                   <label>Mappale<input type="text" maxlength="6" name="mappale" value="<?= $_POST['mappale']??'' ?>"></label>
            		</div>
            		<input type="hidden" name="display" value="modPra">
            		<button type="submit" name="btn" value="modifica">Cerca</button>
            	</form>
        	</div>
        </div>

        <?php
        if($GLOBALS['c']->check(['btn', 'tipo'], $_POST)&&$_POST['btn'] == 'modifica'){
            include_once 'lib/reports.php';
            Reports::modificaPratiche($GLOBALS['c']->db, isset($_POST['considerTipo'])?$_POST['tipo_pratica_'.$_POST['tipologia']]:'', $_POST['anno'], $_POST['numero'], $_POST['barrato'], $_POST['foglio'], $_POST['mappale'], $_POST['tipologia']);
        }
        if($GLOBALS['c']->check(['btn', 'tipo'], $_POST)&&$_POST['btn'] == 'report'){
            include_once 'lib/reports.php';
            switch ($_POST['tipo']) {
                case 'persona':
                    Reports::anagraficaIntestatari($GLOBALS['c']->db, $_POST['nome'], $_POST['cognome'], $_POST['cf'], 'reports/anagrafica.php', 'persona','Visualizza/modifica');
                   break;

                case 'societa':
                    Reports::anagraficaSocieta($GLOBALS['c']->db, $_POST['intestazione'], $_POST['piva'], 'reports/anagrafica.php', 'societa', 'Visualizza/modifica');
                    break;

                case 'tecnico':
                    Reports::anagraficaTecnici($GLOBALS['c']->db, $_POST['nome'], $_POST['cognome'], $_POST['cf'], $_POST['piva']);
                    break;

                case 'impresa':
                    Reports::anagraficaImprese($GLOBALS['c']->db, $_POST['intestazione'], $_POST['cf'], $_POST['piva']);
                    break;

                case 'pratica':
                    switch ($_POST['tipologia']) {
                      case 'pe':
                        Reports::pratiche($GLOBALS['c']->db, isset($_POST['considerTipo'])?$_POST['tipo_pratica_pe']:'', $_POST['anno'], $_POST['numero'], $_POST['barrato'], $_POST['foglio'], $_POST['mappale']);
                        break;

                      case 'tec':
                          Reports::pratiche($GLOBALS['c']->db, isset($_POST['considerTipo'])?$_POST['tipo_pratica_tec']:'', $_POST['anno'], $_POST['numero'], $_POST['barrato'], $_POST['foglio'], $_POST['mappale'], 'tec');
                          break;

                      default:
                        break;
                    }
                    break;
                    
                case 'praticheIntestatario':
                    if($_POST['tipologia'] == 'persona')
                        Reports::anagraficaIntestatari($GLOBALS['c']->db, $_POST['nome'], $_POST['cognome'], $_POST['cf'], 'reports/praticheIntestatario.php', 'p_id', 'Visualizza pratiche');
                    else if($_POST['tipologia'] == 'societa')
                        Reports::anagraficaSocieta($GLOBALS['c']->db, $_POST['intestazione'], $_POST['piva'], 'reports/praticheIntestatario.php', 's_id', 'Visualizza pratiche');
                   break;

                default:
                    echo '<h1 style="color: red;">Report non supportato!<h1>';
                   break;
            }
            if($_POST['tipo'] == 'pratica')
              echo "<script>changeContent('intPra');</script>";
            else if($_POST['tipo'] == 'praticheIntestatario')
                echo "<script>directChgPratInt('$_POST[tipologia]'); changeContent('intPraticheInt');</script>";
            else
              echo "<script>directChg('$_POST[tipo]'); changeContent('intAnag');</script>";
        }
        if(isset($GLOBALS['err'])&&isset($_POST['btn'])&&$_POST['btn'] == 'inserimentoAnagrafica')
            switch ($_POST['tipo']) {
                case 'persone':
                    echo '<script>changeContent(\'insAnagIntestPers\');</script>';
                break;
                case 'societa':
                    echo '<script>changeContent(\'insAnagSocieta\');</script>';
                break;
                case 'tecnici':
                    echo '<script>changeContent(\'insAnagTecnici\');</script>';
                break;
                default:
                    ;
                break;
            }

        if(isset($_REQUEST['display']))
          echo "<script>changeContent('$_REQUEST[display]');</script>";
        ?>
	</body>
</html>
