<?php
/* Copyright (C) 2015 FH Technikum-Wien
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 */
/**
 * FH-Complete Addon Template Datenbank Check
 *
 * Prueft und aktualisiert die Datenbank
 */
$basepath = dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME']))).DIRECTORY_SEPARATOR;
require_once($basepath.'config/system.config.inc.php');
require_once($basepath.'include/basis_db.class.php');
require_once($basepath.'include/berechtigung.class.php');
require_once($basepath.'include/dms.class.php');
require_once($basepath.'include/functions.inc.php');
require_once('include/wawi_benutzerberechtigung.class.php');

// Datenbank Verbindung
$db = new basis_db();

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../skin/vilesci.css" type="text/css">
	<title>Addon Datenbank Check</title>
</head>
<body>
<h1>Addon Datenbank Check</h1>';

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt('basis/addon'))
{
	exit('Sie haben keine Berechtigung für die Verwaltung von Addons');
}

echo '<h2>Aktualisierung der Datenbank</h2>';

// Code fuer die Datenbankanpassungen
$tabellen = array();




$schemaName = "wawi";
$tableName = "tbl_bestellung_kategorie";
$tabellen[$schemaName.'.'.$tableName] = array("bkategorie_id","beschreibung","kommentar", "aktiv","insertamum","insertvon","updateamum","updatevon");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = wawi, pg_catalog;
                
                CREATE TABLE $tableName (
                    bkategorie_id bigint NOT NULL,                  
                    beschreibung character varying(256),
                    kommentar text,
                    aktiv boolean NOT NULL,
                    insertamum timestamp without time zone,
                    insertvon character varying(32),
                    updateamum timestamp without time zone,
                    updatevon character varying(32)
		);                                
                 
                CREATE SEQUENCE $sequenceName
		 INCREMENT BY 1
		 NO MAXVALUE
		 NO MINVALUE
		 CACHE 1;
                 
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_bkategorie_id_pkey PRIMARY KEY (bkategorie_id);
                ALTER TABLE $tableName 
                    ALTER COLUMN bkategorie_id SET DEFAULT nextval('$sequenceName');
                
                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
		GRANT SELECT, UPDATE ON $sequenceName TO vilesci;
                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else
        {
		echo " $tableName: Tabelle $tableName hinzugefuegt!<br>";
                $qry="insert into $tableName(beschreibung,kommentar,aktiv,insertamum) values('GWG','Geringwertiges Wirtschaftsgut bis EUR 2.999',true,now())";
                if(!$db->db_query($qry))
                    echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
                $qry="insert into $tableName(beschreibung,kommentar,aktiv,insertamum) values('Investition','Gebrauchsgut ab EUR 3.000',true,now())";
                if(!$db->db_query($qry))
                    echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
                $qry="insert into $tableName(beschreibung,kommentar,aktiv,insertamum) values('Verbrauchsgut','kurzlebiges Gut, wird bei Benützung idR verbraucht (z.N. Hygienematerial)',true,now())";
                if(!$db->db_query($qry))
                    echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
                $qry="insert into $tableName(beschreibung,kommentar,aktiv,insertamum) values('Labormaterial','Klein-/Bauteile, Laborzubehör, Flüssigkeiten; für das Arbeiten im Labor',true,now())";
                if(!$db->db_query($qry))
                    echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
                $qry="insert into $tableName(beschreibung,kommentar,aktiv,insertamum) values('Büro-, EDV-, Unterrichtsmaterial','',true,now())";
                if(!$db->db_query($qry))
                    echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
                $qry="insert into $tableName(beschreibung,kommentar,aktiv,insertamum) values('Software/Lizenzen','',true,now())";
                if(!$db->db_query($qry))
                    echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
                $qry="insert into $tableName(beschreibung,kommentar,aktiv,insertamum) values('Miet-/Leasingaufwand','',true,now())";
                if(!$db->db_query($qry))
                    echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
                $qry="insert into $tableName(beschreibung,kommentar,aktiv,insertamum) values('Reise-Aufwand','',true,now())";
                if(!$db->db_query($qry))
                    echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
                $qry="insert into $tableName(beschreibung,kommentar,aktiv,insertamum) values('Sonstiger Aufwand','',true,now())";
                if(!$db->db_query($qry))
                    echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
        }
}


$schemaName = "wawi";
$tableName = "tbl_konto";
$tabellen[$schemaName.'.'.$tableName] = array("konto_id","kontonr","beschreibung","kurzbz", "aktiv","insertamum","insertvon","updateamum","updatevon","person_id","ext_id");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = wawi, pg_catalog;
                
                CREATE TABLE $tableName (
                    konto_id bigint bigint NOT NULL,
                    kontonr character varying(32),
                    beschreibung character varying(256)[],
                    kurzbz character varying(32),
                    aktiv boolean NOT NULL,
                    insertamum timestamp without time zone,
                    insertvon character varying(32),
                    updateamum timestamp without time zone,
                    updatevon character varying(32),
                    person_id bigint,
                    ext_id bigint
		);
                
                CREATE SEQUENCE $sequenceName
		 INCREMENT BY 1
		 NO MAXVALUE
		 NO MINVALUE
		 CACHE 1;
                 
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tablename}_konto_id_pkey PRIMARY KEY (konto_id);
                ALTER TABLE $tableName 
                    ALTER COLUMN konto_id SET DEFAULT nextval('$sequenceName');

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
		GRANT SELECT, UPDATE ON $sequenceName TO vilesci;
                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}


