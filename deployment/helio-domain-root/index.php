<?php
/**
 * YangLocal public front controller.
 * WordPress core stays in /wp while the public site is served from domain root.
 */

define( 'WP_USE_THEMES', true );

require __DIR__ . '/wp/wp-blog-header.php';
