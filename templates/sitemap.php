<?php
$sitemap_structure = '<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" type="text/css" href="../assets/css/sitemap.css">
</head>
<body>
<div class="container">
<h1 class="title">Sitemap of home page</h1>';
if ( $results ) {
	$sitemap_structure .= ' <ul class="sitemap-list"> ';
	foreach ( $results as $result ) {
		$sitemap_structure .= '<li><a href="' . $result . '">' . $result . '</a></li>';
	}
	$sitemap_structure .= '</ul>';
} else {
	$sitemap_structure .= '<p>No crawl results available.</p>';
}
$sitemap_structure .= '</div></body>
</html>';
