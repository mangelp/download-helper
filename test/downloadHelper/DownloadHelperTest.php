<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

class DownloadHelperTest extends \PHPUnit_Framework_TestCase {
    
    /**
     *
     * @var DownloadHelper
     */
    protected $downloadHelper;
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $output = new OutputStorageHelper();
        $resource = new RandomFileResource(4092);
        $this->downloadHelper = new DownloadHelper($output, $resource);
        $this->downloadHelper->setDownloadFileName('random.bin');
    }
    
    public function testProperties() {
        $this->downloadHelper->setByteRangesEnabled(false);
        self::assertFalse($this->downloadHelper->isByteRangesEnabled());
        $this->downloadHelper->setByteRangesEnabled(true);
        self::assertTrue($this->downloadHelper->isByteRangesEnabled());
        
        $this->downloadHelper->setMaxBytesPerSecond(12);
        self::assertEquals(12, $this->downloadHelper->getMaxBytesPerSecond());
    }
    
    public function testGetRanges() {
        $rangeValues = [
            'bytes=0-2' => [['start' => 0, 'end' => 2, 'length' => 3]],
            'bytes=4-6' => [['start' => 4, 'end' => 6, 'length' => 3]],
            'bytes=8-12' => [['start' => 8, 'end' => 12, 'length' => 5]],
            'bytes=14-17' => [['start' => 14, 'end' => 17, 'length' => 4]],
            'bytes=3-3' => [['start' => 3, 'end' => 3, 'length' => 1]],
            'bytes=7-7' => [['start' => 7, 'end' => 7, 'length' => 1]],
            'bytes=13-13' => [['start' => 13, 'end' => 13, 'length' => 1]],
            'bytes=0-0' => [['start' => 0, 'end' => 0, 'length' => 1]],
        ];
        
        foreach ($rangeValues as $rangeHeader => $expectedRangeValue) {
            $actual = $this->downloadHelper->getRanges($rangeHeader);
            self::assertFalse($actual);
        }
        
        $this->downloadHelper->setByteRangesEnabled(true);
        
        foreach ($rangeValues as $rangeHeader => $expectedRangeValue) {
            $actual = $this->downloadHelper->getRanges($rangeHeader);
            self::assertNotFalse($actual, "Cannot parse $rangeHeader");
            self::assertEquals($expectedRangeValue, $actual);
        }
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetRangesThrowsExForInvalidRange() {
        $this->setExpectedException('\\InvalidArgumentException');
        
        self::assertFalse($this->downloadHelper->getRanges('bytes=35-132'));
        
        $this->downloadHelper->setByteRangesEnabled(true);
        $this->downloadHelper->getRanges('bytes=9335-863132');
    }
    
    public function testOutput() {
        
        $resource = RandomFileResource::cast($this->downloadHelper->getResource());
        $output = OutputStorageHelper::cast($this->downloadHelper->getOutput());
        
        $this->downloadHelper->download();
        
        self::assertEquals($resource->getSize(), $output->getSize());
    }
    
    public function testOutputThrottling() {
        $resource = RandomFileResource::cast($this->downloadHelper->getResource());
        $output = OutputStorageHelper::cast($this->downloadHelper->getOutput());
        
        // Make a 1 second sleep after downloaded 1000 bytes
        $maxBytesPerSecond = 1024;
        $this->downloadHelper->setMaxBytesPerSecond($maxBytesPerSecond);
        $minExpectedSeconds = (int)floor((float)$resource->getSize()/$maxBytesPerSecond);
        
        $timeBefore = microtime(true);
        $this->downloadHelper->download();
        $timeAfter = microtime(true);
        $diference = (int)ceil($timeAfter - $timeBefore);
        
        self::assertEquals($resource->getSize(), $output->getSize());
        self::assertGreaterThanOrEqual($minExpectedSeconds, $diference, "Expected than $diference were greater than or equal that $minExpectedSeconds");
        self::assertGreaterThanOrEqual($minExpectedSeconds + 2, $diference, "Expected than $diference were less than or equal that " . ($minExpectedSeconds + 2));
    }
    
    public function testHeadersDispositionAttachment() {
        $resource = RandomFileResource::cast($this->downloadHelper->getResource());
        $output = OutputStorageHelper::cast($this->downloadHelper->getOutput());
        
        $this->downloadHelper->setDisposition(DownloadHelper::DISPOSITION_ATTACHMENT);
        $this->downloadHelper->headers();
        
        self::assertNotEmpty($output->getHeaders());
        
        $expectedHeaders = [
            'HTTP/1.1 200 Data download OK',
            'Content-Disposition: attachment; filename="random.bin"',
        ];
        
        self::assertEquals($expectedHeaders, array_intersect($expectedHeaders, $output->getHeaders()));
    }
    
    public function testHeadersInline() {
        $resource = RandomFileResource::cast($this->downloadHelper->getResource());
        $output = OutputStorageHelper::cast($this->downloadHelper->getOutput());
    
        $this->downloadHelper->setDisposition(DownloadHelper::DISPOSITION_INLINE);
        $this->downloadHelper->headers();
    
        self::assertNotEmpty($output->getHeaders());
    
        $expectedHeaders = [
            'HTTP/1.1 200 Data download OK',
            'Content-Disposition: inline; filename="random.bin"',
        ];
    
        self::assertEquals($expectedHeaders, array_intersect($expectedHeaders, $output->getHeaders()));
    }
    
    public function testHeadersNoneCaching() {
        $resource = RandomFileResource::cast($this->downloadHelper->getResource());
        $output = OutputStorageHelper::cast($this->downloadHelper->getOutput());
    
        $this->downloadHelper->setCacheMode(DownloadHelper::CACHE_NONE);
        $this->downloadHelper->headers();
    
        self::assertNotEmpty($output->getHeaders());
    
        $expectedHeaders = [
            'HTTP/1.1 200 Data download OK',
            'Content-Type: application/octect-stream',
            'Content-Disposition: attachment; filename="random.bin"',
            'Content-Transfer-Encoding: binary',
            'Accept-Ranges: none',
            'Content-Length: 4092',
        ];
    
        self::assertEquals($expectedHeaders, array_intersect($expectedHeaders, $output->getHeaders()));
    }
    
    public function testHeadersNeverCaching() {
        $resource = RandomFileResource::cast($this->downloadHelper->getResource());
        $output = OutputStorageHelper::cast($this->downloadHelper->getOutput());
        
        $this->downloadHelper->setCacheMode(DownloadHelper::CACHE_NEVER);
        $this->downloadHelper->headers();
        
        self::assertNotEmpty($output->getHeaders());
        
        $expectedHeaders = [
            'HTTP/1.1 200 Data download OK',
            'Content-Type: application/octect-stream',
            'Content-Disposition: attachment; filename="random.bin"',
            'Content-Transfer-Encoding: binary',
            'Accept-Ranges: none',
            'Pragma: private',
            'Cache-control: private',
            'Expires: Thu, 01 Jan 1970 00:00:00 GMT',
            'Content-Length: 4092',
        ];
        
        self::assertEquals($expectedHeaders, array_intersect($expectedHeaders, $output->getHeaders()));
    }
    
    public function testHeadersRevalidateCaching() {
        $curDateTime = new \DateTime();
        $resource = RandomFileResource::cast($this->downloadHelper->getResource());
        $resource->setLastModifiedDate($curDateTime);
        $resource->setEntityTag('01ThisIsATest10');
        $output = OutputStorageHelper::cast($this->downloadHelper->getOutput());
        
        $this->downloadHelper->setCacheMode(DownloadHelper::CACHE_REVALIDATE);
        $this->downloadHelper->headers();
        
        self::assertNotEmpty($output->getHeaders());
        
        $expectedHeaders = [
            'HTTP/1.1 200 Data download OK',
            'Content-Type: application/octect-stream',
            'Content-Disposition: attachment; filename="random.bin"',
            'Content-Transfer-Encoding: binary',
            'Accept-Ranges: none',
            'Pragma: public',
            'Cache-Control: must-revalidate, post-check=0, pre-check=0',
            'Last-Modified: ' . gmdate('D, d M Y H:i:s T', $curDateTime->getTimestamp()),
            'ETag: 01ThisIsATest10',
            'Content-Length: 4092',
        ];
        
        self::assertEquals($expectedHeaders, array_intersect($expectedHeaders, $output->getHeaders()));
    }
}
