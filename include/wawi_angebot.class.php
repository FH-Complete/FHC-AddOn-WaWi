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

require_once($basepath.'/include/basis_db.class.php');

class wawi_angebot extends basis_db
{
	public $new;					//  boolean
	public $result = array();		//  Konto Objekt
	public $user; 					//  string

	//Tabellenspalten
	public $angebot_id;				//  integer
	public $bestellung_id ;                          //  integer
	public $dms_id;					//  integer
	public $filename;  // string
	/**
	 * Konstruktor
	 * @param $konto_id ID des Kontos das geladen werden soll (Default=null)
	 */
	public function __construct($angebot_id=null)
	{
		parent::__construct();
		
		if(!is_null($angebot_id))
			$this->load($angebot_id);
	}

	/**
	 * Laedt das Angebot mit der ID $angebot_id
	 * @param  $angebot_id ID
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function load($angebot_id)
	{

	  if( !is_numeric($angebot_id) || $angebot_id == '')
		{
			$this->errormsg = 'angebot_id muss eine Zahl sein';
			return false;
		}
                $qry = "SELECT * FROM wawi.tbl_bestellung_angebot WHERE angebot_id =".$this->db_add_param($angebot_id, FHC_INTEGER).';';
		 
		if($this->db_query($qry))
		{
                    if($row = $this->db_fetch_object())
                    {
                            $this->angebot_id	= $row->angebot_id;
                            $this->bestellung_id = $row->bestellung_id;
                            $this->dms_id = $row->dms_id;
                    }
                    else
                    {
                            $this->errormsg = 'Es ist kein Datensatz mit dieser ID vorhanden';
                            return false;
                    }
                }

		return true;
	}

	/**
	 * Laedt alle Angebote zu einer Bestellung. Derzeit wird keine Versionierung der
         * Angebote unterstützt.
	 * @param $bestellung_id integer
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function getAll($bestellung_id)
	{
		if(!is_numeric($bestellung_id))
	    {
	      $this->errormsg = "Bestellung_id ist keine Zahl.";
	      return false;
	    }	

		$qry = "SELECT a.*,dok.* FROM wawi.tbl_bestellung_angebot as a join campus.tbl_dms as dms using(dms_id) join ".
		  "campus.tbl_dms_version as dok using(dms_id) where dok.version=0 and ".
		  "a.bestellung_id=".$this->db_add_param($bestellung_id)." ".
		  "order by dok.name" ;

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		while($row = $this->db_fetch_object())
		{
			$obj = new wawi_angebot();
			
			$obj->angebot_id	= $row->angebot_id;
			$obj->bestellung_id = $row->bestellung_id;
			$obj->dms_id = $row->dms_id;
			$obj->filename = $row->name;
			
			$this->result[] = $obj;
		}
		
		return true;
	}
	
	/**
	 * Prueft die Variablen auf Gueltigkeit
	 * @return true wenn ok, false im Fehlerfall
	 */
	protected function validate()
	{
				
 	  if(!is_numeric($this->bestellung_id))
	    {
	      $this->errormsg = "Bestellung_id fehlerhaft.";
	      return false;
	    }				
	  $this->errormsg = '';
	  if(!$this->new && !is_numeric($this->angebot_id))
	    {
	      $this->errormsg = "angebot_id fehlerhaft.";
	      return false;
	    }				
	  $this->errormsg = '';
	  if(!is_numeric($this->dms_id))
	    {
	      $this->errormsg = "dms_id fehlerhaft.";
	      return false;
	    }				
	  $this->errormsg = '';

	  return true;
	}

	

	public function save()	  
	{
		if(!$this->validate())
			return false;
		
		if($this->new)
		{
			$qry = 'BEGIN; INSERT INTO wawi.tbl_bestellung_angebot (bestellung_id,dms_id) VALUES ('.
			$this->db_add_param($this->bestellung_id, FHC_INTEGER).', '.
			$this->db_add_param($this->dms_id, FHC_INTEGER).')';
		}
		else
		{
			//UPDATE
			$qry = 'UPDATE wawi.tbl_bestellung_angebot SET 
			bestellung_id = '.$this->db_add_param($this->bestellung_id).', 
			dms_id = '.$this->db_add_param($this->dms_id).', 
			WHERE angebot_id = '.$this->db_add_param($this->angebot_id, FHC_INTEGER).';'; 
		}
		
		if($this->db_query($qry))
		{
			if($this->new)
			{
				//aktuelle Sequence holen
				$qry="SELECT currval('wawi.tbl_bestellung_angebot_seq') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->angebot_id = $row->id;
						$this->db_query('COMMIT;');
					}
					else
					{
						$this->db_query('ROLLBACK;');
						$this->errormsg = "Fehler beim Auslesen der Sequence";
						return false;
					}
				}
				else
				{
					$this->db_query('ROLLBACK;');
					$this->errormsg = 'Fehler beim Auslesen der Sequence';
					return false;
				}
			}
		}
		else
		{
			return false;
		}
		return $this->angebot_id;
	}
	
}
?>
