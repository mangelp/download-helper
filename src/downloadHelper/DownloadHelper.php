<?php
namespace mangelp\downloadHelper;

/**
 * Download helper coded following the next samples and git repositories:
 *  + http://www.media-division.com/the-right-way-to-handle-file-downloads-in-php/
 *  + https://github.com/TimOliver/PHP-Framework-Classes/blob/master/download.class.php
 *  + https://github.com/pomle/php-serveFilePartial/blob/master/ServeFilePartial.inc.php
 *  + https://github.com/diversen/http-send-file
 */
class DownloadHelper {
    
    const DISPOSITION_ATTACHMENT = 'attachment';
    const DISPOSITION_INLINE = 'inline';
    
    /**
     * @var AbstractResource
     */
    private $resource = null;

    /**
     * Gets the resource to be downloaded
     * @return AbstractResource
     */
    public function getResource()  {
        return $this->resource;
    }

    /**
     * Sets the resource to be downloaded
     * @param AbstractResource $resource
     */
    public function setResource(AbstractResource $resource) {
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
}