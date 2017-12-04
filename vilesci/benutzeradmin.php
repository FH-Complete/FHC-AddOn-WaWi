<?php
/* Copyright (C) 2017 Technikum-Wien
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

require_once dirname(__FILE__).'/../../../include/firma.class.php';
require_once dirname(__FILE__).'/../../../include/organisationseinheit.class.php';
require_once dirname(__FILE__).'/../../../include/mitarbeiter.class.php';
require_once(dirname(__FILE__).'/../../../include/berechtigung.class.php');

require_once('../include/wawi_kostenstelle.class.php');
require_once('../include/wawi_benutzerberechtigung.class.php');
require_once(dirname(__FILE__).'/../../../include/benutzer.class.php');

$user=get_uid();

function printHeader($titel)
{
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title><?php echo $titel ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css"/>
    <!--    <link rel="stylesheet" href="../../../skin/jquery.css" type="text/css"/> -->
    <link rel="stylesheet" href="../skin/jquery-ui.min.css" type="text/css"/>
    <link rel="stylesheet" href="../../../skin/fhcomplete.css" type="text/css"/>
    <link rel="stylesheet" href="../skin/wawi.css" type="text/css"/>
    <script type="text/javascript" src="../../../vendor/components/jquery/jquery.min.js"></script>
	<script type="text/javascript" src="../../../vendor/components/jqueryui/jquery-ui.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../../../vendor/FHC-vendor/jquery-tablesorter/css/theme.default.css">
	<script src="../../../vendor/FHC-vendor/jquery-tablesorter/js/jquery.tablesorter.js"></script>
	<script src="../../../vendor/FHC-vendor/jquery-tablesorter/js/jquery.tablesorter.widgets.js"></script>
	<link rel="stylesheet" type="text/css" href="../../../include/vendor_custom/jquery-tablesorter/tablesort.css">
    <script type="text/javascript" src="../include/js/jquery.loadTemplate.min.js"></script>
    <link rel="stylesheet" type="text/css" href="../skin/jquery-ui.structure.min.css"/>
    <link rel="stylesheet" type="text/css" href="../skin/jquery-ui.theme.min.css"/>
    <script type="text/javascript">
        $(document).ready(function()
        {
            $('#personFld').autocomplete(
                {
                    source: "wawi_autocomplete.php?work=wawi_mitarbeiter_search",
                    minLength:2,
                    response: function(event, ui)
                    {
                        //Value und Label fuer die Anzeige setzen
                        for(i in ui.content)
                        {
                            //ui.content[i].value=ui.content[i].uid;
                            ui.content[i].value=ui.content[i].vorname+' '+ui.content[i].nachname+' ('+ui.content[i].uid+')';
                            ui.content[i].label=ui.content[i].vorname+' '+ui.content[i].nachname+' ('+ui.content[i].uid+')';
                        }
                    },
                    select: function(event, ui)
                    {
                        ui.item.value=ui.item.label;
                        $('#person_uid').val(ui.item.uid);
                        $('#personAuswahl').submit();
                        /*
                        $.ajax({
                            method: "GET",
                            url: "benutzeradmin.php",
                            data: { person_uid: ui.item.uid },
                            contentType: "text/html",
                        })
                        .done(function( response ) {
                            $( "div#wrapper" ).replaceWith( response );
                            //alert( "ok" );
                        });*/
                    }
            });

            $("#t1").tablesorter(
                {
                    sortList: [[0,0]],
                    //widgets: ["zebra"],
                    headers: {4:{sorter:false},6:{sorter:false},7:{sorter:false}}
                });
        });

        function confdel()
        {
            if(confirm("Diesen Datensatz wirklich löschen?"))
              return true;
            return false;
        }

        function markier(id)
        {
            for (var i = 0; i < document.getElementsByName(id).length; i++)
            {
                document.getElementsByName(id)[i].style.background = "#FC988D";
            }
        }

        function unmarkier(id)
        {
            document.getElementById(id).style.background = "#eeeeee";
        }

        function checkdate(feld)
        {
            if ((feld.value != "") && (!dateCheck(feld)))
            {
                //document.studiengangform.schick.disabled = true;
                feld.className = "input_error";
                return false;
            }
            else
            {
                if(feld.value != "")
                    feld.value = dateCheck(feld);

                feld.className = "input_ok";
                return true;
            }
        }

        function checkrequired(id)
        {
            if(document.getElementById(id).value == "")
            {
                document.getElementById(id).style.border = "solid red 2px";
                return false;
            }
            else
            {
                document.getElementById(id).style.border = "";
                return true;
            }
        }
        function setnull(id)
        {
            document.getElementById(id).selectedIndex=0;
        }
        function disable(id)
        {
            document.getElementById(id).disabled = true;
            //document.getElementById("art_"+id).value="";
        }
        function enable(id)
        {
            document.getElementById(id).disabled = false;
        }
    </script>


