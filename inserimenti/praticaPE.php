<?php
  include_once '../controls.php';
  $c = new Controls();

  if(!$c->logged()){
      header('Location: ../index.php?err=Utente non loggato');
      exit();
  }

  print_r($_POST);

    $infos = [];
    $errors = [];
    if($c->check(['tipo', 'anno', 'numero'], $_POST)){
        if(isset($_POST['documento_elettronico'])){
            $relPath = "$_POST[tipo]\\$_POST[anno]\\$_POST[numero]$_POST[barrato]";
            $path = $c->doc_el_root_path."\\$relPath";
            if(!file_exists($path))
                mkdir($path, 0777, TRUE);
            $_POST['documento_elettronico'] = $relPath;
            if(file_exists($path)) $infos[] = 'Cartella documenti elettronici creata';
            else                    $errors[] = 'Errore nella creazione della cartella documenti elettronici';
        }else
        $_POST['documento_elettronico'] = '';

        $res = $c->db->dml(
            'INSERT INTO pe_pratiche (TIPO, Anno, Numero, Barrato, `Data`, Protocollo, Stradario, Tecnico, Impresa, Direzione_lavori, Intervento, Data_inizio_lavori, Documento_elettronico, Note)
              VALUES (:tipo, :anno, :numero, :barr, :data, :prot, :strad, :tecnico, :imp, :dl, :interv, :data_il, :doc_el, :note)',
            [':tipo' => $_POST['tipo'],
            ':anno' => $_POST['anno'],
            ':numero' => $_POST['numero'],
            ':barr' => $_POST['barrato'],

            ':strad' => ifEmptyGet($_POST['stradario']),

            ':tecnico' => ifEmptyGet($_POST['tecnico']),
            ':imp' => ifEmptyGet($_POST['impresa']),
            ':dl' => ifEmptyGet($_POST['direzione_lavori']),

            ':data' => ifEmptyGet($_POST['data']),
            ':prot' => ifEmptyGet($_POST['protocollo']),
            ':interv' => ifEmptyGet($_POST['intervento']),
            ':data_il' => ifEmptyGet($_POST['data_inizio_lavori']),
            ':doc_el' => ifEmptyGet($_POST['documento_elettronico']),
            ':note' => ifEmptyGet($_POST['note'])]);

        if($res->errorCode() == 0){
            $infos[] = 'Pratica inserita correttamente';

            $idPratica =  $c->db->ql(
                'SELECT ID
                FROM pe_pratiche
                WHERE TIPO = ? AND Anno = ? AND Numero = ? AND Barrato = ?',
                [$_POST['tipo'], $_POST['anno'], $_POST['numero'], $_POST['barrato']])[0]['ID'];

            //inserimento edifici
            foreach ($_POST as $key => $value)
                if(substr($key, 0, strlen('edificio')) == 'edificio'&&$value){
                    $res = $c->db->dml('INSERT INTO pe_edifici_pratiche (Pratica, Edificio)
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
                        $res = $c->db->dml('INSERT INTO pe_fogli_mappali_pratiche (Pratica, Edificio, Foglio, Mappale)
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
                        $res = $c->db->dml('INSERT INTO pe_subalterni_pratiche (Pratica, Edificio, Foglio, Mappale, Subalterno)
                                                    VALUES(?, ?, ?, ?, ?)',
                            [$idPratica, $edificio, $foglio, $mappale, $subalterno]);
                        if($res->errorCode() == 0) $infos[] = "Subalterno $subalterno del F.$foglio m.$mappale associato alla pratica";
                        else                         $errors[] = "Impossibile associare subalterno $subalterno del F.$foglio m.$mappale alla pratica: ".$res->errorInfo()[2];
                    }else
                        $errors[] = "Impossibile trovare F.$foglio m.$mappale: ".$res->errorInfo()[2];
                }

            //inserimento intestatari
            foreach ($_POST as $key => $value)
                if(substr($key, 0, strlen('intestatarioPersona')) == 'intestatarioPersona'&&$value){

                    $res = $c->db->dml('INSERT INTO pe_intestatari_persone_pratiche (Pratica, Persona)
                                                        VALUES(?, ?)', [$idPratica, $value]);
                    if($res->errorCode() == 0) $infos[] = "Pratica $_POST[tipo]$_POST[anno]/$_POST[numero]$_POST[barrato] associata ad un intestatario persona ($value)";
                    else                         $errors[] = "Impossibile associare la pratica $_POST[tipo]$_POST[anno]/$_POST[numero]$_POST[barrato] all'intestatario persona $value: ".$res->errorInfo()[2];
            }else if(substr($key, 0, strlen('intestatarioSocieta')) == 'intestatarioSocieta'&&$value){

                    $res = $c->db->dml('INSERT INTO pe_intestatari_societa_pratiche (Pratica, Societa)
                                                        VALUES(?, ?)', [$idPratica, $value]);
                    if($res->errorCode() == 0) $infos[] = "Pratica $_POST[tipo]$_POST[anno]/$_POST[numero]$_POST[barrato] associata ad un intestatario societ&agrave; ($value)";
                    else                         $errors[] = "Impossibile associare la pratica $_POST[tipo]$_POST[anno]/$_POST[numero]$_POST[barrato] all'intestatario societ&agrave; $value: ".$res->errorInfo()[2];
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
    <title>Inerimento pratica PE</title>
    <script src="../lib/jquery-3.3.1.min.js"></script>
    <script src="../js/hints.js"></script>
    <script src="../js/misc.js"></script>
    <link rel="stylesheet" type="text/css" href="../lib/fontawesome/css/all.css">
    <link rel="stylesheet" type="text/css" href="../css/form.css">
    <link rel="stylesheet" type="text/css" href="../css/alerts.css">
    <link rel="stylesheet" type="text/css" href="../css/utils_bar.css">
	<link rel="stylesheet" type="text/css" href="../css/inserimento_praticaPE.css">
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
    <h1>Inserimento pratiche <span id="info-edificio"></span></h1>
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
              <select name="tipo">
                <?php
                $types = $c->getEnumValues('pe_pratiche', 'Tipo', $c->db);
                foreach ($types as $type) echo "<option value=\"$type\">$type</option>";
                ?>
              </select>
            </div>

            <div class="field">
              <label>Anno</label>
              <input type="number" name="anno" required="required" pattern="\d{4}">
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
              <label>Tecnico</label>
              <input id="tecnico" type="text" onkeyup="updateHints('tecnico', this, '#hintsTecnici', '#tecnicoID');" onclick="this.select();">
              <input id="tecnicoID" name="tecnico" type="hidden">
              <div id="hintsTecnici" class="hintBox"></div>
            </div>

            <div class="field">
              <label>Impresa</label>
              <input id="impresa" type="text" onkeyup="updateHints('impresa', this, '#hintsImprese', '#impresaID');" onclick="this.select();">
              <input id="impresaID" name="impresa" type="hidden">
              <div id="hintsImprese" class="hintBox"></div>
            </div>

            <div class="field">
              <label>Direzione lavori</label>
              <input id="direzione_lavori" type="text" onkeyup="updateHints('tecnico', this, '#hintsDirezione_lavori', '#direzione_lavoriID');" onclick="this.select();">
              <input id="direzione_lavoriID" name="direzione_lavori" type="hidden">
              <div id="hintsDirezione_lavori" class="hintBox"></div>
            </div>

          </div>

          <div class="section">Altre informazioni</div>
          <div class="inner-wrap">
            <div class="field">
              <label>Data presentazione</label>
              <input type="date" name="data" value="<?= $_POST['data']??date('Y-m-d') ?>">
            </div>

            <div class="field">
              <label>Protocollo</label>
              <input type="number" name="protocollo">
            </div>

            <div class="field">
              <label>Stradario</label>
              <input id="stradario" type="text" onkeyup="updateHints('stradario', this, '#hintsStradari', '#stradarioID');" onclick="this.select();">
              <input id="stradarioID" name="stradario" type="hidden">
              <div id="hintsStradari" class="hintBox"></div>
            </div>

            <div class="field">
              <label>Intervento</label>
              <textarea rows="3" name="intervento"></textarea>
            </div>

            <div class="field">
              <label style="display: inline-flex;">Genera cartella documenti elettronici</label>
              <input type="checkbox" name="documento_elettronico" checked="checked" style="display: inline-flex;">
            </div>

            <div class="field">
              <label>Data inizio lavori</label>
              <input type="date" name="data_inizio_lavori">
            </div>

            <div class="field">
              <label>Note</label>
              <textarea rows="3" name="note"></textarea>
            </div>

          </div>

          <button type="submit">Inserisci pratica</button>
        </div>
      </form>
  </div>

  <script src="../js/inserimento_praticaPE.js"></script>
</body>
</html>
