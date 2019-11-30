<?php
    define("INI_DIR", __DIR__."/../PE_ini/");

    class Controls{

        //tells which key is used in session array to store logged user email
        private const USER_EMAIL_KEY = 'user';
        private const USER_TYPE_KEY = 'user_type';

        public $db;
        public $doc_el_root_path;

        public function __construct() {
            if(session_status() != PHP_SESSION_ACTIVE) session_start();

            $peINI = parse_ini_file(INI_DIR.'PE.ini');
            $this->doc_el_root_path = $peINI['DOC_EL_ROOT'];

            require_once 'lib/db.php';

            $this->db = new DB();
        }

        /**
         * Return array:
         * [0] boolean -> login result
         * [1] string -> message
         * @param string $user
         * @param string $pass
         * @return boolean|array
         */
        public function login($user, $pass) {
            $res = $this->db->ql('SELECT Password, Type, Active FROM utenti WHERE Email = ?', [$user]);
            if(count($res) == 1)
                if(password_verify($pass, $res[0]['Password'])){
                if($res[0]['Active'] == 1){
                        $_SESSION[Controls::USER_EMAIL_KEY] = $user;
                        $_SESSION[Controls::USER_TYPE_KEY] = $res[0]['Type'];
                        return [TRUE, ''];
                    }else
                return [FALSE, 'Account inattivo'];
                }else
            return [FALSE, 'Credenziali errate'];
           else
            return [FALSE, 'Credenziali errate'];
        }

        public function logout() {
            session_destroy();
        }

        public function logged() {
            if(isset($_SESSION[Controls::USER_EMAIL_KEY])){
                $res = $this->db->ql('SELECT Password, Type, Active FROM utenti WHERE Email = ?',
                    [$_SESSION[Controls::USER_EMAIL_KEY]]);

                if(count($res) == 1)
                    if($res[0]['Active'] == 1){
                        $_SESSION[Controls::USER_TYPE_KEY] = $res[0]['Type'];
                        return TRUE;
                    }else{
                        $this->logout();
                        header('Location: index.php?err=Account inattivo');
                        exit();
                    }
                else
                return FALSE;
            }
            return FALSE;
        }

        public function isAdmin() {
            return $_SESSION[Controls::USER_TYPE_KEY] == 'ADMIN';
        }

        /**
         *
         * @param string $user
         * @param string $pass
         * @return PDOStatement
         */
        public function registerUser($user, $pass) {
            return $this->db->dml(
                'INSERT INTO utenti (Email, Password) VALUES (?, ?)',
                [$user, password_hash($pass, PASSWORD_DEFAULT)]);
        }

        public function getLastDBErrorInfo(){
            return $this->db->lastErrorInfo;
        }

        public function check($keys, $arr){
            foreach ($keys as $key)
                if(!(isset($arr[$key]) && ($arr[$key] == 0 || !empty($arr[$key]))))
                    return false;
            return true;
        }

        public function echoCode($code) {
            if (is_array($code)) {
                echo '<pre>';
                print_r($code);
                echo '</pre>';
            }else
            echo "<pre>$code</pre>";
        }

        public function includeHTML($path) {
          echo file_get_contents($path);
        }

        /***********DB QUERIES*************/
        /**
         *
         * @param string|int $foglio
         * @param string|int $mappale
         * @return int|NULL se esiste l'ID dell'edificio altrimenti NULL
         */
        public function getEdificioID($foglio, $mappale) {
            $res = $this->db->ql('SELECT Edificio
                                                FROM fogli_mappali_edifici
                                                WHERE Foglio = ? AND Mappale = ?',
                                                [$foglio, $mappale]);
            return (count($res) === 1)?$res[0]['Edificio']:NULL;
        }

        /**
         *
         * @param string $tipo
         * @param string $anno
         * @param string $numero
         * @param string $barrato
         * @return int|NULL se esiste l'ID della pratica altrimenti NULL
         */
        public function getPraticaID($tipo, $anno, $numero, $barrato, $pe_o_tec = 'pe') {
            $res = $this->db->ql('SELECT ID
                                            FROM '.$pe_o_tec.'_pratiche
                                            WHERE TIPO = ? AND Anno = ? AND Numero = ? AND Barrato = ?',
                                            [$tipo, $anno, $numero, $barrato]);
            return (count($res) === 1)?$res[0]['ID']:NULL;
        }

        //TODO docs
        public function getDatiTecnico($id) {
            $res = $this->db->ql('SELECT *
                                            FROM tecnici
                                            WHERE ID = ?',
                                            [$id]);
            return (count($res) === 1)?$res[0]:NULL;
        }

        public function getDatiImpresa($id) {
            $res = $this->db->ql('SELECT *
                                            FROM imprese
                                            WHERE ID = ?',
                                            [$id]);
            return (count($res) === 1)?$res[0]:NULL;
        }
        
        public function getDatiPraticaTEC($id) {
        	$res = $this->db->ql('SELECT *
                                            FROM tec_pratiche_view
                                            WHERE ID = ?',
        			[$id]);
        	return (count($res) === 1)?$res[0]:NULL;
        }
        
        public function getDatiPraticaPE($id) {
        	$res = $this->db->ql('SELECT *
                                            FROM pe_pratiche_view
                                            WHERE ID = ?',
        			[$id]);
        	return (count($res) === 1)?$res[0]:NULL;
        }

        /**
         *
         * @param string $table
         * @param string $field
         * @return array La lista di valori dell'enum
         */
        public function getEnumValues($table, $field){
            $query = $this->db->query("SHOW COLUMNS FROM $table WHERE Field = '$field'");
            $type = $query->fetch(PDO::FETCH_ASSOC)['Type'];
            $matches = [];
            preg_match("/^enum\(\'(.*)\'\)$/", $type, $matches);
            $enum = explode("','", $matches[1]);
            return $enum;
        }
        
        public function getParsedTableDescription($table, $defaultColumn = 'ID'){
            $description = $this->db->ql("SELECT table_comment
                                                            FROM INFORMATION_SCHEMA.TABLES
                                                            WHERE table_schema = (SELECT DATABASE())
                                                                AND table_name = '$table'")[0]['table_comment'];
            $matches = [];
            preg_match("/INFO({.*})ENDINFO/", $description, $matches);
            return (count($matches) > 1) ? json_decode((get_magic_quotes_gpc() ? stripslashes($matches[1]) : $matches[1]), TRUE) : ['Value' => $defaultColumn, 'Description' => $defaultColumn];
        }

    }
?>
