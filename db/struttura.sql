-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versione server:              10.3.10-MariaDB - mariadb.org binary distribution
-- S.O. server:                  Win64
-- HeidiSQL Versione:            10.1.0.5532
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Dump della struttura del database pe
CREATE DATABASE IF NOT EXISTS `pe` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `pe`;

-- Dump della struttura di tabella pe.belfiore
CREATE TABLE IF NOT EXISTS `belfiore` (
  `ISTAT` int(6) unsigned NOT NULL,
  `Nome` char(100) NOT NULL,
  `Sigla` char(100) DEFAULT NULL,
  `CAP` char(5) DEFAULT NULL,
  `Belfiore` char(4) NOT NULL DEFAULT '',
  PRIMARY KEY (`Belfiore`),
  UNIQUE KEY `ISTAT` (`ISTAT`),
  KEY `Nome` (`Nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.edifici
CREATE TABLE IF NOT EXISTS `edifici` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Stradario` int(6) unsigned NOT NULL,
  `Note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `Denominazione.1` (`Stradario`),
  CONSTRAINT `Denominazione.1` FOREIGN KEY (`Stradario`) REFERENCES `stradario` (`identificativo_nazionale`)
) ENGINE=InnoDB AUTO_INCREMENT=1040 DEFAULT CHARSET=utf8 COMMENT='Il valore 0 di foglio ne indica la molteplicità';

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.fogli_mappali_edifici
CREATE TABLE IF NOT EXISTS `fogli_mappali_edifici` (
  `Edificio` int(10) unsigned NOT NULL,
  `Foglio` char(4) NOT NULL,
  `Mappale` char(6) NOT NULL,
  `EX` enum('EX') DEFAULT NULL,
  PRIMARY KEY (`Foglio`,`Mappale`),
  UNIQUE KEY `Edificio` (`Edificio`,`Foglio`,`Mappale`),
  CONSTRAINT `FK_fogli_mappali_edifici_edifici` FOREIGN KEY (`Edificio`) REFERENCES `edifici` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.imprese
CREATE TABLE IF NOT EXISTS `imprese` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Intestazione` char(100) NOT NULL,
  `Codice_fiscale` char(16) DEFAULT NULL,
  `Partita_iva` char(11) DEFAULT NULL,
  `Note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Intestazione`),
  UNIQUE KEY `ID` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=379 DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.intestatari_persone
CREATE TABLE IF NOT EXISTS `intestatari_persone` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Cognome` char(60) NOT NULL,
  `Nome` char(40) NOT NULL,
  `Codice_fiscale` char(16) NOT NULL,
  `Indirizzo` char(50) DEFAULT NULL,
  `Citta` char(40) DEFAULT NULL,
  `Provincia` char(2) DEFAULT NULL,
  `Note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Codice_fiscale`),
  UNIQUE KEY `ID` (`ID`),
  KEY `Cognome` (`Cognome`,`Nome`)
) ENGINE=InnoDB AUTO_INCREMENT=1936 DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.intestatari_societa
CREATE TABLE IF NOT EXISTS `intestatari_societa` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Intestazione` char(70) NOT NULL,
  `Partita_iva` char(11) NOT NULL,
  `Indirizzo` char(50) DEFAULT NULL,
  `Citta` char(40) DEFAULT NULL,
  `Provincia` char(2) DEFAULT NULL,
  `Note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Partita_iva`),
  UNIQUE KEY `ID` (`ID`),
  KEY `Intestazione` (`Intestazione`)
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.pe_condoni
CREATE TABLE IF NOT EXISTS `pe_condoni` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Mappali` char(40) NOT NULL,
  `Anno` int(4) unsigned NOT NULL,
  `Numero` int(6) unsigned NOT NULL,
  `Data` date NOT NULL,
  `Protocollo` int(8) unsigned DEFAULT NULL,
  `Edificio` int(10) unsigned NOT NULL,
  `Stradario` int(6) unsigned NOT NULL,
  `Cognome` char(60) NOT NULL,
  `Nome` char(50) DEFAULT NULL,
  `Codice_fiscale` char(16) NOT NULL,
  `Note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Anno`,`Numero`),
  UNIQUE KEY `ID` (`ID`),
  KEY `FK_condoni_stradario` (`Stradario`),
  KEY `ID Foglio Stradario` (`Edificio`),
  CONSTRAINT `FK_condoni_stradario` FOREIGN KEY (`Stradario`) REFERENCES `stradario` (`identificativo_nazionale`),
  CONSTRAINT `ID Foglio Stradario` FOREIGN KEY (`Edificio`) REFERENCES `edifici` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.pe_edifici_pratiche
CREATE TABLE IF NOT EXISTS `pe_edifici_pratiche` (
  `Pratica` int(10) unsigned NOT NULL,
  `Edificio` int(10) unsigned NOT NULL,
  PRIMARY KEY (`Pratica`,`Edificio`),
  KEY `FK_pe_edifici_pratiche_edifici` (`Edificio`),
  CONSTRAINT `FK_pe_edifici_pratiche_edifici` FOREIGN KEY (`Edificio`) REFERENCES `edifici` (`ID`),
  CONSTRAINT `FK_pe_edifici_pratiche_pe_pratiche` FOREIGN KEY (`Pratica`) REFERENCES `pe_pratiche` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.pe_fogli_mappali_pratiche
CREATE TABLE IF NOT EXISTS `pe_fogli_mappali_pratiche` (
  `Pratica` int(10) unsigned NOT NULL,
  `Edificio` int(10) unsigned NOT NULL,
  `Foglio` char(4) NOT NULL,
  `Mappale` char(6) NOT NULL,
  UNIQUE KEY `Pratica` (`Pratica`,`Foglio`,`Mappale`),
  KEY `FK_pe_mappali_pratiche_fogli_mappali_edifici` (`Edificio`,`Foglio`,`Mappale`),
  KEY `FK_pe_mappali_pratiche_pe_pratiche` (`Pratica`,`Edificio`),
  KEY `Pratica1` (`Pratica`,`Edificio`,`Foglio`,`Mappale`),
  CONSTRAINT `FK_pe_mappali_pratiche_fogli_mappali_edifici` FOREIGN KEY (`Edificio`, `Foglio`, `Mappale`) REFERENCES `fogli_mappali_edifici` (`Edificio`, `Foglio`, `Mappale`),
  CONSTRAINT `FK_pe_mappali_pratiche_pe_edifici_pratiche` FOREIGN KEY (`Pratica`, `Edificio`) REFERENCES `pe_edifici_pratiche` (`Pratica`, `Edificio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.pe_intestatari_persone_pratiche
