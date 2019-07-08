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
    
    function createSelect($xml, $loopCount = 0) {
        
        foreach ($xml as $key => $items) {
            
            if($key != '@attributes' && $key != 'comment'){
                
                echo "<div class=\"level$loopCount".($loopCount>0?' hidden':'')."\" ".(isset($xml['@attributes']['value'])?' id="'.$xml['@attributes']['value'].'"':'').'>';
                
                echo "<h2>$key</h2>";
                
                echo '<select onchange="showOnlyThatDiv(\'level'.($loopCount+1).'\', this.options[this.selectedIndex].getAttribute(\'value\'));">';
                echo '<option></option>';
                foreach ($items as $option)
                    echo '<option value="'.$option['@attributes']['value'].'">'.$option['@attributes']['value'].'</option>';
                    echo '</select>';
                    
                    foreach ($items as $option)
                        if(has_all_keys($option, ['OU1', 'OU2']))
                            echo '<button type="button" id="'.$option['@attributes']['value'].'" class="level'.($loopCount+1).' hidden" onclick="setOU1OU2('.$option['OU1'].', '.$option['OU2'].');">Conferma oneri</button>';
                        else
                    createSelect($option, $loopCount+1);
                  
                    echo '</div>';
            }
            
        }
        
    }
    
    function has_more_keys($array, $exclusions) {
        foreach ($array as $key => $value)
            if(!in_array($key, $exclusions))
                return true;
                return false;
    }
    
    function has_all_keys($array, $keys) {
        foreach ($keys as $key)
            if(!isset($array[$key]))
                return false;
                return true;
    }
    
?>
<html>
<head>
	<title>Calcolo CC e OU</title>
    <script src="../lib/jquery-3.3.1.min.js"></script>
    <script src="../js/inserimento_oneriEcosti.js"></script>
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
	<?php 
	if(isset($_POST['tipo'])&&isset($_POST['anno'])&&isset($_POST['numero'])){ 
	    $xml = json_decode(json_encode(simplexml_load_file('../lib/oneriEcosti/costiBase.xml')), true);
	    createSelect($xml['OU']);
	}
	    /*libxml_use_internal_errors(true);
	    $xml = simplexml_load_string(file_get_contents('../lib/oneriEcosti/costiBase.xml'));
	    if(!$xml){ echo "ERRORE NEL PARSING DEL FILE XML"; exit(); }
	    
	    $ob = simplexml_load_file('../lib/oneriEcosti/costiBase.xml');
	    $json = json_encode($ob);
	    $xmlArray = json_decode($json, true);
	    
        $ou = $xml->OU;
        echo '<h2>Destinazione d\'uso</h2>';
        echo '<select onchange="showOnlyThatDiv(\'divDestinazioneUso\', this.options[this.selectedIndex].innerHTML);">';
        echo '<option></option>';
        foreach ($ou->Destinazione_uso as $dest_uso) echo "<option value=\"$dest_uso[value]\">$dest_uso[value]</option>";
        echo '</select>';
            
        foreach ($ou->Destinazione_uso as $dest_uso){
            echo "<div class=\"divDestinazioneUso\" id=\"$dest_uso[value]\">";
            switch ($dest_uso['value']) {
                
                case 'Residenza':
                    echo '<h2>Tipo di intervento</h2>';
                    echo '<select onchange="showOnlyThatDiv(\'divTipoIntervento\', this.options[this.selectedIndex].innerHTML);">';
                    echo '<option></option>';
                    foreach ($dest_uso->Tipo_di_intervento as $tipo_intervento)
                        echo "<option value=\"$tipo_intervento[value]\">$tipo_intervento[value]</option>";
                    echo '</select>';
                    
                    foreach ($dest_uso->Tipo_di_intervento as $tipo_intervento){
                        echo "<div class=\"divTipoIntervento\" id=\"$tipo_intervento[value]\">";
                        iterateOverZone($tipo_intervento, $countSetDomande++);
                        echo '</div>';
                    }
                        
                break;
                
                
                default:
                    echo "Destinazione d'uso $dest_uso[value] non gestito";
                break;
            }
            echo '</div>';
        }
        
        $cc = $xml->CC;
	} */
	?>
	</div>

</body>
</html>