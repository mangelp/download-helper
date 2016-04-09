<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

/**
 * Random file data reader.
 */
class RandomFileResource implements IDownloadableResource {
    
    /**
     *
     * @param RandomFileResource $resource
     */
    public static function cast(RandomFileResource $resource) {
        return $resource;
    }
    
    private $size = 0;

    public function getSize() {
        return $this->size;
    }
    
    public function setSize($size) {
        $this->size = (int)$size;
    }
    
    private $mime = null;

    public function getMime()  {
        return $this->mime;
    }

    public function setMime($mime) {
        $this->mime = $mime;
    }

    private $lastModifiedDate = null;

    public function getLastModifiedDate()  {
        return $this->lastModifiedDate;
    }

    public function setLastModifiedDate(\DateTime $lastModifiedDate) {
        $this->lastModifiedDate = $lastModifiedDate;
    }
    
    /**
     * @var string
     */
    private $entityTag = null;

    /**
     * Gets
     * @return string
     */
    public function getEntityTag()  {
        return $this->entityTag;
    }

    /**
     * Sets
     * @param string $entityTag
     */
    public function setEntityTag($entityTag) {
        $this->entityTag = $entityTag;
    }
    
    private $readSize = 0;
    
    public function getReminderSize() {
        return $this->size - $this->readSize;
    }
    
    public function getReadSize() {
        return $this->readSize;
    }
    
    public function __construct($size) {
        $this->size = (int)$size;
    }
    
    public function readBytes($startOffset = 0, $length = null, $maxChunkSize = null) {
        // Remaining bytes to read
        $sizeDif = $this->getReminderSize();
        
        if ($sizeDif <= 0) {
            return false;
        }
        
        if ($length === null || $length < 1 || $length > $this->size) {
            $length = $this->size;
        }
        
        if ($length > $sizeDif) {
            $length = $sizeDif;
        }
        
        $randData = null;
        $fd = null;
        
        try {
            $fd = fopen('/dev/urandom', 'rb');
            $randData = fread($fd, $length);
        }
        catch(\Exception $ex) {
            throw new \RuntimeException('Cannot read data from /dev/urandom', 0, $ex);
        }
        finally {
            fclose($fd);
        }
        
        $randData = base64_encode($randData);
        $randData = substr($randData, 0 , $length);
        $this->readSize += strlen($randData);
        
        return $randData;
    }
}
