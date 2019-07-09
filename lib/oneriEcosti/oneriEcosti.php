<?php 
    class OneriECosti {
        
        private static $branchCount;
        private static $costi_e_oneri;
        
        static function init() {
            OneriECosti::$branchCount = 0;
            OneriECosti::$costi_e_oneri = json_decode(json_encode(simplexml_load_file(__DIR__.'\costiBase.xml')), true);
        }
        /**
         * 
         * @param string $intervento Descrizione
         * @param DateTime $data Data
         * @param string $densita_fondiaria
         * @param string $zona
         * @param string $tipo_intervento Su che tipo di edificio è fatto
         * @param string $tipo_edificio
         * @param string $caratteristiche_intervento Se applicabile a riduzioni
         * @param string $caratteristiche_edificio
         * @param float $superficie_scoperta
         * @param array $superfici_alloggi Valori delle superfici degli alloggi
         * @param float $superficie_non_residenziabile
         * @param int $incremento Codice da Tab.4
         * @param int $modo 1 Nessun contributo, 2 Stima analitica, 3 Calcolo tabellare 
         * 
         * @return array Array contenente le chiavi CC, OU1, OU2
         */
        public static function calcola($descrizione_intervento, $data, $destinazione_uso, $zona) {/*$densita_fondiaria, $zona,
            $tipo_intervento, $tipo_edificio, $caratteristiche_intervento, 
            $caratteristiche_edificio, $superficie_scoperta, $superfici_alloggi,
            $superficie_non_residenziabile, $incremento, $modo = 3) {*/
            
            //print_r(COSTI_BASE['OU'][$tipo_intervento][$caratteristiche_intervento][substr($zona, 0, 1)]['Dens']);
        
            //OU1, OU2 
            
            
            
            
        }
        
        public static function generaQuestionarioOU() {
            OneriECosti::createSelect(OneriECosti::$costi_e_oneri['OU']);
        }
        
        public static function generaQuestionarioCC() {
            OneriECosti::createSelect(OneriECosti::$costi_e_oneri['CC']);
        }
        
        private static function createSelect($xml, $loopCount = 0, $branch = NULL) {
            
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
                    OneriECosti::createSelect($option, $loopCount+1, $branch);
                                    
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