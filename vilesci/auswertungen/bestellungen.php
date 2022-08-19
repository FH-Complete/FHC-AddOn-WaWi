<?php
/* Copyright (C) 2010 FH Technikum Wien
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
 * Auswertung der Bestellungen und Rechnungen auf Umsatz pro Lieferant
 */

require_once(dirname(__FILE__).'/../../config.inc.php');
require_once('../auth.php');
require_once(dirname(__FILE__).'/../../include/wawi_benutzerberechtigung.class.php');
require_once(dirname(__FILE__).'/../../../../include/functions.inc.php');
require_once(dirname(__FILE__).'/../../include/wawi_rechnung.class.php');
require_once(dirname(__FILE__).'/../../include/wawi_bestellung.class.php');
require_once(dirname(__FILE__).'/../../include/wawi_kostenstelle.class.php');
require_once(dirname(__FILE__).'/../../../../include/studiensemester.class.php');
require_once(dirname(__FILE__).'/../../../../include/tags.class.php');
require_once(dirname(__FILE__).'/../../../../include/geschaeftsjahr.class.php');
require_once(dirname(__FILE__).'/../../../../include/datum.class.php');
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style;

$user = get_uid();
$rechte = new wawi_benutzerberechtigung();
$rechte->getBerechtigungen($user);

$kst_array = $rechte->getKostenstelle('wawi/bestellung');
$kst_array = array_merge($kst_array, $rechte->getKostenstelle('wawi/rechnung'));
$kst_array = array_merge($kst_array, $rechte->getKostenstelle('wawi/kostenstelle'));
$kst_array = array_merge($kst_array, $rechte->getKostenstelle('wawi/freigabe'));
$kst_array = array_merge($kst_array, $rechte->getKostenstelle('wawi/berichte'));
$kst_array = array_unique($kst_array);
if(count($kst_array)==0)
	die('Sie benoetigen eine Kostenstellenberechtigung um diese Seite anzuzeigen');

$export = (string)filter_input(INPUT_GET, 'export');
$ohne_lieferant = (boolean)filter_input(INPUT_GET, 'ohne_lieferant');
$ohne_konto = (boolean)filter_input(INPUT_GET, 'ohne_konto');
$ohne_kostenstelle = (boolean)filter_input(INPUT_GET, 'ohne_kostenstelle');
$ohne_monat = (boolean)filter_input(INPUT_GET, 'ohne_monat');
$filter_kostenstelle = (boolean)filter_input(INPUT_GET, 'filter_kostenstelle');

$datum_obj = new datum();
$db = new basis_db();

$gj = new geschaeftsjahr();

$geschaeftsjahr = isset($_REQUEST['geschaeftsjahr'])?$_REQUEST['geschaeftsjahr']:$gj->getakt();

$gj->getAll();

if(!count($gj->result))
	die("Es sind noch keine Geschäftsjahre angelegt");

if(!$geschaeftsjahr || $geschaeftsjahr == "")
	$geschaeftsjahr = $gj->result[count($gj->result)-1]->geschaeftsjahr_kurzbz;

$kalenderjahr = isset($_REQUEST['kalenderjahr'])?$_REQUEST['kalenderjahr']:date('Y');
$ohne_bestelldatum = $ohne_monat || $ohne_kostenstelle || $ohne_konto || $ohne_lieferant;

$target_lieferant = (!$ohne_lieferant?'sub.firma_id,sub.lieferant,':'');
$subtarget_lieferant = (!$ohne_lieferant?'f.name as lieferant,':'');
$group_lieferant = (!$ohne_lieferant?'sub.lieferant,sub.firma_id,':'');
$target_konto = (!$ohne_konto?',sub.konto_id,kontonr, konto_kurzbz':'');
$group_konto = (!$ohne_konto?',sub.konto_id,sub.kontonr, sub.konto_kurzbz ':'');
$order_konto = (!$ohne_konto?',konto_kurzbz ':'');
$target_kostenstelle = (!$ohne_kostenstelle?',sub.kostenstelle_id, kst_kurzbz, kst_bezeichnung':'');
$group_kostenstelle = (!$ohne_kostenstelle?',sub.kostenstelle_id, sub.kst_kurzbz, sub.kst_bezeichnung':'');
$order_kostenstelle = (!$ohne_kostenstelle?',kst_kurzbz ':'');
$where_kostenstelle = ($filter_kostenstelle?' and kostenstelle_id='.$_REQUEST['filter_kostenstelle']:'');
$target_monat = (!$ohne_monat?'EXTRACT(month from sub.datum) as mm,':'');
$group_monat = (!$ohne_monat?'mm,':'');
$order_monat = (!$ohne_monat?',mm':'');
$target_yyyy = "EXTRACT(year from sub.datum) as yyyy,";
$target_bestelldatum = (!$ohne_bestelldatum?"to_char(sub.datum,'DD.MM.YYYY') as bestelldatum,":"");
$group_bestelldatum = (!$ohne_bestelldatum?'bestelldatum,':'');;

