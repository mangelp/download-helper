<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

/**
 * Implements a downloadable resource as an in-memory string whose chars are read byte by byte.
 */
class StringResource implements IDownloadableResource {
    
    private $string = null;
    
    public function getString() {
        return $this->string;
    }
    
    public function setString($string) {
        $this->string = $string;
    }
    
    public function getSize() {
        return strlen($this->string);
    }

    private $mime = null;

    public function getMime() {
        return $this->mime;
    }
    
    public function setMime($mime) {
        $this->mime = $mime;
    }
    
    public function __construct($string = null, $mime = null) {
        if (!is_string($string) && !method_exists($string, '__toString')) {
            throw new \InvalidArgumentException("Invalid argument type, only strings and classes that implement __toString are allowed");
        }
        
        if ($string !== null) {
            $this->string = (string)$string;
        }
        
        if ($mime !== null) {
            $this->mime = $mime;
        }
        
        if (!$this->mime && $this->string) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $this->mime = finfo_buffer($finfo, $this->string, FILEINFO_MIME_TYPE);
            finfo_close($finfo);
        }
    }
    
    public function readBytes($startOffset = 0, $length = null, $maxChunkSize = null) {
        $size = strlen($this->string);
        
        if ($size <= $startOffset || $startOffset < 0) {
            return false;
        }
        
        if ($length <= 0) {
            throw new \RuntimeException('Length cannot be less than 1');
        }
        
        return substr($this->string, $startOffset, $length);
    }
}
