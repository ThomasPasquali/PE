<?php 
    class OneriECosti {
        
        static function init() {
            $ob = simplexml_load_file(__DIR__.'\costiBase.xml');
            $json = json_encode($ob);
            $array = json_decode($json, true);
            define('COSTI_BASE', $array);
            //var_dump(COSTI_BASE);
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
      
    }
    
    //Initialization
    OneriECosti::init();
?>