if(isset($_REQUEST['kalenderjahr']) && isset($_REQUEST['show_kalenderjahr']))
	{
		//Kalenderjahr
		$vondatum = $kalenderjahr.'-01-01';
		$endedatum = $kalenderjahr.'-12-31';
	}
  else
	{
		//Geschaeftsjahr
		$_gj= new geschaeftsjahr();
		$_gj->load($geschaeftsjahr);

		$vondatum = $_gj->start;
		$endedatum = $_gj->ende;

		if ($ohne_monat)
			$target_yyyy="case when EXTRACT(month from sub.datum)>8
							then EXTRACT(year from sub.datum)
							else EXTRACT(year from sub.datum)-1
						end as yyyy,";
	}


/*
$qry="select monatlich.*,rank() OVER (PARTITION BY concat(monatlich.mm,monatlich.yyyy) ORDER BY monatlich.brutto_mm DESC nulls last)
		from
		(

		select sub.firma_id,sub.lieferant,EXTRACT(month from sub.datum) as mm,EXTRACT(year from sub.datum) as yyyy,sum(sub.brutto) as brutto_mm
		 from

		(
		select f.name as lieferant, bestellung.*,bdetail.netto,bdetail.brutto,rechnung.rnetto,rechnung.rbrutto from (select b.bestellung_id,b.firma_id,s.datum,b.bestell_nr,b.titel from wawi.tbl_bestellung b join wawi.tbl_bestellung_bestellstatus s using(bestellung_id) where s.bestellstatus_kurzbz='Bestellung' and (s.datum>'".addslashes($vondatum)."' and s.datum<'".addslashes($endedatum)."')) as bestellung left join
		(select distinct r.bestellung_id, sum(rb.betrag) as rnetto, sum(rb.betrag*(100.0+coalesce(rb.mwst))/100.0) as rbrutto from wawi.tbl_rechnung as r left join wawi.tbl_rechnungsbetrag rb using(rechnung_id) group by r.bestellung_id) as rechnung using(bestellung_id)
		LEFT JOIN (SELECT
		                    detail.bestellung_id,
		                    sum((detail.menge * detail.preisprove)) as netto,
		                    sum((detail.menge * detail.preisprove) * ((100+COALESCE(detail.mwst,0))/100)) as brutto
		                FROM
		                    wawi.tbl_bestelldetail as detail

		                GROUP BY detail.bestellung_id) as bdetail using(bestellung_id)
		left join tbl_firma f using (firma_id)
		order by brutto desc
		) as sub
		group by sub.lieferant,sub.firma_id,mm,yyyy

		) as monatlich order by yyyy asc, mm asc, rank asc";*/

$kstIN=$db->implode4SQL($kst_array);

$qry="select $target_bestelldatum $target_lieferant $target_monat $target_yyyy sum(sub.brutto) as brutto_mm,sum(sub.rbrutto) as rbrutto_mm $target_konto $target_kostenstelle
 from

