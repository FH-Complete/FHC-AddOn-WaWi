<?php
/* Copyright (C) 2010 Technikum-Wien
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 * Authors: Christian Paminger <christian.paminger@technikum-wien.at>,
 *          Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at>,
 *          Karl Burkhart <burkhart@technikum-wien.at> and
 *          Andreas Moik <moik@technikum-wien.at>.
 */

require_once(dirname(__FILE__).'/../config.inc.php');
require_once('auth.php');

require_once '../include/wawi_konto.class.php';
require_once '../include/wawi_bestellung.class.php';
require_once '../include/wawi_kostenstelle.class.php';
require_once '../include/wawi_bestelldetail.class.php';
require_once '../include/wawi_aufteilung.class.php';
require_once '../include/wawi_bestellstatus.class.php';
require_once '../include/wawi_zahlungstyp.class.php';
require_once dirname(__FILE__).'/../../../include/datum.class.php';
require_once dirname(__FILE__).'/../../../include/firma.class.php';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Offene Freigaben/Lieferungen</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css"/>
	<link rel="stylesheet" href="../../../skin/jquery.css" type="text/css"/>
	<link rel="stylesheet" href="../../../skin/fhcomplete.css" type="text/css"/>
	<link rel="stylesheet" href="../skin/wawi.css" type="text/css"/>

	<script type="text/javascript" src="../../../vendor/jquery/jquery1/jquery-1.12.4.min.js"></script>
	<script type="text/javascript" src="../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
	<script type="text/javascript" src="../include/js/tablesorter-setup.js"></script>

	<script type="text/javascript">
	function checkKst()
	{
		if(isNaN(document.checkForm.min.value) || isNaN(document.checkForm.max.value))
		{
			alert("Bitte geben Sie eine Nummer ein.");
			return false;
		}
		return true;
	}

	function updateReport()
	{
		var report = $('input[name="reportselector"]:checked').val();
		console.log(report);
		$('input[name="type"]').val(report);
		$('form[name="reportForm"]').submit();
	}
	</script>

</head>
<body>

<?php
$min = (isset($_POST['min'])?$_REQUEST['min']:'1');
$max = (isset($_POST['max'])?$_REQUEST['max']:'42');
$type = (isset($_REQUEST['type'])?$_REQUEST['type']:'');

if ($type == 'nichtgeliefert')
	echo "<h1>Offene Lieferungen</h1>";
else if ($type == 'nichtbestellt')
	echo "<h1>Freigegeben aber nicht bestellt</h1>";
else
	echo "<h1>Offene Freigaben</h1>";
?>
<table>
	<tr>
	<td>
		<form action ="check_bestellung.php" method="post" name="checkForm">
		<table>
			<tr><td>min (Wochen): </td><td><input type="text" name="min" id="min" value="<?php echo $min ?>"></td></tr>
			<tr><td>max (Wochen): </td><td><input type="text" name="max" id="max" value="<?php echo $max ?>"></td></tr>
			<tr><td>&nbsp;</td><td><input type="submit" name="submit" value="anzeigen" onclick="return checkKst();"></td></tr>
		</table>
		<input type="hidden" name="type" value="<?php echo $type ?>" >
		</form>
	</td>
	<td width="100px">&nbsp;</td>
	<td valign="top">
	<form action ="check_bestellung.php" method="post" name="reportForm">
		<fieldset>
		    <legend>Berichtauswahl: </legend>
		    <label for="radio-1">Offene Freigaben</label>
		    <input type="radio" name="reportselector" id="radio-1" <?php if ($type == '' || $type=='offenefreigaben') echo 'checked="checked"' ?> onchange="updateReport(this)" value="offenefreigaben">
		    <label for="radio-2">Nicht bestellt</label>
		    <input type="radio" name="reportselector" id="radio-2" <?php if ($type == 'nichtbestellt') echo 'checked="checked"' ?> onchange="updateReport(this)" value="nichtbestellt">
		    <label for="radio-3">Nicht geliefert</label>
		    <input type="radio" name="reportselector" id="radio-3" <?php if ($type == 'nichtgeliefert') echo 'checked="checked"' ?> onchange="updateReport(this)" value="nichtgeliefert">
		    <input type="hidden" name="type" value="<?php echo $type ?>" >
	    </fieldset>
	</form>

	</td>
	</tr>
</table>

<?php


