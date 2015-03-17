<?php 
require('phpivotal.php');

// Let's setup some static variables, so we can unit test the basic functions. 
$toke		= 'Put Pivotal Token Here';
$projecti	= 'Pivotal Project ID';
$storyid	= 'Pivotal Story';

$pivotal = new phpivotal($token);
$args = [
		'name'			=> 'Story Test',
		'owned_by_id'	=> 927981,
];
$data = json_decode($pivotal->addStory($projectid, $args);

// We should do stuff with $data here, let's just debug it :D
print_r($data);
