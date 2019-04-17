<?php

    print_r($_POST);

?>
<html>
<head>
	<title>Gestione edifici</title>
</head>
<body>
	<div>
		<form method="post">
    		<input type="number" name="foglio" placeholder="Foglio...">
    		<input type="number" name="mappale" placeholder="Mappale...">
    		<input type="submit" value="Cerca">
    	</form>
	</div>

</body>
</html>