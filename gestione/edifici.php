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
	<link rel="stylesheet" href="style_gestione.css">
	<script type="text/javascript" src="/lib/jquery-3.3.1.min.js"></script>
	<style type="text/css">
	   #mappali-new-ed input{
	       /*display: block;*/
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
            		<input type="number" name="foglio" placeholder="Foglio..." value="<?= $_POST['foglio']??'' ?>">
            		<input type="number" name="mappale" placeholder="Mappale..." value="<?= $_POST['mappale']??'' ?>">
            		<input type="submit" value="Cerca">
            	</form>
            </div>
        </div>

        <div id="new-ed" class="content" style="display: none;">
        	<div class="w3-container w3-teal"><h1>Nuovo edificio</h1></div>
            <div class="w3-container">
            	<form method="post">
            		<input id="foglio-new-ed" onkeyup="checkAllMappali();" type="number" name="foglio" placeholder="Foglio...">
            		<div id="mappali-new-ed"></div>
            		<button type="button" onclick="addFiledMappale();">+</button>
            		<!-- ✔ -->
            		<input type="button" onclick="submitNewEdificio();" value="CREA NUOVO EDIFICIO">
            	</form>
            </div>
        </div>

    </div>

    <script type="text/javascript" src="/lib/misc.js"></script>
	<script type="text/javascript" src="edifici.js"></script>
    <script type="text/javascript">addFiledMappale();</script>

</body>
</html>
