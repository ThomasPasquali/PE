<?php
	include_once '../lib/db.php';
	$ini = parse_ini_file("../../PE_ini/DB.ini", TRUE)['timbrature'];
	$db = new DB(
		['db'=>$ini['db'],
		'host'=>$ini['host'],
		'dbName'=>$ini['dbName'],
		'port'=>$ini['port'],
		'user'=>$ini['user'],
		'pass'=>$ini['pass']]);
?>
<html>
	<head>
		<!-- JQUERY -->
		<script type="text/javascript" src="../lib/jquery-3.4.1.min.js"></script>
		<script type="text/javascript" src="../lib/jquery-ui-1.12.1/jquery-ui.min.js"></script>
		
		<!-- TABULATOR -->
		<link href="../lib/tabulator/dist/css/tabulator.min.css" rel="stylesheet">
		<script type="text/javascript" src="../lib/tabulator/dist/js/tabulator.min.js"></script>
		<link href="../lib/tabulator/dist/css/tabulator_midnight.min.css" rel="stylesheet">
		
		<script type="text/javascript">
			$(document).ready(function() {
			    $.getScript("table.js");
			});
		</script>
		<title>Timbrature</title>
	</head>
	<body>
		<form id="form">
            <select name="user">
                <?php
                $users = $db->ql('SELECT DISTINCT Username FROM ts_users WHERE Username <> \'admin\' ORDER BY Username');
                foreach($users as $u) echo "<option value=\"$u[Username]\">$u[Username]</option>";
                ?>
            </select>
            <label>Da: </label>
            <input type="date" name="da" value="<?= $_GET['da'] ?>">
            <label>A: </label>
            <input type="date" name="a" value="<?= $_GET['a'] ?>">
            <button type="button" onclick="refreshData();">Aggiorna tabella</button>
        </form>
		
		<div>
			<button id="download-csv" type="button">Download CSV</button>
			<button id="print" type="button">Stampa</button>
		</div>
		
        <div id="table"></div>
	</body>
</html>