<?php 
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
	<title>Calcolo CC e OU</title>
    <script src="../lib/jquery-3.3.1.min.js"></script>
    <link rel="stylesheet" type="text/css" href="../css/form.css">
    <link rel="stylesheet" type="text/css" href="../css/inserimento_oneriEcosti.css">
</head>
<body>
	
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
              	        echo "<p class=\"risultato-pratica\" onclick=\"selectPratica(this);\"><span style=\"display:none;\">$pratica[ID]</span>$pratica[TIPO]$pratica[Anno]/$pratica[Numero]</p>";
              	?>
              </div>
            </div>
          </div>
	</div>
	
	<div id="main-div">
	<h1>Oneri di urbanizzazione</h1>
	<?php 
	if(isset($_POST['tipo'])&&isset($_POST['anno'])&&isset($_POST['numero'])){ 
	    include_once '../lib/oneriEcosti/oneriEcosti.php';
	    OneriECosti::generaQuestionarioOU();
	}
	?>
	</div>
	
	<div id="inserimento-imponibile">
		<h1 id="titolo-imponibile">Imponibile</h1>
		<input id="imponibile" type="number" onclick="this.select();" onchange="this.value = parseFloat(this.value).toFixed(3);" min="1" step="0.5" >
		<button id="btnBloccaOneri" type="button">Conferma</button>
	</div>
	
	<div id="cc">
		<h1>Costo di costuzione</h1>
		<select onchange="switch (this.selectedIndex) { case 0: $('#cc-residenza').hide(); $('#cc-turistico-direzionale').hide(); break; case 1: $('#cc-residenza').show(); $('#cc-turistico-direzionale').hide(); break; case 2: $('#cc-residenza').hide(); $('#cc-turistico-direzionale').show(); break;}">
			<option></option>
			<option>Residenza</option>
			<option>Turistica, commerciale o direzionale</option>
		</select>
		<div id="cc-residenza">
			<h2>Superifici utili abitabili</h2>
    		<div id="fields-alloggi"></div>
    		<button onclick="addFieldAlloggio();">Aggiungi alloggio</button>
    		<button onclick="fineInserimentoAlloggi();">Prosegui</button>
    		<input id="snr" type="number" onclick="this.select();" onchange="this.value = parseFloat(this.value).toFixed(3);" min="1" step="0.5" placeholder="Su. servizi e accessori...">
		</div>
		<div id="cc-turistico-direzionale">
    		<input id="sn" type="number" onclick="this.select();" onchange="this.value = parseFloat(this.value).toFixed(3);" min="1" step="0.5" placeholder="Su. calpestabile...">
    		<input id="sa" type="number" onclick="this.select();" onchange="this.value = parseFloat(this.value).toFixed(3);" min="1" step="0.5" placeholder="Su. accessori...">
    		<button onclick="fineInserimentoAlloggi();">Prosegui</button>
		</div>
	</div>

	<script src="../js/inserimento_oneriEcosti.js"></script>
</body>
</html>