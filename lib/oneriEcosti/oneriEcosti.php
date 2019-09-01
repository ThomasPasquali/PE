<?php 
    class OneriECosti {
        
        private static $branchCount;
        private static $costi_e_oneri;
        
        static function init() {
            OneriECosti::$branchCount = 0;
            OneriECosti::$costi_e_oneri = json_decode(json_encode(simplexml_load_file(__DIR__.'\costiBase.xml')), true);
        }
        
        public static function calcola($dati, $db = NULL) {
            echo '<pre style="text-align:left;">';
            /*echo '-----------------------------------------RICHIESTA--------------------------------------------
';
            print_r($dati);
            echo '-----------------------------------------ENDRICHIESTA--------------------------------------------
';*/
            //OU
            $ou1 = $dati['imponibileOU'] * $dati['OU1'];
            $ou2 = $dati['imponibileOU'] * $dati['OU2'];
            $ou = json_decode($dati['formOneri'], TRUE);
            $destinazioneUso = $ou['Destinazione_uso']??NULL;
            $tipoIntervento = $ou['Tipo_di_intervento']??NULL;
            $pratica = $dati['pratica'];
            $zona = $ou['Zona'];
            $snr = $dati['snr'];
            $superficie = NULL;
            $imponibileOU = NULL;
            $superficiAlloggi = explode(',', $dati['alloggi']);
            sort($superficiAlloggi);
            
            switch($destinazioneUso) {
            	
            	case 'Turistica':
            	case 'Commerciale':
            	case 'Direzionale':
            		$snr = $dati['sa'];
            		$superficie = $dati['sn'];
            		$imponibileOU = $dati['imponibileOU'];
            		echo 'CC: <span>'.(205.04992 * ($superficie + ($snr * 0.6))).'</span>';
            		$cc = (205.04992 * ($superficie + ($snr * 0.6))) * 0.03;
            		break;
            		
            	case 'Residenza':
	                //CC Tab 1
            		$rangesSuperfici = [];
            		$i = 0;
            		$ranges = [95, 110, 130, 160];
            		$coeffTab1 = [0, 5, 15, 30, 50];
            		$superficie= 0;
            		$imponibileOU = $dati['imponibileOU'];
            		foreach ($superficiAlloggi as $sup) {
            			while(isset($ranges[$i]) && $sup > $ranges[$i]) $i++;
            			
            			if(!isset($rangesSuperfici[$i]))
            				$rangesSuperfici[$i] = 0;
            				
            				$rangesSuperfici[$i] = $rangesSuperfici[$i] + $sup;
            				
            				$superficie = $superficie+ $sup;
            		}
	                $i = 0;
	                $ccTab1 = 0;
	                foreach ($rangesSuperfici as $sup)
	                    $ccTab1 = $ccTab1 + ($coeffTab1[$i++] * ($sup / $superficie));
	                
	                echo "CC Tab.1: <span>$ccTab1</span>

SNR: <span>$dati[snr]</span>
SU: <span>$superficie</span>
";

	                //CC Tab 3
	                $ccTab3 = (int)($snr / $superficie* 100);
	                switch(true) {
	                	case $ccTab3 <= 50:
	                		$ccTab3 = 0;
	                		break;
	                	case in_array($ccTab3, range(51,75)):
	                		$ccTab3 = 10;
	                		break;
	                	case in_array($ccTab3, range(76,100)):
	                		$ccTab3 = 20;
	                		break;
	                	default:
	                		$ccTab3 = 30;
	                		break;
	                }
	                
	                echo "CC Tab.3: <span>$ccTab3</span>

";
	                
	                //CC Tab 4
	                $ccTab4 = 0;
	                foreach (array_keys($dati) as $key) 
	                    if(substr($key, 0, strlen('aumento')) == 'aumento')
	                        $ccTab4 = $ccTab4 + 10;
	                    
	                echo "CC Tab.4: <span>$ccTab4</span>

";
	            
	                $ccTotIncr = floatval($ccTab1 + $ccTab3 + $ccTab4);
	                
	                $maggiorazione = 1;
	                if($ccTotIncr > floatval(50))
	                	$maggiorazione = 1.5;
	                else
	                for ($i = floatval(5); $i <= floatval(50); $i+= floatval(5))
		                	if($ccTotIncr <= $i)
		                		break;
		                	else 
		                	$maggiorazione = $maggiorazione + 0.05;
	                
	               echo "Magg. costo base: <span>$maggiorazione</span>
";
	                
		                $cc = (205.04992 * $maggiorazione) * ($superficie + ($snr * 0.6));
		                
		                echo "CC:  <span>$cc</span>
";
		                
		                $percTassaCC = 0;
		                if($tipoIntervento == 'Nuova_costruzione') {
		                    switch($dati['Caratteristiche_edificio']){
		                        case 'Lusso': $percTassaCC = $percTassaCC + 0.04; break;
		                        case 'Medie': $percTassaCC = $percTassaCC + 0.025; break;
		                        case 'Economiche': $percTassaCC = $percTassaCC + 0.01; break;
		                    }
		                    switch($dati['Tipologia_edificio']){
		                        case 'Blocco_>_di_2_alloggi': $percTassaCC = $percTassaCC + 0.02; break;
		                        case 'Schiera_>_di_2_alloggi': $percTassaCC = $percTassaCC + 0.02; break;
		                        case 'Fino_a_2_alloggi': $percTassaCC = $percTassaCC + 0.03; break;
		                    }
		                    
		                    if(substr($zona, 0, 1) == 'A')
	                        	$percTassaCC = $percTassaCC + 0.02;
		                    else if(substr($zona, 0, 1) == 'B')
	                        	$percTassaCC = $percTassaCC + 0.02;
		                    else if(substr($zona, 0, 1) == 'C')
	                        	$percTassaCC = $percTassaCC + 0.025;
		                    else
	                    	$percTassaCC = $percTassaCC + 0.04;
		                    
		                    $cc = $cc * $percTassaCC;
		                    echo "Perc. Tassa cc:  <span>$percTassaCC</span>
";
		                }else
		               $cc = $cc * 0.03;
	                	break;
	               		
	               	default:
	               		$imponibileOU = $dati['imponibileOU'];
	               		$cc = 0;
	               		break;
            }
            
			echo "Costo di costruzione: <span>$cc</span>\r\nOneri di urbanizzazione primari: <span>$ou1</span>\r\nOneri di urbanizzazione secondari: <span>$ou2</span>";
            $cols = [];
            $cols['Pratica'] = $pratica;
            if($db) $cols['Numero_revisione'] = $db->ql('SELECT IF(MAX(Numero_revisione) IS NULL, 1, MAX(Numero_revisione)+1) AS n FROM tec_ou_cc WHERE Pratica = ?', [$pratica])[0]['n'];
            $cols['Note'] = ($dati['note']??'') ? $dati['note'] : NULL;
            $cols['Zona_omogenea'] = $zona;
            $cols['Tipo_intervento'] = $ou['Tipo_di_intervento'];
            $cols['Caratteristiche_edificio'] = ($dati['Caratteristiche_edificio']??'') ? $dati['Caratteristiche_edificio'] : NULL;
            $cols['Tipologia_edificio'] = ($dati['Tipologia_edificio']??'') ? $dati['Tipologia_edificio'] : NULL;
            $cols['Destinazione_uso'] = $destinazioneUso;
            $cols['Imprenditore'] = ($ou['Imprenditore']??'') ? $ou['Imprenditore'] : NULL;
            $cols['Opzioni_note'] = ($ou['Opzione']??'') ? $ou['Opzione'] : NULL;
            $cols['Prezzo_convenzionato'] = ($ou['Prezzo_convenzionato']??'') ? $ou['Prezzo_convenzionato'] : NULL;
            $cols['ImponibileOU'] = $imponibileOU;
            $cols['Superficie'] = $superficie;
            $cols['Superficie_non_residenziale'] = $snr;
            $cols['Incremento'] = isset($ccTab4) ? ($ccTab4 / 10) : NULL;
            $cols['CC'] = $cc;
            $cols['OU1'] = $ou1;
            $cols['OU2'] = $ou2;
            
            /*echo '-----------------------------------------COLONNE--------------------------------------------
';
            print_r($cols);
            echo '-----------------------------------------ENDCOLONNE--------------------------------------------
';
            echo '-----------------------------------------SQL--------------------------------------------
';*/
            
            $sql = "INSERT INTO tec_ou_cc (".implode(", ", array_keys($cols)).") VALUES (?".str_repeat(', ?', (count(($cols))-1)).')';
            if($db) {
            	$db->dml($sql, array_values($cols));
            	if($db->lastErrorInfo[0] != 0)
            		echo $db->lastErrorInfo[2];

	            foreach ($superficiAlloggi as $alloggio) {
		            $sql = 'INSERT INTO tec_oneri_e_cc_superfici_alloggi (Pratica, Superficie) VALUES (?, ?)';
		            $db->dml($sql, [$pratica, $alloggio]);
		            if($db->lastErrorInfo[0] != 0)
		            	echo $db->lastErrorInfo[2];
	            }
            }
            /*echo '-----------------------------------------ENDSQL--------------------------------------------
';*/
            
            echo '</pre>';
        }
        
        public static function generaQuestionarioOU() {
            OneriECosti::createSelectOU(OneriECosti::$costi_e_oneri['OU']);
        }
        
        public static function generaQuestionarioIncrementoCC() {
            OneriECosti::createSelectCC(OneriECosti::$costi_e_oneri['CC']);
        }
        
        private static function createSelectOU($xml, $loopCount = 0, $branch = NULL) {
            
            foreach ($xml as $key => $items)
                if($key != '@attributes' && $key != 'comment'){
                    
                    $branch = $branch==NULL?OneriECosti::$branchCount++:$branch;
                    
                    echo "<div class=\"branch".$branch." level$loopCount".(isset($xml['@attributes']['value'])?' '.$xml['@attributes']['value']:'').($loopCount>0?' hidden':'')."\">";
                    
                    echo '<h2>'.str_replace('_', ' ', $key).'</h2>';
                    
                    echo '<select onchange="showOnlyThatDiv(\'level'.($loopCount+1).($branch>0?' branch'.$branch:'').'\', this.options[this.selectedIndex].getAttribute(\'value\'));">';
                    echo '<option></option>';
                    foreach ($items as $option)
                        echo '<option value="'.$option['@attributes']['value'].'">'.str_replace('_', ' ', $option['@attributes']['value'].(isset($option['@attributes']['description'])?' ('.$option['@attributes']['description'].')':'')).'</option>';
                    echo '</select>';
                        
                    foreach ($items as $option)
                        if(OneriECosti::has_all_keys($option, ['OU1', 'OU2']))
                            echo '<button type="button" class="branch'.$branch.' level'.($loopCount+1).' '.$option['@attributes']['value'].' hidden" onclick="setCoefficenti('.$option['OU1'].', '.$option['OU2'].', \''.($option['UM']??'metri quadrati').'\');">Conferma oneri</button>';
                        else
                    OneriECosti::createSelectOU($option, $loopCount+1, $branch);
                                    
                    echo '</div>';
            }
            
        }
        
        private static function createSelectCC($xml, $loopCount = 0, $branch = NULL) {
            
            foreach ($xml as $key => $items)
                if($key != '@attributes' && $key != 'comment'){
                    
                    $branch = $branch==NULL?OneriECosti::$branchCount++:$branch;
                    
                    echo "<div>";
                    
                    echo '<h3>'.str_replace('_', ' ', $key).'</h3>';
                    
                    echo "<select name=\"$key\">";
                    echo '<option></option>';
                    foreach ($items as $option)
                        echo '<option value="'.$option['@attributes']['value'].'">'.str_replace('_', ' ', $option['@attributes']['value'].(isset($option['@attributes']['description'])?' ('.$option['@attributes']['description'].')':'')).'</option>';
                   echo '</select>';
                                    
                    echo '</div>';
            }
            
        }
        
        private static function has_more_keys($array, $exclusions) {
            foreach (array_keys($array) as $key)
                if(!in_array($key, $exclusions))
                    return true;
                    return false;
        }
        
        private static function has_all_keys($array, $keys) {
            foreach ($keys as $key)
                if(!isset($array[$key]))
                    return false;
                    return true;
        }
      
    }
    
    //Initialization
    OneriECosti::init();
?>