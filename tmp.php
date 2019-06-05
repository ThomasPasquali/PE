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