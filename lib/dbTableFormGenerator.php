<?php 
class DbTableFormGenerator {
    
    public static function generate($db, $table, $isWhitList = TRUE, $columnsList = []) {
        $tableDecription = $db->ql('DESCRIBE '.$table);
        //print_r($tableDecription);

        foreach ($tableDecription as $column) {
            if(!($isWhitList^in_array($column['Field'], $columnsList))){
                
                $parsedType = [];
                preg_match("/(.*)\((.*)\)/", $column['Type'], $parsedType);
                $type = $parsedType[1]??$column['Type'];
                
                if($column['Key'] != 'MUL') {
                    switch ($type) {
                        case 'int':
                        case 'decimal':
                        case 'float':
                        case 'double':
                            DbTableFormGenerator::generateInput($column['Field'], 'number', $parsedType[2]);
                            break;
                            
                        case 'char':
                        case 'varchar':
                            DbTableFormGenerator::generateInput($column['Field'], 'text', $parsedType[2]);
                            break;
                            
                        case 'date':
                            DbTableFormGenerator::generateInput($column['Field'], 'date');
                            break;
                            
                        case 'text':
                            DbTableFormGenerator::generateTextarea($column['Field']);
                            break;
                            
                       case 'enum':
                           $options = [];
                           preg_match("/^enum\(\'(.*)\'\)$/", $column['Type'], $options);
                           DbTableFormGenerator::generateSelect($column['Field'], explode("','", $options[1]));
                           break;
                            
                        default:
                            echo "<pre style=\"color:red;\">Tipo $type non gestito</pre>";
                            break;
                    }
                    
                }else {
                    //TODO chiavi esterne
                    DbTableFormGenerator::generateForeingKeyField($table, $column['Field'], $db, $parsedType[2]);
                }
            }
            
        }
    }
    
    private static function generateInput($name, $type, $length = NULL) {
        echo "<label>$name</label>";
        echo "<input type=\"$type\" name=\"$name\"".(is_numeric($length)?" length=\"$length\"":'').'>';
    }
    
    private static function generateTextarea($name, $length = NULL) {
        echo "<label>$name</label>";
        echo "<textarea name=\"$name\"".(is_numeric($length)?" length=\"$length\"":'').'></textarea>';
    }
    
    private static function generateSelect($name, $options) {
        echo "<label>$name</label>";
        echo "<select name=\"$name\">";
        foreach ($options as $option)
            echo "<option value=\"$option\">$option</option>";
        echo '</select>';
    }
    
    private function generateForeingKeyField($table, $column, $db, $length = NULL) {
        $fks = $db->ql(
                    "SELECT `referenced_table_name` AS tab, `referenced_column_name`  AS col
                    FROM `information_schema`.`KEY_COLUMN_USAGE`
                    WHERE `constraint_schema` = SCHEMA() AND `column_name`= '$column' AND `table_name` = '$table' AND `referenced_column_name` IS NOT NULL
                    ORDER BY `column_name`")[0];
        echo "<label>$column</label>";
        echo "<input id=\"".$column."SearchField\" type=\"text\" onfocusin=\"this.select();\" onkeyup=\"getHints('$fks[tab]', '$fks[col]', '#".$column."SearchField', '#".$column."Hints', 'input[name=$column]')\"".(is_numeric($length)?" length=\"$length\"":'').'>';
        echo '<input type="hidden" name="'.$column.'">';
        echo '<div id="'.$column.'Hints" class="hintDiv"></div>';
    }
    
}
