<?php 
    include_once '../controls.php';
    $c = new Controls();
    
    if(!$c->logged()){
        header('Location: ../index.php?err=Utente non loggato');
        exit();
    }
?>
<html>
<head>
	<title>Inserimento CC e/o OU</title>
    <script src="../lib/jquery-3.3.1.min.js"></script>
    <link rel="stylesheet" type="text/css" href="../css/form.css">
</head>
<body>
	
	<div id="selezione-pratica">
		<div class="form">
            <h1>Inserimento CC e/o OU<span id="info-pratica"></span></h1>
            <div class="section">Selezione pratica</div>
            <div class="inner-wrap">
              <form id="ricerca-pratica">
                <input type="hidden" name="action" value="searchPratica">
                <select name="tipo">
                <?php
                $types = $c->getEnumValues('tec_pratiche', 'Tipo', $c->db);
                foreach ($types as $type) echo "<option value=\"$type\">$type</option>";
                ?>
              </select>
                <input name="anno" type="number" placeholder="Anno..." autofocus>
                <input name="numero" type="number" placeholder="Numero...">
              </form>
              <h3 class="centered">Risultati ricerca</h3>
              <div id="risultati-ricerca-pratica" class="box-risultati"></div>
    
    
            </div>
          </div>
	</div>

</body>
</html>