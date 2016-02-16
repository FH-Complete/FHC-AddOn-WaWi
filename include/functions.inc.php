<?php

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
    public function convert2pdf($input, $odt, $contentTemplate, $styleTemplate=null)
    {
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