$schemaName = "wawi";
$tableName = "tbl_kostenstelle";
$tabellen[$schemaName.'.'.$tableName] = array("kostenstelle_id","oe_kurzbz","bezeichnung","kurzbz","aktiv","updateamum","updatevon","insertamum","insertvon","ext_id","kostenstelle_nr","deaktiviertvon","deaktiviertamum");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = wawi, pg_catalog;
                
                CREATE TABLE $tableName (
                    kostenstelle_id bigint NOT NULL,
                    oe_kurzbz character varying(32),
                    bezeichnung character varying(256),
                    kurzbz character varying(32),
                    aktiv boolean NOT NULL,
                    updateamum timestamp without time zone,
                    updatevon character varying(32),
                    insertamum timestamp without time zone,
                    insertvon character varying(32),
                    ext_id bigint,
                    kostenstelle_nr character varying(4),
                    deaktiviertvon character varying(32),
                    deaktiviertamum timestamp without time zone
		);
                
                CREATE SEQUENCE $sequenceName
		 INCREMENT BY 1
		 NO MAXVALUE
		 NO MINVALUE
		 CACHE 1;
                 
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_kostenstelle_id_pkey PRIMARY KEY (kostenstelle_id);
                ALTER TABLE $tableName 
                    ALTER COLUMN konto_id SET DEFAULT nextval('$sequenceName');
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_oe_kurzbz_fkey FOREIGN KEY (oe_kurzbz) REFERENCES public.tbl_organisationseinheit(oe_kurzbz) ON UPDATE CASCADE ON DELETE RESTRICT;

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
		GRANT SELECT, UPDATE ON $sequenceName TO vilesci;
                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}


$schemaName = "wawi";
$tableName = "tbl_bestellung";
$tabellen[$schemaName.'.'.$tableName] = array("bestellung_id","bestell_nr","titel",
    "bemerkung","liefertermin","besteller_uid","lieferadresse",
    "kostenstelle_id","konto_id","rechnungsadresse","firma_id","freigegeben",
    "updateamum","updatevon","insertamum","insertvon","ext_id",
    "zahlungstyp_kurzbz");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    bestellung_id bigint NOT NULL,
                    bestell_nr character varying(16),
                    titel character varying(256),
                    bemerkung character varying(256),
                    liefertermin character varying(16),
                    besteller_uid character varying(32),
                    lieferadresse bigint,
                    kostenstelle_id bigint,
                    konto_id bigint,
                    rechnungsadresse bigint,
                    firma_id bigint,
                    freigegeben boolean DEFAULT false NOT NULL,
                    updateamum timestamp without time zone,
                    updatevon character varying(32),
                    insertamum timestamp without time zone,
                    insertvon character varying(32),
                    ext_id bigint,
                    zahlungstyp_kurzbz character varying(32),
                    auftragsbestaetigung date,
                    auslagenersatz boolean default false,
                    iban varchar(50),
                    leasing boolean default false,
                    leasing_txt varchar(255)
                );
                
                CREATE SEQUENCE $sequenceName
                    START WITH 1
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    CACHE 1;
                 
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_bestellung_id_pkey PRIMARY KEY (bestellung_id);
                ALTER TABLE $tableName 
                    ALTER COLUMN konto_id SET DEFAULT nextval('$sequenceName');
                        
                CREATE INDEX ${tableName}_freigegeben_id_idx ON $tableName USING btree (freigegeben_id);
                CREATE INDEX ${tableName}_kostenstelle_id_idx ON $tableName USING btree (kostenstelle_id);
                    
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_besteller_uid_fkey FOREIGN KEY (besteller_uid) REFERENCES public.tbl_benutzer(uid) ON UPDATE CASCADE ON DELETE RESTRICT;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_firma_id_fkey FOREIGN KEY (firma_id) REFERENCES public.tbl_firma(firma_id) ON UPDATE CASCADE ON DELETE RESTRICT;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_kostenstelle_id_fkey FOREIGN KEY (kostenstelle_id) REFERENCES tbl_kostenstelle(kostenstelle_id) ON UPDATE CASCADE ON DELETE RESTRICT;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_konto_id_fkey FOREIGN KEY (konto_id) REFERENCES tbl_konto(konto_id) ON UPDATE CASCADE ON DELETE RESTRICT;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_lieferadresse_fkey FOREIGN KEY (lieferadresse) REFERENCES public.tbl_adresse(adresse_id) ON UPDATE CASCADE ON DELETE RESTRICT;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_rechnungsadresse_fkey FOREIGN KEY (rechnungsadresse) REFERENCES public.tbl_adresse(adresse_id) ON UPDATE CASCADE ON DELETE RESTRICT;        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_zahlungstyp_kurzbz_fkey FOREIGN KEY (zahlungstyp_kurzbz) REFERENCES tbl_zahlungstyp(zahlungstyp_kurzbz) ON UPDATE CASCADE ON DELETE CASCADE;        

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
		GRANT SELECT, UPDATE ON $sequenceName TO vilesci;

                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}
else 
{
    // Migration zu AddOn
    
    $schemaName = "wawi";
    $columnName = 'zuordnung_uid';
    print "Erstelle Attribute für Tabelle $tableName:";
    if (!columnExists($schemaName, $tableName, $columnName))
    {
        $qry = "alter table $schemaName.$tableName add column $columnName varchar(32);";
        if(!$db->db_query($qry))
		echo "<strong>$columnName: '.$db->db_last_error().'</strong><br>";
	else 
		echo " $columnName: Attribut $columnName hinzugefuegt!<br>";
    }
    else
    {
        print "<br>Attribut $columnName exitiert bereits";
    }
  
    $columnName = 'zuordnung_raum';
    if (!columnExists($schemaName, $tableName, $columnName))
    {
        $qry = "alter table $schemaName.$tableName add column $columnName varchar(256);";
        if(!$db->db_query($qry))
		echo '<strong>$columnName: '.$db->db_last_error().'</strong><br>';
	else 
		echo " $columnName: Attribut $columnName hinzugefuegt!<br>";
    }
    else
    {
        print "<br>Attribut $columnName exitiert bereits";
    }
    
    $columnName = 'bkategorie_id';
    if (!columnExists($schemaName, $tableName, $columnName))
    {
        $qry = "alter table $schemaName.$tableName add column $columnName bigint;
            
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_bkategorie_id_fkey FOREIGN KEY (bkategorie_id) REFERENCES tbl_bestellung_kategorie(bkategorie_id) ON UPDATE CASCADE ON DELETE CASCADE;        

                . ";
        if(!$db->db_query($qry))
		echo '<strong>$columnName: '.$db->db_last_error().'</strong><br>';
	else 
		echo " $columnName: Attribut $columnName hinzugefuegt!<br>";
    }
    else
    {
        print "<br>Attribut $columnName exitiert bereits";
    }
    
    $columnName = 'zuordnung';
    if (!columnExists($schemaName, $tableName, $columnName))
    {
        $qry = "alter table $schemaName.$tableName add column $columnName varchar(256);";
        if(!$db->db_query($qry))
		echo '<strong>$columnName: '.$db->db_last_error().'</strong><br>';
	else 
		echo " $columnName: Attribut $columnName hinzugefuegt!<br>";
    }
    else
    {
        print "<br>Attribut $columnName exitiert bereits";
    }
}

