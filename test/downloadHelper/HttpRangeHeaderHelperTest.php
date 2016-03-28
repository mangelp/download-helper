<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

class HttpRangeHaderHelperTest extends \PHPUnit_Framework_TestCase {
    /**
     *
     * @var HttpRangeHeaderHelper
     */
    protected $httpRangeHeaderHelper;
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->httpRangeHeaderHelper = new HttpRangeHeaderHelper();
    }
    
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    
    public function testJoinCountinuousRanges() {
        $ranges = [
            ['start' => 0, 'end' => 99, 'length' => 100],
            ['start' => 100, 'end' => 199, 'length' => 100],
            ['start' => 250, 'end' => 399, 'length' => 50],
            ['start' => 400, 'end' => 449, 'length' => 150],
        ];
        
        $expected = [
            ['start' => 0, 'end' => 199, 'length' => 200],
            ['start' => 250, 'end' => 449, 'length' => 200],
        ];
        
        $actual = $this->httpRangeHeaderHelper->joinContinuousRanges($ranges);
        
        self::assertEquals($expected, $actual);
    }
    
    public function testJoinOverlappedRanges() {
        $ranges = [
            ['start' => 0, 'end' => 99, 'length' => 100],
            ['start' => 10, 'end' => 199, 'length' => 190],
            ['start' => 350, 'end' => 399, 'length' => 50],
            ['start' => 50, 'end' => 249, 'length' => 200],
        ];
        
        $expected = [
            ['start' => 0, 'end' => 249, 'length' => 250],
            ['start' => 350, 'end' => 399, 'length' => 50],
        ];
        
        $actual = $this->httpRangeHeaderHelper->joinContinuousRanges($ranges);
        
        self::assertEquals($expected, $actual);
    }
    
    public function testParseRanges() {
        $actual = $this->httpRangeHeaderHelper->parseRangeHeader('-');
        self::assertFalse($actual);
        
        $actual = $this->httpRangeHeaderHelper->parseRangeHeader('bytes=-19', 0, 59);
        self::assertEquals([['start' => 0, 'end' => 19, 'length' => 20]], $actual);
        
        $actual = $this->httpRangeHeaderHelper->parseRangeHeader('bytes=19-', 0, 59);
        self::assertEquals([['start' => 19, 'end' => 59, 'length' => 41]], $actual);
        
        $actual = $this->httpRangeHeaderHelper->parseRangeHeader('bytes=3-19');
        self::assertEquals([['start' => 3, 'end' => 19, 'length' => 17]], $actual);
        
        $actual = $this->httpRangeHeaderHelper->parseRangeHeader('bytes=3-3');
        self::assertEquals([['start' => 3, 'end' => 3, 'length' => 1]], $actual);
        
        $actual = $this->httpRangeHeaderHelper->parseRangeHeader('bytes=0-0');
        self::assertEquals([['start' => 0, 'end' => 0, 'length' => 1]], $actual);
            
        $expected = [
            ['start' => 0, 'end' => 90, 'length' => 91],
            ['start' => 100, 'end' => 200, 'length' => 101],
            ['start' => 1530, 'end' => 9650, 'length' => 8121],
        ];
        
        $actual = $this->httpRangeHeaderHelper->parseRangeHeader('bytes=0-90,100-200,1530-9650');
        
        self::assertEquals($expected, $actual);
        
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
            $actual = $this->httpRangeHeaderHelper->parseRangeHeader($rangeHeader);
            self::assertEquals($expectedRangeValue, $actual);
        }
    }
    
    public function testParseMultipleRanges() {
        $rangeValues = [
            'bytes=0-2,4-6' => [['start' => 0, 'end' => 2, 'length' => 3], ['start' => 4, 'end' => 6, 'length' => 3]],
            'bytes=8-12,14-17' => [['start' => 8, 'end' => 12, 'length' => 5], ['start' => 14, 'end' => 17, 'length' => 4]],
            'bytes=3-3,7-7,13-13' => [['start' => 3, 'end' => 3, 'length' => 1], ['start' => 7, 'end' => 7, 'length' => 1], ['start' => 13, 'end' => 13, 'length' => 1]],
        ];
        
        foreach ($rangeValues as $rangeHeader => $expectedRangeValue) {
            $actual = $this->httpRangeHeaderHelper->parseRangeHeader($rangeHeader);
            self::assertEquals($expectedRangeValue, $actual);
        }
    }
}