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
        $output = new DownloadOutputHelper(false);
        $resource = new FileResource(__DIR__ . '/foo.txt');
        $this->downloadHelper = new DownloadHelper($output, $resource);
    }
    
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    
    public function testWgetDownload() {
        
        $tmpDir = sys_get_temp_dir();
        $tmpFile = tempnam($tmpDir, '_downloadHelperTest_');
        
        $output = null;

        $cmd = "php -S localhost:28080 -t " . __DIR__ . " > /dev/null 2>&1 & \
echo $!";
        exec($cmd, $output);

        $pid = -1;
        
        if (is_array($output) && isset($output[0])) {
            $pid = (int)$output[0];
        }
        
        if ($pid > 0) {
        
            exec('wget -q -O ' . $tmpFile . ' http://localhost:28080/fooFileDownload.php');
            exec('ps a | grep php > /dev/null | grep ' . $pid . ' && kill -SIGTERM ' . $pid);
            
            self::assertTrue(is_file($tmpFile));
            
            $contents = file_get_contents($tmpFile);
            unlink($tmpFile);
            
            self::assertNotEmpty($contents);
            
            $testFileContents = file_get_contents(__DIR__ . '/foo.txt');
            
            self::assertEquals($testFileContents, $contents);
        }
        else {
            self::fail('Could not execute PHP built-in server');
        }
    }
}