<?php
/* Copyright (C) 2019 FH Technikum Wien
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
 * Erstellung einer Liste der Rechnungen zum Download für die Buchhaltung
 */

require_once(dirname(__FILE__).'/../../config.inc.php');
require_once('../auth.php');
require_once(dirname(__FILE__).'/../../include/wawi_benutzerberechtigung.class.php');
require_once(dirname(__FILE__).'/../../../../include/functions.inc.php');
require_once(dirname(__FILE__).'/../../include/wawi_rechnung.class.php');
require_once(dirname(__FILE__).'/../../include/wawi_bestellung.class.php');
require_once(dirname(__FILE__).'/../../include/wawi_kostenstelle.class.php');
require_once(dirname(__FILE__).'/../../include/wawi_zahlungstyp.class.php');
require_once(dirname(__FILE__).'/../../include/wawi_konto.class.php');
require_once(dirname(__FILE__).'/../../../../include/studiensemester.class.php');
require_once(dirname(__FILE__).'/../../../../include/tags.class.php');
require_once(dirname(__FILE__).'/../../../../include/geschaeftsjahr.class.php');
require_once(dirname(__FILE__).'/../../../../include/datum.class.php');
require_once(dirname(__FILE__).'/../../../../include/dms.class.php');
require_once '../../vendor/autoload.php';



/** Kalenderwoche beginnt mit Freitag */
function getKW()
  {
    $date = date("Y-m-d");// current date
    $date = strtotime(date("Y-m-d", strtotime($date)) . " +4 days");
    $woche = date("W",$date);
    return $woche;
  }

function KW2Date($jahr, $kw)
  {
     $dateString = $jahr.'W'.sprintf('%02d', $kw+0);
     //echo $dateString.'<br>';
     $start = strtotime(date("Y-m-d",strtotime($dateString)) . '-4 days');
     return array("start" => $start ,
       "ende" => strtotime(date("Y-m-d",$start) . ' +6 days'));
  }

function maybeSelect($haystack, $needle)
  {
    if (in_array($needle, $haystack)) return 'selected';
    return '';
  }


$user = get_uid();
$rechte = new wawi_benutzerberechtigung();
$rechte->getBerechtigungen($user);

if (!$rechte->isBerechtigt('wawi/bestellung_advanced',null,'suid'))
    die('Zugriff verweigert');

$db = new basis_db();
$datum_obj = new datum();
$dms = new dms();

$export = (string)filter_input(INPUT_GET, 'export');
$kalenderjahr = isset($_REQUEST['kalenderjahr'])?$_REQUEST['kalenderjahr']:date('Y');
$kalenderwoche = isset($_REQUEST['kalenderwoche'])?$_REQUEST['kalenderwoche']:getKW();


// default Parameter init fuer XLSX-Export
if (!isset($_REQUEST['kalenderjahr']) && !isset($_REQUEST['kalenderwoche']))
  {
    $_REQUEST['kalenderjahr'] = $kalenderjahr;
    $_REQUEST['kalenderwoche'] = $kalenderwoche;
  }

$kwZeitraum = KW2Date($kalenderjahr, $kalenderwoche);
// Zeitraum
$vondatum = date("d.m.Y", $kwZeitraum['start']);
$endedatum = date("d.m.Y", $kwZeitraum['ende']);


$qry="
select distinct b.bestell_nr,f.firma_id as lieferant_id,f.name as firma, f.nation, r.rechnungsnr,r.rechnungsdatum,r.buchungsdatum,r.buchungstext,r.rechnung_id,
(SELECT sum(case when mwst is not null then (rb.betrag*(rb.mwst+100)/100) else (rb.betrag) end) FROM wawi.tbl_rechnungsbetrag rb WHERE rb.rechnung_id=r.rechnung_id) betrag,
(SELECT sum(rb.betrag) FROM wawi.tbl_rechnungsbetrag rb WHERE rb.rechnung_id=r.rechnung_id) betrag_netto

from wawi.tbl_bestellung b left join wawi.tbl_rechnung r using(bestellung_id) left join (select distinct a.nation,firma.firma_id,firma.name from tbl_firma firma join tbl_standort using(firma_id) join tbl_adresse a using(adresse_id)) f using(firma_id)

where
    r.buchungsdatum between '".date("Y-m-d", $kwZeitraum['start'])."' and '".date("Y-m-d", $kwZeitraum['ende'])."'

";

//echo $qry;


