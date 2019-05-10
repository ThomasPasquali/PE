<?php
  include_once '../controls.php';
  $c = new Controls();

  if(!$c->logged()){
      header('Location: index.php?err=Utente non loggato');
      exit();
  }

  function inserisciPraticaPE() {
    //TODO "rifare"
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

  //Misc functions
  function getEnumValues($table, $field, $db){
    $query = $db->query("SHOW COLUMNS FROM $table WHERE Field = '$field'");
    $type = $query->fetch(PDO::FETCH_ASSOC)['Type'];
    $matches = [];
    preg_match("/^enum\(\'(.*)\'\)$/", $type, $matches);
    $enum = explode("','", $matches[1]);
    return $enum;
  }

?>
<html>
<head>
  <script src="../lib/jquery-3.3.1.min.js"></script>
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
    <h1>Inserimento pratiche</h1>
      <input type="hidden" name="tipo" value="pe">
      <div id="dati-edificio" class="inner-wrap">

          <label>Trova edificio</label>
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

      <div id="dati-pratica">
        <div class="inner-wrap">
      		<h2 id="info-edificio"></h2>
      		<input type="hidden" name="edificio" id="edificio" required="required">
        </div>

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

        <div class="inner-wrap">
          <div class="field">
            <label>Tipo</label>
            <select name="tipo_pratica">
              <?php
              $types = getEnumValues('pe_pratiche', 'Tipo', $c->db);
              foreach ($types as $type) echo "<option value=\"$type\">$type</option>";
              ?>
            </select>
          </div>

          <div class="field">
            <label>Anno</label>
            <input type="number" name="anno" required="required" pattern="\d{4}" value="111">
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


        <label>Data<input type="date" name="data" value="<?= $_POST['data']??date('Y-m-d') ?>"></label>
        <label>Protocollo<input type="number" name="protocollo" value="<?= $_POST['protocollo']??'' ?>"></label>
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
  </div>

  <script src="../js/ins_pratPE.js"></script>
</body>
</html>
