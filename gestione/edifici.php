<?php
    include_once '../controls.php';
    $c = new Controls();

    if(!$c->logged()){
        header('Location: /index.php?err=Utente non loggato');
        exit();
    }

    $c->echoCode($_REQUEST);
    
    $errors = [];
    $infos = [];
    
    //INSERT EDIFICIO
    if($c->check(['stradarioNewEd'], $_REQUEST)&&isset($_REQUEST['noteNewEd'])&&$_SERVER['REQUEST_METHOD'] === 'POST'){
        //NEW ID EXTRACTION
        $edID = $c->db->ql('SELECT MAX(ID)+1 id FROM edifici')[0]['id'];
        if($edID === NULL) $edID = 1;
        
        $res = $c->db->dml(
            'INSERT INTO edifici (ID, Stradario, Note) VALUES(?,?,?)',
            [$edID, $_REQUEST['stradarioNewEd'],empty($_REQUEST['noteNewEd'])?NULL:$_REQUEST['noteNewEd']]
        );
        $edInserted = $res->errorCode() == '0';

        //INSERT MAPPALI
        if($edInserted){
            $infos[] = "Edificio $edID creato";
            foreach ($_REQUEST as $keyMappale => $mappale)
                if(substr($keyMappale, 0, strlen('mappalenew')) === 'mappalenew'){
                    $n = substr($keyMappale, strlen('mappalenew'), strlen($keyMappale));
                    $keyFoglio = "foglionew$n";
                    
                    if($c->check([$keyMappale, $keyFoglio], $_REQUEST)){
                        $res = $c->db->dml(
                              'INSERT INTO fogli_mappali_edifici (Edificio, Foglio, Mappale, EX) VALUES(?,?,?,?)',
                            [$edID, $_REQUEST[$keyFoglio] ,$mappale, isset($_REQUEST["exnew$n"])?'EX':NULL]);
                        if($res->errorCode() != '0') $errors[] = $res->errorInfo()[2];
                        print_r( $errors);
                    }
                }
        }
    }

    
    //UPDATDE EDIFICIO TODO cambiare foglio mappale
    if($c->check(['stradarioEditingEd', 'noteEditingEd', 'edificioEditingEd'], $_REQUEST)&&$_SERVER['REQUEST_METHOD'] === 'POST'){
      //UPDATE TABLE EDIFICI
      $res =$c->db->dml(
          'UPDATE edifici SET Stradario = ?, Note = ? WHERE ID = ?',
          [$_REQUEST['stradarioEditingEd'], empty($_REQUEST['noteEditingEd'])?NULL:$_REQUEST['noteEditingEd'], $_REQUEST['edificioEditingEd']]);

      if($res->errorCode() != 0)
          $errors[] = $res->errorInfo()[2];
      else {
        $infos[] = "Edificio $_REQUEST[edificioEditingEd] modificato con successo";
        //UPDATE TABLE MAPPALI
         $mappali = [];
         foreach ($_REQUEST as $key => $value)
             //IF PARAM IS FOR MAPPALE
            if(substr($key, 0, strlen('mappEditingEd')) == 'mappEditingEd'){
                $mappali[] = $value;
                //CHECK IF EXISTS
                $res = $c->db->ql(
                    'SELECT EX FROM fogli_mappali_edifici
                    WHERE Edificio = ? AND Foglio = ? AND Mappale = ?',
                    [$_REQUEST['edificioEditingEd'], $_REQUEST['foglioEditingEd'], $value]);

                $mappaleNum = substr($key, strlen('mappEditingEd'), strlen($key));
                $exFromUser = isset($_REQUEST["isExMappEditingEd$mappaleNum"]);

                if(count($res) > 0){
                  //IF EXISTS AND EX HAS CHANGED
                  $exFromDB = $res[0]['EX'] == 'EX';
                  if($exFromDB != $exFromUser){
                    //CHANGE DB VALUE OF EX
                    $c->db->dml(
                        'UPDATE fogli_mappali_edifici SET EX = ? WHERE Edificio = ? AND Foglio = ? AND Mappale = ?',
                        [($exFromUser?'EX':NULL), $_REQUEST['edificioEditingEd'], $_REQUEST['foglioEditingEd'], $value]);
                        $infos[] = "Valore di EX del mappale $value modificato a: ".($exFromUser?'EX':'NULL');
                  }
                }else{
                  //IF DOESN'T EXIST
                  $res = $c->db->dml(
                      'INSERT INTO fogli_mappali_edifici (Edificio, Foglio, Mappale, EX) VALUES (?,?,?,?)',
                      [$_REQUEST['edificioEditingEd'], $_REQUEST['foglioEditingEd'], $value, ($exFromUser?'EX':NULL)]);
                  if($res->errorCode() == '0')
                      $infos[] = "Mappale $value".($exFromUser?'(EX)':'')." aggiunto correttamente";
                  else
                $errors[] = $res->errorInfo()[2];
                }
            }

        //UPDATE TABLE SUBALTERNI
         $subalterni = [];
          foreach ($_REQUEST as $key => $value)
            if(substr($key, 0, strlen('subEditingEd')) == 'subEditingEd'){
                //INSERT SUBALTERNI
                $mappale = $_REQUEST['mappSubEditingEd'.substr($key, strlen('subEditingEd'), strlen($key))]??'';
                $subalterni[] = ['Mappale' => $mappale, 'Subalterno' => $value];
                
                if(!empty($value)&&!empty($mappale)){
                    $res = $c->db->dml(
                        'INSERT INTO subalterni_edifici (Edificio, Foglio, Mappale, Subalterno) VALUES (?,?,?,?)',
                        //TODO add foglio
                        [$_REQUEST['edificioEditingEd'], $mappale,$value]);
                    if($res->errorCode() == '0')
                        $infos[] = "Subalterno $value del mappale $mappale aggiunto correttamente";
                }
            }

       //DELETE OMITTED MAPPALI
        $mappaliFromDB = getMappaliEdificio($_REQUEST['edificioEditingEd'], $c->db);
       foreach ($mappaliFromDB as $mappaleFromDB)
           if(!in_array($mappaleFromDB['Mappale'], $mappali)){
               $res = $c->db->dml(
                                   'DELETE FROM fogli_mappali_edifici WHERE Edificio = ? AND Foglio = ? AND Mappale = ?',
                                   [$_REQUEST['edificioEditingEd'], $_REQUEST['foglioEditingEd'], $mappaleFromDB['Mappale']]);
               if($res->errorCode() == '0')
                   $infos[] = "Mappale $mappaleFromDB[Mappale] eliminato con successo";
               else
            $errors[] = $res->errorInfo()[2];
            }

       //DELETE OMITTED SUBALIERNI FINIRE
       $subalterniFromDB = getSubalterniEdificio($_REQUEST['edificioEditingEd'], $c->db);
       foreach ($subalterniFromDB as $subalternoFromDB)
           if(!in_array($subalternoFromDB, $subalterni)){
               $res = $c->db->dml(
                                   'DELETE FROM subalterni_edifici WHERE Edificio = ? AND Mappale = ?  AND Subalterno = ?',
                   [$_REQUEST['edificioEditingEd'], $subalternoFromDB['Mappale'], $subalternoFromDB['Subalterno']]);
               if($res->errorCode() == '0')
                   $infos[] = "Subalterno $subalternoFromDB[Subalterno] del mappale $subalternoFromDB[Mappale] eliminato con successo";
               else
            $errors[] = $res->errorInfo()[2];
            }
         }
    }

    //MISC FUNCTIONS
    function getFogliMappaliEdificio($edID, $db){
      return $db->ql('SELECT Foglio, Mappale, EX
                                FROM fogli_mappali_edifici
                                WHERE Edificio = ?',
                                [$edID]);
    }

    function getSubalterniEdificio($edID, $db){
      return $db->ql('SELECT Foglio, Mappale, Subalterno
                                FROM subalterni_edifici
                                WHERE Edificio = ?',
                                [$edID]);
    }

