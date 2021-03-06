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
                                        FROM pe_pratiche
                                        WHERE '.implode(' AND ', $where), $params);
    }
    
?>
<html>
<head>
	<title>Calcolo CC e OU</title>
    <script src="../lib/jquery-3.3.1.min.js"></script>
    <link rel="stylesheet" type="text/css" href="../css/form.css">
    <link rel="stylesheet" type="text/css" href="../css/inserimento_oneriEcosti.css">
    <script type="text/javascript">
    
    </script>
</head>
<body>
	<?php 
	if($c->check(['OU1', 'OU2', 'imponibileOU', 'formOneri'], $_POST)) {
		$db = (isset($_REQUEST['conferma']) ? $c->db : NULL);
		OneriECosti::calcola($_POST, 'pe', $db);
		if(!$db){
	?>		
			<form id="form" method="post">
				<script type="text/javascript">
				<?php 
				foreach ($_POST as $key => $value)
					echo "i = $('<input>').attr('type', 'hidden').attr('name', '$key').val('$value'); $('#form').append(i);";
				?>
				</script>
				<button type="submit" name="conferma">Conferma il calcolo</button>
			</form>
	<?php 
			exit();
		}else {
			header('Location: ../gestione/pagamentiOneriEcostiPE.php?p='.$_POST['pratica']);
			exit();
		}
	} 
	?>
	<div id="selezione-pratica">
		<div class="form">
            <h1>Calcolo CC e OU<span id="info-pratica"></span></h1>
            <div class="section">Selezione pratica</div>
            <div class="inner-wrap">
              <form method="post" id="ricerca-pratica">
                <select name="tipo">
                <?php
                $types = $c->getEnumValues('pe_pratiche', 'TIPO', $c->db);
                foreach ($types as $type) echo "<option value=\"$type\">".str_replace('_', ' ', $type)."</option>";
                ?>
              </select>
                <input name="anno" type="number" placeholder="Anno..." autofocus>
                <input name="numero" type="number" placeholder="Numero...">
                <input type="submit" value="Cerca">
              </form>
            </div>
          </div>
          <h3 class="centered">Risultati ricerca</h3>
          <div class="box-risultati">
              	<?php 
              	if(isset($pratiche))
              	    foreach ($pratiche as $pratica)
              	        echo "<p class=\"risultato-pratica\" onclick=\"selectPratica(this);\"><span style=\"display:none;\">$pratica[ID]</span>$pratica[TIPO]$pratica[Anno]/$pratica[Numero]</p>";
              	?>
          </div>
	</div>
	
	<div>
	
	<form id="form" action="" method="post">
	<div id="container">
		<div id="ou">
        	<h1 id="titolo-ou">Oneri di urbanizzazione</h1>
        	<div id="coefficienti">
        	<?php 
        	if(isset($_POST['tipo'])&&isset($_POST['anno'])&&isset($_POST['numero']))
        	    OneriECosti::generaQuestionarioOU();
        	?>
    		</div>
    		
        	<div id="inserimento-imponibile">
        		<h1>Imponibile</h1>
        		<input name="imponibileOU" type="number" onclick="this.select();" onchange="this.value = parseFloat(this.value).toFixed(3);" min="1" step="0.5" >
        	</div>
		</div>
    	
    	<div id="cc">
    			<h1>Costo di costuzione</h1>
    			<script type="text/javascript">
    				document.getElementsByClassName("branch0 level0")[0].childNodes[1].addEventListener("click", function () {
        				switch (this.selectedIndex) {
    						case 0: 
        						$('#cc-residenza').hide(); 
        						$('#cc-turistico-direzionale').hide();
        						$('#cc-attivita-produttiva').hide();
        						break;
        						
    						case 1: 
            					$('#cc-residenza').show(); 
            					$('#cc-turistico-direzionale').hide();
            					$('#cc-attivita-produttiva').hide();
            					break;
            					
        					case 2: 
            					$('#cc-residenza').hide(); 
            					$('#cc-turistico-direzionale').hide();
            					$('#cc-attivita-produttiva').show();
            					break; 

            				default: 
                				$('#cc-residenza').hide(); 
            					$('#cc-turistico-direzionale').show();
            					$('#cc-attivita-produttiva').hide();
            					break;
    					}
					});
    			</script>
        		<div id="cc-residenza">
        			<h2>Superifici utili abitabili</h2>
            		<div id="fields-alloggi"></div>
            		<button type="button" onclick="addFieldAlloggio();">Aggiungi alloggio</button>
            		<h2>Superificie totale servizi e accessori</h2>
            		<input name="snr" type="number" onclick="this.select();" onchange="this.value = parseFloat(this.value).toFixed(3);" min="1" step="0.5" placeholder="Superficie in mq...">
            		<h2>Caratteristiche particolari</h2>
            		<ol>
            			<li><input type="checkbox" name="aumento0" class="aumento">Pi&ugrave; di un ascensore per ogni scala se questa serve meno di sei piani sopraelevati</li>
            			<li><input type="checkbox" name="aumento1" class="aumento">Scala di servizio non prescritta da leggi o regolamenti o imposta da necessità di prevenzione di infortuni o incendi</li>
            			<li><input type="checkbox" name="aumento2" class="aumento">Altezza netta libera di piano superiore a m. 3,00 a quella minima prescritta da norme regolamentari. Per ambienti con altezze diverse si fa riferimento all'altezza media ponderale</li>
            			<li><input type="checkbox" name="aumento3" class="aumento">Piscina coperta o scoperta quando sia a servizio di uno o pi&egrave; edifici comprendenti meno di 15 unit&agrave; immobiliari</li>
            			<li><input type="checkbox" name="aumento4" class="aumento">Alloggi di custodia a servizio di uno o pi&egrave; edifici comprendenti meno di 15 unit&agrave; immobiliari</li>
        			</ol>
        			<h2>Incremento costo di costruzione</h2>
        			<?php 
                    	if(isset($_POST['tipo'])&&isset($_POST['anno'])&&isset($_POST['numero']))
                    	    OneriECosti::generaQuestionarioIncrementoCC();
                    	?>
        		</div>
        		<div id="cc-turistico-direzionale">
        			<h2>Superficie calpestabile</h2>
            		<input name="sn" type="number" onclick="this.select();" onchange="this.value = parseFloat(this.value).toFixed(3);" min="1" step="0.5" placeholder="Superficie in mq...">
            		<h2>Superficie accessori</h2>
            		<input name="sa" type="number" onclick="this.select();" onchange="this.value = parseFloat(this.value).toFixed(3);" min="1" step="0.5" placeholder="Superficie in mq...">
        		</div>
        		<div id="cc-attivita-produttiva">
        			<p>Nessun costo di costruzione per le attivit&agrave; produttive</p>
        		</div>
        		
			</div>
		</div>
		<textarea id="note" style="margin-top: 50px; display: none;" rows="5" cols="50" name="note" placeholder="Note..."></textarea>
		</form>
		<button id="calcola" type="button" onclick="checkANDsubmit();">Calcola</button>
		
	</div>
	
	<script src="../js/inserimento_oneriEcosti.js"></script>
</body>
</html>