</head>
<body>
<h1>Benutzerberechtigung - Verwaltung</h1>
<?php
}

function printFooter() {
?>
</body>
</html>
<?php
}

function printPersonAuswahl()
{
    printHeader('WAWI Benutzerrechte');
    $personFldVal = '';
    if (isset($_POST['personFld']))
    {
        $personFldVal = $_POST['personFld'];
    } elseif (isset($_POST['uid'])) {
        $uid = $_POST['uid'];
        $name = new benutzer();
        $name->load($uid);
        $personFldVal = $name->nachname." ".$name->vorname." (".$uid.")";
    }

?>
    <form action="<?php $_SERVER['PHP_SELF']?>" method="post" id="personAuswahl">
    Person: <input type="text" id="personFld" name="personFld" value="<?php echo $personFldVal; ?>" size="40" />
    <input type='hidden' name='person_uid' id='person_uid' value="<?php echo isset($_POST['person_uid'])?$_POST['person_uid']:'' ?>" />
    </form>
    <div id="wrapper">
    </div>
<?php
    printFooter();
}

function printErrorPermissionDenied()
{
    printHeader('Fehler');
    echo "Sie haben keine Berechtigung zum Bearbeiten von Benutzerrechten";
    printFooter();
}

/*
function printPersonBerechtigungen($rollenIds, $freigabeIds)
{
    $wawiRolle = false;
    $freigabeBerechtigung = false;
    if (count($rollenIds) > 0) $wawiRolle = true;
    if (count($freigabeIds) > 0) $freigabeBerechtigung = true;
    header('Content-Type: application/json');
    $data = array('wawiRolle' => $wawiRolle, 'freigabeBerechtigung' => $freigabeBerechtigung);
    echo json_encode($data);
}*/

