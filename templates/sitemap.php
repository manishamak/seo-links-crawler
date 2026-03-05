<!DOCTYPE html>
<html>
<head>
<?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
<link rel="stylesheet" type="text/css" href="../assets/css/sitemap.css">
</head>
<body>
<div class="container">
	<h1 class="title"><?php echo esc_html__( 'Sitemap of home page', 'seo-links-crawler' ); ?></h1>
	<?php if ( ! empty( $slc_results ) ) : ?>
		<ul class="sitemap-list">
			<?php foreach ( $slc_results as $slc_result ) : ?>
				<li><a href="<?php echo esc_url( $slc_result ); ?>"><?php echo esc_html( $slc_result ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p><?php echo esc_html__( 'No crawl results available.', 'seo-links-crawler' ); ?></p>
	<?php endif; ?>
</div>
</body>
</html>
