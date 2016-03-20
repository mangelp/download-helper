<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

/**
 * Interface that provides the basic methods to read a resource and output it as a download.
 */
interface IDownloadableResource {
    /**
     * Gets the resource size in bytes
     */
    public function getSize();
    
    /**
     * Gets the mime type of the resource
     */
    public function getMime();
    
    /**
     * Reads the specified number of bytes from the resource from the given offset.
     *
     * Calling this method without parameters will read the complete resource contents and return it.
     *
     * @param int $startOffset File offset. Defaults to 0.
     * @param int $length Number of bytes to read. If set to null then the file will be read to
     * the end in chunks of 1024 bytes. Defaults to null.
     * @return the read bytes as an string or false if it could not read any
     * @throws \InvalidArgumentException If the offset is negative or greater than the file size or
     * if the length is negative or zero.
     */
    public function readBytes($startOffset = 0, $length = null);
}