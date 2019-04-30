<?php
    include_once '../controls.php';
    $c = new Controls();

    if(!$c->logged()){
        header('Location: /index.php?err=Utente non loggato');
        exit();
    }

    if($c->check(['foglio', 'mappale'], $_POST)){
        $res = $c->db->ql('SELECT *
                                    FROM fogli_mappali_edifici
                                    WHERE Foglio LIKE ? AND Mappale LIKE ?',
                                    ["%$_POST[Foglio]%", "%$_POST[Mappale]%"]);
        print_r($res);

    }

    print_r($_POST);

?>
<html>
<head>
	<title>Gestione edifici</title>
	<link rel="stylesheet" href="/css/gestione.css">
	<script type="text/javascript" src="/lib/jquery-3.3.1.min.js"></script>
	<style type="text/css">
        .hintBox *{
            display: block;
            margin-top: 10px;
        }
    </style>
</head>
<body>

	<!-- Sidebar -->
    <div class="w3-sidebar w3-light-grey w3-bar-block" style="width:17%">
      <p onclick="reloadPageWithFlag('vis-mod')" class="w3-bar-item w3-button">Visualizza/<br>modifica</p>
      <p onclick="reloadPageWithFlag('new-ed')" class="w3-bar-item w3-button">Nuovo<br>edificio</p>
    </div>

    <!-- Page Content -->
    <div id="pageContent" style="margin-left:17%">

        <div id="vis-mod" class="content">
        	<div class="w3-container w3-teal"><h1>Visualizza/modifica edifci</h1></div>
            <div class="w3-container">
            	<form method="post">
            		<h2>Foglio</h2>
            		<input type="number" name="foglio" placeholder="Foglio..." value="<?= $_POST['foglio']??'' ?>">
            		<h2>Mappale/i</h2>
            		<input type="number" name="mappale" placeholder="Mappale..." value="<?= $_POST['mappale']??'' ?>">
            		<input type="submit" value="Cerca">
            	</form>
            </div>
        </div>

        <div id="new-ed" class="content" style="display: none;">
        	<div class="w3-container w3-teal"><h1>Nuovo edificio</h1></div>
            <div class="w3-container">
            	<form method="post">
            		<h2>Foglio</h2>
            		<input id="foglio-new-ed" onkeyup="checkAllMappali();" type="number" name="foglio" max="9999" placeholder="Foglio...">

            		<h2>Mappale/i</h2>
            		<div id="mappali-new-ed"></div>
            		<button type="button" onclick="addFiledMappale();">+</button>

            		<h2>Stradario</h2>
 	   				<input id="stradario" type="text" onkeyup="updateHints('stradario', this, '#hintsStradari', '#stradarioID');" onclick="this.select();" placeholder="Stradario...">
 	   				<input id="stradarioID" name="stradario" type="hidden">
     	   			<div id="hintsStradari" class="hintBox"></div>

            		<h2>Note</h2>
            		<textarea rows="3" cols="40" placeholder="Inserire qui eventuali note..."></textarea>

            		<br>
            		<input type="button" onclick="submitNewEdificio();" value="CREA NUOVO EDIFICIO">
            	</form>
            </div>
        </div>

    </div>

    <script type="text/javascript" src="/js/misc.js"></script>
	<script type="text/javascript" src="/js/edifici.js"></script>
    <script type="text/javascript">addFiledMappale();</script>

</body>
</html>
