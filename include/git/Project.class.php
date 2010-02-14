<?php
/**
 * GitPHP Project
 * 
 * Represents a single git project
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_INCLUDEDIR . 'defs.commands.php');
require_once(GITPHP_INCLUDEDIR . 'git/Commit.class.php');

/**
 * Project class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Project
{

	/**
	 * project
	 *
	 * Stores the project internally
	 *
	 * @access protected
	 */
	protected $project;

	/**
	 * owner
	 *
	 * Stores the owner internally
	 *
	 * @access protected
	 */
	protected $owner = "";

	/**
	 * readOwner
	 *
	 * Stores whether the file owner has been read
	 *
	 * @access protected
	 */
	protected $readOwner = false;

	/**
	 * description
	 *
	 * Stores the description internally
	 *
	 * @access protected
	 */
	protected $description;

	/**
	 * readDescription
	 *
	 * Stores whether the description has been
	 * read from the file yet
	 *
	 * @access protected
	 */
	protected $readDescription = false;

	/**
	 * category
	 *
	 * Stores the category internally
	 *
	 * @access protected
	 */
	protected $category = '';

	/**
	 * head
	 *
	 * Stores the head hash internally
	 *
	 * @access protected
	 */
	protected $head;

	/**
	 * readHead
	 *
	 * Stores whether the head ref has been read yet
	 *
	 * @access protected
	 */
	protected $readHead;

	/**
	 * commitCache
	 *
	 * Caches fetched commit objects in case of
	 * repeated requests for the same object
	 *
	 * @access protected
	 */
	protected $commitCache = array();

	/**
	 * __construct
	 *
	 * Class constructor
	 *
	 * @access public
	 * @throws Exception if project is invalid or outside of projectroot
	 */
	public function __construct($project)
	{
		$this->SetProject($project);
	}

	/**
	 * SetProject
	 *
	 * Attempts to set the project
	 *
	 * @access private
	 * @throws Exception if project is invalid or outside of projectroot
	 */
	private function SetProject($project)
	{
		$projectRoot = GitPHP_Config::GetInstance()->GetValue('projectroot');
		$realProjectRoot = realpath($projectRoot);
		$fullPath = realpath($projectRoot . $project);

		if (!is_dir($fullPath)) {
			throw new Exception($project . ' is not a directory.');
		}

		if (!is_file($fullPath . '/HEAD')) {
			throw new Exception($project . ' is not a git repository.');
		}

		$pathPiece = substr($fullPath, 0, strlen($realProjectRoot));

		if (strcmp($pathPiece, $realProjectRoot) !== 0) {
			throw new Exception('Project ' . $project . ' is outside of projectroot.');
		}

		$this->project = $project;

	}

	/**
	 * GetOwner
	 *
	 * Gets the project's owner
	 *
	 * @access public
	 * @return string project owner
	 */
	public function GetOwner()
	{
		if (empty($this->owner) && !$this->readOwner) {
			$uid = fileowner($this->GetPath());
			if ($uid > 0) {
				$data = posix_getpwuid($uid);
				if (isset($data['gecos']) && !empty($data['gecos'])) {
					$this->owner = $data['gecos'];
				} elseif (isset($data['name']) && !empty($data['name'])) {
					$this->owner = $data['name'];
				}
			}
			$this->readOwner = true;
		}
	
		return $this->owner;
	}

	/**
	 * SetOwner
	 *
	 * Sets the project's owner (from an external source)
	 *
	 * @access public
	 * @param string $owner the owner
	 */
	public function SetOwner($owner)
	{
		$this->owner = $owner;
	}

	/**
	 * GetProject
	 *
	 * Gets the project
	 *
	 * @access public
	 * @return string the project
	 */
	public function GetProject()
	{
		return $this->project;
	}

	/**
	 * GetPath
	 *
	 * Gets the full project path
	 *
	 * @access public
	 * @return string project path
	 */
	public function GetPath()
	{
		return GitPHP_Config::GetInstance()->GetValue('projectroot') . $this->project;
	}

	/**
	 * GetDescription
	 *
	 * Gets the project description
	 *
	 * @access public
	 * @param $trim length to trim description to (0 for no trim)
	 * @return string project description
	 */
	public function GetDescription($trim = 0)
	{
		if (!$this->readDescription) {
			$this->description = file_get_contents($this->GetPath() . '/description');
		}
		
		if (($trim > 0) && (strlen($this->description) > $trim)) {
			return substr($this->description, 0, $trim) . '...';
		}

		return $this->description;
	}

	/**
	 * GetCategory
	 *
	 * Gets the project's category
	 *
	 * @access public
	 * @return string category
	 */
	public function GetCategory()
	{
		return $this->category;
	}

	/**
	 * SetCategory
	 * 
	 * Sets the project's category
	 *
	 * @access public
	 * @param string $category category
	 */
	public function SetCategory($category)
	{
		$this->category = $category;
	}

	/**
	 * GetHeadCommit
	 *
	 * Gets the head commit for this project
	 * Shortcut for getting the tip commit of the HEAD branch
	 *
	 * @access public
	 * @return mixed head commit
	 */
	public function GetHeadCommit()
	{
		if (!$this->readHead)
			$this->ReadHeadCommit();

		return $this->GetCommit($this->head);
	}

	/**
	 * ReadHeadCommit
	 *
	 * Reads the head commit hash
	 *
	 * @access protected
	 */
	public function ReadHeadCommit()
	{
		$this->readHead = true;

		$exe = new GitPHP_GitExe(GitPHP_Config::GetInstance()->GetValue('gitbin'), $this);
		$args = array();
		$args[] = '--verify';
		$args[] = 'HEAD';
		$this->head = trim($exe->Execute(GIT_REV_PARSE, $args));
	}

	/**
	 * GetCommit
	 *
	 * Get a commit for this project
	 *
	 * @access public
	 */
	public function GetCommit($hash)
	{
		if (empty($hash))
			return null;

		if ($hash === 'HEAD')
			return $this->GetHeadCommit();

		if (!isset($this->commitCache[$hash]))
			$this->commitCache[$hash] = new GitPHP_Commit($this, $hash);

		return $this->commitCache[$hash];
	}

}
