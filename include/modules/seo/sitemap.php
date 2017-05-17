<?php

/* * ********************************
 * Module Seo @ CaguCT.com 2017
  -------------------------------------------------
 * **********************************
 * Include sitemap in the site
  -------------------------------------------------
 * **********************************
 * ./seo/sitemap.php - Sitemap module
  -------------------------------------------------
 * ******************************** */

/**
 * Generation of page of an error at access out of system
 */

if( !defined('HY_KEY') )
{
    header("HTTP/1.1 404 Not Found");
    exit(file_get_contents('./404.html'));
}
// change header for xml
header("Content-Type: text/xml; charset=utf-8");

// include class for work
include_once dirname(__FILE__) . '/class/sitemap.class.php';
$sitemap = new Sitemap();

// declare vars
$_GET['type'] = !empty($_GET['type']) ? $_GET['type'] : 'index';
$data = array();

// get sitemap list for index sitemap
if( $_GET['type'] === 'index' )
{
    $data['list'] = $sitemap->getSitemapList();
}
elseif( $_GET['type'] === 'other' )
{
    $data['list'] = $sitemap->getOtherList();
}
elseif( array_search($_GET['type'], $sitemap->sitemap_array) !== false )
{
    $data['list'] = $sitemap->getlist($_GET['type']);
}
else
{
    header("HTTP/1.1 404 Not Found");
    header("Content-Type: text/html; charset=utf-8");
    exit(file_get_contents('./404.html'));
}

$display = 'sitemap';
$smarty->assign('data', $data);