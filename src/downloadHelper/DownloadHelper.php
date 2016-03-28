<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

/**
 * Outputs all the needed headers and data to download a file throught HTTP.
 *
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
    
    const CACHE_NEVER = 'never';
    const CACHE_REVALIDATE = 'revalidate';
    
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
     * Gets the file name to send to the HTTP client downloading the resource
     * @return string
     */
    public function getDownloadFileName()  {
        return $this->downloadFileName;
    }

    /**
     * Sets the file name to send to the HTTP client downloading the resource
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
     * Gets if the download must support the Range header
     * @return bool
     */
    public function isByteRangesEnabled()  {
        return $this->byteRangesEnabled;
    }

    /**
     * Sets if the download must support the Range header
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
     * Gets the output helper that writes the headers and data to the output stream in response to
     * an HTTP request
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
     * Gets the number of seconds for each resource read operation to end.
     * If is set to zero or a negative value then the time limit will not be set before reads.
     * @return int
     */
    public function getReadTimeLimit()  {
        return $this->readTimeLimit;
    }

    /**
     * Sets the number of seconds for each resource read operation to end.
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
    public function setRestorePreviousTimeLimit($restorePreviousTimeLimit) {
        $this->restorePreviousTimeLimit = (bool)$restorePreviousTimeLimit;
    }
    
    private $multipart = false;
    
    public function isMultipart() {
        return $this->multipart;
    }
    
    private $multipartBoundary = null;
    
    public function getMultipartBoundary() {
        return $this->multipartBoundary;
    }
    
    /**
     * @var string
     */
    private $cacheMode = self::CACHE_NEVER;

    /**
     * Gets the cache mode that controls what cache-control headers are set and how
     * @return string
     */
    public function getCacheMode()  {
        return $this->cacheMode;
    }

    /**
     * Sets the cache mode that controls what cache-control headers are set and how
     * @param string $cacheMode
     */
    public function setCacheMode($cacheMode) {
        $this->cacheMode = $cacheMode;
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
        $this->processDownload(true, true);
        
        $this->end();
    }
    
    /**
     * Only returns all the headers of the response, but without any data.
     * This method is intended to be of use responding to HEAD requests over the resource. Web
     * browsers might make those requests to validate the download for caching purposses.
     *
     * @throws \RuntimeException
     */
    public function headers() {
        
        $this->processDownload(true, false);
        
        $this->end();
    }
    
    /**
     * Performs the download and headers output
     * @param bool $populateHeaders
     * @param bool $sendData
     * @throws \RuntimeException
     */
    protected function processDownload($populateHeaders, $sendData) {
        if (!$this->resource) {
            throw new \RuntimeException('The resource to download have not been set');
        }
        
        $ranges = false;
        $rangeError = false;
        $unsupportedError = false;
        
        try {
            $ranges = $this->getRanges();
        }
        catch(\InvalidArgumentException $iaex) {
            $rangeError = true;
        }
        
        // Change range type to match
        if ($ranges === false) {
            $ranges = [];
        }
        else if (count($ranges) > 1) {
            $this->multipart = true;
        }
        else if (count($ranges) == 1
                && $ranges[0]['length'] == $this->resource->getSize()
                && $ranges[0]['start'] == 0) {
            // If ranges are empty then
            $ranges = [];
        }
        
        if ($populateHeaders) {
            $this->populateHeaders($ranges, $rangeError, $unsupportedError);
        }
        
        if ($sendData) {
            $this->sendData($ranges);
        }
        
        return $ranges;
    }
    
    protected function populateHeaders($ranges, $rangeError, $unsupportedError) {
        
        if ($rangeError) {
            $this->outputBadRangeHeader();
        }
        else if ($unsupportedError) {
            $this->outputError('Not supported');
        }
        else if ($ranges === false || empty($ranges)
                || (count($ranges) == 1
                    && $ranges[0]['start'] == 0
                    && $ranges[0]['length'] == $this->resource->getSize())) {
            $this->outputNonRangeDownloadHeader();
        }
        else {
            $this->outputRangeDownloadHeaders($ranges);
        }
    }
    
    /**
     * Outputs a first set of headers needed for the download.
     * The method DownloadHelper::outputRangeHeaders() must be also called to output download
     * specific headers if the client specified a range request.
     *
     * @param IOutputHelper $output
     */
    protected function outputCommonHeaders() {
        
        $this->output->addHeader('Content-Disposition: ' . $this->disposition . '; filename="' . $this->downloadFileName . '"');
        $this->output->addHeader('Content-Transfer-Encoding: binary');
        
        if ($this->byteRangesEnabled) {
            $this->output->addHeader('Accept-Ranges: bytes');
        }
        else {
            $this->output->addHeader('Accept-Ranges: none');
        }
        
        $this->output->addHeader('Date: ' . $this->formatHttpHeaderDate(time()));
        
        if ($this->cacheMode == self::CACHE_NEVER) {
            $this->output->addHeader('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        }
        else if ($this->cacheMode == self::CACHE_REVALIDATE) {
            $this->output->addHeader('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            
            // If neither an etag or last modified dates are provided then the must-revalidate cache
            // control header will cause the resource to be re-downloaded each time.
            if ($this->resource->getLastModifiedDate()) {
                $this->output->addHeader('Last-Modified: ' . $this->formatHttpHeaderDate($this->resource->getLastModifiedDate()));
            }
            
            if ($this->resource->getEntityTag()) {
                $this->output->addHeader('ETag: ' . $this->resource->getEntityTag());
            }
        }
    }
    
    /**
     * Formats a date for use into a HTTP header
     * @param \DateTime|int $date
     * @throws \InvalidArgumentException
     * @return a GMT date formatted for use into a HTTP header
     */
    protected function formatHttpHeaderDate($date = null) {
        $format = 'D, j F Y, H:i:s T';
        if ($date === null) {
            $date = time();
        }
        
        if (is_a($date, '\\DateTime')) {
            return gmdate($format, $date->getTimestamp());
        }
        else if (is_numeric($date)) {
            return gmdate($format, $date);
        }
        else {
            throw new \InvalidArgumentException('unsupported date argument type ' . gettype($date));
        }
    }
    
    /**
     * Outputs the headers to return a single data range. Multiple data ranges are not supported
     * @param int $start
     * @param int $end
     */
    protected function outputRangeDownloadHeaders(array $ranges) {
        
        $this->output->addHeader('HTTP/1.1 206 Partial Content');
        
        $this->outputCommonHeaders();
        
        $totalSize = 0;
        
        foreach($ranges as $range) {
            $totalSize += $range['length'];
        }
        
        $this->output->addHeader('Content-Length: ' . $totalSize);
        
        if (count($ranges) > 1) {
            $this->multipartBoundary = sha1($this->downloadFileName . $this->resource->getSize());
            $this->multipart = true;
            $this->output->addHeader('Content-Type: multipart/byteranges; boundary=' . $this->multipartBoundary);
        }
        else {
            $this->output->addHeader('Content-Type: ' . $this->resource->getMime());
            $this->output->addHeader('Content-Range: bytes ' . $ranges[0]['start'] . '-' . $ranges[0]['end'] . '/' . $this->resource->getSize());
        }
    }
    
    protected function writeSingleRangeDownloadHeaders(array $range) {
        $lf = "\r\n";
        $this->output->write("$lf--" . $this->multipartBoundary);
        $this->output->write("$lfContent-Type: " . $this->resource->getMime());
        $this->output->write("$lfContent-Range: bytes " . $range['start'] . '-' . $range['end'] . '/' . $this->resource->getSize());
        $this->output->write("$lf$lf");
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
        $this->output->addHeader('Content-Type: ' . $this->resource->getMime());
        
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
        
        if (empty($ranges)) {
            $size = $this->resource->getSize();
            $ranges = [
                ['start' => 0, 'end' => $size - 1, 'length' => $size]
            ];
        }
        
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
            
            if ($this->multipart) {
                $this->writeSingleRangeDownloadHeaders($ranges[$pos]);
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
