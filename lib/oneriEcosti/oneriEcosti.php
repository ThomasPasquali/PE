<?php 
    class OneriECosti {
        
        private static $branchCount;
        private static $costi_e_oneri;
        
        static function init() {
            OneriECosti::$branchCount = 0;
            OneriECosti::$costi_e_oneri = json_decode(json_encode(simplexml_load_file(__DIR__.'\costiBase.xml')), true);
        }
        
        public static function calcola($dati) {
            echo '<pre style="text-align:left;">';
            echo '-----------------------------------------RICHIESTA--------------------------------------------
';
            print_r($dati);
            echo '-----------------------------------------ENDRICHIESTA--------------------------------------------
';
            //OU
            $ou1 = $dati['imponibileOU'] * $dati['OU1'];
            $ou2 = $dati['imponibileOU'] * $dati['OU2'];
            $ou = json_decode($dati['formOneri'], TRUE);
            
            switch($ou['Destinazione_uso']) {
            	
            	case 'Turistica':
            	case 'Commerciale':
            	case 'Direzionale':
            		echo 'CC: '.(205.04992 * ($dati['sn'] + ($dati['sa'] * 0.6)));
            		$cc = (205.04992 * ($dati['sn'] + ($dati['sa'] * 0.6))) * 0.03;
            		break;
            		
            	case 'Residenza':
	                //CC Tab 1
            		$superficiAlloggi = explode(',', $dati['alloggi']);
            		sort($superficiAlloggi);
            		$rangesSuperfici = [];
            		$i = 0;
            		$ranges = [95, 110, 130, 160];
            		$coeffTab1 = [0, 5, 15, 30, 50];
            		$su = 0;
            		foreach ($superficiAlloggi as $sup) {
            			while(isset($ranges[$i]) && $sup > $ranges[$i]) $i++;
            			
            			if(!isset($rangesSuperfici[$i]))
            				$rangesSuperfici[$i] = 0;
            				
            				$rangesSuperfici[$i] = $rangesSuperfici[$i] + $sup;
            				
            				$su = $su + $sup;
            		}
	                $i = 0;
	                $ccTab1 = 0;
	                foreach ($rangesSuperfici as $sup)
	                    $ccTab1 = $ccTab1 + ($coeffTab1[$i++] * ($sup / $su));
	                
	                echo "CC Tab.1: $ccTab1
";
	                    
	                //CC Tab 3
	                $ccTab3 = $dati['snr'] / $su * 100;
	                $ccTab3 =   ($ccTab3 <= 50) ? 0 :
	                                    ($ccTab3 > 50 && $ccTab3 <= 75) ? 10 :
	                                    ($ccTab3 > 75 && $ccTab3 <= 100) ? 20 : 30;
	                
	                echo "CC Tab.3: $ccTab3
";
	                
	                //CC Tab 4
	                $ccTab4 = 0;
	                foreach ($dati as $key => $value) 
	                    if(substr($key, 0, strlen('aumento')) == 'aumento')
	                        $ccTab4 = $ccTab4 + 10;
	                    
	                echo "CC Tab.4: $ccTab4
";
	            
	                $ccTotIncr = $ccTab1 + $ccTab3 + $ccTab4;
	                $maggiorazione =   ($ccTotIncr <= 5) ? 1 :
	                                                ($ccTotIncr > 5 && $ccTotIncr <= 10) ? 1.05 :
	                                                ($ccTotIncr > 10 && $ccTotIncr <= 15) ? 1.1 :
	                                                ($ccTotIncr > 15 && $ccTotIncr <= 20) ? 1.15 :
	                                                ($ccTotIncr > 20 && $ccTotIncr <= 25) ? 1.2 :
	                                                ($ccTotIncr > 25 && $ccTotIncr <= 30) ? 1.25 :
	                                                ($ccTotIncr > 30 && $ccTotIncr <= 35) ? 1.3 :
	                                                ($ccTotIncr > 35 && $ccTotIncr <= 40) ? 1.35 :
	                                                ($ccTotIncr > 40 && $ccTotIncr <= 45) ? 1.4 :
	                                                ($ccTotIncr > 45 && $ccTotIncr <= 50) ? 1.45 : 1.5;
	                
		                $cc = (205.04992 * $maggiorazione) * ($su + ($dati['snr'] * 0.6));
		                
		                echo "CC:  $cc
";
		                
		                $percTassaCC = 0;
		                if($ou['Tipo_di_intervento'] == 'Nuova_costruzione') {
		                    switch($dati['Caratteristiche_edificio']){
		                        case 'Lusso': $percTassaCC = $percTassaCC + 0.04; break;
		                        case 'Medie': $percTassaCC = $percTassaCC + 0.025; break;
		                        case 'Economiche': $percTassaCC = $percTassaCC + 0.01; break;
		                    }
		                    switch($dati['Tipologia_edificio']){
		                        case 'A_blocco_con_piu_di_due_alloggi': $percTassaCC = $percTassaCC + 0.02; break;
		                        case 'A_schiera_con_piu_di_due_alloggi': $percTassaCC = $percTassaCC + 0.02; break;
		                        case 'Fino_a_due_alloggi': $percTassaCC = $percTassaCC + 0.03; break;
		                    }
		                    switch($dati['Zona']){
		                        case 'A': $percTassaCC = $percTassaCC + 0.02; break;
		                        case 'B': $percTassaCC = $percTassaCC + 0.02; break;
		                        case 'C': $percTassaCC = $percTassaCC + 0.025; break;
		                        default: $percTassaCC = $percTassaCC + 0.04; break;
		                    }
		                    
		                    $cc = $cc * $percTassaCC;
		                    echo "Perc. Tassa cc:  $percTassaCC
";
		                }else
		               //$cc = (205.04992 * ($su + ($dati['snr'] * 0.6))) * 0.03;
		               		$cc = $cc * 0.03;
	                	break;
	               		
	               	default:
	               		$cc = 0;
	               		break;
            }
            
            
            
            $cols = [];
            $cols['OU1'] = $ou1;
            $cols['OU2'] = $ou2;
            $cols['CC'] = $cc;
            //foreach ($ou as $key => $value) $cols[$key] = $value;
            //TODO
            //foreach ($dati as $key => $value) $cols[$key] = $value;
            
            echo '-----------------------------------------COLONNE--------------------------------------------
';
            print_r($cols);
            echo '-----------------------------------------ENDCOLONNE--------------------------------------------
';
            
            $sql = "INSERT INTO tec_ou_cc ('".implode("', '", array_keys($cols))."')
                        VALUES (?".str_repeat(', ?', (count($cols)-1)).')';
            //echo $sql;
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
            foreach ($array as $key => $value)
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