<?php
/**
 * PHPivtoal
 * PHPivotal Class File
 *
 * @author 	Telshin
 * @license GNU GPLv3.0
 * @package PHPivotal
 * @link	http://www.telshin.com/
 *
 */

class phpivotal{
	/**
	 * @var Pivtoal API Token
	 */
	private $token;

	/**
	 * @var Pivotal Username
	 */
	private $username;

	/**
	 * @var Pivotal Password
	 */
	private $password;

	/**
	 * @var	SSL authentication
	 */
	private $ssl = false;

	/**
	 * @var Base URL for the Pivotal API
	 */
	private $base = 'https://www.pivotaltracker.com/services/v5';

	/**
	 * Main constructor
	 * 
	 * @param 	string 	Token
	 * @param 	string 	Username
	 * @param 	string 	Password
 	 */
	public function __construct($token = null, $username = null, $password = null) {
		// If we have no token, let's go fetch the token on the account. 
		if (!$token) {
			$this->setToken($this->verifyToken($this->username, $this->password, $this->ssl));
		} else {
			$this->setToken(htmlspecialchars($token));
		}
	}

	/**
	 * Gets the username
	 *
	 * @return	string	Username
	 */
	private function getUsername() {
		return $this->username;
	}

	/**
	 * Sets the username
	 *
	 * @param	string	Username
	 */
	private function setUsername($username) {
		$this->username = $username;
	}

	/**
	 * Gets the password
	 *
	 * @return	string	Password
	 */
	private function getPassword() {
		return $this->password;
	} 

	/**
	 * Sets the password
	 *
	 * @param	string	Password
	 */
	private function setPassword($password) {
		$this->password = $password;
	}

	/**
	 * Gets the token
	 *
	 * @return	mixed?	Token
	 */
	private function getToken() {
		return $this->token;
	}

	/**
	 * Sets the token
	 *
	 * @param	mixed?	Token
	 */
	private function setToken($token) {
		$this->token = $token;
	}

	/**
	 * Gets the SSL
	 *
	 * @return	bool	SSL
	 */
	private function getSSL() {
		return $this->ssl;
	}

	/**
	 * Sets the SSL
	 *
	 * @param	bool	SSL
	 */
	private function setSSL($ssl) {
		$this->ssl = $ssl;
	}