function printPersonBerechtigungenList($rights, $uid)
{
    // aus benutzerberechtigung_details.php übernommen
    if (!$db = new basis_db())
        die($p->t("global/fehlerBeimOeffnenDerDatenbankverbindung"));
    $rolle_arr = array();
    $berechtigung_arr = array();
    $berechtigung_user_arr = array();
    if (!$b = new berechtigung())
    die($b->errormsg);

    $b->getRollen();
    foreach($b->result as $berechtigung)
    {
        $rolle_arr[] = $berechtigung->rolle_kurzbz;
    }
    sort($rolle_arr);
    $b->getBerechtigungen();
    foreach($b->result as $berechtigung)
    {
        $berechtigung_arr[] = $berechtigung->berechtigung_kurzbz;
        $berechtigung_beschreibung_arr[] = $berechtigung->beschreibung;
    }
    $bn = new benutzerberechtigung();
    $bn->getBerechtigungen($uid);
    foreach($bn->berechtigungen as $berechtigung)
    {
        $berechtigung_user_arr[] = $berechtigung->berechtigung_kurzbz;
    }
    $kostenstelle = new wawi_kostenstelle();
    $kostenstelle->getAll();
    $htmlstr  = "<div id=\"wrapper\">";

    $htmlstr .= "<table id='t1' class='tablesorter' style='width:auto'>\n";
    $htmlstr .= "<thead>\n";
    $htmlstr .= "<tr>
                    <th></th>
                    <th>Rolle</th>
                    <th>Berechtigung</th>
                    <th class='ks_column'>Kostenstelle</th>
                    <th>Neg</th>
                 <!--   <th>Gültig ab</th>
                    <th>Gültig bis</th> -->
                    <th>Anmerkung</th>
                    <th>Info</th>
                    <th></th>
                </tr></thead><tbody>\n";
    foreach($rights->berechtigungen as $b)
    {
        // nur Rolle 'wawi' und Berechtigung 'wawi/freigabe' dürfen bearbeitet werden
        if(! (($b->rolle_kurzbz == 'wawi' && $b->berechtigung_kurzbz == '')
            || ($b->rolle_kurzbz == '' && $b->berechtigung_kurzbz == 'wawi/freigabe')
            || ($b->rolle_kurzbz == 'wawi' && $b->berechtigung_kurzbz == 'wawi/freigabe')) ) continue;

        if(isset($_POST['edit']) && $_POST['benutzerberechtigung_id']==$b->benutzerberechtigung_id)
        {
            $htmlstr .= "   <tr id='".$b->benutzerberechtigung_id."'>\n";
            $htmlstr .= "<form action='".$_SERVER['PHP_SELF']."' method='POST' name='berechtigung".$b->benutzerberechtigung_id."'>\n";
            $htmlstr .= "<input type='hidden' name='benutzerberechtigung_id' value='".$b->benutzerberechtigung_id."'>\n";
            $htmlstr .= "<input type='hidden' name='uid' value='".$b->uid."'>\n";
            $htmlstr .= "<input type='hidden' name='funktion_kurzbz' value='".$b->funktion_kurzbz."'>\n";

            $heute = strtotime(date('Y-m-d'));
            if ($b->ende!='' && strtotime($b->ende)<$heute)
            {
                $status="ampel_rot.png";
                $titel="ccc";
            }
            elseif ($b->start!='' && strtotime($b->start)>$heute)
            {
                $status="ampel_gelb.png";
                $titel="bbb";
            }
            else
            {
                $status="ampel_gruen.png";
                $titel="aaa";
            }
            //Status
            $htmlstr .= "       <td style='text-align: center; vertical-align: middle' name='td_$b->benutzerberechtigung_id'><img title='".$titel."' src='../../../skin/images/".$status."' alt='aktiv'></td>\n";
            //Rolle
            $htmlstr .= "       <td  name='td_$b->benutzerberechtigung_id'><select name='rolle_kurzbz' id='rolle_kurzbz_$b->benutzerberechtigung_id' onchange='markier(\"td_".$b->benutzerberechtigung_id."\"); setnull(\"berechtigung_kurzbz_$b->benutzerberechtigung_id\");' >\n";
            $htmlstr .= "       <option id='aaa' value='' name='' onclick='enable(\"berechtigung_kurzbz_".$b->benutzerberechtigung_id."\");'>&nbsp;</option>\n";
            for ($i = 0; $i < sizeof($rolle_arr); $i++)
            {
                if ($rolle_arr[$i] != 'wawi') continue;
                if ($b->rolle_kurzbz == $rolle_arr[$i])
                {
                    $sel = " selected";
                }
                else
                    $sel = "";
                $htmlstr .= "<option id='".$rolle_arr[$i]."' value='".$rolle_arr[$i]."' ".$sel." onclick='disable(\"berechtigung_kurzbz_".$b->benutzerberechtigung_id."\");' >".$rolle_arr[$i]."</option>";
            }
            $htmlstr .= "       </select></td>\n";

            //Berechtigung
            $htmlstr .= "       <td name='td_$b->benutzerberechtigung_id'><select name='berechtigung_kurzbz' id='berechtigung_kurzbz_$b->benutzerberechtigung_id'  onchange='markier(\"td_".$b->benutzerberechtigung_id."\"); setnull(\"rolle_kurzbz_$b->benutzerberechtigung_id\");'>\n";
            $htmlstr .= "       <option value='' name='' onclick='enable(\"rolle_kurzbz_".$b->benutzerberechtigung_id."\");'>&nbsp;</option>\n";
            for ($i = 0; $i < sizeof($berechtigung_arr); $i++)
            {
                if ($berechtigung_arr[$i] != 'wawi/freigabe') continue;
                if ($b->berechtigung_kurzbz == $berechtigung_arr[$i])
                    $sel = " selected";
                else
                    $sel = "";
                $htmlstr .= "               <option title='".$berechtigung_beschreibung_arr[$i]."' ".(array_search($berechtigung_arr[$i],$berechtigung_user_arr)!==false?"style='color: #666;'":"")." value='".$berechtigung_arr[$i]."' name='".$berechtigung_arr[$i]."' ".$sel." onclick='disable(\"rolle_kurzbz_".$b->benutzerberechtigung_id."\");'>".$berechtigung_arr[$i]."</option>";
            }
            $htmlstr .= "       </select></td>\n";

            //Art
            //$htmlstr .= "       <td name='td_$b->benutzerberechtigung_id'><input id='art_$b->benutzerberechtigung_id' type='text' name='art' value='".$b->art."' size='5' maxlength='5' onChange='validateArt(\"art_$b->benutzerberechtigung_id\"); markier(\"td_".$b->benutzerberechtigung_id."\")'></td>\n";


            //Kostenstelle
            $htmlstr .= "       <td class='ks_column' name='td_$b->benutzerberechtigung_id'><select id='kostenstelle_".$b->benutzerberechtigung_id."'name='kostenstelle_id' ".($b->oe_kurzbz!=''?'disabled':'')." onchange='markier(\"td_".$b->benutzerberechtigung_id."\")' style='width: 200px;'>\n";
            $htmlstr .= "       <option value='' onclick='enable(\"oe_".$b->benutzerberechtigung_id."\");'>&nbsp;</option>\n";

            foreach ($kostenstelle->result as $kst)
            {
                if ($b->kostenstelle_id == $kst->kostenstelle_id)
                    $sel = " selected";
                else
                    $sel = "";
                if(!$kst->aktiv)
                    $class='class="inactive"';
                else
                    $class='';

                $htmlstr .= "   <option value='".$kst->kostenstelle_id."' ".$sel." ".$class." onclick='disable(\"oe_".$b->benutzerberechtigung_id."\");'>".$kst->bezeichnung.'</option>';
            }
            $htmlstr .= "       </select></td>\n";

            $htmlstr .= "       <td align='center' name='td_$b->benutzerberechtigung_id'><input type='checkbox' name='negativ' ".($b->negativ?'checked="checked"':'')." onchange='markier(\"td_".$b->benutzerberechtigung_id."\")'></td>\n";
            /*
            $htmlstr .= "       <td nowrap name='td_$b->benutzerberechtigung_id'><input class='datepicker_datum' type='text' name='start' value='".$b->start."' size='10' maxlength='10' onchange='markier(\"td_".$b->benutzerberechtigung_id."\")'></td>\n";
            $htmlstr .= "       <td nowrap name='td_$b->benutzerberechtigung_id'><input class='datepicker_datum' type='text' name='ende' value='".$b->ende."' size='10' maxlength='10' onchange='markier(\"td_".$b->benutzerberechtigung_id."\")'></td>\n";*/
            $htmlstr .= "       <td name='td_$b->benutzerberechtigung_id'><input id='anmerkung_$b->benutzerberechtigung_id' type='text' name='anmerkung' value='".$b->anmerkung."' title='".$db->convert_html_chars(mb_eregi_replace('\r\n'," ",$b->anmerkung))."' size='30' maxlength='256' markier(\"td_".$b->benutzerberechtigung_id."\")'></td>\n";
            $htmlstr .= "       <td align='center' name='td_$b->benutzerberechtigung_id'><img src='../../../skin/images/information.png' alt='information' title='Angelegt von ".$b->insertvon." am ".$b->insertamum." \nZuletzt geaendert von ".$b->updatevon." am ".$b->updateamum."'></td>\n";

            $htmlstr .= "       <td name='td_$b->benutzerberechtigung_id'><input type='submit' name='schick' value='speichern' style=\"width:110px\"> <input type='submit' name='del' value='l&ouml;schen'></td>";
            $htmlstr .= "</form>\n";
            $htmlstr .= "   </tr>\n";
        }
        else
        {
            $htmlstr .= "   <tr id='".$b->benutzerberechtigung_id."'>\n";
            $htmlstr .= "<form action='".$_SERVER['PHP_SELF']."' method='POST' name='berechtigung".$b->benutzerberechtigung_id."'>\n";
            $htmlstr .= "<input type='hidden' name='benutzerberechtigung_id' value='".$b->benutzerberechtigung_id."'>\n";
            $htmlstr .= "<input type='hidden' name='uid' value='".$b->uid."'>\n";
            $htmlstr .= "<input type='hidden' name='funktion_kurzbz' value='".$b->funktion_kurzbz."'>\n";

            $heute = strtotime(date('Y-m-d'));
            if ($b->ende!='' && strtotime($b->ende)<$heute)
            {
                $status="ampel_rot.png";
                $titel="ccc";
            }
            elseif ($b->start!='' && strtotime($b->start)>$heute)
            {
                $status="ampel_gelb.png";
                $titel="bbb";
            }
            else
            {
                $status="ampel_gruen.png";
                $titel="aaa";
            }
            //Status
            $htmlstr .= "       <td style='text-align: center; vertical-align: middle' name='td_$b->benutzerberechtigung_id'><img title='".$titel."' src='../../../skin/images/".$status."' alt='aktiv'></td>\n";
            //Rolle
            $htmlstr .= "       <td style='padding: 1px;' name='td_$b->benutzerberechtigung_id'>$b->rolle_kurzbz</td>\n";

            //Berechtigung
            $htmlstr .= "       <td name='td_$b->benutzerberechtigung_id'>$b->berechtigung_kurzbz</td>\n";

            //Art
           // $htmlstr .= "       <td name='td_$b->benutzerberechtigung_id'>".$b->art."</td>\n";


            //Kostenstelle
            $kst = new wawi_kostenstelle();
            $kst->load($b->kostenstelle_id);
            if(!$kst->aktiv)
                $style='style="text-decoration:line-through;"';
            else
                $style='';
            $htmlstr .= "       <td class='ks_column' name='td_$b->benutzerberechtigung_id'><span $style>$kst->bezeichnung</span></td>\n";


            $htmlstr .= "       <td align='center' name='td_$b->benutzerberechtigung_id'><input type='checkbox' name='negativ' ".($b->negativ?'checked="checked"':'')." onchange='markier(\"td_".$b->benutzerberechtigung_id."\")' disabled></td>\n";
            //$htmlstr .= "       <td nowrap name='td_$b->benutzerberechtigung_id'>".$b->start."</td>\n";
            //$htmlstr .= "       <td nowrap name='td_$b->benutzerberechtigung_id'>".$b->ende."</td>\n";
            $htmlstr .= "       <td name='td_$b->benutzerberechtigung_id'>".$b->anmerkung."</td>\n";
            $htmlstr .= "       <td align='center' name='td_$b->benutzerberechtigung_id'><img src='../../../skin/images/information.png' alt='information' title='Angelegt von ".$b->insertvon." am ".$b->insertamum." \nZuletzt geaendert von ".$b->updatevon." am ".$b->updateamum."'></td>\n";

            $htmlstr .= "       <td name='td_$b->benutzerberechtigung_id'><input type='submit' name='edit' value='bearbeiten' style=\"width:110px\"> <input type='submit' name='del' value='l&ouml;schen'></td>";
            $htmlstr .= "</form>\n";
            $htmlstr .= "   </tr>\n";
        }


    }

    $htmlstr .= "   </tbody><tfoot>\n";
    $htmlstr .= "   <tr id='neu'>\n";
    $htmlstr .= "       <form action='".$_SERVER['PHP_SELF']."' method='POST' name='berechtigung_neu' id='berechtigungNeu' >\n";
    $htmlstr .= "       <input type='hidden' name='neu' value='1'>\n";
    $htmlstr .= "       <input type='hidden' name='benutzerberechtigung_id' value=''>\n";
    $htmlstr .= "       <input type='hidden' name='uid' value='".$uid."'>\n";
    //$htmlstr .= "<input type='hidden' name='funktion_kurzbz' value='".$funktion_kurzbz."'>\n";

    //Status
    $htmlstr .= "       <td>&nbsp;</td>\n";
    //Rolle
    $htmlstr .= "       <td style='padding-top: 15px;'><select name='rolle_kurzbz' id='rolle_kurzbz_neu' onchange='markier(\"neu\"); setnull(\"berechtigung_kurzbz_neu\"); '>\n";
    $htmlstr .= "           <option value='' onclick='enable(\"berechtigung_kurzbz_neu\");'>&nbsp;</option>\n";
    for ($i = 0; $i < sizeof($rolle_arr); $i++)
    {
        if ($rolle_arr[$i] != 'wawi') continue;
        $sel = "";
        $htmlstr .= "               <option value='".$rolle_arr[$i]."' ".$sel." onclick='disable(\"berechtigung_kurzbz_neu\");'>".$rolle_arr[$i]."</option>";
    }
    $htmlstr .= "       </select></td>\n";

    //Berechtigung_kurzbz
    $htmlstr .= "       <td style='padding-top: 15px;'><select name='berechtigung_kurzbz' id='berechtigung_kurzbz_neu' onchange='markier(\"neu\"); setnull(\"rolle_kurzbz_neu\");'>\n";
    $htmlstr .= "           <option value='' onclick='enable(\"rolle_kurzbz_neu\");'>&nbsp;</option>\n";
    for ($i = 0; $i < sizeof($berechtigung_arr); $i++)
    {
        if ($berechtigung_arr[$i] != 'wawi/freigabe') continue;
        $sel = "";
        $htmlstr .= "               <option title='".$berechtigung_beschreibung_arr[$i]."' ".(array_search($berechtigung_arr[$i],$berechtigung_user_arr)!==false?"style='color: #666'":"")." value='".$berechtigung_arr[$i]."' ".$sel." onclick='disable(\"rolle_kurzbz_neu\");'>".$berechtigung_arr[$i]."</option>";
    }
    $htmlstr .= "       </select></td>\n";

    //Art
    //$htmlstr .= "       <td style='padding-top: 15px;'><input id='art_neu' type='text' name='art' value='' size='5' maxlength='5' onBlur='checkrequired(\"art_neu\")' onChange='validateArt(\"art_neu\")' placeholder='suid'></td>\n";

    //Kostenstelle
    $htmlstr .= "       <td class='ks_column' style='padding-top: 15px;'><select id='kostenstelle_neu' name='kostenstelle_id' onchange='markier(\"".(isset($b->benutzerberechtigung_id)?$b->benutzerberechtigung_id:'')."\")' style='width: 200px;'>\n";
    $htmlstr .= "           <option value='' onclick='enable(\"oe_kurzbz_neu\");'>&nbsp;</option>\n";

    foreach ($kostenstelle->result as $kst)
    {
        if(!$kst->aktiv)
            $class='class="inactive"';
        else
            $class='';

        $htmlstr .= "       <option value='".$kst->kostenstelle_id."' ".$class." onclick='disable(\"oe_kurzbz_neu\");'>".$kst->bezeichnung.'</option>';
    }
    $htmlstr .= "       </select></td>\n";

    $htmlstr .= "       <td align='center' style='padding-top: 15px;'><input type='checkbox' name='negativ' onchange='markier(\"neu\")'></td>\n";
    /*
    $htmlstr .= "       <td nowrap style='padding-top: 15px;'><input class='datepicker_datum' type='text' name='start' value='' size='10' maxlength='10' onchange='markier(\"neu\")'></td>\n";
    $htmlstr .= "       <td nowrap style='padding-top: 15px;'><input class='datepicker_datum' type='text' name='ende' value='' size='10' maxlength='10' onchange='markier(\"neu\")'></td>\n";
   */
    //Anmerkung
    $htmlstr .= "       <td style='padding-top: 15px;'><input id='anmerkung_neu' type='text' name='anmerkung' value='' size='30' maxlength='256' onchange='markier(\"neu\")'></td>\n";

    $htmlstr .= "       <td style='padding-top: 15px;'><input type='submit' name='schick' value='neu'></td>\n";
    //$htmlstr .= "       <input type='submit' name='schick' value='neu'>\n";
    $htmlstr .= "       </form>\n";
    $htmlstr .= "   </tr>\n";


    $htmlstr .= "</tfoot></table>\n";


    $htmlstr .= "<script>";
  /*  $htmlstr .= '$( ".datepicker_datum" ).datepicker({
                     changeMonth: true,
                     changeYear: true,
                     dateFormat: "yy-mm-dd",
                     showOn: "button",
                     buttonImage: "../../../skin/images/date_edit.png",
                     buttonImageOnly: true,
                     buttonText: "Select date"
                     });';
    $htmlstr  .= "$('#berechtigungNeu').submit(function(event) {
                    // get form data
                    var formData = {
                        'neu'                       : $('input[name=neu]').val(),
                        'benutzerberechtigung_id'   : $('input[name=benutzerberechtigung_id]').val(),
                        'uid'                       : $('input[name=uid]').val(),
                        'rolle_kurzbz'              : $('input[name=rolle_kurzbz]').val(),
                        'berechtigung_kurzbz'       : $('input[name=berechtigung_kurzbz]').val(),
                        'kostenstelle_id'           : $('input[name=kostenstelle_id]').val(),
                        'negativ'                   : $('input[name=negativ]').val(),
                        'start'                     : $('input[name=start]').val(),
                        'ende'                      : $('input[name=ende]').val (),
                        'anmerkung'                 : $('input[name=anmerkung]').val(),
                        'schick'                    : 'neu'
                    };

                    alert('submit');
                    // process the form
                    $.ajax({
                        type        : 'POST',
                        url         : 'benutzeradmin.php',
                        data        : formData,
                        dataType    : 'json',
                        encode      : true
                    })
                    .done(function(data) {
                        // log data to the console so we can see
                        console.log(data);
                    });

                    event.preventDefault();

                  });
                 ";  */
    $htmlstr .= "</script>";

    $htmlstr .= "</div>";


    echo $htmlstr;

}

