<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

$path = dirname(__DIR__) . '/src/downloadHelper';
$composerAutoloadPath = dirname(__DIR__) . '/vendor/autoload.php';

include $composerAutoloadPath;

include "$path/IOutputHelper.php";
include "$path/IDownloadableResource.php";
include "$path/FileResource.php";
include "$path/DownloadOutputHelper.php";
include "$path/HttpRangeHeaderHelper.php";
include "$path/DownloadHelper.php";
