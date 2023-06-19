<?php
$sitemap_structure = '<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <article id="post>
            <header class="entry-header">
                <h1 class="entry-title">Sitemap</h1>
            </header>
            <div class="entry-content">';
if ($results) {
    $sitemap_structure .= ' <ul> ';
    foreach ($results as $result) {
        $sitemap_structure .= '<li><a href="' . $result . '">' . $result . '</a></li>';
    }
    $sitemap_structure .= '</ul>';
} else {
    $sitemap_structure .= '<p>No crawl results available.</p>';
}
$sitemap_structure .= '</div>
        </article>
    </main><!-- #main -->
    </div><!-- #primary -->';