$schemaName = "wawi";
$tableName = "tbl_bestellung_angebot";
$tabellen[$schemaName.'.'.$tableName] = array("angebot_id","bestellung_id","dms_id");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    angebot_id bigint NOT NULL,
                    bestellung_id bigint NOT NULL,
                    dms_id bigint NOT NULL
                );
                
                CREATE SEQUENCE $sequenceName
                    START WITH 1
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    CACHE 1;
                 
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_angebot_id_pkey PRIMARY KEY (angebot_id);
                ALTER TABLE $tableName 
                    ALTER COLUMN angebot_id SET DEFAULT nextval('$sequenceName');
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_bestellung_id_fkey FOREIGN KEY (bestellung_id) REFERENCES tbl_bestellung(bestellung_id) ON UPDATE CASCADE ON DELETE CASCADE;        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_dms_id_fkey FOREIGN KEY (dms_id) REFERENCES campus.tbl_dms(dms_id) ON UPDATE CASCADE ON DELETE CASCADE;        

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
		GRANT SELECT, UPDATE ON $sequenceName TO vilesci;

                ";

	if(!$db->db_query($qry))
		echo "<strong>$tableName: ".$db->db_last_error()."</strong><br>";
	else 
		echo " $tableName: Tabelle $tableName hinzugefuegt!<br>";

}


$schemaName = "wawi";
$tableName = "tbl_aufteilung";
$tabellen[$schemaName.'.'.$tableName] = array("aufteilung_id","bestellung_id","oe_kurzbz","anteil", "insertamum","insertvon","updateamum","updatevon");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    aufteilung_id bigint NOT NULL,
                    bestellung_id bigint NOT NULL,
                    oe_kurzbz character varying(32) NOT NULL,
                    anteil numeric(5,2),
                    insertamum timestamp without time zone,
                    insertvon character varying(32),
                    updateamum timestamp without time zone,
                    updatevon character varying(32)
                );
                
                CREATE SEQUENCE $sequenceName
                    START WITH 1
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    CACHE 1;
                 
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_aufteilung_id_pkey PRIMARY KEY (aufteilung_id);
                ALTER TABLE $tableName 
                    ALTER COLUMN aufteilung_id SET DEFAULT nextval('$sequenceName');
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_bestellung_id_fkey FOREIGN KEY (bestellung_id) REFERENCES tbl_bestellung(bestellung_id) ON UPDATE CASCADE ON DELETE CASCADE;        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_oe_kurzbz_fkey FOREIGN KEY (oe_kurzbz) REFERENCES public.tbl_organisationseinheit(oe_kurzbz) ON UPDATE CASCADE ON DELETE CASCADE;        

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
		GRANT SELECT, UPDATE ON $sequenceName TO vilesci;

                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}


$schemaName = "wawi";
$tableName = "tbl_aufteilung_default";
$tabellen[$schemaName.'.'.$tableName] = array("aufteilung_id","kostenstelle_id","oe_kurzbz","anteil", "insertamum","insertvon","updateamum","updatevon");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    aufteilung_id bigint NOT NULL,
                    kostenstelle_id bigint NOT NULL,
                    oe_kurzbz character varying(32) NOT NULL,
                    anteil numeric(5,2),
                    insertamum timestamp without time zone,
                    insertvon character varying(32),
                    updateamum timestamp without time zone,
                    updatevon character varying(32)
                );
                
                CREATE SEQUENCE $sequenceName
                    START WITH 1
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    CACHE 1;
                 
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_aufteilung_id_pkey PRIMARY KEY (aufteilung_id);
                ALTER TABLE $tableName 
                    ALTER COLUMN aufteilung_id SET DEFAULT nextval('$sequenceName');
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_kostenstelle_id_fkey FOREIGN KEY (kostenstelle_id) REFERENCES tbl_kostenstelle(kostenstelle_id) ON UPDATE CASCADE ON DELETE CASCADE;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_oe_kurzbz_fkey FOREIGN KEY (oe_kurzbz) REFERENCES public.tbl_organisationseinheit(oe_kurzbz) ON UPDATE CASCADE ON DELETE CASCADE;        

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
		GRANT SELECT, UPDATE ON $sequenceName TO vilesci;

                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}



$schemaName = "wawi";
$tableName = "tbl_bestelldetail";
$tabellen[$schemaName.'.'.$tableName] = array("bestelldetail_id","bestellung_id","position",
                    "menge","verpackungseinheit","beschreibung","artikelnummer","preisprove","mwst",
                    "erhalten","sort","text","insertamum","insertvon","updateamum","updatevon");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    bestelldetail_id bigint NOT NULL,
                    bestellung_id bigint NOT NULL,
                    \"position\" integer,
                    menge integer,
                    verpackungseinheit character varying(16),
                    beschreibung text,
                    artikelnummer character varying(32),
                    preisprove numeric(12,4),
                    mwst numeric(4,2),
                    erhalten boolean NOT NULL,
                    sort integer,
                    \"text\" boolean NOT NULL,
                    insertamum timestamp without time zone,
                    insertvon character varying(32),
                    updateamum timestamp without time zone,
                    updatevon character varying(32)
                );
                
                CREATE SEQUENCE $sequenceName
                    START WITH 1
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    CACHE 1;
                 
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_bestelldetail_id_pkey PRIMARY KEY (bestelldetail_id);
                ALTER TABLE $tableName 
                    ALTER COLUMN bestelldetail_id SET DEFAULT nextval('$sequenceName');
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_bestellung_id_fkey FOREIGN KEY (bestellung_id) REFERENCES tbl_bestellung(bestellung_id) ON UPDATE CASCADE ON DELETE CASCADE;                

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
		GRANT SELECT, UPDATE ON $sequenceName TO vilesci;

                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}


