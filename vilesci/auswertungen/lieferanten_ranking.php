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

$ohne_monat = (boolean)filter_input(INPUT_GET, 'ohne_monat');

$export = (string)filter_input(INPUT_GET, 'export');

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

$gj_target='EXTRACT(year from sub.datum) as yyyy';

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
			$gj_target="case when EXTRACT(month from sub.datum)>8
							then EXTRACT(year from sub.datum)
							else EXTRACT(year from sub.datum)-1
						end as yyyy";
	}


$qry="select monatlich.*,rank() OVER (PARTITION BY ".(!$ohne_monat?"concat(monatlich.mm,monatlich.yyyy)":"monatlich.yyyy")." ORDER BY monatlich.brutto_mm DESC nulls last)
		from
		(

		select sub.firma_id,sub.lieferant,sub.is_lieferant,sub.homepage,".(!$ohne_monat?"EXTRACT(month from sub.datum) as mm,":"").
			$gj_target.",
			sub.firma_id,sum(sub.rbrutto) as brutto_mm
		 from

		(
		select f.name as lieferant, f.lieferant as is_lieferant, firma_hp.homepage, bestellung.*,bdetail.netto,bdetail.brutto,rechnung.rnetto,rechnung.rbrutto from (select b.bestellung_id,b.firma_id,s.datum,b.bestell_nr,b.titel from wawi.tbl_bestellung b join wawi.tbl_bestellung_bestellstatus s using(bestellung_id) where s.bestellstatus_kurzbz='Bestellung' and (s.datum>='".addslashes($vondatum)."' and s.datum<='".addslashes($endedatum)."')) as bestellung left join
		(select distinct r.bestellung_id, sum(rb.betrag) as rnetto, sum(rb.betrag*(100.0+coalesce(rb.mwst))/100.0) as rbrutto from wawi.tbl_rechnung as r left join wawi.tbl_rechnungsbetrag rb using(rechnung_id) group by r.bestellung_id) as rechnung using(bestellung_id)
		LEFT JOIN (SELECT
		                    detail.bestellung_id,
		                    sum((detail.menge * detail.preisprove)) as netto,
		                    sum((detail.menge * detail.preisprove) * ((100+COALESCE(detail.mwst,0))/100)) as brutto
		                FROM
		                    wawi.tbl_bestelldetail as detail

		                GROUP BY detail.bestellung_id) as bdetail using(bestellung_id)
		left join tbl_firma f using (firma_id)
		left join
			(
				select distinct on (f.firma_id) f.firma_id,k.kontakt as homepage
				from tbl_firma as f join  tbl_standort using(firma_id) join tbl_kontakt k using(standort_id)
				where k.kontakttyp='homepage'
			) as firma_hp using(firma_id)
		order by brutto desc
		) as sub
		group by sub.lieferant,sub.firma_id, sub.homepage,sub.is_lieferant, ".(!$ohne_monat?"mm,":"")."yyyy

		) as monatlich order by yyyy asc,".(!$ohne_monat?" mm asc,":"")." rank asc";


//echo $qry;

