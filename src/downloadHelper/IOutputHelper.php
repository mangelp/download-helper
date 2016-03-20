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
 */
interface IOutputHelper {
    
    /**
     * Gets if headers have been already sent (by the implementation).
     *
     * No more headers can be set if this method returns true and an exception might be thrown if
     * tried to do so.
     */
    public function areHeadersSent();
    
    /**
     * Sets the given response header.
     * @param string $text
     */
    public function addHeader($text);
    
    /**
     * Writes to the output stream
     * @param string $data
     */
    public function write($data);
    
    /**
     * Flushes the already writen contents
     */
    public function flush();
}