<?php
    class Reports{

        /**
         *
         * @param DB $db
         * @param string $nome
         * @param string $cognome
         * @param string $cf
         */
        public static function anagraficaIntestatario($db, $nome = '', $cognome = '', $cf = ''){
            $where = [];
            if(!empty($nome)) $where[] = 'Nome LIKE :n';
            if(!empty($cognome)) $where[] = 'Cognome LIKE :c';
            if(!empty($cf)) $where[] = 'Codice_fiscale LIKE :cf';
            $where = implode(' AND ', $where);

            $sql = 'SELECT ID, Cognome, Nome, Codice_fiscale cf
                        FROM intestatari_persone '.
                        ((!empty($where))?('WHERE '.$where):'');

            $params = [];
            if(!empty($nome)) $params[':n'] = "%$nome%";
            if(!empty($cognome)) $params[':c'] = "%$cognome%";
            if(!empty($cf)) $params[':cf'] = "%$cf%";

            $rs = $db->ql($sql, $params);

            echo '<div class="wrapper">';

            include_once 'cf.php';
            foreach ($rs as $row){
                $codiceFiscale = new CFChecker();
                $codiceFiscale->isFormallyCorrect($row['cf']);
?>
    				<div class="content">
    				<h1><?= $row['Cognome'] ?> <?= $row['Nome'] ?></h1>
    					<div class="inner-wrap">
        					<p><span class="title">Nome:</span> <?= $row['Nome'] ?></p>
    						<p><span class="title">Cognome:</span> <?= $row['Cognome'] ?></p>
    						<p><span class="title">Codice fiscale:</span> <?= $row['cf'] ?></p>
    						<p><span class="title">Data di nascita:</span> <?= $codiceFiscale->getBirthDate(); ?></p>
    						<p><span class="title">Luogo di nascita:</span>
    							<?php
                  $res = $db->ql('SELECT Nome FROM belfiore WHERE Belfiore = ?', [$codiceFiscale->getCountryBirth()]);
    							echo (count($res) > 0) ? $res[0]['Nome'] : 'N/A';
    							?>
    						</p>
    						<p><span class="title">Sesso:</span> <?= $codiceFiscale->getSex() ?></p>
    						<form action="reports/anagrafica.php" method="get">
        						<button name="persona" class="formBtn" value="<?= $row['ID'] ?>">Visualizza/modifica</button>
        					</form>
    					</div>
    				</div>
<?php
            }
            echo '</div>';
        }

        /**
         *
         * @param DB $db
         * @param string $intestazione
         * @param string $piva
         */
        public static function anagraficaSocieta($db, $intestazione = '', $piva = ''){
            $where = [];
            if(!empty($intestazione)) $where[] = 'Intestazione LIKE :i';
            if(!empty($piva)) $where[] = 'Partita_iva LIKE :piva';
            $where = implode(' AND ', $where);

            $sql = 'SELECT ID, Intestazione, Partita_iva piva
                        FROM intestatari_societa '.
                        ((!empty($where))?('WHERE '.$where):'');

                        $params = [];
                        if(!empty($intestazione)) $params[':i'] = "%$intestazione%";
                        if(!empty($piva)) $params[':piva'] = "%$piva%";

                        $rs = $db->ql($sql, $params);

                        echo '<div class="wrapper">';

                        foreach ($rs as $row){
                            ?>
    				<div class="content">
    				<h1><?=  $row['Intestazione'] ?></h1>
    					<div class="inner-wrap">
        					<p><span class="title">Intestazione:</span> <?= $row['Intestazione'] ?></p>
    						<p><span class="title">Partita iva:</span> <?= $row['piva'] ?></p>

    						<form action="reports/anagrafica.php" method="get">
        						<button name="societa" class="formBtn" value="<?= $row['ID'] ?>">Visualizza/modifica</button>
        					</form>
    					</div>
    				</div>
<?php
            }
            echo '</div>';
        }

        /**
         *
         * @param DB $db
         * @param string $nome
         * @param string $cognome
         * @param string $cf
         * @param string $piva
         */
        public static function anagraficaTecnico($db, $nome = '', $cognome = '', $cf = '', $piva = ''){
            $where = [];
            if(!empty($nome)) $where[] = 'Nome LIKE :n';
            if(!empty($cognome)) $where[] = 'Cognome LIKE :c';
            if(!empty($cf)) $where[] = 'Codice_fiscale LIKE :cf';
            if(!empty($piva)) $where[] = 'Partita_iva LIKE :piva';
            $where = implode(' AND ', $where);

            $sql = 'SELECT ID, Cognome, Nome, Codice_fiscale cf, Partita_iva piva, Albo, Numero_ordine, Provncia_albo
                        FROM tecnici '.
                        ((!empty($where))?('WHERE '.$where):'');

                        $params = [];
                        if(!empty($nome)) $params[':n'] = "%$nome%";
                        if(!empty($cognome)) $params[':c'] = "%$cognome%";
                        if(!empty($cf)) $params[':cf'] = "%$cf%";
                        if(!empty($piva)) $params[':piva'] = "%$piva%";

                        $rs = $db->ql($sql, $params);

                        echo '<div class="wrapper">';

                        include_once 'cf.php';
                        foreach ($rs as $row){
                            $codiceFiscale = new CFChecker();
                            $codiceFiscale->isFormallyCorrect($row['cf']);
                            ?>
    				<div class="content">
    				<h1><?= $row['Cognome'] ?> <?= $row['Nome'] ?></h1>
    					<div class="inner-wrap">
    						<h2>Dati anagrafici</h2>
        					<p><span class="title">Nome:</span> <?= $row['Nome'] ?></p>
    						<p><span class="title">Cognome:</span> <?= $row['Cognome'] ?></p>
    						<p><span class="title">Codice fiscale:</span> <?= $row['cf'] ?></p>
    						<p><span class="title">Data di nascita:</span> <?= $codiceFiscale->getBirthDate(); ?></p>
    						<p><span class="title">Luogo di nascita:</span>
    							<?php
    							$db->query('USE utils');
    							echo $db->ql('SELECT Nome FROM belfiore WHERE Belfiore = ?', [$codiceFiscale->getCountryBirth()])[0]['Nome'];
    							$db->query('USE pe');
    							?>
    						</p>
    						<p><span class="title">Sesso:</span> <?= $codiceFiscale->getSex() ?></p>

    						<h2>Dati tecnici</h2>
    						<p><span class="title">Partita iva:</span> <?= $row['piva'] ?></p>
    						<p><span class="title">Albo:</span> <?= $row['Albo']."($row[Provncia_albo])" ?></p>
    						<p><span class="title">Numero ordine:</span> <?= $row['Numero_ordine'] ?></p>
    						<form action="reports/anagrafica.php" method="get">
        						<button name="tecnico" class="formBtn" value="<?= $row['ID'] ?>">Visualizza/modifica</button>
        					</form>
    					</div>
    				</div>
<?php
            }
            echo '</div>';
        }

        /**
         *
         * @param DB $db
         * @param string $intestazione
         * @param string $cf
         * @param string $piva
         */
        public static function anagraficaImprese($db, $intestazione = '', $cf = '', $piva = ''){
            $where = [];
            if(!empty($intestazione)) $where[] = 'Intestazione LIKE :i';
            if(!empty($cf)) $where[] = 'Codice_fiscale LIKE :cf';
            if(!empty($piva)) $where[] = 'Partita_iva LIKE :piva';
            $where = implode(' AND ', $where);

            $sql = 'SELECT ID, Intestazione, Codice_fiscale cf, Partita_iva piva
                        FROM imprese '.
                        ((!empty($where))?('WHERE '.$where):'');

                        $params = [];
                        if(!empty($intestazione)) $params[':i'] = "%$intestazione%";
                        if(!empty($cf)) $params[':cf'] = "%$cf%";
                        if(!empty($piva)) $params[':piva'] = "%$piva%";

                        $rs = $db->ql($sql, $params);

                        echo '<div class="wrapper">';

                        include_once 'cf.php';
                        foreach ($rs as $row){
                            $codiceFiscale = new CFChecker();
                            $codiceFiscale->isFormallyCorrect($row['cf']);
                            ?>
    				<div class="content">
    				<h1><?= $row['Intestazione'] ?></h1>
    					<div class="inner-wrap">
    						<p><span class="title">Intestazione:</span> <?= $row['Intestazione'] ?></p>
    						<p><span class="title">Codice fiscale:</span> <?= $row['cf'] ?></p>
    						<p><span class="title">Partita iva:</span> <?= $row['piva'] ?></p>
    						<form action="reports/anagrafica.php" method="get">
        						<button name="impresa" class="formBtn" value="<?= $row['ID'] ?>">Visualizza/modifica</button>
        					</form>
    					</div>
    				</div>
<?php
            }
            echo '</div>';
        }
    }
