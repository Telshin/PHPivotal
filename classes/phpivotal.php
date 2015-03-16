<?php
/**
 * PHPivtoal
 * PHPivotal Class File
 *
 * @author: 	Telshin
 * @license: 	GNU GPLv3.0
 * @package: 	PHPivotal
 * @link		http://www.telshin.com/
 *
**/

class phpivotal{
	/**
	 * @var 	Pivtoal API Token
	 */
	private $token;

	/**
	 * @var 	Pivotal Username
	 */
	private $username;

	/**
	 * @var 	Pivotal Password
	 */
	private $password;

	/**
	 * @var 	Base URL for the Pivotal API
	 */
	private $base = 'https://www.pivotaltracker.com/services/v5';


	public function __construct($token = null, $username = null, $password =null){
		//If we have no token, let's go fetch the token on the account. 
		if(!$token){
			$this->setToken($this->verifyToken($this->username, $this->password, $this->ssl));
		} else {
			$this->setToken(htmlspecialchars($token));
		}
	}

	private function getUsername() {
		return $this->username;
	}

	private function setUsername($username) {
		$this->username = $username;
	}

	private function getPassword() {
		return $this->password;
	} 

	private function setPassword($password) {
		$this->password = $password;
	}

	private function getToken() {
		return $this->token;
	}

