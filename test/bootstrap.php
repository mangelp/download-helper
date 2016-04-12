<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

$path = dirname(__DIR__) . '/src/downloadHelper';
$pathTest = dirname(__DIR__) . '/test/downloadHelper';

include "$path/IOutputHelper.php";
include "$path/IDownloadableResource.php";
include "$path/FileResource.php";
include "$path/StringResource.php";
include "$path/DownloadOutputHelper.php";
include "$path/HttpRangeHeaderHelper.php";
include "$path/DownloadHelper.php";

include "$pathTest/RandomFileResource.php";
include "$pathTest/OutputStorageHelper.php";
