<?php

$reference = htmlspecialchars($_GET['action']);
$row = flood::getUserRow($reference);
$ini = flood::getIniSection($ary = array ('action' => $reference));

$max_posts = $ini['post_max'];

$interval = $ini['post_interval'];
$post_next = strtotime($row['updated']) + $interval; 

$time_to_next_post = $post_next - time();

if ($time_to_next_post < 0) {
    html::headline(lang::translate('flood: You can post agian'));
    echo lang::translate('flood: You should be able to post');
    return;
}

$res = time::getSecsDivided($time_to_next_post);

//print_r($row );


html::headline(lang::translate('flood:: exceed time limit title'));
echo lang::translate('flood: Max Amount of posts is');
echo $max_posts; 
echo lang::translate('flood: per');
echo $interval;
echo lang::translate('flood: seconds'); 
echo "<br />\n";
//echo "Max Amount of posts is $max_posts per $interval secs\n";
echo lang::translate('flood: Your post counter will be reset in');
echo $res['minutes'];
echo lang::translate('flood: minutes and'); 
echo $res['seconds'];
echo lang::translate('flood: seconds'); 

