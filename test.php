 <?php 

require ('phpivotal.php');

//Let's setup some static variables, so we can unit test the basic functions. 

$token = 'Put Pivotal Token Here';
$projectid = 'Pivotal Project ID';
$storyid = 'Pivotal Story';

$pivotal = new phpivotal($token);
$args = array(
				'name'			=> 'Story Test',
				'owned_by_id' 	=> 927981,
			);
$data = json_decode($pivotal->addStory($projectid, $args);
 ?>