(
select $subtarget_lieferant konto.kontonr,konto.kurzbz as konto_kurzbz, kst.kurzbz as kst_kurzbz,kst.bezeichnung as kst_bezeichnung, bestellung.*, bdetail.netto,bdetail.brutto, rechnung.rnetto, rechnung.rbrutto
from (select b.bestellung_id,b.firma_id,s.datum,b.bestell_nr,b.titel,b.konto_id,b.kostenstelle_id from wawi.tbl_bestellung b join wawi.tbl_bestellung_bestellstatus s using(bestellung_id) where s.bestellstatus_kurzbz='Bestellung' and (s.datum>='".addslashes($vondatum)."' and s.datum<='".addslashes($endedatum)."')
    and b.kostenstelle_id in ($kstIN) $where_kostenstelle
) as bestellung left join
(select distinct r.bestellung_id, sum(rb.betrag) as rnetto, sum(rb.betrag*(100.0+coalesce(rb.mwst))/100.0) as rbrutto from wawi.tbl_rechnung as r left join wawi.tbl_rechnungsbetrag rb using(rechnung_id) group by r.bestellung_id) as rechnung using(bestellung_id)
LEFT JOIN (SELECT
                    detail.bestellung_id,
                    sum((detail.menge * detail.preisprove)) as netto,
                    sum((detail.menge * detail.preisprove) * ((100+COALESCE(detail.mwst,0))/100)) as brutto
                FROM
                    wawi.tbl_bestelldetail as detail

                GROUP BY detail.bestellung_id) as bdetail using(bestellung_id)
left join tbl_firma f using (firma_id)
left join wawi.tbl_konto konto using(konto_id)
left join wawi.tbl_kostenstelle kst using(kostenstelle_id)
order by brutto desc
) as sub
group by $group_bestelldatum $group_lieferant $group_monat yyyy $group_konto $group_kostenstelle
order by yyyy $order_monat $order_konto $order_kostenstelle";

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
	<link rel="stylesheet" href="../../../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../skin/wawi.css" type="text/css">

	<script type="text/javascript" src="../../../../vendor/jquery/jquery1/jquery-1.12.4.min.js"></script>
	<script type="text/javascript" src="../../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
	<script type="text/javascript" src="../../include/js/tablesorter-setup.js"></script>

