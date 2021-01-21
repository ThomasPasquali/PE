<?php
	include_once '..\controls.php';
	$c = new Controls();
	
	if(!$c->logged()){
		header('Location: ../index.php?err=Utente non loggato');
		exit();
	}
	
	$file = ($c->check(['file'], $_REQUEST)&&file_exists(__DIR__.'/'.$_REQUEST['file']))?(__DIR__.'/'.$_REQUEST['file']):NULL;
	$pratica = NULL;
	if($c->check(['tipo', 'p'], $_REQUEST) && ($_REQUEST['tipo'] == 'pe' || $_REQUEST['tipo'] == 'tec')) 
		$pratica = $c->db->ql("SELECT * FROM $_REQUEST[tipo]_modulistica_view WHERE ID = ?", [$_REQUEST['p']])[0];
    
    if($c->check(['p', 'tipo', 'file'], $_REQUEST) && $pratica) {
        $file = file_get_contents($file);
        $matches = [];
        preg_match_all('/<VAR>([^<>\/]+)<\/VAR>/m', $file, $matches);
        if(count($matches) > 1)
            foreach ($matches[1] as $var)
                $file = str_replace("<VAR>$var</VAR>", $pratica[$var]??'', $file);
        $file = str_replace("<AUTO>Data</AUTO>", date('d/m/Y'), $file);
        $file = str_replace("<AUTO>Manual</AUTO>", '<p class="manual"><textarea></textarea><button onclick="bloccaTesto($(this));">Blocca testo</button></p>', $file);
        echo $file;
        exit();
    }
    
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../css/modulistica.css">
	<style type="text/css">
	#ricerca-pe *, #ricerca-tec *, #files *{
		display: block;
	}
	#pratiche {
		display: grid;
		grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
	}
	</style>
</head>
<body>
	<?php
	if(!$file) {
		echo '<h1>Selezionare il modulo</h1>';
		echo '<div id="files">';
		foreach (scandir(__DIR__) as $file) {
			$exploded = explode('.', $file);
			if ($file !== '.' && $file !== '..' && $exploded[(count($exploded)-1)] == 'html' && !in_array($file, ['index.php']))
				echo '<a href="?file='.$file.'">'.explode('.', $file)[0].'</a>';
		}
		echo '</div>';
	} else if(!$pratica) { ?>
		<h1>Selezionare la pratica</h1>
		<form action="" method="post">
			<input type="hidden" name="file" value="<?= $_REQUEST['file'] ?>">
			
			<label>PE</label>
			<input type="radio" name="tipo" value="pe" checked="checked" onclick="document.getElementById('ricerca-pe').style.display = 'block'; document.getElementById('ricerca-tec').style.display = 'none';">
			<label>TEC</label>
			<input type="radio" name="tipo" value="tec" onclick="document.getElementById('ricerca-tec').style.display = 'block'; document.getElementById('ricerca-pe').style.display = 'none';">
			
			<div id="ricerca-pe">
				<label>Tipo</label>
				<select name="pe-tipo">
				<?php foreach ($c->getEnumValues('pe_pratiche', 'TIPO') as $o) echo "<option value=\"$o\">$o</option>"; ?>
				</select>
				<label>Anno</label>
				<input type="number" name="pe-anno" placeholder="Anno...">
				<label>Numero</label>
				<input type="number" name="pe-numero" placeholder="Numero...">
				<input type="submit" value="Cerca pratiche">
			</div>
			
			<div id="ricerca-tec" style="display: none;">
				<label>Tipo</label>
				<select name="tec-tipo">
				<?php foreach ($c->getEnumValues('tec_pratiche', 'TIPO') as $o) echo "<option value=\"$o\">$o</option>"; ?>
				</select>
				<label>Anno</label>
				<input type="number" name="tec-anno" placeholder="Anno...">
				<label>Numero</label>
				<input type="number" name="tec-numero" placeholder="Numero...">
				<input type="submit" value="Cerca pratiche">
			</div>
		</form>
	<?php 
		$tipo = $_REQUEST['tipo']??'';
		if($tipo == 'pe' || $tipo == 'tec') {
			$where = [];
			$params = [];
			if($_REQUEST[$tipo.'-tipo']??'') {
				$where[] = 'Tipo = ?'; $params[] = $_REQUEST[$tipo.'-tipo'];
			}
			if($_REQUEST[$tipo.'-anno']??'') {
				$where[] = 'Anno = ?'; $params[] = $_REQUEST[$tipo.'-anno'];
			}
			if($_REQUEST[$tipo.'-numero']) {
				$where[] = 'Numero = ?'; $params[] = $_REQUEST[$tipo.'-numero'];
			}
			$pratiche = $c->db->ql('SELECT ID, Sigla
	                                        FROM '.$tipo.'_pratiche_view
	                                        WHERE '.implode(' AND ', $where), $params);
			echo '<div id="pratiche">';
			foreach ($pratiche as $pratica)
				echo "<a href=\"?file=$_REQUEST[file]&tipo=$tipo&p=$pratica[ID]\">$pratica[Sigla]</a>";
			echo '</div>';
		}
	}
	?>
</body>
</html>