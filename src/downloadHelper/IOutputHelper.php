<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

/**
 * Models the operations required to write the HTTP response back to the client.
 *
 * Implementors must not raise any kind of notice but can throw exceptions at will.
 *
 * Before sending headers any previous header must be removed.
 */
interface IOutputHelper {
    
    /**
     * Sets if headers are disabled so no header will be send before the data and no header can be
     * added.
     *
     * @param bool $value
     */
    public function setHeadersDisabled($value);
    
    /**
     * Gets if headers are disabled so no header will be send before the data and no header can be
     * added.
     *
     * @return bool
     */
    public function isHeadersDisabled();
    
    /**
     * Gets if headers have been already sent by the implementation. This does not check if
     * headers have been sent or not by PHP.
     *
     * No more headers can be set if this method returns true and an exception might be thrown if
     * tried to do so.
     *
     * @return bool
     */
    public function areHeadersSent();
    
    /**
     * Sets the given response header.
     *
     * If the headers have been already sent it must throw a runtime exception.
     *
     * @param string $text
     * @throws \RuntimeException if the headers have been already sent by this implementation.
     */
    public function addHeader($text);
    
    /**
     * Returns an array with all headers already set
     * @return array
     */
    public function getHeaders();
    
    /**
     * Clears all the headers set.
     *
     * @throws \RuntimeException If the headers have been already sent
     */
    public function clearHeaders();
    
    /**
     * Writes to the output stream but it does not flushes it.
     *
     * If the headers have not been send yet it must send them before any data output.
     * If the headers have been already sent by PHP then a runtime exception must be thrown.
     *
     * @param string $data
     * @throws \RuntimeException if the headers have been already sent by PHP
     * @throws ConnectionAbortedErrorException if the connection have been aborted
     * @return int number of bytes written
     */
    public function write($data);
    
    /**
     * Flushes the already writen contents to output.
     *
     * It must not end output and must not raise any notice or warning, but it can throw runtime
     * exceptions.
     *
     * if the headers are pending to be sent this method must always check it and send them before
     * flushing the output.
     */
    public function flush();
    
    /**
     * Ends sending output.
     */
    public function end();
    
    /**
     * Returns true if the output stream is closed and any write operation will fail.
     *
     * It will return true only if the connection have been aborted or terminated.
     */
    public function isOutputClosed();
}
