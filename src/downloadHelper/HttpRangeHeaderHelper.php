<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/.
 * (c) Miguel Angel Perez <mangelp[ATT]gmail[DOTT]com>
 */

namespace mangelp\downloadHelper;

/**
 * Helper that handles the HTTP range header and returns an array of ranges.
 */
class HttpRangeHeaderHelper {
    /**
     * Parses the header and return an array of ranges or false if it is not valid
     *
     * The returned ranges are sorted first by the start byte ascendent and then when
     * the first byte is the same they are sorted by the last byte ascendent.
     *
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
        
        $parameters = explode(';', trim($rangeHeader));
        
        if (count($parameters) < 1) {
            return false;
        }
        
        $allRanges = [];
        
        foreach($parameters as $parameter) {
            
            $rangeHeaderParts = explode('=', trim($parameter));
        
            if (count($rangeHeaderParts) != 2
                    || strtolower(trim($rangeHeaderParts[0])) != 'bytes'
                    || empty($rangeHeaderParts[1])) {
                        
                continue;
            }
            
            $ranges = $this->parseRangeHeaderParameterValue($rangeHeaderParts[1], $defaultStart, $defaultEnd);
            
            // Invalid bytes parameter, ignore it
            if ($ranges == false) {
                continue;
            }
            
            $allRanges = array_merge($allRanges, $ranges);
        }
        
        if (empty($allRanges)) {
            return false;
        }
        else {
            usort($allRanges, function($ra, $rb){
                $cmp = $ra['start'] - $rb['start'];
                
                if ($cmp == 0) {
                    $cmp = $ra['end'] - $rb['end'];
                }
                
                return $cmp;
            });
        }
        
        return $allRanges;
    }
    
    /**
     * Parses a single range parameter value string like "0-12,23-45".
     *
     * @param string $headerParameterValue
     * @param int $defaultStart
     * @param int $defaultEnd
     */
    protected function parseRangeHeaderParameterValue($headerParameterValue, $defaultStart, $defaultEnd) {
        $parts = explode(',', $headerParameterValue);
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
        
            if ($start > $end) {
                return false;
            }
        
            $ranges[]= ['start' => (int)$rangeParts[0], 'end' => $end, 'length' => $length];
        }
        
        return $ranges;
    }
    
    public function joinContinuousRanges(array $ranges) {
        
        // sort ascending first by start byte and then by end byte
        usort($ranges, function($ra, $rb){
            $cmp = $ra['start'] - $rb['start'];
        
            if ($cmp == 0) {
                $cmp = $ra['end'] - $rb['end'];
            }
        
            return $cmp;
        });
        
        $length = count($ranges);
        $i = 1;

        while($i < $length) {
            if ($this->isContiguous($ranges[$i-1], $ranges[$i])
                    || $this->isOverlaped($ranges[$i-1], $ranges[$i])) {
                // Ranges are contiguous or overlap, join them and decrease size
                $this->joinOverlapped($ranges[$i-1], $ranges[$i]);
                array_splice($ranges, $i, 1);
                --$length;
            } else {
                // Ranges are not contiguous nor overlap, increase pointer to next element
                ++$i;
            }
        }
        
        return array_values($ranges);
    }
    
    private function isContiguous($rangeA, $rangeB) {
        $dif = $rangeB['start'] - $rangeA['end'];
        
        return $dif >= 0 && $dif <= 1;
    }
    
    private function isOverlaped($rangeA, $rangeB) {
        return ($rangeA['start'] >= $rangeB['start'] && $rangeA['start'] <= $rangeB['end'])
            || ($rangeA['end'] >= $rangeB['start'] && $rangeA['end'] <= $rangeB['end'])
            || ($rangeB['start'] >= $rangeA['start'] && $rangeB['start'] <= $rangeA['end'])
            || ($rangeB['end'] >= $rangeA['start'] && $rangeB['end'] <= $rangeA['end']);
    }
    
    /**
     * Joins two ranges taking as first byte the minimum of the start bytes and as end byte the
     * maximum of the end bytes.
     *
     * The first array is modified to include the second one range.
     *
     * @param array $rangeA first array passed by reference.
     * @param array $rangeB Second array passed by copy.
     */
    private function joinOverlapped(array &$rangeA, array $rangeB) {
        $rangeA['start'] = min([$rangeA['start'], $rangeB['start']]);
        $rangeA['end'] = max([$rangeA['end'], $rangeB['end']]);
        $rangeA['length'] = $rangeA['end'] - $rangeA['start'] + 1;
    }
}
