<?php
    include_once '../controls.php';
    $c = new Controls();
    
    if(!$c->logged()){
      header('Location: ../index.php?err=Utente non loggato');
      exit();
    }
    
    //print_r($_POST);

    $infos = [];
    $errors = [];
    if($c->check(['tipo', 'anno', 'numero'], $_POST)){
        
        $keys = ['tipo', 'anno', 'numero', 'oggetto', 'stradario', 'civico', 'n_protocollo', 'n_verbale', 'verbale', 'prescrizioni', 'parere', 'parere_note', 'approvata', 'onerosa', 'beni_ambientali', 'note_pagamenti', 'note_pratica'];
        $cols = [];
        $params = [];
        foreach ($_POST as $key => $value) 
            if(in_array($key, $keys)||substr($key, 0, 5) == 'Data_') {
                $cols[] = $key;
                $params[] = ifEmptyGet($_POST[$key]);
            }
        
        $res = $c->db->dml(
            'INSERT INTO tec_pratiche ('.implode(',', $cols).')
              VALUES (?'.str_repeat(',?', (count($cols)-1)).')',
            $params);

        if($res->errorCode() == 0){
            $infos[] = 'Pratica inserita correttamente';

            $idPratica =  $c->db->ql(
                'SELECT ID
                FROM tec_pratiche
                WHERE TIPO = ? AND Anno = ? AND Numero = ? AND Barrato = ?',
                [$_POST['tipo'], $_POST['anno'], $_POST['numero'], $_POST['barrato']])[0]['ID'];

            //inserimento edifici
            foreach ($_POST as $key => $value)
                if(substr($key, 0, strlen('edificio')) == 'edificio'&&$value){
                    $res = $c->db->dml('INSERT INTO tec_edifici_pratiche (Pratica, Edificio)
                                                    VALUES(?, ?)', [$idPratica, $value]);
                    if($res->errorCode() == 0) $infos[] = "Pratica $_POST[tipo]$_POST[anno]/$_POST[numero]$_POST[barrato] associata all'edificio N° $value";
                    else                         $errors[] = "Impossibile associare la pratica $_POST[tipo]$_POST[anno]/$_POST[numero]$_POST[barrato] all'edificio N° $value: ".$res->errorInfo()[2];
                }

            //inserimento fogli-mappali
            foreach ($_POST as $key => $value)
                if(substr($key, 0, strlen('foglio-mappale')) == 'foglio-mappale'&&is_numeric(substr($key, strlen('foglio-mappale'), strlen($key)))&&$value){
                    $tmp = explode('-', $value);
                    $foglio = $tmp[0];
                    $mappale = $tmp[1];
                    $edificio = $c->getEdificioID($foglio, $mappale, $c->db);

                    if($edificio){
                        $res = $c->db->dml('INSERT INTO tec_fogli_mappali_pratiche (Pratica, Edificio, Foglio, Mappale)
                                                            VALUES(?, ?, ?, ?)', [$idPratica, $edificio, $foglio, $mappale]);

                        if($res->errorCode() == 0) $infos[] = "F.$foglio m.$mappale associato alla pratica";
                        else                         $errors[] = "Impossibile associare F.$foglio m.$mappale alla pratica: ".$res->errorInfo()[2];
                    }else
                        $errors[] = "Impossibile trovare F.$foglio m.$mappale: ".$res->errorInfo()[2];
                }

            //inserimento subalterni
            foreach ($_POST as $key => $value)
                if(substr($key, 0, strlen('foglio-mappale-subalterno')) == 'foglio-mappale-subalterno'&&$value){
                    $tmp = explode('-', $value);
                    $foglio = $tmp[0];
                    $mappale = $tmp[1];
                    $subalterno = $tmp[2];
                    $edificio = $c->getEdificioID($foglio, $mappale, $c->db);

                    if($edificio){
                        $res = $c->db->dml('INSERT INTO tec_subalterni_pratiche (Pratica, Edificio, Foglio, Mappale, Subalterno)
                                                    VALUES(?, ?, ?, ?, ?)',
                            [$idPratica, $edificio, $foglio, $mappale, $subalterno]);
                        if($res->errorCode() == 0) $infos[] = "Subalterno $subalterno del F.$foglio m.$mappale associato alla pratica";
                        else                         $errors[] = "Impossibile associare subalterno $subalterno del F.$foglio m.$mappale alla pratica: ".$res->errorInfo()[2];
                    }else
                        $errors[] = "Impossibile trovare F.$foglio m.$mappale: ".$res->errorInfo()[2];
                }

            //inserimento intestatari e tecnici
            foreach ($_POST as $key => $value)
                if(substr($key, 0, strlen('intestatarioPersona')) == 'intestatarioPersona'&&$value){

                    $res = $c->db->dml('INSERT INTO tec_intestatari_persone_pratiche (Pratica, Persona)
                                                        VALUES(?, ?)', [$idPratica, $value]);
                    if($res->errorCode() == 0) $infos[] = "Pratica $_POST[tipo]$_POST[anno]/$_POST[numero]$_POST[barrato] associata ad un intestatario persona ($value)";
                    else                         $errors[] = "Impossibile associare la pratica $_POST[tipo]$_POST[anno]/$_POST[numero]$_POST[barrato] all'intestatario persona $value: ".$res->errorInfo()[2];
            }else if(substr($key, 0, strlen('intestatarioSocieta')) == 'intestatarioSocieta'&&$value){

                    $res = $c->db->dml('INSERT INTO tec_intestatari_societa_pratiche (Pratica, Societa)
                                                        VALUES(?, ?)', [$idPratica, $value]);
                    if($res->errorCode() == 0) $infos[] = "Pratica $_POST[tipo]$_POST[anno]/$_POST[numero]$_POST[barrato] associata ad un intestatario societ&agrave; ($value)";
                    else                         $errors[] = "Impossibile associare la pratica $_POST[tipo]$_POST[anno]/$_POST[numero]$_POST[barrato] all'intestatario societ&agrave; $value: ".$res->errorInfo()[2];
            }else if(substr($key, 0, strlen('tecnico')) == 'tecnico'&&$value){
            	
            	$res = $c->db->dml('INSERT INTO tec_tecnici_pratiche (Pratica, Tecnico)
                                                        VALUES(?, ?)', [$idPratica, $value]);
            	if($res->errorCode() == 0) $infos[] = "Pratica $_POST[tipo]$_POST[anno]/$_POST[numero]$_POST[barrato] associata ad un tecnico ($value)";
            	else                         $errors[] = "Impossibile associare la pratica $_POST[tipo]$_POST[anno]/$_POST[numero]$_POST[barrato] al tecnico $value: ".$res->errorInfo()[2];
            }

        }else
          $errors[] = 'Impossibile inserire la pratica: '.$res->errorInfo()[2];
    }


  //Misc functions
  function ifEmptyGet($val, $valIfEmpty = NULL){
    return empty($val)?$valIfEmpty:$val;
  }

?>
<html>
<head>
    <title>Inserimento pratica TEC</title>
    <script src="../lib/jquery-3.3.1.min.js"></script>
    <script src="../js/hints.js"></script>
    <script src="../js/misc.js"></script>
    <link rel="stylesheet" type="text/css" href="../lib/fontawesome/css/all.css">
    <link rel="stylesheet" type="text/css" href="../css/form.css">
    <link rel="stylesheet" type="text/css" href="../css/alerts.css">
    <link rel="stylesheet" type="text/css" href="../css/utils_bar.css">
	<link rel="stylesheet" type="text/css" href="../css/inserimento_pratica.css">
</head>
<body>

  <?php
  $c->includeHTML('../htmlUtils/utils_bar.html');

  if(count($errors) > 0)
      echo "<script>displayMessage('Errori: ".str_replace('\'', '\\\'', implode('<br>', $errors)).'\', document.body)</script>';
  if(count($infos) > 0)
      echo "<script>displayMessage('Info: ".str_replace('\'', '\\\'', implode('<br>', $infos))."', document.body, 'info');</script>";
  ?>

  <div class="form">
    <h1>Inserimento pratiche TEC<span id="info-edificio"></span></h1>
      <input type="hidden" name="tipo" value="pe">

      <div id="dati-edificio">

        <div class="section">Selezione edificio/i</div>
        <div class="inner-wrap">
          <form id="ricerca-edificio">
            <input type="hidden" name="action" value="searchEdificio">
            <input name="foglio" type="number" placeholder="Foglio..." autofocus>
            <input name="mappale" type="number" placeholder="Mappale...">
          </form>
          <h3 class="centered">Risultati ricerca</h3>
          <div id="risultati-ricerca-edificio" class="box-risultati"></div>

          <h3 class="centered">Edifici selezionati</h3>
		  <div id="edifici-selezionati" class="box-risultati"></div>
          <button type="button" onclick="freezeEdifici();">Conferma edificio/i</button>

        </div>
      </div>

      <form id="form-pratica" method="post">
        <div id="dati-pratica">
        <div class="section">Azioni</div>
          <div id="bottoni-utili" class="inner-wrap">
          	<button type="button" onclick="backToEdificiSelection();">Indietro</button>
          	<button type="button" onclick="refreshMappaliESubalterni();">Ricarica mappali<br>e subalterni</button>
          </div>
          <div class="section">Mappali e subalterni</div>
          <div class="inner-wrap">
            <div class="field">
            	 <label>Mappale/i</label>
      		     <div id="mappali"></div>
    		       <button type="button" onclick="addFieldFoglioMappale();">Aggiungi foglio-mappale</button>
            </div>
            <div class="field">
              	<label>Subalterno/i</label>
      		      <div id="subalterni"></div>
      		      <button type="button" onclick="addFieldSubalterno();">Aggiungi subalterno</button>
            </div>
          </div>

          <div class="section">Identificativo pratica</div>
          <div class="inner-wrap">
            <div class="field">
              <label>Tipo</label>
              <select id="tipo-pratica" name="tipo">
                <?php
                $types = $c->getEnumValues('tec_pratiche', 'Tipo', $c->db);
                foreach ($types as $type) echo "<option value=\"$type\">$type</option>";
                ?>
              </select>
            </div>

            <div class="field">
              <label>Anno</label>
              <input type="number" name="anno" desc="tec" required="required" pattern="\d{4}">
            </div>

            <div class="field">
              <label>Numero</label>
              <input type="number" name="numero" required="required" pattern="\d{4}">
            </div>

            <div class="field">
              <label>Barrato</label>
              <input type="text" name="barrato">
            </div>

          </div>

          <div class="section">Persone correlate</div>
          <div class="inner-wrap">
            <div class="field">
              <label>Intestatari persone</label>
              <div id="fieldsIntPers"></div>
              <button type="button" onclick="addFieldIntestatarioPersona();">Aggiungi intestatario persona</button>
            </div>

            <div class="field">
              	<label>Intestatari societ&aacute;</label>
              	<div id="fieldsIntSoc"></div>
				<button type="button" onclick="addFieldIntestatarioSocieta();">Aggiungi intestatario societ&aacute;</button>
            </div>

            <div class="field">
              <label>Tecnici</label>
              <div id="fieldsTecnici"></div>
			  <button type="button" onclick="addFieldTecnico();">Aggiungi tecnico</button>
            </div>

          </div>

          <div class="section">Altre informazioni</div>
          	<div class="inner-wrap">
          
          	<div class="field">
              <label>Oggetto</label>
              <textarea rows="3" name="oggetto"></textarea>
            </div>

            <div class="field">
              <label>Stradario</label>
              <input id="stradario" type="text" onkeyup="updateHints('stradario', this, '#hintsStradari', '#stradarioID');" onclick="this.select();">
              <input id="stradarioID" name="stradario" type="hidden">
              <div id="hintsStradari" class="hintBox"></div>
            </div>
            
            <div class="field">
              <label>Civico</label>
              <input type="number" name="civico">
            </div>

             <div class="field">
              <label>N. protocollo</label>
              <input type="number" name="n_protocollo">
            </div>
            
            <div class="field">
              <label>N. verbale</label>
              <input type="number" name="n_verbale">
            </div>
            
            <div class="field">
              <label>Verbale</label>
              <textarea rows="3" name="verbale"></textarea>
            </div>
            
            <div class="field">
              <label>Prescrizioni</label>
              <textarea rows="3" name="prescrizioni"></textarea>
            </div>
            
            <div class="field">
              <label>Parere</label>
              <textarea rows="3" name="parere"></textarea>
            </div>
            
            <div class="field">
              <label>Note parere</label>
              <textarea rows="3" name="parere_note"></textarea>
            </div>
            
            <div class="field">
              <label>Approvata</label>
                <select name="approvata">
                	<option value=""></option>
                	<option value="S">Si</option>
                	<option value="N">No</option>
                </select>
            </div>
            
            <div class="field">
              <label>Onerosa</label>
                <select name="onerosa">
                	<option value=""></option>
                	<option value="S">Si</option>
                	<option value="N">No</option>
                </select>
            </div>
            
            <div class="field">
              <label>Beni ambientali</label>
                <select name="beni_ambientali">
                	<option value="S">Si</option>
                	<option value="N">No</option>
                </select>
            </div>

			<div class="field">
              <label>Note pagamenti</label>
              <textarea rows="3" name="note_pagamenti"></textarea>
            </div>

            <div class="field">
              <label>Note pratica</label>
              <textarea rows="3" name="note_pratica"></textarea>
            </div>

          </div>
          
          <div class="section">Date</div>
          	<div class="inner-wrap">
          		<?php 
          		$fields = $c->db->ql('DESCRIBE tec_pratiche');
          		foreach ($fields as $field)
          		    if(substr($field['Field'], 0, 5) == 'Data_')
          		        echo '<div class="field">
                                      <label>'.str_replace('_', ' ', $field['Field']).'</label>
                                      <input type="date" name="'.$field['Field'].'">
                                    </div>';
          		?>
          	</div>

          <button type="submit">Inserisci pratica</button>
        </div>
      </form>
  </div>

  <script src="../js/inserimento_pratica.js"></script>
</body>
</html>
