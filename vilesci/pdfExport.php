<?php
/* Copyright (C) 2016 Technikum-Wien
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

session_cache_limiter('none'); //muss gesetzt werden sonst funktioniert der Download mit IE8 nicht
require_once('auth.php');

require_once(dirname(__FILE__).'/../../../config/vilesci.config.inc.php');
require_once('../include/bestelldetail.inc.php');
require_once(dirname(__FILE__).'/../../../include/functions.inc.php');
require_once('../include/functions.inc.php');
require_once('../include/wawi_benutzerberechtigung.class.php');
require_once(dirname(__FILE__).'/../../../include/akte.class.php');
require_once(dirname(__FILE__).'/../../../include/vorlage.class.php');
require_once(dirname(__FILE__).'/../../../include/student.class.php');
require_once(dirname(__FILE__).'/../../../include/prestudent.class.php');
require_once(dirname(__FILE__).'/../../../include/variable.class.php');
require_once(dirname(__FILE__).'/../../../include/addon.class.php');
require_once(dirname(__FILE__).'/../../../include/studiengang.class.php');
require_once(dirname(__FILE__).'/../../../include/studiensemester.class.php');
require_once(dirname(__FILE__).'/../../../include/studienordnung.class.php');

$user = get_uid();
$db = new basis_db();

$variable_obj = new variable();
$variable_obj->loadVariables($user);

// Studiengang ermitteln dessen Vorlage verwendet werden soll
$xsl_stg_kz=0;
// Direkte uebergabe des Studienganges dessen Vorlage verwendet werden soll
if(isset($_GET['xsl_stg_kz']))
	$xsl_stg_kz=$_GET['xsl_stg_kz'];
else
{
	// Wenn eine Studiengangskennzahl uebergeben wird, wird die Vorlage dieses Studiengangs verwendet
	if(isset($_GET['stg_kz']))
		$xsl_stg_kz=$_GET['stg_kz'];
	else
	{
		// Werden UIDs uebergeben, wird die Vorlage des Studiengangs genommen
		// in dem der 1. Studierende in der Liste ist
		if(isset($_GET['uid']) && $_GET['uid']!='')
		{
			if(strstr($_GET['uid'],';'))
				$uids = explode(';',$_GET['uid']);
			else
				$uids[1] = $_GET['uid'];

			$student_obj = new student();
			if($student_obj->load($uids[1]))
			{
				$xsl_stg_kz=$student_obj->studiengang_kz;
			}
		}
	}
}
if(isset($_GET['xsl_oe_kurzbz']))
	$xsl_oe_kurzbz=$_GET['xsl_oe_kurzbz'];
else
	$xsl_oe_kurzbz='';

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

//OE fuer Output ermitteln

if ($xsl_oe_kurzbz!='')
{
	$oe_kurzbz = $xsl_oe_kurzbz;
}
else
{
	if($xsl_stg_kz=='')
		$xsl_stg_kz='0';
	$oe = new studiengang();
	$oe->load($xsl_stg_kz);
	$oe_kurzbz = $oe->oe_kurzbz;
}

$output = 'pdf';

$model = bestellungReadModel((int)$_GET['id']);
//var_dump($model);die();
$addonpath = dirname(dirname($_SERVER['SCRIPT_FILENAME'])).DIRECTORY_SEPARATOR;
$odt = $addonpath.'system/vorlage_zip/Bestellschein.odt';  
$contentTemplate = $addonpath.'system/vorlage_zip/Bestellschein.content.php';  
$styleTemplate = $addonpath.'system/vorlage_zip/Bestellschein.styles.php'; 
$logoGmbH = ($xsl_oe_kurzbz=='gmbh'?$addonpath.'system/vorlage_zip/Logo_GMBH.png':null);
$converter = new BestellungPDFConverter();
$tempPdfName = $converter->convert2pdf($model, $odt, $contentTemplate, !$model->lieferant->deutsch, $styleTemplate, $logoGmbH);

if ($tempPdfName !== false)
{    
    if (!file_exists($tempPdfName))
    {
        throw new RuntimeException("PDF nicht gefunden: ".$tempPdfName);
    }
    $fsize = filesize($tempPdfName);
    $handle = fopen($tempPdfName,'r');
    if ($handle !== false) {
        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="Bestellschein_'.$model->bestell_nr.'.pdf"');
        header('Content-Length: '.$fsize);
        while (!feof($handle))
        {
            echo fread($handle, 8192);
        }
        fclose($handle);   
    }
}
?>
