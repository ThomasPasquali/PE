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

if($c->check(['delete'], $_REQUEST)) {
	$res = $c->db->dml('DELETE FROM pe_pratiche WHERE ID = ?', [$id]);
	if($res->errorCode() == 0) {
		header('Location: ../');
		exit();
	}else
		echo $res->errorInfo()[2];
}

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
	//Gestione intestatari persone
	$c->db->dml('DELETE FROM pe_intestatari_persone_pratiche WHERE Pratica = ?', [$id]);
	foreach ($_REQUEST as $key => $persona)
		if(substr($key, 0, strlen('intestatario_persona')) == 'intestatario_persona') {
			$c->db->dml('INSERT INTO pe_intestatari_persone_pratiche (Pratica, Persona) VALUES (?,?)', [$id, $persona]);
			unset($_REQUEST[$key]);
		}
	//Gestione intestatari societa
	$c->db->dml('DELETE FROM pe_intestatari_societa_pratiche WHERE Pratica = ?', [$id]);
	foreach ($_REQUEST as $key => $societa)
		if(substr($key, 0, strlen('intestatario_societa')) == 'intestatario_societa') {
			$c->db->dml('INSERT INTO pe_intestatari_societa_pratiche (Pratica, Societa) VALUES (?,?)', [$id, $societa]);
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
	<title>Modifica pratica PE</title>
    <style type="text/css">
        input, select, textarea {
            display: block;
        }
        .hintBox *{
            display: block;
        }
    </style>
	<script src="../lib/jquery-3.3.1.min.js"></script>
	<script defer type="text/javascript" src="../js/hints.js"></script>
	<script type="text/javascript" src="../js/gestione_pratiche.js"></script>
    <link rel="stylesheet" type="text/css" href="../css/utils_bar.css">
</head>
<body>
	<?php
	$c->includeHTML('../htmlUtils/utils_bar.html');
	if(isset($err))
        echo "<h1>Errore durante la modifica: $err</h1>";
	?>
    <form id="formModifica" method="post">
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
		
		$intestatari_persone = $c->db->ql(
			"SELECT ip.ID, CONCAT(ip.Cognome, ' ', ip.Nome, '(', ip.Codice_fiscale, ')') descr
			FROM pe_intestatari_persone_pratiche ipp
			JOIN intestatari_persone ip ON ip.ID = ipp.Persona
			WHERE Pratica = ?", [$pratica['ID']]);

		$intestatari_societa = $c->db->ql(
			"SELECT i.ID, CONCAT(i.Intestazione, ' (', i.Partita_iva, ')') descr
			FROM pe_intestatari_societa_pratiche isp
			JOIN intestatari_societa i ON i.ID = isp.Societa
			WHERE Pratica = ?", [$pratica['ID']]);
	?>

		<label>Intestatari persone</label><br>
		<div id="intestatari-persone">
			<button class="add" type="button">+</button>
		</div>

		<label>Intestatari societ&agrave;</label><br>
      	<div id="intestatari-societa">
			<button class="add" type="button">+</button>
		</div>

		<label>Fogli-mappali</label><br>
		<div id="fogli-mappali"></div>
		  
		<script>
			var fogliMappaliEdificiAssociati = <?= json_encode($fogli_mappali_edifici_associati, TRUE) ?>;
			<?php 
			foreach ($fogli_mappali_pratica as $fm)
				echo "addManyTOManyField($('#fogli-mappali'), fogliMappaliEdificiAssociati, 'fm', '$fm[Value]'); ";
			foreach($intestatari_persone as $ip) 
				echo "addFieldIntestatarioPersona($ip[ID],'".str_replace("'","\'",$ip['descr'])."'); ";
			foreach($intestatari_societa as $is) 
				echo "addFieldIntestatarioSocieta($is[ID],'".str_replace("'","\'",$is['descr'])."'); ";
			?>
		</script>

		<br><br><button type="button" onclick="addManyTOManyField($('#fogli-mappali'), fogliMappaliEdificiAssociati, 'fm');">Aggiungi foglio-mappale</button><br><br>
		
		<input type="button" name="delete" style="background-color:red;font-size:1rem;position:absolute;right:20px;bottom:20px;" value="Elimina pratica">
		<input type="submit" name="update" style="background-color:green;font-size:1.6rem;" value="Aggiorna pratica">
    </form>
</body>
</html>
