<html >
<head>
	<meta charset="utf-8"/>
	<link rel="stylesheet" href="style.css">
</head>
<body>
<?php
    include_once '../controls.php';
    $controls = new Controls();
    
    /*print_r($_POST);
    print_r($_GET);
    print_r($_REQUEST);*/
    
    if(!$controls->logged()){
        header('Location: ../index.php?err=Utente non loggato');
        exit();
    }
    
    $sql = '';
    $id = '';
    $type = '';
    
    if($controls->check(['persona'], $_REQUEST)){
        
        $sql = 'SELECT Cognome, Nome, Codice_fiscale, Indirizzo, Citta, Provincia, Note
                    FROM intestatari_persone
                    WHERE ID = ?';
        $id = $_REQUEST['persona'];
        $type = 'Intestatario persona';
        
    }else if($controls->check(['societa'], $_REQUEST)){
        
        $sql = 'SELECT Intestazione, Partita_iva, Indirizzo, Citta, Provincia, Note
                    FROM intestatari_societa
                    WHERE ID = ?';
        $id = $_REQUEST['societa'];
        $type = 'Intestatario società';
        
    }else if($controls->check(['tecnico'], $_REQUEST)){
        
        $sql = 'SELECT Cognome, Nome, Codice_fiscale, Partita_iva, Albo, Numero_ordine, Provncia_albo, Indirizzo, Citta, Provincia, Note
                    FROM tecnici
                    WHERE ID = ?';
        $id = $_REQUEST['tecnico'];
        $type = 'Tecnico';
        
    }else if($controls->check(['impresa'], $_REQUEST)){
        
        $sql = 'SELECT Intestazione, Codice_fiscale, Partita_iva, Note
                    FROM imprese
                    WHERE ID = ?';
        $id = $_REQUEST['impresa'];
        $type = 'Impresa';
        
    }
    
    if(empty($sql))
        exit();
    
    if($controls->check(['m'], $_REQUEST)&&$_REQUEST['m'] == 2){
        $err = '';
        foreach ($_REQUEST as $key => $value) 
            if(empty($value))
                $_REQUEST[$key] = NULL;
        
        switch ($type) {
            case 'Intestatario persona':
                $res = $controls->db->dml(
                    'UPDATE intestatari_persone
                    	SET 	Cognome = :c,
                        			Nome = :n,
                        			Codice_fiscale = :cf,
                        			Indirizzo = :ind,
                        			Citta = :citta,
                        			Provincia = :pr,
                        			Note = :note
                    	WHERE ID = :id',
                    [':c' => $_REQUEST['Cognome'],
                    ':n' => $_REQUEST['Nome'],
                    ':cf' => $_REQUEST['Codice_fiscale'],
                    ':ind' => $_REQUEST['Indirizzo'],
                    ':citta' => $_REQUEST['Citta'],
                    ':pr' => $_REQUEST['Provincia'],
                    ':note' => $_REQUEST['Note'],
                    ':id' => $_REQUEST['persona']]);
                    if($res->errorInfo()[0] != 0)
                        $err = $res->errorInfo()[2];
                break;
                
            case 'Intestatario società':
                $res = $controls->db->dml(
                'UPDATE intestatari_societa
                    	SET 	Intestazione = :int,
                        			Partita_iva = :piva,
                                    Indirizzo = :ind,
                        			Citta = :citta,
                        			Provincia = :pr,
                        			Note = :note
                    	WHERE ID = :id',
                    	[':int' => $_REQUEST['Intestazione'],
                    	':piva' => $_REQUEST['Partita_iva'],
                    	':ind' => $_REQUEST['Indirizzo'],
                    	':citta' => $_REQUEST['Citta'],
                    	':pr' => $_REQUEST['Provincia'],
                    	':note' => $_REQUEST['Note'],
                    	':id' => $_REQUEST['societa']]);
                    	if($res->errorInfo()[0] != 0)
                    	    $err = $res->errorInfo()[2];
                    	    break;
                
            case 'Tecnico':
                $res = $controls->db->dml(
                    'UPDATE tecnici
                    	SET 	Cognome = :c,
                        			Nome = :n,
                        			Codice_fiscale = :cf,
                                    Partita_iva = :piva,
                                    Albo = :albo,
                                    Numero_ordine = :nord,
                                    Provncia_albo = :palbo,
                        			Indirizzo = :ind,
                        			Citta = :citta,
                        			Provincia = :pr,
                        			Note = :note
                    	WHERE ID = :id',
                    [':c' => $_REQUEST['Cognome'],
                        ':n' => $_REQUEST['Nome'],
                        ':cf' => $_REQUEST['Codice_fiscale'],
                        ':piva' => $_REQUEST['Partita_iva'],
                        ':albo' => $_REQUEST['Albo'],
                        ':nord' => $_REQUEST['Numero_ordine'],
                        ':palbo' => $_REQUEST['Provncia_albo'],
                        ':ind' => $_REQUEST['Indirizzo'],
                        ':citta' => $_REQUEST['Citta'],
                        ':pr' => $_REQUEST['Provincia'],
                        ':note' => $_REQUEST['Note'],
                        ':id' => $_REQUEST['tecnico']]);
                    if($res->errorInfo()[0] != 0)
                        $err = $res->errorInfo()[2];
                break;
                
            case 'Impresa':
                $res = $controls->db->dml(
                    'UPDATE imprese
                    	SET 	Intestazione = :int,
                        			Codice_fiscale = :cf,
                                    Partita_iva = :piva,
                        			Note = :note
                    	WHERE ID = :id',
                    [':int' => $_REQUEST['Intestazione'],
                        ':cf' => $_REQUEST['Codice_fiscale'],
                        ':piva' => $_REQUEST['Partita_iva'],
                        ':note' => $_REQUEST['Note'],
                        ':id' => $_REQUEST['impresa']]);
                    if($res->errorInfo()[0] != 0)
                        $err = $res->errorInfo()[2];
                break;
                
            default:
                ;
                break;
        }
        
        echo
        empty($err)?
            '<h1 style="color:blue; text-align:center;">Modifiche apportate con successo</h1>'
                    :
            '<h1 style="color:red; text-align:center;">Errore durante la modifica: '.$err.'</h1>';
        
        unset($_REQUEST['m']);
    }
    
    $res = $controls->db->ql($sql, [$id]);
    
    if(count($res) != 1){
        echo '<h1 style="color:red; text-align:center;">Nessun risultato</h1></body></html>';
        exit();
    }
    
    echo isset($_REQUEST['m'])?
                '<div class="form">
                    <form method="post">
                        <h1>Modifica dati'." - $type".'</h1>
                            <div class="inner-wrap">'
                :
                '<div class="view">
                        <h1>Visualizzazione dati'." - $type".'</h1>
                            <div class="inner-wrap">';
    
    $res = $res[0];
    foreach ($res as $key => $value)
        echo isset($_REQUEST['m'])?
            '<label>'.str_replace('_', ' ', $key).'</label>'.generateInputFor(str_replace(' ', '_', $key), $value)
                :
            "<p><span>".str_replace('_', ' ', $key).": </span>$value</p>";
    
    if(isset($_REQUEST['m'])){
        echo '<button type="submit" name="m" value="2">Attua modifiche</button>
                    </form>
                </div>
            </div>';
    }else{
        echo '<form method="post">
	                   <button type="submit" name="m" value="1">Modifica</button>';
        foreach ($_REQUEST as $key => $value) 
				echo "<input type=\"hidden\" name=\"$key\" value=\"$value\">";
        echo '</form>';
    }
    
    function generateInputFor($key, $val) {
        $type = 'text';
        $attrs = '';
        switch ($key) {
            case 'Codice_fiscale':
                $attrs = 'pattern = "[A-Za-z]{6}[0-9]{2}[A-Za-z][0-9]{2}[A-Za-z][0-9]{3}[A-Za-z]"';
                break;
            
            case 'Partita_iva':
                $attrs = 'pattern = "\d{11}"';
                break;
                
            case 'Provincia':
                $attrs = 'pattern = "|[A-Z]{2}"';
                break;
            
            default:
                break;
        }
        return "<input type=\"$type\" name=\"$key\" value=\"$val\"$attrs>";
    }
?>
</body>
</html>