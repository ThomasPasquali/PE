<?php 
    include_once '../controls.php';
    $c = new Controls();
    
    function cmp($a, $b) {
    	return strcmp($a['Data'], $b['Data']);
    }
    
?>
<html>
<head>
	<title>Report pratiche</title>
	<style type="text/css">
	h1 {
	   font-size: 1.8em;
	}
	form {
	   text-align: center;
	}
	p {
	   word-wrap: break-word;
	   margin: 2px;
	}
	label {
	   font-size: 150%;
	}
	input {
	   height: 30px;
	   margin-bottom: 5px; 
	}
	.pratica{
       display: grid;
       grid-template-columns: 1fr 1fr 1fr 1fr;
       border-top: solid black 1px;
       border-bottom: solid black 1px;
       padding-top: 5px; 
       padding-bottom: 5px; 
	}
	#Intestatario {
	   grid-column-start: 3;
        grid-column-end: 5;
	}
	#Fogli_Mappali {
	   grid-column-start: 1;
        grid-column-end: 3;
	}
	#Oggetto {
	   grid-column-start: 1;
        grid-column-end: 5;
	}
	</style>
</head>
<body>
	<?php 
	if($c->check(['start', 'end'], $_REQUEST)){
	    ?>
	    <h1>Comune di Canale d'Agordo (BL) - Ufficio Tecnico</h1>
	    <h2>Elenco pratiche edilizie dal <?= (new DateTime($_REQUEST['start']))->format('d/m/Y') ?> al <?= (new DateTime($_REQUEST['end']))->format('d/m/Y') ?></h2>
    <?php 
        $praticheTEC = $c->db->ql(
            'SELECT TIPO Tipo, `Data`, Sigla, Protocollo, 
				FogliMappali Fogli_Mappali, 
				CONCAT(IF(Intestatari_persone IS NULL, \'\', Intestatari_persone), IF(Intestatari_societa IS NULL, \'\', Intestatari_societa)) Intestatario, 
				Intervento Oggetto
			FROM tec_pratiche_view
			WHERE `Data` BETWEEN ? AND ?
			ORDER BY `Data`',
            [$_REQUEST['start'], $_REQUEST['end']]);
        
        $pratichePE = $c->db->ql(
        	'SELECT TIPO Tipo, `Data`, Sigla, Protocollo, 
				FogliMappali Fogli_Mappali, CONCAT(IF(Intestatari_persone IS NULL, \'\', Intestatari_persone), IF(Intestatari_societa IS NULL, \'\', Intestatari_societa)) Intestatario, 
		    	Intervento Oggetto
			FROM pe_pratiche_view
			WHERE `Data` BETWEEN ? AND ?
			ORDER BY `Data`',
        	[$_REQUEST['start'], $_REQUEST['end']]);
        
        $pratiche = array_merge($pratichePE, $praticheTEC);
        usort($pratiche, "cmp");
        
        foreach ($pratiche as $pratica) {
            echo '<div class="pratica">';
            foreach ($pratica as $key => $value)
                echo "<p id=\"$key\"><strong>$key: </strong>$value</p>";
            echo '</div>';
        }
	}else{ ?>
	    <form method="post">
    		<label>Da</label>
    		<input type="date" name="start"><br>
    		<label>A</label>
    		<input type="date" name="end"><br>
    		<input type="submit" value="Crea report">
    	</form>
	<?php } ?>
	
</body>
</html>