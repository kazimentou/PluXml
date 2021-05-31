<?php

if (!defined('PLX_ROOT')) {
    exit;
}

include 'header.php';
include 'posts.php';

$plxShow->artFeed('rss', $plxShow->catId(), '<span><a class="rss" href="#feedUrl" title="#feedTitle">#feedName</a></span>');

include 'footer.php';
