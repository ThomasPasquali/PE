<?php
    include_once '../controls.php';
    $c = new Controls();

    if(!$c->logged()){
        header('Location: /index.php?err=Utente non loggato');
        exit();
    }

    $c->echoCode($_REQUEST);
    
    //INSERT EDIFICIO
    $edInserted = FALSE;
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
        if($edInserted)
            foreach ($_REQUEST as $key => $mappale)
                if(substr($key, 0, strlen('mappNewEd')) === 'mappNewEd'&&!empty($mappale)){
                    $foglio = substr($key, strlen('mappNewEd'), strlen($key));
                    echo "Foglio $foglio";
                    $c->db->dml(
                          'INSERT INTO fogli_mappali_edifici (Edificio,Foglio,Mappale) VALUES(?,?,?)',
                        [$edID, $foglio ,$mappale]);
                }
                  
    }

    
    //UPDATDE EDIFICIO TODO cambiare foglio mappale
    $edUpdated = FALSE;
    $edUpdateErrors = [];
    $edUpdateInfos = [];

    if($c->check(['foglioEditingEd', 'stradarioEditingEd', 'noteEditingEd', 'edificioEditingEd'], $_REQUEST)&&$_SERVER['REQUEST_METHOD'] === 'POST'){
      $edUpdated = TRUE;
      //UPDATE TABLE EDIFICI
      $res =$c->db->dml(
          'UPDATE edifici SET Foglio = ?, Stradario = ?, Note = ? WHERE ID = ?',
          [$_REQUEST['foglioEditingEd'], $_REQUEST['stradarioEditingEd'], empty($_REQUEST['noteEditingEd'])?NULL:$_REQUEST['noteEditingEd'], $_REQUEST['edificioEditingEd']]);

      if($res->errorCode() != 0)
          $edUpdateErrors[] = $res->errorInfo()[2];
      else {
        $edUpdateInfos[] = "Edificio $_REQUEST[edificioEditingEd] modificato con successo";
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
                        $edUpdateInfos[] = "Valore di EX del mappale $value modificato a: ".($exFromUser?'EX':'NULL');
                  }
                }else{
                  //IF DOESN'T EXIST
                  $res = $c->db->dml(
                      'INSERT INTO fogli_mappali_edifici (Edificio, Foglio, Mappale, EX) VALUES (?,?,?,?)',
                      [$_REQUEST['edificioEditingEd'], $_REQUEST['foglioEditingEd'], $value, ($exFromUser?'EX':NULL)]);
                  if($res->errorCode() == '0')
                      $edUpdateInfos[] = "Mappale $value".($exFromUser?'(EX)':'')." aggiunto correttamente";
                  else
                $edUpdateErrors[] = $res->errorInfo()[2];
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
                        'INSERT INTO subalterni_edifici (Edificio, Mappale, Subalterno) VALUES (?,?,?)',
                        [$_REQUEST['edificioEditingEd'], $mappale,$value]);
                    if($res->errorCode() == '0')
                        $edUpdateInfos[] = "Subalterno $value del mappale $mappale aggiunto correttamente";
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
                   $edUpdateInfos[] = "Mappale $mappaleFromDB[Mappale] eliminato con successo";
               else
            $edUpdateErrors[] = $res->errorInfo()[2];
            }

       //DELETE OMITTED SUBALIERNI FINIRE
       $subalterniFromDB = getSubalterniEdificio($_REQUEST['edificioEditingEd'], $c->db);
       foreach ($subalterniFromDB as $subalternoFromDB)
           if(!in_array($subalternoFromDB, $subalterni)){
               $res = $c->db->dml(
                                   'DELETE FROM subalterni_edifici WHERE Edificio = ? AND Mappale = ?  AND Subalterno = ?',
                   [$_REQUEST['edificioEditingEd'], $subalternoFromDB['Mappale'], $subalternoFromDB['Subalterno']]);
               if($res->errorCode() == '0')
                   $edUpdateInfos[] = "Subalterno $subalternoFromDB[Subalterno] del mappale $subalternoFromDB[Mappale] eliminato con successo";
               else
            $edUpdateErrors[] = $res->errorInfo()[2];
            }
         }
    }

    //MISC FUNCTIONS
    function getMappaliEdificio($edID, $db){
      return $db->ql('SELECT Mappale, EX
                        FROM fogli_mappali_edifici
                        WHERE Edificio = ?',
                        [$edID]);
    }

    function getSubalterniEdificio($edID, $db){
      return $db->ql('SELECT Mappale, Subalterno
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
                    			<h2>Modifica edificio N° <?= $_REQUEST['editingEdificio'] ?></h2>
                    			<input type="hidden" name="edificioEditingEd" value="<?= $ed['id'] ?>">

                    			<h4>Foglio</h4>
                    			<input id="foglio-editing-ed" name="foglioEditingEd" autocomplete="off" type="number" onkeyup="checkAllMappaliEditingEd();" max="9999" placeholder="Foglio..." required="required" value="<?= $ed['foglio'] ?>">

                    			<h4>Mappale/i</h4>
                        		<div id="mappali-editing-ed"></div>
                        		<button type="button" onclick="addFieldMappaleEditingEd('', false, <?= $_REQUEST['editingEdificio'] ?>);">+</button>

								<h4>Subalterni</h4>
                        		<div id="subalterni-editing-ed"></div>
                        		<button type="button" onclick="addFieldSubalternoEditingEd('', '');">+</button>

                        		<h4>Stradario</h4>
             	   				<input id="stradario-editing-ed" type="text" required="required" autocomplete="off" onkeyup="updateHints('stradario', this, '#hintsStradari-editing-ed', '#stradarioID-editing-ed');" onclick="this.select();" value="<?= $ed['strad'] ?>" placeholder="Stradario...">
             	   				<input id="stradarioID-editing-ed" name="stradarioEditingEd" type="hidden" value="<?= $ed['stradID'] ?>">
                 	   			<div id="hintsStradari-editing-ed" class="hintBox"></div>

                        		<h4>Note</h4>
                        		<textarea id="note-new-ed" name="noteEditingEd" rows="3" cols="40" placeholder="Inserire qui eventuali note..."><?= $ed['note'] ?></textarea>

                    			<br>
            					<input type="button" onclick="submitModificheEdificio();" value="APPLICA MODIFICHE">
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
            		<input type="button" onclick="submitNewEdificio();" value="CREA NUOVO EDIFICIO">
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
        $mappaliFromDB = getMappaliEdificio($_REQUEST['editingEdificio'], $c->db);
        $subalterniFromDB = getSubalterniEdificio($_REQUEST['editingEdificio'], $c->db);
        echo '<script>';
        foreach ($mappaliFromDB as $mappale)
            echo "addFieldMappaleEditingEd($mappale[Mappale], ".($mappale['EX']=='EX'?'true':'false').", $_REQUEST[editingEdificio]);";
        foreach ($subalterniFromDB as $subalterno)
            echo "addFieldSubalternoEditingEd('$subalterno[Subalterno]', '$subalterno[Mappale]');";
        echo '</script>';
    }
    
    if($edInserted) echo "<script>displayMessage('Edificio creato', document.getElementById('vis-mod'), 'info');</script>";
    
    if($edUpdated){
        if(count($edUpdateInfos) > 0)
            echo "<script>displayMessage('".str_replace('\'', '\\\'', implode('<br>', $edUpdateInfos))."', document.getElementById('vis-mod'), 'info');</script>";
        if(count($edUpdateErrors) > 0)
            echo "<script>displayMessage('Errore durante la modifica dell\'edificio $_REQUEST[edificioEditingEd]: ".str_replace('\'', '\\\'', implode('<br>', $edUpdateErrors)).'\', document.getElementById(\'vis-mod\'));</script>';
    }
    ?>
</body>
</html>
