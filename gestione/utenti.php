<?php
    include_once '../controls.php';
    $c = new Controls();

    if(!$c->logged()){
        header('Location: ../index.php?err=Utente non loggato');
        exit();
    }

    if(!$c->isAdmin()){
        header('Location: ../home.php?err=Permessi richiesti');
        exit();
    }

    $inattivi = $c->db->ql('SELECT Email FROM utenti WHERE Active = \'0\'');
    $attivi = $c->db->ql('SELECT Email, Type FROM utenti WHERE Active = \'1\'');
?>
<html>
<head>
	<title>Gestione utenti</title>
	<link rel="stylesheet" href="../css/gestione.css">
	<link rel="stylesheet" href="../css/alerts.css">
	<script type="text/javascript" src="../lib/jquery-3.3.1.min.js"></script>
  <link rel="stylesheet" type="text/css" href="../lib/fontawesome/css/all.css">
  <link rel="stylesheet" type="text/css" href="../css/utils_bar.css">
</head>
<body>
    <?php
    $c->includeHTML('../htmlUtils/utils_bar.html');
    ?>

    <!-- Sidebar -->
    <div class="w3-sidebar w3-light-grey w3-bar-block" style="width:17%">
      <p onclick="reloadPageWithFlag('richieste')" class="w3-bar-item w3-button">Richieste di<br>attivazione</p>
      <p onclick="reloadPageWithFlag('gestione')" class="w3-bar-item w3-button">Gestione<br>accounts</p>
    </div>

    <!-- Page Content -->
    <div id="pageContent" style="margin-left:17%;margin-top:50px;">

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
    <script type="text/javascript" src="../js/misc.js"></script>
    <script type="text/javascript" src="../js/gestione_utenti.js"></script>
</body>
</html>
