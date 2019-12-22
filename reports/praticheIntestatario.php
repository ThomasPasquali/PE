<?php
include_once '../controls.php';
$c = new Controls();

if(!$c->logged()){
    header('Location: ../index.php?err=Utente non loggato');
    exit();
}

if($c->check(['p_id'], $_REQUEST)) {
    $resPE = $c->db->ql('SELECT p.ID, p.Sigla
                       FROM pe_intestatari_persone_pratiche ip
                       JOIN pe_pratiche_view p ON ip.Pratica = p.ID
                       WHERE ip.Persona = ?', [$_REQUEST['p_id']]);
    $resTEC = $c->db->ql('SELECT p.ID, p.Sigla
                       FROM tec_intestatari_persone_pratiche ip
                       JOIN tec_pratiche_view p ON ip.Pratica = p.ID
                       WHERE ip.Persona = ?', [$_REQUEST['p_id']]);
}else if($c->check(['s_id'], $_REQUEST)) {
    $resPE = $c->db->ql('SELECT p.ID, p.Sigla
                       FROM pe_intestatari_societa_pratiche ip
                       JOIN pe_pratiche_view p ON ip.Pratica = p.ID
                       WHERE ip.Societa = ?', [$_REQUEST['s_id']]);
    $resTEC = $c->db->ql('SELECT p.ID, p.Sigla
                       FROM tec_intestatari_societa_pratiche ip
                       JOIN tec_pratiche_view p ON ip.Pratica = p.ID
                       WHERE ip.Societa = ?', [$_REQUEST['s_id']]);
}

?>
<html>
	<head>
    	<link rel="stylesheet" type="text/css" href="../css/utils_bar.css">
    	<style type="text/css">
    	.res {
    	   display:block;
    	   margin-bottom:10px;
    	   font-size:1.2em;
    	}
    	</style>
	</head>
	<body>
	<?php 
	$c->includeHTML('../htmlUtils/utils_bar.html');
	if(isset($resPE)) {
	    echo '<div style="display:block;">';
	    if(count($resPE) == 0 && count($resTEC) == 0)
	        echo '<h1>L\'intestatario non ha pratiche</h1>';
	    else
	        foreach ($resPE as $pratica)
	           echo '<a class="res" href="praticaPE.php?id='.$pratica['ID'].'">'.$pratica['Sigla'].'</a>';
	        foreach ($resTEC as $pratica)
	           echo '<a class="res" href="praticaTEC.php?id='.$pratica['ID'].'">'.$pratica['Sigla'].'</a>';
	   echo '</div>';
	}else {
	?>
		<h1>Richiesta incompleta o errata</h1>
	<?php
	}
	?>
	</body>
</html>