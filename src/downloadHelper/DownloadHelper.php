<?php
namespace mangelp\downloadHelper;

/**
 * Download helper coded following the next samples and repositories:
 *  + http://www.media-division.com/the-right-way-to-handle-file-downloads-in-php/
 *  + https://github.com/TimOliver/PHP-Framework-Classes/blob/master/download.class.php
 *  + https://github.com/pomle/php-serveFilePartial/blob/master/ServeFilePartial.inc.php
 *  + https://github.com/diversen/http-send-file
 * HTTP spec reference
 *  + https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.16
 */
class DownloadHelper {
    
    const DISPOSITION_ATTACHMENT = 'attachment';
    const DISPOSITION_INLINE = 'inline';
    
    /**
     * @var IDownloadableResource
     */
    private $resource = null;

    /**
     * Gets the resource to be downloaded
     * @return IDownloadableResource
     */
    public function getResource()  {
        return $this->resource;
    }

    /**
     * Sets the resource to be downloaded
     * @param IDownloadableResource $resource
     */
    public function setResource(IDownloadableResource $resource) {
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
        $this->disableOutputFilters();
        $this->disableOutputBuffering();
        
        $ranges = $this->getRanges();
        
        if ($ranges !== false && (count($ranges > 1) || count($ranges) == 0)) {
            $this->outputBadRangeHeader();
            $this->outputEnd();
        }
        
        $this->outputHeaders();

        if ($ranges === false) {
            $this->outputNonRangeDownloadHeader();
            $size = $this->resource->getSize();
            $ranges = [
                ['start' => 0, 'end' => $size - 1, 'length' => $size]
            ];
        }
        else {
            // We only support single-range download :'(
            $this->outputRangeDownloadHeaders($ranges[0]);
        }
        
        $this->outputData($ranges);
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
        header('Content-Type: ' . $this->resource->getMime());
        
        if ($this->byteRangesEnabled) {
            header('Accept-Ranges: bytes');
        }
        else {
            header('Accept-Ranges: none');
        }
    }
    
    /**
     * Outputs the headers to return a single data range. Multiple data ranges are not supported
     * @param int $start
     * @param int $end
     */
    protected function outputRangeDownloadHeaders(array $range) {
        
        header('HTTP/1.1 206 Partial Content');
        header('Accept-Ranges: bytes');
        header('Content-Range: bytes ' . $range['start'] . '-' . $range['end'] . '/' . $this->resource->getSize());
        header('Content-Length: ' . $range['length']);
    }
    
    /**
     * Outputs the bad range header
     */
    protected function outputBadRangeHeader() {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
    }
    
    /**
     * Outputs headers to return the full file
     */
    protected function outputNonRangeDownloadHeader() {
        header('HTTP/1.1 200');
        header('Content-Length: ' . $this->resource->getSize());
    }
    
    /**
     * Processes the existing HTTP_RANGE server header and returns the ranges as an array where each
     * item has an start and length keys for both the start byte offset and the number of bytes to
     * retrieve from the start offset.
     *
     * @return array Ranges array
     */
    protected function getRanges() {
        if (!$this->byteRangesEnabled || !isset($_SERVER['HTTP_RANGE']) || empty($_SERVER['HTTP_RANGE'])) {
            return false;
        }
        
        $rangeHeaderHelper = new HttpRangeHeaderHelper();
        $ranges = $rangeHeaderHelper->parseRangeHeader();
        
        if ($ranges !== false) {
            $rangeHeaderHelper->joinContinuousRanges($ranges);
        }
        else {
            // When there is no valid range disable byte ranges to return the full set of data
            $this->byteRangesEnabled = false;
        }
        
        return $ranges;
    }
    
    /**
     * Outputs the data
     */
    protected function outputData($ranges) {
        $offset = 0;

        $pos = 0;
        $limit = count($ranges);
        $data = true;
        $timeLimit = ini_get('max_execution_time');
        
        while($data !== false && $pos < $limit) {
            // Use a short time limit for every read
            set_time_limit(60);
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
        
        set_time_limit($timeLimit);
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
