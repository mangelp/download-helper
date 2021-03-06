<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

class FileResourceTest extends \PHPUnit_Framework_TestCase {
    /**
     *
     * @var FileResource
     */
    protected $fileResource;
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->fileResource = new FileResource(__DIR__ . '/foo.txt');
    }
    
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    
    public function testFileProperties() {
        self::assertEquals(__DIR__ . '/foo.txt', $this->fileResource->getFileName());
        self::assertEquals('text/plain; charset=us-ascii', $this->fileResource->getMime());
        self::assertEquals(18, $this->fileResource->getSize());
        self::assertNotEmpty($this->fileResource->getLastModifiedDate());
        self::assertNotEmpty($this->fileResource->getEntityTag());
    }
    
    public function testReadWholeFooFile() {
        $expected = file_get_contents(__DIR__ . '/foo.txt');
        $fileResource = new FileResource(__DIR__ . '/foo.txt');
        $fileResourceBytes = $fileResource->readBytes(0, 8192);
        
        self::assertEquals($expected, $fileResourceBytes);
    }
    
    public function testReadFileContentsByteByByte() {
        $this->fileResource->setChunkSize(1);
        self::assertEquals(1, $this->fileResource->getChunkSize());
        
        $data = null;
        $datas = [];
        $offset = 0;
        
        do {
            $data = $this->fileResource->readBytes($offset, 3);
            
            if ($data) {
                $offset += strlen($data);
                $datas[]= $data;
            }
        } while($data);
        
        self::assertEquals(['one',"\ntw", "o\nt", "hre", "e\nf", 'our'], $datas);
        
        $this->fileResource->setChunkSize(12);
        self::assertEquals(12, $this->fileResource->getChunkSize());
        
        $data = null;
        $datas = [];
        $offset = 0;
        
        do {
            $data = $this->fileResource->readBytes($offset, 5);
        
            if ($data) {
                $offset += strlen($data);
                $datas[]= $data;
            }
        } while($data);
        
        self::assertEquals(["one\nt", "wo\nth", "ree\nf", "our"], $datas);
        
        $actual = implode("", $datas);
        $expected = file_get_contents(__DIR__ . '/foo.txt');
        self::assertEquals($actual, $expected);
    }
    
    public function testReadFileChunks() {
        $chunks = [
            [0, 2],
            [4,6],
            [8,12],
            [14,17],
        ];
        
        $expected = [
            'one',
            'two',
            'three',
            'four',
        ];
        
        foreach($chunks as $pos => $chunk) {
            $bytes = $this->fileResource->readBytes($chunk[0], $chunk[1] - $chunk[0] + 1);
            self::assertEquals($expected[$pos], $bytes);
        }
    }
}