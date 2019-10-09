<?php
    include_once 'controls.php';
    $controls =  new Controls();
    $err = '';

    if($_SERVER['REQUEST_METHOD'] == 'POST')
        if ($_POST['singin'] == 'normal') {
            $user = $_POST['email'] ?? '';
            $pass = $_POST['password'] ?? '';
            if(empty($user))
                $err = 'Utente vuoto';
            else if(empty($pass))
                $err = 'Password vuota';
            else{
                $res = $controls->registerUser($user, $pass);
                if($res->errorCode() == 0){
                    header('Location: index.php?info=Utente in attesa di approvazione');
                    exit();
                }
                $err = 'Impossibile richiedere l\'account';
            }
        }

?>
<html lang="it">
<head>
	<link href='css/index.css' rel='stylesheet' type='text/css'>
</head>
<body>
    <div class="page">
    <p class="error"><?= $err ?></p>
      <div class="container">
        <div class="left">
          <div class="login">Comune di<br>Canale d'Agordo</div>
          <div class="eula"><img src="imgs/logo.jpg"></div>
        </div>
        <div class="right">
         <div class="login">Registrazione<br>account</div>
         <form action="" method="post">
          <div class="form">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" autocomplete="off">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="off">
            <button type="submit" id="submit" name="singin" value="normal">Chiedi approvazione account</button>
            <a href="index.php">Torna al login</a>
          </div>
          </form>
        </div>
      </div>
    </div>
</body>
</html>
