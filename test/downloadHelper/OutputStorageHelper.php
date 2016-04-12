<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */


namespace mangelp\downloadHelper;

/**
 * Output helper that stores the contents generated and ignores everything else.
 */
class OutputStorageHelper implements IOutputHelper {
    
    /**
     *
     * @param OutputStorageHelper $output
     */
    public static function cast(OutputStorageHelper $output) {
        return $output;
    }
    
    private $data = [];
    
    public function getData() {
        return $this->data;
    }
    
    private $size = 0;
    
    public function getSize() {
        return $this->size;
    }
    
    public function getCount() {
        return count($this->data);
    }

    public function setHeadersDisabled($value) {}
    
    public function isHeadersDisabled() { return true; }
    
    public function areHeadersSent() { return false; }
    
    private $headers = [];
    
    public function addHeader($text) {
        $this->headers[]= $text;
    }
    
    public function getHeaders() { return $this->headers; }
    
    public function clearHeaders() {
        $this->headers = [];
    }
    
    public function write($data) {
        $this->size += strlen($data);
        $this->data []= $data;
    }

    public function flush() {}
    
    public function end() {}
    
    public function isOutputClosed() { return false; }
}