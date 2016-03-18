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
    
    /**
     * Performs the generation of an HTTP response that returns a given resource to the client that
     * should handle it and either download or open it.
     *
     * @throws \RuntimeException
     */
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
    
    /**
     * Disables all output buffers
     *
     * @throws \RuntimeException If headers have been already sent
     */
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
    
    /**
     * Outputs a first set of headers needed for the download.
     * The method DownloadHelper::outputRangeHeaders() must be also called to output download
     * specific headers if the client specified a range request.
     */
    protected function outputHeaders() {
        header('Pragma: public');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Disposition: ' . $this->disposition . '; filename="' . $this->downloadFileName . '"');
        
        if ($this->byteRangesEnabled) {
            header('Accept-Ranges: bytes');
        }
    }
    
    /**
     * Outputs the headers to return a single data range. Multiple data ranges are not supported
     * @param int $start
     * @param int $end
     */
    protected function outputRangeHeaders($start, $end) {
        header('HTTP/1.1 206 Partial Content');
        header('Accept-Ranges: bytes');
        header('Content-Range: bytes ' . ($start - $end) . '/' . $this->resource->getSize());
        $contentLength = $end - $start + 1;
        header('Content-Length: ' . $contentLength);
    }
    
    /**
     * Outputs the bad range header
     */
    protected function outputBadRangeHeader() {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
    }
    
    /**
     * Processes the existing HTTP_RANGE server header and returns the ranges as an array where each
     * item has an start and length keys for both the start byte offset and the number of bytes to
     * retrieve from the start offset.
     *
     * @return array Ranges array
     */
    protected function getRanges() {
        if (!$this->byteRangesEnabled || !isset($_SERVER['HTTP_RANGE'])) {
            return false;
        }
        
        $rangeHeaderHelper = new HttpRangeHeaderHelper();
        $ranges = $rangeHeaderHelper->parseRangeHeader();
        
        if ($ranges === false) {
            $this->outputBadRangeHeader();
            $this->outputEnd();
        }
        
        return $ranges;
    }
    
    /**
     * Outputs the data
     */
    protected function outputData() {
        set_time_limit(0);
        $offset = 0;
        
        if ($this->byteRangesEnabled) {
            $ranges = $this->getRanges();
        }
        else {
            $ranges = [['start' => 0, 'length' => $this->size]];
        }
        
        if (count($ranges) != 1) {
            $this->outputBadRangeHeader();
            $this->outputEnd();
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
    
    /**
     * Flushes all buffers and ends the script.
     */
    protected function outputEnd() {
        ob_flush();
        flush();
        die();
    }
}
