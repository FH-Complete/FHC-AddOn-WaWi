<?php

require_once($basepath.'/include/functions.inc.php');
require_once('wawi_kostenstelle.class.php');
require_once $basepath.'/include/organisationseinheit.class.php';
require_once($basepath.'/include/phrasen.class.php');
require_once($basepath.'/include/sprache.class.php');



function addScheme($url, $scheme = 'http://')
{
  return parse_url($url, PHP_URL_SCHEME) === null ?
    $scheme . $url : $url;
}

function personFormat($titelpre,$vorname,$nachname,$titelpost) 
{
    $e = array($titelpre,$vorname,$nachname,$titelpost);
    return join(' ',$e);
}

function isGMBHKostenstelle($kostenstelle_id) {
    $kostenstelle = new wawi_kostenstelle(); 
    $kostenstelle->load($kostenstelle_id);
    if ($kostenstelle->oe_kurzbz == 'gmbh') return true;    
    $oe = new organisationseinheit();
    $parents = $oe->getParents($oe->oe_kurzbz);
    foreach ($parents as $oeRow) 
    {
        if ($oeRow->oe_kurzbz == 'gmbh') return true;
    }
    return false;
}

class BestellungPDFConverter {

    private $tempfolder = null;
    private $tempPdfName = null;
    private $tempname_zip =  null;
    
    /**
     * 
     * @param object $input   Quelldaten für PDF-File
     * @param String $odt     ODT-Template
     * @param String $contentTemplate PHP-File das content.xml generiert und im 
     * ODT-Template eingebaut wird
     * @param String $styleTemplate PHP-File das styles.xml generiert
     * @return Pfad zum generierten PDF oder FALSE
     */
    public function convert2pdf($input, $odt, $contentTemplate, $english=false, $styleTemplate=null, $logoGmbh=null)
    {
        // aktuell eingestellte Sprache
        $sprache = getSprache(); 
        $lang = new sprache(); 
        if ($english) 
        {
            // override Sprache, damit Bestellschein in Englisch erstellt wird
            $sprache = 'English';
            $p = new phrasen($sprache);     
        }         
        $lang->load($sprache);
        $p = new phrasen($sprache);     

        $this->tempfolder = '/tmp/'.uniqid();
        $this->tempname_zip = 'out.zip';
        if (!mkdir($this->tempfolder)) 
        {
            throw new RuntimeException($this->tempfolder.' konnte nicht erstellt werden');
        }
        chdir($this->tempfolder);

        ob_start();
        require $contentTemplate;
        file_put_contents('content.xml', ob_get_contents());
        ob_end_clean();

        if ($styleTemplate != null)
        {
            ob_start();
            require $styleTemplate;
            file_put_contents('styles.xml', ob_get_contents());
            ob_end_clean();
        } 
        else 
        {
            return false;
        }



        if(copy($odt, $this->tempname_zip))
        {            

            // Gmbh-Logo einbauen (default ist FH)
            if ($logoGmbh != null) {
                mkdir('Pictures');                
                copy($logoGmbh, 'Pictures'.DIRECTORY_SEPARATOR.'100002010000012C000000A0E47DB5C13E40A7E8.png');
                exec("zip -r ".$this->tempname_zip." Pictures");
            }

            exec("zip -r ".$this->tempname_zip." content.xml");
            if ($styleTemplate != null)
            {
                exec("zip -r ".$this->tempname_zip." styles.xml");
            }

            $this->tempPdfName = 'Bestellschein.pdf';                        
            //echo "unoconv -e Watermark=muster --stdout -f pdf ".$this->tempname_zip." > ".$this->tempPdfName;
            exec("unoconv -e Watermark=demo --server 127.0.0.1 --port 2002 --stdout -f pdf ".$this->tempname_zip." > ".$this->tempPdfName);

            return $this->tempfolder.DIRECTORY_SEPARATOR.$this->tempPdfName;
        } 
        return false;


    }
    
    /**
     * Cleanup
     * @TODO Cleanup wenn Exception geworfen wird
     */
    
    function __destruct() {
        if ($this->tempfolder != null) {
            unlink($this->tempfolder.DIRECTORY_SEPARATOR.'content.xml');
            unlink($this->tempfolder.DIRECTORY_SEPARATOR.'styles.xml');
            unlink($this->tempfolder.DIRECTORY_SEPARATOR.$this->tempPdfName);
            unlink($this->tempfolder.DIRECTORY_SEPARATOR.$this->tempname_zip);
            rmdir($this->tempfolder);
        }
    }


}
