<?php
  include_once '../controls.php';
  $c = new Controls();

  if(!$c->logged()){
      header('Location: index.php?err=Utente non loggato');
      exit();
  }

  print_r($_POST);

    if($c->check(['tipo', 'anno', 'numero', 'edificio'], $_POST)){
        
        if(isset($_POST['documento_elettronico'])){
            $relPath = "$_POST[tipo]\\$_POST[anno]\\$_POST[numero]$_POST[barrato]";
            $path = $c->doc_el_root_path."\\$relPath";
            if(!file_exists($path))
                mkdir($path, 0777, TRUE);
            $_POST['documento_elettronico'] = $relPath;
        }else 
        $_POST['documento_elettronico'] = '';
        
        $res = $c->db->dml(
            'INSERT INTO pe_pratiche (TIPO, Anno, Numero, Barrato, `Data`, Protocollo, Edificio, Stradario, Tecnico, Impresa, Direzione_lavori, Intervento, Data_inizio_lavori, Documento_elettronico, Note)
              VALUES (:tipo, :anno, :numero, :barr, :data, :prot, :edificio, :strad, :tecnico, :imp, :dl, :interv, :data_il, :doc_el, :note)',
            [':tipo' => $_POST['tipo'],
            ':anno' => $_POST['anno'],
            ':numero' => $_POST['numero'],
            ':barr' => $_POST['barrato'],

            ':edificio' => $_POST['edificio'],
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
            echo 'Pratica inserita correttamente';

            $ed =  $c->db->ql(
                'SELECT p.ID pid, e.ID eid, e.Foglio foglio
                FROM pe_pratiche p
                JOIN edifici e ON e.ID = p.Edificio
                WHERE p.TIPO = ? AND p.Anno = ? AND p.Numero = ? AND p.Barrato = ?',
                [$_POST['tipo'], $_POST['anno'], $_POST['numero'], $_POST['barrato']])[0];

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
        echo 'Impossibile inserire la pratica: '.$res->errorInfo()[2];
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
  <link rel="stylesheet" type="text/css" href="../css/form.css">
  <style>
    .form{
      width: 90%;
    }
    #risultati-ricerca-edificio{
        display: grid;
        grid-template-columns: auto auto auto auto;
    }
    .risultato-ricerca-edificio{
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
    }
    .risultato-ricerca-edificio > div p,strong{
        display: inline-flex;
        margin-top: 2px;
        margin-bottom: 2px;
    }
    .risultato-ricerca-edificio > div strong{
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
  <div class="form">
    <h1>Inserimento pratiche <span id="info-edificio"></span></h1>
      <input type="hidden" name="tipo" value="pe">

      <div id="dati-edificio">

        <div class="section">Selezione edificio</div>
        <div class="inner-wrap">
            <form id="ricerca-edificio">
            <input type="hidden" name="action" value="searchEdificio">
            <input name="foglio" type="number" placeholder="Foglio...">
            <input name="mappale" type="number" placeholder="Mappale...">
          </form>
          <div id="risultati-ricerca-edificio"></div>

          <label>N° Edificio</label>
          <input type="number" id="ricerca-edificio-field" required="required" disabled="disabled">
          <button type="button" onclick="freezeEdificio();">Blocca edificio</button>

        </div>
      </div>

      <form method="post">
        <div id="dati-pratica">
      		<input type="hidden" name="edificio" id="edificio" required="required">

          <div class="section">Non so che scrivere</div>
          <div class="inner-wrap">
            <div class="field">
            	 <label>Mappale/i</label>
      		     <div id="mappali"></div>
    		       <button type="button" onclick="addFieldMappale();">+</button>
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
