<?php
    include_once '../lib/db.php';
    $db = new DB(['db'=>'mysql', 'host'=>'127.0.0.1', 'dbName'=>'timbrature', 'port'=>'3306', 'user'=>'pe-webapp', 'pass'=>'waa']);
    if(isset($_POST['nome'])&&isset($_POST['cognome'])&&isset($_POST['da'])&&isset($_POST['a'])) {

        $user = $db->ql(
            'SELECT *
            FROM ts_users
            WHERE   Name_First = ?
                AND Name_Last = ?',
            [$_POST['nome'], $_POST['cognome']]);

        if(count($user) == 1) {

            $user = $user[0];
            $results = $db->ql(
                'SELECT d.devName, r.*
                FROM ts_records r
                JOIN ts_users u ON u.idUser = r.idUser
                LEFT JOIN ts_devices d ON d.devNum = r.deviceNum 
                WHERE   u.Name_First LIKE :n
                    AND u.Name_Last LIKE :c
                    AND r.logTime BETWEEN :da AND :a
                ORDER BY r.logTime',
                [':n'=>$_POST['nome'], ':c'=>$_POST['cognome'], ':da'=>$_POST['da'], ':a'=>$_POST['a']]);

        }else {
            echo '<pre>Utente non identificato:\n';
            print_r($user);
            echo '</pre>';
        }

        
    }
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Timbrature</title>
    <style>
        table {
            width: 100%;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <?php if(!isset($results)) { ?>

        <form action="" method="POST">
            <input type="text" name="nome" placeholder="Nome...">
            <input type="text" name="cognome" placeholder="Cognome...">
            <label>Da: </label>
            <input type="date" name="da">
            <label>A: </label>
            <input type="date" name="a">
            <input type="submit">
        </form>

    <?php }else { ?>

        <h1>Piano di lavoro di <?= $user['Username'] ?> dal <?= date_format(date_create($_POST['da']),"d/m/Y"); ?> al <?= date_format(date_create($_POST['a']),"d/m/Y"); ?><h1>
        <table>
            <tr>
                <td>Data</td>
                <td>Ora</td>
                <td>Timbratore</td>
            </tr>
    <?php 
        foreach($results as $result) {
            $datetime = date_create($result['logTime']); 
            ?>
            <tr>
                <td><?= date_format($datetime,"d/m/Y") ?></td>
                <td><?= date_format($datetime,"H:i:s") ?></td>
                <td><?= $result['devName'] ?></td>
            </tr>
            <?php 
        }
        /*echo '<pre>';
        print_r($_POST);
        print_r($user);
        print_r($results);
        echo '</pre>';*/
    ?>
        </table>
    <?php } ?>

</body>
</html>