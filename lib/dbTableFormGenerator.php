<?php 
class DbTableFormGenerator {
    
    public static function generate($c, $table, $initArray = [], $isWhitList = TRUE, $columnsList = [], $notRequiredColumns = []) {
        $tableDecription = $c->db->ql('DESCRIBE '.$table);
        //print_r($initArray);

        foreach ($tableDecription as $column) {
            $columnName = $column['Field'];
            echo "<input type=\"hidden\" name=\"old_$columnName\" value=\"$initArray[$columnName]\">";
            if(!($isWhitList^in_array($columnName, $columnsList))){
                
                $parsedType = [];
                preg_match("/(.*)\((.*)\)/", $column['Type'], $parsedType);
                $type = $parsedType[1]??$column['Type'];
                $length = $parsedType[2]??NULL;
                
                $required = in_array($columnName, $notRequiredColumns) ? FALSE : $column['Null'] == 'NO';
                
                if($column['Key'] != 'MUL') {
                    switch ($type) {
                        case 'int':
                        case 'decimal':
                        case 'float':
                        case 'double':
                            DbTableFormGenerator::generateInput($columnName, 'number', $initArray[$columnName], $required, $length);
                            break;
                            
                        case 'char':
                        case 'varchar':
                            if(is_numeric($length) && $length > 50)
                                DbTableFormGenerator::generateTextarea($columnName, $initArray[$columnName], $required, $length);
                            else 
                        DbTableFormGenerator::generateInput($columnName, 'text', $initArray[$columnName], $required, $length);
                            break;
                            
                        case 'date':
                            DbTableFormGenerator::generateInput($columnName, 'date', $initArray[$columnName], $required, NULL);
                            break;
                            
                        case 'text':
                            DbTableFormGenerator::generateTextarea($columnName, $initArray[$columnName], $required, $length);
                            break;
                            
                       case 'enum':
                           $options = [];
                           preg_match("/^enum\(\'(.*)\'\)$/", $column['Type'], $options);
                           DbTableFormGenerator::generateSelect($columnName, explode("','", $options[1]), $initArray[$columnName], $required);
                           break;
                            
                        default:
                            echo "<pre style=\"color:red;\">Tipo $type non gestito</pre>";
                            break;
                    }
                    
                }else
                DbTableFormGenerator::generateForeingKey($table, $columnName, $c, $initArray[$columnName], $required, $length);
            }
        }
    }
    
    private static function generateInput($name, $type, $value, $required, $length) {
        echo "<label>$name</label>";
        echo "<input type=\"$type\" name=\"$name\" value=\"".($value??'')."\"".(is_numeric($length)?" length=\"$length\"":'').($required ? ' required="required"' : '').'>';
    }
    
    private static function generateTextarea($name, $value, $required, $length) {
        echo "<label>$name</label>";
        echo "<textarea name=\"$name\"".(is_numeric($length)?" length=\"$length\"":'').($required ? ' required="required"' : '').' rows="5" cols="50">'.($value??'').'</textarea>';
    }
    
    private static function generateSelect($name, $options, $value, $required) {
        echo "<label>$name</label>";
        echo "<select name=\"$name\">";
        if($required) echo '<option value=""><option>';
        foreach ($options as $option)
            echo "<option value=\"$option\"".($option == $value ? ' selected="selected"' : '').">$option</option>";
        echo '</select>';
    }
    
