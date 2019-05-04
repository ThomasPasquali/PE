<?php
    include_once '../controls.php';
    $c = new Controls();

    if(!$c->logged()){
        header('Location: /index.php?err=Utente non loggato');
        exit();
    }

    print_r($_POST);
    
    //INSERT EDIFICIO
    $edInserted = FALSE;
    if($c->check(['foglioNewEd','stradarioNewEd'], $_POST)&&isset($_POST['noteNewEd'])){
        //NEW ID EXTRACTION
        $edID = $c->db->ql('SELECT MAX(ID)+1 id FROM edifici')[0]['id'];
        $res = $c->db->dml(
            'INSERT INTO edifici (ID, Foglio, Stradario, Note) VALUES(?,?,?,?)',
            [$edID,$_POST['foglioNewEd'],$_POST['stradarioNewEd'],empty($_POST['noteNewEd'])?NULL:$_POST['noteNewEd']]
        );
        $edInserted = $res->errorCode() == '0';
        
        //INSERT MAPPALI
        if($edInserted)
            foreach ($_POST as $key => $value)
                if(substr($key, 0, strlen('mappNewEd')) === 'mappNewEd'&&!empty($value))
                    $c->db->dml(
                        'INSERT INTO fogli_mappali_edifici (Edificio,Foglio,Mappale) VALUES(?,?,?)',
                        [$edID, $_POST['foglioNewEd'],$value]
                    );
    }

    //UPDATDE EDIFICIO TODO subalterni
    $edUpdated = FALSE;
    $edUpdateError = FALSE;
    if($c->check(['foglioEditingEd', 'stradarioEditingEd', 'noteEditingEd', 'edificioEditingEd'], $_POST)){
        //UPDATE TABLE EDIFICI
        $res =$c->db->dml(
            'UPDATE edifici SET Foglio = ?, Stradario = ?, Note = ? WHERE ID = ?',
            [$_POST['foglioEditingEd'], $_POST['stradarioEditingEd'], $_POST['noteEditingEd'], $_POST['edificioEditingEd']]);
    
        if($res->errorCode() != 0)
            $edUpdateError = $res->errorInfo()[2];
        else
        //UPDATE TABLE MAPPALI
            /*Array (
             * [mappEditingEd1] => 1
             * [isExMappEditingEd1] => EX
             * [mappEditingEd2] => 3
             * [isExMappEditingEd2] => EX
             * [mappEditingEd3] => 123
             * )*/
             foreach ($_POST as $key => $value) {
                if(substr($key, 0, strlen('mappEditingEd')) == 'mappEditingEd'){
                    
                    //CHECK IF EXISTS
                    $res = $c->db->ql(
                        'SELECT EX FROM fogli_mappali_edifici 
                        WHERE Edificio = ? AND Foglio = ? AND Mappale = ?',
                        [$_POST['edificioEditingEd'], $_POST['foglioEditingEd'], $value]);
                    
                    if(count($res) > 0){
                        //IF EXISTS AND EX HAS CHANGED
                        $mappaleNum = substr($key, strlen('mappEditingEd')+1, strlen($key));
                        echo $mappaleNum;
                        $exFromUser = isset($_POST["isExMappEditingEd$mappaleNum"]);
                        $exFromDB = $res['EX'] == 'EX';
                        
                        if($exFromDB != $exFromUser){
                            //CHANGE DB VALUE OF EX
                            $c->db->dml(
                                'UPDATE fogli_mappali_edifici SET EX = ? WHERE Edificio = ? AND Foglio = ? AND Mappale = ?',
                                [($exFromUser?'EX':NULL), $_POST['edificioEditingEd'], $_POST['foglioEditingEd'], $value]);
                        }else {
                            //TODO IF DOESN'T EXIST
                            
                        }
                    }
                }
             }
        $edUpdated = TRUE;
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
        #mappali-editing-ed div *{
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
            	<form id="form-search-ed" method="post">
            		<div id="search-editing-ed">
                		<h2>Foglio</h2>
                		<input type="number" name="searchFoglio" placeholder="Foglio..." value="<?= $_POST['searchFoglio']??'' ?>">
                		<h2>Mappale/i</h2>
                		<input type="number" name="searchMappale" placeholder="Mappale..." value="<?= $_POST['searchMappale']??'' ?>">
                		<input type="submit" value="Cerca">
        		

                		<div id="search-results">
                			<?php
                                if($c->check(['searchFoglio', 'searchMappale'], $_POST)){
                                     $res = $c->db->ql('SELECT DISTINCT e.ID id, e.Foglio foglio, s.Denominazione strad, e.Note note
                                                                 FROM fogli_mappali_edifici fm
                                                                JOIN edifici e ON e.ID = fm.Edificio
                                                                JOIN stradario s ON s.Identificativo_nazionale = e.Stradario
                                                                 WHERE fm.Foglio LIKE ? AND fm.Mappale LIKE ?',
                                                                 ["%$_POST[searchFoglio]%", "%$_POST[searchMappale]%"]);
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
                    		if($c->check(['editingEdificio'], $_POST)){
                    		    $ed = $c->db->ql('SELECT e.ID id, e.Foglio foglio, s.Denominazione strad, s.Identificativo_nazionale stradID, e.Note note
                                                            FROM fogli_mappali_edifici fm
                                                            JOIN edifici e ON e.ID = fm.Edificio
                                                            JOIN stradario s ON s.Identificativo_nazionale = e.Stradario
                                                            WHERE e.ID = ?',
                    		                              [$_POST['editingEdificio']]);
                    		    if(count($ed) > 0){
                    		        $ed = $ed[0];
                                    $mappali = getMappaliEdificio($_POST['editingEdificio'], $c->db);
                                    print_r($ed);
                                    print_r($mappali);
                    		?>
                    			<h2>Modifica edificio N° <?= $_POST['editingEdificio'] ?></h2>
                    			<input type="hidden" name="edificioEditingEd" value="<?= $ed['id'] ?>">
                    			<br>
                    			
                    			<h4>Foglio</h4>
                    			<input id="foglio-editing-ed" name="foglioEditingEd" autocomplete="off" type="number" onkeyup="checkAllMappaliEditingEd();" max="9999" placeholder="Foglio..." required="required" value="<?= $ed['foglio'] ?>">
                    			
                    			<h4>Mappale/i</h4>
                        		<div id="mappali-editing-ed"></div>
                        		<button type="button" onclick="addFiledMappaleEditingEd('', false, <?= $_POST['editingEdificio'] ?>);">+</button>
            
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
            echo "addFiledMappaleEditingEd($mappale[Mappale], ".($mappale['EX']=='EX'?'true':'false').", $_POST[editingEdificio]);";
        echo '</script>';
    }
    if($edInserted) echo "<script>displayMessage('Edificio creato', document.body, 'info');</script>";
    if($edUpdated)
        if($edUpdateError === FALSE)
            echo "<script>displayMessage('Edificio $_POST[edificioEditingEd] modificato con successo', document.body, 'info');</script>";
        else
         echo "<script>displayMessage('Errore durante la modifica dell'edificio $_POST[edificioEditingEd]: $edUpdateError', document.body);</script>";
    ?>
	
</body>
</html>