function createOrUpdateBerechtigung()
{
    global $user;
    $benutzerberechtigung_id = $_POST['benutzerberechtigung_id'];
    $art = 'suid';
    $oe_kurzbz = '';
    $berechtigung_kurzbz = (isset($_POST['berechtigung_kurzbz'])?$_POST['berechtigung_kurzbz']:'');
    $rolle_kurzbz = (isset($_POST['rolle_kurzbz'])?$_POST['rolle_kurzbz']:'');
    $uid = $_POST['uid'];
    $studiensemester_kurzbz = null;//$_POST['studiensemester_kurzbz'];
    $start = (isset($_POST['start'])?$_POST['start']:'');
    $ende = (isset($_POST['ende'])?$_POST['ende']:'');
    $kostenstelle_id = (isset($_POST['kostenstelle_id'])?$_POST['kostenstelle_id']:'');
    $anmerkung = (isset($_POST['anmerkung'])?$_POST['anmerkung']:'');

    if (($rolle_kurzbz != '' && $rolle_kurzbz != 'wawi')
        || ($berechtigung_kurzbz != '' && $berechtigung_kurzbz != 'wawi/freigabe'))
    {
        return 'Diese Berechtigung kann nicht bearbeitet werden';
    }

    $ber = new benutzerberechtigung();
    if (isset($_POST['neu']))
    {
        $ber->insertamum=date('Y-m-d H:i:s');
        $ber->insertvon = $user;
        $ber->new = true;
    }
    else
    {
        if(!$ber->load($benutzerberechtigung_id))
            die('Fehler beim Laden der Berechtigung');
    }
    if (isset($_POST['negativ']))
        $ber->negativ = true;
    else
        $ber->negativ = false;

    $ber->benutzerberechtigung_id = $benutzerberechtigung_id;
    $ber->art = $art;
    $ber->oe_kurzbz = $oe_kurzbz;
    $ber->berechtigung_kurzbz = $berechtigung_kurzbz;
    $ber->rolle_kurzbz = $rolle_kurzbz;
    $ber->uid = $uid;
    $ber->studiensemester_kurzbz = $studiensemester_kurzbz;
    $ber->start = $start;
    $ber->ende = $ende;
    $ber->updateamum = date('Y-m-d H:i:s');
    $ber->updatevon = $user;
    $ber->kostenstelle_id = $kostenstelle_id;
    $ber->anmerkung = $anmerkung;

    if(!$ber->save()){
        if (!$ber->new)
            return "Datensatz konnte nicht upgedatet werden!".$ber->errormsg;
        else
            return "Datensatz konnte nicht gespeichert werden!".$ber->errormsg;
    }


}

