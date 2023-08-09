<?php
/* Copyright (C) 2018 FH Technikum Wien
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
 * Erstellung einer Liste für die Buchhaltung
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
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style;

const land_auswahl = array ( "alle" => "", "inland" => "Inland", "ausland" => "Ausland");

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
    if (is_array($haystack) && in_array($needle, $haystack)) return 'selected';
    return '';
  }


$user = get_uid();
$rechte = new wawi_benutzerberechtigung();
$rechte->getBerechtigungen($user);

if (!$rechte->isBerechtigt('wawi/bestellung_advanced',null,'suid'))
    die('Zugriff verweigert');

$db = new basis_db();
$datum_obj = new datum();


$export = (string)filter_input(INPUT_GET, 'export');
$kalenderjahr = isset($_REQUEST['kalenderjahr'])?$_REQUEST['kalenderjahr']:date('Y');
$kalenderwoche = isset($_REQUEST['kalenderwoche'])?$_REQUEST['kalenderwoche']:getKW();
$konto_id = (integer)isset($_REQUEST['konto'])?$_REQUEST['konto']:null;
$firma_id = (integer)isset($_REQUEST['firma_id'])?$_REQUEST['firma_id']:null;
$firmenname = (string)isset($_REQUEST['firmenname'])?$_REQUEST['firmenname']:null;
$land = (string)isset($_REQUEST['land'])?$_REQUEST['land']:null;
$zahlungsweise = isset($_REQUEST['zahlungsweise'])?$_REQUEST['zahlungsweise']:null;

if ($firma_id == '')
    $firmenname = '';
if ($land == '')
    $land= 'alle';

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

$where_konto = "";
if ($konto_id > 0)
  {
    $where_konto = " AND b.konto_id=$konto_id ";
  }
  $where_lieferant = "";
if ($firma_id > 0)
  {
    $where_lieferant = " AND f.firma_id=$firma_id";
  }
$where_zahlungsweise = "";
if ($zahlungsweise != null)
  {
    $where_zahlungsweise = " AND b.zahlungstyp_kurzbz in (".$db->implode4SQL($zahlungsweise).") ";
  }
$where_land = "";
if ($land != "")
  {

    if ($land == "inland")
      {
        $where_land = " AND f.nation='A' ";
      }
    else if ($land == "ausland")
      {
        $where_land = " AND (f.nation<>'A') ";
      }

  }


$qry="
select distinct b.bestell_nr,f.firma_id as lieferant_id,f.name as firma, f.nation, r.rechnungsnr,r.rechnungsdatum,r.buchungsdatum,r.buchungstext,
(SELECT sum(case when mwst is not null then (rb.betrag*(rb.mwst+100)/100) else (rb.betrag) end) FROM wawi.tbl_rechnungsbetrag rb WHERE rb.rechnung_id=r.rechnung_id) betrag,
(SELECT sum(rb.betrag) FROM wawi.tbl_rechnungsbetrag rb WHERE rb.rechnung_id=r.rechnung_id) betrag_netto,
  k.kurzbz konto,
  zt.bezeichnung as zahlungsweise,
  case when eulaender.nation_code is not null and eulaender.nation_code<>'A' then true else false end as ige
from wawi.tbl_bestellung b left join wawi.tbl_rechnung r using(bestellung_id) left join (select distinct a.nation,firma.firma_id,firma.name from tbl_firma firma join tbl_standort using(firma_id) join tbl_adresse a using(adresse_id)) f using(firma_id) left join wawi.tbl_konto  k using(konto_id)
  left join (select nation_code from bis.tbl_nation where eu=true) as eulaender on (f.nation=eulaender.nation_code)
  left join wawi.tbl_zahlungstyp as zt on (b.zahlungstyp_kurzbz=zt.zahlungstyp_kurzbz)
where
    r.buchungsdatum between '".date("Y-m-d", $kwZeitraum['start'])."' and '".date("Y-m-d", $kwZeitraum['ende'])."'

    $where_konto
    $where_zahlungsweise
    $where_land
    $where_lieferant
";

//echo $qry;


if ($export == '' || $export == 'html')
    {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>WaWi - Bestellungen - Auswertung</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <link rel="stylesheet" href="../../../../skin/jquery.css" type="text/css">
    <link rel="stylesheet" href="../../../../skin/tablesort.css" type="text/css">
    <link rel="stylesheet" href="../../skin/jquery-ui.min.css" type="text/css"/>
    <link rel="stylesheet" href="../../../../skin/fhcomplete.css" type="text/css">
    <link rel="stylesheet" href="../../skin/wawi.css" type="text/css">

    <script type="text/javascript" src="../../../../vendor/jquery/jquery1/jquery-1.12.4.min.js"></script>
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

            $('#firmenname').autocomplete(
            {
                source: "../wawi_autocomplete.php?work=wawi_firma_search",
                minLength:2,
                response: function(event, ui)
                {
                    //Value und Label fuer die Anzeige setzen
                    for(i in ui.content)
                    {
                        ui.content[i].value=ui.content[i].firma_id;
                        ui.content[i].label=ui.content[i].gesperrt+ui.content[i].name;
                        if(ui.content[i].kurzbz!='')
                            ui.content[i].label+=' ('+ui.content[i].kurzbz+')';
                        ui.content[i].label+=' '+ui.content[i].firma_id;
                    }
                },
                select: function(event, ui)
                {
                    ui.item.value=ui.item.name;
                    $('#firma_id').val(ui.item.firma_id);
                }
            });

          });

        function resetLieferant() {
            $("#firma_id").val(null);
            $("#firmenname").val("");
        }

    </script>
</head>
<body>
<h1>Bericht - Buchhaltung</h1>

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

echo '<div class="frm">
      <label for="konto" class="frm">Konto: </label>';
echo '<select name="konto" id="konto" >';
echo '<option value="" ></option>';
$konto = new wawi_konto();
$konto->getAll(true);
if(numberOfElements($konto->result)>0)
  {
    foreach($konto->result as $ko)
      {
        if($ko->konto_id==$konto_id)
            $selected='selected';
        else
            $selected='';
        echo '<option '.$selected.' value='.$ko->konto_id.' >'.$ko->kurzbz."</option>\n";
      }
  }
echo '</select>';
echo '</div>';
echo '<div class="frm">
      <label for="lieferant" class="frm">Lieferant: </label>
      <input type="text" id="firmenname" name="firmenname"  value="'.$firmenname.'" >
      <input type ="hidden" id="firma_id" name="firma_id" size="10" maxlength="30" value="'.$firma_id.'" >
      <a onClick="resetLieferant()"" title="reset" id="reset_lieferant" class="resetBtn"> <img src="../../../../skin/images/delete_round.png" class="cursor"></a>
     </div>';
echo '<div class="frm">
      <label for="land" class="frm">Land: </label>
      <select name="land">';
foreach (land_auswahl as $key => $value)
  {
        echo '<option '.($key == $land ? 'selected':'').' value="'.$key.'">'.$value.'</option>';
  }
echo '
     </select>
     </div>';
echo '<div class="frm">
      <label for="zahlungsweise" class="frm">Zahlungsweise: </label>';
    echo "<SELECT name='zahlungsweise[]' id='zahlungstyp' multiple>";
//    echo "<option value=''></option>";
    $zahlungstyp = new wawi_zahlungstyp();
    $zahlungstyp->getAllFiltered();
    foreach($zahlungstyp->result as $typ)
      {
        $selected = maybeSelect($zahlungsweise, $typ->zahlungstyp_kurzbz);
        echo '<option '.$selected.' value='.$typ->zahlungstyp_kurzbz.' '.$selected.'>'.$typ->bezeichnung."</option>\n";
      }
    echo "</select> ";
echo  '</div>';
?>
<input type="submit" value="Anzeigen" name="show" style="margin-left: 150px">
</form>


<?php

echo "<p style=\"padding-left:150px\">";
$keine_parameter = false;
if (strrpos($_SERVER['REQUEST_URI'], "buchhaltung.php"))
  {
    $keine_parameter = true;
  }

echo "<a href=\"".htmlspecialchars($_SERVER['REQUEST_URI']).($keine_parameter?'?kalenderjahr='.$kalenderjahr.'&kalenderwoche='.$kalenderwoche:'')."&export=xlsx\"><IMG src=\"../../../../skin/images/ExcelIcon.png\" > XLSX</a></p>";


echo '<span style="font-size: small">Zeitraum: ',$datum_obj->formatDatum($vondatum,'d.m.Y'),' - ',$datum_obj->formatDatum($endedatum,'d.m.Y').'</span>';


    if($result = $db->db_query($qry))
      {

        echo '<table id="myTable" class="tablesorter" style="width: auto;">
            <thead>
                <tr>
                    <th>WAWI-Bestellnr</th>
                    <th>Lieferant-ID</th>
                    <th>Firma</th>
                    <th>Land</th>
                    <th>Rechnungsnr</th>
                    <th>Rechnungsdatum</th>
                    <th>Text/Zweck</th>
                    <th>Konto</th>
                    <th>Zahlungsweise</th>
                    <th>Brutto-Betrag in €</th>
                    <th>Netto-Betrag in €</th>
                    <th>IGE</th>
                    ';

        echo '
                </tr>
            </thead>
            <tbody>';
        while($row = $db->db_fetch_object($result))
          {
            echo '<tr>';
            echo '<td><a href= "../bestellung.php?method=suche&submit=true&bestellnr='.$row->bestell_nr.'" >'.$row->bestell_nr.'</a></td>';
            echo '<td>'.$row->lieferant_id.'</td>';
            echo '<td>'.$row->firma.'</td>';
            echo '<td>'.$row->nation.'</td>';
            echo '<td>'.$row->rechnungsnr.'</td>';
            echo '<td>'.$datum_obj->formatDatum($row->rechnungsdatum,'d.m.Y').'</td>';
            echo '<td>'.$row->buchungstext.'</td>';
            echo '<td>'.$row->konto.'</td>';
            echo '<td>'.$row->zahlungsweise.'</td>';
            echo '<td class="number">'.number_format($row->betrag,2,',','.').'</td>';
            echo '<td class="number">'.number_format($row->betrag_netto,2,',','.').'</td>';
            echo '<td>'.($row->ige=='t'?'IGE':'').'</td>';
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
else if ($export == 'xlsx')
 {

        // EXCEL

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="buchhaltung.xlsx"');
        header('Cache-Control: max-age=0');


        $spreadsheet = new Spreadsheet();  // \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Buchhaltung');

        $styleArray = [
         'font' =>['bold' => true],
         'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'color' => ['argb' => 'FFCCFFCC']],
         'alignment' =>['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
         'borders'=>['bottom' =>['borderStyle'=> \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM]]
        ];

        $spalten = ['A','B','C','D','E','F','G','H','I','J','K','L'];
        $spalten_bezeichnung = ['WAWI-Bestellnr','Lieferant-ID','Firma','Land','Rechnungsnr','Rechnungsdatum','Text/Zweck','Konto','Zahlungsweise','Brutto-Betrag in €','Netto-Betrag in €','IGE'];

        $spalten_anzahl = 0;
        for ($i=0; $i < numberOfElements($spalten); $i++) {
            $sheet->setCellValue($spalten[$spalten_anzahl].'1',$spalten_bezeichnung[$i]);
            $spalten_anzahl++;
        }
        $spreadsheet->getActiveSheet()->getStyle('A1:'.$spalten[$spalten_anzahl-1].'1')->applyFromArray($styleArray);

        $rownum = 2;

        if($result = $db->db_query($qry))
          {
            while($row = $db->db_fetch_object($result))
              {
                $spaltenindex = 0;
                $sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->bestell_nr);
                $sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->lieferant_id);
                $sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->firma);
                $sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->nation);
                //$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->rechnungsnr);
                $spreadsheet->getActiveSheet()->getCell($spalten[$spaltenindex++]."$rownum")
                  ->setValueExplicit(
                      $row->rechnungsnr,
                      \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                  );
                if ($row->rechnungsdatum != null)
                  {
                    $sheet->setCellValue($spalten[$spaltenindex++]."$rownum",\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($datum_obj->formatDatum($row->rechnungsdatum,'d/m/Y')));
                    $sheet->getStyle("F$rownum")
                                ->getNumberFormat()
                                ->setFormatCode('d/m/yy');
                  }
                else
                  {
                    $spaltenindex++;
                  }
                $sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->buchungstext);
                $sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->konto);
                $sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->zahlungsweise);
                $sheet->setCellValue($spalten[$spaltenindex++]."$rownum",number_format($row->betrag,2,'.',''));
                $sheet->setCellValue($spalten[$spaltenindex++]."$rownum",number_format($row->betrag_netto,2,'.',''));
                $sheet->setCellValue($spalten[$spaltenindex++]."$rownum",($row->ige=='t'?'IGE':''));
                $rownum++;
              }
          }



        foreach ($spalten as $key ) {
            $sheet->getColumnDimension($key)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

  }
