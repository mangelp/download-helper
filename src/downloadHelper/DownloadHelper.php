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
    
    public function __construct(IOutputHelper $output, IDownloadableResource $resource = null) {
        $this->ouput = $output;
        
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
        
        $ranges = $this->getRanges();
        
        if ($ranges !== false && (count($ranges > 1) || count($ranges) == 0)) {
            $this->outputBadRangeHeader($output);
            $this->die($output);
        }
        
        $this->outputHeaders();

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
            $this->die();
        }
        
        $this->sendData($ranges);
        $this->die();
    }
    
    /**
     * Outputs a first set of headers needed for the download.
     * The method DownloadHelper::outputRangeHeaders() must be also called to output download
     * specific headers if the client specified a range request.
     *
     * @param IOutputHelper $output
     */
    protected function outputHeaders() {
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
    protected function outputRangeDownloadHeaders(array $range, IOutputHelper $output) {
        
        $this->output->addHeader('HTTP/1.1 206 Partial Content');
        $this->output->addHeader('Accept-Ranges: bytes');
        $this->output->addHeader('Content-Range: bytes ' . $range['start'] . '-' . $range['end'] . '/' . $this->resource->getSize());
        $this->output->addHeader('Content-Length: ' . $range['length']);
    }
    
    /**
     * Outputs the bad range header
     */
    protected function outputBadRangeHeader(IOutputHelper $output) {
        $this->output->addHeader('HTTP/1.1 416 Requested Range Not Satisfiable');
    }
    
    /**
     * Outputs headers to return the full file
     */
    protected function outputNonRangeDownloadHeader(IOutputHelper $output) {
        $this->output->addHeader('HTTP/1.1 200');
        $this->output->addHeader('Content-Length: ' . $this->resource->getSize());
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
        
        return $ranges;
    }
    
    /**
     * Outputs the data
     */
    protected function sendData(array $ranges, IOutputHelper $output) {
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
                $this->output->write($data);
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
