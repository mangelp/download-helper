<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

/**
 * Output helper for downloads that implements IOutputHelper.
 *
 * All headers are sent at once when the first write call is done.
 *
 * This helper can disable output buffering and compressioni when it is initiallized to avoid
 * problems with the download.
 */
class DownloadOutputHelper implements IOutputHelper {
    
    private $outputClosed = false;
    
    public function isOutputClosed() {
        return $this->outputClosed;
    }

    /**
     * Initiallizes the instance
     *
     * @param string $clearOutputBuffers
     * @param string $disableOutputCompression
     */
    public function __construct($clearOutputBuffers = true, $disableOutputCompression = true) {
        if ($clearOutputBuffers) {
            $this->clearOutputBuffers();
        }
        
        if ($disableOutputCompression) {
            $this->disableOutputCompression();
        }
    }
    
    protected function clearOutputBuffers() {
        $result = true;
        
        // Loop to disable all output buffers
        do {
            $result = @ob_end_clean();
        } while($result);
    }
    
    protected function disableOutputCompression() {
        if((int)ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 0);
        }
    }
    
    private $headersSent = false;
    
    public function areHeadersSent() {
        return $this->headersSent;
    }
    
    private $headers = [];
    
    public function addHeader($string) {
        if ($this->headersSent) {
            throw new \RuntimeException('Headers have been already sent');
        }
        else if ($this->headersDisabled) {
            throw new \RuntimeException('Headers have been disabled');
        }
        
        $this->headers[]= $string;
    }
    
    public function getHeaders() {
        return $this->headers;
    }
    
    public function clearHeaders() {
        if ($this->headersDisabled) {
            return;
        }
        
        if ($this->headersSent) {
            throw new \RuntimeException('Cannot clear headers, they have been already sent.');
        }
        
        $this->headers = [];
    }
    
    private $headersDisabled = false;
    
    public function setHeadersDisabled($headersDisabled) {
        $this->headersDisabled = (bool)$headersDisabled;
    }
    
    public function isHeadersDisabled() {
        return $this->headersDisabled;
    }
    
    /**
     * Outputs all headers and throws the required exception if PHP already sent the headers.
     *
     * This method only sends the headers the first time, subsequent calls will do nothing.
     */
    protected function sendHeaders() {
        if ($this->headersDisabled || $this->headersSent || $this->outputClosed) {
            return;
        }
        
        // If PHP sent the headers already we can not send ours
        if (headers_sent()) {
            throw new \RuntimeException('Cannot send headers. Headers have been already sent by PHP');
        }
        
        // Try to remove any previous headers
        header_remove();
        
        foreach($this->headers as $pos => $header) {
            header($header, true);
        }
        
        $this->headersSent = true;
    }
    
    public function write($data) {
        
        $this->sendHeaders();
        
        print($data);
        
        // We need to check connection availability after writting to the end point.
        if (connection_aborted()) {
            $this->outputClosed = true;
            throw new ConnectionAbortedErrorException('Output stream already closed');
        }
        
        return strlen($data);
    }
    
    public function flush() {
        $this->sendHeaders();
        
        // This is supossed to be disabled, ¿or not?
        @ob_flush();
        @flush();
    }
    
    public function end() {
        die();
    }
}
