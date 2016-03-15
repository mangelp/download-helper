<?php
namespace mangelp\downloadHelper;

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
     * Reads the specified number of bytes from the resource
     *
     * @param int $startOffset
     * @param int $length
     */
    public function readBytes($startOffset, $length);
    
    /**
     * Reads all the bytes from a resource offset to the end of it.
     * @param int $startOffset
     */
    public function readToEnd($startOffset);
}