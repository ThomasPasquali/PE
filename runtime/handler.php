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
                        sendTecnicoHints($_POST['search']);
                        exit();
                        
                    case 'activation':
                        header('Content-type: text/plain');
                        $res = $c->db->dml('UPDATE utenti SET Active = 1 WHERE Email = ?', [$_POST['email']]);
                        echo $res->errorCode() == 0?'DONE':$res->errorInfo()[2];
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
    
    function sendTecnicoHints($search) {
        $res = $c->db->ql('SELECT ID, Cognome, Nome, Codice_fiscale
                                    FROM tecnici
                                    WHERE Cognome LIKE ? OR Nome LIKE ?
                                    LIMIT 50',
                                    ["%$search%", "%$search%"]);
        header('Content-type: application/json');
        echo json_encode($res);
    }