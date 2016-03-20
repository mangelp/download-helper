<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

class DownloadOutputHelperTest extends \PHPUnit_Framework_TestCase {
    /**
     *
     * @var DownloadOutputHelper
     */
    protected $downloadOutputHelper;
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->downloadOutputHelper = new DownloadOutputHelper(false);
    }
    
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    
    public function testHeaderHandling() {
        self::assertFalse($this->downloadOutputHelper->areHeadersSent());
        $this->downloadOutputHelper->addHeader('foo');
        $this->downloadOutputHelper->addHeader('bar: bazz!');
        
        self::assertEquals(['foo', 'bar: bazz!'], $this->downloadOutputHelper->getHeaders());
    }
    
    public function testWriteFileToOutput() {
        ob_start();
        $fileResource = new FileResource(__DIR__ . '/foo.txt');
        $this->downloadOutputHelper->write($fileResource->readBytes(0, 8192));
        $contents = ob_get_clean();
        $expected = file_get_contents(__DIR__ . '/foo.txt');
        
        self::assertEquals($expected, $contents);
    }
}
