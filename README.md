[![Build Status](https://travis-ci.org/mangelp/download-helper.svg?branch=master)](https://travis-ci.org/mangelp/download-helper)

# PHP download-helper  #

## What is this repository for? ##

OOP download helper that supports the Range header to allow downloads to be resumed. 

## What is supported? ##

 * Download-related headers: Range, If-Modified-Since.
 * Download of files and data stored in memory.
 * Specify a maximum size in file read operations to control memory usage when downloading. 
 * Decouple implementation of output writting (IOutputHelper) and resource reading 
   (IDownloadableResource) as separate interfaces with default implementations for standard script
   output (echo) and reading from files and strings.
 * Set headers to control caching and modification validation by optional use of etag and las
   modified dates. These are automatically generated for files and optional for in-memory data.
 * Set a time limit before each segment download and reset it to the default after segment data 
   output ends.
 * Multi-part download (multipart/byteranges).
 * Data output throttling to a given amount of bytes per second.

## What is not supported? ##

 * Real-world usage (not tested in production environments).

## Alternatives ##

If you need a download helper you should first consider using pecl http extension, but if it that is
not an option you can take a look at these repos that I checked while writting my own helper:

 * https://github.com/TimOliver/PHP-Framework-Classes/blob/master/download.class.php
 * https://github.com/pomle/php-serveFilePartial/blob/master/ServeFilePartial.inc.php
 * https://github.com/diversen/http-send-file

## Testing ##

PHPUnit is a development dependency but you will also need to have curl installed to run the tests.
Some tests start the built-in php webserver with a random port between 10999 and31999 and will try 
to download a test file and the headers using curl.


## TODO ##

 * If pecl http extension is available use it. 
 * Real-world testing.
 
 