if ($export == '' || $export == 'html')
    {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>WaWi - Rechnungen - Auswertung</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <link rel="stylesheet" href="../../../../skin/jquery.css" type="text/css">
    <link rel="stylesheet" href="../../../../skin/tablesort.css" type="text/css">
    <link rel="stylesheet" href="../../skin/jquery-ui.min.css" type="text/css"/>
    <link rel="stylesheet" href="../../../../skin/fhcomplete.css" type="text/css">
    <link rel="stylesheet" href="../../skin/wawi.css" type="text/css">

    <script type="text/javascript" src="../../../../vendor/jquery/jqueryV1/jquery-1.12.4.min.js"></script>
    <script type="text/javascript" src="../../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
    <script type="text/javascript" src="../../../../vendor/components/jqueryui/jquery-ui.min.js"></script>
    <script type="text/javascript" src="../../include/js/tablesorter-setup.js"></script>
    <style>

      label.frm {
            width:150px;
            float:left;
      }

      input[type="text"],input[type="number"]{width:200px;}
      input[type="radio"]{width:20px;}

      fieldset {
            border: none;
        }

      div.frm {
        margin-top: 5px;
      }

    </style>
    <script type="text/javascript">
        $(document).ready(function()
          {

            $("#myTable").tablesorter(
            {
                //sortList: [[1,0]],
                widgets: ["zebra"],
                headers: {
                     9: { sorter: "digitmittausenderpunkt"},
                     10: { sorter: "digitmittausenderpunkt"},
                     3: { sorter:"dedate" },
                }
            });

          });

    </script>
</head>
<body>
<h1>Bericht - Rechnungen</h1>

<form ction="'.$_SERVER['PHP_SELF'].'" method="GET">
<?php

//Kalenderjahr
echo '
  <div>
    <label for="kalenderjahr" class="frm">Kalenderjahr: </label>';
echo '<SELECT name="kalenderjahr" >';

for($i=date('Y'); $i>=date('Y')-5; $i--)
{
    if($i+""==$kalenderjahr)
        $selected='selected';
    else
        $selected='';
    echo '<option '.$selected.' value="'.$i.'">'.$i.'</option>';
}
echo '</SELECT>
    </div>
    <div class="frm">
        <label for="kw" class="frm">Kalenderwoche: </label>
        <input type="number" name="kalenderwoche" value="'.$kalenderwoche.'" min="1" max="52" /><br/>
    </div>';


?>
<input type="submit" value="Anzeigen" name="show" >
</form>


<?php

echo "<p style=\"padding-left:150px\">";
$keine_parameter = false;
if (strrpos($_SERVER['REQUEST_URI'], "rechnungen.php"))
  {
    $keine_parameter = true;
  }

echo "<a href=\"".htmlspecialchars($_SERVER['REQUEST_URI']).($keine_parameter?'?kalenderjahr='.$kalenderjahr.'&kalenderwoche='.$kalenderwoche:'')."&export=zip\"><IMG src=\"../../../../skin/images/zip_icon.png\" > ZIP</a></p>";


echo '<span style="font-size: small">Zeitraum: ',$datum_obj->formatDatum($vondatum,'d.m.Y'),' - ',$datum_obj->formatDatum($endedatum,'d.m.Y').'</span>';


    if($result = $db->db_query($qry))
      {

        echo '<table id="myTable" class="tablesorter" style="width: auto;">
            <thead>
                <tr>
                    <th>WAWI-Bestellnr</th>
                    <th>Lieferant-ID</th>
                    <th>Firma</th>
                    <th>Rechnungsnr</th>
                    <th>Rechnungsdatum</th>
                    <th>Text/Zweck</th>
                    <th>Brutto-Betrag in €</th>
                    <th>Rechnung</th>
                    ';

        echo '
                </tr>
            </thead>
            <tbody>';
        $icon = '../../../../skin/images/pdf_icon.png';
        $rechnungDAO = new wawi_rechnung();
        while($row = $db->db_fetch_object($result))
          {

            echo '<tr>';
            echo '<td><a href= "../bestellung.php?method=suche&submit=true&bestellnr='.$row->bestell_nr.'" >'.$row->bestell_nr.'</a></td>';
            echo '<td>'.$row->lieferant_id.'</td>';
            echo '<td>'.$row->firma.'</td>';
            echo '<td>'.$row->rechnungsnr.'</td>';
            echo '<td>'.$datum_obj->formatDatum($row->rechnungsdatum,'d.m.Y').'</td>';
            echo '<td>'.$row->buchungstext.'</td>';
            echo '<td class="number">'.number_format($row->betrag,2,',','.').'</td>';
            $rechnung_id = $row->rechnung_id;
            $rechnungDAO->load($rechnung_id);
            if($dms->load($rechnungDAO->dms_id,0))
            {
              echo '<td><a href="../rechnungDoc.php?method=download&rechnung_id=' . $rechnung_id .'" target="_blank"><img src="'.$icon.'" alt="rechnung"/></a></td>';
            } else
            {
              echo '<td></td>';
            }

            echo '</tr>';

          }
          echo '
        </tbody>
        </table>';
      }

?>
<br /><br />
</body>
</html>

<?php
  }
else if ($export == 'zip')
  {
        $zip = new ZipArchive();

        $rechnungDAO = new wawi_rechnung();
        // Create temp zip file in temp dir
    		$tmp_zip_file = tempnam(sys_get_temp_dir(), "FHC_RECHNUNGEN_".$kalenderjahr."_"). '.zip';

        // Create zip archive
        if ($zip->open($tmp_zip_file, ZipArchive::CREATE) === TRUE)
        {
          if($result = $db->db_query($qry))
          {
            while($row = $db->db_fetch_object($result))
              {
                $rechnung_id = $row->rechnung_id;
                $rechnungDAO->load($rechnung_id);
                if($dms->load($rechnungDAO->dms_id,0))
                {
                  $tempPdfName = DMS_PATH.$dms->filename;
                  if (!file_exists($tempPdfName))
                  {
                    throw new RuntimeException("Datei nicht gefunden: ".$tempPdfName);
                  }

                  $zip->addFile($tempPdfName, $dms->name);

                }
              }
          }
          // Close zip archive
          $zip->close();

          // ZIP
          header('Content-Type: application/zip');
          header('Content-disposition: attachment; filename=FHC_RECHNUNGEN_'.$kalenderjahr.'_'.$kalenderwoche.'.zip');
          header('Content-Length: '. filesize($tmp_zip_file));
          header('Cache-Control: max-age=0');

          readfile($tmp_zip_file);

          // Delete temp zip file
          unlink($tmp_zip_file);
        }
        else
        {
            echo 'Fehler beim Zippen der Rechnungen.';
        }



  }
