<?php
$slc_sitemap_structure = '<!DOCTYPE html>
<html>
<head>';
// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
$slc_sitemap_structure .= '<link rel="stylesheet" type="text/css" href="../assets/css/sitemap.css">
</head>
<body>
<div class="container">
<h1 class="title">Sitemap of home page</h1>';
if ( $slc_results ) {
	$slc_sitemap_structure .= ' <ul class="sitemap-list"> ';
	foreach ( $slc_results as $slc_result ) {
		$slc_sitemap_structure .= '<li><a href="' . $slc_result . '">' . $slc_result . '</a></li>';
	}
	$slc_sitemap_structure .= '</ul>';
} else {
	$slc_sitemap_structure .= '<p>No crawl results available.</p>';
}
$slc_sitemap_structure .= '</div></body>
</html>';
