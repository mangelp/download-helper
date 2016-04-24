<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

/**
 * Outputs all the needed headers and data to download a file throught HTTP protocol.
 *
 * This helper does not check the current HTTP verb, but reads the Ranges and HTTP_IF_MODIFIED_SINCE
 * headers while processing the download.
 *
 * Header output can be disabled to allow setting your own headers, but headers set while sending
 * the data in multi-part downloads cannot be disabled.
 *
 * Caching control headers can be also disabled to allow the use of custom ones. The default ones
 * provided are only must-revalidate and etag/last-modified dates.
 *
 * Download helper coded with advice taken from the next samples and repositories:
 *  + http://www.media-division.com/the-right-way-to-handle-file-downloads-in-php/
 *  + https://github.com/TimOliver/PHP-Framework-Classes/blob/master/download.class.php
 *  + https://github.com/pomle/php-serveFilePartial/blob/master/ServeFilePartial.inc.php
 *  + https://github.com/diversen/http-send-file
 *
 * References to have at hand:
 *  + https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.16
 *  + http://www.ietf.org/rfc/rfc2616.txt
 */
class DownloadHelper {
    /**
     * Download to client
     * @var string
     */
    const DISPOSITION_ATTACHMENT = 'attachment';
    /**
     * Open in browser if it has a viewer for the given mime type.
     * @var string
     */
    const DISPOSITION_INLINE = 'inline';
    
    /**
     * Entirely disable caching of the resource
     * @var string
     */
    const CACHE_NEVER = 'never';
    /**
     * Set revalidation headers.
     *
     * If the resource provides an etag or modification date then the download content will be
     * revalidated (requires HEAD verb support).
     *
     * If the resource does not provides etag or modification date then the resource will not be
     * cached at all due to the browser not being able to check validity. This case is the same
     * as setting the cache to 'never'.
     * @var string
     */
    const CACHE_REVALIDATE = 'revalidate';
    /**
     * Do not use any caching control headers.
     * @var string
     */
    const CACHE_NONE = 'none';
    
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
    
    /**
     * Get the boundary string used with multipart downloads.
     *
     * If this string is needed and not set a random one will be generated.
     *
     * @return null|string
     */
    public function getMultipartBoundary() {
        return $this->multipartBoundary;
    }
    
