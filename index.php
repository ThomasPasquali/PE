<?php
    include_once 'controls.php';
    $controls =  new Controls();
    $err = '';
    $info = '';
    
    if($controls->logged()&&!isset($_POST['destroy'])){
        header('Location: home.php');
        exit();
    }
    
    if(isset($_GET['err']))
        $err = $_GET['err'];
    if(isset($_GET['info']))
        $info = $_GET['info'];
    
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        if(isset($_POST['destroy']))
            $controls->logout();
        else if ($_POST['access'] == 'normal') {
                $user = $_POST['email'] ?? '';
                $pass = $_POST['password'] ?? '';
                if(empty($user))
                    $err = 'Utente vuoto';
                else if(empty($pass))
                    $err = 'Password vuota';
                else {
                    $res = $controls->login($user, $pass);
                    if($res[0] == TRUE){
                        header('Location: home.php');
                        exit();
                    }else
               $err = $res[1];     
                }
            }
        }
?>
<html lang="it">
<head>
	<link href='index.css' rel='stylesheet' type='text/css'>
</head>
<body>
    <div class="page">
        <?= empty($err)?'':"<p class=\"error\"> $err </p>" ?>
        <?= empty($info)?'':"<p class=\"info\"> $info </p>" ?>
      <div class="container">
        <div class="left">
          <div class="login">Comune di<br>Canale d'Agordo</div>
          <div class="eula"><img src="imgs/logo.jpg"></div>
        </div>
        <div class="right">
         <div class="login">Gestione<br>Pratiche Edilizie</div>
         <form action="" method="post">
          <div class="form">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" autocomplete="off">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="off">
            <button type="submit" id="submit" name="access" value="normal">Accedi</button>
            <a href="singin.php">Registra nuovo account</a>
          </div>
          </form>
        </div>
      </div>
    </div>
</body>
</html>