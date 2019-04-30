<?php
    include_once '../controls.php';
    $c = new Controls();
    
    if(!$c->logged()){
        header('Location: index.php?err=Utente non loggato');
        exit();
    }
    
    if(!$c->isAdmin()){
        header('Location: home.php?err=Permessi richiesti');
        exit();
    }
    
    $inattivi = $c->db->ql('SELECT Email FROM utenti WHERE Active = \'0\'');
    $attivi = $c->db->ql('SELECT Email, Type FROM utenti WHERE Active = \'1\'');
?>
<html>
<head>
	<title>Gestione utenti</title>
	<link rel="stylesheet" href="style_gestione.css">
	<script type="text/javascript" src="/lib/jquery-3.3.1.min.js"></script>
	<style type="text/css">
	   .alert {
          padding: 20px;
          background-color: #f44336;
          color: white;
          opacity: 1;
          transition: opacity 0.6s;
          margin-bottom: 15px;
        }
        
        .alert.success {background-color: #4CAF50;}
        .alert.info {background-color: #2196F3;}
        .alert.warning {background-color: #ff9800;}
        
        .closebtn {
          margin-left: 15px;
          color: white;
          font-weight: bold;
          float: right;
          font-size: 22px;
          line-height: 20px;
          cursor: pointer;
          transition: 0.3s;
        }
        
        .closebtn:hover {
          color: black;
        }
        
        .row{
            font-size: 0.7em;
            margin-top: 15px;
        }
        
	</style>
</head>
<body>
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
    <script type="text/javascript" src="utenti.js"></script>
</body>
</html>