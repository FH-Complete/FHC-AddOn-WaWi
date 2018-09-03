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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
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

require_once dirname(__FILE__).'/../../../include/firma.class.php';
require_once dirname(__FILE__).'/../../../include/organisationseinheit.class.php';
require_once dirname(__FILE__).'/../../../include/mitarbeiter.class.php';
require_once dirname(__FILE__).'/../../../include/datum.class.php';
require_once '../include/wawi_benutzerberechtigung.class.php';
require_once dirname(__FILE__).'/../../../include/standort.class.php';
require_once dirname(__FILE__).'/../../../include/adresse.class.php';
require_once dirname(__FILE__).'/../../../include/studiengang.class.php';
require_once dirname(__FILE__).'/../../../include/mail.class.php';
require_once dirname(__FILE__).'/../../../include/geschaeftsjahr.class.php';
require_once '../include/wawi_konto.class.php';
require_once '../include/wawi_zuordnung.class.php';
require_once '../include/wawi_bestellung.class.php';
require_once '../include/wawi_kostenstelle.class.php';
require_once '../include/wawi_bestelldetail.class.php';
require_once '../include/wawi_aufteilung.class.php';
require_once '../include/wawi_bestellstatus.class.php';
require_once '../include/wawi_zahlungstyp.class.php';
require_once '../include/wawi_angebot.class.php';
require_once dirname(__FILE__).'/../../../include/tags.class.php';
require_once dirname(__FILE__).'/../../../include/projekt.class.php';
require_once '../include/functions.inc.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style;

$aktion ='';
$test = 0;			// Bestelldetail Anzahl
$date = new datum();
$user=get_uid();
$ausgabemsg='';

$berechtigung_kurzbz='wawi/bestellung';
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);
$kst=new wawi_kostenstelle();
$kst->loadArray($rechte->getKostenstelle($berechtigung_kurzbz),'bezeichnung');

$projekt = new projekt();
$projekt->getProjekteMitarbeiter($user);
$projektZugeordnet = false;

$export = (string)filter_input(INPUT_GET, 'export');

// Abfrage ob dem user ein oder mehrere Projekte zugeordnet sind
if(count($projekt->result) > 0)
	$projektZugeordnet = true;

if(isset($_POST['getKonto']))
{
	$id = $_POST['id'];
	if(is_numeric($id))
	{
		$konto = new wawi_konto();
		$konto->getKontoFromKostenstelle($id);
		if(count($konto->result)>0)
		{
			foreach($konto->result as $ko)
			{
				echo '<option value='.$ko->konto_id.' >'.$ko->kurzbz."</option>\n";
			}
		}
		else
			echo "<option value =''>Keine Konten zu dieser Kst</option>";
	}
	else
		echo "<option value =''>Keine Konten zu dieser Kst</option>";
	exit;
}

if(isset($_POST['getFirma']))
{
	$id = $_POST['id'];
	if(isset($_POST['id']))
	{
		if($_POST['id'] == 'opt_auswahl')
		{
			echo "<option value=''>-- auswählen --</option>\n";
		}
		else
		{
			// anzeige der Firmen die oe zugeordnet sind
			$firma = new firma();
			$firma->get_firmaorganisationseinheit(null,$id);
			if(count($firma->result)>0)
			{
				echo "<option value=''>-- auswählen --</option>\n";
				foreach($firma->result as $fi)
				{
					echo '<option value='.$fi->firma_id.' >'.$fi->name."</option>\n";
				}
			}
			else
				echo "<option value =''>Keine Firmen zu dieser OE</option>";
		}
	}
	else
		echo "<option value =''>Keine Firmen zu dieser OE</option>";
	exit;
}

if(isset($_POST['getSearchKonto']))
{
	$id = $_POST['id'];
	if(isset($_POST['id']))
	{
		if($_POST['id'] == 'opt_auswahl')
		{
			$konto = new wawi_konto();
			$konto->getAll(true,'kontonr ASC');
			// anzeige aller Konten
			echo "<option value=''>-- auswählen --</option>\n";
			foreach($konto->result as $ko)
			{
				echo '<option value='.$ko->konto_id.' >'.$ko->kurzbz."</option>\n";
			}
		}
		else
		{
			// anzeige aller Konten die der Kostenstelle zugeordnet sind
			$konto = new wawi_konto();
			$konto->getKontoFromOE($id);
			if(count($konto->result)>0)
			{
				echo "<option value=''>-- auswählen --</option>\n";
				foreach($konto->result as $ko)
				{
					echo '<option value='.$ko->konto_id.' >'.$ko->kurzbz."</option>\n";
				}
			}
			else
				echo "<option value =''>Kein Konto zu dieser OE</option>";
		}
	}
	else
		echo "<option value =''>Kein Konto zu dieser OE</option>";
	exit;
}

if(isset($_POST['getDetailRow']) && isset($_POST['id']))
{
	if(is_numeric($_POST['id']))
	{
		echo getDetailRow($_POST['id'],'','','1','','','','','','',$_POST['bestellung_id'],$_POST['id']);
		$test++;
		exit;
	}
	else
	{
		die('ID ungueltig');
	}
}

if(isset($_POST['deleteDetail']) && isset($_POST['id']))
{
	if(is_numeric($_POST['id']))
	{
		$detail = new wawi_bestelldetail();
		$bestellung = new wawi_bestellung();

		if(!$detail->load($_POST['id']))
			die('Eintrag wurde nicht gefunden');
		if(!$bestellung->load($detail->bestellung_id))
			die('Bestellung konnte nicht geladen werden');

		if(!$rechte->isberechtigt('wawi/bestellung',null, 'suid', $bestellung->kostenstelle_id))
			die('Sie haben keine Berechtigung fuer diese Aktion');

		$detail->delete($_POST['id']);
		exit;
	}
	else
	{
		die('ID ungültig: '.$_POST['id']);
	}
}

if(isset($_POST['saveDetail']))
{
	$bestellung = new wawi_bestellung();
	if(!$bestellung->load($_POST['bestellung']))
		die('Bestellung konnte nicht geladen werden');

	if(!$rechte->isberechtigt('wawi/bestellung',null, 'sui', $bestellung->kostenstelle_id))
		die('Sie haben keine Berechtigung zum Aendern der Daten');

	$detail = new wawi_bestelldetail();
	$detail->bestellung_id = $_POST['bestellung'];
	$detail->position = $_POST['pos'];
	$detail->menge = $_POST['menge'];
	$detail->verpackungseinheit = $_POST['ve'];
	$detail->beschreibung = $_POST['beschreibung'];
	$detail->artikelnummer = $_POST['artikelnr'];
	$detail->preisprove = $_POST['preis'];
	$detail->mwst = $_POST['mwst'];
	if($_POST['sort'] != '')
		$detail->sort = $_POST['sort'];
	else
		$detail->sort = $_POST['pos'];
	$detail->insertamum = date('Y-m-d H:i:s');
	$detail->updateamum = date('Y-m-d H:i:s');
	$detail->new = true;
	if(!$detail->save())
		echo $detail->errormsg;
	echo $detail->bestelldetail_id;
	exit;
}

if(isset($_POST['updateDetail']))
{
	$bestellung = new wawi_bestellung();
	if(!$bestellung->load($_POST['bestellung']))
		die('Bestellung konnte nicht geladen werden');

	if(!$rechte->isberechtigt('wawi/bestellung',null, 'sui', $bestellung->kostenstelle_id))
		die('Sie haben keine Berechtigung zum Aendern der Daten');

	$detail = new wawi_bestelldetail();
	$detail->bestelldetail_id = $_POST['detail_id'];
	$detail->bestellung_id = $_POST['bestellung'];
	$detail->position = $_POST['pos'];
	$detail->menge = $_POST['menge'];
	$detail->verpackungseinheit = $_POST['ve'];
	$detail->beschreibung = $_POST['beschreibung'];
	$detail->artikelnummer = $_POST['artikelnr'];
	$detail->preisprove = $_POST['preis'];
	$detail->mwst = $_POST['mwst'];
	$detail->sort = $_POST['sort'];
	$detail->insertamum = date('Y-m-d H:i:s');
	$detail->updateamum = date('Y-m-d H:i:s');
	$detail->erhalten = false;
	$detail->text = false;
	$detail->new = false;
	if(!$detail->save())
		echo $detail->errormsg;
	echo $detail->bestelldetail_id;
	exit;
}

if(isset($_POST['deleteBtnGeliefert']) && isset($_POST['id']))
{
	$bestellung = new wawi_bestellung();
	if(!$bestellung->load($_POST['id']))
		die('Bestellung konnte nicht geladen werden');

	if(!$rechte->isberechtigt('wawi/bestellung',null, 'sui', $bestellung->kostenstelle_id))
		die('Sie haben keine Berechtigung fuer diese Aktion');

	$bestellstatus = new wawi_bestellstatus();
	$bestellstatus->bestellung_id = $_POST['id'];
	$bestellstatus->bestellstatus_kurzbz = 'Lieferung';
	$bestellstatus->uid = $_POST['user_id'];
	$bestellstatus->oe_kurzbz = '';
	$bestellstatus->datum = date('Y-m-d H:i:s');
	$bestellstatus->insertvon = $_POST['user_id'];
	$bestellstatus->insertamum = date('Y-m-d H:i:s');
	$bestellstatus->updatevon = $_POST['user_id'];
	$bestellstatus->updateamum = date('Y-m-d H:i:s');
	if($bestellstatus->save())
	{
		echo $date->formatDatum($bestellstatus->datum, 'd.m.Y');
		sendBestellerMail($bestellung, 'geliefert');
	}
	else
		echo $bestellstatus->errormsg;
	exit;
}

if(isset($_POST['deleteBtnBestellt']) && isset($_POST['id']))
{
	$bestellung = new wawi_bestellung();
	if(!$bestellung->load($_POST['id']))
		die('Bestellung konnte nicht geladen werden');

	if(!$rechte->isberechtigt('wawi/bestellung',null, 'sui', $bestellung->kostenstelle_id))
		die('Sie haben keine Berechtigung fuer diese Aktion');

	$bestellstatus = new wawi_bestellstatus();
	$bestellstatus->bestellung_id = $_POST['id'];
	$bestellstatus->bestellstatus_kurzbz = 'Bestellung';
	$bestellstatus->uid = $_POST['user_id'];
	$bestellstatus->oe_kurzbz = '';
	$bestellstatus->datum = date('Y-m-d H:i:s');
	$bestellstatus->insertvon = $_POST['user_id'];
	$bestellstatus->insertamum = date('Y-m-d H:i:s');
	$bestellstatus->updatevon = $_POST['user_id'];
	$bestellstatus->updateamum = date('Y-m-d H:i:s');
	if($bestellstatus->save())
	{
		echo $date->formatDatum($bestellstatus->datum, 'd.m.Y');
		sendBestellerMail($bestellung, 'bestellt');
	}
	else
		echo $bestellstatus->errormsg;
	exit;
}

if(isset($_POST['deleteBtnStorno']) && isset($_POST['id']))
{
	$bestellung = new wawi_bestellung();
	if(!$bestellung->load($_POST['id']))
		die('Bestellung konnte nicht geladen werden');

	if(!$rechte->isberechtigt('wawi/bestellung',null, 'sui', $bestellung->kostenstelle_id))
		die('Sie haben keine Berechtigung fuer diese Aktion');

	$date = new datum();
	$bestellstatus = new wawi_bestellstatus();
	$bestellstatus->bestellung_id = $_POST['id'];
	$bestellstatus->bestellstatus_kurzbz = 'Storno';
	$bestellstatus->uid = $_POST['user_id'];
	$bestellstatus->oe_kurzbz = '';
	$bestellstatus->datum = date('Y-m-d H:i:s');
	$bestellstatus->insertvon = $_POST['user_id'];
	$bestellstatus->insertamum = date('Y-m-d H:i:s');
	$bestellstatus->updatevon = $_POST['user_id'];
	$bestellstatus->updateamum = date('Y-m-d H:i:s');
	if($bestellstatus->save())
	{
		echo $date->formatDatum($bestellstatus->datum, 'd.m.Y');
		sendBestellerMail($bestellung, 'storno');
	}
	else
		echo $bestellstatus->errormsg;
	exit;
}

