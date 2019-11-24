<?php 
    include_once 'controls.php';
    include_once 'lib/dbTableFormGenerator.php';
    $c = new Controls();
    
    $res = $c->db->ql('select Documento_elettronico from pe_pratiche where Documento_elettronico is not null');
    foreach ($res as $path) {
    	$str = $path['Documento_elettronico'];
    	
    	if(substr($str, 0, strlen('U:ARCHIVIO EDILIZIA PRIVATAPE-DOCUMENTI ELETTRONICI')) == 'U:ARCHIVIO EDILIZIA PRIVATAPE-DOCUMENTI ELETTRONICI')
    		echo substr($str, strlen('U:ARCHIVIO EDILIZIA PRIVATAPE-DOCUMENTI ELETTRONICI'), strlen($str)-1).'<br>';
    }
    /*$arr = [
    		'tec_ou_cc' => [
    				'Caratteristiche_intervento' => [
	    						'A' => 'Nuova A',
    							'B' => 'Nuova B'
	    			],
    				'Caratteristiche_edificio' => [
    						'A' => 'Nuova A1',
    						'B' => 'Nuova B1'
    				]
	    	]
    ];
    $arr = [
    		'tec_ou_cc' => [
    				'Caratteristiche_intervento' => [
    						'A' => 'Nuova costruzione',
    						'B' => 'Ristrutturazione',
    						'C' => 'Restauro, risanamento conservativo',
    						'D' => 'Ampliamento senza incremento superficie utile'
    				],
    				'Caratteristiche_edificio' => [
    						'A' => 'Lusso',
    						'B' => 'Medie',
    						'C' => 'Economiche'
    				],
    						'Tipo_edificio' => [
    						'A' => 'Blocco > di 2 alloggi',
    						'B' => 'Schiera > di 2 alloggi',
    						'C' => 'Fino a 2 alloggi'
    				],
    						'Densita_fondiaria' => [
    						'A' => 'X < 1',
    						'B' => '1 <= X <1,5',
    						'C' => '1,5 <= X <3',
    						'D' => 'X > 3'
    				],
    						'Zona_omogenea' => [
    						'A' => 'X < 1',
    						'B1' => '1 <= X <1,5',
    						'B2' => '1,5 <= X <3',
    						'C1.1' => 'X < 1',
    						'C1.2' => '1 <= X <1,5',
    						'C2.1' => '1,5 <= X <3',
							'C2.2' => 'X < 1',
    						'D' => '1 <= X <1,5',
    						'E' => '1,5 <= X <3'
    			    ],
    						'Tipo_intervento' => [
    						'A' => 'RESIDENZA',
    						'A1' => 'Residenza senza rid. o magg. (caso ordinario)',
    						'A2' => 'Residenza nei P.E.E.P.',
    						'A3' => 'Residenza su area Comunale',
    						'A4' => 'Residenza costruita da I.A.C.P.',
    						'A5' => 'Residenza Costruita da Cooperativa Edilizia',
    						'A6' => 'Residenza primo alloggio del concessionario',
    						'A7' => 'Residenza agricola (NO imprend. agricolo)',
    						'A8' => 'Residenza agricola (SI imprend. agricolo)',
    						'B' => 'INDUSTRIA',
    						'B1' => 'Industria senza rid. o magg. (caso ordinario)',
    						'C' => 'ARTIGIANATO',
    						'C1' => 'Artigianato senza rid. o magg. (caso ordinario)',
    						'D' => 'TURISMO',
    						'D1' => 'Turismo senza rid. o magg. (caso ordinario)',
    						'E' => 'COMMERCIO',
    						'E1' => 'Commercio senza rid. o magg. (caso ordinario)',
    						'F' => 'DIREZIONALE',
    						'F1' => 'Direzionale senza rid. o magg. (caso ordinario)',
    						'G' => 'PRODUTTIVA AGRICOLA',
    						'G1' => 'Prod. Agr. SI impr. agr. NO conduz. del fondo',
    						'G2' => 'Prod. Agr. NO impr. agr., SI conduz. del fondo',
    						'G3' => 'Prod. Agr. NO impr. agr., NO conduz. del fondo',
    						'H' => 'DISTRIBUTORI DI CARBURANTI',
    						'H1' => 'Distributori di carburanti',
    						'I' => 'IMPIANTI SPORTIVI PRIVATI',
    						'I1' => 'Impianti Sportivi Privati SI convenzionati',
    						'I2' => 'Impianti Sportivi Privati NON convenzionati',
    						'L' => 'EDICOLE FUNERARIE',
    						'L1' => 'Edicole Funerarie'
    				]
    		]
    ];
    
    $arr = [
    		'tec_ou_cc' => [
    				'Tipo_edificio' => [
    						'Blocco > di 2 alloggi' => 'Blocco_>_di_2_alloggi',
    						'Schiera > di 2 alloggi' => 'Schiera_>_di_2_alloggi',
    						'Fino a 2 alloggi' => 'Fino_a_2_alloggi'
    				]/*,
    				'Tipo_intervento' => [
    						'A' => 'RESIDENZA',
    						'A1' => 'Residenza senza rid. o magg. (caso ordinario)',
    						'A2' => 'Residenza nei P.E.E.P.',
    						'A3' => 'Residenza su area Comunale',
    						'A4' => 'Residenza costruita da I.A.C.P.',
    						'A5' => 'Residenza Costruita da Cooperativa Edilizia',
    						'A6' => 'Residenza primo alloggio del concessionario',
    						'A7' => 'Residenza agricola (NO imprend. agricolo)',
    						'A8' => 'Residenza agricola (SI imprend. agricolo)',
    						'B' => 'INDUSTRIA',
    						'B1' => 'Industria senza rid. o magg. (caso ordinario)',
    						'C' => 'ARTIGIANATO',
    						'C1' => 'Artigianato senza rid. o magg. (caso ordinario)',
    						'D' => 'TURISMO',
    						'D1' => 'Turismo senza rid. o magg. (caso ordinario)',
    						'E' => 'COMMERCIO',
    						'E1' => 'Commercio senza rid. o magg. (caso ordinario)',
    						'F' => 'DIREZIONALE',
    						'F1' => 'Direzionale senza rid. o magg. (caso ordinario)',
    						'G' => 'PRODUTTIVA AGRICOLA',
    						'G1' => 'Prod. Agr. SI impr. agr. NO conduz. del fondo',
    						'G2' => 'Prod. Agr. NO impr. agr., SI conduz. del fondo',
    						'G3' => 'Prod. Agr. NO impr. agr., NO conduz. del fondo',
    						'H' => 'DISTRIBUTORI DI CARBURANTI',
    						'H1' => 'Distributori di carburanti',
    						'I' => 'IMPIANTI SPORTIVI PRIVATI',
    						'I1' => 'Impianti Sportivi Privati SI convenzionati',
    						'I2' => 'Impianti Sportivi Privati NON convenzionati',
    						'L' => 'EDICOLE FUNERARIE',
    						'L1' => 'Edicole Funerarie'
    				]
    		]
    ];
    renewColumnValues($arr);
    
    function renewColumnValues($arr) {
    	
    	$enums = [];
    	foreach ($arr as $table => $columns) 
    		foreach ($columns as $column => $values)
    			foreach ($values as $oldValue => $newValue) {
    				$GLOBALS['c']->echoCode("UPDATE `$table` SET $column = '$newValue' WHERE `$column` = '$oldValue';");
    				if(!isset($enums[$table][$column])) $enums[$table][$column] = [];
    				$enums[$table][$column][] = $newValue;
    			}
    	foreach ($enums as $table => $columns) 
    		foreach ($columns as $column => $values) {
    			$GLOBALS['c']->echoCode("ALTER TABLE `$table` CHANGE COLUMN `$column` `$column` ENUM('".implode("', '", array_intersect_key($values, array_unique(array_map('strtolower', $values))))."') NOT NULL;");
    		}
	    		
    	
    }
    */
    /*
    $res = $c->db->ql('SELECT Pratica p, Superfici_alloggi s FROM oneri');
    
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
        
    
?>

<html>
<head>
	<script src="../lib/jquery-3.3.1.min.js"></script>
    <script src="../js/gestione_pratiche.js"></script>
</head>
<body>
	<form method="post">
	<?php 
	$c->echoCode($_POST);
	$pratica = $c->db->ql("SELECT * FROM pe_pratiche WHERE ID = ?", [1491])[0];
	$res = $c->db->ql('SELECT Edificio FROM pe_edifici_pratiche WHERE Pratica = ?', [$pratica['ID']]);
	$edificiPratica = [];
	foreach ($res as $value)
		$edificiPratica[] = $value['Edificio'];
		DbTableFormGenerator::generateManyToMany($c->db,
			[
					'pe_fogli_mappali_pratiche' => [
							'title' => 'Fogli-mappali',
							'name' => 'fm',
							'optionsFilter' => ['Edificio' => $edificiPratica],
							'initValuesFilter' => ['Pratica' => $pratica['ID']],
							'value' => ['Edificio', 'Pratica', 'Foglio', 'Mappale'],
							'description' => "CONCAT('F.',Foglio,'m.',Mappale)"
					],
					'pe_intestatari_persone_pratiche' => [
							'title' => 'Intestatari persone',
							'name' => 'ip',
							'initValuesFilter' => ['Pratica' => $pratica['ID']],
							'value' => ['Persona', 'Pratica'],
							'description' => ['table' => 'intestatari_persone', 'description' => "CONCAT('F.',Foglio,'m.',Mappale)"]
					]
			]);
	?>
    	<input type="submit" name="update">
    </form>
</body>
</html>
    }*/