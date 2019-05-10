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
              checkIfMappaleIsFree($_POST['foglio'], $_POST['mappale'], $_POST['edificioToExclude'], $c->db);
              exit();

            case 'searchEdificio':
                searchEdificio($_POST['foglio'], $_POST['mappale'], $c->db);
                exit();

            case 'getMappaliEdificio':
                getMappaliEdificio($_POST['edificio'], $c->db);
                exit();

            case 'getSubalterniEdificio':
                getSubalterniEdificio($_POST['edificio'], $c->db);
                exit();

            case 'getPraticaNumberForAnno':
                getPraticaNumberForAnno($_POST['anno'], $c->db);
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

    function checkIfMappaleIsFree($foglio, $mappale, $edificioToExclude, $db) {
        header('Content-type: text/plain');
        if(empty($foglio)||empty($mappale)){
            echo 'NO';
            exit();
        }
        $params = [$foglio, $mappale];
        if(!empty($edificioToExclude)) $params[] = $edificioToExclude;
        $res = $db->ql('SELECT *
                              FROM fogli_mappali_edifici
                              WHERE Foglio = ? AND Mappale = ? '
                            .(empty($edificioToExclude)?'':'AND Edificio <> ?'),
                              $params);
        //print_r($res);
        echo count($res) > 0?'NO':'OK';
    }

    function searchEdificio($foglio, $mappale, $db) {
        $params = [];
        if(!empty($foglio)) $params[] = $foglio;
        if(!empty($mappale)) $params[] = $mappale;

        $where = [];
        if(!empty($foglio)) $where[] = 'e.Foglio = ?';
        if(!empty($mappale)) $where[] = ' fm.Mappale = ?';

        $res = $db->ql(
            'SELECT  e.ID ID, e.Foglio Foglio,
            	           GROUP_CONCAT(CONCAT(fm.Mappale, IF(fm.EX IS NULL, \'\', \'(EX)\')) ORDER BY fm.Mappale SEPARATOR \', \') Mappali,
                           s.Denominazione Stradario, e.Note Note
            FROM edifici e
            LEFT JOIN fogli_mappali_edifici fm ON fm.Edificio = e.ID
            JOIN stradario s ON s.Identificativo_nazionale = e.Stradario '.
            (count($params) > 0?' WHERE '.implode(' AND ', $where):'').
             ' GROUP BY e.ID
               LIMIT 10',
            $params);

        header('Content-type: text/json');
        echo json_encode($res, TRUE);
    }

    function getMappaliEdificio($edificio, $db){
        $res = $db->ql(
            'SELECT Mappale, EX
             FROM fogli_mappali_edifici
             WHERE Edificio = ?',
            [$edificio]);

        header('Content-type: text/json');
        echo json_encode($res, TRUE);
    }

    function getSubalterniEdificio($edificio, $db){
        $res = $db->ql(
            'SELECT Subalterno, Mappale
             FROM subalterni_edifici
             WHERE Edificio = ?',
            [$edificio]);

        header('Content-type: text/json');
        echo json_encode($res, TRUE);
    }

    function getPraticaNumberForAnno($anno, $db){
      $res = $db->ql(
          'SELECT MAX(Numero)+1 n
           FROM pe_pratiche
           WHERE Anno = ?',
          [$anno]);

      header('Content-type: text/plain');
      echo ($res?$res[0]['n']:'');
    }
