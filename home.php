<?php
    include_once 'controls.php';
    $GLOBALS['c'] = new Controls();
    $err = $_GET['err']??'';

    if(!$GLOBALS['c']->logged()){
        header('Location: index.php?err=Utente non loggato');
        exit();
    }

   $GLOBALS['c']->echoCode($_POST);

   $esitoGestMapp = '';

    if($GLOBALS['c']->check(['btn', 'tipo'], $_POST)){
        switch ($_POST['btn']) {
            case 'inserimentoAnagrafica':
                inserimentoAnagrafiche();
                break;

            case 'inserimentoPratica':
                inserimentoPratiche();
                break;

            case 'mappaliEX':
                $esitoGestMapp = gestioneMappaliEX();
                $GLOBALS[$esitoGestMapp[1]?'err':'succ'] = $esitoGestMapp[0];
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

    function inserimentoPratiche() {
        switch ($_POST['tipo']) {
            case 'pe':
            inserisciPraticaPE();
            break;

            case 'tec':
            inserisciPraticaTEC();
            break;

            default:
            ;
            break;
        }
    }

    function inserisciPraticaPE() {
        if($GLOBALS['c']->check(['tipo_pratica', 'mappale', 'anno', 'numero', 'edificio'], $_POST)){
            $stmt = $GLOBALS['c']->db->dml(
                'INSERT INTO pe_pratiche (TIPO, Mappale, Subalterno, Anno, Numero, Barrato, `Data`, Protocollo, Edificio, Stradario, Tecnico, Impresa, Direzione_lavori, Intervento, Data_inizio_lavori, Documento_elettronico, Note)
                  VALUES (:tipo, :mapp, :sub, :anno, :numero, :barr, :data, :prot, :edificio, :strad, :tecnico, :imp, :dl, :interv, :data_il, :doc_el, :note)',
                [':tipo' => $_POST['tipo_pratica'],
                ':mapp' => $_POST['mappale'],
                ':sub' => getValueORNULL($_POST['subalterno']),
                ':anno' => $_POST['anno'],
                ':numero' => $_POST['numero'],
                ':barr' => $_POST['barrato'],
                ':data' => getValueORNULL($_POST['data']),
                ':prot' => getValueORNULL($_POST['protocollo']),
                ':edificio' => $_POST['edificio'],
                ':strad' => getValueORNULL($_POST['stradario']),
                ':tecnico' => getValueORNULL($_POST['tecnico']),
                ':imp' => getValueORNULL($_POST['impresa']),
                ':dl' => getValueORNULL($_POST['direzione_lavori']),
                ':interv' => getValueORNULL($_POST['intervento']),
                ':data_il' => getValueORNULL($_POST['data_inizio_lavori']),
                ':doc_el' => getValueORNULL($_POST['documento_elettronico']),
                ':note' => getValueORNULL($_POST['note'])]);

            if($stmt->errorInfo()[0] == 0){
                $GLOBALS['succ'] = 'Pratica inserita correttamente';

                $idPratica =  $GLOBALS['c']->db->ql(
                    'SELECT ID FROM pe_pratiche WHERE TIPO = ? AND Anno = ? AND Numero = ? AND Barrato = ?',
                    [$_POST['tipo_pratica'], $_POST['anno'], $_POST['numero'], $_POST['barrato']])[0]['ID'];

                 //inserimento intestatari persone
                $i = 0;
                $tryNext = true;
                while ($tryNext) {
                    if(isset($_POST['intestatario_persona_'.$i])){
                        $persona = $_POST['intestatario_persona_'.$i];
                        $res = $GLOBALS['c']->db->dml('INSERT INTO pe_intestatari_persone_pratiche (Pratica, Persona)
                                                                    VALUES(?, ?)', [$idPratica, $persona]);
                        if($res->errorInfo()[0] != 0) print_r($res->errorInfo());
                    }else
                $tryNext = false;
                    $i++;
                }

                //inserimento intestatari societa
                $i = 0;
                $tryNext = true;
                while ($tryNext) {
                    if(isset($_POST['intestatario_societa_'.$i])){
                        $societa = $_POST['intestatario_societa_'.$i];
                        $res = $GLOBALS['c']->db->dml('INSERT INTO pe_intestatari_societa_pratiche (Pratica, Societa)
                                                                    VALUES(?, ?)', [$idPratica, $societa]);
                        if($res->errorInfo()[0] != 0) print_r($res->errorInfo());
                    }else
                        $tryNext = false;
                        $i++;
                }
            }else
            $GLOBALS['err'] = 'Impossibile inserire la pratica: '.$stmt->errorInfo()[2];
        }else
            $GLOBALS['err'] = 'Dati inseriti non corretti: valori mancanti';
    }

    function inserisciPraticaTEC() {
        ;//TODO
    }

    function inserisciPersona() {
        if($GLOBALS['c']->check(['nome', 'cognome', 'cf'], $_POST)){
            $stmt = $GLOBALS['c']->db->dml(
                'INSERT INTO intestatari_persone (Nome, Cognome, Codice_fiscale, Indirizzo, Citta, Provincia, Note)
                VALUES (:n, :c, :cf, :ind, :citta, :prov, :note)',
                [':n' => $_POST['nome'],
                ':c' => $_POST['cognome'],
                ':cf' => $_POST['cf'],
                ':ind' => getValueORNULL($_POST['indirizzo']),
                ':citta' => getValueORNULL($_POST['citta']),
                ':prov' => getValueORNULL($_POST['provincia']),
                ':note' => getValueORNULL($_POST['note'])]);

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
                'INSERT INTO intestatari_societa (Intestazione, Partita_iva, Indirizzo, Citta, Provincia, Note)
                VALUES (:int, :piva, :ind, :citta, :prov, :note)',
                [':int' => $_POST['intestazione'],
                ':piva' => $_POST['piva'],
                ':ind' => getValueORNULL($_POST['indirizzo']),
                ':citta' => getValueORNULL($_POST['citta']),
                ':prov' => getValueORNULL($_POST['provincia']),
                ':note' => getValueORNULL($_POST['note'])]);

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
                'INSERT INTO imprese (Intestazione, Codice_fiscale, Partita_iva, Note)
                 VALUES (:int, :cf, :piva, :note)',
                [':int' => $_POST['intestazione'],
                ':piva' => getValueORNULL($_POST['piva']),
                ':cf' => getValueORNULL($_POST['cf']),
                ':note' => getValueORNULL($_POST['note'])]);

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

    function gestioneMappaliEX() {
        $foglio = $_POST['foglio'];
        $mappale = $_POST['mappale'];
        $ex = (($_POST['ex'] === 'yes') ? TRUE : (($_POST['ex'] === 'no') ? FALSE : NULL));

        if(!is_numeric($foglio)||is_null($ex))
            return ['Richiesta non valida!', TRUE];

        $res = $GLOBALS['c']->db->ql(
            'SELECT *
            FROM pe_mappali_edifici m
            JOIN pe_edifici e ON e.ID = m.Edificio
            WHERE m.Mappale = :mapp AND e.Foglio = :fg',
            [':mapp' => $mappale,
            ':fg' => $foglio]);

        if(count($res) == 0)
            return ["La coppia foglio $foglio, mappale $mappale non esiste", TRUE];

        $res = $GLOBALS['c']->db->dml(
            'UPDATE pe_mappali_edifici m
            JOIN pe_edifici e ON e.ID = m.Edificio
            SET m.EX = :ex
            WHERE m.Mappale = :mapp AND e.Foglio = :fg',
            [':ex' => $ex?'EX':NULL,
            ':mapp' => $mappale,
            ':fg' => $foglio]);

            return $res->rowCount() == 0 ? ['Non è stato necessario apportare modifiche', FALSE] : ['Modifiche apportate con successo', FALSE];
    }
?>
<html lang="it">
	<head>
		<title>PE</title>
		<!-- CSS -->
		<script src="lib/jquery-3.3.1.min.js"></script>
		<link rel="stylesheet" href="/lib/mini-default.min.css">
		<link rel="stylesheet" href="/lib/fontawesome/css/all.css">
		<link rel="stylesheet" type="text/css" href="/css/home.css">
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

		<!-- OTHER -->
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" type="image/ico" href="favicon.ico">
	</head>
	<body>
		<script type="text/javascript" src="/js/home.js"></script>

		<div id="navbar" class="navbar sticky">
            <div class="dropdown active">
            	<button class="dropbtn">Interrogazioni<i class="fas fa-caret-down" style="margin-left: 10px;"></i></button>
                <div class="dropdown-content">
           		    <a onclick="changeContent('intEdificio');">Storico edificio</a>
                	<a onclick="changeContent('intAnag');">Anagrafiche</a>
                    <a onclick="changeContent('intPra');">Pratiche</a>
                </div>
            </div>

            <div class="dropdown active">
            	<button class="dropbtn">Inserimenti<i class="fas fa-caret-down"></i></button>
                <div class="dropdown-content">
                	<a onclick="changeContent('insPratiche');">Pratica</a>
                	<a onclick="changeContent('insPraticheTec');">Pratica tec</a>
                    <a onclick="changeContent('insAnagIntestPers');">Persona</a>
                    <a onclick="changeContent('insAnagTecnici');">Tecnico</a>
                    <a onclick="changeContent('insAnagSocieta');">Società</a>
                    <a onclick="changeContent('insAnagImprese');">Impresa</a>
                </div>
            </div>

 			<div class="dropdown active">
 				<button class="dropbtn">Altro<i class="fas fa-caret-down"></i></button>
                <div class="dropdown-content">
                	<a onclick="changeContent('gestMappEX');">Gestione mappali EX</a>
                	<a href="/gestione/edifici.php">Gestione edifici</a>
            	</div>
 			</div>

 			<form action="index.php" method="post" id="logoutForm">
 				<button type="submit"  name="destroy" class="secondary" >Logout<i class="fas fa-sign-out-alt" style="margin-left: 10px;"></i></button>
 				<?=
 			        $GLOBALS['c']->isAdmin()?
 			        '<a target="_blank" href="/gestione/utenti.php">Gestione utenti<i class="fas fa-cogs"></i></a>'
                    :'';
 				?>
 				<a>Impostazioni<i class="fas fa-cogs"></i>	</a>
 				<a target="_blank" href="/phpmyadmin/">Database<i class="fas fa-database"></i></a>
 			</form>
        </div>

        <h2 style="margin-top: 70px; text-align: center;">Comune di Canale d'Agordo - UTC</h2>

        <?php
        if(isset($GLOBALS['err'])&&!empty($GLOBALS['err']))
                 echo "<pre style=\"border-left-color: red;\" class=\"info\">$GLOBALS[err]</pre>";
         if(isset($GLOBALS['succ'])&&!empty($GLOBALS['succ']))
                echo "<pre class=\"info\">$GLOBALS[succ]</pre>";
        ?>

        <div id="intAnag" class="content">
        	<div class="form">
        		<h1>Report anagrafiche</h1>

            	<form action="" method="post">

            		<div class="section">Tipologia</div>
            		<script type="text/javascript">
						function chg(el) {
							directChg(el.value);
						}
						function directChg(val) {
							switch (val) {
							case 'impresa':
								hide('nome-cognome'); show('piva'); show('intestazione'); show('cf');
								break;

							case 'persona':
								hide('intestazione'); hide('piva'); show('nome-cognome'); show('cf');
								break;

							case 'societa':
								hide('nome-cognome'); show('piva'); show('intestazione'); hide('cf');
								break;

							case 'tecnico':
								hide('intestazione'); show('piva'); show('nome-cognome'); show('cf');
								break;

							default:
								break;
							}
							var sel = document.getElementById('tipo');
						  	var opts = sel.options;
						  	for (var opt, j = 0; opt = opts[j]; j++)
						    	if (opt.value == val) {
    						      sel.selectedIndex = j;
    						      break;
    						    }
						}
            		</script>
            		<div class="inner-wrap">
							<select id="tipo" name="tipo" onchange="chg(this);">
								<option value="persona" selected="selected">Intestatario persona</option>
								<option value="societa">Intestatario società</option>
                    			<option value="tecnico">Tecnico</option>
                    			<option value="impresa">Impresa</option>
                    		</select>
            		</div>

            		<div class="section">Dati personali</div>
            		<div class="inner-wrap">
            			<div id="nome-cognome">
            				<label>Nome<input type="text" name="nome" value="<?= $_POST['nome']??'' ?>"></label>
                			<label>Cognome<input type="text" name="cognome" value="<?= $_POST['cognome']??'' ?>"></label>
            			</div>
            			<div id="intestazione" style="display: none;">
            				<label>Intestazione<input type="text" name="intestazione" value="<?= $_POST['intestazione']??'' ?>"></label>
            			</div>
            			<div id="cf">
            				<label>Codice fiscale<input type="text" name="cf" value="<?= $_POST['cf']??'' ?>"></label>
            			</div>
                		<div id="piva" style="display: none;">
            				<label>Partita iva<input type="text" name="piva" value="<?= $_POST['piva']??'' ?>"></label>
            			</div>
            		</div>

            		<button type="submit" name="btn" value="reportAnagrafica">Cerca</button>
            	</form>
        	</div>
        	<?php
        	if($GLOBALS['c']->check(['btn', 'tipo'], $_POST)&&$_POST['btn'] == 'reportAnagrafica'){
        	    include_once 'lib/reports.php';
        	    switch ($_POST['tipo']) {
        	        case 'persona':
        	            Reports::anagraficaIntestatario($GLOBALS['c']->db, $_POST['nome'], $_POST['cognome'], $_POST['cf']);
        	           break;

        	        case 'societa':
        	            Reports::anagraficaSocieta($GLOBALS['c']->db, $_POST['intestazione'], $_POST['piva']);
        	            break;

        	        case 'tecnico':
        	            Reports::anagraficaTecnico($GLOBALS['c']->db, $_POST['nome'], $_POST['cognome'], $_POST['cf'], $_POST['piva']);
        	            break;

        	        case 'impresa':
        	            Reports::anagraficaImprese($GLOBALS['c']->db, $_POST['intestazione'], $_POST['cf'], $_POST['piva']);
        	            break;

        	        case 'pratica':
        	            //TODO
        	            break;

        	        default:
        	            echo '<h1 style="color: red;">Report non supportato!<h1>';
        	           break;
        	    }
        	    echo "<script>directChg('$_POST[tipo]')</script>";
        	}

    	    ?>
        </div>

        <div id="intPra" class="content">
        	<div class="form">
            	<h1>Report pratiche</h1>

            	<form action="" method="post">
            		<input type="hidden" name="tipo" value="pratica">
            		<div class="inner-wrap">
            			<label>Tipo
                			<select name="tipo_pratica">
                				<option value="SCIA">SCIA</option>
                				<option value="CILA">CILA</option>
                				<option value="DIA">DIA</option>
                				<option value="CIL">CIL</option>
                				<option value="PERMESSI">Permessi</option>
                				<option value="VARIE">Varie</option>
            				</select>
        				</label>
            			<label>Anno<input type="number" name="anno" pattern="\d{4}" value="<?= $_POST['anno']??date('Y') ?>"></label>
     	   				<label>Numero<input type="number" name="numero" value="<?= $_POST['numero']??'' ?>"></label>
     	   				<label>Edificio (Foglio - Mappale/i)<br><select name="edificio" class="js-example-basic-single" style="width: 100%;"><?php generaListEdifici($_POST['edificio']??NULL); ?></select></label>
            		</div>
            		<button type="submit" name="btn" value="reportPratiche">Cerca</button>
            	</form>
        	</div>
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
            			<label>Note<textarea rows="3" name="note"><?= $_POST['note']??'' ?></textarea></label>
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
            			<label>Città<input type="text" name="citta" value="<?= $_POST['citta']??'' ?>"></label>
            			<label>Provincia (sigla)<input type="text" name="provincia" pattern="|[A-Z]{2}" value="<?= $_POST['provincia']??'' ?>"></label>
            			<label>Note<textarea rows="3" name="note"><?= $_POST['note']??'' ?></textarea></label>
            		</div>
            		<button type="submit" name="btn" value="inserimentoAnagrafica">Inserisci</button>
				</form>
			</div>
        </div>

         <div id="insAnagSocieta" class="content">
        	<div class="form">
        		<h1>Inserimento Società</h1>

            	<form action="" method="post">
            		<input type="hidden" name="tipo" value="societa">
            		<div class="section">Dati</div>
            		<div class="inner-wrap">
            			<label>Intestazione<input type="text" name="intestazione" required="required" value="<?= $_POST['intestazione']??'' ?>"></label>
            		    <label>Partita iva<input type="text" name="piva" required="required" pattern="\d{11}" value="<?= $_POST['piva']??'' ?>"></label>
            			<label>Indirizzo<input type="text" name="indirizzo" value="<?= $_POST['indirizzo']??'' ?>"></label>
            			<label>Città<input type="text" name="citta" value="<?= $_POST['citta']??'' ?>"></label>
            			<label>Provincia (sigla)<input type="text" name="provincia" pattern="|[A-Z]{2}" value="<?= $_POST['provincia']??'' ?>"></label>
            			<label>Note<textarea rows="3" name="note"><?= $_POST['note']??'' ?></textarea></label>
            		</div>
            		<button type="submit" name="btn" value="inserimentoAnagrafica">Inserisci</button>
				</form>
			</div>
        </div>

        <div id="insAnagImprese" class="content">
        	<div class="form">
        		<h1>Inserimento Impresa</h1>

            	<form action="" method="post">
            		<input type="hidden" name="tipo" value="impresa">
            		<div class="section">Dati</div>
            		<div class="inner-wrap">
            			<label>Intestazione<input type="text" name="intestazione" required="required" value="<?= $_POST['intestazione']??'' ?>"></label>
            			<label>Codice fiscale<input type="text" name="cf" pattern="[A-Za-z]{6}[0-9]{2}[A-Za-z][0-9]{2}[A-Za-z][0-9]{3}[A-Za-z]" value="<?= $_POST['cf']??'' ?>"></label>
            		    <label>Partita iva<input type="text" name="piva" pattern="\d{11}" value="<?= $_POST['piva']??'' ?>"></label>
            			<label>Note<textarea rows="3" name="note"><?= $_POST['note']??'' ?></textarea></label>
            		</div>
            		<button type="submit" name="btn" value="inserimentoAnagrafica">Inserisci</button>
				</form>
			</div>
        </div>

        <div id="insPratiche" class="content">
        	<div class="form">
        		<h1>Inserimento pratiche</h1>

            	<form action="" method="post">
            		<input type="hidden" name="tipo" value="pe">
            		<div class="section">Dati</div>
            		<div class="inner-wrap">
            			<label>Tipo
                			<select name="tipo_pratica">
                				<option value="SCIA">SCIA</option>
                				<option value="CILA">CILA</option>
                				<option value="DIA">DIA</option>
                				<option value="CIL">CIL</option>
                				<option value="PERMESSI">Permessi</option>
                				<option value="VARIE">Varie</option>
                			</select>
            			</label>
            			<label>Mappale/i<input type="text" name="mappale" pattern="^(\d{1,4}-)*\d{1,4}$" required="required" value="<?= $_POST['mappale']??'' ?>"></label>
            			<label>Subalterno/i<input type="text" name="subalterno" pattern="^(\d{1,4}-)*\d{1,4}$" value="<?= $_POST['subalterno']??'' ?>"></label>
         	   			<label>Anno<input type="number" name="anno" required="required" pattern="\d{4}" value="<?= $_POST['anno']??date('Y') ?>"></label>
         	   			<label>Numero<input type="number" name="numero" required="required" value="<?= $_POST['numero']??'' ?>"></label>
         	   			<label>Barrato<input type="text" name="barrato" value="<?= $_POST['barrato']??'' ?>"></label>
         	   			<label>Data<input type="date" name="data" value="<?= $_POST['data']??date('Y-m-d') ?>"></label>
         	   			<label>Protocollo<input type="number" name="protocollo" value="<?= $_POST['protocollo']??'' ?>"></label>
         	   			<label>Edificio (Foglio - Mappale/i)<br><select name="edificio" class="js-example-basic-single" style="width: 100%;"><?php generaListEdifici($_POST['edificio']??NULL); ?></select></label>
         	   			<div class="extensible">
         	   				<label>Intestatari persone<br>
             	   				<div id="fieldsIntPers"></div>
         	   				</label>
         	   				<div style="display:inline-flex;">
         	   					<button type="button" style="background-color:red;" onclick="genPers.removeField();">-</button>
             	   				<button type="button" onclick="genPers.addField();">+</button>
         	   				</div>
            			</div>
            			<div class="extensible">
         	   				<label>Intestatari societ&aacute<br>
             	   				<div id="fieldsIntSoc"></div>
         	   				</label>
         	   				<div style="display:inline-flex;">
         	   					<button type="button" style="background-color:red;" onclick="genSoc.removeField();">-</button>
             	   				<button type="button" onclick="genSoc.addField();">+</button>
         	   				</div>
            			</div>



            			<label>Stradario<br>
         	   				<input id="stradario" type="text" onkeyup="updateHints('stradario', this, '#hintsStradari', '#stradarioID');" onclick="this.select();">
         	   				<input id="stradarioID" name="stradario" type="hidden">
     	   				</label>
         	   			<div id="hintsStradari" class="hintBox"></div>

         	   			<label>Tecnico<br>
         	   				<input id="tecnico" type="text" onkeyup="updateHints('tecnico', this, '#hintsTecnici', '#tecnicoID');" onclick="this.select();">
         	   				<input id="tecnicoID" name="tecnico" type="hidden">
     	   				</label>
         	   			<div id="hintsTecnici" class="hintBox"></div>

         	   			<label>Impresa<br>
         	   				<input id="impresa" type="text" onkeyup="updateHints('impresa', this, '#hintsImprese', '#impresaID');" onclick="this.select();">
         	   				<input id="impresaID" name="impresa" type="hidden">
     	   				</label>
         	   			<div id="hintsImprese" class="hintBox"></div>

         	   			<label>Direzione lavori<br>
         	   				<input id="direzione_lavori" type="text" onkeyup="updateHints('tecnico', this, '#hintsDirezione_lavori', '#direzione_lavoriID');" onclick="this.select();">
         	   				<input id="direzione_lavoriID" name="direzione_lavori" type="hidden">
     	   				</label>
         	   			<div id="hintsDirezione_lavori" class="hintBox"></div>

         	   			<?php 
                	    //TODO altri 
                	    ?>

         	   			<label>Intervento<textarea rows="3" name="intervento"><?= $_POST['intervento']??'' ?></textarea></label>
         	   			<label>Documento elettronico<input type="text" name="documento_elettronico" value="<?= $_POST['documento_elettronico']??'' ?>"></label>
         	   			<label>Data inizio lavori<input type="date" name="data_inizio_lavori" value="<?= $_POST['data_inizio_lavori']??'' ?>"></label>
           		    	<label>Note<textarea rows="3" name="note"><?= $_POST['note']??'' ?></textarea></label>
            		</div>
            		<button type="submit" name="btn" value="inserimentoPratica">Inserisci</button>
				</form>
			</div>
        </div>

        <div id="intEdificio" class="content">
        	<div class="form">
        		<h1>Storico edificio</h1>

            	<form action="reports/edificio.php" method="post" target="_blank">
            		<div class="section">Dati</div>
            		<div class="inner-wrap">
            			<label>Foglio<input type="number" name="foglio" required="required" value="<?= $_POST['foglio']??'' ?>"></label>
            			<label>Mappale (qualsiasi)<input type="number" name="mappale" required="required" value="<?= $_POST['mappale']??'' ?>"></label>
            		</div>
            		<button type="submit">Genera report</button>
				</form>
			</div>
        </div>

        <div id="gestMappEX" class="content">
        	<div class="form">
        		<h1>Gestione mappali EX</h1>

            	<form method="post">
            		<input type="hidden" name="tipo">
            		<div class="inner-wrap">
            			<label>Foglio<input type="text" name="foglio" pattern="\d{1,2}" required="required" value="<?= $_POST['foglio']??'' ?>"></label>
            			<!-- pattern="\d{1,4}[A-Za-z]" -->
            			<label>Mappale<input type="text" name="mappale" required="required" value="<?= $_POST['mappale']??'' ?>"></label>
            			<label>
            				Normale<input type="radio" name="ex" value="no" <?= isset($_POST['ex'])?$_POST['ex']=='no'?'checked="checked"':'':'checked="checked"' ?>>
            				<br>
            				Ex<input type="radio" name="ex" value="yes" <?= isset($_POST['ex'])&&$_POST['ex']=='yes'?'checked="checked"':'' ?>></label>
            		</div>
            		<button type="submit" name="btn" value="mappaliEX">Modifica</button>
				</form>
			</div>
        </div>
        <?= isset($_POST['btn'])&&$_POST['btn']=='mappaliEX'?'<script type="text/javascript">changeContent(\'gestMappEX\');</script>':'' ?>

        <script type="text/javascript">
    		var genPers = new FieldsGenIntPers(document.getElementById('fieldsIntPers'), '<?php generaListIntestatariPersone(); ?>');
    		var genSoc = new FieldsGenIntSoc(document.getElementById('fieldsIntSoc'), '<?php generaListIntestatariSocieta(); ?>');
		</script>

        <?php
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
        ?>
	</body>
</html>
