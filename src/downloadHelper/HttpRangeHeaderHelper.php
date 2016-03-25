<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) 2009-2015 Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

/**
 * Helper that handles the HTTP range header and returns an array of ranges.
 */
class HttpRangeHeaderHelper {
    /**
     * Parses the header and return an array of ranges or false if it is not valid
     * @param string $rangeHeader
     * @return boolean|array
     */
    public function parseRangeHeader($rangeHeader = null, $defaultStart = 0, $defaultEnd = null) {
        
        if ($rangeHeader === null && isset($_SERVER) && isset($_SERVER['HTTP_RANGE'])) {
            $rangeHeader = $_SERVER['HTTP_RANGE'];
        }
        
        if (!$rangeHeader) {
            return false;
        }
        
        $rangeHeaderParts = explode('=', trim($rangeHeader));
        
        if (count($rangeHeaderParts) != 2 || strtolower(trim($rangeHeaderParts[0])) != 'bytes'
                || empty($rangeHeaderParts[1])) {
                    
            return false;
        }
        
        $parts = explode(',', $rangeHeaderParts[1]);
        $ranges = [];
        
        foreach($parts as $part) {
            if ($part == '-') {
                return false;
            }
        
            if ($part[0] == '-') {
                $part = "$defaultStart$part";
            }
            else if (substr($part, -1) == '-') {
                $part .= $defaultEnd;
            }
        
            $rangeParts = explode('-', $part);
        
            if (count($rangeParts) != 2) {
                return false;
            }
            
            $start = (int)$rangeParts[0];
            $end = (int)$rangeParts[1];
            $length = $end - $start + 1;
            
            if ($length < 1) {
                return false;
            }
        
            $ranges[]= ['start' => (int)$rangeParts[0], 'end' => $end, 'length' => $length];
        }
        
        return $ranges;
    }
    
    public function joinContinuousRanges(array $ranges) {
        $length = count($ranges);
        $i = 1;

        while($i < $length) {
            if ($ranges[$i-1]['end'] == ($ranges[$i]['start'] - 1)) {
                $ranges[$i-1]['end'] = $ranges[$i]['end'];
                $ranges[$i-1]['length'] += $ranges[$i]['length'];
                array_splice($ranges, $i, 1);
                --$length;
            }
            else {
                ++$i;
            }
        }
        
        return array_values($ranges);
    }
}
