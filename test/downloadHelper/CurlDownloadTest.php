<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */


namespace mangelp\downloadHelper;

class CurlDownloadTest extends \PHPUnit_Framework_TestCase {

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
        $candidatePort = mt_rand(12000, 31900);
    
        while(in_array("$candidatePort", $usedPorts)) {
            $candidatePort = mt_rand(10999, 31999);
            exec('netstat -ltn4 | grep 127.0.0.1 | cut -d \: -f 2 | cut -d \  -f 1', $usedPorts);
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
    }
    
    protected function requirePhpWebServer() {
    
        if ($this->phpServerPid || !$this->setRandomPort()) {
            return false;
        }
    
        $output = null;
        $this->phpServerPid = false;
        $serverListenDomain = $this->getPhpServerAddress();
    
        if (!$serverListenDomain) {
            return false;
        }
    
        $cmd = "php -S $serverListenDomain -t " . __DIR__ . " > /dev/null 2>&1 & \
    echo $!";
    
        exec($cmd, $output);
    
        if (is_array($output) && isset($output[0]) && is_numeric($output[0])) {
            $this->phpServerPid = (int)$output[0];
            // Do a small wait to avoid the socket not being ready on time
            usleep(50000);
            return true;
        }
        else {
            return false;
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
    
    private function existsCommand($cmdName) {
        $output = null;
        $return = null;
    
        exec('which ' . $cmdName . ' > /dev/null', $output, $return);
    
        if ($return == 0) {
            return true;
        }
        else {
            return false;
        }
    }
    
    
    
    public function testDownloadSingleRangeDataByHttpClientTool() {
    
        if (!$this->existsCommand('curl')) {
            self::markTestSkipped('Curl is required to test HTTP client download with range headers');
            return;
        }
        
        if (!$this->existsCommand('netstat')) {
            self::markTestSkipped('netstat is required to start a random PHP built-in webserver');
            return;
        }
    
        if (!$this->requirePhpWebServer()) {
            self::markTestSkipped('The PHP built-in server could not be started and this test will not be run without it');
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
        self::assertCount(4, $contents, "Read contents: " . print_r($contents, true));
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
    
        // The ranges specify a consecutive set of bytes, so they are downloaded as a single range
        exec('curl -s --header "Range: bytes=8-12,13-15,10-17" ' . $testScript, $dataReadA);
        self::assertCount(2, $dataReadA, "No proper data read: " . print_r($dataReadA, true));
        self::assertEquals(['three', 'four'], $dataReadA);
    
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
    
        $dataReadFailed = null;
    
        exec('curl -s --header "Range: bytes=35-168" ' . $testScript, $dataReadFailed);
    
        self::assertTrue(is_array($dataReadFailed));
        self::assertEmpty($dataReadFailed);
    }
    
    public function testDownloadSingleRangeHeadersByHttpClientTool() {
    
        if (!$this->existsCommand('curl')) {
            self::markTestSkipped('Curl is required to test HTTP client download with range headers');
            return;
        }
        
        if (!$this->existsCommand('netstat')) {
            self::markTestSkipped('netstat is required to start a random PHP built-in webserver');
            return;
        }
    
        if (!$this->requirePhpWebServer()) {
            self::markTestSkipped('The PHP built-in server could not be started and this test will not be run without it');
            return;
        }
    
        $testScript = 'http://' . $this->getPhpServerAddress() . '/fooFileDownload.php';
    
        $expectedHeaders = [
            'HTTP/1.1 200 Data download OK',
            'Cache-Control: must-revalidate, post-check=0, pre-check=0',
            'Content-Disposition: attachment; filename="foo.txt"',
            'Content-Transfer-Encoding: binary',
            'Content-Type: text/plain; charset=us-ascii',
            'Accept-Ranges: bytes',
            'Content-Length: 18',
        ];
    
        $headers = [];
        exec('curl -s -o /dev/null --dump-header - ' . $testScript, $headers);
    
        self::assertNotEmpty($headers);
        self::assertOneArrayElementStartsBy('Content-type: text/plain', $headers);
        self::assertEquals($expectedHeaders, array_intersect($expectedHeaders, $headers), "Received headers: " . print_r($headers, true));
    
        $expectedHeaders = [
            'HTTP/1.1 206 Partial Content',
            'Content-Disposition: attachment; filename="foo.txt"',
            'Content-Transfer-Encoding: binary',
            'Content-Type: text/plain; charset=us-ascii',
            'Accept-Ranges: bytes',
            'Content-Range: bytes 0-2/18',
            'Content-Length: 3',
        ];
    
        $headers = [];
        exec('curl -s -o /dev/null --dump-header - --header "Range: bytes=0-2" ' . $testScript, $headers);
    
        self::assertNotEmpty($headers);
        self::assertOneArrayElementStartsBy('Content-type: text/plain', $headers);
        self::assertEquals($expectedHeaders, array_intersect($expectedHeaders, $headers), "Received headers: " . print_r($headers, true));
    
        $expectedHeaders = [
            'HTTP/1.1 416 Requested Range Not Satisfiable',
        ];
    
        $headers = [];
        exec('curl -s -o /dev/null --dump-header - --header "Range: bytes=35-168" ' . $testScript, $headers);
    
        self::assertNotEmpty($headers);
        self::assertEquals($expectedHeaders, array_intersect($expectedHeaders, $headers));
    
        $expectedHeaders = [
            'HTTP/1.1 206 Partial Content',
            'Content-Disposition: attachment; filename="foo.txt"',
            'Content-Transfer-Encoding: binary',
            'Accept-Ranges: bytes',
        ];
        $headers = [];
        exec('curl -s -o /dev/null --dump-header - --header "Range: bytes=0-2,8-12" ' . $testScript, $headers);
    
        self::assertNotEmpty($headers);
        self::assertEquals($expectedHeaders, array_intersect($expectedHeaders, $headers));
    }
    
    public function testDownloadMultipleRangeDataByHttpClientTool() {
    
        if (!$this->existsCommand('curl')) {
            self::markTestSkipped('Curl is required to test HTTP client download with range headers');
            return;
        }
        
        if (!$this->existsCommand('netstat')) {
            self::markTestSkipped('netstat is required to start a random PHP built-in webserver');
            return;
        }
    
        if (!$this->requirePhpWebServer()) {
            self::markTestSkipped('The PHP built-in server could not be started and this test will not be run without it');
            return;
        }
    
        $testScript = 'http://' . $this->getPhpServerAddress() . '/fooFileDownload.php';
    
        exec('curl -s --header "Range: bytes=0-2,8-12" ' . $testScript, $dataReadA);
    
        $expectedOutput = [
            '',
            'REPLACEME',
            'Content-Type: text/plain; charset=us-ascii',
            'Content-Range: bytes 0-2/18',
            '',
            'one',
            'REPLACEME',
            'Content-Type: text/plain; charset=us-ascii',
            'Content-Range: bytes 8-12/18',
            '',
            'three',
        ];
    
        self::assertCount(11, $dataReadA, "No proper data read: " . print_r($dataReadA, true));
        // We cannot predict the separator string, so we simply copy it
        $expectedOutput[1] = $dataReadA[1];
        $expectedOutput[6] = $dataReadA[6];
        self::assertEquals($expectedOutput, $dataReadA);
    }
    
    public function testDownloadMultipleRangeHeadersByHttpClientTool() {
    
        if (!$this->existsCommand('curl')) {
            self::markTestSkipped('Curl is required to test HTTP client download with range headers');
            return;
        }
    
        if (!$this->requirePhpWebServer()) {
            self::markTestSkipped('The PHP built-in server could not be started and this test will not be run without it');
            return;
        }
    
        $testScript = 'http://' . $this->getPhpServerAddress() . '/fooFileDownload.php';
    
        $expectedHeaders = [
            'HTTP/1.1 206 Partial Content',
            'Content-Disposition: attachment; filename="foo.txt"',
            'Content-Transfer-Encoding: binary',
            'Accept-Ranges: bytes',
        ];
    
        $headers = [];
        exec('curl -s -o /dev/null --dump-header - --header "Range: bytes=0-2,8-12" ' . $testScript, $headers);
    
        self::assertNotEmpty($headers);
        $intersectedHeaders = array_intersect($expectedHeaders, $headers);
        self::assertCount(4, $intersectedHeaders);
        self::assertEquals($expectedHeaders, $intersectedHeaders);
    
        $this->assertOneArrayElementStartsBy('Content-Type: multipart/byteranges; boundary=', $headers);
        $this->assertOneArrayElementStartsBy('Content-Length: ', $headers);
    }
    
    protected function assertOneArrayElementStartsBy($string, array $array) {
        $found = 0;
        $length = strlen($string);
    
        if ($length == 0) {
            return;
        }
    
        foreach($array as $key => $item) {
            if (strlen($item) >= $length && strcasecmp(substr($item, 0, $length), $string) == 0) {
                ++$found;
            }
        }
    
        if ($found == 0) {
            $this->fail('No array value starts with ' . $string);
        }
        else if ($found > 1) {
            $this->fail($found . ' arrays starts with value ' . $string);
        }
    }
    
    public function testIfModifiedSinceHeader() {
        if (!$this->existsCommand('curl')) {
            self::markTestSkipped('Curl is required to test HTTP client download with range headers');
            return;
        }
        
        if (!$this->existsCommand('netstat')) {
            self::markTestSkipped('netstat is required to start a random PHP built-in webserver');
            return;
        }
    
        if (!$this->requirePhpWebServer()) {
            self::markTestSkipped('The PHP built-in server could not be started and this test will not be run without it');
            return;
        }
    
        $sinceStamp = time() - 14800;
        self::assertTrue(touch(__DIR__ . '/foo.txt', $sinceStamp));
    
        $testScript = 'http://' . $this->getPhpServerAddress() . '/fooFileDownload.php';
    
        $expectedHeaders = [
            'HTTP/1.1 206 Partial Content',
            'Content-Disposition: attachment; filename="foo.txt"',
            'Content-Transfer-Encoding: binary',
            'Accept-Ranges: bytes',
        ];
    
        // Test using GET verb
    
        $headers = [];
        $httpSinceDate = gmdate("D, d M Y H:i:s T", time());
        exec('curl -s --dump-header - --header "Range: bytes=0-2,8-12" --header "If-Modified-Since: ' . $httpSinceDate . '" ' . $testScript, $headers);
    
        self::assertNotEmpty($headers);
        $intersectedHeaders = array_intersect($expectedHeaders, $headers);
        self::assertCount(4, $intersectedHeaders, 'Not expected headers: ' . print_r($headers, true));
        self::assertEquals($expectedHeaders, $intersectedHeaders);
    
        // Test using HEAD verb
    
        $headers = [];
        exec('curl -s --head --dump-header - --header "Range: bytes=0-2,8-12" --header "If-Modified-Since: ' . $httpSinceDate . '" ' . $testScript, $headers);
    
        self::assertNotEmpty($headers);
        $intersectedHeaders = array_intersect($expectedHeaders, $headers);
        self::assertCount(4, $intersectedHeaders, 'Not expected headers: ' . print_r($headers, true));
        self::assertEquals($expectedHeaders, $intersectedHeaders);
    
        $expectedHeaders = [
            'HTTP/1.1 304 Not Modified',
        ];
    
        // Test using GET verb
    
        $headers = [];
        $httpSinceDate = gmdate("D, d M Y H:i:s T", $sinceStamp - 14800);
        exec('curl -s --dump-header - --header "If-Modified-Since: '. $httpSinceDate . '" ' . $testScript, $headers);
    
        self::assertNotEmpty($headers);
        $intersectedHeaders = array_intersect($expectedHeaders, $headers);
        self::assertCount(1, $intersectedHeaders, 'Missing headers from: ' . print_r($headers, true));
        self::assertEquals($expectedHeaders, $intersectedHeaders);
    
        // Test using HEAD verb
    
        $headers = [];
        $httpSinceDate = gmdate("D, d M Y H:i:s T", $sinceStamp - 14800);
        exec('curl -s --head --dump-header - --header "If-Modified-Since: '. $httpSinceDate . '" ' . $testScript, $headers);
    
        self::assertNotEmpty($headers);
        $intersectedHeaders = array_intersect($expectedHeaders, $headers);
        self::assertCount(1, $intersectedHeaders, 'Missing headers from: ' . print_r($headers, true));
        self::assertEquals($expectedHeaders, $intersectedHeaders);
    }
    
    public function testDownloadThrotling() {
        if (!$this->existsCommand('curl')) {
            self::markTestSkipped('Curl is required to test HTTP client download with range headers');
            return;
        }
        
        if (!$this->existsCommand('netstat')) {
            self::markTestSkipped('netstat is required to start a random PHP built-in webserver');
            return;
        }
    
        if (!$this->requirePhpWebServer()) {
            self::markTestSkipped('The PHP built-in server could not be started and this test will not be run without it');
            return;
        }
    
        $testScript = 'http://' . $this->getPhpServerAddress() . '/randomFileDownload.php';
        $output = [];
    
        $bytesPerMegaByte = 1024*1024;
        $tempFile = tempnam('/tmp/', mt_rand());
    
        $timeBefore = microtime(true);
        // Parameter file_size unit is Megabyte. Download 1MB
        exec('curl -s -o ' . $tempFile . ' "' . $testScript . '?file_size=1"', $output);
        $timeAfter = microtime(true);
        
        $timeDif = $timeAfter - $timeBefore;
        $bytesPerSecond = (int)round($bytesPerMegaByte/$timeDif, 0);
    
        self::assertEmpty($output, 'Got unexpected output ' . print_r($output, true));
        $fileSize = filesize($tempFile);
        self::assertEquals($bytesPerMegaByte, $fileSize, "File $tempFile has $fileSize bytes but was expected to have $bytesPerMegaByte bytes");
    
        $timeThrottledBefore = microtime(true);
        // Try to download at 150 KB/s, ideally it should last from 6 to 10 seconds.
        exec('curl -s -o ' . $tempFile . ' "' . $testScript . '?file_size=1&throttle=' . (200*1024) . '"', $output);
        $timeThrottledAfter = microtime(true);
        
        $timeThrottledDif = $timeThrottledAfter - $timeThrottledBefore;
        $bytesPerSecondThrottled = (int)round($bytesPerMegaByte/$timeThrottledDif, 0);
    
        self::assertEmpty($output, 'Got unexpected output ' . print_r($output, true));
        $fileSize = filesize($tempFile);
        self::assertEquals($bytesPerMegaByte, $fileSize, "File $tempFile has $fileSize bytes but was expected to have $bytesPerMegaByte bytes");
    
        // Throttling must have the effect of slowing down the download
        self::assertGreaterThan($timeDif, $timeThrottledDif, "Download without throttling ended in $timeDif and is less than $timeThrottledDif");
        self::assertGreaterThan($bytesPerSecondThrottled, $bytesPerSecond, "Download with throttling speed was $bytesPerSecondThrottled bytes/s and is less than $bytesPerSecond bytes/s");
    
        unlink($tempFile);
    }
}