</head>
<body>
<h1>Bericht - Bestellungen</h1>
<?php


	echo '<form action="'.$_SERVER['PHP_SELF'].'" method="GET">';
	echo '<table>';

	echo '<tr>';
	echo '<td>Spalten: </td><td>';
	echo '<label><input type="checkbox" name="ohne_monat" value="1" '.($ohne_monat?'checked':'').'/> ohne Monat</label> ';
	echo '<label><input type="checkbox" name="ohne_lieferant" value="1" '.($ohne_lieferant?'checked':'').'/> ohne Lieferant</label> ';
	echo '<label><input type="checkbox" name="ohne_konto" value="1" '.($ohne_konto?'checked':'').'/> ohne Konto</label> ';
	echo '<label><input type="checkbox" name="ohne_kostenstelle" value="1" '.($ohne_kostenstelle?'checked':'').'/> ohne Kostenstelle</label> ';


	echo '</td>';
	echo '</tr>';

	echo '<tr>';
	echo '<td>Filter: </td>';

	//$kst_array = $rechte->getKostenstelle('wawi/bestellung');
	$kostenstelle = new wawi_kostenstelle_extended();
	$kostenstelle->loadArray($kst_array,'bezeichnung');

	echo "<td> Kostenstelle: ";
	echo "<SELECT name='filter_kostenstelle' id='searchKostenstelle' style='width: 350px;'>\n";
	echo "<option value=''>-- auswählen --</option>\n";
	foreach($kostenstelle->result as $kostenst)
	{
		echo '<option value="'.$kostenst->kostenstelle_id.'" '.($filter_kostenstelle && $kostenst->kostenstelle_id==$_REQUEST['filter_kostenstelle']?'selected':'').' >'.$kostenst->bezeichnung."</option>\n";
	}

	echo "</SELECT>\n";


	echo '</td>';
	echo '</tr>';

	echo '<tr><td>';
	//Geschaeftsjahr
	echo '
	Geschäftsjahr: </td><td>
	<SELECT name="geschaeftsjahr" >';


	foreach ($gj->result as $gjahr)
	{
		if($gjahr->geschaeftsjahr_kurzbz==$geschaeftsjahr)
			$selected='selected';
		else
			$selected='';
		echo '<option value="'.$gjahr->geschaeftsjahr_kurzbz.'" '.$selected.'>'.$gjahr->geschaeftsjahr_kurzbz.'</option>';
	}

	echo '
	</SELECT>
	<input type="submit" value="Anzeigen" name="show_geschaeftsjahr">';

	echo '</td><td width="60px"> &nbsp; </td><td>';

	//Kalenderjahr
	echo '
	Kalenderjahr
	<SELECT name="kalenderjahr" >';



	for($i=date('Y')-5; $i<date('Y')+2; $i++)
	{
		if($i==$kalenderjahr)
			$selected='selected';
		else
			$selected='';
		echo '<option value="',$i,'" ',$selected,'>1.1.',$i,' - 31.12.',$i,'</option>';
	}
	echo '
	</SELECT>
	<input type="submit" value="Anzeigen" name="show_kalenderjahr">';

	echo '</td>';
	echo '</td><td width="50px"> &nbsp; </td>';
	echo '<td>';
	echo "<a href=\"".htmlspecialchars($_SERVER['REQUEST_URI']).(!isset($_REQUEST['kalenderjahr']) && !isset($_REQUEST['geschaeftsjahr'])?"?geschaeftsjahr=".$geschaeftsjahr.'&show_geschaeftsjahr=Anzeigen':'')."&export=xlsx\"><IMG src=\"../../../../skin/images/ExcelIcon.png\" > XLSX</a>";
	echo '</td>';
	echo '</tr>';

	echo '</table>';
	echo '</form>';




	//$kstIN=$db->implode4SQL($kst_array);

	$sort_offset = 0;
	if ($ohne_monat)
		$sort_offset--;
	if ($ohne_kostenstelle)
		$sort_offset-=1;
	if ($ohne_lieferant)
		$sort_offset--;
	if ($ohne_konto)
		$sort_offset-=1;
	if ($ohne_bestelldatum)
		$sort_offset-=1;



	echo '<span style="font-size: small">Zeitraum: ',$datum_obj->formatDatum($vondatum,'d.m.Y'),' - ',$datum_obj->formatDatum($endedatum,'d.m.Y').'</span>';
	echo '
	<script type="text/javascript">
	$(document).ready(function()
		{
			$("#myTable").tablesorter(
			{
				sortList: [[1,0],[0,0]],
				widgets: ["zebra"],
				headers: {
                     '.(6+$sort_offset).': { sorter: "digitmittausenderpunkt"},
                     '.(7+$sort_offset).': { sorter: "digitmittausenderpunkt"},
             	}
			});
		});
	 </script>';
	echo '<table id="myTable" class="tablesorter" style="width: auto;">
			<thead>
				<tr>'.
					(!$ohne_monat?'<th>Monat</th>':'').
					'<th>Jahr</th>'.
					(!$ohne_bestelldatum?'<th>Bestelldatum</th>':'').
					(!$ohne_lieferant?'<th>Lieferant</th>':'').
					(!$ohne_konto?'<th>Konto</th>':'').
					(!$ohne_kostenstelle?'<th>Kostenstelle</th>':'').
					'<th>Bestellungen Brutto</th>
					<th>Rechnungen Brutto</th>

					';

	echo '
				</tr>
			</thead>
			<tbody>';

	$gesamt_rechnung = 0;
	$gesamt_bestellung = 0;


	if($result = $db->db_query($qry))
	{
		while($row = $db->db_fetch_object($result))
		{

			//$lastday = new DateTime('last day of '.$row->yyyy.'-'.$row->mm);

			echo '<tr>';
			if (!$ohne_monat)
				echo '<td>'.$row->mm.'</td>';
			echo '<td>'.$row->yyyy.'</td>';
			if (!$ohne_bestelldatum)
				echo '<td>'.$row->bestelldatum.'</td>';
			if (!$ohne_lieferant)
			  {
				echo '<td><a href="../bestellung.php?method=suche&bvon='.$datum_obj->formatDatum($vondatum,'d.m.Y').'&bbis='.$datum_obj->formatDatum($endedatum,'d.m.Y').'&firmenname='.urlencode($row->lieferant).'&firma_id='.$row->firma_id.(!$ohne_kostenstelle?'&filter_kostenstelle='.$row->kostenstelle_id:'').(!$ohne_konto?'&filter_konto='.$row->konto_id:'').'&submit=true">'.$row->lieferant.'</a></td>';
			  }
			if (!$ohne_konto)
			  {
				//echo '<td class="number">'.$row->kontonr.'</td>';
				echo '<td class="number">'.$row->konto_kurzbz.'</td>';
				/*echo '<td><a href="../bestellung.php?method=suche&bvon='.$datum_obj->formatDatum($vondatum,'d.m.Y').'&bbis='.$datum_obj->formatDatum($endedatum,'d.m.Y').'&filter_konto='.$row->konto_id.(!$ohne_lieferant?'&firmenname='.urlencode($row->lieferant).'&firma_id='.$row->firma_id:'').(!$ohne_kostenstelle?'&filter_kostenstelle='.$row->kostenstelle_id:'').'&submit=true">'.$row->konto_kurzbz.'</a></td>';*/
			  }
			if (!$ohne_kostenstelle)
			  {
				//echo '<td class="number">'.$row->kst_kurzbz.'</td>';
				echo '<td class="number">'.$row->kst_bezeichnung.'</td>';
			  }
			echo '<td class="number">'.number_format($row->brutto_mm,2,',','.').'</td>';
			echo '<td class="number">'.number_format($row->rbrutto_mm,2,',','.').'</td>';

			echo '</tr>';

			$gesamt_bestellung += $row->brutto_mm;
			$gesamt_rechnung += $row->rbrutto_mm;

		}
	}
	echo '
		</tbody>
		<tfoot>
			<tr>'.
				(!$ohne_monat?'<th></th>':'').
				(!$ohne_bestelldatum?'<th></th>':'').
				(!$ohne_lieferant?'<th></th>':'').
				(!$ohne_konto?'<th></th>':'').
				(!$ohne_kostenstelle?'<th></th>':'').
				'<th>Summe:</th>
				<th class="number">',number_format($gesamt_bestellung,2,',','.'),'</th>
				<th class="number">',number_format($gesamt_rechnung,2,',','.'),'</th>';

	echo '
			</tr>
		</tfoot>
		</table>';
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
		header('Content-Disposition: attachment;filename="bestellungen.xlsx"');
		header('Cache-Control: max-age=0');


		$spreadsheet = new Spreadsheet();  // \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle('Lieferanten Ranking');


		$styleArray = [
		 'font' =>['bold' => true],
		 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'color' => ['argb' => 'FFCCFFCC']],
		 'alignment' =>['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
		 'borders'=>['bottom' =>['borderStyle'=> \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM]]
		];

		$spalten = ['A','B','C','D','E','F','G','H'];
		$spalten_bezeichnung = ['Monat','Jahr','Bestelldatum','Lieferant','Konto','Kostenstelle','Bestellungen Brutto','Rechnungen Brutto'];

		$spalten_anzahl = 0;
		for ($i=0; $i < count($spalten); $i++) {
			if ($ohne_monat && $spalten_bezeichnung[$i] == 'Monat')
				continue;
			if ($ohne_bestelldatum && $spalten_bezeichnung[$i] == 'Bestelldatum')
				continue;
			if ($ohne_lieferant && $spalten_bezeichnung[$i] == 'Lieferant')
				continue;
			if ($ohne_konto && (/*$spalten_bezeichnung[$i] == 'Kontonr' ||*/ $spalten_bezeichnung[$i] == 'Konto'))
				continue;
			if ($ohne_kostenstelle && ($spalten_bezeichnung[$i] == 'Kostenstelle' /*|| $spalten_bezeichnung[$i] == 'Kostenstelle-Nr'*/))
				continue;
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
				if (!$ohne_monat)
					$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->mm);
				$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->yyyy);
				// Bestelldatum
				if (!$ohne_bestelldatum)
				  $sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->bestelldatum);
				if (!$ohne_lieferant)
				  $sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->lieferant);
				if (!$ohne_konto)
			  	  {
					//$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->kontonr);
					$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->konto_kurzbz);
				  }
				if (!$ohne_kostenstelle)
				  {
					//$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->kst_kurzbz);
					$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->kst_bezeichnung);
				  }
				$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",number_format($row->brutto_mm,2,'.',''));
				$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",number_format($row->rbrutto_mm,2,'.',''));
				$rownum++;
			  }
		  }



		foreach ($spalten as $key ) {
			$sheet->getColumnDimension($key)->setAutoSize(true);
		}

		$writer = new Xlsx($spreadsheet);
		$writer->save('php://output');

	}