$schemaName = "wawi";
$tableName = "tbl_bestelldetailtag";
$tabellen[$schemaName.'.'.$tableName] = array("tag","bestelldetail_id","insertamum","insertvon");
if(!tableExists($schemaName,$tableName))
{
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    tag character varying(128) NOT NULL,
                    bestelldetail_id bigint NOT NULL,
                    insertamum timestamp without time zone,
                    insertvon character varying(32)
                );
                
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_tag_pkey PRIMARY KEY (tag);

                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_bestelldetail_id_fkey FOREIGN KEY (bestellung_id) REFERENCES tbl_bestelldetail(bestelldetail_id) ON UPDATE CASCADE ON DELETE CASCADE;                
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_tag_fkey FOREIGN KEY (tag) REFERENCES public.tbl_tag(tag) ON UPDATE CASCADE ON DELETE CASCADE;
                

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
	

                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}


$schemaName = "wawi";
$tableName = "tbl_bestellstatus";
$tabellen[$schemaName.'.'.$tableName] = array("bestellstatus_kurzbz","beschreibung");
if(!tableExists($schemaName,$tableName))
{
        
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    bestellstatus_kurzbz character varying(32) NOT NULL,
                    beschreibung character varying(256)
                );
                
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_tag_pkey PRIMARY KEY (bestellstatus_kurzbz);
                
                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
                    
                INSERT INTO $tableName(bestellstatus_kurzbz,beschreibung) VALUES('Freigabe','Freigabe der Bestellung');
                INSERT INTO $tableName(bestellstatus_kurzbz,beschreibung) VALUES('Storno','Stornierung einer Bestellung');
                INSERT INTO $tableName(bestellstatus_kurzbz,beschreibung) VALUES('Lieferung','Ware wurde geliefert');
                INSERT INTO $tableName(bestellstatus_kurzbz,beschreibung) VALUES('Bestellung','Ware wurde bestellt');
                INSERT INTO $tableName(bestellstatus_kurzbz,beschreibung) VALUES('Abgeschickt','Bestellvorgang wurde eingeleitet');

                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}




$schemaName = "wawi";
$tableName = "tbl_bestellung_bestellstatus";
$tabellen[$schemaName.'.'.$tableName] = array("bestellung_bestellstatus_id","bestellung_id",
                    "bestellstatus_kurzbz","uid","oe_kurzbz","datum","insertvon",
                    "insertamum","updatevon","updateamum");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    bestellung_bestellstatus_id bigint NOT NULL,
                    bestellung_id bigint NOT NULL,
                    bestellstatus_kurzbz character varying(32) NOT NULL,
                    uid character varying(32),
                    oe_kurzbz character varying(32),
                    datum date,
                    insertvon character varying(32),
                    insertamum timestamp without time zone,
                    updatevon character varying(32),
                    updateamum timestamp without time zone
                );
                
                CREATE SEQUENCE $sequenceName
                    START WITH 1
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    CACHE 1;
                 
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_bestellung_bestellstatus_id_pkey PRIMARY KEY (bestellung_bestellstatus_id);
                ALTER TABLE $tableName 
                    ALTER COLUMN bestellung_bestellstatus_id SET DEFAULT nextval('$sequenceName');
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_uid_fkey FOREIGN KEY (uid) REFERENCES public.tbl_benutzer(uid) ON UPDATE CASCADE ON DELETE RESTRICT;              
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_bestellstatus_kurzbz_fkey FOREIGN KEY (bestellstatus_kurzbz) REFERENCES tbl_bestellstatus(bestellstatus_kurzbz) ON UPDATE CASCADE ON DELETE RESTRICT;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_bestellung_id_fkey FOREIGN KEY (bestellung_id) REFERENCES tbl_bestellung(bestellung_id)ON UPDATE CASCADE ON DELETE CASCADE;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_oe_kurzbz_fkey FOREIGN KEY (oe_kurzbz) REFERENCES public.tbl_organisationseinheit(oe_kurzbz)) ON UPDATE CASCADE ON DELETE RESTRICT;
                        

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
		GRANT SELECT, UPDATE ON $sequenceName TO vilesci;

                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}

$schemaName = "wawi";
$tableName = "tbl_bestellungtag";
$tabellen[$schemaName.'.'.$tableName] = array("tag","bestellung_id","insertamum","insertvon");
if(!tableExists($schemaName,$tableName))
{
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    tag character varying(128) NOT NULL,
                    bestellung_id bigint NOT NULL,
                    insertamum timestamp without time zone,
                    insertvon character varying(32)
                );
                
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_tag_bestellung_id_pkey PRIMARY KEY (tag, bestellung_id);
                

                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_bestellung_id_fkey FOREIGN KEY (bestellung_id) REFERENCES tbl_bestellung(bestellung_id) ON UPDATE CASCADE ON DELETE CASCADE;              
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_tag_fkey FOREIGN KEY (tag) REFERENCES public.tbl_tag(tag) ON UPDATE CASCADE ON DELETE CASCADE;              


                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
                   
                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';
}


