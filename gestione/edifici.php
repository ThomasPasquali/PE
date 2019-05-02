<?php
    include_once '../controls.php';
    $c = new Controls();

    if(!$c->logged()){
        header('Location: /index.php?err=Utente non loggato');
        exit();
    }

    /*if($c->check(['foglio', 'mappale'], $_POST)){
        $res = $c->db->ql('SELECT *
                                    FROM fogli_mappali_edifici
                                    WHERE Foglio LIKE ? AND Mappale LIKE ?',
                                    ["%$_POST[Foglio]%", "%$_POST[Mappale]%"]);
        print_r($res);

    }*/
    
    $edInserted = false;
    if($c->check(['foglioNewEd','stradarioNewEd'], $_POST)&&isset($_POST['noteNewEd'])){
        $edID = $c->db->ql('SELECT MAX(ID)+1 id FROM edifici')[0]['id'];
        $res = $c->db->dml(
            'INSERT INTO edifici (ID, Foglio, Stradario, Note) VALUES(?,?,?,?)',
            [$edID,$_POST['foglioNewEd'],$_POST['stradarioNewEd'],empty($_POST['noteNewEd'])?NULL:$_POST['noteNewEd']]
        );
        $edInserted = $res->errorCode() == '0';
        if($edInserted)
            foreach ($_POST as $key => $value)
                if(substr($key, 0, strlen('mappNewEd')) === 'mappNewEd'&&!empty($value))
                    $c->db->dml(
                        'INSERT INTO fogli_mappali_edifici (Edificio,Foglio,Mappale) VALUES(?,?,?)',
                        [$edID, $_POST['foglioNewEd'],$value]
                    );
    }
    
    print_r($_POST);

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
        #search-editing-ed{
        
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
            <div class="w3-container">
            	<form id="form-editing-ed" method="post">
            		<div id="search-editing-ed">
                		<h2>Foglio</h2>
                		<input type="number" name="foglio" placeholder="Foglio..." value="<?= $_POST['foglio']??'' ?>">
                		<h2>Mappale/i</h2>
                		<input type="number" name="mappale" placeholder="Mappale..." value="<?= $_POST['mappale']??'' ?>">
                		<input type="submit" value="Cerca">
            		</div>
            	</form>
            </div>
        </div>

        <div id="new-ed" class="content">
        	<div class="w3-container w3-teal"><h1>Nuovo edificio</h1></div>
            <div id="container-new-ed" class="w3-container">
            	<form id="form-new-ed" method="post">
            		<h2>Foglio</h2>
            		<input id="foglio-new-ed" autocomplete="off" onkeyup="checkAllMappali();" type="number" name="foglioNewEd" max="9999" placeholder="Foglio...">

            		<h2>Mappale/i</h2>
            		<div id="mappali-new-ed"></div>
            		<button type="button" onclick="addFiledMappale();">+</button>

            		<h2>Stradario</h2>
 	   				<input id="stradario-new-ed" type="text" autocomplete="off" onkeyup="updateHints('stradario', this, '#hintsStradari-new-ed', '#stradarioID-new-ed');" onclick="this.select();" placeholder="Stradario...">
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
    <script type="text/javascript">addFiledMappale();</script>
	<?php if($edInserted) echo "<script>displayMessage('Edificio creato', document.body, 'info');</script>" ?>
</body>
</html>
