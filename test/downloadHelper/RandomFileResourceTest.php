<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */


namespace mangelp\downloadHelper;

class RandomFileResourceTest extends \PHPUnit_Framework_TestCase {
    
    /**
     *
     * @var RandomFileResource
     */
    protected $fileResource;
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->fileResource = new RandomFileResource(1024*1024);
    }
    
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    
    public function testRead1KB() {
        
        for ($i=1; $i<=1024; $i++) {
            
            self::assertEquals(($i-1) * 1024, $this->fileResource->getReadSize());
            self::assertEquals(1024*1024 - (($i-1)*1024), $this->fileResource->getReminderSize());
            
            $data = $this->fileResource->readBytes(0, 1024);
            
            $dataLength = strlen($data);
            self::assertNotNull($data);
            self::assertTrue(is_string($data), "#$i: Data must be string but was " . gettype($data));
            self::assertNotEmpty($data);
            self::assertGreaterThan(0, $dataLength);
            self::assertEquals(1024, $dataLength);
            self::assertEquals($i * 1024, $this->fileResource->getReadSize());
            self::assertEquals(1024*1024 - ($i*1024), $this->fileResource->getReminderSize());
        }
        
        self::assertFalse($this->fileResource->readBytes(0, 1024));
    }
}