CREATE TABLE IF NOT EXISTS `pe_intestatari_persone_pratiche` (
  `Persona` int(10) unsigned NOT NULL,
  `Pratica` int(10) unsigned NOT NULL,
  PRIMARY KEY (`Pratica`,`Persona`),
  KEY `FK_pe_intestatari_persone_pratiche_intestatari_persone` (`Persona`),
  CONSTRAINT `FK_pe_intestatari_persone_pratiche_intestatari_persone` FOREIGN KEY (`Persona`) REFERENCES `intestatari_persone` (`ID`),
  CONSTRAINT `FK_pe_intestatari_persone_pratiche_pe_pratiche` FOREIGN KEY (`Pratica`) REFERENCES `pe_pratiche` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.pe_intestatari_rubrica
CREATE TABLE IF NOT EXISTS `pe_intestatari_rubrica` (
  `Rubrica` int(10) unsigned NOT NULL,
  `Cognome` char(50) DEFAULT NULL,
  `Nome` char(50) DEFAULT NULL,
  `Codice_fiscale` char(16) DEFAULT NULL,
  KEY `FK_intestatari_rubrica_rubrica` (`Rubrica`),
  CONSTRAINT `FK_intestatari_rubrica_rubrica` FOREIGN KEY (`Rubrica`) REFERENCES `pe_rubrica` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.pe_intestatari_societa_pratiche
CREATE TABLE IF NOT EXISTS `pe_intestatari_societa_pratiche` (
  `Societa` int(10) unsigned NOT NULL,
  `Pratica` int(10) unsigned NOT NULL,
  PRIMARY KEY (`Pratica`,`Societa`),
  KEY `FK_pe_intestatari_societa_pratiche_intestatari_societa` (`Societa`),
  CONSTRAINT `FK_pe_intestatari_societa_pratiche_intestatari_societa` FOREIGN KEY (`Societa`) REFERENCES `intestatari_societa` (`ID`),
  CONSTRAINT `FK_pe_intestatari_societa_pratiche_pe_pratiche` FOREIGN KEY (`Pratica`) REFERENCES `pe_pratiche` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.pe_pratiche
CREATE TABLE IF NOT EXISTS `pe_pratiche` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `TIPO` enum('SCIA','DIA','CIL','CILA','VARIE','PERMESSI') NOT NULL,
  `Anno` int(4) NOT NULL,
  `Numero` int(4) NOT NULL,
  `Barrato` char(12) NOT NULL DEFAULT '',
  `Data` date DEFAULT NULL,
  `Protocollo` int(8) DEFAULT NULL,
  `Stradario` int(10) unsigned DEFAULT NULL,
  `Tecnico` int(10) unsigned DEFAULT NULL,
  `Impresa` int(10) unsigned DEFAULT NULL,
  `Direzione_lavori` int(10) unsigned DEFAULT NULL,
  `Zona` varchar(255) DEFAULT NULL,
  `Intervento` varchar(255) DEFAULT NULL,
  `Data_inizio_lavori` date DEFAULT NULL,
  `Documento_elettronico` char(255) DEFAULT NULL,
  `Note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`TIPO`,`Anno`,`Numero`,`Barrato`),
  UNIQUE KEY `ID` (`ID`),
  KEY `Denominazione.5` (`Stradario`),
  KEY `Cognome Nome Codice_fiscale.6` (`Tecnico`),
  KEY `Cognome Nome Codice_fiscale.7` (`Impresa`),
  KEY `Cognome Nome Codice_fiscale.8` (`Direzione_lavori`),
  CONSTRAINT `FK_pe_pratiche_tecnici` FOREIGN KEY (`Direzione_lavori`) REFERENCES `tecnici` (`ID`),
  CONSTRAINT `FK_pe_pratiche_tecnici_2` FOREIGN KEY (`Tecnico`) REFERENCES `tecnici` (`ID`),
  CONSTRAINT `pe_pratiche_ibfk_2` FOREIGN KEY (`Impresa`) REFERENCES `imprese` (`ID`),
  CONSTRAINT `pe_pratiche_ibfk_4` FOREIGN KEY (`Stradario`) REFERENCES `stradario` (`identificativo_nazionale`)
) ENGINE=InnoDB AUTO_INCREMENT=1628 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.pe_pratiche_non_trovate
CREATE TABLE IF NOT EXISTS `pe_pratiche_non_trovate` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Edificio` char(10) DEFAULT NULL,
  `Anno` int(4) unsigned NOT NULL,
  `Numero` char(16) NOT NULL,
  `Foglio` char(4) DEFAULT NULL,
  `Mappali` char(40) NOT NULL,
  `Identificativo_nazionale` char(6) DEFAULT NULL,
  `Cognome` char(60) NOT NULL,
  `Nome` char(50) DEFAULT NULL,
  `Tipo` char(16) NOT NULL,
  `Note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Anno`,`Numero`),
  UNIQUE KEY `ID` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=235 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.pe_rubrica
CREATE TABLE IF NOT EXISTS `pe_rubrica` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Anno` int(4) NOT NULL,
  `Numero` char(10) NOT NULL,
  `Barrato` char(10) NOT NULL,
  `Edificio` int(10) unsigned DEFAULT NULL,
  `Tipo` enum('Pratica','Pratica non trovata','Licenza') NOT NULL,
  `Note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Anno`,`Numero`,`Barrato`),
  UNIQUE KEY `ID` (`ID`),
  KEY `Mappale` (`Edificio`),
  CONSTRAINT `FK_rubrica_edifici` FOREIGN KEY (`Edificio`) REFERENCES `edifici` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=2696 DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.pe_subalterni_pratiche
CREATE TABLE IF NOT EXISTS `pe_subalterni_pratiche` (
  `Pratica` int(10) unsigned NOT NULL,
  `Edificio` int(10) unsigned NOT NULL,
  `Foglio` char(4) NOT NULL,
  `Mappale` char(6) NOT NULL,
  `Subalterno` int(3) unsigned NOT NULL,
  PRIMARY KEY (`Pratica`,`Foglio`,`Mappale`,`Subalterno`),
  KEY `Edificio` (`Edificio`,`Foglio`,`Mappale`,`Subalterno`),
  KEY `FK_pe_subalterni_pratiche_pe_fogli_mappali_pratiche` (`Pratica`,`Edificio`,`Foglio`,`Mappale`),
  CONSTRAINT `FK_pe_subalterni_pratiche_pe_edifici_pratiche` FOREIGN KEY (`Pratica`, `Edificio`) REFERENCES `pe_edifici_pratiche` (`Pratica`, `Edificio`),
  CONSTRAINT `FK_pe_subalterni_pratiche_pe_fogli_mappali_pratiche` FOREIGN KEY (`Pratica`, `Edificio`, `Foglio`, `Mappale`) REFERENCES `pe_fogli_mappali_pratiche` (`Pratica`, `Edificio`, `Foglio`, `Mappale`),
  CONSTRAINT `FK_pe_subalterni_pratiche_subalterni_edifici` FOREIGN KEY (`Edificio`, `Foglio`, `Mappale`, `Subalterno`) REFERENCES `subalterni_edifici` (`Edificio`, `Foglio`, `Mappale`, `Subalterno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.
-- Dump della struttura di tabella pe.stradario
CREATE TABLE IF NOT EXISTS `stradario` (
  `Identificativo_nazionale` int(6) unsigned NOT NULL,
  `Denominazione` char(60) NOT NULL,
  PRIMARY KEY (`Identificativo_nazionale`,`Denominazione`),
  UNIQUE KEY `Identificativo_nazionale` (`Identificativo_nazionale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.subalterni_edifici
CREATE TABLE IF NOT EXISTS `subalterni_edifici` (
  `Edificio` int(10) unsigned NOT NULL,
  `Foglio` char(4) NOT NULL,
  `Mappale` char(6) NOT NULL,
  `Subalterno` int(3) unsigned NOT NULL,
  PRIMARY KEY (`Edificio`,`Foglio`,`Mappale`,`Subalterno`),
  UNIQUE KEY `Foglio` (`Foglio`,`Mappale`,`Subalterno`),
  CONSTRAINT `FK_subalterni_edifici_fogli_mappali_edifici` FOREIGN KEY (`Edificio`, `Foglio`, `Mappale`) REFERENCES `fogli_mappali_edifici` (`Edificio`, `Foglio`, `Mappale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.tecnici
CREATE TABLE IF NOT EXISTS `tecnici` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Cognome` varchar(30) NOT NULL,
  `Nome` varchar(30) NOT NULL,
  `Codice_fiscale` varchar(16) NOT NULL,
  `Partita_iva` varchar(11) DEFAULT NULL,
  `Albo` varchar(30) DEFAULT NULL,
  `Numero_ordine` int(6) unsigned DEFAULT NULL,
  `Provncia_albo` varchar(2) DEFAULT NULL,
  `Indirizzo` varchar(60) DEFAULT NULL,
  `Citta` varchar(40) DEFAULT NULL,
  `Provincia` varchar(2) DEFAULT NULL,
  `Note` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`Codice_fiscale`),
  UNIQUE KEY `Tec-ID` (`ID`),
  KEY `Cognome` (`Cognome`,`Nome`)
) ENGINE=InnoDB AUTO_INCREMENT=292 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.tec_edifici_pratiche
CREATE TABLE IF NOT EXISTS `tec_edifici_pratiche` (
  `Pratica` char(10) NOT NULL,
  `Edificio` int(10) unsigned NOT NULL,
  PRIMARY KEY (`Edificio`,`Pratica`),
  KEY `FK_tec_edifici_pratiche_tec_pratiche` (`Pratica`),
  CONSTRAINT `FK_tec_edifici_pratiche_edifici` FOREIGN KEY (`Edificio`) REFERENCES `edifici` (`ID`),
  CONSTRAINT `FK_tec_edifici_pratiche_tec_pratiche` FOREIGN KEY (`Pratica`) REFERENCES `tec_pratiche` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.tec_fogli_mappali_pratiche
CREATE TABLE IF NOT EXISTS `tec_fogli_mappali_pratiche` (
  `Pratica` char(10) NOT NULL,
  `Edificio` int(10) unsigned NOT NULL,
  `Foglio` char(4) NOT NULL,
  `Mappale` char(6) NOT NULL,
  `Superficie` int(10) unsigned DEFAULT NULL,
  `Zona_omogenea` varchar(20) DEFAULT NULL,
  UNIQUE KEY `Pratica` (`Pratica`,`Foglio`,`Mappale`),
  KEY `FK_tec_mappali_pratiche_tec_pratiche` (`Pratica`,`Edificio`),
  KEY `FK_tec_mappali_pratiche_fogli_mappali_edifici` (`Edificio`,`Foglio`,`Mappale`),
  CONSTRAINT `FK_tec_mappali_pratiche_fogli_mappali_edifici` FOREIGN KEY (`Edificio`, `Foglio`, `Mappale`) REFERENCES `fogli_mappali_edifici` (`Edificio`, `Foglio`, `Mappale`),
  CONSTRAINT `FK_tec_mappali_pratiche_tec_pratiche` FOREIGN KEY (`Pratica`, `Edificio`) REFERENCES `tec_edifici_pratiche` (`Pratica`, `Edificio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Vari mappali sono identificati con 0 (VARI, STRADE)';

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.tec_intestatari_persone_pratiche
CREATE TABLE IF NOT EXISTS `tec_intestatari_persone_pratiche` (
  `Persona` int(10) unsigned NOT NULL,
  `Pratica` char(10) NOT NULL,
  `Note` char(10) DEFAULT NULL,
  PRIMARY KEY (`Pratica`,`Persona`),
  KEY `FK_tec_intestatari_persone_pratiche_new_intestatari_persone` (`Persona`),
  CONSTRAINT `FK_tec_intestatari_persone_pratiche_intestatari_persone` FOREIGN KEY (`Persona`) REFERENCES `intestatari_persone` (`ID`),
  CONSTRAINT `FK_tec_intestatari_persone_pratiche_tec_pratiche` FOREIGN KEY (`Pratica`) REFERENCES `tec_pratiche` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.tec_intestatari_societa_pratiche
CREATE TABLE IF NOT EXISTS `tec_intestatari_societa_pratiche` (
  `Societa` int(10) unsigned NOT NULL,
  `Pratica` char(10) NOT NULL,
  `Note` char(10) DEFAULT NULL,
  PRIMARY KEY (`Pratica`,`Societa`),
  KEY `Societa` (`Societa`),
  CONSTRAINT `FK_tec_intestatari_societa_pratiche_societa` FOREIGN KEY (`Societa`) REFERENCES `intestatari_societa` (`ID`),
  CONSTRAINT `FK_tec_intestatari_societa_pratiche_tec_pratiche` FOREIGN KEY (`Pratica`) REFERENCES `tec_pratiche` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.tec_pagamenti_cc
CREATE TABLE IF NOT EXISTS `tec_pagamenti_cc` (
  `Pratica` char(10) NOT NULL,
  `Importo` decimal(10,2) unsigned NOT NULL,
  `Data` date DEFAULT NULL,
  KEY `FK_tec_pagamenti_cc_new_tec_pratiche` (`Pratica`),
  CONSTRAINT `FK_tec_pagamenti_cc_new_tec_pratiche` FOREIGN KEY (`Pratica`) REFERENCES `tec_pratiche` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.tec_pagamenti_ou
CREATE TABLE IF NOT EXISTS `tec_pagamenti_ou` (
  `Pratica` char(10) NOT NULL,
  `Importo` decimal(10,2) unsigned NOT NULL,
  `Data` date DEFAULT NULL,
  KEY `FK_tec_pagamenti_ou_new_tec_pratiche` (`Pratica`),
  CONSTRAINT `FK_tec_pagamenti_ou_new_tec_pratiche` FOREIGN KEY (`Pratica`) REFERENCES `tec_pratiche` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.tec_pratiche
CREATE TABLE IF NOT EXISTS `tec_pratiche` (
  `ID` char(10) NOT NULL,
  `Oggetto` varchar(300) DEFAULT NULL,
  `Tipologia_fabbricato` int(11) DEFAULT NULL,
  `COD_INT` int(11) DEFAULT NULL,
  `Stradario` int(6) unsigned NOT NULL,
  `Civico` varchar(6) DEFAULT NULL,
  `Data_domanda` date DEFAULT NULL,
  `N_protocollo` varchar(6) DEFAULT NULL,
  `N_verbale` varchar(6) DEFAULT NULL,
  `Verbale` text DEFAULT NULL,
  `Prescrizioni` text DEFAULT NULL,
  `Parere` text DEFAULT NULL,
  `Parere_Note` text DEFAULT NULL,
  `Approvata` varchar(1) DEFAULT NULL,
  `Onerosa` varchar(1) DEFAULT NULL,
  `Beni_Ambientali` varchar(3) DEFAULT NULL,
  `Pratica_Note` text DEFAULT NULL,
  `Pagam_Note` varchar(112) DEFAULT NULL,
  `DATA_RDA` date DEFAULT NULL,
  `DATA_SDA` date DEFAULT NULL,
  `DATA_PT` date DEFAULT NULL,
  `DATA_PUS` date DEFAULT NULL,
  `DATA_CE` date DEFAULT NULL,
  `DATA_CON` date DEFAULT NULL,
  `DATA_CD` date DEFAULT NULL,
  `DATA_RIT` date DEFAULT NULL,
  `DATA_SRP` date DEFAULT NULL,
  `DATA_INIZ` date DEFAULT NULL,
  `DATA_FINE` date DEFAULT NULL,
  `DATA_ILC` date DEFAULT NULL,
  `DATA_FLC` date DEFAULT NULL,
  `DATA_RAA` date DEFAULT NULL,
  `DATA_SDT` date DEFAULT NULL,
  `DATA_AA` date DEFAULT NULL,
  `DATA_ARC` date DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `FK_tec_pratiche_stradario` (`Stradario`),
  CONSTRAINT `FK_tec_pratiche_stradario` FOREIGN KEY (`Stradario`) REFERENCES `stradario` (`Identificativo_nazionale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.tec_subalterni_pratiche
CREATE TABLE IF NOT EXISTS `tec_subalterni_pratiche` (
  `Pratica` char(10) NOT NULL,
  `Edificio` int(10) unsigned NOT NULL,
  `Foglio` char(4) NOT NULL,
  `Mappale` char(6) NOT NULL,
  `Subalterno` int(3) unsigned NOT NULL,
  PRIMARY KEY (`Pratica`,`Foglio`,`Mappale`,`Subalterno`),
  KEY `Edificio` (`Edificio`,`Foglio`,`Mappale`,`Subalterno`),
  KEY `FK_tec_subalterni_pratiche_tec_fogli_mappali_pratiche` (`Pratica`,`Edificio`,`Foglio`,`Mappale`),
  CONSTRAINT `FK_tec_subalterni_pratiche_subalterni_edifici` FOREIGN KEY (`Edificio`, `Foglio`, `Mappale`, `Subalterno`) REFERENCES `subalterni_edifici` (`Edificio`, `Foglio`, `Mappale`, `Subalterno`),
  CONSTRAINT `FK_tec_subalterni_pratiche_tec_edifici_pratiche` FOREIGN KEY (`Pratica`, `Edificio`) REFERENCES `tec_edifici_pratiche` (`Pratica`, `Edificio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.tec_tecnici_pratiche
CREATE TABLE IF NOT EXISTS `tec_tecnici_pratiche` (
  `Tecnico` int(10) unsigned NOT NULL,
  `Pratica` char(10) NOT NULL,
  `Tipo` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`Pratica`,`Tecnico`),
  KEY `FK_tec_tecnici_pratiche_new_tecnici` (`Tecnico`),
  CONSTRAINT `FK_tec_tecnici_pratiche_tec_pratiche` FOREIGN KEY (`Pratica`) REFERENCES `tec_pratiche` (`ID`),
  CONSTRAINT `FK_tec_tecnici_pratiche_tecnici` FOREIGN KEY (`Tecnico`) REFERENCES `tecnici` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='CAPIRE COSA METTERE AL POSTO DELLA COLONNA TIPO';

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di tabella pe.utenti
CREATE TABLE IF NOT EXISTS `utenti` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(100) NOT NULL,
  `Type` enum('ADMIN','USER') NOT NULL DEFAULT 'USER',
  `Active` enum('1','0') NOT NULL DEFAULT '0',
  PRIMARY KEY (`Email`),
  UNIQUE KEY `ID` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- L’esportazione dei dati non era selezionata.

-- Dump della struttura di vista pe.edifici_view
-- Rimozione temporanea di tabella e creazione della struttura finale della vista
CREATE VIEW `edifici_view` AS SELECT e.ID ID, s.Denominazione Stradario, e.Note Note,
		(SELECT GROUP_CONCAT(DISTINCT fm.Foglio ORDER BY fm.Foglio)
		FROM fogli_mappali_edifici fm
		GROUP BY fm.Edificio
		HAVING fm.Edificio = e.ID) Fogli,
		(SELECT GROUP_CONCAT(CONCAT( 'F.',fm.Foglio,
									' m.', fm.Mappale,
									IF(fm.EX IS NOT NULL, ' (ex)', ''))
							ORDER BY fm.Foglio, fm.Mappale
							SEPARATOR ', ')
		FROM fogli_mappali_edifici fm
		GROUP BY fm.Edificio
		HAVING fm.Edificio = e.ID) Mappali,
		(SELECT GROUP_CONCAT(CONCAT('Sub.', se.Subalterno,
									' F.', se.Foglio,
									' m.', se.Mappale,
									IF(fm.EX IS NOT NULL, ' (ex)', ''))
							ORDER BY fm.Foglio, fm.Mappale
							SEPARATOR ', ')
		FROM subalterni_edifici se
		JOIN fogli_mappali_edifici fm
			ON fm.Edificio = se.Edificio
			AND fm.Foglio = se.Foglio
			AND fm.Mappale = se.Mappale
		GROUP BY se.Edificio
		HAVING se.Edificio = e.ID) Subalterni
FROM edifici e
JOIN stradario s ON s.Identificativo_nazionale = e.Stradario ;

-- Dump della struttura di vista pe.pratiche_view
-- Rimozione temporanea di tabella e creazione della struttura finale della vista
CREATE VIEW `pe_pratiche_view` AS
SELECT  p.ID,
		p.TIPO Tipo,
		p.Anno,
		p.Numero,
		p.Barrato,
		`Data`,
		Protocollo,
		Tecnico,
		Impresa,
		Direzione_lavori,
		Zona,
		Intervento,
		Data_inizio_lavori,
		Documento_elettronico,
		Note,

		CONCAT(p.TIPO, p.Anno, '/', p.Numero, p.Barrato) Sigla,
		IF(s.Denominazione IS NULL, '', s.Denominazione) Stradario,

		(SELECT GROUP_CONCAT(CONCAT( 'F.',fm.Foglio,
									' m.', fm.Mappale,
									IF(fm.EX IS NOT NULL, ' (ex)', ''))
							ORDER BY fm.Foglio, fm.Mappale
							SEPARATOR ', ')
		FROM pe_fogli_mappali_pratiche fmp
		JOIN fogli_mappali_edifici fm
			ON fm.Edificio = fmp.Edificio
			AND fm.Foglio = fmp.Foglio
			AND fm.Mappale = fmp.Mappale
		GROUP BY fmp.Pratica
		HAVING fmp.Pratica = p.ID) FogliMappali,

		(SELECT GROUP_CONCAT(CONCAT('Sub.', sp.Subalterno,
									' F.', sp.Foglio,
									' m.', sp.Mappale,
									IF(fm.EX IS NOT NULL, ' (ex)', ''))
							ORDER BY fm.Foglio, fm.Mappale
							SEPARATOR ', ')
		FROM pe_subalterni_pratiche sp
		JOIN fogli_mappali_edifici fm
			ON fm.Edificio = sp.Edificio
			AND fm.Foglio = sp.Foglio
			AND fm.Mappale = sp.Mappale
		GROUP BY sp.Pratica
		HAVING sp.Pratica = p.ID) Subalterni,

		(SELECT GROUP_CONCAT(CONCAT(ip.Cognome, ' ', ip.Nome) SEPARATOR ', ')
		FROM pe_intestatari_persone_pratiche ipp
		JOIN intestatari_persone ip ON ip.ID = ipp.Persona
		WHERE ipp.Pratica = p.ID) Intestatari_persone,

		(SELECT GROUP_CONCAT(i.Intestazione SEPARATOR ', ')
		FROM pe_intestatari_societa_pratiche isp
		JOIN intestatari_societa i ON i.ID = isp.Societa
		WHERE isp.Pratica = p.ID) Intestatari_societa

FROM pe_pratiche p
LEFT JOIN stradario s ON p.Stradario = s.Identificativo_nazionale;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
