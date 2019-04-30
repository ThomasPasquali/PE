<?php
    define("INI_DIR", $_SERVER['DOCUMENT_ROOT']."/../PE_ini/");

    class Controls{

        //tells which key is used in session array to store logged user email
        private const USER_EMAIL_KEY = 'user';
        private const USER_TYPE_KEY = 'user_type';

        public $db;

        public function __construct() {
            if(session_status() != PHP_SESSION_ACTIVE) session_start();

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

    }
?>
