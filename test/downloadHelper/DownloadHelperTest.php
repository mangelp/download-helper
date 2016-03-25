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
    private $phpServerPid = false;
    private $phpServerPort = false;
    private $phpServerIPv4 = '127.0.0.1';
    
    private function setRandomPort() {
        $usedPorts = [];
        exec('netstat -ltn4 | grep 127.0.0.1 | cut -d \: -f 2 | cut -d \  -f 1', $usedPorts);
        $candidatePort = 28800;
        
        while(in_array("$candidatePort", $usedPorts)) {
            ++$candidatePort;
        }
        
        if ($candidatePort < 32000) {
            $this->phpServerPort = $candidatePort;
            return true;
        }
        else {
            return false;
        }
    }
    
    private function getPhpServerAddress() {
        return $this->phpServerIPv4 . ':' . $this->phpServerPort;
    }
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $output = new DownloadOutputHelper(false);
        $resource = new FileResource(__DIR__ . '/foo.txt');
        $this->downloadHelper = new DownloadHelper($output, $resource);
        
        if ($this->getName() == 'testDownloadByHttpClientTool'
                && !$this->phpServerPid
                && $this->setRandomPort()) {
            
            $output = null;
            $serverListenDomain = $this->getPhpServerAddress();
            $cmd = "php -S $serverListenDomain -t " . __DIR__ . " > /dev/null 2>&1 & \
    echo $!";
            
            exec($cmd, $output);
            
            $this->phpServerPid = false;
            
            if (is_array($output) && isset($output[0]) && is_numeric($output[0])) {
                $this->phpServerPid = (int)$output[0];
                // Do a small wait to avoid the socket not being ready on time
                usleep(50000);
            }
        }
    }
    
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        if ($this->phpServerPid) {
            // Send the process a SIGTERM
            posix_kill($this->phpServerPid, 15);
            usleep(50000);
            $this->phpServerPid = false;
            $this->phpServerPort = false;
        }
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
    
    public function testDownloadByHttpClientTool() {
        
        if (!$this->phpServerPid || !$this->phpServerPort) {
            self::fail('Cannot execute test if the php server was not started');
            return;
        }

        $dataRead_0_2 = null;
        $dataRead_4_6 = null;
        $dataRead_8_12 = null;
        $dataRead_14_17 = null;
        $dataRead_3_3 = null;
        $dataRead_7_7 = null;
        $dataRead_13_13 = null;
        $contents = null;
        
        $testScript = 'http://' . $this->getPhpServerAddress() . '/fooFileDownload.php';
        exec('curl -s ' . $testScript, $contents);
        
        self::assertNotEmpty($contents);
        $testFileContents = file_get_contents(__DIR__ . '/foo.txt');
        self::assertCount(4, $contents);
        self::assertEquals($testFileContents, implode("\n", $contents));
        
        exec('curl -s --header "Range: bytes=0-2" ' . $testScript, $dataRead_0_2);
        self::assertCount(1, $dataRead_0_2, "No proper data read: " . print_r($dataRead_0_2, true));
        self::assertEquals('one', $dataRead_0_2[0]);
        
        exec('curl -s --header "Range: bytes=4-6" ' . $testScript, $dataRead_4_6);
        self::assertCount(1, $dataRead_4_6, "No proper data read: " . print_r($dataRead_4_6, true));
        self::assertEquals('two', $dataRead_4_6[0]);
        
        exec('curl -s --header "Range: bytes=8-12" ' . $testScript, $dataRead_8_12);
        self::assertCount(1, $dataRead_8_12, "No proper data read: " . print_r($dataRead_8_12, true));
        self::assertEquals('three', $dataRead_8_12[0]);
        
        exec('curl -s --header "Range: bytes=14-17" ' . $testScript, $dataRead_14_17);
        self::assertCount(1, $dataRead_14_17, "No proper data read: " . print_r($dataRead_14_17, true));
        self::assertEquals('four', $dataRead_14_17[0]);
        
        // The next tests should return a line feed, but since we are getting the contents from
        // the command output as an array of lines we get instead an array with one empty
        // element.
        exec('curl -s --header "Range: bytes=3-3" ' . $testScript, $dataRead_3_3);
        self::assertCount(1, $dataRead_3_3);
        self::assertEmpty($dataRead_3_3[0]);
        
        exec('curl -s --header "Range: bytes=7-7" ' . $testScript, $dataRead_7_7);
        self::assertCount(1, $dataRead_7_7);
        self::assertEmpty($dataRead_7_7[0]);
        
        exec('curl -s --header "Range: bytes=13-13" ' . $testScript, $dataRead_13_13);
        self::assertCount(1, $dataRead_13_13);
        self::assertEmpty($dataRead_13_13[0]);
    }
}