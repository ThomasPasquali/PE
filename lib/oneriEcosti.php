<?php 
    class OneriECosti {
        
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
         * @param float $quantita
         * @param float $superficie_scoperta
         * @param array $alloggi Valori delle superfici degli alloggi
         * @param float $superficie_non_residenziabile
         * @param int $incremento Codice da Tab.4
         * @param int $modo 1 Nessun contributo, 2 Stima analitica, 3 Calcolo tabellare 
         * 
         * @return array Array contenente le chiavi CC, OU1, OU2
         */
        public static function calcola($intervento, $data, $densita_fondiaria, $zona,
            $tipo_intervento, $tipo_edificio, $caratteristiche_intervento, 
            $caratteristiche_edificio, $quantita, $superficie_scoperta, $alloggi,
            $superficie_non_residenziabile, $incremento, $modo = 3) {
            //TODO
            //OU1, OU2 
            switch ($tipo_intervento) {
                case 'A':
                $arr = ['A' => ['1336', '2005'], 'B'];
                $da_pagare_OU1 = $quantita * $tariffa_OU1_da_array;
                $da_pagare_OU2 = $quantita * $tariffa_OU2_da_array;
                break;
                
                default:
                    ;
                break;
            }
            
            
            
        }
        
    }
?>