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
    
    public function testParseRanges() {
        $expected = [
            ['start' => 0, 'end' => 90, 'length' => 91],
            ['start' => 100, 'end' => 200, 'length' => 101],
            ['start' => 1530, 'end' => 9650, 'length' => 8121],
        ];
        
        $actual = $this->httpRangeHeaderHelper->parseRangeHeader('0-90,100-200,1530-9650');
        
        self::assertEquals($expected, $actual);
        
        $actual = $this->httpRangeHeaderHelper->parseRangeHeader('-');
        self::assertFalse($actual);
        
        $actual = $this->httpRangeHeaderHelper->parseRangeHeader('-19', 0, 59);
        self::assertEquals([['start' => 0, 'end' => 19, 'length' => 20]], $actual);
        
        $actual = $this->httpRangeHeaderHelper->parseRangeHeader('19-', 0, 59);
        self::assertEquals([['start' => 19, 'end' => 59, 'length' => 41]], $actual);
    }
}