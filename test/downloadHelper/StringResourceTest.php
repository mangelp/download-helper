<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

class StringResourceTest extends \PHPUnit_Framework_TestCase {
    /**
     *
     * @var StringResource
     */
    protected $stringResource;
    
    private $testString = "Lorem ipsum dolor sit amet.";
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->stringResource = new StringResource($this->testString);
    }
    
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    
    public function testProperties() {
        self::assertEquals('text/plain', $this->stringResource->getMime());
        self::assertEquals(strlen($this->testString), $this->stringResource->getSize());
        self::assertEquals($this->testString, $this->stringResource->getString());
    }
    
    public function testReadContentsByteByByte() {
        
        $data = null;
        $datas = [];
        $offset = 0;
        
        do {
            $data = $this->stringResource->readBytes($offset, 12);
            
            if ($data) {
                $offset += strlen($data);
                $datas[]= $data;
            }
        } while($data);
        
        self::assertEquals(['Lorem ipsum ', 'dolor sit am', 'et.'], $datas);
        
        $actual = implode($datas);
        self::assertEquals($this->testString, $actual);
    }
    
    public function testReadStringChunks() {
        $chunks = [
            [0, 4],
            [6,10],
            [12,16],
            [18,20],
            [22,25],
        ];
    
        $expected = [
            'Lorem',
            'ipsum',
            'dolor',
            'sit',
            'amet',
        ];
    
        foreach($chunks as $pos => $chunk) {
            $startByte = $chunk[0];
            $length = $chunk[1] - $chunk[0] + 1;
            $bytes = $this->stringResource->readBytes($startByte, $length);
            self::assertEquals($expected[$pos], $bytes, "Failed to properly read bytes(${chunk[0]},${chunk[1]})");
        }
    }
}