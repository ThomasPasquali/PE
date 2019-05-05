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
    if($c->check(['foglioNewEd','stradarioNewEd'], $_REQUEST)&&isset($_REQUEST['noteNewEd'])&&$_SERVER['REQUEST_METHOD'] === 'POST'){
        //NEW ID EXTRACTION
        $edID = $c->db->ql('SELECT MAX(ID)+1 id FROM edifici')[0]['id'];
        $res = $c->db->dml(
            'INSERT INTO edifici (ID, Foglio, Stradario, Note) VALUES(?,?,?,?)',
            [$edID,$_REQUEST['foglioNewEd'],$_REQUEST['stradarioNewEd'],empty($_REQUEST['noteNewEd'])?NULL:$_REQUEST['noteNewEd']]
        );
        $edInserted = $res->errorCode() == '0';

        //INSERT MAPPALI
        if($edInserted)
            foreach ($_REQUEST as $key => $value)
                if(substr($key, 0, strlen('mappNewEd')) === 'mappNewEd'&&!empty($value))
                    $c->db->dml(
                        'INSERT INTO fogli_mappali_edifici (Edificio,Foglio,Mappale) VALUES(?,?,?)',
                        [$edID, $_REQUEST['foglioNewEd'],$value]
                    );
    }

    //UPDATDE EDIFICIO
    $edUpdated = FALSE;
    $edUpdateErrors = [];
    $edUpdateInfos = [];

    if($c->check(['foglioEditingEd', 'stradarioEditingEd', 'noteEditingEd', 'edificioEditingEd'], $_REQUEST)&&$_SERVER['REQUEST_METHOD'] === 'POST'){
      $edUpdated = TRUE;
      //UPDATE TABLE EDIFICI
      $res =$c->db->dml(
          'UPDATE edifici SET Foglio = ?, Stradario = ?, Note = ? WHERE ID = ?',
          [$_REQUEST['foglioEditingEd'], $_REQUEST['stradarioEditingEd'], empty($_REQUEST['noteEditingEd'])?NULL:$_REQUEST['noteEditingEd'], $_REQUEST['edificioEditingEd']]);
        
      $c->echoCode($res->errorInfo()[2]);
      
      if($res->errorCode() != 0)
          $edUpdateErrors[] = $res->errorInfo()[2];
      else {
        $edUpdateInfos[] = "Edificio $_REQUEST[edificioEditingEd] modificato con successo";
        //UPDATE TABLE MAPPALI
         $mappali = [];
         foreach ($_REQUEST as $key => $value) {
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
                //IF PARAM IS FOR MAPPALE
            }else if(substr($key, 0, strlen('subEditingEd')) == 'subEditingEd'){
                //INSERT SUBALTERNI
                if(!empty($value)){
                    $res = $c->db->dml(
                        'INSERT INTO subalterni_edifici (Edificio, Subalterno) VALUES (?,?)',
                        [$_REQUEST['edificioEditingEd'], $value]);
                    if($res->errorCode() == '0')
                        $edUpdateInfos[] = "Subalterno $value aggiunto correttamente";
                    else
                $edUpdateErrors[] = $res->errorInfo()[2];
                }
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
       
       // TODO DELETE OMITTED SUBALIERNI
       
         }
    }

    //MISC FUNCTIONS
    function getMappaliEdificio($edID, $db){
      return $db->ql('SELECT Mappale, EX
                        FROM fogli_mappali_edifici
                        WHERE Edificio = ?',
                        [$edID]);
    }
?>
<html>
<head>
	<title>Gestione edifici</title>
	<link rel="stylesheet" href="/css/gestione.css">
	<link rel="stylesheet" href="/css/alerts.css">
	<script type="text/javascript" src="/lib/jquery-3.3.1.min.js"></script>
	<style type="text/css">
        .hintBox *{
            display: block;
            margin-top: 10px;
        }
        #form-editing-ed{

        }
        #search-editing-ed *{
            display: inline-flex;
        }
        #search-editing-ed div{
            display: block;
        }
        #search-results *{
            display: block;
        }
        .search-result{
            border: 1px solid black;
            padding: 10px;
            text-align: center;
        }
        .search-result:hover{
            text-decoration: underline;
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
        #mappali-editing-ed div span{
            margin-left:10px;
            font-size: 1.7em;
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
                              if(isset($_REQUEST['searchFoglio'])&&isset($_REQUEST['searchMappale'])){
                                $where = [];
                                $params = [];
                                if(!empty($_REQUEST['searchFoglio'])){
                                  $where[] = 'e.Foglio LIKE ?';
                                  $params[] = "%$_REQUEST[searchFoglio]%";
                                }
                                if(!empty($_REQUEST['searchMappale'])){
                                  $where[] = 'fm.Mappale LIKE ?';
                                  $params[] = "%$_REQUEST[searchMappale]%";
                                }
                                $where = implode(' AND ', $where);
                               $res = $c->db->ql('SELECT DISTINCT e.ID id, e.Foglio foglio, s.Denominazione strad, e.Note note
                                                          FROM edifici e
                                                          LEFT JOIN fogli_mappali_edifici fm ON e.ID = fm.Edificio
                                                          JOIN stradario s ON s.Identificativo_nazionale = e.Stradario'.
                                                          (empty($where)?'':" WHERE $where").
                                                          ' LIMIT 50',
                                                           $params);
                               foreach ($res as $ed) {
                                   echo "<p class=\"search-result\" onclick=\"editEdificio($ed[id]);\">";
                                   echo "<strong>ID edificio </strong>$ed[id]";
                                   echo "<strong>Foglio </strong>$ed[foglio]";
                                   $mappali = getMappaliEdificio($ed['id'], $c->db);
                                   $strMappali = '';
                                   foreach ($mappali as $mappale)
                                      $strMappali = $strMappali.", $mappale[Mappale]".($mappale['EX']==='EX'?'(EX)':'');
                                   echo "<strong>Mappale/i </strong>".substr($strMappali, 2);
                                   echo "<strong>Stradario </strong>$ed[strad]";
                                   echo empty($ed['note'])?'':"<strong>Note </strong>$ed[note]";
                                   echo "</p>";
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
                    		    $ed = $c->db->ql('SELECT e.ID id, e.Foglio foglio, s.Denominazione strad, s.Identificativo_nazionale stradID, e.Note note
                                                            FROM edifici e
                                                            JOIN stradario s ON s.Identificativo_nazionale = e.Stradario
                                                            WHERE e.ID = ?',
                    		                              [$_REQUEST['editingEdificio']]);
                                if(count($ed) > 0){
                    		        $ed = $ed[0];
                                    $mappali = getMappaliEdificio($_REQUEST['editingEdificio'], $c->db);
                                      //print_r($ed);
                                    //print_r($mappali);
                    		?>
                    			<h2>Modifica edificio N° <?= $_REQUEST['editingEdificio'] ?></h2>
                    			<input type="hidden" name="edificioEditingEd" value="<?= $ed['id'] ?>">
                    			<br>

                    			<h4>Foglio</h4>
                    			<input id="foglio-editing-ed" name="foglioEditingEd" autocomplete="off" type="number" onkeyup="checkAllMappaliEditingEd();" max="9999" placeholder="Foglio..." required="required" value="<?= $ed['foglio'] ?>">

                    			<h4>Mappale/i</h4>
                        		<div id="mappali-editing-ed"></div>
                        		<button type="button" onclick="addFiledMappaleEditingEd('', false, <?= $_REQUEST['editingEdificio'] ?>);">+</button>

								<h4>Subalterni</h4>
                        		<div id="subalterni-editing-ed"></div>
                        		<button type="button" onclick="addFiledSubalternoEditingEd('', <?= $_REQUEST['editingEdificio'] ?>);">+</button>

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
            		<h2>Foglio</h2>
            		<input id="foglio-new-ed" autocomplete="off" required="required" onkeyup="checkAllMappaliNewEd();" type="number" name="foglioNewEd" max="9999" placeholder="Foglio...">

            		<h2>Mappale/i</h2>
            		<div id="mappali-new-ed"></div>
            		<button type="button" onclick="addFiledMappaleNewEd();">+</button>

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
	<script type="text/javascript" src="/js/gestione_edifici.js"></script>
    <script type="text/javascript">addFiledMappaleNewEd();</script>
    <?php
    if(isset($mappali)){
        echo '<script>';
        foreach ($mappali as $mappale)
            echo "addFiledMappaleEditingEd($mappale[Mappale], ".($mappale['EX']=='EX'?'true':'false').", $_REQUEST[editingEdificio]);";
        echo '</script>';
    }
    if($edInserted) echo "<script>displayMessage('Edificio creato', document.body, 'info');</script>";
    if($edUpdated){
        if(count($edUpdateInfos) > 0)
            echo "<script>displayMessage('".str_replace('\'', '\\\'', implode('<br>', $edUpdateInfos))."', document.body, 'info');</script>";
        if(count($edUpdateErrors) > 0)
            echo "<script>displayMessage('Errore durante la modifica dell\'edificio $_REQUEST[edificioEditingEd]: ".str_replace('\'', '\\\'', implode('<br>', $edUpdateErrors)).'\', document.body);</script>';
    }
    ?>
</body>
</html>
