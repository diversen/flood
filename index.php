<?php

include_once "timeanddate.php";

$row = flood::getUserRow();
$max_posts = config::getModuleIni('flood_post_max');

$interval = config::getModuleIni('flood_post_interval');
$post_next = strtotime($row['updated']) + $interval; 
//return;
$time_to_next_post = $post_next - time();

if ($time_to_next_post < 0) {
    echo "You should be able to post";
    return;
}

$res = timeAndDate::getSecsDivided($time_to_next_post);

echo "Max Amount of posts is $max_posts per $interval secs\n";
echo "Your post counter will be reset in $res[minutes] and $res[seconds]";