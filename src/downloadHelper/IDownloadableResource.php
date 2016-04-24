<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
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
     * Gets a \DateTime instance with the last modified date or null if this is not supported by
     * the implementation or not specified.
     *
     * @return \DateTime|null
     */
    public function getLastModifiedDate();
    
    /**
     * Gets an string that changes only when the resource has changed or null if this is not
     * supported by the implementation or not specified.
     *
     * @return string|null
     */
    public function getEntityTag();
    
    /**
     * Reads the specified number of bytes from the resource from the given offset with a maximum
     * number of bytes.
     *
     * Calling this method without parameters will read a chunk of the maximum size and return it.
     *
     * The default chunk size depends of each implementation, so it can be a fixed size or nothing.
     * But the read operation must always honor the $maxChunkSize parameter.
     *
     * @param int $startOffset File offset. Defaults to 0.
     * @param int $length Number of bytes to read. If set to null then the file will be read to
     * the end in chunks of a default size specified by the concreate IDownloadableResource
     * implementation. Defaults to null.
     * @param int $maxChunkSize Maximum size of data reads. If set to a negative, zero or null value
     * it will use the default specified by the underliying IDownloadableResource implementation.
     * @return the read bytes as an string or false if it could not read any
     * @throws \InvalidArgumentException If the offset is negative or greater than the file size or
     * if the length is negative or zero.
     */
    public function readBytes($startOffset = 0, $length = null, $maxChunkSize = null);
}