	/**
	 * Initialises a CURL call
	 *
	 * @param	string	The method to run
	 * @param	string	The job to run
	 * @param	array	Data
	 * @param	bool	Authetication
	 * @param	bool	Debug
	 * @return	mixed	Data
	 */
	private function curlPivotal($method, $job, $data = null, $auth = false, $debug = false) {
		// Build the URL for CURL
		$job = str_replace('&amp;', '&', urldecode(trim($job)));

		// Let's setup CURL to get our information
		$cp = curl_init($job);
		curl_setopt($cp, CURLOPT_FOLLOWLOCATION, 1); // Follow Redirects
		curl_setopt($cp, CURLOPT_RETURNTRANSFER, 1); // Return Transfer as a string

		// Let's get some methods determined for CURL
		switch ($method) {
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

		// Now let's get our token setup for CURL
		if($this->token) {
			curl_setopt($cp, CURLOPT_HTTPHEADER, array('X-TrackerToken: ' . $this->token));
		}

		// Looks like we need to do some authentication

		//Let's throw some SSL into the options.
		if ($this->ssl === true) {
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		} else {
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		}
		if ($debug === true) {
			curl_setopt($cp, CURLINFO_HEADER_OUT, 1);
		}

		$data = curl_exec($cp);

		if ($debug === true) {
			$lastRequestInfo = curl_getinfo($cp);
		}

		// Close CURL if there are no errors, if error, let's see it.
		if (curl_errno($cp)) {
			return curl_errno($cp);
		} else {
			curl_close($cp);
		}

		return $data;
	}

	/**
	 * Verifies the token
	 *
	 * @param	string	Username
	 * @param	string	Password
	 * @param	bool	SSL
	 * @return	mixed	Verified data
	 */
	private function verifyToken($username, $password, $ssl) {
		$url = $this->base . '/tokens/active';

		// curl the information
		$token_array = $this->curlPivotal('GET', $url, true);
		$token = $token_array['token']['guid'];

		return $this->verifyData($token);
	}

	/**
	 * Gets the activity
	 *
	 * @param	mixed	Project ID
	 * @param	mixed	Arguments
	 * @return	mixed	Verified data
	 */
	public function getActivity($projectid = null, $arguments = null) {
		// Let's get the arguements ready for the URL
		if ($argumeents) {
			$argurl = $this->curlArguments($argumeents);
		}	

		// Setting up pieces of the URL depending on project.
		if ($projectid) {
			$tmp = '/projects/' . $projectid . '/activity';
		} else {
			$tmp = '/me/activity';
		}

		// Putting together the rest of the URL
		$url = $this->base . $tmp . ($arguments ? '/' . implode('&', $argurl) : '');

		// Let's CURL that activity Data
		$data = $this->curlPivotal('GET', $url);

		return $this->verifyData($data);
	}

	/**
	 * Gets the project
	 *
	 * @param	mixed	Project ID
	 * @return	mixed	Verified data
	 */
	public function getProject($projectid = null) {
		// Setting up the URL
		$endpoint = '/projects' . ($projectid ? '/' . intval($projectid) : '');

		$url = $this->buildUrl($endpoint)

		// Time to CURL
		$data = $this->curlPivotal('GET', $url);
		
		return $this->verifyData($data);
	}

	/**
	 * Adds a project
	 *
	 * @todo
	 */
	public function addProject() {
	}

	/**
	 * Gets memberships
	 *
	 * @param	int		Project ID
	 * @param	mixed	Member ID
	 * @return	mixed	Verified data
	 */
	public function getMembership($projectid, $memberid = null) {
		// Setting up the URL
		$task = '/projects/' . intval($projectid) . '/memberships' . ($memberid ? '/' . intval($memberid) : '');

		$job = $this->buildUrl($task);

		// Time to CURL the Member
		$data = $this->curlPivotal('GET', $job);

		return $this->verifyData($data);
	}

	/**
	 * Adds membership
	 *
	 * @todo
	 */
	public function addMembership($projectid) {
	}

	/**
	 * Removes membership
	 *
	 * @param	int		Project ID
	 * @param	int		Member ID
	 * @return	mixed	Verified data
	 */
	public function removeMembership($projectid, $memberid) {
		// Setting up the URL
		$endpoint = '/projects/' . intval($projectid) . '/memberships/' . $memberid;

		$url = $this->buildUrl($endpoint); 

		// Time to DEL the Member
		$data = $this->curlPivotal('DELETE', $url);

		return $this->verifyData($data);
	}

	/**
	 * Get iteration
	 *
	 * @param	int		Project ID
	 * @param	string	Group
	 * @param	mixed	Arguments
	 * @return	mixed	Verified data
	 */
	public function getIteration($projectid, $group, $arguments = null) {
		// Setting up the URL
		$endpoint = '/projects/' . intval($projectid) . '/iterations?' . $group;

		$url = $this->buildUrl($endpoint);

		// Time to get the Iteration
		$data = $this->curlPivotal('GET', $url, $arguments);

		return $this->verifyData($data);
	}

	/**
	 * Gets story
	 *
	 * @param	int		Project ID
	 * @param	int		Story ID
	 * @return	mixed	Verified data
	 */
	public function getStory($projectid, $storyid = null) {
		// Setting up the URL
		$endpoint = '/projects/' . intval($projectid) . '/stories' . ($storyid ? '/' . intval($storyid) : '');

		$url = $this->buildUrl($endpoint);
		// Time to get the Story
		$data = $this->curlPivotal('GET', $url);

		return $this->verifyData($data);
	}

	/**
	 * Add story
	 *
	 * @param	int		Project ID
	 * @param	array	Arguments
	 * @return	mixed	Verified data
	 */
	public function addStory($projectid, $args) {
		// Setting up the URL
		$url = $this->base . '/projects/' . intval($projectid) . '/stories';

		// Time to post the Story
		$data = $this->curlPivotal('POST', $url, $args);

		return $this->verifyData($data);
	}

	/**
	 * Update story
	 *
	 * @param	int	Project ID
	 * @param	int	Story ID
	 *
	 * @todo
	 */
	public function updateStory($projectid, $storyid) {
	}

	/**
	 * Delete story
	 *
	 * @param	int		Project ID
	 * @param	int		Story ID
	 * @return	mixed	Verified data
	 */
	public function deleteStory($projectid, $storyid) {
		// Setting up the URL
		$url = $this->base . '/projects/' . intval($projectid) . '/stories/' . intval($storyid);

		// Time to DEL this story
		$data = $this->curlPivotal('DELETE', $url);

		return $this->verifyData($data);
	}

	/**
	 * Deliver story
	 *
	 * @param	int	Project ID
	 *
	 * @todo
	 */
	public function deliverStory($projectid) {
	}

	/**
	 * Move story
	 *
	 * @param	int	Project ID
	 * @param	int	Story ID
	 *
	 * @todo
	 */
	public function moveStory($projectid, $storyid) {
	}

	/**
	 * Get comments
	 *
	 * @param	int		Project ID
	 * @param	int		Story ID
	 * @return	mixed	Verified data
	 */
	public function getComments($projectid, $storyid) {
		// setting up the URL
		$url = $this->base . '/projects/' . intval($projectid) . '/stories/' . intval($storyid) . '/comments';

		// Time to GET the comments
		$data = $this->curlPivotal('GET', $url);

		return $this->verifyData($data);
	}

	/**
	 * Post comments
	 *
	 * @param	int		Project ID
	 * @param	int		Story ID
	 * @param	array	Arguments
	 * @return	mixed	Verified data
	 */
	public function postComments($projectid, $storyid, $args) {
		// setting up the URL
		$url = $this->base . '/projects/' . intval($projectid) . '/stories/' . intval($storyid) . '/comments';

		// Time to post the Story
		$data = $this->curlPivotal('POST', $url, $args);

		return $this->verifyData($data); 
	}

	/**
	 * Get tasks
	 *
	 * @param	int		Project ID
	 * @param	int		Story ID
	 * @param	mixed	Task ID
	 * @return	mixed	Verified data
	 */
	public function getTask($projectid, $storyid, $taskid = null) {
		// Seting up the URL 
		$url = $this->base . '/projects/' . intval($projectid) . '/stories/' . intval($storyid) . '/tasks' . ($taskid ? '/' . intval($taskid) : '');

		// Time to retrieve some tasks
		$data = $this->curlPivotal('GET', $url);

		return $this->verifyData($data);
	}

	/**
	 * Add task
	 *
	 * @param	int	Project ID
	 * @param	int	Story ID
	 *
	 * @todo
	 */
	public function addTask($projectid, $storyid) {
	}

	/**
	 * @param	int	Project ID
	 * @param	int	Story ID
	 * @param	int	Task ID
	 *
	 * @todo
	 */
	public function updateTask($projectid, $storyid, $taskid) {
	}

	/**
	 * Delete task
	 *
	 * @param	int		Project ID
	 * @param	int		Story ID
	 * @param	int		Task ID
	 * @return	mixed	Verified data
	 */
	public function deleteTask($projectid, $storyid, $taskid) {
		// Setting up the URL
		$url = $this->base . '/projects/' . intval($projectid) . '/stories/' . intval($storyid) . '/tasks/' . intval($taskid);

		// Time to DEL this story
		$data = $this->curlPivotal('DELETE', $url);

		return $this->verifyData($data);
	}

	/**
	 * Verifies the data
	 *
	 * @param	mixed	Data
	 * @return	mixed	Verified data
	 */
	public function verifyData($data) {
		if ($data) {
			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Curl-alize arguments
	 *
	 * @param	array	Arguments
	 * @return	array	Arguments return
	 */
	private function curlArguments($arguements) {
		foreach ($arguements as $key => $value) {
			$args[] = $key . '=' . $value;
		}
		return $args;
	}

	/**
	 * Builds the URL
	 *
	 * @param	string	Task
	 * @param	mixed	Arguments
	 * @return	string	URL
	 */
	private function buildUrl($task, $arguments = null) {
		$url = $this->base . $task;
		if ($arguments) {
			$args = $this->curlArguments($arguments);
			$url = $url . '/' . implode('&', $args);
			$url = str_replace('&amp;', '&', urldecode(trim($url)));
		}
		return $url;
	}
}
?>
