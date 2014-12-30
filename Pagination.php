<?php
// Copyright 2015 The Smpe Authors. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

class Smpe_Pagination
{
    public static function full($url, $rows = 0, $pageSize = 30, $filter = null) {
        if($rows == 0) return '';

        if(isset($filter['p'])) {
            unset($filter['p']);
        }

        if($filter === null) $filter = $_GET;

        $pageIndex = isset($filter['PageIndex']) ? $filter['PageIndex'] : 0;

        $filter['PageIndex'] = 0;
        $zeroPage = $url.'?'.http_build_query($filter);

        $returnValue = '<ul class="pagination">';

        // First page
        $returnValue .= '<li><a href="' . $zeroPage . '" title="First page">«</a></li>';

        // Previous
        $filter['PageIndex'] = (($pageIndex - $pageSize) >= 0 ? $pageIndex - $pageSize : 0);
        $backUrl = $url.'?'.http_build_query($filter);
        $returnValue .= '<li><a href="' . ($filter['PageIndex'] == 0 ? $zeroPage : $backUrl) . '" title="Previous">‹</a></li>';

        // Page list
        $pages = (int)($rows/$pageSize) + (($rows % $pageSize) > 0 ? 1 : 0);
        $currentIndex = (int)($pageIndex/$pageSize);
        $pagesStart = ($currentIndex > 3 ? ($currentIndex - 3) : 0);
        $pagesEnd = ($pages > ($currentIndex + 3) ? ($currentIndex + 3) : $pages);

        if($pagesStart > 0){
            $returnValue .= '<li><a href="javascript:;">...</a></li>';
        }

        // Start display pager no.
        for($i = $pagesStart; $i < $pagesEnd; $i++) {
            if($pageIndex == ($i * $pageSize)) {
                $returnValue .= sprintf("<li class=\"active\"><a href=\"#\">%d <span class=\"sr-only\">(current)</span></a></li>", $i + 1);
            }
            else {
                $filter['PageIndex'] = ($i * $pageSize);
                $currentUrl = $url.'?'.http_build_query($filter);
                $returnValue .= sprintf("<li><a href=\"%s\">%d</a> </li>", ( $i == 0 ? $zeroPage : $currentUrl ), $i + 1);
            }
        }

        if($pagesEnd < $pages){
            $returnValue .= '<li><a href="javascript:;">...</a></li>';
        }

        // Next
        $filter['PageIndex'] = (($pageIndex + $pageSize) < $rows ? $pageIndex + $pageSize : $pageIndex);
        $forwardUrl = $url.'?'.http_build_query($filter);
        $returnValue .= '<li><a href="' . ( $filter['PageIndex'] == 0 ? $zeroPage : $forwardUrl ) . '" title="Next">›</a></li>';

        // Last page
        $filter['PageIndex'] = (($pageIndex + $pageSize) < $rows ? ($pages - 1) * $pageSize : $pageIndex);
        $endUrl = $url.'?'.http_build_query($filter);
        $returnValue .= '<li><a href="' . ($filter['PageIndex'] == 0 ? $zeroPage : $endUrl ) . '" title="Last page">»</a></li>';

        $returnValue .= '</ul>';

        return $returnValue;
    }

    public static function simple() {

    }
}