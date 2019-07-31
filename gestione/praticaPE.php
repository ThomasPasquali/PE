<?php 
include_once '../controls.php';
include_once '../lib/dbTableFormGenerator.php';
$c = new Controls();

if(!$c->logged()){
    header('Location: ../index.php?err=Utente non loggato');
    exit();
}
?>
<html>
<head>
    <style type="text/css">
        input, select, textarea {
            display: block;
        }
        .hintDiv *{
            display: block;
        }
    </style>
    <script src="../lib/jquery-3.3.1.min.js"></script>
</head>
<body>
	<?php 
	DbTableFormGenerator::generate($c->db, 'tec_pratiche', FALSE, ['ID', 'IDold']);
	?>
	
	<script type="text/javascript" src="../js/hints.js"></script>
</body>
</html>