$schemaName = "wawi";
$tableName = "tbl_betriebsmittel";
$tabellen[$schemaName.'.'.$tableName] = array("betriebsmittel_id","beschreibung",
                    "betriebsmitteltyp","nummer","reservieren","updateamum",
                    "updatevon","insertamum","insertvon","ext_id",
                    "inventarnummer","oe_kurzbz","ort_kurzbz","hersteller",
                    "seriennummer","bestellung_id","bestelldetail_id",
                    "afa","verwendung","anmerkung","leasing_bis","inventuramum",
                    "inventurvon","anschaffungswert","anschaffungsdatum",
                    "tiefe","hoehe","breite","nummer2","verplanen");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    betriebsmittel_id integer NOT NULL,
                    beschreibung character varying(256),
                    betriebsmitteltyp character varying(16) NOT NULL,
                    nummer character varying(32),
                    reservieren boolean DEFAULT false NOT NULL,
                    updateamum timestamp without time zone,
                    updatevon character varying(32),
                    insertamum timestamp without time zone,
                    insertvon character varying(32),
                    ext_id bigint,
                    inventarnummer character varying(32),
                    oe_kurzbz character varying(32),
                    ort_kurzbz character varying(16),
                    hersteller character varying(128),
                    seriennummer character varying(32),
                    bestellung_id bigint,
                    bestelldetail_id bigint,
                    afa smallint,
                    verwendung character varying(256),
                    anmerkung text,
                    leasing_bis date,
                    inventuramum timestamp without time zone,
                    inventurvon character varying(32),
                    anschaffungswert numeric(12,2),
                    anschaffungsdatum date,
                    tiefe numeric(6,2),
                    hoehe numeric(6,2),
                    breite numeric(6,2),
                    nummer2 character varying(32),
                    verplanen  boolean DEFAULT false NOT NULL
                );
                
                CREATE SEQUENCE $sequenceName
                    START WITH 1
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    CACHE 1;
                 
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_betriebsmittel_id_pkey PRIMARY KEY (betriebsmittel_id);
                ALTER TABLE $tableName 
                    ALTER COLUMN betriebsmittel_id SET DEFAULT nextval('$sequenceName');
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_betriebsmitteltyp_fkey 
                    FOREIGN KEY (betriebsmitteltyp) 
                    REFERENCES tbl_betriebsmitteltyp(betriebsmitteltyp) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_bestelldetail_id_fkey 
                    FOREIGN KEY (bestelldetail_id) 
                    REFERENCES tbl_bestelldetail(bestelldetail_id) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_bestellung_id_fkey 
                    FOREIGN KEY (bestellung_id) 
                    REFERENCES tbl_bestellung(bestellung_id) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_oe_kurzbz_fkey 
                    FOREIGN KEY (oe_kurzbz) 
                    REFERENCES public.tbl_organisationseinheit(oe_kurzbz) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_ort_kurzbz_fkey 
                    FOREIGN KEY (oe_kurzbz) 
                    REFERENCES public.tbl_ort(ort_kurzbz) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
		GRANT SELECT, UPDATE ON $sequenceName TO vilesci;

                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}



$schemaName = "wawi";
$tableName = "tbl_betriebsmittelstatus";
$tabellen[$schemaName.'.'.$tableName] = array("betriebsmittelstatus_kurzbz","beschreibung");
if(!tableExists($schemaName,$tableName))
{
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    betriebsmittelstatus_kurzbz character varying(16) NOT NULL,
                    beschreibung character varying(256)
                );
                
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_betriebsmittelstatus_kurzbz_pkey PRIMARY KEY (betriebsmittelstatus_kurzbz);
                
                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
                   
                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';
}




$schemaName = "wawi";
$tableName = "tbl_betriebsmittel_betriebsmittelstatus";
$tabellen[$schemaName.'.'.$tableName] = array("betriebsmittelbetriebsmittelstatus_id",
                    "betriebsmittel_id","betriebsmittelstatus_kurzbz",
                    "datum","anmerkung","updateamum","updatevon",
                    "insertamum","insertvon");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    betriebsmittelbetriebsmittelstatus_id integer NOT NULL,
                    betriebsmittel_id integer NOT NULL,
                    betriebsmittelstatus_kurzbz character varying(16) NOT NULL,
                    datum date,
                    anmerkung text,
                    updateamum timestamp without time zone,
                    updatevon character varying(32),
                    insertamum timestamp without time zone,
                    insertvon character varying(32)
                );
                
                CREATE SEQUENCE $sequenceName
                    START WITH 1
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    CACHE 1;
                 
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_betriebsmittelbetriebsmittelstatus_id_pkey PRIMARY KEY (betriebsmittelbetriebsmittelstatus_id);
                ALTER TABLE $tableName 
                    ALTER COLUMN betriebsmittelbetriebsmittelstatus_id SET DEFAULT nextval('$sequenceName');
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_bestellung_id_fkey 
                    FOREIGN KEY (bestellung_id) 
                    REFERENCES tbl_bestellung(bestellung_id) 
                    ON UPDATE CASCADE ON DELETE CASCADE;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_oe_kurzbz_fkey 
                    FOREIGN KEY (oe_kurzbz) 
                    REFERENCES public.tbl_organisationseinheit(oe_kurzbz) 
                    ON UPDATE CASCADE ON DELETE CASCADE;

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
		GRANT SELECT, UPDATE ON $sequenceName TO vilesci;

                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}



$schemaName = "wawi";
$tableName = "tbl_betriebsmittelperson ";
$tabellen[$schemaName.'.'.$tableName] = array("betriebsmittelperson_id",
                    "betriebsmittel_id","person_id",
                    "anmerkung","kaution","ausgegebenam","retouram",
                    "insertamum","insertvon","updateamum","updatevon",
                    "ext_id","uid");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    betriebsmittelperson_id integer NOT NULL,
                    betriebsmittel_id integer NOT NULL,
                    person_id integer NOT NULL,
                    anmerkung character varying(256),
                    kaution numeric(6,2),
                    ausgegebenam date,
                    retouram date,
                    insertamum timestamp without time zone DEFAULT now(),
                    insertvon character varying(32),
                    updateamum timestamp without time zone,
                    updatevon character varying(32),
                    ext_id bigint,                    
                    uid character varying(32)
                );
                
                CREATE SEQUENCE $sequenceName
                    START WITH 1
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    CACHE 1;
                 
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_betriebsmittelperson_id_pkey PRIMARY KEY (betriebsmittelperson_id);
                ALTER TABLE $tableName 
                    ALTER COLUMN betriebsmittelperson_id SET DEFAULT nextval('$sequenceName');
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_betriebsmittel_id_fkey 
                    FOREIGN KEY (betriebsmittel_id) 
                    REFERENCES tbl_betriebsmittel(betriebsmittel_id) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_uid_fkey 
                    FOREIGN KEY (uid) 
                    REFERENCES public.tbl_benutzer(uid) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_person_id_fkey 
                    FOREIGN KEY (person_id) 
                    REFERENCES public.tbl_person(person_id) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
		GRANT SELECT, UPDATE ON $sequenceName TO vilesci;

                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}