?>
<html>
<head>
	<title>Gestione edifici</title>
	<link rel="stylesheet" href="../css/gestione.css">
	<link rel="stylesheet" href="../css/alerts.css">
	<script type="text/javascript" src="/lib/jquery-3.3.1.min.js"></script>
	<style type="text/css">
        .hintBox *{
            display: block;
            margin-top: 10px;
        }
        #search-editing-ed h2,input{
            display: inline-flex;
        }
        #editing-edificio *{
            display: block;
        }
        #mappali-editing-ed div *, #subalterni-editing-ed div *{
            display: inline-flex;
            padding:0px;
            margin:0px;
            margin-right: 10px;
        }
        #fogli-mappali-new-ed  > div > *,#fogli-mappali-editing-ed > div > *{
            display: inline-flex;
            margin-left:10px;
            margin-bottom:10px;
        }
        #search-results{
            display: grid;
            grid-template-columns: auto auto auto auto;
        }
        .search-result{
            border: 1px solid black;
            padding: 5px;
            text-align: center;
        }
        .search-result > p{
            display: block;
        }
        .search-result:hover{
            text-decoration: underline;
        }
    </style>
</head>
<body>

	<!-- Sidebar -->
    <div class="w3-sidebar w3-light-grey w3-bar-block" style="width:17%">
      <p onclick="changeContent('vis-mod')" class="w3-bar-item w3-button">Visualizza/<br>modifica</p>
      <p onclick="changeContent('new-ed')" class="w3-bar-item w3-button">Nuovo<br>edificio</p>
    </div>

    <!-- Page Content -->
    <div id="pageContent" style="margin-left:17%">

        <div id="vis-mod" class="content active">
        	<div class="w3-container w3-teal"><h1>Visualizza/modifica edifci</h1></div>
            <div id="container-editing-ed" class="w3-container">
            	<form id="form-search-ed" method="get">
            		<div id="search-editing-ed">
                		<h2>Foglio</h2>
                		<input type="number" name="searchFoglio" placeholder="Foglio..." value="<?= $_REQUEST['searchFoglio']??'' ?>">
                		<h2>Mappale/i</h2>
                		<input type="number" name="searchMappale" placeholder="Mappale..." value="<?= $_REQUEST['searchMappale']??'' ?>">
                		<input type="submit" value="Cerca">


                		<div id="search-results">
                			<?php
                			if(isset($_REQUEST['searchFoglio'])&&isset($_REQUEST['searchMappale'])&&!isset($_REQUEST['editingEdificio'])){
                                $where = [];
                                $params = [];
                                if(!empty($_REQUEST['searchFoglio'])){
                                  $where[] = 'Foglio = ?';
                                  $params[] = $_REQUEST['searchFoglio'];
                                }
                                if(!empty($_REQUEST['searchMappale'])){
                                  $where[] = 'Mappale = ?';
                                  $params[] = $_REQUEST['searchMappale'];
                                }
                                $where = implode(' AND ', $where);
                               $res = $c->db->ql(' SELECT ID, Mappali, Stradario, Note
                                                            FROM edifici_view
                                                            WHERE ID IN (  SELECT Edificio
                                                                                      FROM fogli_mappali_edifici'.
                                                                                      (empty($where)?'':" WHERE $where").')'.
                                                           'LIMIT 50',
                                                           $params);
                               foreach ($res as $ed) {
                                   echo "<div class=\"search-result\" onclick=\"editEdificio($ed[ID]);\">";
                                   echo "<p><strong>ID edificio </strong>$ed[ID]</p>";
                                   echo '<p><strong>Fogli/Mappali </strong><br>'.str_replace(', ', '<br>', $ed['Mappali']).'</p>';
                                   echo "<p><strong>Stradario </strong>$ed[Stradario]<br>";
                                   echo empty($ed['Note'])?'':"<p style=\"white-space: pre-line;\"><strong>Note </strong><br>$ed[Note]</p>";
                                   echo "</div>";
                                 }
                               }
  			              ?>
                		</div>
                		</div>
            		</form>

            		<form id="form-edit-ed" action="" method="post">
                		<div id="editing-edificio">
                    		<?php
                    		if($c->check(['editingEdificio'], $_REQUEST)){
                    		    $edID = $_REQUEST['editingEdificio'];
                    		    $ed = $c->db->ql('SELECT e.ID id, s.Denominazione strad, s.Identificativo_nazionale stradID, e.Note note,
                                                        		(SELECT GROUP_CONCAT(DISTINCT fm.Foglio ORDER BY fm.Foglio)
                                                        		FROM fogli_mappali_edifici fm
                                                        		GROUP BY fm.Edificio
                                                        		HAVING fm.Edificio = e.ID) foglio
                                                        FROM edifici e
                                                        JOIN stradario s ON s.Identificativo_nazionale = e.Stradario
                                                        WHERE e.ID = ?',
                    		                              [$_REQUEST['editingEdificio']]);
                                if(count($ed) > 0){
                    		            $ed = $ed[0];
                                      //print_r($ed);
                                    //print_r($mappali);
                    		?>
                    			<h2>Modifica edificio N° <?= $edID ?></h2>
                    			<input type="hidden" name="edificioEditingEd" value="<?= $ed['id'] ?>">

                    			<h4>Fogli/Mappali</h4>
                        		<div id="fogli-mappali-editing-ed"></div>
                        		<button type="button" onclick="addFieldFoglioMappale('editing', '', '', false, <?= $edID ?>);">+</button>

								<h4>Subalterni</h4>
                        		<div id="subalterni-editing-ed"></div>
                        		<button type="button" onclick="addFieldSubalterno('', '', '');">+</button>

                        		<h4>Stradario</h4>
             	   				<input id="stradario-editing-ed" type="text" required="required" autocomplete="off" onkeyup="updateHints('stradario', this, '#hintsStradari-editing-ed', '#stradarioID-editing-ed');" onclick="this.select();" value="<?= $ed['strad'] ?>" placeholder="Stradario...">
             	   				<input id="stradarioID-editing-ed" name="stradarioEditingEd" type="hidden" value="<?= $ed['stradID'] ?>">
                 	   			<div id="hintsStradari-editing-ed" class="hintBox"></div>

                        		<h4>Note</h4>
                        		<textarea id="note-new-ed" name="noteEditingEd" rows="3" cols="40" placeholder="Inserire qui eventuali note..."><?= $ed['note'] ?></textarea>

                    			<br>
            					<input type="button" id="submit-modifiche-edificio" value="APPLICA MODIFICHE">
                			<?php
                    		    }
                    		}
                    		?>
                		</div>
        		</form>
            </div>
        </div>

        <div id="new-ed" class="content">
        	<div class="w3-container w3-teal"><h1>Nuovo edificio</h1></div>
            <div id="container-new-ed" class="w3-container">
            	<form id="form-new-ed" method="post">
            		<h2>Fogli/Mappali</h2>
            		<div id="fogli-mappali-new-ed"></div>
            		<button type="button" onclick="addFieldFoglioMappale('new');">+</button>

            		<h2>Stradario</h2>
 	   				<input id="stradario-new-ed" type="text" required="required" autocomplete="off" onkeyup="updateHints('stradario', this, '#hintsStradari-new-ed', '#stradarioID-new-ed');" onclick="this.select();" placeholder="Stradario...">
 	   				<input id="stradarioID-new-ed" name="stradarioNewEd" type="hidden">
     	   			<div id="hintsStradari-new-ed" class="hintBox"></div>

            		<h2>Note</h2>
            		<textarea id="note-new-ed" name="noteNewEd" rows="3" cols="40" placeholder="Inserire qui eventuali note..."></textarea>

            		<br>
            		<input type="button" id="submit-new-edificio" value="CREA NUOVO EDIFICIO">
            	</form>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="/js/misc.js"></script>
    <script type="text/javascript" src="/js/hints.js"></script>
	<script type="text/javascript" src="/js/gestione_edifici.js"></script>
    <script type="text/javascript">addFieldFoglioMappale('new');</script>
    <?php
    if(isset($_REQUEST['editingEdificio'])){
        $foglimappaliFromDB = getFogliMappaliEdificio($_REQUEST['editingEdificio'], $c->db);
        $subalterniFromDB = getSubalterniEdificio($_REQUEST['editingEdificio'], $c->db);
        echo '<script>';
        foreach ($foglimappaliFromDB as $tmp)
            echo "addFieldFoglioMappale('editing', $tmp[Foglio], $tmp[Mappale],".($tmp['EX']=='EX'?'true':'false').", $_REQUEST[editingEdificio]);";
        foreach ($subalterniFromDB as $subalterno)
            echo "addFieldSubalternoEditingEd('$subalterno[Subalterno]', '$subalterno[Mappale]');";
        echo '</script>';
    }
    
    if(count($errors) > 0)
        echo "<script>displayMessage('Errori: ".str_replace('\'', '\\\'', implode('<br>', $errors)).'\', document.getElementById(\'vis-mod\'));</script>';
    if(count($infos) > 0)
        echo "<script>displayMessage('Info: ".str_replace('\'', '\\\'', implode('<br>', $infos))."', document.getElementById('vis-mod'), 'info');</script>";
    
    ?>
</body>
</html>
