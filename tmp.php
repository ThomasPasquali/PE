<?php 
    include_once 'controls.php';
    $c = new Controls();
    
    $xml = json_decode(json_encode(simplexml_load_file('lib/oneriEcosti/costiBase.xml')), true);
    $cc = $xml['CC'];
    $ou = $xml['OU'];
    
    createSelect($ou);
    
    function createSelect($xml, $loopCount = 0) {
        
        foreach ($xml as $key => $items) {
            
            if($key != '@attributes' && $key != 'comment'){
                
                echo "<div class=\"level$loopCount".($loopCount>0?' hidden':'')."\" ".(isset($xml['@attributes']['value'])?' id="'.$xml['@attributes']['value'].'"':'').'>';
                
                echo "<h2>$key</h2>";
                
                echo '<select onchange="showOnlyThatDiv(\'level'.($loopCount+1).'\', this.options[this.selectedIndex].getAttribute(\'value\'));">';
                echo '<option></option>';
                foreach ($items as $option)
                    echo '<option value="'.$option['@attributes']['value'].'">'.$option['@attributes']['value'].'</option>';
                    echo '</select>';
                    
                    foreach ($items as $option)
                        if(has_all_keys($option, ['OU1', 'OU2']))
                            echo '<button type="button" id="'.$option['@attributes']['value'].'" class="level'.($loopCount+1).' hidden" onclick="setOU1OU2('.$option['OU1'].', '.$option['OU2'].');">Conferma oneri</button>';
                            else
                                createSelect($option, $loopCount+1);
                                
                                echo '</div>';
                                
            }
            
        }
        
    }
    
    function has_more_keys($array, $exclusions) {
        foreach ($array as $key => $value)
            if(!in_array($key, $exclusions))
                return true;
                return false;
    }
    
    function has_all_keys($array, $keys) {
        foreach ($keys as $key)
            if(!isset($array[$key]))
                return false;
                return true;
    }
    /*$res = $c->db->ql('SELECT Pratica p, Superfici_alloggi s FROM oneri');
    
    foreach ($res as $tmp) {
    $superfici = preg_split("/ +/", $tmp['s']);
    
    foreach ($superfici as $superficie) 
        if((int)$superficie != 0)
            $c->db->dml("INSERT INTO oneri_superfici_alloggi (Pratica, Superficie) VALUES ('$tmp[p]',$superficie)") ;
    }
    var_dump($c->getLastDBErrorInfo());*
    
    include_once 'lib/oneriEcosti/oneriEcosti.php';
    $intervento = 'Intervento';
    $data = '2019-06-22';
    $densita_fondiaria = 1.5;
    $zona = 'A';
    $tipo_intervento = 'A';
    $tipo_edificio = 'A';
    $caratteristiche_intervento = 'A';
    $caratteristiche_edificio = 'A';
    $superficie_scoperta = 0;
    $superfici_alloggi = 100;
    $superficie_non_residenziabile = 0;
    $incremento = 0;/
    //OneriECosti::calcola($intervento, $data, $densita_fondiaria, $zona, $tipo_intervento, $tipo_edificio, $caratteristiche_intervento, $caratteristiche_edificio, $superficie_scoperta, $superfici_alloggi, $superficie_non_residenziabile, $incremento);
    //OneriECosti::calcola('Intervento', '2019-06-06', 1.5, 'B', 'A', 'A', 'A', 'A', 0, [50, 10.5], 0, 0);
    
    /*$res = $c->db->ql('SELECT ID FROM tec_pratiche');
    
    foreach ($res as $pratica) {
        $id = $pratica['ID'];
        
        
        $numero = (int)substr($id, 5, 3);
        
        echo $c->db->dml("update tec_pratiche set numero = $numero WHERE ID = '$id'")->errorInfo()[2];
        
        if(substr($id, 1, 1) == 2){
            $anno = substr($id, 1, 4);
        }else{
            $anno = '19'.substr($id, 1, 2);
        }
        
        $tipo = substr($id, 0, 1);
        switch ($tipo) {
            case 'A':
                $tipo = 'Autorizzazione';
                break;
            
            case 'C':
                $tipo = 'Concessione';
                break;
                
            case 'I':
                $tipo = 'Opera_interna';
                break;
            
            case 'S':
                $tipo = 'Sanatoria';
                break;
                
            case 'P':
                $tipo = 'Permesso';
                break;
                
            case 'K':
                $tipo = 'Condono';
                break;
                
            default:
                echo "ORCOOOOOO $tipo   ";
            break;
        }
        
    }*/
    
    