if ($export == '' || $export == 'html')
	{
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>WaWi - Lieferanten - Auswertung</title>
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
<h1>Bericht - Lieferanten</h1>
<?php


	echo '<form action="'.$_SERVER['PHP_SELF'].'" method="GET">';



	echo '<table>';
	echo '<tr>';
	echo '<td>Spalten: ';
	echo '<label><input type="checkbox" name="ohne_monat" value="1" '.($ohne_monat?'checked':'').'/> ohne Monat</label> ';
	echo '</td>';
	echo '</tr>';

	echo '<tr><td>';
	//Geschaeftsjahr
	echo '
	Geschäftsjahr
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
	echo '</tr></table>';
	echo '</form>';




	//$kstIN=$db->implode4SQL($kst_array);


	$sort_offset = 0;
	if ($ohne_monat)
		$sort_offset--;


	echo '<span style="font-size: small">Zeitraum: ',$datum_obj->formatDatum($vondatum,'d.m.Y'),' - ',$datum_obj->formatDatum($endedatum,'d.m.Y').'</span>';
	echo '
	<script type="text/javascript">
	$(document).ready(function()
		{
			$("#myTable").tablesorter(
			{
				sortList: [[3,0],[2,0],[5,0]],
				widgets: ["zebra"],
				headers: {
                     '.(4+$sort_offset).': { sorter: "digitmittausenderpunkt"},
             	}
			});
		});
	 </script>';
	echo '<table id="myTable" class="tablesorter" style="width: auto;">
			<thead>
				<tr>
				    <th>Lieferant-ID</th>
					<th>Lieferant</th>'.
					(!$ohne_monat?'<th>Monat</th>':'').
					'<th>Jahr</th>
					<th>Brutto</th>
					<th>Rang</th>
					<th>Lieferant</th>
					<th>Homepage</th>';

	echo '
				</tr>
			</thead>
			<tbody>';

	$gesamt_rechnung = 0;
	$gesamt_bestellung = 0;

	$evon = $datum_obj->formatDatum($vondatum,'d.m.Y');
	$ebis = $datum_obj->formatDatum($endedatum,'d.m.Y');

	if($result = $db->db_query($qry))
	{
		while($row = $db->db_fetch_object($result))
		{

			if (!$ohne_monat)
			  {
				$lastday = new DateTime('last day of '.$row->yyyy.'-'.$row->mm);
				$evon='1.'.$row->mm.'.'.$row->yyyy;
				$ebis=$lastday->format('d.m.Y');
			  }
			else if(isset($_REQUEST['kalenderjahr']) && isset($_REQUEST['show_kalenderjahr']))
			  {
				//Kalenderjahr
				$evon='1.1.'.$row->yyyy;
				$ebis='31.12.'.$row->yyyy;
			  }

			echo '<tr>';
			echo '<td>'.$row->firma_id.'</td>';
			echo '<td>'.$row->lieferant.'</td>';
			if (!$ohne_monat)
				echo '<td>'.$row->mm.'</td>';
			echo '<td>'.$row->yyyy.'</td>';
			echo '<td class="number"><a href="../bestellung.php?method=suche&bvon='.$evon.'&bbis='.$ebis.'&firmenname='.urlencode($row->lieferant).'&firma_id='.$row->firma_id.'&submit=true">',number_format($row->brutto_mm,2,',','.'),'</a></td>';
			echo '<td class="number">'.$row->rank.'</td>';
			echo '<td style="text-align:center">'.($row->is_lieferant=='t'?'X':'').'</td>';
			echo '<td>'.$row->homepage.'</td>';
			echo '</tr>';

			$gesamt_bestellung += $row->brutto_mm;
			//$gesamt_bestellung += $brutto['bestellung'];

		}
	}
	echo '
		</tbody>
		<tfoot>
			<tr>
				'.(!$ohne_monat?'<th></th>':'').'
				<th></th>
				<th>Summe:</th>
				<th class="number">',number_format($gesamt_bestellung,2,',','.'),'</th>
				<th ></th>';

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
		header('Content-Disposition: attachment;filename="lieferanten.xlsx"');
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
		$spalten_bezeichnung = ['Lieferant-ID','Lieferant','Monat','Jahr','Brutto','Rang','Lieferant','Homepage'];

		$spalten_anzahl = 0;
		for ($i=0; $i < count($spalten); $i++) {
			if ($ohne_monat && $spalten_bezeichnung[$i] == 'Monat')
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
			  	$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->firma_id);
				$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->lieferant);
				if (!$ohne_monat)
					$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->mm);
				$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->yyyy);
				$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",number_format($row->brutto_mm,2,'.',''));
				$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->rank);
				$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->is_lieferant=='t'?'X':'');
				$sheet->setCellValue($spalten[$spaltenindex++]."$rownum",$row->homepage);
				$rownum++;
			  }
		  }

		foreach ($spalten as $key ) {
			$sheet->getColumnDimension($key)->setAutoSize(true);
		}

		$writer = new Xlsx($spreadsheet);
		$writer->save('php://output');

	}