if ($export == '' || $export == 'html')
  {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>WaWi Bestellung</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css"/>
	<link rel="stylesheet" href="../skin/jquery-ui.min.css" type="text/css"/>
	<link rel="stylesheet" href="../../../skin/fhcomplete.css" type="text/css"/>
	<link rel="stylesheet" href="../skin/wawi.css" type="text/css"/>

	<script type="text/javascript" src="../../../vendor/jquery/jqueryV1/jquery-1.12.4.min.js"></script>
	<script type="text/javascript" src="../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
	<script type="text/javascript" src="../include/js/tablesorter-setup.js"></script>
	<script type="text/javascript" src="../../../vendor/components/jqueryui/jquery-ui.min.js"></script>
	<!--script type="text/javascript" src="../../../include/js/jquery.ui.datepicker.translation.js"></script-->
	<script type="text/javascript" src="../../../vendor/components/jqueryui/ui/i18n/datepicker-de.js"></script>
	<script type="text/javascript" src="../../../vendor/jquery/sizzle/sizzle.js"></script>
	<script type="text/javascript" src="../include/js/tablesorter-setup.js"></script>

	<link rel="stylesheet" type="text/css" href="../skin/jquery-ui.structure.min.css"/>
	<link rel="stylesheet" type="text/css" href="../skin/jquery-ui.theme.min.css"/>

	<script type="text/javascript">
	function conf_del()
	{
		return confirm('Diese Bestellung wirklich löschen?');
	}

	function loadKonto(id)
	{
		$.post("bestellung.php", {id: id, getKonto: 'true'},
		function(data){
			$('#konto').html(data);
		});
	}

	function loadFirma(id)
	{
		$.post("bestellung.php", {id: id, getFirma: 'true'},
		function(data){
			$('#firma').html(data);
		});
	}

	function formatItem(row)
	{
		return row[0] + " <br>" + row[1] + "<br> ";
	}
	function formatItemTag(row)
	{
		return row[0];
	}

	function conf_del()
	{
		return confirm('Diese Bestellung wirklich löschen?');
	}

	$(document).ready(function()
	{
		$("#myTable").tablesorter(
		{
			sortList: [[4,1]],
			widgets: ['zebra'],
			headers: {
                     4: { sorter:"dedate" },
                     7: { sorter: "digitmittausenderpunkt"},
                     8: { sorter: "digitmittausenderpunkt"},
					 9: { sorter:"dedateMitText" }
				 }
		});

		$( "#datepicker_evon" ).datepicker($.datepicker.regional['de']);
		$( "#datepicker_ebis" ).datepicker($.datepicker.regional['de']);
		$( "#datepicker_bvon" ).datepicker($.datepicker.regional['de']);
		$( "#datepicker_bbis" ).datepicker($.datepicker.regional['de']);
		$( "#datepicker_auftragsbestaetigung" ).datepicker($.datepicker.regional['de']);
		$( "#datepicker_erstelldatum" ).datepicker($.datepicker.regional['de']);
		$( "#erstelldatum_gj" ).datepicker($.datepicker.regional['de']);

		$('#aufteilung').hide();

		$('#aufteilung_link').click(function()
		{
			$('#aufteilung').toggle();
			return false;
		});

		$('#mitarbeiter_name').autocomplete({
			source: "wawi_autocomplete.php?work=wawi_mitarbeiter_search",
			minLength:2,
			response: function(event, ui)
			{
				//Value und Label fuer die Anzeige setzen
				for(i in ui.content)
				{
					ui.content[i].value=ui.content[i].uid;
					ui.content[i].label=ui.content[i].vorname+' '+ui.content[i].nachname+' ('+ui.content[i].uid+')';
				}
			},
			select: function(event, ui)
			{
				ui.item.value=ui.item.uid;
				$('#mitarbeiter_uid').val(ui.item.uid);
			}
		});


		$('#firmenname').autocomplete(
		{
			source: "wawi_autocomplete.php?work=wawi_firma_search",
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



<?php
	if($rechte->isBerechtigt('wawi/bestellung_advanced'))
	{
?>

		$('#besteller').autocomplete({
			source: "wawi_autocomplete.php?work=wawi_mitarbeiter_search",
			minLength:2,
			response: function(event, ui)
			{
				//Value und Label fuer die Anzeige setzen
				for(i in ui.content)
				{
					ui.content[i].value=ui.content[i].uid;
					ui.content[i].label=ui.content[i].vorname+' '+ui.content[i].nachname+' ('+ui.content[i].uid+')';
				}
			},
			select: function(event, ui)
			{
				ui.item.value=ui.item.label;
				$('#besteller_uid').val(ui.item.uid);
			}
		});
<?php
	}
	else
	{
?>

		$("#besteller").keydown(function(e)
		{
			e.preventDefault();
		});

<?php
	}
?>

		$('#zuordnung_person').autocomplete(
		{
			source: "wawi_autocomplete.php?work=wawi_mitarbeiter_search",
			minLength:2,
			response: function(event, ui)
			{
				//Value und Label fuer die Anzeige setzen
				for(i in ui.content)
				{
					ui.content[i].value=ui.content[i].uid;
					ui.content[i].label=ui.content[i].vorname+' '+ui.content[i].nachname+' ('+ui.content[i].uid+')';
				}
			},
			select: function(event, ui)
			{
				ui.item.value=ui.item.label;
				$('#zuordnung_uid').val(ui.item.uid);
			}
		});

		$('#zuordnung_raum').autocomplete(
		{
			source: "wawi_autocomplete.php?work=wawi_raum_search",
			minLength:2,
			response: function(event, ui)
			{
				//Value und Label fuer die Anzeige setzen
				for(i in ui.content)
				{
					ui.content[i].value=ui.content[i].ort_kurzbz;
					ui.content[i].label=ui.content[i].ort_kurzbz + ' ' + ui.content[i].bezeichnung;;
				}
			},
			select: function(event, ui)
			{
				ui.item.value=ui.item.ort_kurzbz;
			}
		});


	});
	</script>
</head>
<body>

<?php
  } // if $export


if (isset($_GET['method']))
	$aktion = $_GET['method'];

if($aktion == 'suche')
  {
	if(!isset($_REQUEST['submit']))
	{
		if(!$rechte->isberechtigt('wawi/bestellung',null, 's'))
			die('Sie haben keine Berechtigung zum Suchen von Bestellungen');

		// Suchmaske anzeigen
		$konto = new wawi_konto();
		$konto->getAll(true,'kontonr ASC');
		$konto_all = $konto->result;
		$zahlungstyp = new wawi_zahlungstyp();
		$zahlungstyp->getAll();

		$kostenstelle = new wawi_kostenstelle();
		$oe_berechtigt = new organisationseinheit();

		$datum = new datum();
		$datum=getdate();

		if ($datum['mon']<9)
			$suchdatum="01.09.".($datum['year']-1);
		else
			$suchdatum="01.09.".$datum['year'];

		echo "<h2>Bestellung suchen</h2>\n";
		echo "<form action ='bestellung.php' method='GET' name='sucheForm'>\n";
		echo "<input type='hidden' name='method' value='suche'/>";
		echo "<table border =0>\n";
		echo "<tr>\n";
		echo "<td>Bestell.- Inventarnummer</td>\n";
		echo "<td><input type = 'text' size ='32' maxlength = '16' name = 'bestellnr'></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td>Beschreibung</td>\n";
		echo "<td><input type = 'text' size ='32' maxlength = '256' name = 'titel'></td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td>Bestellposition:</td>\n";
		echo "<td><input type='text' name='bestellposition' size='32' maxlength='256'></td>";
		echo "</tr>";
		echo "<tr>\n";
		echo "<td>Erstelldatum</td>\n";
		echo "<td>von <input type ='text' id='datepicker_evon' size ='12' name ='evon' value='$suchdatum'> bis <input type ='text' id='datepicker_ebis' size ='12' name = 'ebis'></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td>Bestelldatum</td>\n";
		echo "<td>von <input type ='text' id='datepicker_bvon' size ='12' name ='bvon'> bis <input type ='text' id='datepicker_bbis' size ='12' name = 'bbis'></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td> Lieferant/Empfänger: </td>\n";
		echo "<td> <input id='firmenname' name='firmenname' size='32' value=''  >\n";
		echo "</td>\n";
		echo "<td> <input type ='hidden' id='firma_id' name='firma_id' size='10' maxlength='30' value=''  >\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		$kst_array = $rechte->getKostenstelle('wawi/bestellung');
		$kostenstelle->loadArray($kst_array,'bezeichnung');

		echo "<td> Kostenstelle: </td>\n";
		echo "<td><SELECT name='filter_kostenstelle' id='searchKostenstelle' style='width: 230px;'>\n";
		echo "<option value=''>-- auswählen --</option>\n";
		foreach($kostenstelle->result as $kostenst)
		{
			echo '<option value='.$kostenst->kostenstelle_id.' >'.$kostenst->bezeichnung."</option>\n";
		}

		echo "</SELECT>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td> Konto: </td>\n";
		echo "<td><SELECT name='filter_konto' id='searchKonto' style='width: 230px;'>\n";
		echo "<option value=''>-- auswählen --</option>\n";
		foreach($konto_all as $ko)
		{
			echo '<option value='.$ko->konto_id.' >'.$ko->kurzbz."</option>\n";
		}
		echo "</SELECT>\n";

		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td>Tag:</td>\n";
		echo "<td> <input id='tag' name='tag' size='32' maxlength='30' value=''  /></td>\n";
		echo "</tr>\n";

		echo "<script type='text/javascript'>
			$('#tag').autocomplete(
			{
				source: 'wawi_autocomplete.php?work=tags',
				minChars:2,
				response:function(event,ui)
				{
					for(i in ui.content)
					{
						ui.content[i].value=ui.content[i].tag;
						ui.content[i].label=ui.content[i].tag;
					}
				},
				select: function(event, ui)
				{
					ui.item.value=ui.item.tag;
				}
			});
			</script>";

		echo "<tr>\n";
		echo "<td> Änderung durch: </td>\n";
		echo "<td> <input id='mitarbeiter_name' name='mitarbeiter_name' size='32' maxlength='30' value=''  >\n";
		echo "</td>\n";
		echo "<td> <input type ='hidden' id='mitarbeiter_uid' name='mitarbeiter_uid' size='10' maxlength='30' value=''  >\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td>Nur ohne Rechnung</td>\n";
		echo "<td><input type ='checkbox' name ='rechnung'></td>\n";
		echo "</tr>\n";
		echo "<tr>";
		echo "<td> Nur ohne Tags </td>";
		echo "<td><input type='checkbox' name='tagsvorhanden'/></td>\n";
		echo "</tr>";
		echo "<tr>\n";
		echo "<td>Nur ohne Freigabe</td>\n";
		echo "<td><input type ='checkbox' name ='ohneFreigabe'></td>\n";
		echo "</tr>\n";
		echo "<tr><td>&nbsp;</td></tr>\n";
		echo "<tr><td><input type='submit' name ='submit' value='Suche' class='cursor'></td></tr>\n";
		echo "</table>\n";
		echo "</form>\n";
	}
	else
	{
		// Suchergebnisse anzeigen
		if(!$rechte->isberechtigt('wawi/bestellung',null, 's'))
			die('Sie haben keine Berechtigung zum Suchen von Bestellungen');

		$_SESSION['wawi/lastsearch']=$_SERVER['QUERY_STRING'];

		$status = new wawi_bestellstatus();
		$bestellnummer = (isset($_REQUEST['bestellnr'])?$_REQUEST['bestellnr']:'');
		$titel = (isset($_REQUEST['titel'])?mb_str_replace("'", "´",$_REQUEST['titel']):'');
		$evon = (isset($_REQUEST['evon'])?$_REQUEST['evon']:'');
		$ebis = (isset($_REQUEST['ebis'])?$_REQUEST['ebis']:'');
		$bvon = (isset($_REQUEST['bvon'])?$_REQUEST['bvon']:'');
		$bbis = (isset($_REQUEST['bbis'])?$_REQUEST['bbis']:'');
		$tag = (isset($_REQUEST['tag'])?$_REQUEST['tag']:'');
		$zahlungstyp = (isset($_REQUEST['filter_zahlungstyp'])?$_REQUEST['filter_zahlungstyp']:'');
		$firma_id = (isset($_REQUEST['firma_id'])?$_REQUEST['firma_id']:'');
		if(!isset($_REQUEST['filter_oe_kurzbz']) || $_REQUEST['filter_oe_kurzbz'] == 'opt_auswahl')
			$oe_kurzbz = '';
		else
			$oe_kurzbz = $_REQUEST['filter_oe_kurzbz'];
		$filter_kostenstelle = (isset($_REQUEST['filter_kostenstelle'])?$_REQUEST['filter_kostenstelle']:'');
		$filter_tag = (isset($_REQUEST['filter_tag'])?$_REQUEST['filter_tag']:'');
		$filter_konto = (isset($_REQUEST['filter_konto'])?$_REQUEST['filter_konto']:'');
		$mitarbeiter_uid =  (isset($_REQUEST['mitarbeiter_uid'])?$_REQUEST['mitarbeiter_uid']:'');
		$filter_firma = (isset($_REQUEST['filter_firma'])?$_REQUEST['filter_firma']:'');
		$rechnung = (isset ($_REQUEST['rechnung'])?true:false);
		$tagsNotExists = (isset ($_REQUEST['tagsvorhanden'])?true:false);
		$ohneFreigabe = (isset ($_REQUEST['ohneFreigabe'])?true:false);

		$bestellposition= (isset($_REQUEST['bestellposition'])?mb_str_replace("'", "´", $_REQUEST['bestellposition']):'');
		$bestellung = new wawi_bestellung();

		if($evon != '')
			$evon = $date->formatDatum($evon);
		if($ebis != '')
			$ebis = $date->formatDatum($ebis);
		if($bvon != '')
			$bvon = $date->formatDatum($bvon);
		if($bbis != '')
			$bbis = $date->formatDatum($bbis);

		if(($evon || $evon === '') && ($ebis || $ebis === '' ) && ($bvon || $bvon === '') && ($bbis || $bbis === ''))
		{
			if($bestellnummer=='' && $titel=='' && $evon=='' && $ebis=='' && $bvon=='' && $bbis=='' && $firma_id=='' && $oe_kurzbz=='' && $filter_konto=='' && $mitarbeiter_uid=='' && $filter_firma=='' && $filter_kostenstelle=='' && $tag=='' && $zahlungstyp=='' && $bestellposition=='')
			{
				echo "Bitte grenzen Sie Ihre Suche weiter ein";
			}
			else
			{
				// Filter firma oder firma id werden angezeigt
				if($bestellung->getAllSearch($bestellnummer, $titel, $evon, $ebis, $bvon, $bbis, $firma_id, $oe_kurzbz, $filter_konto, $mitarbeiter_uid, $rechnung, $filter_firma, $filter_kostenstelle, $tag, $zahlungstyp, $tagsNotExists, $bestellposition, $ohneFreigabe))
				{
					$brutto = 0;
					$gesamtpreis =0;
					$gesamtpreis_rechnung=0;
					$firma = new firma();
					$date = new datum();

					if ($export == '' || $export == 'html')
					  {

						// HTML
						echo "<p style=\"text-align:right;\">";
						echo "<a href=\"".htmlspecialchars($_SERVER['REQUEST_URI'])."&export=xlsx\"><IMG src=\"../../../skin/images/ExcelIcon.png\" > XLSX</a></p>";

						echo "<table id='myTable' class='tablesorter' width ='100%'> <thead>\n";
						echo "<tr>
								<th></th>
								<th>Bestellnr.</th>
								<th>Bestell_ID</th>
								<th>Lieferant/Empfänger</th>
								<th>Erstellung</th>
								<th>Freigegeben</th>
								<th>Geliefert</th>
								<th>Brutto</th>
								<th>Rechnung Brutto</th>
								<th>Beschreibung</th>
								<th>Letzte Änderung</th>
								</tr></thead><tbody>\n";

						foreach($bestellung->result as $row)
						{
							$geliefert = 'nein';
							$brutto = $bestellung->getBrutto($row->bestellung_id);
							$gesamtpreis +=$brutto;
							$gesamtpreis_rechnung+=$row->rechnung_brutto;
							if($status->isStatiVorhanden($row->bestellung_id, 'Lieferung'))
								$geliefert = 'ja';
							$firmenname = '';
							if(is_numeric($row->firma_id))
							{
								$firma->load($row->firma_id);
								$firmenname = $firma->name;
							}

							// freigegebene oder bestellte Bestellungen können nur vom Zentraleinkauf gelöscht werden
							$bestellung_status_help = new wawi_bestellstatus();

							//Zeilen der Tabelle ausgeben
							echo "<tr>\n";
							echo "<td nowrap> <a href= \"bestellung.php?method=update&id=$row->bestellung_id\" title=\"Bestellung bearbeiten\"> <img src=\"../skin/images/edit_wawi.gif\"> </a><a href=\"bestellung.php?method=delete&id=$row->bestellung_id\" onclick='return conf_del()' title='Bestellung löschen' > <img src=\"../../../skin/images/delete_x.png\" ></a><a href= \"rechnung.php?method=update&bestellung_id=$row->bestellung_id\" title=\"Neue Rechnung anlegen\"> <img src=\"../../../skin/images/Calculator.png\"> </a><a href= \"bestellung.php?method=copy&id=$row->bestellung_id\" title=\"Bestellung kopieren\"> <img src=\"../../../skin/images/copy.png\"> </a></td>";
							echo '<td>'.$row->bestell_nr."</td>\n";
							echo '<td>'.$row->bestellung_id."</td>\n";
							echo '<td>'.$firmenname."</td>\n";
							echo '<td>'.$date->formatDatum($row->insertamum, 'd.m.Y')."</td>\n";
							echo '<td>'.($row->freigegeben?'ja':'nein')."</td>\n";
							echo '<td>'.$geliefert.'</td>';
							echo '<td class="number">'.number_format($brutto, 2, ",",".")."</td>\n";
							echo '<td class="number"><a href="./rechnung.php?method=suche&bestellnummer='.$row->bestell_nr.'&submit=true">'.number_format($row->rechnung_brutto, 2, ",",".")."</a></td>\n";
							echo '<td>'.$row->titel."</td>\n";
							echo '<td>'.$date->formatDatum($row->updateamum,'d.m.Y').' '.$row->updatevon ."</td>\n";

							echo "</tr>\n";
						}
						echo "</tbody>\n";
						echo "<tfoot><tr><td></td><td></td><td></td><td></td><td></td><td><td>Summe:</td><td class=\"number\">".number_format($gesamtpreis,2, ",",".")."</td><td class=\"number\">".number_format($gesamtpreis_rechnung,2, ",",".")."</td></tr></tfoot></table>\n";

					  }
					else if ($export == 'xlsx')
					  {

						// EXCEL

						header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
						header('Content-Disposition: attachment;filename="bestellungen.xlsx"');
						header('Cache-Control: max-age=0');


						$spreadsheet = new Spreadsheet();  // \PhpOffice\PhpSpreadsheet\Spreadsheet();
						$sheet = $spreadsheet->getActiveSheet();
						$sheet->setTitle('Bestellungen');


						$styleArray =[
						 'font' =>['bold' => true],
						 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'color' => ['argb' => 'FFCCFFCC']],
						 'alignment' =>['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
						 'borders'=>['bottom' =>['borderStyle'=> \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM]]
						];

						$spreadsheet->getActiveSheet()->getStyle('A1:J1')->applyFromArray($styleArray);


						$sheet->setCellValue('A1','Bestellnr.');
						$sheet->setCellValue('B1','Bestell_ID');
						$sheet->setCellValue('C1','Lieferant/Empfänger');
						$sheet->setCellValue('D1','Erstellung');
						$sheet->setCellValue('E1','Freigegeben');
						$sheet->setCellValue('F1','Geliefert');
						$sheet->setCellValue('G1','Bestellung Brutto');
						$sheet->setCellValue('H1','Rechnung Brutto');
						$sheet->setCellValue('I1','Beschreibung');
						$sheet->setCellValue('J1','Letzte Änderung');

						$rownum = 2;

						foreach($bestellung->result as $row)
						{
							$geliefert = 'nein';
							$brutto = $bestellung->getBrutto($row->bestellung_id);
							$gesamtpreis +=$brutto;
							if($status->isStatiVorhanden($row->bestellung_id, 'Lieferung'))
								$geliefert = 'ja';
							$firmenname = '';
							if(is_numeric($row->firma_id))
							{
								$firma->load($row->firma_id);
								$firmenname = $firma->name;
							}

							// freigegebene oder bestellte Bestellungen können nur vom Zentraleinkauf gelöscht werden
							$bestellung_status_help = new wawi_bestellstatus();

							//Zeilen der Tabelle ausgeben
							$sheet->setCellValue("A$rownum",$row->bestell_nr);
							$sheet->setCellValue("B$rownum",$row->bestellung_id);
							$sheet->setCellValue("C$rownum",$firmenname);
							//$sheet->setCellValue("D$rownum",$row->insertamum);
							//$sheet->setCellValue("D$rownum",$date->formatDatum($row->insertamum, 'd/m/Y'));
							$sheet->setCellValue("D$rownum",\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($date->formatDatum($row->insertamum, 'd/m/Y')));
							$sheet->getStyle("D$rownum")
							    ->getNumberFormat()
							    ->setFormatCode('d/m/yy');
							$sheet->setCellValue("E$rownum",($row->freigegeben?'ja':'nein'));
							$sheet->setCellValue("F$rownum",$geliefert);
							$sheet->setCellValue("G$rownum",number_format($brutto, 2, ".",""));
							$sheet->setCellValue("H$rownum",number_format($row->rechnung_brutto, 2, ".",""));
							$sheet->setCellValue("I$rownum",$row->titel);
							$sheet->setCellValue("J$rownum",$date->formatDatum($row->updateamum,'d.m.Y').' '.$row->updatevon);

							$rownum++;

						}

						$sheet->setCellValue("G$rownum",'=SUM(G2:G'.($rownum-1).')');
						$sheet->getStyle("G$rownum")->getFont()->setBold(true);
						$sheet->setCellValue("H$rownum",'=SUM(H2:H'.($rownum-1).')');
						$sheet->getStyle("H$rownum")->getFont()->setBold(true);

						$sheet->getColumnDimension('A')->setAutoSize(true);
						$sheet->getColumnDimension('B')->setAutoSize(true);
						$sheet->getColumnDimension('C')->setWidth(40);
						$sheet->getColumnDimension('D')->setAutoSize(true);
						$sheet->getColumnDimension('E')->setAutoSize(true);
						$sheet->getColumnDimension('F')->setAutoSize(true);
						$sheet->getColumnDimension('G')->setAutoSize(true);
						$sheet->getColumnDimension('I')->setWidth(40);
						$sheet->getColumnDimension('H')->setAutoSize(true);
						$sheet->getColumnDimension('J')->setAutoSize(true);


						$writer = new Xlsx($spreadsheet);
						$writer->save('php://output');
						exit;
					  }
				}
				else
					echo $bestellung->errormsg;
			}
		}
		else
		echo "ungültiges Datumsformat";
	}
  }
elseif($aktion == 'new')
  {
	// Maske für neue Bestellung anzeigen
	if(!$rechte->isberechtigt('wawi/bestellung',null, 'sui'))
		die('Sie haben keine Berechtigung zum Anlegen von Bestellungen');

		echo '	<script type="text/javascript">
		function FensterOeffnen (adresse)
		{
			MeinFenster = window.open(adresse, "Info", "scrollbars=1,width=600,height=500,left=100,top=200");
			MeinFenster.focus();
		}
		</script>';

	echo "<h2>Neue Bestellung</h2>";
	echo "<form action ='bestellung.php?method=save&new=true' method='post' name='newForm'>\n";
	echo "<table border = 0>\n";
	echo "<tr>\n";
	echo "<td>Was wird bestellt:</td>\n";
	echo "<td><input type=\"text\" size =\"80\" maxlength='256' id ='titel' name ='titel' required>";
	echo "</td></tr>\n";
	echo "<tr>\n";
	echo "<td>Lieferant/Empfänger:</td>\n";
	echo "<td> <input type=\"text\" id='firmenname' name='firmenname' size=\"80\" value='' required ></td>\n";
	echo "<td> <input type ='hidden' id='firma_id' name='firma_id' size='10' maxlength='30' value=\"\" ></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td>Zuordnung:</td><td>";
	$index = 0;
	$zuordnungListe = wawi_zuordnung::getAll();
	while (list($key, $val) = each($zuordnungListe))
	{
		echo '<input type="radio" id="zuordnung_'.$key.'" name="zuordnung" value="'.$key.'" '.($index==0?'checked="1"':'').'\"><label for="zuordnung_'.$key.'"> '.$val.'</label> ';
		$index++;
	}
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td>Kostenstelle:</td><td><SELECT name='filter_kst' onchange=\"loadKonto(this.value)\" required>\n";
	echo "<option value =\"\">-- Kostenstelle auswählen --</option>\n";
	foreach ($kst->result as $ks)
	{
		echo "<option value=".$ks->kostenstelle_id.">".$ks->bezeichnung."(".mb_strtoupper($ks->kurzbz).") - ".mb_strtoupper($ks->oe_kurzbz)."</option>\n";
	}
	echo "</SELECT></td>\n";
	echo "</tr>\n";

	echo "<tr>";
	echo "<td>Konto: </td>\n";
	echo "<td>\n";
	echo "<select name='konto' id='konto' style='width: 230px;'>\n";
	echo "<option value='' >Konto auswählen</option>\n";
	echo "</select>\n";
	echo '<a href="konto_hilfe.php" onclick="FensterOeffnen(this.href); return false" title="Informationen zu den Konten"> <img src="../../../skin/images/question.png"> </a>';

	echo "</td></tr>\n";

	// Bestelldatum Override fuer Leute mit entsprechender Berechtigung
	if($rechte->isBerechtigt('wawi/bestellung_advanced',null,'sui'))
	{
		$aktuellesdatum=date('d.m.Y');
		echo "<tr>";
		echo "<td>Erstelldatum: </td>\n";
		echo "<td>\n";
		echo "<input type =\"text\" id=\"datepicker_erstelldatum\" size =\"12\" name =\"erstelldatum_override\" value=\"$aktuellesdatum\"> ";
		echo "</td></tr>\n";
	}

	echo "<tr>\n";
	echo "<td>&nbsp;</td></tr>\n";
	echo "<tr><td><input type='submit' id='submit' name='submit' value='Anlegen' onclick='return checkKst();' class='cursor'></td></tr>\n";
	echo "</table>\n";
	echo "</form>";


	echo '
		<script type="text/javascript">
		function checkKst()
		{
			if(document.newForm.firma_id.value == \'\')
			{
				alert("Kein gültiger Lieferant ausgewählt. Geben Sie bitte zumindest 2 Zeichen ein um eine Auswahlliste zu erhalten.");
				return false;
			}
			return true;
		}
		</script>';
  }
elseif($aktion == 'save')
  {
	if(isset($_POST))
	{
		// Die Bestellung wird gespeichert und die neue id zurückgegeben
		if(!$rechte->isberechtigt('wawi/bestellung',null, 'sui'))
			die('Sie haben keine Berechtigung fuer diese Aktion');

		$newBestellung = new wawi_bestellung();
		$newBestellung->titel = mb_str_replace("'", "´", $_POST['titel']);
		$newBestellung->zuordnung = (isset($_POST['zuordnung'])?$_POST['zuordnung']:null);

		if($_POST['filter_kst']=='opt_kostenstelle')
		{
			$newBestellung->kostenstelle_id = null;
		}
		else
		{
			$newBestellung->kostenstelle_id = $_POST['filter_kst'];
		}

		$newBestellung->firma_id = $_POST['firma_id'];

		if(!isset($_POST['konto']))
			$newBestellung->konto_id = null;
		else
			$newBestellung->konto_id = $_POST['konto'];

		if (isset($_POST['erstelldatum_override']) &&
			trim($_POST['erstelldatum_override']) != '' &&
			$rechte->isberechtigt('wawi/bestellung_advanced'))
		{
			$date = new datum();
			$erstelldatum_override = $date->formatDatum($_POST['erstelldatum_override']);
			if ($erstelldatum_override !== false)
			{
				$newBestellung->insertamum = $erstelldatum_override;
			}
			else
			{
				// Datum Konvertierung fehlgeschlagen -> nimm aktuelles
				$newBestellung->insertamum = date('Y-m-d H:i:s');
			}
		}
		else
		{
			$newBestellung->insertamum = date('Y-m-d H:i:s');
		}

		$newBestellung->insertvon = $user;
		$newBestellung->updateamum = date('Y-m-d H:i:s');
		$newBestellung->updatevon = $user;
		$newBestellung->besteller_uid = $user;
		$newBestellung->new = true;
		$newBestellung->freigegeben = false;
		// vordefinierte Werte
		$newBestellung->zahlungstyp_kurzbz = 'rechnung';
		if ($newBestellung->kostenstelle_id != null && isGMBHKostenstelle($newBestellung->kostenstelle_id))
		{
			// automatisch GMBH-Adresse auswählen
			$newBestellung->lieferadresse = '31813';
			$newBestellung->rechnungsadresse = '31813';
		}
		else
		{
			$newBestellung->lieferadresse = '1';
			$newBestellung->rechnungsadresse = '1';
		}
		$newBestellung->bestell_nr =
			$newBestellung->createBestellNr(
				$newBestellung->kostenstelle_id,
				$date->mktime_fromtimestamp($newBestellung->insertamum));
		if (!$bestell_id = $newBestellung->save())
		{
			echo $newBestellung->errormsg;
		}
		else
		{
			$ausgabemsg.='<span class="ok">Bestellung wurde erfolgreich angelegt!</span><br>';
			$_GET['method']= 'update';
			$_GET['id'] = $bestell_id;
		}
	}
}
elseif($_GET['method']=='delete')
{
	// Bestellung löschen
	$id = (isset($_GET['id'])?$_GET['id']:null);
	$bestellung = new wawi_bestellung();
	$bestellung_status_help = new wawi_bestellstatus();
	$bestellung->load($id);

	if(!$rechte->isberechtigt('wawi/bestellung',null, 'suid', $bestellung->kostenstelle_id))
		die('Sie haben keine Berechtigung zum Löschen von Bestellungen');

	if($bestellung->RechnungVorhanden($id))
	{
		echo 'Kann nicht gelöscht werden. Der Bestellung ist eine Rechnung zugeordnet.';
	}
	else if(($bestellung_status_help->isStatiVorhanden($id, 'Bestellung') || $bestellung_status_help->isStatiVorhanden($id, 'Freigabe'))&& !$rechte->isBerechtigt('wawi/delete_advanced'))
	{
		echo 'Bestellte oder Freigegebene Bestellungen können nicht gelöscht werden, wenden Sie sich bitte an den Zentraleinkauf';
	}
	else
	{
		if($bestellung->delete($id))
			echo 'Bestellung erfolgreich gelöscht. <br>';
		else
			echo $bestellung->errormsg;
	}
}
elseif($_GET['method']=='deletedetail')
{
	if(!$rechte->isberechtigt('wawi/bestellung',null, 'suid'))
		die('Sie haben keine Berechtigung zum Löschen von Bestellungen');

	// Detail löschen
	$id = (isset($_GET['id'])?$_GET['id']:null);
	$detail = new wawi_bestelldetail();
	$detail->delete($id);

}
elseif($_GET['method']=='copy')
{
	$bestellung_id = $_GET['id'];
	$bestellung = new wawi_bestellung();
	$bestellung->load($bestellung_id);
	if(!$rechte->isberechtigt('wawi/bestellung',null, 'sui', $bestellung->kostenstelle_id))
		die('Sie haben keine Berechtigung zum Kopieren dieser Bestellung.');

	if ($bestellung_neu = $bestellung->copyBestellung($bestellung_id, $user))
	  {
	     $_GET['method']='update';
	     $_GET['id']=$bestellung_neu;
	     $ausgabemsg.='<span class="ok">Bestellung wurde erfolgreich kopiert.</span><br>';
	  }
	else
	  {
	  	$_GET['method']='update';
	  	$_GET['id']=$bestellung_id;
	  	$errormsg = $bestellung->errormsg;
	  	$bestellung->load($bestellung_id);
		$ausgabemsg.='<span class="error">'.$errormsg.'</span><br>';
	  }




}
if($_GET['method']=='update')
{
	echo '	<script type="text/javascript">
		function FensterOeffnen (adresse)
		{
			MeinFenster = window.open(adresse, "Info", "scrollbars=1,width=400,height=500,left=100,top=200");
			MeinFenster.focus();
		}
		</script>';

	// Bestellung Editieren
	if(isset($_GET['bestellung']))
	{
		// Update auf Bestellung
		$date = new datum();
		$error = false;
		$save = false;
		$bestellung_id = $_GET['bestellung'];
		$bestellung_old = new wawi_bestellung();
		$bestellung_old->load($bestellung_id);
		$bestellung_new = new wawi_bestellung();
		$bestellung_new->load($bestellung_id);
		$bestellung_new_brutto = $bestellung_new->getBrutto($bestellung_id);
		$status = new wawi_bestellstatus();

		if(!$rechte->isberechtigt('wawi/bestellung',null, 'sui',$bestellung_old->kostenstelle_id)
		&& !$rechte->isberechtigt('wawi/freigabe',null, 's',$bestellung_old->kostenstelle_id)
		&& !$rechte->isberechtigt('wawi/freigabe_advanced'))
			die('Sie haben keine Berechtigung fuer diese Bestellung');

		// speichern
		if(isset($_POST['btn_abschicken']) || isset($_POST['btn_submit']))
		{
			// überprüfen wenn js fehlschlägt, nicht speichern
			if(isset($_POST['filter_kst']) || isset($_POST['titel']))
			{

				//$aufteilung_anzahl = $_POST['anz_aufteilung'];
				$bestellung_detail_anz = $_POST['detail_anz'];

				$bestellung_new->new = false;
				$bestellung_new->besteller_uid=$_POST['besteller_uid'];
				$bestellung_new->zuordnung_uid=$_POST['zuordnung_uid'];
				$bestellung_new->zuordnung_raum=$_POST['zuordnung_raum'];
				$bestellung_new->zuordnung=(isset($_POST['zuordnung'])?$_POST['zuordnung']:null);
				if(isset($_POST['filter_konto']) && is_numeric($_POST['filter_konto']))
					$bestellung_new->konto_id = $_POST['filter_konto'];
				else
					$bestellung_new->konto_id = '';
				$bestellung_new->firma_id = $_POST['firma_id'];
				$bestellung_new->lieferadresse = $_POST['filter_lieferadresse'];
				$bestellung_new->rechnungsadresse = $_POST['filter_rechnungsadresse'];
				$bestellung_new->titel = mb_str_replace("'", "´", $_POST['titel']);
				$bestellung_new->bemerkung = mb_str_replace("'", "´",$_POST['bemerkung']);
				$bestellung_new->liefertermin = $_POST['liefertermin'];
				$bestellung_new->updateamum = date('Y-m-d H:i:s');
				$bestellung_new->updatevon = $user;
				$bestellung_new->zahlungstyp_kurzbz = $_POST['filter_zahlungstyp'];
				$bestellung_new->kostenstelle_id = $_POST['filter_kst'];

				if (isset($_POST['auftragsbestaetigung']))
				{
					$datum_formatiert = $date->formatDatum($_POST['auftragsbestaetigung']);
					$bestellung_new->auftragsbestaetigung =
					$datum_formatiert !== false ? $datum_formatiert :  null;
				}
				else
				{
					$bestellung_new->auftragsbestaetigung = null;
				}

				if (isset($_POST['auslagenersatz']))
				{
					$bestellung_new->auslagenersatz = true;
				}
				else
				{
					$bestellung_new->auslagenersatz = false;
				}

				if (isset($_POST['bankverbindung']))
				{
					$bestellung_new->iban = $_POST['bankverbindung'];
				}
				else
				{
					$bestellung_new->iban = null;
				}

				if (isset($_POST['wird_geleast']))
				{
					$bestellung_new->wird_geleast = true;
				}
				else
				{
					$bestellung_new->wird_geleast = false;
				}
				if (isset($_POST['nicht_bestellen']))
				{
					$bestellung_new->nicht_bestellen = true;
				}
				else
				{
					$bestellung_new->nicht_bestellen = false;
				}
				if (isset($_POST['empfehlung_leasing']))
				{
					$bestellung_new->empfehlung_leasing = true;
				}
				else
				{
					$bestellung_new->empfehlung_leasing = false;
				}


				if(isset($_POST['filter_projekt']))
				{
					// Projekt zu Bestellung speichern
					$bestellung_new->saveProjektToBestellung($bestellung_new->bestellung_id, $_REQUEST['filter_projekt']);
				}

				// wenn sich kostenstelle geändert hat und oder das Geschäftsjahr -> neue bestellnummer generieren
				$geschaeftsjahr_change  = @$_POST['geschaeftsjahr_change'] > 0 ? $_POST['geschaeftsjahr_change'] : 0;
				if(($bestellung_new->kostenstelle_id != $bestellung_old->kostenstelle_id && !$status->isStatiVorhanden($bestellung_id, 'Bestellung')) ||
				   ($geschaeftsjahr_change > 0 ))
				{
					if ($geschaeftsjahr_change > 0)
					{
						// override datum, damit anderes Geschäftsjahr verwendet wird
						$bestellung_new->bestell_nr =
							$bestellung_new->createBestellNr($bestellung_new->kostenstelle_id,
								mktime(0, 0, 0, 12, 1, $geschaeftsjahr_change));
						// Erstelldatum auch ändern?
						$erstelldatum_change  = isset($_POST['erstelldatum_change']) ? $_POST['erstelldatum_change'] : '';

						if ($erstelldatum_change != '')
						{
							$date = new datum();
							$erstelldatum_override = $date->formatDatum($_POST['erstelldatum_change']);
							if ($erstelldatum_override !== false)
							{
								$bestellung_new->insertamum = $erstelldatum_override;
							}
						}

					}
					else
					{
						$bestellung_new->bestell_nr = $bestellung_new->createBestellNr($bestellung_new->kostenstelle_id);
					}
				}

				$tags = explode(";", $_POST['tags']);
				$help_tags = new tags();
				$help_tags->bestellung_id = $bestellung_id;
				$help_tags->deleteBestellungTag($tags);

				foreach ($tags as $bestelltags)
				{
					$tag_bestellung = new tags();
					$tag_bestellung->tag = trim($bestelltags);
					$tag_bestellung->bestellung_id = $bestellung_id;
					$tag_bestellung->insertvon = $user;
					$tag_bestellung->insertamum = date('Y-m-d H:i:s');

					if(!$tag_bestellung->TagExists())
					{
						$tag_bestellung->saveTag();
						$tag_bestellung->saveBestellungTag();
					}
					else
					{
						if(!$tag_bestellung->BestellungTagExists())
							$tag_bestellung->saveBestellungTag();
					}
				}
				for($i = 1; $i <= $bestellung_detail_anz; $i++)
				{
					// wenn ein Detail gelöscht wird Durchlauf überspringen
					if(!isset($_POST["bestelldetailid_$i"]))
						continue;
					// wenn letzte zeile leer ist, nicht speichern
					if(($i) == $bestellung_detail_anz
						&& $_POST["ve_$i"] == ''
						&& $_POST["beschreibung_$i"]==''
						&& $_POST["artikelnr_$i"] =='')
					{
						continue;
					}
					$detail_id = $_POST["bestelldetailid_$i"];
					$bestell_detail = new wawi_bestelldetail();

					// gibt es ein bestelldetail schon
					if($detail_id != '')
					{
						// Update
						$bestell_detail->load($detail_id);
						$tags_detail = explode(";", $_POST["detail_tag_$i"]);
						$help_detailtags = new tags();
						$help_detailtags->bestelldetail_id = $detail_id;
						$help_detailtags->deleteBestelldetailTag($tags_detail);
						foreach ($tags_detail as $det)
						{
							$detail_tag = new tags();
							$detail_tag->tag = trim($det);
							$detail_tag->bestelldetail_id = $detail_id;
							$detail_tag->insertvon = $user;
							$detail_tag->insertamum = date('Y-m-d H:i:s');

							if(!$detail_tag->TagExists())
							{
								$detail_tag->saveTag();
								$detail_tag->saveBestelldetailTag();
							}
							else
							{
								if(!$detail_tag->BestelldetailTagExists())
									$detail_tag->saveBestelldetailTag();
							}
						}
						$menge = $_POST["menge_$i"];
						if($menge == '')
							$menge = '0';
						$bestell_detail->position = $_POST["pos_$i"];
						if($_POST["sort_$i"] !='')
							$bestell_detail->sort = $_POST["sort_$i"];
						else
							$bestell_detail->sort = $_POST["pos_$i"];

						$bestell_detail->menge = $menge;
						$bestell_detail->verpackungseinheit = $_POST["ve_$i"];
						$bestell_detail->beschreibung = $_POST["beschreibung_$i"];
						$bestell_detail->artikelnummer = $_POST["artikelnr_$i"];
						$bestell_detail->preisprove = mb_str_replace(',', '.', $_POST["preis_$i"]);
						$bestell_detail->mwst = mb_str_replace(',', '.', $_POST["mwst_$i"]);
						$bestell_detail->updateamum = date('Y-m-d H:i:s');
						$bestell_detail->updatevon = $user;
						$bestell_detail->new = false;
					}
					else
					{
						// Insert
						$menge = $_POST["menge_$i"];
						if($menge == '')
							$menge = '0';

						$bestell_detail->mwst = ($_POST["mwst_$i"]=='')?0:mb_str_replace(',', '.', $_POST["mwst_$i"]);
						$bestell_detail->bestellung_id = $_GET['bestellung'];
						$bestell_detail->position = $_POST["pos_$i"];
						$bestell_detail->menge = $menge;
						$bestell_detail->verpackungseinheit = $_POST["ve_$i"];
						$bestell_detail->beschreibung = $_POST["beschreibung_$i"];
						$bestell_detail->artikelnummer = $_POST["artikelnr_$i"];
						$bestell_detail->preisprove =mb_str_replace(',', '.', $_POST["preis_$i"]);
						if($bestell_detail->preisprove == '')
							$bestell_detail->preisprove=0;
						if($_POST["sort_$i"] != '')
							$bestell_detail->sort = $_POST["sort_$i"];
						else
							$bestell_detail->sort = $_POST["pos_$i"];

						$bestell_detail->insertamum = date('Y-m-d H:i:s');
						$bestell_detail->insertvon = $user;
						$bestell_detail->updateamum = date('Y-m-d H:i:s');
						$bestell_detail->updatevon = $user;
						$bestell_detail->new = true;
					}
					if(!$bestell_detail->save())
					{
						echo '<span class="error">'.$bestell_detail->errormsg.'</span>';
						$error = true;
					}
				}

				if($error == false)
					if($bestellung_new->save())
					{
						$ausgabemsg.='<span class="ok">Bestellung wurde erfolgreich gespeichert!</span><br>';
						$save = true;
					}
			}
		}
		// Bestellung freigeben wird in gang gesetzt --> durch Abschick Button
		if(isset($_POST['btn_abschicken']) )
		{
			// wenn status Storno vorhanden ist kann nicht mehr freigegeben werden
			if($status->isStatiVorhanden($bestellung_new->bestellung_id, 'Storno'))
			{
				echo '<span class="error">Keine Freigabe mehr möglich, da Storniert wurde.</span><br />';
			}
			else
			{
				$status_abgeschickt = new wawi_bestellstatus();
				if(!$status_abgeschickt->isStatiVorhanden($bestellung_id, 'Abgeschickt'))
				{
					$bestellung_new->load($bestellung_id);

					$status_abgeschickt->bestellung_id = $bestellung_id; ;
					$status_abgeschickt->bestellstatus_kurzbz ='Abgeschickt';
					$status_abgeschickt->uid = $user;
					$status_abgeschickt->oe_kurzbz = '';
					$status_abgeschickt->datum = date('Y-m-d H:i:s');
					$status_abgeschickt->insertvon = $user;
					$status_abgeschickt->insertamum = date('Y-m-d H:i:s');
					$status_abgeschickt->updatevon = $user;
					$status_abgeschickt->updateamum = date('Y-m-d H:i:s');
					if(!$status_abgeschickt->save())
						echo "Fehler beim Setzen auf Status Abgeschickt.";

					// wer ist freigabeberechtigt auf kostenstelle
					$rechte_fg = new benutzerberechtigung();
					$uids = $rechte_fg->getFreigabeBenutzer($bestellung_new->kostenstelle_id, null);
					if(empty($uids))
						$ausgabemsg .='<span class="error">Es ist niemand zur Freigabe der Kostenstelle berechtigt.</span><br>';
					else
						$ausgabemsg.=sendFreigabeMails($uids, $bestellung_new, $user);
				}
			}
		}
		// Kostenstelle hat freigegeben
		if(isset($_POST['btn_freigabe']) || isset($_POST['btn_freigabe_kst']) )
		{
			if(!$rechte->isBerechtigt('wawi/freigabe',null, 'suid', $bestellung_new->kostenstelle_id)
			&& !$rechte->isBerechtigt('wawi/freigabe_advanced'))
				die('Sie haben keine Berechtigung zum Freigeben der Bestellung');

			if(isset($_POST['btn_freigabe_kst']))
			{
				// wenn status Storno vorhanden, soll nicht mehr freigegeben werden.
				if($status->isStatiVorhanden($bestellung_new->bestellung_id, 'Storno'))
				{
					$ausgabemsg.= '<span class="error">Keine Freigabe mehr möglich, da Storniert wurde.</span><br>';
				}
				else
				{
					// Freigabestatus für Kostenstelle
					$bestellung_new->load($bestellung_id);
					$status = new wawi_bestellstatus();
					$status->bestellung_id = $bestellung_new->bestellung_id;
					$status->bestellstatus_kurzbz = 'Freigabe';
					$status->uid = $user;
					$status->oe_kurzbz = '';
					$status->datum = date('Y-m-d H:i:s');
					$status->insertvon = $user;
					$status->insertamum = date('Y-m-d H:i:s');
					$status->updateamum = date('Y-m-d H:i:s');
					$status->updatevon = $user;

					if(!$status->save())
					{
						$ausgabemsg.= '<span class="error">Fehler beim Setzen auf Status Freigabe.</span><br>';
					}
					else
					{
						$ausgabemsg.= '<span class="ok">Bestellung wurde erfolgreich freigegeben</span><br>';

						// wer ist freigabeberechtigt auf nächsthöhere Organisationseinheit
						$oes = array();
						$oes = $bestellung_new->FreigabeOe($bestellung_id);
						$freigabe= false;
						foreach($oes as $o)
						{
							if(!$status->isStatiVorhanden($bestellung_new->bestellung_id, 'Freigabe', $o))
							{
								$rechte_fg = new benutzerberechtigung();
								$uids = $rechte_fg->getFreigabeBenutzer(null, $o);
								if(empty($uids))
									$ausgabemsg .='<span class="error">Es ist niemand zur Freigabe der Kostenstelle berechtigt.</span><br>';
								else
									$freigabe = true;
								break;
							}
						}
						if(!$freigabe == false)
						{
							$ausgabemsg.=sendFreigabeMails($uids, $bestellung_new, $user);
						}
						else
						{
							//Bestellung komplett freigegeben
							//Freigabe setzen und Info an Zentraleinkauf schicken
							if(!$bestellung_new->isFreigegeben($bestellung_new->bestellung_id))
								$bestellung_new->SetFreigegeben($bestellung_new->bestellung_id);

							sendZentraleinkaufFreigegeben($bestellung_new);
							sendBestellerMail($bestellung_new, 'freigabe');
						}
					}
				}
			}
			else
			{
				// OE hat freigegeben
				// wenn status Storno vorhanden, soll nicht mehr freigegeben werden.
				if($status->isStatiVorhanden($bestellung_new->bestellung_id, 'Storno'))
				{
					$ausgabemsg.='<span class="error">Keine Freigabe mehr möglich, da Storniert wurde.</span><br>';
				}
				else
				{
					// Freigabestatus für Kostenstelle
					$bestellung_new->load($bestellung_id);
					$status = new wawi_bestellstatus();
					$status->bestellung_id = $bestellung_new->bestellung_id;
					$status->bestellstatus_kurzbz = 'Freigabe';
					$status->uid = $user;
					$status->oe_kurzbz = $_POST['freigabe_oe'];
					$status->datum = date('Y-m-d H:i:s');
					$status->insertvon = $user;
					$status->insertamum = date('Y-m-d H:i:s');
					$status->updateamum = date('Y-m-d H:i:s');
					$status->updatevon = $user;

					if(!$status->save())
					{
						$ausgabemsg.= '<span class="error">Fehler beim Setzen auf Status Freigabe.</span><br>';
					}
					else
					{
						$ausgabemsg.= '<span class="ok">Bestellung wurde erfolgreich freigegeben</span><br>';

						// wer ist freigabeberechtigt auf nächsthöhere Organisationseinheit
						$oes = array();
						$oes = $bestellung_new->FreigabeOe($bestellung_id);
						$freigabe = false;
						foreach($oes as $o)
						{
							if(!$status->isStatiVorhanden($bestellung_new->bestellung_id, 'Freigabe', $o))
							{
								$rechte_fg = new benutzerberechtigung();
								$uids = $rechte_fg->getFreigabeBenutzer(null, $o);
								if(empty($uids))
									$ausgabemsg .='<span class="error">Es ist niemand zur Freigabe der Kostenstelle berechtigt. Bitte wenden Sie sich an den Support.</span><br>';
								else
									$freigabe = true;
								break;
							}
						}
						if(!$freigabe == false)
						{
							// es wurde noch nicht alles Freigegeben
							$ausgabemsg.=sendFreigabeMails($uids, $bestellung_new, $user);
						}
						else
						{
							//Bestellung komplett freigegeben
							//Freigabe setzen und Info an Zentraleinkauf schicken
							if(!$bestellung_new->isFreigegeben($bestellung_new->bestellung_id))
								$bestellung_new->SetFreigegeben($bestellung_new->bestellung_id);

							sendZentraleinkaufFreigegeben($bestellung_new);
							sendBestellerMail($bestellung_new, 'freigabe');
						}
					}
				}
			}
		}

		// es soll die freigabenachricht erneut versendet werden, an dem der zum freigeben drann ist
		if(isset($_POST['btn_erneut_abschicken']))
		{
			if(!$status->isStatiVorhanden($bestellung_new->bestellung_id, 'Freigabe'))
			{
				// KST hat noch nicht freigegeben
				$rechte_fg = new benutzerberechtigung();
				$uids = $rechte_fg->getFreigabeBenutzer($bestellung_new->kostenstelle_id, null);
				if(empty($uids))
					$ausgabemsg .='<span class="error">Es ist niemand zur Freigabe der Kostenstelle berechtigt.</span><br>';
				else
					$ausgabemsg.=sendFreigabeMails($uids, $bestellung_new, $user);
			}
			else
			{
				$bestellung_new->load($bestellung_id);

					// wer ist freigabeberechtigt auf nächsthöhere Organisationseinheit
					$oes = array();
					$oes = $bestellung_new->FreigabeOe($bestellung_id);
					$freigabe= false;
					foreach($oes as $o)
					{
						if(!$status->isStatiVorhanden($bestellung_new->bestellung_id, 'Freigabe', $o))
						{

							$rechte_fg = new benutzerberechtigung();
							$uids = $rechte_fg->getFreigabeBenutzer(null, $o);
							if(empty($uids))
								$ausgabemsg .='<span class="error">Es ist niemand zur Freigabe der Kostenstelle berechtigt.</span><br>';
							else
								$freigabe = true;
							break;
						}
					}
					if(!$freigabe == false)
					{
						$ausgabemsg.=sendFreigabeMails($uids, $bestellung_new, $user);
						// fehlermeldung wenn kein uid gefunden
					}
					else
					{
						$ausgabemsg.= '<span class="ok">Die Bestellung wurde komplett freigegeben</span><br>';
					}

			}

			// Speichern dass Bestellung erneut abgeschickt wurde, damit
			// Zentraleinkauf weiß, wann das letzte Mal urgiert wurde
			$status_abgeschickt = new wawi_bestellstatus();
			$bestellung_new->load($bestellung_id);
			$status_abgeschickt->bestellung_id = $bestellung_id; ;
			$status_abgeschickt->bestellstatus_kurzbz ='Abgeschickt-Erneut';
			$status_abgeschickt->uid = $user;
			$status_abgeschickt->oe_kurzbz = '';
			$status_abgeschickt->datum = date('Y-m-d H:i:s');
			$status_abgeschickt->insertvon = $user;
			$status_abgeschickt->insertamum = date('Y-m-d H:i:s');
			$status_abgeschickt->updatevon = $user;
			$status_abgeschickt->updateamum = date('Y-m-d H:i:s');
			if(!$status_abgeschickt->save())
				echo "Fehler beim Setzen auf Status Abgeschickt-Erneut.";

		}
		$_GET['method']='update';
		$_GET['id']=$bestellung_new->bestellung_id;
	}

	// Bestellung Editieren
	$id = (isset($_GET['id'])?$_GET['id']:null);

	$bestellung = new wawi_bestellung();
	if(!$bestellung->load($id))
		die("Bestellung ist nicht vorhanden.");

	if(!$rechte->isberechtigt('wawi/bestellung',null, 's',$bestellung->kostenstelle_id)
	&& !$rechte->isberechtigt('wawi/freigabe',null, 's',$bestellung->kostenstelle_id))
			die('Sie haben keine Berechtigung fuer diese Bestellung <a href="javascript:history.back()">Zurück</a>');

	//Session setzen damit von der Firmenanlage wieder zurueckgesprungen werden kann
	$_SESSION['wawi/last_bestellung_id']=$id;

	$detail = new wawi_bestelldetail();
	$detail->getAllDetailsFromBestellung($id);
	$anz_detail = count($detail->result);
	$konto = new wawi_konto();
	$konto->getKontoFromKostenstelle($bestellung->kostenstelle_id);
	$konto_bestellung = new wawi_konto();
	$konto_bestellung->load($bestellung->konto_id);
	$kostenstelle = new wawi_kostenstelle();
	$kostenstelle->load($bestellung->kostenstelle_id);
	$aufteilung = new wawi_aufteilung();

	// Bei neuer Bestellung Default Aufteilung holen ansonsten von bestehender bestellung
	if(isset($_GET['new']))
		$aufteilung->getAufteilungFromKostenstelle($bestellung->kostenstelle_id);
	else
		$aufteilung->getAufteilungFromBestellung($bestellung->bestellung_id);

	$firma = new firma();
	$firma->load($bestellung->firma_id);
	$allStandorte = new standort();
	$allStandorte->getStandorteWithTyp('Intern');
	$status= new wawi_bestellstatus();
	$bestell_tag = new tags();
	$studiengang = new studiengang();
	$studiengang->getAll('typ, kurzbz', null);

	//budget berechnung
	$geschaeftsjahr = new geschaeftsjahr();
	$gJahr = $geschaeftsjahr->getSpecific($bestellung->insertamum);
	$budget = $kostenstelle->getBudget($bestellung->kostenstelle_id,$gJahr);
	$spentBudget = $bestellung->getSpentBudget($bestellung->kostenstelle_id, $gJahr);
	$restBudget = $budget - $spentBudget;
	$summe= 0;
	$konto_vorhanden = false;
	$kst_vorhanden =false;
	$alert ='';
	$besteller = new benutzer();
	$besteller->load($bestellung->besteller_uid);
	$besteller_vorname=$besteller->vorname;
	$besteller_nachname=$besteller->nachname;
	$zuordnung_person_vorname='';
	$zuordnung_person_nachname='';
	$zuordnung_person = new benutzer();
	if ($zuordnung_person->load($bestellung->zuordnung_uid) != false)
	{
		$zuordnung_person_vorname=$zuordnung_person->vorname;
		$zuordnung_person_nachname=$zuordnung_person->nachname;
	}


	if($restBudget < 0 && $budget != 0)
		$ausgabemsg.='<span class="error">Ihr aktuelles Budget ist bereits überzogen.</span>';

$js = <<<EOT
<script type="text/javascript" >

  function maybeShowNichtBestellen() {
		if ($('#nicht_bestellen').is(':checked'))
		{
			$('#nichtbestellen_tr').show();
		} else {
			$('#nichtbestellen_tr').hide();
		}
	}

	function maybeShowBankverbindung()
	{
		if ($('#auslagenersatz').is(':checked'))
		{
			var uid = $('#besteller_uid').val();
			$.ajax(
			{
				method: "GET",
				url: "wawi_autocomplete.php",
				data: { work: "wawi_bankverbindung", term: uid },
				dataType:'json',
			}).done(function( msg )
			{
				$('#bankverbindung').val(msg.iban);
			});
			$('#bankverbindung_span').show();
		}
		else
		{
			$('#bankverbindung_span').hide();
		}
	}



	$(document).ready(function()
	{
		maybeShowBankverbindung();
		$('#auslagenersatz').on('change', function()
		{
			maybeShowBankverbindung();
		});
		maybeShowNichtBestellen();
		$('#nicht_bestellen').on('change', function()
		{
			maybeShowNichtBestellen();
		});
	});

	$(function()
	{
		var dialog;
		var angebotDeleteDialog;
		var bestellnrDialog;

		function getExtension(path) {
            var basename = path.split(/[\\/]/).pop(),

            pos = basename.lastIndexOf(".");

            if (basename === "" || pos < 1)
                return "";

            return basename.slice(pos + 1);
        }

        function getIcon(filename) {
            var ext = getExtension(filename);

            if (ext == 'pdf' || ext == 'PDF')
            	return '../../../skin/images/pdf_icon.png';

            return '../../../skin/images/ExcelIcon.png'
        }

		function renderAngebote()
		{
			var angeboteDummy = [ {'name' : 'Angebot 1 ','angebot_id' : 1, 'bestellung_id' : 2 },
			{'name' : 'Angebot 2 ','angebot_id' : 2, 'bestellung_id' : 2 } ];
			var angeboteElement = $("#angeboteListe");
			angeboteElement.empty();

			$.post( "angebot.php", { method: 'list', bestellung_id: bestellung_id }, function(data)
			{
				if (data.result ==undefined || data.result != 1)
				{
					alert("Fehler: Check console");
					return;
				}
				angeboteElement.append(
					$.map(data.list,function(al)
					{
						return $('<li>',{}).append(
							$('<a>',{ target: '_blank', href: 'angebot.php?method=download&angebotId=' + al.angebot_id + "&bestellung_id=" + bestellung_id }).append(
							$('<img>',{ src : getIcon(al.name), class : 'cursor' }))
							//.text(al.name)
						).append(
							$('<a style="background: transparent;padding-left:1px;padding-right:1px">',{ href: '#' }).append(
							$('<img>',{ src :'../../../skin/images/delete_round.png', class : 'cursor' })
							).click(function()
							{
								$('#currentAngebotId').val(al.angebot_id);
								$('#angebotFilename').empty();
								$('#angebotFilename').append(al.name);
								angebotDeleteDialog.dialog("open");
							})
						);
					})
				); // angeboteElement
			}) // post
			.fail(function(d)
			{
				alert( d.responseText );
			}, "json");
		}

		renderAngebote();

		angebotDeleteDialog = $( "#angebot-delete" ).dialog(
		{
			autoOpen: false,
			height: 300,
			width: 550,
			modal: true,
			buttons:
			{
				"OK": doDelete,
				Cancel: function()
				{
					angebotDeleteDialog.dialog( "close" );
				}
			},
			close: function()
			{
				//form[ 0 ].reset();
			}
		}); // Dialog

		dialog = $( "#angebot-upload" ).dialog(
		{
			autoOpen: false,
			height: 300,
			width: 550,
			modal: true,
			buttons:
			[
		        {
		            id: "upload-button-ok",
		            text: "Ok",
		            click: function() {
		                doUpload();
		            }
		        },
		        {
		            id: "button-cancel",
		            text: "Cancel",
		            click: function() {
		                $(this).dialog("close");
		            }
		        }
		    ],
			close: function()
			{
				//form[ 0 ].reset();
			}
		}); // Dialog
		$( "#angebotBtn" ).on( "click", function()
		{
			$('div#response').empty();
			var controlInput = $(':file');
			controlInput.replaceWith(controlInput = controlInput.val('').clone(true));
			dialog.dialog( "open" );
			callProgressBar(0);
		});

		$(':file').change(function()
		{
			var file = this.files[0];
			var name = file.name;
			var size = file.size;
			var type = getExtension(file.name);
			if (!type.match(/.*(ods|xlsx|xls|pdf)$/i))
			{
				$("<div title='Fehler'>Es werden nur Dateien mit folgenden Endungen akzeptiert: PDF, XLS, XLSX, ODS<br/>Diese Datei hat die Endung <i>" + type.toUpperCase() + "</i>.</div>").dialog(
				{
					title: 'Fehler',
					resizable: false,
					modal: true,
					buttons:
					{
						"OK": function ()
						{
							$(this).dialog("close");
						}
					}
				});
				$("#upload-button-ok").button("disable");
				$(':file').value='';
			} else {
				$("#upload-button-ok").button("enable");
			}
		});
		// Bestellnr-dialog
		bestellnrDialog = $( "#bestellnr-change" ).dialog(
		{
			autoOpen: false,
			height: 200,
			width: 350,
			modal: true,
			buttons:
			[
		        {
		            id: "bestellnr-button-ok",
		            text: "Ok",
		            click: function() {
		                doBestellnrChange();
		            }
		        },
		        {
		            id: "button-cancel",
		            text: "Cancel",
		            click: function() {
		                $(this).dialog("close");
		            }
		        }
		    ],
			close: function()
			{
				//form[ 0 ].reset();
			}
		}); // Bestellnr-Dialog

		$( "#bestellnrChangeBtn" ).on( "click", function() {
			bestellnrDialog.dialog("open");
		});



		function doBestellnrChange()
		{
			var gj = $('#geschaeftsjahr','#bestellnrFrm').val();
			var erstelldatum = $('#erstelldatum_gj','#bestellnrFrm').val();
			$('#geschaeftsjahr_change','#editForm').val(gj);
			$('#erstelldatum_change','#editForm').val(erstelldatum);
			bestellnrDialog.dialog("close");
			$("input[name='btn_submit']").click();
		}

		function doUpload()
		{
			var formData = new FormData($('#uploadFrm')[0]);
			//formData.append('upload',$('#uploadFrm')[0].files[0]);
			formData.append('method','upload');
			formData.append('bestellung_id',bestellung_id);
			$.ajax(
			{
				url: 'angebot.php',  //Server script to process data
				type: 'POST',
				xhr: function()
				{  // Custom XMLHttpRequest
					var myXhr = $.ajaxSettings.xhr();
					if(myXhr.upload)
					{ // Check if upload property exists
						myXhr.upload.addEventListener('progress',progressHandlingFunction, false); // For handling the progress of the upload
					}
					return myXhr;
				},
				//Ajax events
				//beforeSend: beforeSendHandler,
				success: function (res)
				{
					$('div#response').html("<br>Datei erfolgreich geladen.");
					renderAngebote();
					dialog.dialog('close');
				},
				error: errorHandler,
				// Form data
				data: formData,
				//Options to tell jQuery not to process data or worry about content-type.
				cache: false,
				contentType: false,
				processData: false
			});
		};

		function doDelete()
		{
			var formData = new FormData($('#deleteFrm')[0]);
			formData.append('method','delete');
			formData.append('bestellung_id',bestellung_id);

			$.ajax(
			{
				url: 'angebot.php',
				type: 'POST',
				success: function (res)
				{
					renderAngebote();
					angebotDeleteDialog.dialog("close");
				},
				error: errorHandler,
				processData: false,
				contentType: false,
				data: formData
			});
		}

		function errorHandler(e)
		{
			alert(e);
		}

		function progressHandlingFunction(e)
		{
			console.log(e);
			if(e.lengthComputable)
			{
				callProgressBar( (e.loaded/e.total)*100);
			}
		}

		function callProgressBar(step)
		{
			$( "#progressbar" ).progressbar(
			{
				value: step
			});
		};
	});
</script>\n
EOT;
echo $js;
?>

<div id="angebot-upload" title="Angebot hochladen">
	<form enctype="multipart/form-data" id="uploadFrm">
		<input name="file" type="file" />
		<br><br>
		<div id="progressbar">
		</div>
		<div id="response">
		</div>
	</form>


</div>

<div id="angebot-delete" title="Angebot löschen">
	<form method="post" id="deleteFrm">
		<input type="hidden" name="currentAngebotId" id="currentAngebotId" value="" />
		<br><br>
		Angebot '<span id="angebotFilename"></span>' wirklich löschen?
	</form>
</div>

<div id="bestellnr-change" title="Neue Bestellnummer erzeugen?">
	<form method="post" id="bestellnrFrm">
<?php
	// Auswahlfeld für Geschäftsjahr erzeugen
	$akt_jahr = date('Y');
	echo '<p><label for="geschaeftsjahr">Geschäftsjahr:</label> <select id="geschaeftsjahr" name="geschaeftsjahr">';
	for ($j=$akt_jahr-1;$j<($akt_jahr+1);$j++)
	{
?>
		<option value="<?php echo $j ?>"><?php echo $j.'/'.($j+1) ?></option>
<?php
	}
?>
		</select></p>
		<p>
		<label for="erstelldatum_gj">Erstelldatum:</label> <input type ='text' id='erstelldatum_gj' size ='12' name ='erstelldatum_gj' value=''>
		</p>
	<br>

	</form>
</div>

<?php


	//Meldungen Ausgeben
	echo '<div style="float: right">',$ausgabemsg,'</div>';

	echo "<h2>Bearbeiten</h2>";

	echo "<form action =\"bestellung.php?method=update&amp;bestellung=$bestellung->bestellung_id\" method='post' name='editForm' id='editForm' onSubmit='return maybeSubmit()'>\n";
	echo "<h4>Bestellnummer: ".$bestellung->bestell_nr;
	echo '	<a href= "bestellung.php?method=copy&amp;id='.$bestellung->bestellung_id.'"> <img src="../../../skin/images/copy.png" title="Bestellung kopieren" class="cursor"></a>';
	echo '	<a href= "rechnung.php?method=update&amp;bestellung_id='.$bestellung->bestellung_id.'"> <img src="../../../skin/images/Calculator.png" title="Rechnung anlegen" class="cursor"></a>';
	if($rechte->isBerechtigt('wawi/bestellung_advanced'))
		echo '	<a href= "#" id="bestellnrChangeBtn" name="bestellnrChangeBtn"> <img src="../skin/images/bnr.png" title="Bestellnummer erzeugen" class="cursor"></a>';

	if($rechte->isBerechtigt('system/developer'))
		echo '	<a href= "bestellung.php?method=update&amp;id='.$bestellung->bestellung_id.'"> <img src="../../../skin/images/refresh.png" title="Refresh" class="cursor"></a>';

	if (isGMBHKostenstelle($bestellung->kostenstelle_id))
	{
		echo " <span style=\"color:white;background-color:red\"> GMBH-Bestellung! </span>";
	}

	echo '</h4>';

	// hidden field für geschäftsjahr; wird in dialog gesetzt
	echo '<input type="hidden" name="geschaeftsjahr_change" id="geschaeftsjahr_change" value="" />';
	echo '<input type="hidden" name="erstelldatum_change" id="erstelldatum_change" value="" />';

	//tabelle Bestelldetails
	echo "<table border = 0 width= '100%' class='dark'>\n";
	echo "<tr>\n";
	echo "	<td>Was wird bestellt: </td>\n";
	echo "	<td><input name= 'titel' type='text' size='60' maxlength='256' value ='".$bestellung->titel."'></td>\n";
	echo "	<td>Erstellt am:</td>\n";
	echo "	<td><span name='erstellt' title ='".$bestellung->insertvon."' >".$date->formatDatum($bestellung->insertamum, 'd.m.Y')."</span></td>\n";
	echo "	<td>Vorauss. Liefertermin: <input type='text' name ='liefertermin' size='16' maxlength='16' value='".$bestellung->liefertermin."'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td>Lieferant/Empfänger: </td>\n";
	echo "	<td><input type='text' name='firmenname' id='firmenname' size='60' maxlength='256' value ='".$firma->name."'>\n";
	echo "	<input type='hidden' name='firma_id' id='firma_id' size='5' maxlength='7' value ='".$bestellung->firma_id."'></td>\n";
	echo "	<td>Besteller:</td><td> <input type='text' name='besteller' id='besteller' size='30' maxlength='256' value ='".$besteller_vorname.' '.$besteller_nachname."'></td>\n";
	echo "	<td>";
	// wenn user projekt zugeordnet ist -> Projekt Drop Down anzeigen
	$ProjektUser = new projekt();
	$ProjektUser->getProjektFromBestellung($bestellung->bestellung_id);
	$Bestellung_Projekt = false; // Projekt DropDown aus allen Projekten von eingeloggten User und dem der Bestellung -> true wenn Projekt aus Bestellung in User Projekten enthalten ist
	if($projektZugeordnet == true)
	{
		echo " Projekt:";
		echo "<SELECT name='filter_projekt' id='filter_projekt' style='width: 230px;'>\n";
		echo "<option value=''>-- Kein Projekt ausgewählt --</option>";
		// Projekte vom User
		foreach ($projekt->result as $userProjekts)
		{
			$selected = "";
			if($ProjektUser->projekt_kurzbz == $userProjekts->projekt_kurzbz)
			{
				$selected = 'selected';
				$Bestellung_Projekt = true;
			}
				echo "<option value='".$userProjekts->projekt_kurzbz."' $selected>".$userProjekts->titel."</option>";
		}
		// Projekt von der Bestellung
		if($Bestellung_Projekt == false && $ProjektUser->projekt_kurzbz != '')
			echo "<option value='".$ProjektUser->projekt_kurzbz."' selected>".$ProjektUser->titel."</option>";
		echo "</select>";
	}

	echo "	<input type='hidden' name='besteller_uid' id='besteller_uid' size='5' maxlength='7' value ='".$bestellung->besteller_uid."'>\n";
	echo "	<input type='hidden' name='zuordnung_uid' id='zuordnung_uid' size='5' maxlength='7' value ='".$bestellung->zuordnung_uid."'>\n";
	echo "</td>";
	echo "</tr>\n";
	echo "<tr>\n";
	$disabled = '';
	if($status->isStatiVorhanden($bestellung->bestellung_id, 'Bestellung') || $status->isStatiVorhanden($bestellung->bestellung_id, 'Storno') || $status->isStatiVorhanden($bestellung->bestellung_id, 'Abgeschickt'))
		$disabled = 'disabled';
	if($rechte->isberechtigt('wawi/bestellung_advanced',null, 'suid', $bestellung->kostenstelle_id) || ($rechte->isBerechtigt('wawi/freigabe', null, 'suid',$bestellung->kostenstelle_id) && $bestellung->freigegeben))
		$disabled = '';

	echo "<td>Kostenstelle:</td><td><SELECT name='filter_kst' style='width: 483px;' onchange='loadKonto(this.value)' $disabled id='filter_kst'>\n";

	foreach ($kst->result as $ks)
	{
		$selected = '';
		if($ks->kostenstelle_id == $bestellung->kostenstelle_id)
		{
			$selected = 'selected';
			$kst_vorhanden = true;
		}
		echo "<option value=".$ks->kostenstelle_id." $selected>".$ks->bezeichnung."(".mb_strtoupper($ks->kurzbz).") - ".mb_strtoupper($ks->oe_kurzbz)."</option>\n";
	}
	// wenn user nicht auf kst berechtigt ist, trotzdem anzeigen zum freigeben
	if(!$kst_vorhanden)
		echo "<option value='".$bestellung->kostenstelle_id."' selected >".$kostenstelle->bezeichnung."(".mb_strtoupper($kostenstelle->kurzbz).") - ".mb_strtoupper($kostenstelle->oe_kurzbz)."</option>\n";

		echo "</SELECT></td>\n";
	echo "<td>Lieferadresse:</td>\n";
	echo "<td colspan ='2'><Select name='filter_lieferadresse' id='filter_lieferadresse' style='width: 400px;'>\n";

	foreach($allStandorte->result as $standorte)
	{
		$selected ='';
		$standort_lieferadresse = new adresse();
		$standort_lieferadresse->load($standorte->adresse_id);

		if($standort_lieferadresse->adresse_id == $bestellung->lieferadresse)
		{
			$selected ='selected';
		}
		echo "<option value='".$standort_lieferadresse->adresse_id."' ". $selected.">".$standorte->kurzbz.' - '.$standort_lieferadresse->strasse.', '.$standort_lieferadresse->plz.' '.$standort_lieferadresse->gemeinde."</option>\n";
	}
	echo "</select></td></tr>\n";
	echo "<tr>\n";

	echo "	<td>Konto: </td>\n";
	echo "	<td><SELECT name='filter_konto' id='konto' style='width: 230px;'>\n";
	foreach($konto->result as $ko)
	{
		$selected ='';
		if($ko->konto_id == $bestellung->konto_id)
		{
			$selected = 'selected';
			$konto_vorhanden = true;
		}
		echo '<option value='.$ko->konto_id.' '.$selected.'>'.$ko->kurzbz."</option>\n";
	}
	//wenn die konto_id von der bestellung nicht in den Konten die der Kostenstelle zugeordnet sind befidet --> selbst hinschreiben
	if(!$konto_vorhanden)
	{
		echo '<option value='.$bestellung->konto_id.' selected>'.$konto_bestellung->kurzbz."</option>\n";
	}
	echo "</select>";
	echo '<a href="konto_hilfe.php" onclick="FensterOeffnen(this.href); return false" title="Informationen zu den Konten"> <img src="../../../skin/images/question.png"> </a>';
	echo "&nbsp; <label for=\"nicht_bestellen\">nicht bestellen</label> <input type=\"checkbox\" id=\"nicht_bestellen\" name=\"nicht_bestellen\" ".($bestellung->nicht_bestellen != null && $bestellung->nicht_bestellen === true ?'checked':'').">";
	echo "</td>";


	// Rechnungsadresse
	echo "<td>Rechnungsadresse:</td>\n";
	echo "<td colspan ='2'><Select name='filter_rechnungsadresse' id='filter_rechnungsadresse' style='width: 400px;'>\n";
	foreach($allStandorte->result as $standorte)
	{
		$selected ='';
		$standort_rechnungsadresse = new adresse();
		$standort_rechnungsadresse->load($standorte->adresse_id);

		if($standort_rechnungsadresse->adresse_id == $bestellung->rechnungsadresse)
			$selected ='selected';

		echo "<option value='".$standort_rechnungsadresse->adresse_id."' ". $selected.">".$standorte->kurzbz.' - '.$standort_rechnungsadresse->strasse.', '.$standort_rechnungsadresse->plz.' '.$standort_rechnungsadresse->ort."</option>\n";
	}
	echo "</select></td></tr>\n";
	echo "<tr>\n";
	echo "	<td >Interne Bemerkungen: </td>\n";
	echo "	<td ><textarea name='bemerkung' cols=\"70\" rows=\"2\" style='width: 482px;'>$bestellung->bemerkung</textarea></td>\n";
	echo "	<td>Status:</td>\n";
	echo "	<td width ='200px'>\n";
	echo "<span id='btn_bestellt'>";

	$new = 0;
	if($status->isStatiVorhanden($bestellung->bestellung_id, 'Bestellung'))
	{
		$status_help = new wawi_bestellstatus();
		$status_help->getStatiFromBestellung('Bestellung', $bestellung->bestellung_id);
		echo ' <span style=\"white-space: nowrap;\" title ="Bestellt von '.$status_help->insertvon.'">Bestellt am: '.$date->formatDatum($status->datum,'d.m.Y').'</span>';
		$new++;
	}
	echo "</span>";
	echo "<span id='btn_geliefert'>";
	if($status->isStatiVorhanden($bestellung->bestellung_id, 'Lieferung') )
	{
		$status_help = new wawi_bestellstatus();
		$status_help->getStatiFromBestellung('Lieferung', $bestellung->bestellung_id);
		echo " <span style=\"white-space: nowrap;\" title=$status_help->insertvon>Geliefert am: ".$date->formatDatum($status->datum, 'd.m.Y')."</span>";
		$new++;
	}
	echo "</span>";
	echo "<span id='btn_storniert'>";
	if($status->isStatiVorhanden($bestellung->bestellung_id, 'Storno') )
	{
		echo " <span>Storniert am: ".$date->formatDatum($status->datum, 'd.m.Y')."</span>";
		$new++;
	}

	echo "</span>";
	if($new == 0)
	{
		echo "<span id=\"btn_erstellt\" name='erstellt' title ='".$bestellung->insertvon."' >Erstellt am: ".$date->formatDatum($bestellung->insertamum, 'd.m.Y')."</span>";
	}
	echo "</td><td>\n";

	$disabled='';
	if(($status->isStatiVorhanden($bestellung->bestellung_id, 'Bestellung')
	 ||$status->isStatiVorhanden($bestellung->bestellung_id, 'Storno')
	 || $rechte->isBerechtigt('wawi/bestellung_advanced',null,'suid') == false))
	{
		$disabled ='disabled';
	}

	echo "<input type='button' value='bestellen' id='bestellt' onclick='deleteBtnBestellt($bestellung->bestellung_id)' class='cursor' $disabled>";

	$disabled='';
	if($status->isStatiVorhanden($bestellung->bestellung_id, 'Lieferung')
	 ||$status->isStatiVorhanden($bestellung->bestellung_id, 'Storno'))
	{
		$disabled ='disabled';
	}
	echo "<input type='button' value='geliefert' id='geliefert' onclick='deleteBtnGeliefert($bestellung->bestellung_id)' class='cursor' $disabled>";

	$disabled = '';

	if($status->isStatiVorhanden($bestellung->bestellung_id, 'Bestellung')
	|| $status->isStatiVorhanden($bestellung->bestellung_id, 'Storno'))
	{
		$disabled ='disabled';
	}
	if($rechte->isberechtigt('wawi/storno',null, 'suid', $bestellung->kostenstelle_id))
	{
		echo "<input type='button' value='stornieren' id='storniert' name='storniert' $disabled onclick='deleteBtnStorno($bestellung->bestellung_id)' class='cursor' >";
	}

	echo"</td></tr>\n";
	echo "<tr>\n";
	echo"<td>Tags:</td>\n";
	$bestell_tag->GetTagsByBestellung($bestellung->bestellung_id);
	$tag_help = $bestell_tag->GetStringTags();
	echo "<td><input type='text' id='tags' name='tags' size='32' value='".$tag_help."'>\n";

	echo "<script type='text/javascript'>
		$('#tags').autocomplete(
		{
			source: 'wawi_autocomplete.php?work=tags',
			minChars:1,
			response:function(event,ui)
			{
				for(i in ui.content)
				{
					ui.content[i].value=ui.content[i].tag;
					ui.content[i].label=ui.content[i].tag;
				}
			},
			select: function(event, ui)
			{
				ui.item.value=ui.item.tag;
			}
		});
		</script>";

	echo "</td>\n";
	echo "<td>Freigabe:</td>\n";
	echo "<td colspan =2>";

	if(!$status->isStatiVorhanden($bestellung->bestellung_id, 'Abgeschickt') && !$bestellung->freigegeben)
	{
		echo "<span>Bitte Abschicken zur Freigabe.</span>";
	}

	$freigabebutton = true;
	// Freigabe Buttons fuer Kostenstelle Anzeigen
	if($status->isStatiVorhanden($bestellung->bestellung_id, 'Freigabe','','ASC'))
	{
		echo "<span title='$status->insertvon'>KST:".$date->formatDatum($status->datum,'d.m.Y')." </span>";
	}
	else
	{
		$disabled = 'disabled';
		if($status->isStatiVorhanden($bestellung->bestellung_id, 'Abgeschickt'))
		{
			if($rechte->isberechtigt('wawi/freigabe',null, 'su', $bestellung->kostenstelle_id)
			|| $rechte->isBerechtigt('wawi/freigabe_advanced'))
			{
				$disabled = '';
				$freigabebutton=false;
			}

			echo "<input type='submit' value='KST Freigabe' ".($disabled==''?"name ='btn_freigabe_kst'":$disabled).">";
		}
	}

	// Freigabe Buttons fuer Organisationseinheiten anzeigen
	if($status->isStatiVorhanden($bestellung->bestellung_id, 'Abgeschickt'))
	{
		$oes = array();
		$oes = $bestellung->FreigabeOe($bestellung->bestellung_id);
		$freigabe = false;
		foreach($oes as $o)
		{
			if(!$status->isStatiVorhanden($bestellung->bestellung_id, 'Freigabe', $o))
			{
				if($freigabebutton===true && ($rechte->isberechtigt('wawi/freigabe',$o, 'su', null)
				|| $rechte->isBerechtigt('wawi/freigabe_advanced')))
				{
					echo "<input type='submit' value='".mb_strtoupper($o)." Freigabe ' name ='btn_freigabe'>";
					echo "<input type='hidden' value='$o' name ='freigabe_oe' id ='freigabe_id'>";
					$freigabebutton=false;
				}
				else
				{
					echo "<input type='button' value='".mb_strtoupper($o)." Freigabe ' disabled>";
				}
				$freigabe = true;
			}
			else
			{
				echo "<span title='$status->insertvon'>".$o.":".$date->formatDatum($status->datum,'d.m.Y')." </span>";
			}
		}
	}

	echo "</td></tr>";
	echo "<tr><td>Zahlungsweise:</td>";
	echo "<td><SELECT name='filter_zahlungstyp' id='search_zahlungstyp' >\n";
	echo "<option value=''>-- bitte auswählen --</option>";
	$zahlungstyp = new wawi_zahlungstyp();
	if ($bestellung->insertamum!='')
	{
		$datum_helper = new datum();
		$insertTimestamp = $datum_helper->mktime_fromdate($bestellung->insertamum);
		$date_newZahlungstyp = mktime(0, 0, 0, 2, 1, 2016);
		if ($insertTimestamp >= $date_newZahlungstyp)
		{
			$zahlungstyp->getAllFiltered();
		}
		else
		{
			$zahlungstyp->getAll();
		}
	}
	else
	{
		$zahlungstyp->getAllFiltered();
	}

	foreach($zahlungstyp->result as $typ)
	{
		$selected = '';
		if($bestellung->zahlungstyp_kurzbz == $typ->zahlungstyp_kurzbz)
			$selected = "selected";
		echo '<option value='.$typ->zahlungstyp_kurzbz.' '.$selected.'>'.$typ->bezeichnung."</option>\n";
	}
	echo "</select> ";
	echo " <label for=\"auslagenersatz\">Auslagenersatz/IBAN:</label> <input type=\"checkbox\" id=\"auslagenersatz\" name=\"auslagenersatz\" ".($bestellung->auslagenersatz != null && $bestellung->auslagenersatz === true ?'checked':'').">";

	echo "<span id=\"bankverbindung_span\" style=\"display:none\"> <input type=\"text\" id=\"bankverbindung\" style=\"width: 110px\" name=\"bankverbindung\" maxlength=\"32\" value=\"".$bestellung->iban."\"></span>";
	echo "</td>\n";
	echo "<td>Rest-Budget:</td>\n";

	$restBudget = sprintf('%01.2f',$restBudget);
	echo "<td colspan=\"2\" id='restbudget'>$restBudget <small>(nach Abzug dieser Bestellung)</small></td></tr>";
	echo "<tr>";
	echo "<td>Zuordnung:</td><td>";
	$zuordnungListe = wawi_zuordnung::getAll();

	while (list($key, $val) = each($zuordnungListe))
	{
		$selected = false;
		if (isset($bestellung->zuordnung) && $bestellung->zuordnung == $key)
		$selected = true;
		echo '<input type="radio" id="zuordnung_'.$key.'" name="zuordnung" value="'.$key.'" '.($selected?'checked="1"':'').'\"><label for="zuordnung_'.$key.'"> '.$val.'</label> ';
	}
	echo "</td>";
	echo "<td>Für wen (Person):</td>";
	echo "<td><input type='text' name='zuordnung_person' id='zuordnung_person' size='27' maxlength='256' value ='".$zuordnung_person_vorname.' '.$zuordnung_person_nachname."'>".
		"<a onClick='resetFuerWen()' title='Zuordnung löschen' id=\"reset_zuordnung_person\" class=\"resetBtn\"> <img src=\"../../../skin/images/delete_round.png\" class='cursor'></a>"
		."</td>";
	echo "<td>Wo eingesetzt (Raum): <input name= \"zuordnung_raum\" id=\"zuordnung_raum\" type='text' size='13' maxlength='32' value =\"".$bestellung->zuordnung_raum."\"></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td>Auftragsbestätigung</td><td><input type=\"text\" id=\"datepicker_auftragsbestaetigung\" size=\"12\" name=\"auftragsbestaetigung\" value=\"".$date->formatDatum($bestellung->auftragsbestaetigung,'d.m.Y')."\"> Angebote: <ul id=\"angeboteListe\"><li>1<li>2</ul> <input type=\"button\" name=\"angebotBtn\" id=\"angebotBtn\" value=\"hinzufügen\" ></td>";
	echo "<td>Leasing:</td><td colspan=\"2\"><input type=\"checkbox\" id=\"empfehlung_leasing\" name=\"empfehlung_leasing\" ".($bestellung->empfehlung_leasing != null && $bestellung->empfehlung_leasing === true ?'checked':'')."><label for=\"empfehlung_leasing\">Empfehlung Leasing</label> <input type=\"checkbox\" id=\"wird_geleast\" name=\"wird_geleast\"  ".($bestellung->wird_geleast != null && $bestellung->wird_geleast === true ?'checked':'')."><label for=\"wird_geleast\">wird geleast</label> </td>";
	echo "</tr>";

	echo "</table>\n";
	echo "<br>";
	//tabelle Details
	echo "<table border ='0' width='70%'>\n";
	echo "<tr>\n";
	echo "<th></th>\n";
	echo "<th></th>\n";
	echo "<th></th>\n";
	echo "<th>Pos</th>\n";
	//	echo "<th>Sort</th>\n";
	echo "<th>Menge</th>\n";
	echo "<th>VE</th>\n";
	echo "<th>Bezeichnung</th>\n";
	echo "<th>Artikelnr.</th>\n";
	echo "<th>Preis/VE</th>\n";
	echo "<th>USt</th>\n";
	echo "<th>Brutto</th>\n";
	echo "<th nowrap>Tags <a id='tags_link' onClick='hideTags();'><img src='../../../skin/images/plus.png' title='Detailtags anzeigen' class ='cursor'> </a></th>";
	echo "</tr>\n";
	echo "<tbody id='detailTable'>";
	$i= 1;
	foreach($detail->result as $det)
	{

		$brutto=($det->menge * ($det->preisprove +($det->preisprove * ($det->mwst/100))));
		$brutto = floor( $brutto * 1000) / 1000;
		getDetailRow($i, $det->bestelldetail_id, $det->sort, $det->menge, $det->verpackungseinheit, $det->beschreibung, $det->artikelnummer, $det->preisprove, $det->mwst, sprintf("%01.2f",$brutto), $bestellung->bestellung_id, $det->position);
		$summe+=$brutto;
		$i++;
	}
	if(!$bestellung->freigegeben)
	{
		// Wenn neue Bestellung -> erste Detailzeile mit 'Was wird bestellt' (Titel der Bestellung) befüllen
		// (ist angeblich in 90% der Fälle sinnvoll)
		$default_beschreibung = null;
		if (isset($_REQUEST['new']) && $_REQUEST['new'] == true)
		{
			$default_beschreibung = $bestellung->titel;
		}
		getDetailRow($i,null,$i,1,null,$default_beschreibung,null,null,null,null,$bestellung->bestellung_id,$i);
	}

	$test = $i;
	echo "</tbody>";
	echo "<tfoot>";
?>
	<tr id="nichtbestellen_tr" style="display:none"><td colspan="6"></td>
			<td style="font-family:'Courier New',Arial,TimesNewRoman;font-size:10pt;font-weight:bold;background:#FFF;color:#FF0000;border:1px solid #ccc;">Bitte nicht bestellen!</td>
			<td colspan="5"></td>
	</tr>
<?php

	echo "<tr>";
	echo "<td></td>";
	echo "<td></td>";
	echo "<td></td>";
	echo "<td colspan='3'>";

	// neue Zeile hinzufügen nur mit Berechtigung
	if($rechte->isberechtigt('wawi/bestellung_advanced',null, 'suid', $bestellung->kostenstelle_id))
		echo "<input type='button' value='neue Zeile' onclick='newRow();' class='cursor'>";
	echo "</td>";
	echo "<td></td>";
	echo "<td><input type='hidden' name='detail_anz' id='detail_anz' class='number' value='$test'></td>";
	echo "<td colspan ='2' style='text-align:right;'>Gesamtpreis Netto:</td>";
	echo "<td class='number'><span id='netto'></span> &euro;</td>";
	echo "<td></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td></td>";
	echo "<td></td>";
	echo "<td></td>";
	echo "<td></td>";
	echo "<td></td>";
	echo "<td></td>";
	echo "<td></td>";
	echo "<td></td>";
	echo "<td colspan ='2' style='text-align:right;'>Gesamtpreis Brutto:</td>";
	echo "<td class='number'><span id='brutto'></span> &euro;</td>";
	echo"<td></td>";
	echo "</tr>";
	echo "</tfoot>";
	echo "</table>\n";
	echo "<br><br>\n";
	echo '
		<script type="text/javascript">

		var anzahlRows='.$i.';
		var bestellung_id ='.$bestellung->bestellung_id.';
		var uid = "'.$user.'";
		var focusRow ="1";


		function FelderSperren(value)
		{
			var inputs = $("#editForm :input");

			inputs.each(function()
			{
				if(this.id!="tags"
				&& !this.id.match(/^detail_tag_/)
				&& this.id!="filter_projekt"
				&& this.type!="button"
				&& this.type!="submit"
				&& this.type!="hidden")
				{
					this.disabled=value;
				}
			});
		}

		//zeigt/versteckt die Tags an
		function hideTags()
		{
			i=1;
			while(i<=anzahlRows)
			{
				$("#detail_tag_"+i).toggle();
				i=i+1;
			}
			$("#tags_link").toggle();
			return false;
		}

		//zeigt die Tags der Details an wenn vorhanden, und versteckt diese wenn sie leer sind
		function hideTags2()
		{
			var i=1;
			var show=false;
			while(i<=anzahlRows)
			{
				if($("#detail_tag_"+i).val()!="")
				{
					show=true;
				}
				i=i+1;
			}

			if(show)
			{
				var i=1;
				while(i<=anzahlRows)
				{

					$("#detail_tag_"+i).show();
					i=i+1;
				}
				$("#tags_link").hide();
			}
			else
			{
				var i=1;
				while(i<=anzahlRows)
				{
					$("#detail_tag_"+i).hide();
					i=i+1;
				}
				$("#tags_link").show();
			}
		}



		// Status bestellt wird gesetzt
		function deleteBtnBestellt(bestellung_id)
		{
			doBestellt(bestellung_id);
			/* Bestätigung temporär deaktiviert
			bestellenConfirmDialog = $( "#dialog-confirm-bestellen" ).dialog({
		      autoOpen: true,
		      resizable: false,
		      height: "auto",
		      width: 400,
		      modal: true,
		      closeOnEscape: true,
    		  closeText: "",
		      buttons: {
		        "OK": function() {
		          doBestellt(bestellung_id);
		          $( this ).dialog( "close" );
		        },
		        Cancel: function() {
		          $( this ).dialog( "close" );
		        }
		      }
		    });*/


		}

		function doBestellt(bestellung_id)
		{
			$("#btn_bestellt").html();
			$("btn_bestellt").empty();
			$("#btn_erstellt").empty();
			$.post("bestellung.php", {id: bestellung_id, user_id: uid, deleteBtnBestellt: "true"},
			function(data)
			{

				if(data.length>10)
				{
					alert(data);
				}
				else
				{
					$("#btn_bestellt").html(" <span style=\"white-space:nowrap\">Bestellt am: " +data + "</span>");

					if(typeof(document.editForm.storniert) != "undefined")
					{
						document.editForm.storniert.disabled=true;
					}
					document.editForm.bestellt.disabled=true;
					document.editForm.filter_kst.disabled=true;
				}
			});
		}

		// Status geliefert wird gesetzt
		function deleteBtnGeliefert(bestellung_id)
		{
			doGeliefert(bestellung_id);
			/* Bestätigung temporär deaktiviert
			geliefertConfirmDialog = $( "#dialog-confirm-geliefert" ).dialog({
		      autoOpen: true,
		      resizable: false,
		      height: "auto",
		      width: 400,
		      modal: true,
		      closeOnEscape: true,
    		  closeText: "",
		      buttons: {
		        "OK": function() {
		          doGeliefert(bestellung_id);
		          $( this ).dialog( "close" );
		        },
		        Cancel: function() {
		          $( this ).dialog( "close" );
		        }
		      }
		    });
		    */
		}
		function doGeliefert(bestellung_id)
		{
			$("#btn_geliefert").html();
			$("#btn_erstellt").empty();
			$.post("bestellung.php", {id: bestellung_id, user_id: uid, deleteBtnGeliefert: "true"},
			function(data){
				if(data.length>10)
				{
					alert(data);
				}
				else
				{
					$("#btn_geliefert").html(" <span style=\"white-space:nowrap\">Geliefert am: " +data + "</span>");
					document.editForm.geliefert.disabled=true;
				}
			});
		}

		// Status storno wird gesetzt
		function deleteBtnStorno(bestellung_id)
		{
			doStorno(bestellung_id);
			/* Bestätigung temporär deaktiviert
			storniertConfirmDialog = $( "#dialog-confirm-stornieren" ).dialog({
		      autoOpen: true,
		      resizable: false,
		      height: "auto",
		      width: 400,
		      modal: true,
		      closeOnEscape: true,
    		  closeText: "",
		      buttons: {
		        "OK": function() {
		          doStorno(bestellung_id);
		          $( this ).dialog( "close" );
		        },
		        Cancel: function() {
		          $( this ).dialog( "close" );
		        }
		      }
		    }); */
		}
		function doStorno(bestellung_id)
		{
			$("#btn_storniert").html();
			$("#btn_erstellt").empty();
			$.post("bestellung.php", {id: bestellung_id, user_id: uid, deleteBtnStorno: "true"},
			function(data){
				if(data.length>10)
				{
					alert(data);
				}
				else
				{
					$("#btn_storniert").html(" <span style=\"white-space:nowrap\">Storniert am: " +data + "</span>");
					document.editForm.btn_submit.disabled=true;
					document.editForm.btn_abschicken.disabled=true;
					document.editForm.storniert.disabled=true
					document.editForm.bestellt.disabled=true
					document.editForm.filter_kst.disabled=true;
				}
			});
		}

		/*
		Berechnet die Brutto Summe für eine Zeile
		*/
		function calcBrutto(id)
		{
			var brutto=0;
			var menge = $("#menge_"+id).val();
			var betrag = $("#preisprove_"+id).val();
			document.getElementById("preis_"+id).value = betrag;
			var betrag = $("#preis_"+id).val();
			var mwst = $("#mwst_"+id).val();

			if(mwst =="")
				mwst = "0";
			if(betrag!="" && mwst!="" && menge!="")
			{
				betrag = betrag.replace(",",".");
				mwst = mwst.replace(",",".");
				menge = parseFloat(menge);
				betrag = parseFloat(betrag);
				mwst = parseFloat(mwst);
				brutto = menge * (brutto + (betrag+(betrag*mwst/100)));
			}
			brutto = Math.floor(brutto*100)/100;
			document.getElementById("brutto_"+id).value = brutto;
			summe();
		}

		function calcNetto(id)
		{
			var brutto=0;
			var menge = $("#menge_"+id).val();
			var brutto = $("#brutto_"+id).val();
			var mwst = $("#mwst_"+id).val();
			if(mwst =="")
			mwst = "0";

			if(brutto!="" && mwst!="" && menge!="")
			{
				brutto = brutto.replace(",",".");
				mwst = mwst.replace(",",".");
				menge = parseFloat(menge);
				brutto = parseFloat(brutto);
				mwst = parseFloat(mwst);

				// Nettopreis berechnen
				var netto = brutto/(100+mwst)*100;
				var netto = netto / menge;

				//nicht runden für hiddenfeld
				$("#preis_"+id).val(netto);
				netto = Math.round(netto*100)/100;
				//netto = str_replace(".",",",netto);
				$("#preisprove_"+id).val(netto);
			}
			summe();
		}

		function calcBruttoNetto(id)
		{
			var inetto = $("#preis_"+id).val();
			var ibrutto = $("#brutto_"+id).val();

			if(inetto=="" || inetto==0 || inetto=="0,00")
			{
				calcNetto(id);
			}
			else
			{
				calcBrutto(id);
			}
		}

		/*
		Berechnet die gesamte Brutto Summe für eine Bestellung
		*/
		function summe()
		{
			var i=1;
			var netto=0;
			var brutto=0;
			while(i<=anzahlRows)
			{
				var menge =$("#menge_"+i).val();
				var betrag = $("#preis_"+i).val();
				var mwst = $("#mwst_"+i).val();
				if(mwst =="")
					mwst = "0";

				// wenn es spalte nicht gibt, auslassen
				if(typeof(menge) != "undefined")
				{
					if(betrag!="" && mwst!="" && menge!="")
					{
						betrag = betrag.replace(",",".");
						mwst = mwst.replace(",",".");
						menge = parseFloat(menge);
						betrag = parseFloat(betrag);
						mwst = parseFloat(mwst);
						netto = netto + betrag*menge;
						brutto = brutto + (menge * (betrag*((mwst+100)/100)));
					}
				}
				i=i+1;
			}
			netto = Math.round(netto*100)/100;
			brutto = Math.round(brutto*100)/100;
			$("#netto").html(netto);
			$("#brutto").html(brutto);
		}

		$(document).ready(function()
		{
			summe();
		});

		/**
		*	Fügt eine neue Zeile ein
		*
		*/
		function newRow()
		{
			var id = anzahlRows;
			$.post("bestellung.php", {id: id+1, bestellung_id: bestellung_id, getDetailRow: "true"},
				function(data){
					$("#detailTable").append(data);
					anzahlRows=anzahlRows+1;
					var test = 0;
					test = document.getElementById("detail_anz").value;
					document.getElementById("detail_anz").value = parseFloat(test) +1;
					hideTags2();
				});
		}

		/**
		 * Fuegt eine neue Zeile fuer den Betrag hinzu wenn die
		 * uebergebene id, die der letzte Zeile ist
		 * und der Betrag eingetragen wurde
		 */
		function checkNewRow(id, bestellung_id)
		{
			var betrag="";
			betrag = $("#preisprove_"+id).val();
			// Wenn der betrag nicht leer ist,
			// und die letzte reihe ist,
			// dann eine neue Zeile hinzufuegen
			if(betrag.length>0 && anzahlRows==id)
			{
				$.post("bestellung.php", {id: id+1, bestellung_id: bestellung_id, getDetailRow: "true"},
					function(data){
						$("#detailTable").append(data);
						anzahlRows=anzahlRows+1;
						var test = 0;
						test = document.getElementById("detail_anz").value;
						document.getElementById("detail_anz").value = parseFloat(test) +1;
						hideTags2();
					});
			}

			return false;
		}

		// speichert eine Bestelldetailzeile
		function saveDetail(i)
		{
			var pos = $("#pos_"+i).val();
			var menge = $("#menge_"+i).val();
			var ve = $("#ve_"+i).val();
			var beschreibung = $("#beschreibung_"+i).val();
			var artikelnr = $("#artikelnr_"+i).val();
			var preis = $("#preis_"+i).val();
			preis = preis.replace(",",".");
			var mwst = $("#mwst_"+i).val();
			mwst = mwst.replace(",",".");
			var brutto = $("#brutto_"+i).val();
			brutto = brutto.replace(",",".");
			var sort = $("#sort_"+i).val();

			if(menge!="" && !(menge%1==0))
			{
				alert("Menge muss eine ganze Zahl sein");
				return false;
			}
			var detailid= $("#bestelldetailid_"+i).val();
			if(detailid != "")
			{
				$.post("bestellung.php", {pos: pos, menge: menge, ve: ve, beschreibung: beschreibung, artikelnr: artikelnr, preis: preis, mwst: mwst, brutto: brutto, bestellung: bestellung_id, detail_id: detailid, sort: sort, updateDetail: "true"},
				function(data)
				{
					if(isNaN(data))
					{

					}
				});
			}
			else
			{
				$.post("bestellung.php", {pos: pos, menge: menge, ve: ve, beschreibung: beschreibung, artikelnr: artikelnr, preis: preis, mwst: mwst, brutto: brutto, bestellung: bestellung_id, sort:sort, saveDetail: "true"},
					function(data)
					{
						if(isNaN(data) == false)
						{
							document.getElementById("bestelldetailid_"+i).value = data;
						}
						else
						{

						}
					});
			}
		}

		// löscht einen Bestelldetaileintrag
		function removeDetail(i)
		{
			var detail_id= $("#bestelldetailid_"+i).val();
			if (!detail_id) {
				// leere Zeile ohne Post entfernen (damit keine Fehlermeldung kommt)
				$("#row_"+i).remove();
				return;
			}
			$.post("bestellung.php", {id: detail_id, deleteDetail: "true"},
			function(data)
			{
				if(data=="")
				{
					$("#row_"+i).remove();
				}
				else
					alert(data);
			});
			summe();
		}

		function resetFuerWen() {
			$("#zuordnung_uid").val(null);
			$("#zuordnung_person").val("");
		}

		function conf_del_budget(aktBrutto)
		{
			FelderSperren(false);
		}

		function warnungSummeIst0()
		{
			var nettoSummeHTML = $("#netto").html();
			var nettoSumme = parseFloat(nettoSummeHTML);
			if (nettoSumme == 0.0 )
			{
				return confirm("Achtung Bestellsumme ist 0!");
			}
			return true;
		}

		function maybeSubmit()
		{
			if (!warnungSummeIst0())
			{
				return false;
			}
			document.getElementById("filter_kst").disabled=false;
			return true;
			}

		// beim verlassen der textbox ändere . in ,
		function replaceKomma(rowid)
		{
			var mwst = $("#mwst_"+rowid).val();
			mwst=str_replace(".",",",mwst);
			document.getElementById("mwst_"+rowid).value = mwst;
			var preisprove = $("#preisprove_"+rowid).val();
			preisprove =str_replace(".",",",preisprove);
			document.getElementById("preisprove_"+rowid).value=preisprove;
			var preis = $("#preis_"+rowid).val();
			preis =str_replace(".",",",preis);
			document.getElementById("preis_"+rowid).value=preis;
			var brutto = $("#brutto_"+rowid).val();
			brutto = str_replace(".",",",brutto);
			document.getElementById("brutto_"+rowid).value=brutto;
		}


		// ändert sich der fokus der Bestelldetailzeile -> speichern der geänderten
		function checkSave(rowid)
		{
			if(focusRow != rowid)
			{
				saveDetail(focusRow);
				focusRow = rowid;
			}
		}

		//wie PHP str_replace();
		var str_replace = function(mysearch, myreplace, mysubject)
		{
			return mysubject.split(mysearch).join(myreplace);
		}

		// check USt
		function checkUst(i)
		{
			var mwst = $("#mwst_"+i).val();
			mwst=str_replace(",",".",mwst);
			if(mwst > 99 || mwst < 0 || isNaN(mwst))
			{
				alert("Ungültige Mehrwertssteuer eingetragen.");
				document.getElementById("mwst_"+i).value = "20,00";
				calcBruttoNetto(i);
			}
		}

		// Beim Start pruefen ob Tags bei den Details vorhanden sind und ggf verstecken/anzeigen
		$(document).ready(function()
		{
			hideTags2();
		});

		//Verschiebt die Details in der Tabelle und setzt das Sort Attribut neu
		function verschieben(obj)
		{
			//row holen und an die richtige stelle schieben
			var row = $(obj).parents("tr:first");

			if ($(obj).is(".up"))
				row.insertBefore(row.prev());
			else
				row.insertAfter(row.next());

			// alle Rows durchlaufen, und das Sort Attribut neu setzen
			rows = $("#detailTable").children();
			var anzahl = rows.length;
			var i=0;
			while(i<anzahl)
			{
				id = rows[i].id.substring("row_".length);
				sort = document.getElementById("sort_"+id);
				sort.value=i;
				i++;
			}
		}
		</script>';

	$disabled='';
	if($status->isStatiVorhanden($bestellung->bestellung_id, 'Storno') || $status->isStatiVorhanden($bestellung->bestellung_id, 'Abgeschickt') || ($bestellung->freigegeben))
		$disabled='disabled';

	$aktBrutto = $bestellung->getBrutto($bestellung->bestellung_id);
	if($aktBrutto =='')
		$aktBrutto ="0";
	echo '<table border ="0" style="width: auto"> <tr>';
	echo "<td><div style='float:right;'><input type='submit' value='Speichern' id='btn_submit' name='btn_submit' onclick='return conf_del_budget($aktBrutto);' class='cursor'></td>";
	echo "<td><input type='submit' value='Abschicken' id='btn_abschicken' name='btn_abschicken' $disabled class='cursor'></td>";
	if($status->isStatiVorhanden($bestellung->bestellung_id, 'Abgeschickt') && !$bestellung->freigegeben)
		echo "<td><input type='submit' value='Erneut Abschicken' id='btn_erneut_abschicken' name='btn_erneut_abschicken' class='cursor'></td>";
	echo"<td style='width:100%' align='right'>";
	echo "<div ><a href =\"pdfExport.php?xml=bestelldetail.rdf.php&xsl_oe_kurzbz=$kostenstelle->oe_kurzbz&xsl=Bestellung&id=$bestellung->bestellung_id\" target=\"_blank\">Bestellschein generieren <img src='../../../skin/images/pdf.ico'></a></div>";
	echo "</td></tr></table><br>";
	if($disabled!='')
	{
		//Wenn die Advanced Berechtigung vorhanden ist, werden die Felder nicht gesperrt oder derjenige hat berechtigungen auf die kst oder oe und die bestellung ist noch nicht freigegeben
		if(!($rechte->isBerechtigt('wawi/bestellung_advanced',null, 'suid')
		|| ($rechte->isBerechtigt('wawi/freigabe',null,'suid',$bestellung->kostenstelle_id)) && !$bestellung->freigegeben))
		{
			// Felder Sperren
			echo '<script type="text/javascript">
				$(document).ready(function()
				{
					FelderSperren(true);
				});
			</script>';
		}
	}
	if(isset($_SESSION['wawi/lastsearch']))
		echo '<input type="button" class="cursor" onclick="window.location.href=\'bestellung.php?'.$_SESSION['wawi/lastsearch'].'\'" value="Zurück zur Liste" /><br><br>';

	if($status->isStatiVorhanden($bestellung->bestellung_id, 'Abgeschickt'))
		echo "Bestellung wurde am ".$date->formatDatum($status->datum,'d.m.Y')." zur Freigabe abgeschickt.";


	if ($status->getAllStatiFromBestellung($bestellung->bestellung_id, 'Abgeschickt-Erneut'))
	{

		foreach ($status->result as $s)
		{
			echo "<br>Bestellung wurde am ".$date->formatDatum($s->datum,'d.m.Y')." erneut zur Freigabe abgeschickt.";
		}

	} else {
		echo '<br/>'.$status->errormsg;
	}

	if($bestellung->isFreigegeben($bestellung->bestellung_id))
		echo "<p class='freigegeben'>Die Bestellung wurde vollständig freigegeben</p>";

	echo "</form> <!-- editFrm -->";
?>
	<!-- confirm dialog -->
	<div id="dialog-confirm-bestellen" title="Bestellung bestätigen" style="display:none">
	  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Bestellung wirklich durchführen?</p>
	</div>
	<div id="dialog-confirm-geliefert" title="Lieferung bestätigen" style="display:none">
	  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Bestellung als geliefert eintragen?</p>
	</div>
	<div id="dialog-confirm-stornieren" title="Stornierung bestätigen" style="display:none">
	  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Bestellung wirklich stornieren?</p>
	</div>
<?php
}

/**
 * Gibt eine Bestelldetail Zeile aus
 */
function getDetailRow($i, $bestelldetail_id='', $sort='', $menge='', $ve='', $beschreibung='', $artikelnr='', $preisprove='', $mwst='', $brutto='', $bestell_id='', $pos='')
{
	global $basepath;
	$removeDetail ='';
	$checkSave = "checkSave(".$i.");";
	$checkRow = '';
	$replaceKomma = "replaceKomma(".$i.");";
	$user=get_uid();
	$status= new wawi_bestellstatus();
	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($user);
	$bestellung = new wawi_bestellung();
	$bestellung->load($bestell_id);
	// wenn status Storno oder Abgeschickt, kein löschen der Details mehr möglich
	if(!$status->isStatiVorhanden($bestell_id,'Storno'))
	{
		if(!$status->isStatiVorhanden($bestell_id,'Abgeschickt'))
		{
			$removeDetail = "removeDetail(".$i.");";
			$checkRow = "setTimeout(\"checkNewRow(".$i.",".$bestell_id.")\",100);";
		}

		if($status->isStatiVorhanden($bestell_id,'Abgeschickt') && ($rechte->isBerechtigt('wawi/bestellung_advanced') || ($rechte->isBerechtigt('wawi/freigabe', null,'suid',$bestellung->kostenstelle_id) && !$bestellung->freigegeben)))
			$removeDetail = "removeDetail(".$i.");";
	}

	if($sort == '')
		$sort = $i;
	$mwst = str_replace('.', ',', $mwst);

	echo "<tr id ='row_$i'>\n";
	echo "<td><a onClick='$removeDetail' title='Bestelldetail löschen'> <img src=\"../../../skin/images/delete_round.png\" class='cursor'> </a></td>\n";
	echo "<td><a href='#' class='down' onClick='verschieben(this);'><img src=\"../../../skin/images/arrow-single-down-green.png\" class='cursor' ></a></td>\n";
	echo "<td> <a href='#' class='up' onClick='verschieben(this);'><img src=\"../../../skin/images/arrow-single-up-green.png\" class='cursor' ></a></td>\n";
	echo "<td><input type='text' size='2' name='pos_$i' id='pos_$i' maxlength='2' value='$pos' onfocus='$checkSave'></td>\n";
	echo "<td><input type=\"number\" min=\"0\" size='5' class='number' name='menge_$i' id='menge_$i' maxlength='7' value='$menge' onChange='calcBruttoNetto($i);' onfocus='$checkSave' style=\"width:60px\"></td>\n";
	echo "<td><input type='text' size='5' name='ve_$i' id='ve_$i' maxlength='7' value='$ve' onfocus='$checkSave'></td>\n";
	echo "<td><input type='text' size='70' name='beschreibung_$i' id='beschreibung_$i' value='$beschreibung' onblur='$checkRow' onfocus='$checkSave'></td>\n";
	echo "<td><input type='text' size='15' name='artikelnr_$i' id='artikelnr_$i' maxlength='32' value='$artikelnr' onfocus='$checkSave'></td>\n";
	echo "<td><input type='text' size='15' class='number' name='preisprove_$i' id='preisprove_$i' maxlength='15' value='".sprintf("%01.2f",$preisprove)."' onblur='$checkRow $replaceKomma' onChange='calcBrutto($i);' onfocus='$checkSave'></td>\n";
	echo "<td><input type='text' size='8' class='number' name='mwst_$i' id='mwst_$i' maxlength='5' value='$mwst' onChange='calcBruttoNetto($i);' onfocus='$checkSave' onblur='checkUst($i); $replaceKomma'></td>\n";
	echo "<td><input type='text' size='10' class='number' name ='brutto_$i' id='brutto_$i' value='$brutto' onChange ='calcNetto($i);' onBlur='$replaceKomma' onfocus='$checkSave'></td>\n";
	$detail_tag = new tags();
	$detail_tag->GetTagsByBestelldetail($bestelldetail_id);
	$help = $detail_tag->GetStringTags();

	echo "<script type='text/javascript'>
		$(document).ready(function()
		{
			$('#detail_tag_'+$i).autocomplete(
			{
				source: 'wawi_autocomplete.php?work=detail_tags',
				minChars:1,
				response:function(event,ui)
				{
					for(i in ui.content)
					{
						ui.content[i].value=ui.content[i].tag;
						ui.content[i].label=ui.content[i].tag;
					}
				},
				select: function(event, ui)
				{
					ui.item.value=ui.item.tag;
				}
			});
		});
	</script>";

	echo "<td><input type='text' size='10' name='detail_tag_$i' id='detail_tag_$i' value='$help' ></td>";
	echo "<td><input type='hidden' size='20' name='bestelldetailid_$i' id='bestelldetailid_$i' value='$bestelldetail_id'></td>";
	echo "<td><input type='hidden' size='3' name='sort_$i' id='sort_$i' maxlength='2' value='$sort'></td>\n";
	echo "<td><input type='hidden' size='3' name='preis_$i' id='preis_$i' value='$preisprove'></td>\n";
	echo "</tr>\n";

}

/**
 * Sendet ein FreigabeMail fuer einen Bestellung
 *
 * @param uids Array mit UIDs an die das Freigabemail gesendet werden soll
 * @param bestellung Bestellung Object mit der Bestellung die freigegeben werden soll
 */
function sendFreigabeMails($uids, $bestellung, $user)
{
	global $date;
	$tags = new tags();
	$tags->GetTagsByBestellung($bestellung->bestellung_id);
	$tagsAusgabe='';
	foreach($tags->result as $res)
	{
		if($tagsAusgabe!='')
			$tagsAusgabe.=', ';

		$tagsAusgabe.=$res->tag;
	}
	$msg = '';

	$kst_mail = new wawi_kostenstelle();
	$kst_mail->load($bestellung->kostenstelle_id);
	$firma_mail = new firma();
	$firma_mail->load($bestellung->firma_id);
	$konto_mail = new wawi_konto();
	$konto_mail->load($bestellung->konto_id);
	$besteller = new benutzer();
	$besteller->load($bestellung->besteller_uid);

	// E-Mail an Kostenstellenverantwortliche senden
	$email= "Dies ist eine automatisch generierte E-Mail.<br><br>";
	$email.="Es wurde eine neue Bestellung auf Kostenstelle '".$kst_mail->bezeichnung."' erstellt bzw. eine bestehende ge&auml;ndert. Bitte geben Sie die Bestellung frei.<br>";
	$email.="Bestellnummer: ".$bestellung->bestell_nr."<br>";
	$email.="Beschreibung: ".$bestellung->titel."<br>";
	$email.="Lieferant/Empfänger: ".$firma_mail->name."<br>";
	$email.="Kontaktperson: ".$besteller->titelpre.' '.$besteller->vorname.' '.$besteller->nachname.' '.$besteller->titelpost."<br>";
	$email.="Erstellt am: ".$date->formatDatum($bestellung->insertamum,'d.m.Y')."<br>";
	$email.="Kostenstelle: ".$kst_mail->bezeichnung."<br>Konto: ".$konto_mail->kurzbz."<br>";
	$email.="Tags: ".$tagsAusgabe."<br>";

	$email.="Link: <a href='".APP_ROOT."vilesci/indexFrameset.php?content=bestellung.php&method=update&id=$bestellung->bestellung_id'>zur Bestellung </a>";

	foreach($uids as $uid)
	{
		$mail = new mail($uid.'@'.DOMAIN, $user, 'Freigabe Bestellung '.$bestellung->bestell_nr, 'Bitte sehen Sie sich die Nachricht in HTML Sicht an, um den Link vollständig darzustellen.');
		$mail->setHTMLContent($email);
		if(!$mail->send())
			$msg.= '<span class="error">Fehler beim Senden des Mails</span><br />';
		else
			$msg.= ' Mail verschickt an '.$uid.'@'.DOMAIN.'!<br>';
	}
	return $msg;
}

/**
 * E-Mail Benachrichtigung ueber vollstaendige Freigabe der Bestellung an
 * den Zentraleinkauf senden
 * @param $bestellung Bestellung Object der freigegebenen Bestellung
 */
function sendZentraleinkaufFreigegeben($bestellung)
{
	global $date;
	$tags = new tags();
	$tags->GetTagsByBestellung($bestellung->bestellung_id);
	$tagsAusgabe='';
	foreach($tags->result as $res)
	{
		if($tagsAusgabe!='')
			$tagsAusgabe.=', ';

		$tagsAusgabe.=$res->tag;
	}
	$msg = '';

	$kst_mail = new wawi_kostenstelle();
	$kst_mail->load($bestellung->kostenstelle_id);
	$firma_mail = new firma();
	$firma_mail->load($bestellung->firma_id);
	$konto_mail = new wawi_konto();
	$konto_mail->load($bestellung->konto_id);
	$besteller = new benutzer();
	$besteller->load($bestellung->besteller_uid);

	// E-Mail an Kostenstellenverantwortliche senden
	$email= "Dies ist eine automatisch generierte E-Mail.<br><br>";
	$email.= "Die folgende Bestellung wurde freigegeben und kann bestellt werden:<br>";
	$email.="Kostenstelle: ".$kst_mail->bezeichnung."<br>";
	$email.="Bestellnummer: ".$bestellung->bestell_nr."<br>";
	$email.="Beschreibung: ".$bestellung->titel."<br>";
	$email.="Lieferant/Empfänger: ".$firma_mail->name."<br>";
	$email.="Kontaktperson: ".$besteller->titelpre.' '.$besteller->vorname.' '.$besteller->nachname.' '.$besteller->titelpost."<br>";
	$email.="Erstellt am: ".$date->formatDatum($bestellung->insertamum,'d.m.Y')."<br>";
	$email.="Konto: ".$konto_mail->kurzbz."<br>";
	$email.="Tags: ".$tagsAusgabe."<br>";

	$email.="Link: <a href='".APP_ROOT."vilesci/indexFrameset.php?content=bestellung.php&method=update&id=$bestellung->bestellung_id'>zur Bestellung </a>";

	$mail = new mail(MAIL_ZENTRALEINKAUF, 'no-reply', 'Freigabe Bestellung', 'Bitte sehen Sie sich die Nachricht in HTML Sicht an, um den Link vollständig darzustellen.');
	$mail->setHTMLContent($email);
	if(!$mail->send())
		$msg.= '<span class="error">Fehler beim Senden des Mails</span><br />';
	else
		$msg.= ' Mail verschickt an '.MAIL_ZENTRALEINKAUF.'!<br>';

	return $msg;
}

/**
 * Schickt ein Status-Mail an die Kontaktperson der Bestellung
 *
 * @param $bestellung Bestellung Object der Bestellung
 * @param $status Art der Statusaenderung (bestellt|geliefert|freigabe|storno)
 */
function sendBestellerMail($bestellung, $status)
{
	global $date;
	$tags = new tags();
	$tags->GetTagsByBestellung($bestellung->bestellung_id);
	$tagsAusgabe='';
	foreach($tags->result as $res)
	{
		if($tagsAusgabe!='')
			$tagsAusgabe.=', ';

		$tagsAusgabe.=$res->tag;
	}
	$msg = '';

	$kst_mail = new wawi_kostenstelle();
	$kst_mail->load($bestellung->kostenstelle_id);
	$firma_mail = new firma();
	$firma_mail->load($bestellung->firma_id);
	$konto_mail = new wawi_konto();
	$konto_mail->load($bestellung->konto_id);

	// E-Mail an Kostenstellenverantwortliche senden
	$email= "Dies ist eine automatisch generierte E-Mail.<br><br>";

	switch($status)
	{
		case 'bestellt':	$email.=" <b>Ihre Bestellung wurde bestellt</b>"; break;
		case 'geliefert':	$email.=" <b>Ihre Bestellung wurde geliefert</b><br>Hinweis: Nach erfolgter Lieferung werden Waren ab einem Wert von EUR 400,-- pro Einzelposition inventarisiert. <br>"; break;
		case 'freigabe':	$email.=" <b>Ihre Bestellung wurde freigegeben</b>"; break;
		case 'storno':		$email.=" <b>Ihre Bestellung wurde storniert</b>"; break;
	}

	$email.="<br>";
	$email.="Kostenstelle: ".$kst_mail->bezeichnung."<br>";
	$email.="Bestellnummer: ".$bestellung->bestell_nr."<br>";
	$email.="Beschreibung: ".$bestellung->titel."<br>";
	$email.="Lieferant/Empfänger: ".$firma_mail->name."<br>";
	$email.="Erstellt am: ".$date->formatDatum($bestellung->insertamum,'d.m.Y')."<br>";
	$email.="Kostenstelle: ".$kst_mail->bezeichnung."<br>Konto: ".$konto_mail->kurzbz."<br>";
	$email.="Tags: ".$tagsAusgabe."<br>";

	$email.="Link: <a href='".APP_ROOT."vilesci/indexFrameset.php?content=bestellung.php&method=update&id=$bestellung->bestellung_id'>zur Bestellung </a>";

	$mail = new mail($bestellung->besteller_uid.'@'.DOMAIN, 'no-reply', 'Bestellung '.$bestellung->bestell_nr, 'Bitte sehen Sie sich die Nachricht in HTML Sicht an, um den Link vollständig darzustellen.');
	$mail->setHTMLContent($email);
	if(!$mail->send())
		$msg.= '<span class="error">Fehler beim Senden des Mails</span><br />';
	else
		$msg.= ' Mail verschickt an '.$bestellung->besteller_uid.'@'.DOMAIN.'!<br>';

	return $msg;
}

if ($export == '' || $export == 'html')
  {
?>
</body>
</html>
<?php
  }
