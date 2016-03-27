# README - download-helper #

## What is this repository for? ##

OOP download helper that supports the Range header to allow downloads to be resumed. 

## What is supported and tested? ##

 * Download a file with a given name.
 * Download a resource stored as an in-memory string.
 * Guess the mime type of the resource if not set.
 * Decouple implementation of output writting (response) and resource reading as separate interfaces with default implementations.

## What is not supported and tested? ##

 * Multiple range downloads (multipart response).
 * Big file downloads.
 * Chunked download (Flush the data written after a given amount is written to the output).
 * Throttling (sleep between chunks written).
 * Implement caching/expiration options to programatically control or disable (so you can set yourself) those generated headers.

## Alternatives ##

If you need a download helper you can take a look at these repos that I checked while writting my
own helper:

 * https://github.com/TimOliver/PHP-Framework-Classes/blob/master/download.class.php
 * https://github.com/pomle/php-serveFilePartial/blob/master/ServeFilePartial.inc.php
 * https://github.com/diversen/http-send-file
