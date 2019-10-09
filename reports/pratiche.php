<?php 
    include_once '../controls.php';
    $c = new Controls();
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
        $pratiche = $c->db->ql(
            'SELECT *
            FROM 
                (SELECT tecp.TIPO Tipo, tecp.`Data`, tecp.Sigla, tecp.Protocollo, 
                tecp.FogliMappali Fogli_Mappali, CONCAT(IF(tecp.Intestatari_persone IS NULL, \'\', tecp.Intestatari_persone), IF(tecp.Intestatari_societa IS NULL, \'\', tecp.Intestatari_societa)) Intestatario, 
                tecp.Intervento Oggetto
                FROM tec_pratiche_view tecp
                UNION
                SELECT pep.TIPO Tipo, pep.`Data`, pep.Sigla, pep.Protocollo, 
                pep.FogliMappali Fogli_Mappali, CONCAT(IF(pep.Intestatari_persone IS NULL, \'\', pep.Intestatari_persone), IF(pep.Intestatari_societa IS NULL, \'\', pep.Intestatari_societa)) Intestatario, 
                pep.Intervento Oggetto
                FROM pe_pratiche_view pep) a
            WHERE `Data` BETWEEN ? AND ?
            ORDER BY `Data`',
            [$_REQUEST['start'], $_REQUEST['end']]);
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