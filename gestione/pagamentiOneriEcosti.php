<?php
	include_once '../lib/oneriEcosti/oneriEcosti.php';
	include_once '../controls.php';
	$c = new Controls();
	
	if(!$c->logged()){
		header('Location: ../index.php?err=Utente non loggato');
		exit();
	}
	
	if(isset($_POST['tipo'])&&isset($_POST['anno'])&&isset($_POST['numero'])){
		$where = [];
		$params = [];
		if($_POST['tipo']) {
			$where[] = 'Tipo = ?';
			$params[] = $_POST['tipo'];
		}
		if($_POST['anno']) {
			$where[] = 'Anno = ?';
			$params[] = $_POST['anno'];
		}
		if($_POST['numero']) {
			$where[] = 'Numero = ?';
			$params[] = $_POST['numero'];
		}
		$pratiche = $c->db->ql('SELECT ID, TIPO, Anno, Numero, Barrato
                                        FROM tec_pratiche
                                        WHERE '.implode(' AND ', $where), $params);
	}
?>
<html>
	<head>
		<title>Pagamenti CC e OU</title>
	    <script src="../lib/jquery-3.3.1.min.js"></script>
	    <script src="../js/pagamentiOneriEcosti.js"></script>
	    <link rel="stylesheet" type="text/css" href="../css/pagamentiOneriEcosti.css">
	    <link rel="stylesheet" type="text/css" href="../css/form.css">
	</head>
	<body>
	<?php 
	$pratica = $_REQUEST['p']??NULL;
	if(!$pratica) {
	?>
		<div id="selezione-pratica">
			<div class="form">
	            <h1>Calcolo CC e OU<span id="info-pratica"></span></h1>
	            <div class="section">Selezione pratica</div>
	            <div class="inner-wrap">
	              <form method="post" id="ricerca-pratica">
	                <select name="tipo">
	                <?php
	                $types = $c->getEnumValues('tec_pratiche', 'TIPO', $c->db);
	                foreach ($types as $type) echo "<option value=\"$type\">".str_replace('_', ' ', $type)."</option>";
	                ?>
	              </select>
	                <input name="anno" type="number" placeholder="Anno..." autofocus>
	                <input name="numero" type="number" placeholder="Numero...">
	                <input type="submit" value="Cerca">
	              </form>
	              <h3 class="centered">Risultati ricerca</h3>
	              <div class="box-risultati">
	              	<?php 
	              	if(isset($pratiche))
	              	    foreach ($pratiche as $pratica)
	              	        echo "<a class=\"risultato-pratica\" style=\"display:block;\" href=\"?p=$pratica[ID]\">$pratica[TIPO]$pratica[Anno]/$pratica[Numero]</a>";
	              	?>
	              </div>
	            </div>
	          </div>
		</div>
	<?php 
	} else {
		$pratica = $c->getDatiPraticaTEC($pratica);
	?>
	
		<div id="main">
			<h1><?= $pratica['Tipo'].$pratica['Anno'].'/'.$pratica['Numero'] ?></h1>
			<h2>Calcoli associati</h2>
			<div id="calcoli">
				<?php 
				$calcoli = $c->db->ql('SELECT * FROM tec_ou_cc WHERE Pratica = ? ORDER BY Numero_revisione', [$pratica['ID']]);
				if(count($calcoli) == 0)
					echo '<h3>Nessun calcolo associato alla pratica, <a href="pagamentiOneriEcosti.php">cerca un\'altra pratica</a></h3>';
				foreach ($calcoli as $calcolo) {
					?>
					<div class="calcolo<?= $calcolo['Attivo']=='N'?' inattivo':'' ?>">
						<button type="button" onclick="<?= $calcolo['Attivo']=='N'?'':'dis' ?>attivaPagamenti('<?= $calcolo['ID'] ?>', this);"><?= $calcolo['Attivo']=='N'?'Attiva':'Disattiva' ?> pagamenti</button>
						<div class="calcolo-sub">
							<div class="descrizione-calcolo">
								<h3>Descrizione calcolo</h3>
								<?php 
								$blacklist = ['Pratica', 'Numero_revisione', 'Attivo'];
								foreach ($calcolo as $titolo => $valore)
									if(!in_array($titolo, $blacklist))
										echo "<div class=\"descrizione-calcolo-riga\"><span>$titolo: </span><div>$valore</div></div>";
								?>
							</div>
							<div id="pagamenti">
								<h3>Pagamenti associati</h3>
								<h4>Oneri di urbanizzazione</h4>
								<div id="pagamentiOU<?= $calcolo['ID'] ?>" class="pagamenti-sub"></div>
								<?php 
								$pagamentiOU = $c->db->ql('SELECT * FROM tec_pagamenti_ou WHERE Ou_cc = ? ORDER BY Data', [$calcolo['ID']]);
								echo "<script>";
								foreach ($pagamentiOU as $pagamento) {
									$data = explode('-', $pagamento['Data']);
									echo "addFieldsPagamentoOU($pagamento[Importo], '".(count($data)==3?'versati il '.$data[2].'/'.$data[1].'/'.$data[0]:'')."', $pagamento[ID], $calcolo[ID]);";
								}
								echo "</script>";
								?>
								<div>
									<label>Importo:</label>
									<input id="importoOU<?= $calcolo['ID'] ?>" type="number">
									<label>Data:</label>
									<input id="dataOU<?= $calcolo['ID'] ?>" type="date">
									<button type="button" onclick="aggiungiPagamentoOU(<?= $calcolo['ID'] ?>);">Aggiungi pagamento</button>
								</div>
								<h4>Costo di costruzione</h4>
								<div id="pagamentiCC<?= $calcolo['ID'] ?>" class="pagamenti-sub"></div>
								<?php 
								$pagamentiCC = $c->db->ql('SELECT * FROM tec_pagamenti_cc WHERE Ou_cc = ? ORDER BY Data', [$calcolo['ID']]);
								echo "<script>";
								foreach ($pagamentiCC as $pagamento) {
									$data = explode('-', $pagamento['Data']);
									echo "addFieldsPagamentoCC($pagamento[Importo], '".(count($data)==3?'versati il '.$data[2].'/'.$data[1].'/'.$data[0]:'')."', $pagamento[ID], $calcolo[ID]);";
								}
								echo "</script>";
								?>
								<div>
									<label>Importo:</label>
									<input id="importoCC<?= $calcolo['ID'] ?>" type="number">
									<label>Data:</label>
									<input id="dataCC<?= $calcolo['ID'] ?>" type="date">
									<button type="button" onclick="aggiungiPagamentoCC(<?= $calcolo['ID'] ?>);">Aggiungi pagamento</button>
								</div>
							</div>
						</div>
					</div>
					<?php 				
				}
				?>
			</div>
		</div>
		
	<?php 
	}
	?>	
	</body>
</html>