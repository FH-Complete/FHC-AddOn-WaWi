<?php
/* Copyright (C) 2018 Technikum-Wien
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

require_once(dirname(__FILE__).'/../config.inc.php');
require_once('auth.php');

require_once(dirname(__FILE__).'/../../../include/firma.class.php');
require_once(dirname(__FILE__).'/../../../include/organisationseinheit.class.php');
require_once(dirname(__FILE__).'/../../../include/mitarbeiter.class.php');
require_once(dirname(__FILE__).'/../../../include/datum.class.php');
require_once(dirname(__FILE__).'/../include/wawi_benutzerberechtigung.class.php');
require_once(dirname(__FILE__).'/../../../include/standort.class.php');
require_once(dirname(__FILE__).'/../../../include/adresse.class.php');
require_once(dirname(__FILE__).'/../../../include/studiengang.class.php');
require_once(dirname(__FILE__).'/../../../include/mail.class.php');
require_once(dirname(__FILE__).'/../../../include/geschaeftsjahr.class.php');
require_once '../include/wawi_konto.class.php';
//require_once '../include/wawi_kategorie.class.php';
require_once dirname(__FILE__).'/../include/wawi_zuordnung.class.php';
require_once dirname(__FILE__).'/../include/wawi_bestellung.class.php';
require_once dirname(__FILE__).'/../include/wawi_kostenstelle.class.php';
require_once dirname(__FILE__).'/../include/wawi_bestelldetail.class.php';
require_once dirname(__FILE__).'/../include/wawi_aufteilung.class.php';
require_once dirname(__FILE__).'/../include/wawi_bestellstatus.class.php';
require_once dirname(__FILE__).'/../include/wawi_zahlungstyp.class.php';
require_once dirname(__FILE__).'/../include/wawi_rechnung.class.php';
require_once(dirname(__FILE__).'/../../../include/tags.class.php');
require_once(dirname(__FILE__).'/../../../include/projekt.class.php');
require_once(dirname(__FILE__).'/../../../include/dms.class.php');

$user=get_uid();
$ausgabemsg='';

$berechtigung_kurzbz='wawi/rechnung';
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);
$kst=new wawi_kostenstelle();
$kst->loadArray($rechte->getKostenstelle($berechtigung_kurzbz),'bezeichnung');
//$bestellung_kategorie=new wawi_bestellung_kategorie();
//$bestellung_kategorie->getAll(true);

$projekt = new projekt();
$projekt->getProjekteMitarbeiter($user);
$projektZugeordnet = false;

// wawi dms kategorie
$kategorie_kurzbz = 'rechnungen';

// Abfrage ob dem user ein oder mehrere Projekte zugeordnet sind
if(count($projekt->result) > 0)
	$projektZugeordnet = true;
	$rechnung = new wawi_rechnung();
//var_dump($_FILES);
//var_dump($_POST);
if (isset($_POST['rechnung_id']))
{
	$id=$_POST['rechnung_id'];
}
else if (isset($_GET['rechnung_id']))
{
	$id=$_GET['rechnung_id'];
}
else
{
	echo "rechnung_id nicht gesetzt";
	die();
}


if (!is_numeric($id))
{
	$id = (int)$id;
}

if(!$rechnung->load($id))
{
	var_dump($_REQUEST);
	die("Rechnung $id ist nicht vorhanden.");
}

$bestellung = new wawi_bestellung();
$bestellung->load($rechnung->bestellung_id);	

if(!$rechte->isberechtigt('wawi/bestellung',null, 's', $bestellung->kostenstelle_id))
	die('Sie haben keine Berechtigung für diese Rechnung.');

if (isset($_POST['method']) && $_POST['method'] == 'upload')
{
	if(!$rechte->isberechtigt('wawi/bestellung',null, 'sui', $bestellung->kostenstelle_id))
		die('Sie haben keine Berechtigung für diese Rechnung.');

	//var_dump($_FILES);
	$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
	$filename = uniqid();
	$filename.=".".$ext;
	$uploadfile = DMS_PATH.$filename;

	if(move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile))
	{
		echo $_FILES['file']['name']. " OK";
		echo "uploadfile: $uploadfile";

		$dms = new dms();
		$class_methods = get_class_methods($dms);
		foreach ($class_methods as $method_name) {
			echo "$method_name\n";
		}
		if(!$dms->setPermission($uploadfile))
			echo $dms->errormsg;

		$dms->version='0';
		$dms->kategorie_kurzbz=$kategorie_kurzbz;

		$dms->insertamum=date('Y-m-d H:i:s');
		$dms->insertvon = $user;
		$dms->mimetype=$_FILES['file']['type'];
		$dms->filename = $filename;
		$dms->name = $_FILES['file']['name'];

		if($dms->save(true))
		{
			$rechnung->dms_id = $dms->dms_id;
			if ($rechnung->save() === false)
			{
				echo $rechnung->errormsg;
			}
			/*
			$dms_id=$dms->dms_id;
			$link = new wawi_rechnung();
			$link->new = true;
			$link->rechnung_id = $id;
			$link->dms_id = $dms_id;
			if ($link->save() === false)
			{
			echo $link->errormsg;
			}*/
		}
		else
		{
			echo 'Fehler beim Speichern der Daten';
			$error = true;
		}
	}
	else
	{
		echo $_FILES['file']['name']. " konnte nicht gespeichert werden.";
	}
}
else if (isset($_POST['method']) && $_POST['method'] == 'list')
{
	$rechnungDAO = new wawi_rechnung();
	if ($rechnungDAO->getAll($id) === false)
	{
		header('Content-type: application/json');
		echo json_encode( array( "result"=> 0, "errormsg"=> $rechnungDAO->errormsg));
		die();
	}

	$data = array();
	foreach ($rechnungDAO->result as $a)
	{
		$data[] = array( 'rechnung_id' => $a->rechnung_id,
					'dms_id' => $a->dms_id,
					'version'  => 0,
					'name' => $a->filename);
	}
	header('Content-type: application/json');
	echo json_encode( array( "result"=> 1, "list"=> $data));
}
else if (isset($_POST['method']) &&  $_POST['method'] == 'delete')
{
	if(!$rechte->isberechtigt('wawi/bestellung',null, 'suid', $bestellung->kostenstelle_id))
		die('Sie haben keine Berechtigung für diese Bestellung.');

	$rechnung = new wawi_rechnung();
	$rechnung_id = (int)$_POST['currentRechnungId'];

	if ($rechnung->load($rechnung_id) === false)
	{
		header('Content-type: application/json');
		echo json_encode( array( "result"=> 0, "errormsg"=> $rechnung->errormsg));
		die();
	}

	$dms = new dms();
	$dms->deleteDMS($rechnung->dms_id);
	$rechnung->dms_id=null;
	if ($rechnung->save() === false)
	{
		echo $rechnung->errormsg;
	}
	header('Content-type: application/json');
	echo json_encode( array( "result"=> 1));
}
else if (isset($_GET['method']) && $_GET['method'] == 'download')
{
	$rechnungDAO = new wawi_rechnung();
	$rechnung_id = (int)$_GET['rechnung_id'];

	if ($rechnungDAO->load($rechnung_id) === false)
	{
	  	header("HTTP/1.0 404 Not Found");
	  	die();
	}

	$dms = new dms();
	if(!$dms->load($rechnungDAO->dms_id,0))
	{
		header("HTTP/1.0 404 Not Found");
		die();
	}

	$tempPdfName = DMS_PATH.$dms->filename;

	if (!file_exists($tempPdfName))
	{
		throw new RuntimeException("Datei nicht gefunden: ".$tempPdfName);
	}

	$fsize = filesize($tempPdfName);
	$handle = fopen($tempPdfName,'r');
	if ($handle !== false)
	{
		header('Content-type: '.$dms->mimetype);
		header('Content-Disposition: inline; filename="'.$dms->name);
		header('Content-Length: '.$fsize);
		while (!feof($handle))
		{
			echo fread($handle, 8192);
		}
		fclose($handle);
	}

}
