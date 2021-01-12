<?php
    include_once '../lib/db.php';

    define('FESTE_ITERATIVE', ['01-01','01-06','04-25','05-01','06-02','06-24','08-15','11-01','12-08','12-25','12-26']);
    define('FESTE_STATICHE', ['2020-04-13', '2021-04-05', '2022-04-18']);
    define('DIURNO_START', 6);
    define('DIURNO_END', 22);
    define('WORKCODES_BL', [0,2]);
    define('CSV_SEP', ';');
    define('LETTERE_SETTIMANA', ['Lu','Ma','Me','Gi','Ve','Sa','Do']);
    define('WARNING_ASSENZE_TIMBRATURE', ['Ferie', 'Donazione Sangue', 'Malattia', 'Infortunio', 'Aspettativa', 'Congedo', 'Maternità /Paternità']);

    class Lib {
        private $ini, $db;

        public $username, $user, $da, $a;

        public $orarioSettimanale, $orariSettimanali = [];
        public $workcodes = [];
        public $assenze = [];
        public $timbrature = [];

        public $days = [];

        public $tot, $totAssenze, $totTeorico;
        public $totSecondsDiurniFestivi, $totSecondsNotturniFestivi, $totSecondsDiurniFeriali, $totSecondsNotturniFeriali;

        public $giorniSettimana = array('Lun','Mar','Mer','Gio','Ven','Sab','Dom');
        public $giorniLavorati;

        public $assenzeParzialiStats = [];
        public $assenzeIntereStats = [];

        public $reportReady = false;
        
        public function __construct($user, $password, $logout) {
            $this->ini = parse_ini_file("../../PE_ini/DB.ini", TRUE)['timbrature'];
            $this->db = new DB([
                'db'=>$this->ini['db'], 
                'host'=>$this->ini['host'],
                'dbName'=>$this->ini['dbName'],
                'port'=>$this->ini['port'],
                'user'=>$this->ini['user'],
                'pass'=>$this->ini['pass']
            ]);
            $this->username = $user;

            //if(!$logged) $this->exitWithMessage(NULL,'Formato richiesta errato');
            $this->checkNewLogin($password);
            $this->checkLogout($logout);

            if($this->validRequestForReport()) {
                $this->a = new DateTime($_REQUEST['a']);
                $this->da = new DateTime($_REQUEST['da']);

                $this->user = $this->db->ql('SELECT * FROM ts_users WHERE Username = ?', [$this->username]);
                if(count($this->user) != 1) $this->exitWithMessage('Username errato');
                $this->user = $this->user[0];

                $this->loadOrariSettimanali();
                $this->loadWorkcodes();
                $this->loadAssenze();
                $this->loadTimbrature();

                $this->checkTimbrature();

                $this->tot=$this->totAssenze=$this->totAssenze=$this->totTeorico=$this->totSecondsDiurniFeriali=$this->totSecondsDiurniFestivi=$this->totSecondsNotturniFeriali=$this->totSecondsNotturniFestivi=$this->giorniLavorati = (int)0;
                
                $this->initDays();
                $this->elaborateTimbrature();
                $this->elaborateAssenze();
                $this->countGiorniLavorati();
                $this->elaborateSaldoStraordinari();
                $this->reportReady = true;
            }
        }

        /************LOAD FUNCTIONS************/

        private function loadOrariSettimanali() {
            $orariSettimanaliDB = $this->db->ql('SELECT idTime, timeName, segments FROM ts_timetables WHERE timeName LIKE ? ORDER BY idTime', ['%'.$this->user['Username'].'%']);
            
            if(count($orariSettimanaliDB) < 1) $this->exitWithMessage('Orario settimanale non trovato');

            foreach ($orariSettimanaliDB as $o) {
                $orario = json_decode($o['segments'], TRUE)[0];
                foreach ($orario['Days'] as $day)
                    $this->orariSettimanali[$o['idTime']]['orario'][] = (int)$this->HMStoMinutes($day['ServiceChunk']['Duration']);
                $this->orariSettimanali[$o['idTime']]['nome'] = $o['timeName'].' -> ';
            }
            
            $specifiedOrario = (isset($_REQUEST['orario']) && intval($_REQUEST['orario']) > 0);
            $this->orarioSettimanale = $specifiedOrario ? 
                                            $this->orariSettimanali[$_REQUEST['orario']] :
                                            array_values($this->orariSettimanali)[0];
            define('ORARIO_SETTIMANALE', $this->orarioSettimanale);
        }

        private function loadWorkcodes() {
            $res = $this->db->ql('SELECT * FROM ts_workcodes');
           	foreach ($res as $workcode)
            	$this->workcodes[$workcode['WorkCode_Number']] = $workcode['WorkCode_Desc'];
        }

        private function loadAssenze() {
            $assenze = $this->db->ql(
                'SELECT dayStart, exWhy, exLen_days, exLen_time 
                FROM ts_schedules_ex
                WHERE   idDeptUser = :user
                    AND (dayStart + interval (exLen_days-1) day) BETWEEN :da AND :a
                ORDER BY dayStart',
                [':user' => $this->user['id'], ':da'=>$_REQUEST['da'], ':a'=>$_REQUEST['a']]);
                
            //Numero assenze per tipo
            $tmp = [];
            foreach($assenze as $assenza) {
                $day = new DateTime($assenza['dayStart']);

                //Controllo che non sia un giorno antecedente ad inizio report
                $daysDiff = $this->da->diff($day);
                if($daysDiff->format('%r') == '-') {
                    //lunghezza assenza -= (diff inizioFerie-inizioReport - festivi/nonLavorativi nel periodo + 1)
                    $assenza['exLen_days'] = intval($assenza['exLen_days'])-(intval($daysDiff->format('%d'))-$this->countFestiviOnonLavorativi($day, $this->da));
                    $day = clone $this->da;
                }

                //"Scompatto" i giorni di assenza multipli
                while($this->dateDiff($day, $this->a) >= 0 && $assenza['exLen_days'] > 0) {
                    //Se Why = Festivo lo posso sovrapporre alle festività
                    //Controllo se il giorno è lavorativo per la persona in questione
                    if($assenza['exWhy'] == 'Festivo' || !($this->isFestivo($day) || $this->orarioSettimanale['orario'][$this->dayOfWeek($day)] == 0)) {
                        $tmp[] = ['dayStart' => date_format($day, 'Y-m-d'), 'exWhy' => $assenza['exWhy'], 'exLen_time' => intval($assenza['exLen_time'])*60];
                        
                        if(!isset($assenzeIntereStats[$assenza['exWhy']]))
                            $this->assenzeIntereStats[$assenza['exWhy']] = (int)0;
                            
                        //Statistiche sui giorni "scompattati"
                        $this->assenzeIntereStats[$assenza['exWhy']]++;
                        
                        $assenza['exLen_days']--;
                    }
                    
                    $day->modify('+1 day');
                    
                }
            }
            $this->assenze = $tmp;
        }

        private function loadTimbrature() {
            $this->timbrature = $this->db->ql(
                'SELECT e.logNote AS note, r.*
                FROM ts_records r
                JOIN ts_users u ON u.idUser = r.idUser
				LEFT JOIN ts_records_extra e ON r.idLog = e.idLog
                WHERE   u.Username LIKE :u
                    AND DATE(r.logTime) BETWEEN :da AND :a
					AND r.valid = 1
                ORDER BY r.logTime',
            	[':u'=>$this->username, ':da'=>$_REQUEST['da'], ':a'=>$_REQUEST['a']]);
        }

        /************CHECK FUNCTIONS************/
        
        private function checkNewLogin($password) {
            if(is_null($this->username)&&isset($password)) {
                $res = $this->db->ql('SELECT Username u FROM ts_users WHERE Pwd = ?', [$password]);
                if(count($res) > 0) $_SESSION['user_timbrature'] = $res[0]['u'];
            }
        }
    
        private function checkLogout($logout) {
            if(!is_null($logout)) {
                unset($_SESSION['user_timbrature']);
                $this->exitWithRedirect('index.php');
            }
        }
    
        public function validRequestForReport() {
            return (isset($_REQUEST['da']) && isset($_REQUEST['a'])&&!is_null($this->username)) && 
                   ($this->username != 'admin' || ($this->username == 'admin' && isset($_REQUEST['user'])));
        }

        /**
         * Controllo effettuato su tutti i giorni del periodo
         */
        private function checkTimbrature() {
            $erroriDisparitaTimbrature = $this->db->ql(
                'SELECT DATE(r.logTime) Data, COUNT(*) n
                FROM ts_records r
                    JOIN ts_users u ON u.idUser = r.idUser
                WHERE u.Username LIKE :u
                    AND DATE(r.logTime) BETWEEN :da AND :a
                    AND r.valid = 1
                GROUP BY DATE(r.logTime)
                HAVING COUNT(*)%2 <> 0',
                [':u'=>$this->username, ':da'=>$_REQUEST['da'], ':a'=>$_REQUEST['a']]);
        
            if(count($erroriDisparitaTimbrature) > 0) {
                $a = [];
                foreach ($erroriDisparitaTimbrature as $disp)
                    $a[] = "Timbrature dispari ($disp[n]) il giorno $disp[Data]";
                $this->exitWithMessage(implode('<br>', $a));
            }

            $erroriInOutTimbrature = [];
            for ($i = 0; $i < count($this->timbrature); $i+=2)
            	if($this->timbrature[$i]['logAction'] != 0 || $this->timbrature[$i+1]['logAction'] != 1)
            		$erroriInOutTimbrature[] = new DateTime($this->timbrature[$i]['logTime']);
            	
            if(count($erroriInOutTimbrature) > 0) {
                $a = [];
            	foreach ($erroriInOutTimbrature as $err)
            		$a[] = "Entrate/Uscite non coerenti il giorno ".date_format($err, 'Y-m-d');
                $this->exitWithMessage(implode('<br>', $a));
            }
        }

        /**************ELABORATION*************/
        private function initDays() {
            $da_cpy = clone $this->da;
            while(date_format($da_cpy, "Y-m-d") <= date_format($this->a, "Y-m-d")) {
                $this->days[date_format($da_cpy, "d/m/Y")] = 
                    ['timbrature' => [],
                    'workcodes' => [],
                    'totSeconds' => (int)0,
                    'teorico' => (int)0,
                    'totSecondsAssenza' => (int)0,
                    'giustificazione' => '',
                    'totSecondsDiurniFeriali' => (int)0,
                    'totSecondsNotturniFeriali' => (int)0,
                    'totSecondsDiurniFestivi' => (int)0,
                   	'totSecondsNotturniFestivi' => (int)0,
                    'totSecondsSDiurniFeriali' => (int)0];

                if(!$this->isFestivo($da_cpy))
                    $this->totTeorico += $this->orarioSettimanale['orario'][$this->dayOfWeek($da_cpy)]*60;

                $da_cpy->modify('+1 day');
            }
        }

        private function elaborateTimbrature() {
            for ($i = 0; $i < count($this->timbrature); $i+=2) {

            	$in = new DateTime($this->timbrature[$i]['logTime']);
                $out = new DateTime($this->timbrature[$i+1]['logTime']);
                $diff = $this->dateDiff($in, $out);
                $date = date_format($in, "d/m/Y");
                $this->days[$date]['teorico'] = ($this->isFestivo($in)?0:($this->orarioSettimanale['orario'][$this->dayOfWeek($in)]*60));
                
                //Controlli
                if(date_format($in, "Y-m-d") != date_format($out, "Y-m-d")) echo '<br>Entrata ed uscita su giorni diversi<br>';
                
                //Statistiche sulle ore lavorate
                $hIn = intval($in->format('H'));
                $hOut = intval($out->format('H'));
				$secondsDiurniFestivi = 0;
                $secondsNotturniFestivi = 0;
				$secondsDiurniFeriali = 0;
                $secondsNotturniFeriali = 0;
                
                //Assenza giustificata parziale
                if(!in_array($this->timbrature[$i]['logCode'], WORKCODES_BL) && $this->timbrature[$i]['logCode'] == $this->timbrature[($i+1)]['logCode']) {
                	$this->days[$date]['4'][] = [
                			'in' => $in,
                			'out' => $out,
                			'diff' => $diff,
                			'workcode' => $this->timbrature[$i]['logCode'],
                			'note_in' => $this->timbrature[$i]['note'],
                			'note_out' => $this->timbrature[$i+1]['note']
                	];
                	if(!isset($assenzeParzialiStats[$this->workcodes[$this->timbrature[$i]['logCode']]])) 
                		$assenzeParzialiStats[$this->workcodes[$this->timbrature[$i]['logCode']]] = (int)0;
                	$assenzeParzialiStats[$this->workcodes[$this->timbrature[$i]['logCode']]] += $diff;
                		
                }else {
                	//Tutto il resto
	                //Se festivo
	                if($this->isFestivo($in)) {
	                    //Se la prima timbratura è "diurna"
	                    if($hIn >= DIURNO_START && $hIn < DIURNO_END){
	                        //Se la prima e la seconda timbratura sono "diurne"
	                        if($hOut >= DIURNO_START && $hOut < DIURNO_END){
	                            $secondsDiurniFestivi += $this->dateDiff($in, $out);
	                        //Se la prima timbratura è "diurna" e la seconda "notturna"
	                        }else {
	                            //Aggingo l'intervallo in -> DIURNO END
	                            $secondsDiurniFestivi += $this->dateDiff($in, new DateTime($in->format('Y-m-d '.DIURNO_END.':0:0')));
	                            //Aggingo l'intervallo DIURNO END -> out
	                            $secondsNotturniFestivi += $this->dateDiff(new DateTime($out->format('Y-m-d '.DIURNO_END.':0:0')), $out);
	                        }
	                    //Se la prima timbratura è "notturna"
	                    }else {
	                        //Se la prima timbratura è "notturna" e la seconda "diurna"
	                        if($hOut >= DIURNO_START && $hOut < DIURNO_END){
	                            //Aggingo l'intervallo in -> DIURNO START
	                            $secondsNotturniFestivi += $this->dateDiff($in, new DateTime($in->format('Y-m-d '.DIURNO_START.':0:0')));
	                            //Aggingo l'intervallo DIURNO END -> out
	                            $secondsDiurniFestivi += $this->dateDiff(new DateTime($out->format('Y-m-d '.DIURNO_START.':0:0')), $out);
	                        //Se la prima e la seconda timbratura sono "notturne"
	                        }else {
	                            $secondsNotturniFestivi += $this->dateDiff($in, $out);
	                        }
	                    }
	                //Se feriale    
	                }else {
	                    //Se la prima timbratura è "diurna"
	                    if($hIn >= DIURNO_START && $hIn < DIURNO_END){
	                        //Se la prima e la seconda timbratura sono "diurne"
	                        if($hOut >= DIURNO_START && $hOut < DIURNO_END){
	                            $secondsDiurniFeriali += $this->dateDiff($in, $out);
	                        //Se la prima timbratura è "diurna" e la seconda "notturna"
	                        }else {
	                            //Aggingo l'intervallo in -> DIURNO END
	                            $secondsDiurniFeriali += $this->dateDiff($in, new DateTime($in->format('Y-m-d '.DIURNO_END.':0:0')));
	                            //Aggingo l'intervallo DIURNO END -> out
	                            $secondsNotturniFeriali += $this->dateDiff(new DateTime($out->format('Y-m-d '.DIURNO_END.':0:0')), $out);
	                        }
	                    //Se la prima timbratura è "notturna"
	                    }else {
	                        //Se la prima timbratura è "notturna" e la seconda "diurna"
	                        if($hOut >= DIURNO_START && $hOut < DIURNO_END){
	                            //Aggingo l'intervallo in -> DIURNO START
	                            $secondsNotturniFeriali += $this->dateDiff($in, new DateTime($in->format('Y-m-d '.DIURNO_START.':0:0')));
	                            //Aggingo l'intervallo DIURNO END -> out
	                            $secondsDiurniFeriali += $this->dateDiff(new DateTime($out->format('Y-m-d '.DIURNO_START.':0:0')), $out);
	                        //Se la prima e la seconda timbratura sono "notturne"
	                        }else {
	                            $secondsNotturniFeriali += $this->dateDiff($in, $out);
	                        }
	                    }
	                }
	                $this->totSecondsDiurniFestivi += $secondsDiurniFestivi;
	                $this->totSecondsNotturniFestivi += $secondsNotturniFestivi;
	                $this->totSecondsDiurniFeriali += $secondsDiurniFeriali;
	                $this->totSecondsNotturniFeriali += $secondsNotturniFeriali;
	                
	                //Inserimento giornata
	                $this->days[$date]['timbrature'][] = ['in' => $in, 'out' => $out, 'note_in' => $this->timbrature[$i]['note'], 'note_out' => $this->timbrature[$i+1]['note']];
	                
		           	$this->days[$date]['totSeconds'] += $diff;
	                
	                $this->days[$date]['totSecondsDiurniFeriali'] += $secondsDiurniFeriali;
	                $this->days[$date]['totSecondsNotturniFeriali'] += $secondsNotturniFeriali;
	                $this->days[$date]['totSecondsDiurniFestivi'] += $secondsDiurniFestivi;
	                $this->days[$date]['totSecondsNotturniFestivi'] += $secondsNotturniFestivi;
	                
	                //Stats globali
               		$this->tot += $diff;
                }       
            }
        }

        private function elaborateAssenze() {
            foreach ($this->assenze as $assenza) {
                $dataAssenza = new DateTime($assenza['dayStart']);
                $date = date_format($dataAssenza, "d/m/Y");
                //Errore in caso di timbrature in giorni di assenze note
                if(count($this->days[$date]['timbrature']) > 0 && in_array($assenza['exWhy'], WARNING_ASSENZE_TIMBRATURE))
                    $this->days[$date]['giustificazione'] .= '<span style="color:red">ATTENZIONE: timbrature effettuate<br>in giorno in cui &egrave; prevista assenza.</span><br>';
                //Il Why = Festivo si sovrappone alle festivita'
                if(!($this->isFestivo($dataAssenza) && $assenza['exWhy'] == 'Festivo')) {
                    $secAssenza = $assenza['exLen_time'] > 0 ? $assenza['exLen_time'] : $this->orarioSettimanale['orario'][$this->dayOfWeek($dataAssenza)]*60;
                    $this->days[$date]['totSecondsAssenza'] += $secAssenza;
                    $this->days[$date]['totSecondsDiurniFeriali'] += $secAssenza;
                    $this->totAssenze += $secAssenza;
                }
                $this->days[date_format($dataAssenza, "d/m/Y")]['giustificazione'] .= $assenza['exWhy'];
            }
        }

        private function countGiorniLavorati() {
            foreach($this->days as $day)
            	if(count($day['timbrature']) > 0)
            		$this->giorniLavorati++;
        }

        private function elaborateSaldoStraordinari() {
            $totSecondsSDiurniFeriali = (int)0;
            foreach (array_keys($this->days) as $date)
            	if(count($this->days[$date]['timbrature']) > 0) {
	            	$day = date_create_from_format ('d/m/Y', $date);
	            	$teorico = ($this->isFestivo($day)?0:($this->orarioSettimanale['orario'][$this->dayOfWeek($day)]*60));
	            	$secondsSDiurniFeriali = $this->days[$date]['totSecondsDiurniFeriali'] - $teorico;
	            	$totSecondsSDiurniFeriali += $secondsSDiurniFeriali;
	            	$this->days[$date]['totSecondsSDiurniFeriali']  = $secondsSDiurniFeriali;
	            }
        }

        /********UTILITY FUNCTIONS************/

        public function getUsers() {
            return $this->db->ql('SELECT DISTINCT Username FROM ts_users WHERE Username <> \'admin\' ORDER BY Username');
        }

        public function exitWithRedirect(String $redirectLocation) {
            header('Location: '.$redirectLocation);
            exit();
        }

        public function exitWithMessage(String $message = NULL) {
            echo "<h1>$message</h1>";
            exit();
        }

        function HMStoMinutes($hms){
            $hms = explode(':', $hms);
            return ($hms[0]*60) + ($hms[1]) + ($hms[2]/60);
        }
    
        function secondsToHMS($seconds) {
            $seconds = round($seconds);
            $s = abs((int)($seconds/ 60 % 60));
            return (int)($seconds/ 3600).':'.(strlen($s) < 2?'0'.$s:$s);//.(int)($seconds % 60).'s';
        }
    
        /**
         * 0 = Mon
         * .....
         * 6 = Sun
         */
        function dayOfWeek(DateTime $date) {
            return ((int)date('N', strtotime(date_format($date, "Y-m-d")))-1);
        }
    
        /**
         * @return int Differenza in secondi
         */
        function dateDiff(DateTime $from, DateTime $to) {
            return ($this->getTimeUnixTruncatedToMinute($to) - $this->getTimeUnixTruncatedToMinute($from));
        }
        
        function getTimeUnixTruncatedToMinute(DateTime $date) {
            return ($date->getTimestamp() - ($date->getTimestamp()%60));
        }
    
        /**
         * @return bool
         */
        function isFestivo(DateTime $day) {
            return ((in_array($day->format('m-d'), FESTE_ITERATIVE)) || (in_array($day->format('Y-m-d'), FESTE_STATICHE)));
        }
    
        /**
         * Per quanto riguarda la persona in questione
         * @return bool
         */
        function isNonLavorativo(DateTime $day) {
            return (ORARIO_SETTIMANALE['orario'][$this->dayOfWeek($day)] == 0);
        }
    
        /**
         * @return int
         */
        function countFestiviOnonLavorativi(DateTime $from, DateTime $to) {
            $count = 0;
            $from = clone $from;
            while(dateDiff($from, $to) >= 0) {
                //echo $from->format('Y-m-d').(isFestivo($from) || isNonLavorativo($from)).'<br>';
                if($this->isFestivo($from) || $this->isNonLavorativo($from)) $count++;
                $from->modify('+1 day');
            }
            return $count;
        }
    }
    
    
    

    