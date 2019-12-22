<?php
    class Reports{

        /**
         *
         * @param DB $db
         * @param string $nome
         * @param string $cognome
         * @param string $cf
         */
        public static function anagraficaIntestatari($db, $nome = '', $cognome = '', $cf = '', $url = '', $paramName = '', $btnTxt = ''){
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
    						<form action="<?= $url ?>" method="get">
        						<button name="<?= $paramName ?>" class="formBtn" value="<?= $row['ID'] ?>"><?= $btnTxt ?></button>
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
        public static function anagraficaSocieta($db, $intestazione = '', $piva = '', $url = '', $paramName = '', $btnTxt = ''){
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

    						<form action="<?= $url ?>" method="get">
        						<button name="<?= $paramName ?>" class="formBtn" value="<?= $row['ID'] ?>"><?= $btnTxt ?></button>
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
        public static function anagraficaTecnici($db, $nome = '', $cognome = '', $cf = '', $piva = ''){
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
    							<?= $db->ql('SELECT Nome FROM belfiore WHERE Belfiore = ?', [$codiceFiscale->getCountryBirth()])[0]['Nome']??'N/A'; ?>
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

    /**
     *
     * @param DB $db
     */
    public static function pratiche($db, $tipo = '', $anno = '', $numero = '', $barrato = '', $foglio = '', $mappale = '', $pe_o_tec = 'pe'){
        $where = [];
        $params = [];
        if(!empty($tipo)){
            $where[] = 'p.Tipo = :tipo';
            $params[':tipo'] = $tipo;
        }
        if(!empty($anno)){
          $where[] = 'p.Anno = :anno';
          $params[':anno'] = $anno;
        }
        if(!empty($numero)){
            $where[] = 'p.Numero = :numero';
            $params[':numero'] = $numero;
        }
        if(!empty($barrato)){
            $where[] = 'p.Barrato = :barrato';
            $params[':barrato'] = $barrato;
        }
        if(!empty($foglio)){
            $where[] = 'fm.Foglio = :foglio';
            $params[':foglio'] = $foglio;
        }
        if(!empty($mappale)){
            $where[] = 'fm.Mappale = :mappale';
            $params[':mappale'] = $mappale;
        }
        $where = implode(' AND ', $where);
        
        $sql = 'SELECT ID, Sigla, TIPO, Anno, Numero, FogliMappali, Intestatari_persone, Intestatari_societa
                FROM '.$pe_o_tec.'_pratiche_view
                WHERE ID IN (
                  SELECT DISTINCT p.ID
                  FROM '.$pe_o_tec.'_pratiche p
                  LEFT JOIN '.$pe_o_tec.'_fogli_mappali_pratiche fm ON p.ID = fm.Pratica '.
                  ((!empty($where))?('WHERE '.$where):'') .')
                ORDER BY Anno, Numero';

        $rs = $db->ql($sql, $params);
        
        if(!count($rs)) echo "<h2>Nessun risultato</h2>";

        echo '<div class="wrapper">';

        foreach ($rs as $row){ ?>

        <div class="content">
        <h1><?= $row['Sigla'] ?></h1>
          <div class="inner-wrap">
            <p><span class="title">Fogli-mappali:</span> <?= $row['FogliMappali'] ?></p>
            <p><span class="title">Intestatari persone:</span> <?= $row['Intestatari_persone'] ?></p>
            <p><span class="title">Intestatari societ&aacute;:</span> <?= $row['Intestatari_societa'] ?></p>
            <form action="reports/pratica<?= strtoupper($pe_o_tec) ?>.php" method="post">
                <button name="id" class="formBtn" value="<?= $row['ID'] ?>">Visualizza</button>
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
       */
      public static function modificaPratiche($db, $tipo = '', $anno = '', $numero = '', $barrato = '', $foglio = '', $mappale = '', $pe_o_tec = 'pe'){
          $where = [];
          $params = [];
          if(!empty($tipo)){
              $where[] = 'p.Tipo = :tipo';
              $params[':tipo'] = $tipo;
          }
          if(!empty($anno)){
              $where[] = 'p.Anno = :anno';
              $params[':anno'] = $anno;
          }
          if(!empty($numero)){
              $where[] = 'p.Numero = :numero';
              $params[':numero'] = $numero;
          }
          if(!empty($barrato)){
              $where[] = 'p.Barrato = :barrato';
              $params[':barrato'] = $barrato;
          }
          if(!empty($foglio)){
              $where[] = 'fm.Foglio = :foglio';
              $params[':foglio'] = $foglio;
          }
          if(!empty($mappale)){
              $where[] = 'fm.Mappale = :mappale';
              $params[':mappale'] = $mappale;
          }
          $where = implode(' AND ', $where);
          
          $sql = 'SELECT ID, Sigla, TIPO, Anno, Numero, FogliMappali, Intestatari_persone, Intestatari_societa
                FROM '.$pe_o_tec.'_pratiche_view
                WHERE ID IN (
                  SELECT DISTINCT p.ID
                  FROM '.$pe_o_tec.'_pratiche p
                  LEFT JOIN '.$pe_o_tec.'_fogli_mappali_pratiche fm ON p.ID = fm.Pratica '.
                  ((!empty($where))?('WHERE '.$where):'') .')
                ORDER BY Anno, Numero';
                  
                  $rs = $db->ql($sql, $params);
                  
                  echo '<div class="wrapper">';
                  
                  foreach ($rs as $row){ ?>

        <div class="content">
        <h1><?= $row['Sigla'] ?></h1>
          <div class="inner-wrap">
            <p><span class="title">Fogli-mappali:</span> <?= $row['FogliMappali'] ?></p>
            <p><span class="title">Intestatari persone:</span> <?= $row['Intestatari_persone'] ?></p>
            <p><span class="title">Intestatari societ&aacute;:</span> <?= $row['Intestatari_societa'] ?></p>
            <form action="gestione/pratica<?= strtoupper($pe_o_tec) ?>.php" method="post">
                <button name="id" class="formBtn" value="<?= $row['ID'] ?>">Modifica</button>
            </form>
          </div>
        </div>

      <?php
        }
        echo '</div>';
      }
      
      
  }