    /**
     * Set the boundary string used with multipart downloads.
     *
     * If this string is needed and not set a random one will be generated.
     *
     * @param unknown $multipartBoundary
     */
    public function setMultipartBoundary($multipartBoundary) {
        $this->multipartBoundary = trim($multipartBoundary);
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
    
    /**
     * @var int
     */
    private $maxBytesPerSecond = 0;

    /**
     * Gets the maximum number of bytes to output per second
     *
     * A number less than 1 will disable this feature.
     *
     * @return int
     */
    public function getMaxBytesPerSecond()  {
        return $this->maxBytesPerSecond;
    }

    /**
     * Sets the maximum number of bytes to output per second.
     *
     * A number less than 1 will disable this feature.
     *
     * @param int $maxBytesPerSecond
     */
    public function setMaxBytesPerSecond($maxBytesPerSecond) {
        $this->maxBytesPerSecond = (int)$maxBytesPerSecond;
    }
    
    public function __construct(IOutputHelper $output, IDownloadableResource $resource = null) {
        $this->output = $output;
        
        if ($resource !== null) {
            $this->setResource($resource);
        }
    }
    
    /**
     * Writes the data to the given IOutputHelper implementation along with all the needed HTTP
     * headers.
     *
     * @throws \RuntimeException
     * @throws \ErrorException When the connection is aborted
     */
    public function download() {
        $this->processDownload(true, true);
        
        $this->end();
    }
    
    /**
     * Only writes all the headers to the IOutputHelper implementation, but without any data.
     *
     * This method is intended to be of use responding to HEAD requests over the resource to check
     * for cache revalidation.
     *
     * @throws \RuntimeException
     * @throws \ErrorException When the connection is aborted
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
        $this->multipart = false;
        $this->multipartBoundary = null;
        
        try {
            $ranges = $this->getRanges();
        }
        catch(\InvalidArgumentException $iaex) {
            $rangeError = true;
        }
        
        $ifModifiedSinceDate = $this->getIfModifiedHeaderDate();
        
        // Change range type to match
        if ($ranges === false) {
            $ranges = [];
        }
        else if (count($ranges) > 1) {
            // This is a multi-part download, set the flag and ensure we have a valid boundary
            $this->multipart = true;
            
            if (empty($this->multipartBoundary)) {
                $this->multipartBoundary = $this->generateMultipartBoundary();
            }
        }
        else if (count($ranges) == 1
                && $ranges[0]['length'] == $this->resource->getSize()
                && $ranges[0]['start'] == 0) {
            // If ranges are empty then
            $ranges = [];
        }
        
        if ($populateHeaders) {
            $this->populateHeaders($ranges, $rangeError, $unsupportedError, $ifModifiedSinceDate);
        }
        
        if ($sendData) {
            $this->sendData($ranges);
        }
        
        return $ranges;
    }
    
    /**
     * Parses the If-Modified-Since header date and returns it as a DateTime. It does not allow a
     * date string that only contains alphabetic characters.
     *
     * @return \DateTime|null The parsed date as a DateTime or null if was not present or invalid.
     */
    protected function getIfModifiedHeaderDate() {
        
        if (!isset($_SERVER) || !isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            return null;
        }
        
        $dateStr = trim($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        
        if (preg_match('/^[a-zA-Z]$/', $dateStr)) {
            return null;
        }
        
        $dtime = new \DateTime($dateStr, new \DateTimeZone('GMT'));
        
        if ($dtime > new \DateTime()) {
            return null;
        }
        
        return $dtime;
    }
    
    /**
     * Populates the headers of the response.
     *
     * @param array|false $ranges
     * @param bool $rangeError
     * @param bool $unsupportedError
     * @param \DateTime $ifModifiedSinceDate
     */
    protected function populateHeaders($ranges, $rangeError, $unsupportedError, \DateTime $ifModifiedSinceDate = null) {
        
        if ($rangeError) {
            $this->outputBadRangeHeader();
        }
        else if ($unsupportedError) {
            $this->outputError('Not supported');
        }
        else if ($ifModifiedSinceDate !== null
              && $this->resource->getLastModifiedDate() !== null
              && $ifModifiedSinceDate <= $this->resource->getLastModifiedDate()) {
            
            $this->outputNotModifiedHeader();
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
            $this->output->addHeader('Pragma: private');
            $this->output->addHeader('Cache-control: private');
            $this->output->addHeader('Expires: ' . $this->formatHttpHeaderDate(0));
        }
        else if ($this->cacheMode == self::CACHE_REVALIDATE) {
            $this->output->addHeader('Pragma: public');
            $this->output->addHeader('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            
            // Setting must-revalidate without etag or last-modified causes the resource to be
            // redownloaded each time
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
        $format = 'D, d M Y H:i:s T';
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
    
    protected function addResponseCodeHeader($code, $message) {
        $protocol = isset($_SERVER) && isset($_SERVER['SERVER_PROTOCOL']) ?
            $_SERVER['SERVER_PROTOCOL'] :
            'HTTP/1.1';
        
        $this->output->addHeader("$protocol $code $message");
    }
    
    /**
     * Outputs the headers to return a single data range. Multiple data ranges are not supported
     * @param int $start
     * @param int $end
     */
    protected function outputRangeDownloadHeaders(array $ranges) {
        
        $this->addResponseCodeHeader(206, 'Partial Content');
        
        $this->outputCommonHeaders();
        
        $totalSize = 0;
        
        if (count($ranges) > 1) {
            foreach($ranges as $range) {
                $totalSize += 4 + strlen($this->multipartBoundary);
                $totalSize += 2 + strlen('Content-Type: ') + strlen($this->resource->getMime());
                $totalSize += 2 + strlen('Content-Range: bytes ') + strlen("{$range['start']}")
                    + 1 + strlen("{$range['end']}") + 1 + strlen($this->resource->getSize() . "");
                $totalSize += 4 + $range['length'];
            }
            
            $this->output->addHeader('Content-Length: ' . $totalSize);
            $this->output->addHeader('Content-Type: multipart/byteranges; boundary=' . $this->multipartBoundary);
        }
        else {
            $totalSize = $ranges[0]['length'];
            $this->output->addHeader('Content-Length: ' . $totalSize);
            $this->output->addHeader('Content-Type: ' . $this->resource->getMime());
            $this->output->addHeader('Content-Range: bytes ' . $ranges[0]['start'] . '-' . $ranges[0]['end'] . '/' . $this->resource->getSize());
        }
    }
    
    /**
     * In case of multi-part downloads we need to write some headers to the output between data
     * segments.
     * @param array $range
     */
    protected function writeSingleRangeDownloadHeaders(array $range) {
        $lf = "\r\n";
        $this->output->write("{$lf}--" . $this->multipartBoundary);
        $this->output->write("{$lf}Content-Type: " . $this->resource->getMime());
        $this->output->write("{$lf}Content-Range: bytes " . $range['start'] . '-' . $range['end'] . '/' . $this->resource->getSize());
        $this->output->write("$lf$lf");
    }
    
    /**
     * Outputs the bad range header and finishes execution
     */
    protected function outputBadRangeHeader() {
        $this->output->addHeader($_SERVER["SERVER_PROTOCOL"] . ' 416 Requested Range Not Satisfiable');
        $this->end();
    }
    
    /**
     * Outputs headers to return the full file
     */
    protected function outputNonRangeDownloadHeader() {
        $this->addResponseCodeHeader(200, 'Data download OK');
        $this->output->addHeader('Content-Type: ' . $this->resource->getMime());
        
        $this->outputCommonHeaders();
        
        $this->output->addHeader('Content-Length: ' . $this->resource->getSize());
    }
    
    /**
     * Outputs the not modified header and finishes execution
     */
    protected function outputNotModifiedHeader() {
        $this->addResponseCodeHeader(304, 'Not Modified');
        $this->end();
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
     * Gets the diference in microseconds between two Unix timestamps with microseconds obtained
     * from microtime(true).
     *
     * $before must be always less than or equals than $after
     *
     * @param float $before Unix time with microseconds
     * @param float $after Unix time with microseconds
     */
    private function getMicrotimeDiff($before, $after) {
        return (int)round(($after * 1000000) - ($before * 1000000), 0);
    }
    
    /**
     * Outputs the data to the client.
     *
     * Takes care of max-chunk reading control, applies time limits before range processing and
     * controls throttling.
     *
     * It will never throw exceptions.
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
        $aborted = false;
        
        while($data !== false && $pos < $limit && !$aborted) {
            if ($this->readTimeLimit > 0) {
                // Set the time limit for every read
                set_time_limit($this->readTimeLimit);
            }
            
            if ($this->multipart) {
                try {
                    $this->writeSingleRangeDownloadHeaders($ranges[$pos]);
                }
                catch (ConnectionAbortedErrorException $caeex) {
                    // Connection aborted
                    $aborted = true;
                    break;
                }
            }
            
            // Length of the data already read from the current range
            $dataLength = 0;
            // Target length to read
            $targetLength = $ranges[$pos]['length'];
            // Previous loop end time
            $endTime = microtime(true);
            // Current loop start time
            $startTime = 0;
            
            while($data !== false && $dataLength < $ranges[$pos]['length'] && !$aborted) {
                $startTime = microtime(true);
                $iterationTargetLength = $targetLength;
                
                if ($this->maxBytesPerSecond > 0 && $this->maxBytesPerSecond < $iterationTargetLength) {
                    $iterationTargetLength = $this->maxBytesPerSecond;
                }
                
                // Read the data with the target length
                $data = $this->resource->readBytes($ranges[$pos]['start'], $iterationTargetLength);
                
                if ($data !== false) {
                    try {
                        $this->output->write($data);
                    }
                    catch (ConnectionAbortedErrorException $caeex) {
                        // Connection aborted
                        $aborted = true;
                        break;
                    }
                    
                }
                else {
                    // If there is no more data to be read we end this loop here
                    break;
                }
                
                // Get the length and free the data already sent (set to true to keep the loop going)
                $readLength = strlen($data);
                $data = true;
                
                // Then check if the data read filled the desired size
                if ($readLength < $targetLength) {
                    // If the data read size does not fills the length required update the target
                    // length for the next read.
                    $targetLength -= $readLength;
                }
                
                // Increase the already read data size to check the limit before each read
                $dataLength += $readLength;
                $readLength = 0;
                
                if ($this->maxBytesPerSecond > 0) {
                    // Must send output to client before sleep
                    $this->output->flush();
                    // Sleep for 1000000 microseconds but take account of the time we might have
                    // spent waiting between loops
                    
                    // $endTime <= $startTime always
                    $dif = (int)round(($startTime * 1000000) - ($endTime * 1000000), 0);
                    $sleepTime = 1000000 - $dif;
                    
                    usleep($sleepTime);
                }
                
                // The difference between the time when the loop finishes and the time when it
                // starts is substracted from the 1 second base sleep time, for better accuracy.
                $endTime = microtime(true);
            }
            
            if ($data === false) {
                // If there is no more data to be read we end this loop here
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
        $this->output->end();
    }
    
    protected function outputError($error) {
        $this->output->addHeader($_SERVER["SERVER_PROTOCOL"] . ' 500 ' . $error);
        $this->output->flush();
        $this->output->end();
    }
    
    protected function generateMultipartBoundary() {
        return sha1($this->downloadFileName . time() . rand() . $this->resource->getSize());
    }
}