$schemaName = "wawi";
$tableName = "tbl_betriebsmitteltyp";
$tabellen[$schemaName.'.'.$tableName] = array("betriebsmitteltyp","beschreibung","anzahl","kaution","typ_code","mastershapename");
if(!tableExists($schemaName,$tableName))
{
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    betriebsmitteltyp character varying(16) NOT NULL,
                    beschreibung character varying(256),
                    anzahl smallint,
                    kaution numeric(6,2),
                    typ_code character(2),
                    mastershapename character varying(256)
                );
                
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_betriebsmitteltyp_pkey PRIMARY KEY (betriebsmitteltyp);
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_betriebsmitteltyp_fkey 
                    FOREIGN KEY (betriebsmitteltyp) 
                    REFERENCES tbl_betriebsmitteltyp(betriebsmitteltyp) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;                    
                
                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
                   
                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';
}


$schemaName = "wawi";
$tableName = "tbl_betriebsmitteltyp";
$tabellen[$schemaName.'.'.$tableName] = array("betriebsmitteltyp","beschreibung","anzahl","kaution","typ_code","mastershapename");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    betriebsmitteltyp character varying(16) NOT NULL,
                    beschreibung character varying(256),
                    anzahl smallint,
                    kaution numeric(6,2),
                    typ_code character(2),
                    mastershapename character varying(256)
                );
                
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_betriebsmitteltyp_pkey PRIMARY KEY (betriebsmitteltyp);
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_betriebsmitteltyp_fkey 
                    FOREIGN KEY (betriebsmitteltyp) 
                    REFERENCES tbl_betriebsmitteltyp(betriebsmitteltyp) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;                    
                
                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
                   
                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';
}

$schemaName = "wawi";
$tableName = "tbl_buchungstyp";
$tabellen[$schemaName.'.'.$tableName] = array("buchungstyp_kurzbz","bezeichnung");
if(!tableExists($schemaName,$tableName))
{
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    buchungstyp_kurzbz character varying(32) NOT NULL,
                    bezeichnung character varying(256)
                );
                               
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_buchungstyp_kurzbz_pkey PRIMARY KEY (buchungstyp_kurzbz);                

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;

                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}

$schemaName = "wawi";
$tableName = "tbl_budget";
$tabellen[$schemaName.'.'.$tableName] = array("geschaeftsjahr_kurzbz","kostenstelle_id","budget");
if(!tableExists($schemaName,$tableName))
{
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    geschaeftsjahr_kurzbz character varying(32) NOT NULL,
                    kostenstelle_id bigint NOT NULL,
                    budget numeric(12,2) NOT NULL
                );
                               
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_geschaeftsjahr_kurzbz_pkey PRIMARY KEY (geschaeftsjahr_kurzbz, kostenstelle_id);    
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_geschaeftsjahr_kurzbz_fkey 
                    FOREIGN KEY (geschaeftsjahr_kurzbz) 
                    REFERENCES public.tbl_geschaeftsjahr(geschaeftsjahr_kurzbz) 
                    ON UPDATE CASCADE ON DELETE CASCADE;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_kostenstelle_id_fkey 
                    FOREIGN KEY (kostenstelle_id) 
                    REFERENCES tbl_kostenstelle(kostenstelle_id) 
                    ON UPDATE CASCADE ON DELETE CASCADE;

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;

                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}



$schemaName = "wawi";
$tableName = "tbl_konto_kostenstelle";
$tabellen[$schemaName.'.'.$tableName] = array("konto_id","kostenstelle_id","insertamum","insertvon");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    konto_id bigint NOT NULL,
                    kostenstelle_id bigint NOT NULL,
                    insertamum timestamp without time zone,
                    insertvon character varying(32)
                );
                
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_konto_id_kostenstelle_id_pkey PRIMARY KEY (konto_id,kostenstelle_id);
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_konto_id_fkey 
                    FOREIGN KEY (konto_id) 
                    REFERENCES tbl_konto(konto_id) 
                    ON UPDATE CASCADE ON DELETE CASCADE;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_kostenstelle_id_fkey 
                    FOREIGN KEY (kostenstelle_id) 
                    REFERENCES tbl_kostenstelle(kostenstelle_id) 
                    ON UPDATE CASCADE ON DELETE CASCADE;
                
                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
                   
                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';
}



$schemaName = "wawi";
$tableName = "tbl_projekt_bestellung";
$tabellen[$schemaName.'.'.$tableName] = array("projekt_kurzbz","bestellung_id","anteil");
if(!tableExists($schemaName,$tableName))
{
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    projekt_kurzbz character varying(16) NOT NULL,
                    bestellung_id bigint NOT NULL,
                    anteil numeric(5,2)
                );
                
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_konto_id_kostenstelle_id_pkey PRIMARY KEY (konto_id,kostenstelle_id);
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_bestellung_id_fkey 
                    FOREIGN KEY (bestellung_id) 
                    REFERENCES tbl_bestellung(bestellung_id) 
                    ON UPDATE CASCADE ON DELETE CASCADE;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_projekt_kurzbz_fkey 
                    FOREIGN KEY (projekt_kurzbz) 
                    REFERENCES fue.tbl_projekt(projekt_kurzbz) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;
                
                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
                   
                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';
}


$schemaName = "wawi";
$tableName = "tbl_rechnungstyp";
$tabellen[$schemaName.'.'.$tableName] = array("rechnungstyp_kurzbz","beschreibung","berechtigung_kurzbz");
if(!tableExists($schemaName,$tableName))
{
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    rechnungstyp_kurzbz character varying(32) NOT NULL,
                    beschreibung character varying(256),
                    berechtigung_kurzbz character varying(32)
                );
                
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_rechnungstyp_kurzbz_pkey PRIMARY KEY (rechnungstyp_kurzbz);
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_berechtigung_kurzbz_fkey 
                    FOREIGN KEY (berechtigung_kurzbz) 
                    REFERENCES system.tbl_berechtigung(berechtigung_kurzbz) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;
                
                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
                   
                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';
}

$schemaName = "wawi";
$tableName = "tbl_zahlungstyp";
$tabellen[$schemaName.'.'.$tableName] = array("zahlungstyp_kurzbz","bezeichnung");
if(!tableExists($schemaName,$tableName))
{
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    zahlungstyp_kurzbz character varying(32) NOT NULL,
                    bezeichnung character varying(256) NOT NULL
                );
                
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_zahlungstyp_kurzbz_pkey PRIMARY KEY (zahlungstyp_kurzbz);
                        
                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
                   
                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';
}




