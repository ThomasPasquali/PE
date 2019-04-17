<?php 
    include_once '../controls.php';
    $c = new Controls();
    
    if(!$c->logged()){
        header('Location: index.php?err=Utente non loggato');
        exit();
    }
    
    $res = $c->db->ql('SELECT ID, Mappale
                                FROM pe_pratiche');
    
    $pratiche = [];
    foreach ($res as $pra) {
        
    }
?>