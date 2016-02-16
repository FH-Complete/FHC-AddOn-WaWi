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

$basepath = $_SERVER['DOCUMENT_ROOT'];
require_once($basepath.'/config/vilesci.config.inc.php');
require_once($basepath.'/include/datum.class.php');
require_once($basepath.'/include/basis_db.class.php');
require_once('../include/wawi_bestellung.class.php');
require_once('../include/wawi_bestelldetail.class.php');
require_once($basepath.'/include/benutzer.class.php');
require_once('../include/wawi_konto.class.php');
require_once('../include/wawi_kostenstelle.class.php');
require_once('../include/wawi_kategorie.class.php');
require_once('../include/wawi_zuordnung.class.php');
require_once($basepath.'/include/nation.class.php');
require_once($basepath.'/include/adresse.class.php');
require_once($basepath.'/include/firma.class.php');
require_once($basepath.'/include/standort.class.php');
require_once($basepath.'/include/kontakt.class.php');
require_once($basepath.'/include/studiengang.class.php'); 


function bestellungReadModel($id=null)
{
	
	$bestellung = new wawi_bestellung();
	if($id != null)
	{
		if(!$bestellung->load($id)) {
                    throw new RuntimeException('Bestellung wurde nicht gefunden', 1);                    
                }			
			
		$besteller = new benutzer();
		if(!$besteller->load($bestellung->besteller_uid))
                    throw new RuntimeException('Besteller konnte nicht geladen werden', 1);   
		
		$konto = new wawi_konto();
		$konto->load($bestellung->konto_id);
		
		$kostenstelle = new wawi_kostenstelle();
		$kostenstelle->load($bestellung->kostenstelle_id);
		
		$rechnungsadresse = new adresse();
		$rechnungsadresse->load($bestellung->rechnungsadresse);   
                
                // für Steuernummer
                $firma_rechnung = new firma();
                $firma_rechnung->load($rechnungsadresse->firma_id);
		
		$lieferadresse = new adresse();
		$lieferadresse->load($bestellung->lieferadresse);
		
		$aufteilung = new wawi_aufteilung(); 
		$aufteilung->getAufteilungFromBestellung($bestellung->bestellung_id); 
		
		$studiengang = new studiengang(); 
                
                $kategorie='';
                if (isset($bestellung->bkategorie_id))
                {
                    $wawi_kategorie = new wawi_bestellung_kategorie();
                    $wawi_kategorie->load($bestellung->bkategorie_id);
                    $kategorie=$wawi_kategorie->beschreibung;
                }
                
                $zuordnung_person = null;
                if (isset($bestellung->zuordnung_uid) && $bestellung->zuordnung_uid != '')
                {
                    $zuordnung_person = new benutzer();
                    if(!$zuordnung_person->load($bestellung->zuordnung_uid))
                        throw new RuntimeException('Zugeordnete Person konnte nicht geladen werden', 1);  
                }
                
                
		$firma = new firma();
		$standort = new standort();
		$empfaengeradresse = new adresse();
		if($bestellung->firma_id!='')
		{
			$firma->load($bestellung->firma_id);
			$kundennummer = $firma->get_kundennummer($bestellung->firma_id, $kostenstelle->oe_kurzbz);
			
			$standort->load_firma($firma->firma_id);
			if(isset($standort->result[0]))
				$standort = $standort->result[0];
					
			$empfaengeradresse->load($standort->adresse_id);
			$kontakt = new kontakt();
			$kontakt->loadFirmaKontakttyp($standort->standort_id, 'telefon');
			$telefon = $kontakt->kontakt;
			$kontakt = new kontakt();
			$kontakt->loadFirmaKontakttyp($standort->standort_id, 'fax');
			$fax = $kontakt->kontakt;
                        $kontakt = new kontakt();
                        $kontakt->loadFirmaKontakttyp($standort->standort_id, 'email');
			$email = $kontakt->kontakt;
                        $kontakt = new kontakt();
                        $kontakt->loadFirmaKontakttyp($standort->standort_id, 'homepage');
			$homepage = $kontakt->kontakt;
                        
                        $nation = new nation();
                        $nation->load($empfaengeradresse->nation);
                        $nation_code=$empfaengeradresse->nation;
                        if ($nation !== false)
                        {
                            $isEU=$nation->eu;
                        }
                        else
                        {
                            $isEU=true;                        
                        }
		}
		else
		{
			$telefon='';
			$fax='';
			$kundennummer='';
                        $email='';
                        $url=''; 
                        $nation_code='';
                        $isEU=true;
                        $kategorie='';
		}
		$datum_obj = new datum();
				
                $model = new stdClass();
		$model->bestell_nr = $bestellung->bestell_nr;
		$model->titel = $bestellung->titel;
		$model->liefertermin = $bestellung->liefertermin;
		$model->kundennummer = $kundennummer;
                $model->konto = $konto->kurzbz;
                $model->kostenstelle = $kostenstelle->bezeichnung;
                $model->kategorie = $kategorie;
                if ($bestellung->zuordnung != null && $bestellung->zuordnung != '')
                {
                    $model->zuordnung = wawi_zuordnung::getLabel($bestellung->zuordnung);
                }
                else 
                {
                    $model->zuordnung = null;
                }
                $model->zuordnung_raum = $bestellung->zuordnung_raum;
                
                // Zuordnung Person
                if ($zuordnung_person != null)
                {
                    $model->zuordnung_person = new stdClass();
                    $model->zuordnung_person->titelpre = $zuordnung_person->titelpre;
                    $model->zuordnung_person->vorname = $zuordnung_person->vorname;
                    $model->zuordnung_person->nachname = $zuordnung_person->nachname;
                    $model->zuordnung_person->titelpost = $zuordnung_person->titelpost;
                    $model->zuordnung_person->email = $zuordnung_person->uid.'@'.DOMAIN;
                }
                else
                {
                    $model->zuordnung_person = null;
                }
                // Besteller
		$model->kontaktperson = new stdClass();
		$model->kontaktperson->titelpre = $besteller->titelpre;
		$model->kontaktperson->vorname = $besteller->vorname;
		$model->kontaktperson->nachname = $besteller->nachname;
		$model->kontaktperson->titelpost = $besteller->titelpost;
		$model->kontaktperson->email = $besteller->uid.'@'.DOMAIN;		
                // Rechnungsadresse
		$model->rechnungsadresse = new stdClass();                
                //$model->rechnungsadresse->kurzbz = $rechnungsadresse->kurzbz;
                $model->rechnungsadresse->steuernummer = $firma_rechnung->steuernummer;
		$model->rechnungsadresse->name = $rechnungsadresse->name;
		$model->rechnungsadresse->strasse = $rechnungsadresse->strasse;
		$model->rechnungsadresse->plz = $rechnungsadresse->plz;
		$model->rechnungsadresse->ort = $rechnungsadresse->ort;
		// Lieferadresse
		$model->lieferadresse = new stdClass();
		$model->lieferadresse->name = $lieferadresse->name;
		$model->lieferadresse->strasse = $lieferadresse->strasse;
		$model->lieferadresse->plz = $lieferadresse->plz;
		$model->lieferadresse->ort = $lieferadresse->ort;
                // Lieferant
		$model->lieferant = new stdClass();
		$model->lieferant->name = $firma->name;
                $model->lieferant->steuernummer = $firma->steuernummer;
		$model->lieferant->strasse = $empfaengeradresse->strasse;
		$model->lieferant->plz = $empfaengeradresse->plz;
		$model->lieferant->ort = $empfaengeradresse->ort;
		$model->lieferant->telefon = $telefon;
		$model->lieferant->fax = $fax;	
                $model->lieferant->email = $email;
                $model->lieferant->homepage = $homepage;
                $model->lieferant->nation = $nation_code;
                $model->lieferant->eu = $isEU;
				
		$details = new wawi_bestelldetail();
		$details->getAllDetailsFromBestellung($bestellung->bestellung_id);
		$summe_netto=0;
		$summe_brutto=0;
		$summe_mwst=0;
		
		$i=0;
		$pagebreakposition=30;
		$pagebreak=false;
		$model->details = array();
		foreach($details->result as $row)
		{
                        $tempDetail = new stdClass();			
			$tempDetail->position = $row->position;
			$tempDetail->menge = $row->menge;
			$tempDetail->verpackungseinheit = $row->verpackungseinheit;
			$tempDetail->beschreibung = $row->beschreibung;
			$tempDetail->artikelnummer = $row->artikelnummer;
			$tempDetail->preisprove = number_format($row->preisprove,2,',','.');
			$tempDetail->mwst = number_format($row->mwst,2,',','.');
			$summe_brutto_detail=$row->menge*$row->preisprove/100*($row->mwst+100);
			$summe_netto_detail=$row->menge*$row->preisprove;
			$tempDetail->summe_brutto = number_format($summe_brutto_detail,2,',','.');
			$tempDetail->summe_netto = number_format($summe_netto_detail,2,',','.');
			$model->details[] = $tempDetail;
			$summe_brutto+=$summe_brutto_detail;
			$summe_netto+=$row->menge*$row->preisprove;
			$summe_mwst+=$row->menge*$row->preisprove/100*$row->mwst;
			$i++;
		}
		$model->datum = date('d.m.Y');
		$model->erstelldatum = $datum_obj->formatDatum($bestellung->insertamum, 'd.m.Y');
		$model->summe_netto = number_format($summe_netto,2,',','.');
		$model->summe_mwst = number_format($summe_mwst,2,',','.');
		$model->summe_brutto = number_format($summe_brutto,2,',','.');
                return $model;
	}
	else
        {
		return false;
        }
}