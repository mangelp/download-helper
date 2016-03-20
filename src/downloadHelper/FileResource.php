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
    private $chunkSize = 1024*8;

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
     * Initiallizes the path to the file and reads the file size.
     *
     * If the file size cannot be read a RuntimeException will be thrown.
     *
     * @param string $filePath
     * @throws \RuntimeException If the file size cannot be read and so the file does not exists
     * or is not accesible.
     */
    public function __construct($filePath, $mime = null) {
        
        $size = filesize($filePath);
        
        if ($size === false) {
            throw new \RuntimeException("Could not read from file: $filePath");
        }
        
        $this->size = $size;
        $this->filePath = $filePath;
        
        if (empty($mime)) {
            $finfod = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfod, $this->filePath);
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
    public function readBytes($startOffset = 0, $length = null) {
        $this->ensureOpen();
        
        $startOffset = (int)$startOffset;
        $length = (int)$length;
        
        if ($startOffset < 0 || $startOffset > $this->size - 1) {
            return false;
        }
        
        if (fseek($this->fd, $startOffset, SEEK_SET) == -1) {
            throw new \RuntimeException("Cannot seek into file descriptor");
        }
        
        if (feof($this->fd)) {
            return false;
        }
        
        $data = false;
        
        if ($this->chunkSize === null || $this->chunkSize >= $length) {
            $data = fread($this->fd, $length);
        }
        else {
            $readSize = 0;
            $readData = true;
            
            while($readSize < $length && $readData !== false) {
                $readData = fread($this->fd, $this->chunkSize);
                
                if ($readData === false) {
                    break;
                }
                
                if ($data) {
                    $data .= $readData;
                }
                else {
                    $data = $readData;
                }
                
                $readSize += strlen($readData);
            }
        }
        
        return $data;
    }
}
