<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

include(dirname(__DIR__) . '/bootstrap.php');

use mangelp\downloadHelper\DownloadOutputHelper;
use mangelp\downloadHelper\DownloadHelper;
use mangelp\downloadHelper\RandomFileResource;

$fileSize = (int)$_REQUEST['file_size'] * 1024 * 1024;
$throttling = (int)$_REQUEST['throttle'];

$output = new DownloadOutputHelper(true);
// Random data with 256 characters
$resource = new RandomFileResource($fileSize);
$downloadHelper = new DownloadHelper($output, $resource);

$downloadHelper->setByteRangesEnabled(true);
$downloadHelper->setDisposition(DownloadHelper::DISPOSITION_ATTACHMENT);
$downloadHelper->setDownloadFileName('foo.txt');
$downloadHelper->setCacheMode(DownloadHelper::CACHE_NEVER);
$downloadHelper->setMaxBytesPerSecond($throttling);

$requestMethod = isset($_SERVER) && isset($_SERVER['REQUEST_METHOD']) ?
    strtolower($_SERVER['REQUEST_METHOD']):
    'none';

if ($requestMethod == 'get' || $requestMethod == 'post') {
    $downloadHelper->download();
}
else {
    $downloadHelper->headers();
}