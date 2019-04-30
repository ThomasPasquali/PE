<?php
    include_once '../controls.php';
    $c = new Controls();
    
    if(!$c->logged()){
        header('Location: index.php?err=Utente non loggato');
        exit();
    }
    
    if($c->check(['foglio', 'mappale'], $_POST)){
        $res = $c->db->ql('SELECT *
                                    FROM fogli_mappali_edifici
                                    WHERE Foglio LIKE ? AND Mappale LIKE ?',
                                    ["%$_POST[Foglio]%", "%$_POST[Mappale]%"]);
        print_r($res);
            
    }
    
    print_r($_POST);
    
?>
<html>
<head>
	<title>Gestione edifici</title>
	<link rel="stylesheet" href="style_gestione.css">
	<script type="text/javascript" src="/lib/jquery-3.3.1.min.js"></script>
</head>
<body>
	<div>
		<form method="post">
    		<input type="number" name="foglio" placeholder="Foglio..." value="<?= $_POST['foglio']??'' ?>">
    		<input type="number" name="mappale" placeholder="Mappale..." value="<?= $_POST['mappale']??'' ?>">
    		<input type="submit" value="Cerca">
    	</form>
	</div>


	<!-- Sidebar -->
    <div class="w3-sidebar w3-light-grey w3-bar-block" style="width:17%">
      <p onclick="reloadPageWithFlag('richieste')" class="w3-bar-item w3-button">Richieste di<br>attivazione</p>
      <p onclick="reloadPageWithFlag('gestione')" class="w3-bar-item w3-button">Gestione<br>accounts</p>
    </div>
    
    <!-- Page Content -->
    <div id="pageContent" style="margin-left:17%">
        
        <div id="richieste" class="content">
        	<div class="w3-container w3-teal"><h1>Account inattivi</h1></div>
            
            <div class="w3-container">
                <?php 
                foreach ($inattivi as $account) 
                    echo "<div class=\"row\">
                                <p>$account[Email]</p>
                                <button onclick=\"activate('$account[Email]', this);\">Attiva</button>
                              </div>";
                ?>
            </div>
        </div>
        
        <div id="gestione" class="content" style="display: none;">
        	<div class="w3-container w3-teal"><h1>Gestione accounts</h1></div>
            
            <div class="w3-container">
                <?php 
                    foreach ($attivi as $account) 
                        echo "<div class=\"row\">
                                    <p>$account[Email]</p>
                                    <button onclick=\"deactivate('$account[Email]', this);\">Disattiva</button>
                                    <button onclick=\"changeType('$account[Email]', '".($account['Type']=='ADMIN'?'USER':'ADMIN')."', this);\">".($account['Type']=='ADMIN'?'Declassa ad utente':'Promuovi ad admin')."</button>
                                    <button onclick=\"deleteAccount('$account[Email]', this);\" style=\"background-color:red;\">Elimina</button>
                                 </div>";
                ?>
            </div>
        </div>
        
    </div>
    
    <script type="text/javascript" src="/lib/misc.js"></script>
    <script type="text/javascript" src="edifici.js"></script>
</body>
</html>