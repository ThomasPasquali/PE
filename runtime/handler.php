<?php
include_once '..\controls.php';
$c = new Controls();

if(!$c->logged()){
    header('Location: /index.php?err=Utente non loggato');
    exit();
}

if($c->check(['action'], $_POST)){
    switch ($_POST['action']) {
        case 'hint':
            switch ($_POST['type']) {
                case 'tecnico':
                    $res = $c->db->ql(
                    'SELECT ID, Cognome, Nome, Codice_fiscale
                             FROM tecnici
                             WHERE Cognome LIKE ? OR Nome LIKE ?
                              LIMIT 50',
                              ["%$_POST[search]%", "%$_POST[search]%"]);
                    header('Content-type: text/json');
                    echo json_encode($res);
                    exit();
                    break;
                    
                default:
                    ;
                    break;
            }
            break;
            
        default:
            ;
            break;
    }
}