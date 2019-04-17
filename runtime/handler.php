<?php
    include_once '..\controls.php';
    $c = new Controls();
    
    if(!$c->logged()){
        header('Location: /index.php?err=Utente non loggato');
        exit();
    }
    
    if($c->check(['action'], $_POST))
        switch ($_POST['action']) {
            case 'hint':
                switch ($_POST['type']) {
                    case 'tecnico':
                        sendTecnicoHints($_POST['search'], $c->db);
                        exit();
                        
                    default:
                        break;
                }
                break;
                
            case 'activation':
                activateUser($_POST['email'], $c->db);
                exit();
                
            case 'deactivation':
                deactivateUser($_POST['email'], $c->db);
                exit();
                
            default:
                break;
        }
    
    function sendTecnicoHints($search, $db) {
        $res = $db->ql('SELECT ID, Cognome, Nome, Codice_fiscale
                                    FROM tecnici
                                    WHERE Cognome LIKE ? OR Nome LIKE ?
                                    LIMIT 20',
                                    ["%$search%", "%$search%"]);
        header('Content-type: application/json');
        echo json_encode($res);
    }
    
    function activateUser($email, $db) {
        $res = $db->dml('UPDATE utenti SET Active = 1 WHERE Email = ?', [$email]);
        header('Content-type: text/plain');
        echo $res->errorCode() == 0?'DONE':$res->errorInfo()[2];
    }
    
    function deactivateUser($email, $db) {
        $res = $db->dml('UPDATE utenti SET Active = \'0\' WHERE Email = ?', [$email]);
        header('Content-type: text/plain');
        echo $res->errorCode() == 0?'DONE':$res->errorInfo()[2];
    }
    