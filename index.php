<?php

$reference = htmlspecialchars($_GET['action']);
$row = flood::getUserRow($reference);
$ini = flood::getIniSection($ary = array ('action' => $reference));

$max_posts = $ini['post_max'];

$interval = $ini['post_interval'];
$post_next = strtotime($row['updated']) + $interval; 

$time_to_next_post = $post_next - time();

if ($time_to_next_post < 0) {
    echo "You should be able to post";
    return;
}

$res = time::getSecsDivided($time_to_next_post);

//print_r($row );

echo "Max Amount of posts is $max_posts per $interval secs\n";
echo "Your post counter will be reset in $res[minutes] minutes and $res[seconds] seconds";