if ($type == '' || $type=='offenefreigaben')
{
	echo '
		<script type="text/javascript">
		$(document).ready(function()
		{
			$("#checkTable").tablesorter(
			{
				sortList: [[4,1]],
				widgets: ["zebra"],
				headers: {
                     3: { sorter:"dedate" },
                     5: { sorter: "digitmittausenderpunkt"},
             	}
			});
		});
		</script>';
} else if ($type == 'nichtbestellt')
{
	echo '
		<script type="text/javascript">
		$(document).ready(function()
		{
			$("#checkTable").tablesorter(
			{
				sortList: [[4,1]],
				widgets: ["zebra"],
				headers: {
                     3: { sorter:"dedate" },
                     5: { sorter: "digitmittausenderpunkt"},
             	}
			});
		});
		</script>';
} else
{
	echo '
		<script type="text/javascript">
		$(document).ready(function()
		{
			$("#checkTable").tablesorter(
			{
				sortList: [[4,1]],
				widgets: ["zebra"],
				headers: {
                     3: { sorter:"dedate" },
                     4: { sorter:"dedate" },
                     7: { sorter: "digitmittausenderpunkt"},
             	}
			});
		});
		</script>';
}




	$date = new datum();
	$firma = new firma();
	$bestellung = new wawi_bestellung();
	if($type=='nichtgeliefert')
		$bestellung->loadBestellungNichtGeliefert();
	else if ($type=='nichtbestellt')
		$bestellung->loadNichtBestellt();
	else if(is_numeric($min) && is_numeric($max) && ($type=='offenefreigaben' || $type==''))
	{
		$bestellung->loadBestellungForCheck($min, $max);
	}
	else
		die('Fehlerhafte Parameter');



	echo '	<table id="checkTable" class="tablesorter" width ="100%">
			<thead>
			<tr>
				<th></th>
				<th>Bestellnr.</th>
				<th>Firma</th>

				'.
				($type!='nichtgeliefert'?
					'<th>Erstellt</th><th>Geliefert</th>':
					'<th>Bestellt</th><th>Auftragsbestätigung</th><th>Freigegeben</th><th>Liefertermin</th>').

				'<th>Brutto</th>
				<th>Titel</th>
			</tr>
			</thead>
			<tbody>';
	foreach($bestellung->result as $row)
	{
		$firmenname = '';
		$geliefert ='nein';
		$geliefert_datum = null;
		$bestellt ='nein';
		$bestellt_datum = null;
		$status = new wawi_bestellstatus();
		if(is_numeric($row->firma_id))
		{
			$firma->load($row->firma_id);
			$firmenname = $firma->name;
		}
		if($row->freigegeben)
		{
			$freigegeben = 'ja';
			if ($type!='nichtgeliefert' && $type!='nichtbestellt') continue;
		}
		else {
            $freigegeben = 'nein';
            if ($type=='nichtbestellt') continue;
        }


		if($status->isStatiVorhanden($row->bestellung_id, 'Lieferung'))
		{
			$geliefert = 'ja';
			$geliefert_datum = $status->datum;
		}

		if($status->isStatiVorhanden($row->bestellung_id, 'Bestellung'))
		{
			$bestellt = 'ja';
			$bestellt_datum = $status->datum;
		}


		$brutto = $bestellung->getBrutto($row->bestellung_id);
		echo '	<tr>
					<td nowrap><a href="bestellung.php?method=update&id='.$row->bestellung_id.'" title="Bestellung bearbeiten"> <img src="../skin/images/edit_wawi.gif"></a><a href="bestellung.php?method=delete&id='.$row->bestellung_id.'" onclick="return conf_del()" title="Bestellung löschen"> <img src="../../../skin/images/delete_x.png"></a></td>
					<td>'.$row->bestell_nr.'</td>
					<td>'.$firmenname.'</td>'.
					($type!='nichtgeliefert'
					?
						'<td>'.$date->formatDatum($row->insertamum, "d.m.Y").'</td>'.
						'<td>'.$geliefert.'</td>'
					:
						'<td>'.($bestellt_datum != null ? $date->formatDatum($bestellt_datum, "d.m.Y") : 'nein').'</td>'.
						'<td>'.($row->auftragsbestaetigung != null ? $date->formatDatum($row->auftragsbestaetigung, "d.m.Y") : 'nein').'</td>
						<td>'.$freigegeben.'</td>
						<td>'.$row->liefertermin.'</td>').'
					<td align="right">'.number_format($brutto, 2, ",",".").'</td>
					<td>'.$row->titel.'</td>
				</tr>';
	}
	echo '	</tbody>
			</table>';

?>