    private function generateForeingKey($table, $column, $c, $value, $required, $length) {
        $fks = $c->db->ql("SELECT `referenced_table_name` AS tab, `referenced_column_name`  AS col
                                FROM `information_schema`.`KEY_COLUMN_USAGE`
                                WHERE `constraint_schema` = SCHEMA() AND `column_name`= '$column' AND `table_name` = '$table' AND `referenced_column_name` IS NOT NULL")[0];

        if($value) {
            $info = $c->getParsedTableDescription($fks['tab']);
            $value = $c->db->ql("SELECT $info[Description] AS Description, $info[Value] AS Value
                                            FROM $fks[tab]
                                            WHERE $fks[col] = ?",
                                            [$value]);
            $description = (count($value) > 0) ? $value[0]['Description'] : '';
            $value = (count($value) > 0) ? $value[0]['Value'] : '';
        }
        
        DbTableFormGenerator::generateForeingKeyField($column, $description??'', $value, $fks['tab'], $fks['col'], $required, $length);
    }
    
    private static function generateForeingKeyField($title, $description, $value, $extTab, $extCol, $required, $length) {
        echo "<label>$title</label>";
        echo "<input id=\"".$title."SearchField\" type=\"text\" value=\"".($description??'')."\" onfocusin=\"this.select();\" onkeyup=\"getHints('$extTab', '$extCol', '#".$title."SearchField', '#".$title."Hints', 'input[name=$title]')\"".(is_numeric($length)?" length=\"$length\"":'').'>';
        echo '<input type="hidden" name="'.$title.'" value="'.($value??'').'" '.($required ? ' required="required"' : '').'>';
        echo '<div id="'.$title.'Hints" class="hintDiv"></div>';
    }
    
    /**
     * 
     * @param DB $db
     * @param string $table
     * @param array $request
     */
    public static function updateRecord($db, $table, $request) {
        //print_r($params);
        $tableDescription = $db->ql('DESCRIBE '.$table);
        $pks = [];
        foreach ($tableDescription as $column)
            if($column['Key'] == 'PRI')
                $pks[] = $column['Field'];
        
        $count = 0;
        $where = [];
        $values = [];
        $set = [];
        
        foreach ($request as $name => $value) {
            if(substr($name, 0, 4) == 'old_'){
                if(in_array(substr($name, 4, strlen($name)), $pks)){
                    $where[] = substr($name, 4, strlen($name))." = :$count";
                    $values[":$count"] = $value;
                }
            }else{
                $set[] = "$name = :$count";
                if(!$value)
                    foreach ($tableDescription as $column) 
                        if($column['Field'] == $name){
                            $value = ($column['Null'] == 'YES') ? NULL : '';
                            break;
                        }
                $values[':'.($count++)] = $value;
            }
            $count++;
        }
        $sql = "UPDATE $table SET ".implode(', ', $set).' WHERE '.implode(' AND ', $where);
        //print_r($db->ql("SELECT * FROM $table WHERE ".implode(' AND ', $where), $values));

        return ($db->dml($sql, $values)->rowCount() > 0) ? TRUE : $db->lastErrorInfo[2];
    }
    
    /**
     *
     * @param DB $db
     * @param array $map
     */
    public static function generateManyToMany($db, $map) {
    	//echo '<pre>'; print_r($map); echo '</pre>';
        foreach ($map as $table => $arr) {
        	echo '<div id="'.$arr['title'].'" class="manyTOmany">';
            echo "<label>$arr[title]</label>";
            
            $where = [];
            $values = [];
            foreach ($arr['optionsFilter'] as $col => $value) {
            	$where[] = $col.' = ?';
            	$values[] = $value;
            }
            $options = $db->ql("SELECT ".implode(', ', $arr['value']).", $arr[description] Description FROM $table WHERE ".implode(' AND ', $where), $values);
            echo '<script>'.$arr['name'].' = '.json_encode($options).'; </script>';
            
            $where = [];
            $values = [];
            foreach ($arr['initValuesFilter'] as $col => $value) {
                $where[] = $col.' = ?';
                $values[] = $value;
            }
            foreach ($arr['optionsFilter'] as $col => $value) {
            	$where[] = $col.' = ?';
            	$values[] = $value;
            }
            $initRecors = $db->ql("SELECT ".implode(', ', $arr['value'])." FROM $table WHERE ".implode(' AND ', $where), $values);
            
            foreach ($initRecors as $record) {
            	echo '<script>1addManyTOManyField($(this).parent(), '.$arr['name'].');"
            	/*echo '<select disabled name="'.$arr['name'].($i++).'">';
            	$desc = $record['Description'];
            	unset($record['Description']);
            	echo '<option vaule="'.implode('-', $record)."\">$desc</option>";
            	echo '</select>';
            	echo '<button type="button" onclick="$(this).parent().remove();">Elimina</button>';*/
            }
            
            echo '<button type="button" onclick="addManyTOManyField($(this).parent(), '.$arr['name'].');">Aggiungi</button>';
            
            echo '<pre>'; print_r($initRecors); echo '</pre>';
            echo '</div>';
        }
    }
    
}
