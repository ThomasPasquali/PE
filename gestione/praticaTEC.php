<?php
include_once '../controls.php';
include_once '../lib/dbTableFormGenerator.php';
$c = new Controls();
$table = 'tec_pratiche';

if(!$c->logged()){
    header('Location: ../index.php?err=Utente non loggato');
    exit();
}

$id = $_REQUEST['id'];

if($c->check(['update'], $_REQUEST)) {
    unset($_REQUEST['id']);
    unset($_REQUEST['update']);
    $res = DbTableFormGenerator::updateRecord($c->db, $table, $_REQUEST);
    if($res === TRUE) {
        header('Location: ../home.php?succ=Pratica modificata');
        exit();
    }else
        $err = $res;
}

if(!$id){
    header('Location: ../home.php?err=Richiesta errata');
    exit();
}

$res = $c->db->ql("SELECT * FROM $table WHERE ID = ?", [$id]);
if(count($res) < 0){
    header('Location: ../home.php?err=Pratica non trovata');
    exit();
}
else $pratica = $res[0];
?>
<html>
<head>
    <style type="text/css">
        input, select, textarea {
            display: block;
        }
        .hintDiv *{
            display: block;
        }
    </style>
    <script src="../lib/jquery-3.3.1.min.js"></script>
    <script src="../js/gestione_pratiche.js"></script>
    <link rel="stylesheet" type="text/css" href="../css/utils_bar.css">
</head>
<body>
	<?php
	$c->includeHTML('../htmlUtils/utils_bar.html');
	if(isset($err))
        echo "<h1>Errore durante la modifica: $err</h1>";
	?>
    <form method="post">
    	<input type="hidden" name="id" value="<?= $id ?>">
    	<?php
    	DbTableFormGenerator::generate($c, $table, $pratica, FALSE, ['ID', 'IDold'], ['Barrato']);

      /*$res = $c->db->ql('SELECT Edificio FROM pe_edifici_pratiche WHERE Pratica = ?', [$pratica['ID']]);
      $edificiPratica = [];
      foreach ($res as $value)
        $edificiPratica[] = $value['Edificio'];
      DbTableFormGenerator::generateManyToMany($c->db,
	    [
        	'pe_fogli_mappali_pratiche' => [
	      	    'title' => 'Fogli-mappali',
	        	'name' => 'fm',
        	  	'optionsFilter' => ['Edificio' => $edificiPratica],
        		'initValuesFilter' => ['Pratica' => $pratica['ID']],
        		'value' => ['Edificio', 'Pratica', 'Foglio', 'Mappale'],
        		//TODO Da gestire tabella esterna
        	   'description' => "CONCAT('F.',Foglio,'m.',Mappale)"
        	]
    	]);*/
    	?>
    	<input type="submit" name="update">
    </form>
	<script type="text/javascript" src="../js/hints.js"></script>
</body>
</html>