<?php
namespace mangelp\downloadHelper;

/**
 * Download helper coded following the next samples and repositories:
 *  + http://www.media-division.com/the-right-way-to-handle-file-downloads-in-php/
 *  + https://github.com/TimOliver/PHP-Framework-Classes/blob/master/download.class.php
 *  + https://github.com/pomle/php-serveFilePartial/blob/master/ServeFilePartial.inc.php
 *  + https://github.com/diversen/http-send-file
 */
class DownloadHelper {
    
    const DISPOSITION_ATTACHMENT = 'attachment';
    const DISPOSITION_INLINE = 'inline';
    
    /**
     * @var IDowloadableResource
     */
    private $resource = null;

    /**
     * Gets the resource to be downloaded
     * @return IDowloadableResource
     */
    public function getResource()  {
        return $this->resource;
    }

    /**
     * Sets the resource to be downloaded
     * @param IDowloadableResource $resource
     */
    public function setResource(IDowloadableResource $resource) {
        $this->resource = $resource;
    }
    
    /**
     * @var string
     */
    private $downloadFileName = null;

    /**
     * Gets
     * @return string
     */
    public function getDownloadFileName()  {
        return $this->downloadFileName;
    }

    /**
     * Sets
     * @param string $downloadFileName
     */
    public function setDownloadFileName($downloadFileName) {
        $this->downloadFileName = $downloadFileName;
    }
    
    /**
     * @var string
     */
    private $disposition = self::DISPOSITION_ATTACHMENT;

    /**
     * Gets the download disposition
     * @return string
     */
    public function getDisposition()  {
        return $this->disposition;
    }

    /**
     * Sets the download disposition
     * @param string $disposition
     */
    public function setDisposition($disposition) {
        $this->disposition = $disposition;
    }
    
    /**
     * @var bool
     */
    private $byteRangesEnabled = false;

    /**
     * Gets the flag
     * @return bool
     */
    public function isByteRangesEnabled()  {
        return $this->byteRangesEnabled;
    }

    /**
     * Sets the flag
     * @param bool $byteRangesEnabled
     */
    public function setByteRangesEnabled($byteRangesEnabled) {
        $this->byteRangesEnabled = (bool)$byteRangesEnabled;
    }
    
    public function __construct() {
    }
    
    public function download() {
        
        if (empty($this->resource)) {
            throw new \RuntimeException('Missing the resource to be downloaded');
        }
        
        $this->disableOutputBuffering();
        $this->disableOutputFilters();
        $this->outputHeaders();
        $this->outputData();
        $this->outputEnd();
    }
    
    protected function disableOutputBuffering() {
        if (headers_sent()) {
            throw new \RuntimeException('Headers already sent, cannot start a download.');
        }
        
        $result = true;
        
        // Loop to disable all output buffers
        do {
            $result = ob_end_clean();
        } while($result);
    }
    
    protected function disableOutputFilters() {
        
    }
    
    protected function outputHeaders() {
        header("Pragma: public");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        
        if ($this->byteRangesEnabled) {
            header("Accept-Ranges: bytes");
        }
    }
    
    protected function outputRangeHeaders($start, $end) {
        header('HTTP/1.1 206 Partial Content');
        header('Accept-Ranges: bytes');
        header("Content-Range: bytes $start-$end/" . $this->resource->getSize());
        $contentLength = $end - $start + 1;
        header("Content-Length: $contentLength");
    }
    
    protected function sendErrorBadRange() {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
    }
    
    protected function getRanges() {
        if (!$this->byteRangesEnabled || !isset($_SERVER['HTTP_RANGE'])) {
            return false;
        }
        
        $parts = explode(',', $_SERVER['HTTP_RANGE']);
        $ranges = [];
        
        foreach($parts as $part) {
            if ($part == '-') {
                $this->sendErrorBadRange();
                return false;
            }
            
            if ($part[0] == '-') {
                $part = "0$part";
            }
            
            if ($part[strlen($part) - 1] == '-') {
                $part .= "" . $this->size - 1;
            }
            
            $rangeParts = explode('-', $part);
            
            if (count($rangeParts) != 2) {
                $this->sendErrorBadRange();
                return false;
            }
            
            $ranges[]= ['start' => $rangeParts[0], 'length' => $rangeParts[1] - $rangeParts[0] + 1];
        }
        
        usort($ranges, function($a, $b){
            return ($a['start'] - $b['start']) % 2;
        });
        
        return $ranges;
    }
    
    protected function outputData() {
        set_time_limit(0);
        $offset = 0;
        
        if ($this->byteRangesEnabled) {
            $ranges = $this->getRanges();
        }
        else {
            $ranges = [['start' => 0, 'length' => $this->size]];
        }

        $pos = 0;
        $limit = count($ranges);
        $data = true;
        
        while($data !== false && $pos < $limit) {
            $data = $this->resource->readBytes($ranges[$pos]['start'], $ranges[$pos]['length']);
            
            if ($data !== false) {
                print($data);
                unset($data);
                ob_flush();
                flush();
            }
            else {
                break;
            }
            
            ++$pos;
        }
    }
    
    protected function outputEnd() {
        
    }
}
