<?php
namespace mangelp\downloadHelper;

class FileResource extends AbstractResource implements IDownloadableResource {
    
    /**
     * @var string
     */
    private $fileName = null;

    /**
     * Gets
     * @return string
     */
    public function getFileName()  {
        return $this->fileName;
    }
    
    private $fd;
    private $size;
    
    public function getSize() {
        return $this->size;
    }
    
    public function __construct($fileName) {
        
        $size = filesize($fileName);
        
        if ($size === false) {
            throw new \RuntimeException("Could not read from file: $fileName");
        }
        
        $this->size = $size;
        $this->fileName = $fileName;
    }
    
    public function __destruct() {
        if ($this->fd) {
            fclose($this->fd);
            $this->fd = null;
        }
    }
    
    protected function ensureOpen() {
        if (!$this->fd) {
            $this->fd = fopen($this->fileName, 'rb');
        }
        
        if ($this->fd === false) {
            throw new \RuntimeException("Could not open file for reading: $fileName");
        }
    }
    
    public function readBytes($startOffset, $length) {
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
        
        $data = fread($this->fd, $length);
        
        return $data;
    }
}