function deleteBerechtigung()
{
    global $user;
    $benutzerberechtigung_id = $_POST['benutzerberechtigung_id'];

    $ber = new benutzerberechtigung();
    if(!$ber->delete($benutzerberechtigung_id))
        return 'Datensatz konnte nicht gel&ouml;scht werden!';

}

function control()
{
    global $user;

    $rechte = new benutzerberechtigung();
    $rechte->getBerechtigungen($user);
    if(!$rechte->isBerechtigt('wawi/bestellung_advanced'))
    {
        printErrorPermissionDenied();
    } elseif (isset($_POST['edit']) && isset($_POST['uid']) && isset($_POST['benutzerberechtigung_id'])) {
        printPersonAuswahl();

        $uid = $_POST['uid'];
        $rechte->loadBenutzerRollen($uid);
        printPersonBerechtigungenList($rechte, $uid);
    } elseif (isset($_POST['del']) && isset($_POST['uid']) && isset($_POST['benutzerberechtigung_id'])) {
        printPersonAuswahl();

        // Berechtigung löschen
        $err = deleteBerechtigung();
        if ($err != '')
        {
            echo '<div style="background:red; color: white; padding: 5px; margin: 5px 0px">'.$err.'</div>';
        } else {
            echo '<div style="background:green; color: white; padding: 5px; margin: 5px 0px">Berechtigung gelöscht.</div>';
        }
        $uid = $_POST['uid'];
        $rechte->loadBenutzerRollen($uid);
        printPersonBerechtigungenList($rechte, $uid);
    } elseif (isset($_POST['schick']) && isset($_POST['uid'])) {
        printPersonAuswahl();

        // Berechtigung aktualisieren oder hinzufügen
        $err = createOrUpdateBerechtigung();

        if ($err != '')
        {
            echo '<div style="background:red; color: white; padding: 5px; margin: 5px 0px">'.$err.'</div>';
        } else {
            echo '<div style="background:green; color: white; padding: 5px; margin: 5px 0px">Berechtigung '.($_POST['schick'] == 'neu'?'hinzugefügt':'gespeichert').'.</div>';
        }
        $uid = $_POST['uid'];
        $rechte->loadBenutzerRollen($uid);
        printPersonBerechtigungenList($rechte, $uid);

    } elseif (isset($_POST['person_uid'])) {
        printPersonAuswahl();
        // Berechtigungen anzeigen (nach Auswahl der Person via AJAX)
        $uid = $_POST['person_uid'];
        $rechte->loadBenutzerRollen($uid);
        printPersonBerechtigungenList($rechte, $uid);

        /*
        $rollen = $rechte->berechtigungen;
        $rollenIds = array_filter($rollen, function($item) {
            return $item->rolle_kurzbz == 'wawi';
        });*/
        // hat WAWI-Freigabe-Berechtigung

        // sonstige Berechtigungen
        //printPersonBerechtigungen($rollenIds);

    } elseif (isset($_POST['person_uid']) && isset($_POST['person_update'])) {

    } else {
        printPersonAuswahl();
    }
}


control();
