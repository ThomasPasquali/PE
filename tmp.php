<?php 
    include_once 'controls.php';
    $c = new Controls();
    
    /*$res = $c->db->ql('SELECT Pratica p, Superfici_alloggi s FROM oneri');
    
    foreach ($res as $tmp) {
    $superfici = preg_split("/ +/", $tmp['s']);
    
    foreach ($superfici as $superficie) 
        if((int)$superficie != 0)
            $c->db->dml("INSERT INTO oneri_superfici_alloggi (Pratica, Superficie) VALUES ('$tmp[p]',$superficie)") ;
    }
    var_dump($c->getLastDBErrorInfo());*/
    
    include_once 'lib/oneriEcosti/oneriEcosti.php';
    //OneriECosti::calcola($intervento, $data, $densita_fondiaria, $zona, $tipo_intervento, $tipo_edificio, $caratteristiche_intervento, $caratteristiche_edificio, $superficie_scoperta, $superfici_alloggi, $superficie_non_residenziabile, $incremento)
    OneriECosti::calcola('Intervento', '2019-06-06', 1.5, 'B', 'A', 'A', 'A', 'A', 0, [50, 10.5], 0, 0);
    