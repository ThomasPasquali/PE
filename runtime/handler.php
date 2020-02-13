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
                    case 'get':
                        sendHints($_POST['table'], $_POST['column'], $_POST['search'], $c);
                        exit();
                    case 'tecnico':
                        sendTecnicoHints($_POST['search'], $c->db);
                        exit();

                    case 'impresa':
                        sendImpresaHints($_POST['search'], $c->db);
                        exit();

                    case 'stradario':
                        sendStradarioHints($_POST['search'], $c->db);
                        exit();

                    case 'intestatarioPersona':
                        sendIntestatarioPersonaHints($_POST['search'], $c->db);
                        exit();

                    case 'intestatarioSocieta':
                        sendIntestatarioSocietaHints($_POST['search'], $c->db);
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

            case 'getFogliMappaliEdifici':
                getFogliMappaliEdifici($_POST['edifici'], $c->db);
                exit();

            case 'getSubalterniEdifici':
                getSubalterniEdifici($_POST['edifici'], $c->db);
                exit();

            case 'getPraticaPENumberForTipoAnno':
                getPraticaNumberForTipoAnno($_POST['tipo'], $_POST['anno'], 'pe', $c->db);
                exit();
                
            case 'getPraticaTECNumberForTipoAnno':
                getPraticaNumberForTipoAnno($_POST['tipo'], $_POST['anno'], 'tec', $c->db);
                exit();
                
            case 'activatePagamenti':
            	activatePagamenti($_POST['calcolo'], $_POST['pe_o_tec'], $c->db);
            	exit();
            	
            case 'deactivatePagamenti':
            	deactivatePagamenti($_POST['calcolo'], $_POST['pe_o_tec'], $c->db);
            	exit();
            	
            case 'aggiungiPagamentoOU':
            	aggiungiPagamentoOU($_POST['importo'], $_POST['data'], $_POST['calcolo'], $_POST['pe_o_tec'], $c->db);
            	exit();
            	
            case 'aggiungiPagamentoCC':
            	aggiungiPagamentoCC($_POST['importo'], $_POST['data'], $_POST['calcolo'], $_POST['pe_o_tec'], $c->db);
            	exit();
            	
            case 'eliminaPagamentoOU':
            	eliminaPagamentoOU($_POST['pagamento'], $_POST['pe_o_tec'], $c->db);
            	exit();
            	
            case 'eliminaPagamentoCC':
            	eliminaPagamentoCC($_POST['pagamento'], $_POST['pe_o_tec'], $c->db);
            	exit();
            	

            default:
                header('Content-type: text/plain');
                echo $_POST['action'].' non implementato';
                exit();
        }

    function sendHints($table, $column, $search, $c) {
        $description = $c->getParsedTableDescription($table, $column);
        header('Content-type: application/json');
        echo  json_encode(
                        $c->db->ql("SELECT $description[Value] Value, $description[Description] Description
                                            FROM $table
                                            WHERE $description[Description] LIKE ?
                                            ORDER BY $description[Description]
                                            LIMIT 50",
                                            ["%$search%"]), TRUE);
    }
    
    function sendTecnicoHints($search, $db) {
        $res = $db->ql('SELECT ID, CONCAT_WS(\' \', Cognome, Nome, \' (\', Codice_fiscale, \')\') Description
                                FROM tecnici
                                WHERE Cognome LIKE ?
                                LIMIT 20',
                                ["$search%"]);
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

    function sendIntestatarioPersonaHints($search, $db) {
        $res = $db->ql('SELECT ID, CONCAT(Cognome, \' \', Nome, \' (\', Codice_fiscale, \')\') Description
                                FROM intestatari_persone
                                WHERE CONCAT(Cognome, \' \', Nome) LIKE ?
                                ORDER BY Cognome, Nome
                                LIMIT 20',
            ["$search%"]);
        header('Content-type: application/json');
        echo json_encode($res);
    }

    function sendIntestatarioSocietaHints($search, $db) {
        $res = $db->ql('SELECT ID, CONCAT(Intestazione, \' (\', Partita_iva, \')\') Description
                                FROM intestatari_societa
                                WHERE Intestazione LIKE ?
                                ORDER BY Intestazione
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
        if($foglio) $params[] = $foglio;
        if($mappale) $params[] = $mappale.'%';

        $where = [];
        if($foglio) $where[] = 'Foglio = ?';
        if($mappale) $where[] = 'Mappale LIKE ?';
        
        $res = $db->ql(
            'SELECT ID, Mappali, Stradario, Note
            FROM edifici_view
            WHERE ID IN(
                SELECT  Edificio
                FROM fogli_mappali_edifici '.
                'WHERE '.(count($params) > 0?implode(' AND ', $where):' FALSE').
             ') LIMIT 10',
            $params);

        header('Content-type: text/json');
        echo json_encode($res, TRUE);
    }

    function getFogliMappaliEdifici($edifici, $db){
        if(count($edifici) > 0){
            $res = $db->ql(
                'SELECT Foglio, Mappale, EX
                 FROM fogli_mappali_edifici
                 WHERE Edificio IN (?'.str_repeat(',?', count($edifici)-1).')',
                $edifici);

            header('Content-type: application/json');
            echo json_encode($res, TRUE);
        }else{
            header('Content-type: text/plain');
            echo 'FORNIRE ALMENO UN EDIFICIO';
        }
    }

    function getSubalterniEdifici($edifici, $db){
        if(count($edifici) > 0){
            $res = $db->ql(
                'SELECT Foglio, Mappale, Subalterno
                 FROM subalterni_edifici
                 WHERE Edificio IN (?'.str_repeat(',?', count($edifici)-1).')',
                $edifici);

            header('Content-type: application/json');
            echo json_encode($res, TRUE);
        }else{
            header('Content-type: text/plain');
            echo 'FORNIRE ALMENO UN EDIFICIO';
        }
    }

    function getPraticaNumberForTipoAnno($tipo, $anno, $tec_o_pe, $db){
      $res = $db->ql(
          'SELECT MAX(Numero)+1 n
           FROM '.$tec_o_pe.'_pratiche
           WHERE Tipo = ? AND Anno = ?',
          [$tipo, $anno]);

      header('Content-type: text/plain');
      echo ($res?$res[0]['n']:'');
    }
    
    function activatePagamenti($calcolo, $pe_o_tec, $db) {
    	$db->dml('UPDATE '.$pe_o_tec.'_ou_cc SET Attivo = \'S\' WHERE ID = ?', [$calcolo]);
    	if($db->lastErrorInfo[0] != 0) echo $db->lastErrorInfo[2];
    }
    
    function deactivatePagamenti($calcolo, $pe_o_tec, $db) {
    	$db->dml('UPDATE '.$pe_o_tec.'_ou_cc SET Attivo = \'N\' WHERE ID = ?', [$calcolo]);
    	if($db->lastErrorInfo[0] != 0) echo $db->lastErrorInfo[2];
    }
    
    function aggiungiPagamentoOU($importo, $data, $calcolo, $pe_o_tec, $db) {
    	$db->dml('INSERT INTO '.$pe_o_tec.'_pagamenti_ou (Ou_cc, Importo, Data) VALUES (?, ?, ?)', [$calcolo, $importo, ($data)?$data:NULL]);
    	if($db->lastErrorInfo[0] != 0) echo $db->lastErrorInfo[2];
    }
    
    function aggiungiPagamentoCC($importo, $data, $calcolo, $pe_o_tec, $db) {
    	$db->dml('INSERT INTO '.$pe_o_tec.'_pagamenti_cc (Ou_cc, Importo, Data) VALUES (?, ?, ?)', [$calcolo, $importo, ($data)?$data:NULL]);
    	if($db->lastErrorInfo[0] != 0) echo $db->lastErrorInfo[2];
    }
    
    function eliminaPagamentoOU($pagamento, $pe_o_tec, $db) {
    	$db->dml('DELETE FROM '.$pe_o_tec.'_pagamenti_ou WHERE ID = ?', [$pagamento]);
    	if($db->lastErrorInfo[0] != 0) echo $db->lastErrorInfo[2];
    }
    
    function eliminaPagamentoCC($pagamento, $pe_o_tec, $db) {
    	$db->dml('DELETE FROM '.$pe_o_tec.'_pagamenti_cc WHERE ID = ?', [$pagamento]);
    	if($db->lastErrorInfo[0] != 0) echo $db->lastErrorInfo[2];
    }
    
    
    