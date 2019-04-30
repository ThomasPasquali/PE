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

                    case 'impresa':
                        sendImpresaHints($_POST['search'], $c->db);
                        exit();

                    case 'stradario':
                        sendStradarioHints($_POST['search'], $c->db);
                        exit();

                    default:
                        break;
                }
                break;

            case 'accountActivation':
                activateUser($_POST['email'], $c->db);
                exit();

            case 'accountDeactivation':
                deactivateUser($_POST['email'], $c->db);
                exit();

            case 'userPermissionsChange':
                changeUserPermissions($_POST['email'], $_POST['type'], $c->db);
                exit();

            case 'accountDelete':
                deleteUser($_POST['email'], $c->db);
                exit();

            case 'checkMappale':
              checkIfMappaleIsFree($_POST['foglio'], $_POST['mappale'], $c->db);
              exit();

            default:
                break;
        }

    function sendTecnicoHints($search, $db) {
        $res = $db->ql('SELECT ID, CONCAT_WS(\' \', Cognome, Nome, \' (\', Codice_fiscale, \')\') Description
                                FROM tecnici
                                WHERE Cognome LIKE ? OR Nome LIKE ?
                                LIMIT 20',
                                ["%$search%", "%$search%"]);
        header('Content-type: application/json');
        echo  json_encode($res);
    }

    function sendImpresaHints($search, $db) {
        $res = $db->ql('SELECT ID, CONCAT_WS(\' \', Intestazione, \' (\', Codice_fiscale, \'-\',Partita_iva, \')\') Description
                                FROM imprese
                                WHERE Intestazione LIKE ?
                                LIMIT 20',
            ["%$search%"]);
        header('Content-type: application/json');
        echo json_encode($res);
    }

    function sendStradarioHints($search, $db) {
        $res = $db->ql('SELECT Identificativo_nazionale ID, Denominazione Description
                                FROM stradario
                                WHERE Denominazione LIKE ?
                                LIMIT 20',
            ["%$search%"]);
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

    function changeUserPermissions($email, $type, $db) {
        $res = $db->dml('UPDATE utenti SET Type = ? WHERE Email = ?', [$type, $email]);
        header('Content-type: text/plain');
        echo $res->errorCode() == 0?'DONE':$res->errorInfo()[2];
    }

    function deleteUser($email, $db) {
        $res = $db->dml('DELETE FROM utenti WHERE Email = ?', [$email]);
        header('Content-type: text/plain');
        echo $res->errorCode() == 0?'DONE':$res->errorInfo()[2];
    }

    function checkIfMappaleIsFree($foglio, $mappale, $db) {
        header('Content-type: text/plain');
        if(empty($foglio)||empty($mappale)){
            echo 'NO';
            exit();
        }
        $res = $db->ql('SELECT *
                                  FROM fogli_mappali_edifici
                                  WHERE Foglio LIKE ? AND Mappale LIKE ?',
                                  ["%$foglio%", "%$mappale%"]);
        echo count($res) > 0?'NO':'OK';
    }
