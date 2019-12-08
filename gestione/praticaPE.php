<?php
include_once '../controls.php';
include_once '../lib/dbTableFormGenerator.php';
$c = new Controls();
$table = 'pe_pratiche';

if(!$c->logged()){
    header('Location: ../index.php?err=Utente non loggato');
    exit();
}

$id = $_REQUEST['id'];

if($c->check(['update'], $_REQUEST)) {
	//Gestione fogli-mappali
	$c->db->dml('DELETE FROM pe_fogli_mappali_pratiche WHERE Pratica = ?', [$id]);
	foreach ($_REQUEST as $key => $edFoMa)
		if(substr($key, 0, 2) == 'fm') {
			$edFoMa = explode('-', $edFoMa);
			$c->db->dml('INSERT INTO pe_fogli_mappali_pratiche (Pratica, Edificio, Foglio, Mappale) VALUES (?,?,?,?)',
					[$id, $edFoMa[0], $edFoMa[1], $edFoMa[2]]);
			unset($_REQUEST[$key]);
		}
	//Modifica record tabella
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
	<title>Modifica pratica TEC</title>
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
    	//Form pratica
    	DbTableFormGenerator::generate($c, $table, $pratica, FALSE, ['ID', 'Documento_elettronico'], ['Barrato']);

    	//Form fogli-mappali
      	$fogli_mappali_edifici_associati = $c->db->ql(
      		'SELECT CONCAT(\'F.\', fm.Foglio, \' m.\', fm.Mappale) Description, CONCAT_WS(\'-\', e.Edificio, fm.Foglio, fm.Mappale) Value
			FROM pe_edifici_pratiche e
			JOIN fogli_mappali_edifici fm ON fm.Edificio = e.Edificio
			WHERE e.Pratica = ?', [$pratica['ID']]);
      	
      	$fogli_mappali_pratica = $c->db->ql(
      			'SELECT CONCAT_WS(\'-\', Edificio, Foglio, Mappale) Value
			FROM pe_fogli_mappali_pratiche
			WHERE Pratica = ?', [$pratica['ID']]);
      	
      	echo '<label>Fogli-mappali</label><br>';
      	echo '<div id="fogli-mappali"></div>';
      	echo '<script>
				var fogliMappaliEdificiAssociati = '.json_encode($fogli_mappali_edifici_associati, TRUE).';';
      	foreach ($fogli_mappali_pratica as $fm)
      		echo 'addManyTOManyField($("#fogli-mappali"), fogliMappaliEdificiAssociati, "fm", "'.$fm['Value'].'");';
      	echo '</script>';
      	echo '<br><br><button type="button" onclick="addManyTOManyField($(\'#fogli-mappali\'), fogliMappaliEdificiAssociati, \'fm\');">Aggiungi foglio-mappale</button><br><br>';
      /*
       * TODO Buone intenzioni troppo ambiziose
       * DbTableFormGenerator::generateManyToMany($c->db,
	    [
        	'pe_fogli_mappali_pratiche' => [
	      	    'title' => 'Fogli-mappali',
	        	'name' => 'fm',
        	  	'optionsFilter' => ['Edificio' => $edificiPratica],
        		'initValuesFilter' => ['Pratica' => $pratica['ID']],
        		'value' => ['Pratica', 'Edificio', 'Foglio', 'Mappale'],
        	   'description' => "CONCAT('F.',Foglio,' m.',Mappale)"
        	]
    	]);*/
    	?>
    	<input type="submit" name="update">
    </form>
	<script type="text/javascript" src="../js/hints.js"></script>
</body>
</html>