	private function setToken($token) {
		$this->token = $token;
	}

/* Curl Function */
	private function curlPivotal($method, $job, $data = null, $auth = false, $debug = false){
		//Build the URL for CURL
		$job = str_replace( '&amp;', '&', urldecode(trim($job)));

		//Let's setup CURL to get our information
		$cp = curl_init($job);
		curl_setopt($cp, CURLOPT_FOLLOWLOCATION, 1); //Follow Redirects
		curl_setopt($cp, CURLOPT_RETURNTRANSFER, 1); //Return Transfer as a string

		//Let's get some methods determined for CURL
		switch ($method){
			case 'POST':
				curl_setopt($cp, CURLOPT_POST, 1);
				curl_setopt($cp, CURLOPT_POSTFIELDS, $data);
				break;
			case 'PUT':
				curl_setopt($cp, CURLOPT_CUSTOMREQUEST, 'PUT');
				break;
			case 'DELETE':
				curl_setopt($cp, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
			case 'GET':
			default:
				curl_setopt($cp, CURLOPT_HTTPGET, 1);
				break;
		}

		//Now let's get our token setup for CURL
		if($this->token){
			curl_setopt($cp, CURLOPT_HTTPHEADER, array('X-TrackerToken: ' . $this->token));
		}

		//Looks like we need to do some authentication

		/*//Let's throw some SSL into the options.
		if($this->ssl == true){
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		} else {
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		}*/
		if ($debug === true) {
			curl_setopt($cp, CURLINFO_HEADER_OUT, 1);
		}

		$data = curl_exec($cp);

		if ($debug === true) {
			$lastRequestInfo = curl_getinfo($cp);
		}

		//Close CURL if there are no errors, if error, let's see it.
		if(curl_errno($cp)){
			return curl_errno($cp);
		} else {
			curl_close($cp);
		}

		return $data;
	}

/* Token Retrievel Functions */

	private function verifyToken($username, $password, $ssl){
		$url = $this->base.'/tokens/active';

		//curl the information
		$token_array = $this->curlPivotal('GET', $url, true);
		$token = $token_array['token']['guid'];

		return $this->verifyData($token);
	}

/* Feed Functions */
	public function getActivity($projectid = null, $arguements = null){
		//Let's get the arguements ready for the URL
		if($arguements){
			$argurl = $this->curlArguments($arguements);
		}	

		//Setting up pieces of the URL depending on project.
		if($projectid){
			$tmp = '/projects/'.$projectid.'/activity';
		} else {
			$tmp = '/me/activity';
		}

		//Putting together the rest of the URL
		$url = $this->base.$tmp.($arguements ? '/'.implode('&', $argurl) : '');

		//Let's CURL that activity Data
		$data = $this->curlPivotal('GET', $url);

		return $this->verifyData($data);
	}

/* Project Functions */

	public function getProject($projectid = null){
		//Setting up the URL
		$url = $this->base.'/projects'.($projectid ? '/'.intval($projectid) : '');

		//Time to CURL
		$data = $this->curlPivotal('GET', $url);
		
		return $this->verifyData($data);
	}

	public function addProject(){

	}

/* Membership Functions */

	public function getMembership($projectid, $memberid = null){
		//Setting up the URL
		$task = '/projects/'.intval($projectid).'/memberships'.($memberid ? '/'.intval($memberid) : '');

		$job = $this->buildUrl($task);

		//Time to CURL the Member
		$data = $this->curlPivotal('GET', $job);

		return $this->verifyData($data);
	}

	public function addMembership($projectid){

	}

	public function removeMembership($projectid, $memberid){
		//Setting up the URL
		$url = $this->base.'/projects/'.intval($projectid).'/memberships/'.$memberid;

		//Time to DEL the Member
		$data = $this->curlPivotal('DELETE', $url);

		return $this->verifyData($data);
	}

/* Iteration Functions */

	public function getIteration($projectid, $group, $arguements = null){
		//Setting up the URL
		$endpoint = '/projects/'.intval($projectid).'/iterations?'.$group;

		$url = $this->buildUrl($endpoint);

		//Time to get the Iteration
		$data = $this->curlPivotal('GET', $url, $arguments);

		return $this->verifyData($data);

	}

/* Story Functions */

	public function getStory($projectid, $storyid = null){
		//Setting up the URL
		$endpoint = '/projects/'.intval($projectid).'/stories'.($storyid ? '/'.intval($storyid) : '');

		$url = $this->buildUrl($endpoint);
		// Time to get the Story
		$data = $this->curlPivotal('GET', $url);

		return $this->verifyData($data);
	}

	public function addStory($projectid, $args){
		$url = $this->base.'/projects/'.intval($projectid).'/stories';

		// Time to post the Story
		$data = $this->curlPivotal('POST', $url, $args);

		return $this->verifyData($data);
	}

	public function updateStory($projectid, $storyid){

	}

	public function deleteStory($projectid, $storyid){
		//Setting up the URL
		$url = $this->base.'/projects/'.intval($projectid).'/stories/'.intval($storyid);

		//Time to DEL this story
		$data = $this->curlPivotal('DELETE', $url);

		return $this->verifyData($data);
	}

	public function deliverStory($projectid){

	}

	public function moveStory($projectid, $storyid){

	}

/* Comment Functions */
	public function getComments($projectid, $storyid) {
		//setting up the URL
		$url = $this->base.'/projects/'.intval($projectid).'/stories/'.intval($storyid).'/comments';

		// TIme to GET the comments
		$data = $this->curlPivotal('GET', $url);

		return $this->verifyData($data);
	}

	public function postComments($projectid, $storyid, $args) {
		$url = $this->base.'/projects/'.intval($projectid).'/stories/'.intval($storyid).'/comments';

		// Time to post the Story
		$data = $this->curlPivotal('POST', $url, $args);

		return $this->verifyData($data); 
	}

/* Task Functions */

	public function getTask($projectid, $storyid, $taskid = null){
		//Seting up the URL 
		$url = $this->base.'/projects/'.intval($projectid).'/stories/'.intval($storyid).'/tasks'.($taskid ? '/'.intval($taskid) : '');

		//Time to retrieve some tasks
		$data = $this->curlPivotal('GET', $url);

		return $this->verifyData($data);
	}

	public function addTask($projectid, $storyid){

	}

	public function updateTask($projectid, $storyid, $taskid){

	}

	public function deleteTask($projectid, $storyid, $taskid){
		//Setting up the URL
		$url = $this->base.'/projects/'.intval($projectid).'/stories/'.intval($storyid).'/tasks/'.intval($taskid);

		//Time to DEL this story
		$data = $this->curlPivotal('DELETE', $url);

		return $this->verifyData($data);
	}

	/* Miscellaneous Functions */
	public function verifyData($data){
		if($data){
			return $data;
		} else {
			return false;
		}
	}

	/* URL Arguements for Curl URL */
	private function curlArguments($arguements){
		foreach($arguements as $key => $value){
			$args[] = $key.'='.$value;
		}
		return $args;
	}

	/**
	 * buildURL
	 */
	private function buildUrl($task, $arguments = null) {
		$url = $this->base.$task;
			if ($arguments) {
				$args = $this->curlArguments($arguments);
				$url = $url . '/' . implode('&', $args);
				$url = str_replace('&amp;', '&', urldecode(trim($url)));
			}
		return $url;
	}
}
?>