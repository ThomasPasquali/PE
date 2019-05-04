<?php
    class DB extends PDO{

        private const DB_FILE = 'DB.ini';
        public $lastErrorInfo;

        public function __construct() {
            $ini = parse_ini_file(INI_DIR.DB::DB_FILE);
            parent::__construct("$ini[db]:host=$ini[host];dbname=$ini[dbName];port=$ini[port]", $ini['user'], $ini['pass']);
        }

        public function ql($sql, $params=NULL, $fetchType = PDO::FETCH_ASSOC) {
            try {
                $this->beginTransaction();

                $stmt = $this->prepare($sql);
                $stmt->execute($params);
                $this->lastErrorInfo = $stmt->errorInfo();

                $righe_estratte = [];
                while ($riga = $stmt->fetch($fetchType))
                    $righe_estratte[] = $this->changeEncoding($riga);

                $this->commit();
                return $righe_estratte;
            }catch (PDOException $e){
                $this->rollback();
                throw new PDOException($e);
            }
        }

        /**
         *
         * @param string $sql query text
         * @param array $params parameters to bind to the query
         * @throws PDOException
         * @return PDOStatement the result statement
         */
        public function dml($sql, $params=NULL) {
            try {
                $this->beginTransaction();
                $stmt = $this->prepare($sql);
                $stmt->execute($this->changeEncoding($params));
                $this->lastErrorInfo = $stmt->errorInfo();
                $this->commit();
                return $stmt;
            }catch (PDOException $e){
                $this->rollback();
                throw new PDOException($e);
            }
        }

        public function changeEncoding($arr, $newEnc = 'UTF-8') {
            if($arr != NULL)
                foreach ($arr as $key => $value)
                    if($value != NULL)
                        $arr[$key] = iconv(mb_detect_encoding($value, mb_detect_order(), true), $newEnc, $value);
            return $arr;
        }
    }