$schemaName = "wawi";
$tableName = "tbl_rechnung";
$tabellen[$schemaName.'.'.$tableName] = array("rechnung_id",
                    "bestellung_id","rechnungstyp_kurzbz",
                    "buchungsdatum","rechnungsnr","rechnungsdatum",
                    "transfer_datum","buchungstext",
                    "insertamum","insertvon","updateamum","updatevon",
                    "freigegeben","freigegebenamum","freigegebenvon");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    rechnung_id bigint NOT NULL,
                    bestellung_id integer,
                    rechnungstyp_kurzbz character varying(32),
                    buchungsdatum date,
                    rechnungsnr character varying(32),
                    rechnungsdatum date,
                    transfer_datum date,
                    buchungstext text,
                    insertamum timestamp without time zone,
                    insertvon character varying(32),
                    updateamum timestamp without time zone,
                    updatevon character varying(32),
                    freigegeben boolean NOT NULL,
                    freigegebenamum timestamp without time zone,
                    freigegebenvon character varying(32)
                );
                
                CREATE SEQUENCE $sequenceName
                    START WITH 1
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    CACHE 1;
                 
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_rechnung_id_pkey PRIMARY KEY (rechnung_id);
                ALTER TABLE $tableName 
                    ALTER COLUMN rechnung_id SET DEFAULT nextval('$sequenceName');
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_bestellung_id_fkey 
                    FOREIGN KEY (bestellung_id) 
                    REFERENCES tbl_bestellung(bestellung_id) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_rechnungstyp_kurzbz_fkey 
                    FOREIGN KEY (rechnungstyp_kurzbz) 
                    REFERENCES tbl_rechnungstyp(rechnungstyp_kurzbz) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
		GRANT SELECT, UPDATE ON $sequenceName TO vilesci;

                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}


$schemaName = "wawi";
$tableName = "tbl_rechnungsbetrag";
$tabellen[$schemaName.'.'.$tableName] = array("rechnungsbetrag_id",
                    "rechnung_id","mwst","betrag",
                    "bezeichnung","ext_id");
if(!tableExists($schemaName,$tableName))
{
        $sequenceName = $tableName.'_seq';
	$qry = "
                SET search_path = $schemaName, pg_catalog;
                
                CREATE TABLE $tableName (
                    rechnungsbetrag_id bigint  NOT NULL,
                    rechnung_id bigint,
                    mwst numeric(4,2),
                    betrag numeric(14,4),
                    bezeichnung text,
                    ext_id integer
                );
                
                CREATE SEQUENCE $sequenceName
                    START WITH 1
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    CACHE 1;
                 
                ALTER TABLE $tableName
                    ADD CONSTRAINT ${tableName}_rechnungsbetrag_id_pkey PRIMARY KEY (rechnungsbetrag_id);
                ALTER TABLE $tableName 
                    ALTER COLUMN rechnungsbetrag_id SET DEFAULT nextval('$sequenceName');
                        
                ALTER TABLE $tableName 
                    ADD CONSTRAINT ${tableName}_rechnung_id_fkey 
                    FOREIGN KEY (rechnung_id) 
                    REFERENCES tbl_rechnung(rechnung_id) 
                    ON UPDATE CASCADE ON DELETE RESTRICT;

                GRANT SELECT ON $tableName TO web;
		GRANT SELECT, UPDATE, INSERT, DELETE ON $tableName TO vilesci;
		GRANT SELECT, UPDATE ON $sequenceName TO vilesci;

                ";

	if(!$db->db_query($qry))
		echo '<strong>$tableName: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' $tableName: Tabelle $tableName hinzugefuegt!<br>';

}


$schemaName = "public";
$tableName = "tbl_firma";
$columnName = 'lieferbedingungen';
print "Erstelle Attribute für Tabelle $tableName:";
if (!columnExists($schemaName, $tableName, $columnName))
{
    $qry = "alter table $schemaName.$tableName add column $columnName varchar(256);";
    if(!$db->db_query($qry))
            echo "<strong>$columnName: '.$db->db_last_error().'</strong><br>";
    else 
            echo " $columnName: Attribut $columnName hinzugefuegt!<br>";
}
else
{
    print "<br>Attribut $columnName exitiert bereits";
}

$schemaName = "wawi";
$tableName = "tbl_bestellung";
$columnName = 'auftragsbestaetigung';
print "Erstelle Attribute für Tabelle $tableName:";
if (!columnExists($schemaName, $tableName, $columnName))
{
    $qry = "alter table $schemaName.$tableName add column $columnName date;";
    if(!$db->db_query($qry))
            echo "<strong>$columnName: '.$db->db_last_error().'</strong><br>";
    else 
            echo " $columnName: Attribut $columnName hinzugefuegt!<br>";
}
else
{
    print "<br>Attribut $columnName exitiert bereits";
}

$schemaName = "wawi";
$tableName = "tbl_bestellung";
$columnName = 'auslagenersatz';
print "Erstelle Attribute für Tabelle $tableName:";
if (!columnExists($schemaName, $tableName, $columnName))
{
    $qry = "alter table $schemaName.$tableName add column $columnName boolean default false;";
    if(!$db->db_query($qry))
            echo "<strong>$columnName: '.$db->db_last_error().'</strong><br>";
    else 
            echo " $columnName: Attribut $columnName hinzugefuegt!<br>";
}
else
{
    print "<br>Attribut $columnName exitiert bereits";
}

$schemaName = "wawi";
$tableName = "tbl_bestellung";
$columnName = 'iban';
print "Erstelle Attribute für Tabelle $tableName:";
if (!columnExists($schemaName, $tableName, $columnName))
{
    $qry = "alter table $schemaName.$tableName add column $columnName varchar(50);";
    if(!$db->db_query($qry))
            echo "<strong>$columnName: '.$db->db_last_error().'</strong><br>";
    else 
            echo " $columnName: Attribut $columnName hinzugefuegt!<br>";
}
else
{
    print "<br>Attribut $columnName exitiert bereits";
}

