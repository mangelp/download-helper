<?php
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
    public function parseRangeHeader($rangeHeader = null) {
        
        if ($rangeHeader === null && isset($_SERVER['HTTP_RANGE'])) {
            $rangeHeader = $_SERVER['HTTP_RANGE'];
        }
        
        if (!$rangeHeader) {
            return false;
        }
        
        $parts = explode(',', $rangeHeader);
        $ranges = [];
        
        foreach($parts as $part) {
            if ($part == '-') {
                return false;
            }
        
            if ($part[0] == '-') {
                $part = "0$part";
            }
        
            if ($part[strlen($part) - 1] == '-') {
                $part .= "" . $this->size - 1;
            }
        
            $rangeParts = explode('-', $part);
        
            if (count($rangeParts) != 2) {
                return false;
            }
        
            $ranges[]= ['start' => $rangeParts[0], 'end' => $rangeParts[1], 'length' => $rangeParts[1] - $rangeParts[0] + 1];
        }
        
        usort($ranges, function($a, $b){
            return ($a['start'] - $b['start']) % 2;
        });
        
        return $ranges;
    }
    
    public function joinContinuousRanges(array &$ranges) {
        foreach($ranges as $pos => $range) {
            if ($pos == 0) {
                continue;
            }
            
            $prev = $ranges[$pos-1];
            
            if ($prev['end'] == $range['start'] - 1) {
                $ranges[$pos-1]['end'] = $range['end'];
                $ranges[$pos-1]['length'] += $range['length'];
                unset($ranges[$pos]);
            }
        }
    }
}
