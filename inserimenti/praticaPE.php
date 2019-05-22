<?php
  include_once '../controls.php';
  $c = new Controls();

  if(!$c->logged()){
      header('Location: index.php?err=Utente non loggato');
      exit();
  }

  print_r($_POST);
  /**
   * Array ( [foglio-mappale1] => 1-1 [foglio-mappale-subalterno1] => 1-34-2 [tipo] => SCIA [anno] => 2019 [numero] => 1 [barrato] => A [intestatarioPersona1] => 1 [tecnico] => [impresa] => [direzione_lavori] => [data] => 2019-05-22 [protocollo] => [stradario] => [intervento] => Intervento [documento_elettronico] => on [data_inizio_lavori] => [note] => )
   */
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
                WHERE p.TIPO = ? AND p.Anno = ? AND p.Numero = ? AND p.Barrato = ?',
                [$_POST['tipo'], $_POST['anno'], $_POST['numero'], $_POST['barrato']])[0]['ID'];

            //inserimento edifici
            //TODO

            //inserimento intestatari, mappali e subalterni
            foreach ($_POST as $key => $value)
                if(substr($key, 0, strlen('intestatarioPersona')) == 'intestatarioPersona'){

                    $res = $c->db->dml('INSERT INTO pe_intestatari_persone_pratiche (Pratica, Persona)
                                                        VALUES(?, ?)', [$ed['pid'], $value]);

            }else if(substr($key, 0, strlen('intestatarioSocieta')) == 'intestatarioSocieta'){

                    $res = $c->db->dml('INSERT INTO pe_intestatari_societa_pratiche (Pratica, Societa)
                                                        VALUES(?, ?)', [$ed['pid'], $value]);

            }else if(substr($key, 0, strlen('mapp')) == 'mapp'){

                $res = $c->db->dml('INSERT INTO pe_mappali_pratiche (Pratica, Edificio, Foglio, Mappale)
                                                        VALUES(?, ?, ?, ?)', [$ed['pid'], $ed['eid'], $ed['foglio'], $value]);

            }else if(substr($key, 0, strlen('sub')) == 'sub'){

                $sub_mapp = explode('mapp', $value);
                $res = $c->db->dml('INSERT INTO pe_subalterni_pratiche (Pratica, Edificio, Mappale, Subalterno)
                                                        VALUES(?, ?, ?, ?)', [$ed['pid'], $ed['eid'], $sub_mapp[1], $sub_mapp[0]]);
            }

        }else
          $errors[] = 'Impossibile inserire la pratica: '.$res->errorInfo()[2];
    }


  //Misc functions
  function getEnumValues($table, $field, $db){
    $query = $db->query("SHOW COLUMNS FROM $table WHERE Field = '$field'");
    $type = $query->fetch(PDO::FETCH_ASSOC)['Type'];
    $matches = [];
    preg_match("/^enum\(\'(.*)\'\)$/", $type, $matches);
    $enum = explode("','", $matches[1]);
    return $enum;
  }

  function ifEmptyGet($val, $valIfEmpty = NULL){
    return empty($val)?$valIfEmpty:$val;
  }

?>
<html>
<head>
  <script src="../lib/jquery-3.3.1.min.js"></script>
  <script src="../js/hints.js"></script>
  <script src="../js/misc.js"></script>
  <link rel="stylesheet" type="text/css" href="../css/form.css">
  <link rel="stylesheet" type="text/css" href="../css/alerts.css">
  <style>
    .form{
      width: 90%;
    }
    #risultati-ricerca-edificio{
        display: grid;
        grid-template-columns: auto auto auto auto;
    }
    .risultato-ricerca-edificio,.edificio-selezionato{
        border: solid 1px black;
        margin: 5px;
        padding-left: 10px;
        overflow: scroll;
        overflow-y: auto;
        overflow-x: auto;
        white-space: pre-line;
    }
    .risultato-ricerca-edificio:hover{
        text-decoration: underline;
        color: green;
    }
    .edificio-selezionato:hover{
        text-decoration: underline;
        color: red;
    }
    .risultato-ricerca-edificio > div p,strong,.edificio-selezionato > div p,strong {
        display: inline-flex;
        margin-top: 2px;
        margin-bottom: 2px;
    }
    .risultato-ricerca-edificio > div strong,.edificio-selezionato > div strong{
        margin-right: 3px;
    }
    #dati-pratica{
        display: none;
    }
    #mappali > div,#subalterni > div{
        display: flex;
    }
  </style>
</head>
<body>
  <?php
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
            <input name="foglio" type="number" placeholder="Foglio...">
            <input name="mappale" type="number" placeholder="Mappale...">
          </form>
          <h2>Risultati ricerca</h2>
          <div id="risultati-ricerca-edificio"></div>

          <h2>Edifici selezionati</h2>
		  <div id="edifici-selezionati"></div>
          <button type="button" onclick="freezeEdifici();">Conferma edificio/i</button>

        </div>
      </div>

      <form id="form-pratica" method="post">
        <div id="dati-pratica">
          <div class="section">Non so che scrivere</div>
          <div class="inner-wrap">
            <div class="field">
            	 <label>Mappale/i</label>
      		     <div id="mappali"></div>
    		       <button type="button" onclick="addFieldFoglioMappale();">+</button>
            </div>
            <div class="field">
              	<label>Subalterno/i</label>
      		      <div id="subalterni"></div>
      		      <button type="button" onclick="addFieldSubalterno();">+</button>
            </div>
          </div>

          <div class="section">Identificativo pratica</div>
          <div class="inner-wrap">
            <div class="field">
              <label>Tipo</label>
              <select name="tipo">
                <?php
                $types = getEnumValues('pe_pratiche', 'Tipo', $c->db);
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
              <button type="button" onclick="addFieldIntestatarioPersona();">+</button>
            </div>

            <div class="field">
              	<label>Intestatari societ&aacute</label>
              	<div id="fieldsIntSoc"></div>
				<button type="button" onclick="addFieldIntestatarioSocieta();">+</button>
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
              <label>Genera cartella documenti elettronici</label>
              <input type="checkbox" name="documento_elettronico" checked="checked">
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

  <script src="../js/ins_pratPE.js"></script>
</body>
</html>
