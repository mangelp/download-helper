<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

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
    
    /**
     * @var IOutputHelper
     */
    private $output = null;

    /**
     * Gets
     * @return IOutputHelper
     */
    public function getOutput()  {
        return $this->output;
    }
    
    /**
     * @var int
     */
    private $readTimeLimit = 30;

    /**
     * Gets a number of seconds for the read operation to end.
     * If is set to zero or a negative value then the time limit will not be set before reads.
     * @return int
     */
    public function getReadTimeLimit()  {
        return $this->readTimeLimit;
    }

    /**
     * Sets a number of seconds for the read operation to end.
     * If is set to zero or a negative value then the time limit will not be set before reads.
     * @param int $readTimeLimit
     */
    public function setReadTimeLimit($readTimeLimit) {
        $this->readTimeLimit = (int)$readTimeLimit;
    }
    
    /**
     * @var bool
     */
    private $restorePreviousTimeLimit = false;

    /**
     * Gets if the time limit before downloading is reset after the download data ouput have finished
     * @return bool
     */
    public function isRestorePreviousTimeLimit()  {
        return $this->restorePreviousTimeLimit;
    }

    /**
     * Sets if the time limit before downloading is reset after the download data ouput have finished
     * @param bool $restorePreviousTimeLimit
     */
    public function setrestorePreviousTimeLimit($restorePreviousTimeLimit) {
        $this->restorePreviousTimeLimit = (bool)$restorePreviousTimeLimit;
    }
    
    public function __construct(IOutputHelper $output, IDownloadableResource $resource = null) {
        $this->output = $output;
        
        if ($resource !== null) {
            $this->setResource($resource);
        }
    }
    
    /**
     * Writes the download to the given IOutputHelper implementation along with all the needed
     * HTTP headers.
     *
     * @throws \RuntimeException
     */
    public function download() {
        
        $ranges = false;
        
        try {
            $ranges = $this->getRanges();
        }
        catch(\InvalidArgumentException $iaex) {
            $this->outputBadRangeHeader();
        }
        
        if ($ranges === false) {
            $this->outputNonRangeDownloadHeader();
            $size = $this->resource->getSize();
            $ranges = [
                ['start' => 0, 'end' => $size - 1, 'length' => $size]
            ];
        }
        else if (count($ranges) == 1) {
            $this->outputRangeDownloadHeaders($ranges[0]);
        }
        else if (count($ranges) > 1) {
            // multipart download is not supported
            $this->outputError('Not supported');
        }
        
        $this->sendData($ranges);
        die();
    }
    
    /**
     * Outputs a first set of headers needed for the download.
     * The method DownloadHelper::outputRangeHeaders() must be also called to output download
     * specific headers if the client specified a range request.
     *
     * @param IOutputHelper $output
     */
    protected function outputCommonHeaders() {
        $this->output->addHeader('Pragma: public');
        $this->output->addHeader('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        $this->output->addHeader('Content-Disposition: ' . $this->disposition . '; filename="' . $this->downloadFileName . '"');
        $this->output->addHeader('Content-Type: ' . $this->resource->getMime());
        
        if ($this->byteRangesEnabled) {
            $this->output->addHeader('Accept-Ranges: bytes');
        }
        else {
            $this->output->addHeader('Accept-Ranges: none');
        }
    }
    
    /**
     * Outputs the headers to return a single data range. Multiple data ranges are not supported
     * @param int $start
     * @param int $end
     */
    protected function outputRangeDownloadHeaders(array $range) {
        
        $this->output->addHeader('HTTP/1.1 206 Partial Content');
        
        $this->outputCommonHeaders();
        
        $this->output->addHeader('Accept-Ranges: bytes');
        $this->output->addHeader('Content-Range: bytes ' . $range['start'] . '-' . $range['end'] . '/' . $this->resource->getSize());
        $this->output->addHeader('Content-Length: ' . $range['length']);
    }
    
    /**
     * Outputs the bad range header
     */
    protected function outputBadRangeHeader() {
        $this->output->addHeader('HTTP/1.1 416 Requested Range Not Satisfiable');
        $this->end();
    }
    
    /**
     * Outputs headers to return the full file
     */
    protected function outputNonRangeDownloadHeader() {
        $this->output->addHeader('HTTP/1.1 200');
        
        $this->outputCommonHeaders();
        
        $this->output->addHeader('Content-Length: ' . $this->resource->getSize());
    }
    
    /**
     * Processes the existing HTTP_RANGE server header and returns the ranges as an array where each
     * item has an start and length keys for both the start byte offset and the number of bytes to
     * retrieve from the start offset.
     *
     * @throws \InvalidArgumentException If the range is invalid
     * @return array Ranges array
     */
    public function getRanges($rangeHeaderContent = null) {
        if ($rangeHeaderContent === null && isset($_SERVER) && isset($_SERVER['HTTP_RANGE'])) {
            $rangeHeaderContent = $_SERVER['HTTP_RANGE'];
        }
        
        if (!$this->byteRangesEnabled || empty($rangeHeaderContent)) {
            // We do not have any range to process
            return false;
        }
        
        $rangeHeaderHelper = new HttpRangeHeaderHelper();
        $ranges = $rangeHeaderHelper->parseRangeHeader($rangeHeaderContent);
        
        if ($ranges !== false) {
            
            $ranges = $rangeHeaderHelper->joinContinuousRanges($ranges);
            
            // Check that the ranges are within file contents
            foreach($ranges as $range) {
                if ($range['start'] < 0 || $range['end'] >= $this->resource->getSize()) {
                    throw new \InvalidArgumentException("Invalid range: [${range['start']},${range['end']}]");
                }
            }
        }
        
        return $ranges;
    }
    
    /**
     * Outputs the data
     */
    protected function sendData(array $ranges) {
        $offset = 0;

        $pos = 0;
        $limit = count($ranges);
        $data = true;
        $previousTimeLimit = ini_get('max_execution_time');
        
        while($data !== false && $pos < $limit) {
            if ($this->readTimeLimit > 0) {
                // Set the time limit for every read
                set_time_limit($this->readTimeLimit);
            }
            
            $data = $this->resource->readBytes($ranges[$pos]['start'], $ranges[$pos]['length']);
            
            if ($data !== false) {
                $this->output->write($data);
            }
            else {
                break;
            }
            
            ++$pos;
        }
        
        if ($this->restorePreviousTimeLimit) {
            set_time_limit($previousTimeLimit);
        }
    }
    
    /**
     * Flushes all buffers and ends the script.
     */
    protected function end() {
        $this->output->flush();
        die();
    }
    
    protected function outputError($error) {
        $this->output->addHeader('HTTP/1.1 500 ' . $error);
        $this->output->flush();
        die();
    }
}