$schemaName = "wawi";
$tableName = "tbl_bestellung";
$columnName = 'leasing';
print "Erstelle Attribute für Tabelle $tableName:";
if (!columnExists($schemaName, $tableName, $columnName))
{
    $qry = "alter table $schemaName.$tableName add column $columnName boolean;";
    if(!$db->db_query($qry))
            echo "<strong>$columnName: '.$db->db_last_error().'</strong><br>";
    else 
            echo " $columnName: Attribut $columnName hinzugefuegt!<br>";
}
else
{
    print "<br>Attribut $columnName exitiert bereits";
}

$schemaName = "wawi";
$tableName = "tbl_bestellung";
$columnName = 'leasing_txt';
print "Erstelle Attribute für Tabelle $tableName:";
if (!columnExists($schemaName, $tableName, $columnName))
{
    $qry = "alter table $schemaName.$tableName add column $columnName varchar(255);";
    if(!$db->db_query($qry))
            echo "<strong>$columnName: '.$db->db_last_error().'</strong><br>";
    else 
            echo " $columnName: Attribut $columnName hinzugefuegt!<br>";
}
else
{
    print "<br>Attribut $columnName exitiert bereits";
}

/**
 * Neue Berechtigung für das Addon hinzufügen
 */
echo '<h2>Aktualisierung der Berechtigungen</h2>';
$berechtigung = new berechtigung();
$berechtigungenList = array('addon/wawi' => 'Addon WAWI',
                            'wawi/inventar:begrenzt' => 'Inventarverwaltung',
                            'wawi/inventar' => 'Inventar Administration',
                            'wawi/konto' => 'Kontoverwaltung',
                            'wawi/kostenstelle' => 'Kostenstellenverwaltung',
                            'wawi/bestellung' => 'Bestellungen verwalten',
                            'wawi/rechnung' => 'Rechnungen verwalten',
                            'wawi/rechnung_freigeben' => 'Rechnungen Freigeben (bei Gutschriften)',
                            'wawi/rechnung_transfer' => 'Rechnungen - Eintragen des TransferDatums',
                            'wawi/freigabe' => 'Bestellungen freigeben, entweder oe_kurzbz oder kostenstelle_id muss gesetzt sein',
                            'wawi/firma' => 'Firmenverwaltung abgespeckt',
                            'wawi/budget' => 'Budgeteingabe',
                            'wawi/storno' => 'Bestellung stornieren',
                            'wawi/bestellung_advanced' => 'Bestellungen editieren nach dem Abschicken',
                            'wawi/freigabe_advanced' => 'Berechtigung zum Freigeben von ALLEN Bestellungen');

foreach ($berechtigungenList as $key => $value) 
{
    if ($berechtigung->load($key) === false)
    {
        $berechtigung->berechtigung_kurzbz = $key;
        $berechtigung->beschreibung = $value;
        if ($berechtigung->save(true))
        {
            echo "Berechtigung $key erstellt.<br/>";
        }
        else
        {
            echo '<strong>'.$berechtigung->errormsg."</strong><br/>";
            
        }
    }
    else
    {
        echo "Berechtigung $key existiert bereits.<br/>";
    }
}

// Kategorie für DMS anlegen
$dms = new dms();
$wawiKategorie = $dms->loadKategorie('wawi');
if ($wawiKategorie === false)
{
    $dms->kategorie_kurzbz = 'wawi';
    $dms->bezeichnung = 'WAWI';
    $dms->beschreibung = 'WAWI Dokumente (Angebote, etc.)';
    $dms->parent_kategorie_kurzbz = null;
    if ($dms->saveKategorie(true))
    {
        echo "DMS Kategorie WAWI erstellt<br>";
    }
    else
    {
        echo '<strong>'.$dms->errormsg.'</strong>';
    }
}
else
{
    echo 'DMS Kategorie WAWI existiert bereits<br>';
}
$angeboteKategorie = $dms->loadKategorie('angebot');
if ($wawiKategorie === false)
{
    $dms->kategorie_kurzbz = 'angebote';
    $dms->bezeichnung = 'Angebote';
    $dms->beschreibung = 'Angebote für WAWI';
    $dms->parent_kategorie_kurzbz = 'wawi';
    if ($dms->saveKategorie(true))
    {
        echo "DMS Kategorie WAWI-Angebote erstellt<br>";
    }
    else
    {
        echo '<strong>'.$dms->errormsg.'</strong>';
    }
}
else
{
    echo 'DMS Kategorie WAWI-Angebote existiert bereits<br>';
}
/*
if($result = $db->db_query("SELECT * FROM system.tbl_berechtigung WHERE berechtigung_kurzbz='addon/wawi'"))
{
        if($db->db_num_rows($result)==0)
        {
                $qry = "INSERT INTO system.tbl_berechtigung(berechtigung_kurzbz, beschreibung) 
                                VALUES('addon/wawi','Addon WAWI');";

                if(!$db->db_query($qry))
                        echo '<strong>Berechtigung: '.$db->db_last_error().'</strong><br>';
                else 
                        echo 'Neue Berechtigung addon/wawi hinzugefuegt!<br>';
        }
}
*/

echo '<br>Aktualisierung abgeschlossen<br><br>';
echo '<h2>Gegenprüfung</h2>';

$tabs=array_keys($tabellen);
$i=0;
foreach ($tabellen AS $attribute)
{
	$sql_attr='';
	foreach($attribute AS $attr)
		$sql_attr.=$attr.',';
	$sql_attr=substr($sql_attr, 0, -1);

	if (!@$db->db_query('SELECT '.$sql_attr.' FROM '.$tabs[$i].' LIMIT 1;'))
		echo '<BR><strong>'.$tabs[$i].': '.$db->db_last_error().' </strong><BR>';
	else
		echo '<BR>'.$tabs[$i].': OK ';
	flush();
	$i++;
}


function tableExists($schemaName,$tablename) 
{
    global $db;
    return @$db->db_query("SELECT 1 FROM ${schemaName}.${tablename}");
}

function columnExists($schema, $tableName, $columnName)
{
    global $db;
    $qry="
SELECT EXISTS (SELECT 1 
FROM information_schema.columns 
WHERE table_schema='$schema' AND table_name='$tableName' AND column_name='$columnName');";
    $r=$db->db_query($qry);
    if($row = $db->db_fetch_object($r))
    {
        if ($row->exists == 't') return true;
    }
    return false;
}        

?>
