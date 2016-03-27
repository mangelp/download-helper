<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

/**
 * Output helper for downloads that implements IOutputHelper
 *
 * All headers are sent at once when the first write call is done.
 */
class DownloadOutputHelper implements IOutputHelper {

    public function __construct($clearOutputBuffers = true) {
        if ($clearOutputBuffers) {
            $this->clearOutputBuffers();
        }
    }
    
    private $outputBuffersCleared = false;
    
    protected function clearOutputBuffers() {
        if ($this->outputBuffersCleared) {
            return;
        }
        
        $result = true;
        
        // Loop to disable all output buffers
        do {
            $result = @ob_end_clean();
        } while($result);
        
        $this->outputBuffersCleared = true;
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
        
        $this->headers[]= $string;
    }
    
    public function getHeaders() {
        return $this->headers;
    }
    
    /**
     * Outputs all headers
     */
    protected function sendHeaders() {
        if ($this->headersSent) {
            return;
        }
        
        foreach($this->headers as $pos => $header) {
            header($header, true);
        }
        
        $this->headersSent = true;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \mangelp\downloadHelper\IOutputHelper::write()
     */
    public function write($data) {
        if (!$this->headersSent) {
            $this->sendHeaders();
        }
        
        echo $data;
        
        return strlen($data);
    }
    
    public function flush() {
        
        if (!$this->headersSent) {
            $this->sendHeaders();
        }
        
        @ob_flush();
        @flush();
    }
}
