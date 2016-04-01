<?php
namespace mangelp\downloadHelper;

/**
 * File reader for use with the download helper.
 */
class FileResource implements IDownloadableResource {
    
    /**
     * @var string
     */
    private $filePath = null;

    /**
     * Gets
     * @return string
     */
    public function getFileName()  {
        return $this->filePath;
    }
    
    /**
     * @var string
     */
    private $mime = null;

    /**
     * Gets the mime type
     * @return string
     */
    public function getMime()  {
        return $this->mime;
    }

    /**
     * Sets the mime type
     * @param string $mime
     */
    public function setMime($mime) {
        $this->mime = $mime;
    }
    
    private $fd = null;
    private $size = 0;
    
    public function getSize() {
        return $this->size;
    }
    
    /**
     * @var int
     */
    private $chunkSize = 8092;

    /**
     * Gets the maximum number of bytes read from the file in each fread call
     * @return int
     */
    public function getChunkSize()  {
        return $this->chunkSize;
    }

    /**
     * Sets the maximum number of bytes read from the file in each fread call
     * @param int $chunkSize
     */
    public function setChunkSize($chunkSize) {
        $this->chunkSize = (int)$chunkSize;
    }
    
    /**
     *
     * @var \DateTime
     */
    private $lastModifiedDate = null;
    
    /**
     * Gets the last modified GMT date
     */
    public function getLastModifiedDate() {
        return $this->lastModifiedDate;
    }
    
    /**
     *
     * @var string
     */
    private $entityTag = null;
    
    public function getEntityTag() {
        return $this->entityTag;
    }
    
    /**
     * Initiallizes the path to the file and reads the file size.
     *
     * If the file size cannot be read a RuntimeException will be thrown.
     *
     * @param string $filePath
     * @throws \RuntimeException If the file size cannot be read and so the file does not exists
     * or is not accesible.
     */
    public function __construct($filePath, $mime = null) {
        
        $fileStat = stat($filePath);
        
        if ($fileStat === false) {
            throw new \RuntimeException("Could not read from file: $filePath");
        }
        
        $this->size = $fileStat['size'];
        $this->lastModifiedDate = new \DateTime();
        $this->lastModifiedDate->setTimestamp((int)$fileStat['mtime']);
        $this->lastModifiedDate->setTimezone(new \DateTimeZone('UTC'));
        $this->entityTag = sha1($filePath . ':' . $fileStat['size'] . '@' . $fileStat['mtime']);
        $this->filePath = $filePath;
        
        if (empty($mime)) {
            $finfod = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfod, $this->filePath);
            finfo_close($finfod);
        }
        
        if (!empty($mime)) {
            $this->setMime($mime);
        }
    }
    
    /**
     * Closes the file descriptor and clears it
     */
    public function __destruct() {
        if ($this->fd) {
            fclose($this->fd);
            $this->fd = null;
        }
    }
    
    /**
     * Ensures that the file descriptor is ready to be used
     * @throws \RuntimeException If the file cannot be read
     */
    protected function ensureOpen() {
        if ($this->fd) {
            return;
        }
        
        $this->fd = fopen($this->filePath, 'rb');
        
        if ($this->fd === false) {
            throw new \RuntimeException("Could not open file for reading: $fileName");
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \mangelp\downloadHelper\IDownloadableResource::readBytes()
     */
    public function readBytes($startOffset = 0, $length = null, $maxChunkSize = null) {
        
        $startOffset = (int)$startOffset;
        $length = (int)$length;
        $maxChunkSize = (int)$maxChunkSize;
        
        if ($startOffset < 0 || $startOffset > $this->size - 1) {
            return false;
        }
        
        if ($maxChunkSize < 1) {
            $maxChunkSize = $this->chunkSize;
        }
        
        if ($length < 1) {
            $length = $this->size;
        }
        
        $this->ensureOpen();
        
        if (fseek($this->fd, $startOffset, SEEK_SET) == -1) {
            throw new \RuntimeException("Cannot seek into file descriptor");
        }
        
        if (feof($this->fd)) {
            return false;
        }
        
        $data = false;
        
        if ($length <= $maxChunkSize) {
            $data = fread($this->fd, $length);
        }
        else {
            $sizeRead = 0;
            $dataRead = '';
            
            // Read chunks of the file until the desired data length is reached.
            // Those chunks are appended to an string as they are also read as strings of bytes
            while($sizeRead < $length && !feof($this->fd)) {
                $nextChunkSize = $length - $sizeRead;
                
                if ($nextChunkSize > $maxChunkSize) {
                    $nextChunkSize = $maxChunkSize;
                }
                
                $dataRead = fread($this->fd, $nextChunkSize);
                
                if ($dataRead === false) {
                    // No more data to read
                    break;
                }
                
                $sizeRead += strlen($dataRead);
                $data .= $dataRead;
            }
        }
        
        return $data;
    }
}
