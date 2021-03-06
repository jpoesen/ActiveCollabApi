<?php

namespace ActiveCollabApi;

/**
 * @file
 *   Methods for interacting with the activeCollab API.
 * @category
 * @tags
 * @package
 * @license
 */

/**
 * activeCollabAPI class.
 *
 */
class ActiveCollabApi
{

    /**
     * Key for authorization
     *
     * @var string
     */
    protected static $key = null;

    /**
     * API URL
     *
     * @var string
     */
    protected static $api_url = null;

    /**
     * API url
     *
     * @var string
     */
    protected static $api_string = null;

    /**
     * API response
     *
     * @var string
     */
    protected static $API_response = null;

    public function __construct() {

    }

    /**
     * Format request string
     *
     * @param string $path_info
     *        The requested path.
     * @param string $additional_params
     *        Additional params.
     * @return string
     */
    public static function setRequestString($path_info, $additional_params = null)
    {
        if (self::$key != null && $path_info != null) {
            self::$api_string = self::$api_url;
            self::$api_string .= "?auth_api_token=" . self::$key;
            self::$api_string .= "&path_info=" . $path_info;
        }
        // Set format to JSON.
        self::$api_string .= "&format=json";
        return self::$api_string;
    }

    /**
     * Set key for authorisation
     *
     * @param integer $key
     *        The token for authorizing with activeCollab.
     * @return string
     */
    public static function setKey($key)
    {
      if ($key != null)
        return self::$key = $key;
    }

    /**
     * Set API url
     *
     * @param string $value
     *        The URL for the activeCollab API.
     * @return string
     */
    public static function setAPIUrl($value)
    {
        return self::$api_url = $value;
    }

    /**
     * Make API call
     *
     * @param array $post_params
     * @param array $file_params
     * @return object
     */
    public static function callAPI($parameters = array(), $method = 'GET')
    {
           // redefine
        $path = (string) self::$api_string;
        $parameters = (array) $parameters;

        // init var
        $options = array();

        // HTTP method
        if ($method == 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($parameters);
        } else {
            $options[CURLOPT_POST] = false;
            if (!empty($parameters)) {
                $path .= '&' . http_build_query($parameters);
            }
        }

        // set options
        $options[CURLOPT_URL] = $path;
        // $options[CURLOPT_USERAGENT] = $this->getUserAgent();
        // if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            // $options[CURLOPT_FOLLOWLOCATION] = true;
        // }
        $options[CURLOPT_RETURNTRANSFER] = true;
        // $options[CURLOPT_TIMEOUT] = (int) $this->getTimeOut();
        $options[CURLOPT_SSL_VERIFYPEER] = false;
        $options[CURLOPT_SSL_VERIFYHOST] = false;

        // init
        $curl = curl_init();

        // set options
        curl_setopt_array($curl, $options);

        // execute
        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);

        // fetch errors
        $errorNumber = curl_errno($curl);
        $errorMessage = curl_error($curl);

        // close
        curl_close($curl);

        // we expect JSON, so decode it
        return @json_decode($response, true);
    }

    /**
     * Return user ID of the logged-in user.
     *
     * @return int
     */
    public function whoAmI() {
        $api = $this->getVersion();
        $loggedInUser = $api->logged_user;
        $path = parse_url($loggedInUser);
        $parts = explode('/', $path['path']);
        return $parts[4];
    }

    /**
     * Return API version
     *
     * @param void
     * @return array
     */
    public function getVersion()
    {
        self::setRequestString('info');
        return self::callAPI();
    }

    public function getTasksForProject($project_slug)
    {
        self::setRequestString(sprintf('projects/%s/tasks', $project_slug));
        return self::callAPI();
    }

    public function getSubtasksForProject($project_slug)
    {
        self::setRequestString(sprintf('projects/%s/subtasks', $project_slug));
        return self::callAPI();
    }

    /**
     * List all project
     *
     * @param void
     * @return array - array of ActiveCollabProject objects
     */
    public function listProjects()
    {
        $path_info = '/projects';
        self::setRequestString($path_info);
        return self::callAPI();
    }

    /**
     * Helper function to check if the response is an object or array.
     *
     * @param $response
     *        String, object, or array returned from activeCollab.
     */
    public static function checkResponse($response)
    {
        return (is_array($response) || is_object($response)) ? $response : FALSE;
    }

    /**
     * List all people involved with a project and their permissions.
     *
     * @param int $project_id
     *        The project Id.
     * @return array - array of ActiveCollabUser objects
     */
    public function listPeopleByProjectId($project_id)
    {
        $path_info = '/projects/' . $project_id . '/people';
        self::setRequestString($path_info);
        return self::callAPI();
    }

    /**
     * Get API version
     *
     * @return float
     */
    protected function getAPIVersion()
    {
        $api_version = self::getVersion();
        return $api_version['api_version'];
    }

    /**
     * List all categories
     *
     * @param $project_id - Project id
     * @param $object - Object name
     * @return array of ActiveCollabCategory object`s
     */
    protected function listCategories($project_id,$object_name)
    {
        if (self::getAPIVersion() > 2.0) {
            $path_info = 'projects/' . $project_id . '/' . $object_name . '/categories';
            self::setRequestString($path_info);
            $response = self::callAPI();
            if (is_array($response)) {
                return $response;
            }
        } else {
            throw new ActiveCollabCommandNotRecognized(COMMAND_NOT_RECOGNIZED . 'Your API version is ' . self::getAPIVersion());
        }
    }

    /**
     * List ticket categories
     *
     * @param $project_id
     * @return array
     */
    public function listTicketCategoriesByProjectId($project_id) {
      return self::listCategories($project_id,'tickets');
    }

    /**
     * List discussion categories
     *
     * @param $project_id
     * @return array
     */
    public function listDiscussionCategoriesByProjectId($project_id) {
      return self::listCategories($project_id,'discussions');
    }

    /**
     * List file categories
     *
     * @param $project_id
     * @return array
     */
    public function listFileCategoriesByProjectId($project_id) {
      return self::listCategories($project_id,'files');
    }

    /**
     * List page categories
     *
     * @param $project_id
     * @return array
     */
    public function listPageCategoriesByProjectId($project_id) {
      return self::listCategories($project_id,'pages');
    }

    /**
     * List all available project groups.
     *
     * @param void
     * @return array - array of ActiveCollabProjectGroup objects
     */
    public function listProjectGroups() {
      $path_info = 'projects/groups';
      self::setRequestString($path_info);
      $response = self::callAPI();
      if (is_array($response)) {
        return $response;
      }
    }

    /**
     * List all ticket for specific project
     *
     * @param $project_id - project Id
     * @return array - array of ActiveCollabTicket objects
     */
    public function listTicketsByProjectId($project_id) {
      $path_info = '/projects/' . $project_id . '/tickets';
      self::setRequestString($path_info);
      $response = self::callAPI();
      if (is_array($response)) {
        return $response;
      }
    }

    /**
     * List all ticket for specific project and category
     *
     * @param $project_id - Project id
     * @param $category_id - Category id
     * @return array - array of ActiveCollabTicket objects
     */
    public function listTicketsByCategoryId($project_id,$category_id) {
      if(self::getAPIVersion() > 2.0) {
        $additional_param = array('category_id' => $category_id);
        $path_info = '/projects/' . $project_id . '/tickets';
        self::setRequestString($path_info,$additional_param);
        $response = self::callAPI();
        if (is_array($response)) {
          return $response;
        }
      } else {
        throw new ActiveCollabCommandNotRecognized(COMMAND_NOT_RECOGNIZED . 'Your API version is ' . self::getAPIVersion());
      } // if
    } // listTicketsByCategoryId

    /**
     * List all archived ticket for specific project
     *
     * @param $project_id - project Id
     * @return array - array of ActiveCollabTicket objects
     */
    public function listArchivedTicketsByProjectId($project_id) {
      $path_info = '/projects/' . $project_id . '/tickets/archive';
      self::setRequestString($path_info);
      $response = self::callAPI();
      if (is_array($response)) {
        return $response;
      }
    }

    /**
     * List all files for specific project
     *
     * @param $project_id - project Id
     * @return array - array of ActiveCollabFile objects
     */
    public function listFilesByProjectId($project_id) {
      $path_info = '/projects/' . $project_id . '/files';
      self::setRequestString($path_info);
      $response = self::callAPI();
      if (is_array($response)) {
        return $response;
      }
    } // listFilesByProjectId

    /**
     * List all files for specific project and category
     *
     * @param $project_id - Project id
     * @param $category_id - Category id
     * @return array - array of ActiveCollabFile objects
     */
    public function listFilesByCategoryId($project_id,$category_id) {
      if(self::getAPIVersion() > 2.0) {
        $additional_param = array('category_id' => $category_id);
        $path_info = '/projects/' . $project_id . '/files';
        self::setRequestString($path_info,$additional_param);
        $response = self::callAPI();
        if (is_array($response)) {
          return $response;
        }
      } else {
        throw new ActiveCollabCommandNotRecognized(COMMAND_NOT_RECOGNIZED . 'Your API version is ' . self::getAPIVersion());
      } // if
    } // listFilesByCategoryId

    /**
     * List all discussions for specific project
     *
     * @param $project_id - project Id
     * @return array - array of ActiveCollabDiscussion objects
     */
    public function listDiscussionsByProjectId($project_id) {
      $path_info = '/projects/' . $project_id . '/discussions';
      self::setRequestString($path_info);
      $response = self::callAPI();
      if (is_array($response)) {
        return $response;
      }
    } // listDiscussionsByProjectId

    /**
     * List all discussion for specific project and category
     *
     * @param $project_id - Project id
     * @param $category_id - Category id
     * @return array - array of ActiveCollabDiscussion objects
     */
    public function listDiscussionsByCategoryId($project_id,$category_id) {
      if(self::getAPIVersion() > 2.0) {
        $additional_param = array('category_id' => $category_id);
        $path_info = '/projects/' . $project_id . '/discussions';
        self::setRequestString($path_info,$additional_param);
        $response = self::callAPI();
        if (is_array($response)) {
          return $response;
        }
      } else {
        throw new ActiveCollabCommandNotRecognized(COMMAND_NOT_RECOGNIZED . 'Your API version is ' . self::getAPIVersion());
      } // if
    } // listDiscussionsByCategoryId

    /**
     * List all milestones for specific project
     *
     * @param $project_id - project Id
     * @return array - array of ActiveCollabMilestone objects
     */
    public function listMilestonesByProjectId($project_id) {
      $path_info = '/projects/' . $project_id . '/milestones';
      self::setRequestString($path_info);
      $response = self::callAPI();
      if (is_array($response)) {
        return $response;
      }
    }

    /**
     * List all checklists for specific project
     *
     * @param $project_id - project Id
     * @return array - array of ActiveCollabChecklist objects
     */
    public function listChecklistsByProjectId($project_id) {
      $path_info = '/projects/' . $project_id . '/checklists';
      self::setRequestString($path_info);
      $response = self::callAPI();
      if (is_array($response)) {
        return $response;
      }
    }

    /**
     * List all arhived checklist for specific project
     *
     * @param $project_id - project Id
     * @return array - array of ActiveCollabChecklist object
     */
    public function listArchivedChecklistsByProjectId($project_id) {
      $path_info = '/projects/' . $project_id . '/checklists/archive';
      self::setRequestString($path_info);
      $response = self::callAPI();
      if (is_array($response)) {
        return $response;
      }
    }

    /**
     * List all page for specific project and category
     *
     * @param $project_id - Project id
     * @param $category_id - Category id
     * @return array - array of ActiveCollabPage objects
     */
    public function listPagesByCategoryId($project_id,$category_id) {
      if(self::getAPIVersion() > 2.0) {
        $additional_param = array('category_id' => $category_id);
        $path_info = '/projects/' . $project_id . '/pages';
        self::setRequestString($path_info,$additional_param);
        $response = self::callAPI();
        if (is_array($response)) {
          return $response;
        }
      } else {
        throw new ActiveCollabCommandNotRecognized(COMMAND_NOT_RECOGNIZED . 'Your API version is ' . self::getAPIVersion());
      } // if
    } // listPageByCategoryId

    /**
     * List all time records for specific project
     *
     * @param $project_id - project Id
     * @return array - array of ActiveCollabTime objects
     */
    public function listTimeRecordsByProjectId($project_id) {
      $path_info = '/projects/' . $project_id . '/time';
      self::setRequestString($path_info);
      $response = self::callAPI();
      if (is_array($response)) {
        return $response;
      }
    }

    /**
     * List all system roles
     *
     * @param void
     * @return array - array of ActiveCollabRole objects
     */
    public function listSystemRoles() {
      $path_info = '/roles/system';
      self::setRequestString($path_info);
      $response = self::callAPI();
      if (is_array($response)) {
        return $response;
      }
    }

    /**
     * List all project roles
     *
     * @param void
     * @return array - array of ActiveCollabRole objects
     */
    public function listProjectRoles() {
      $path_info = '/roles/project';
      self::setRequestString($path_info);
      $response = self::callAPI();
      if (is_array($response)) {
        return $response;
      }
    }

    /**
     * List role details
     *
     * @param $role_id - role id
     * @return object - ActiveCollabRole object
     */
    public function findRoleDetailsById($role_id) {
      $path_info = '/roles/' . $role_id;
      self::setRequestString($path_info);
      $response = self::callAPI();
      if (is_array($response)) {
        return $response;
      }
    }

    /**
     * List all companies
     *
     * @param $void
     * @return array - array of ActiveCollabCompany objects
     */
    public function listCompanies() {
      $path_info = '/people';
      self::setRequestString($path_info);
      return self::callAPI();
    }

    /**
     * List all status message
     *
     * @param $void
     * @return array - array of ActiveCollabStatusMessage objects
     */
    public function listStatusMessages() {
      $path_info = '/status';
      self::setRequestString($path_info);
      return self::callAPI();
    }

    /**
     * List all status message created by user
     *
     * @param $user_id - user id
     * @return array - array of ActiveCollabStatusMessage objects
     */
    public function listStatusMessagesByUserId($user_id) {
      $path_info = '/status';
      $additional_params = array('user_id' => $user_id);
      self::setRequestString($path_info, $additional_params);
      return self::callAPI();
    }

    /**
     * Create ActiveCollabTicket object
     *
     * @param $project_id - project id
     * @param $id - ticket id
     * @return object
     */
    public function findTicketById($project_id,$id) {
      return new ActiveCollabTicket($project_id, $id);
    } // findTicketById

    /**
     * Create ActiveCollabSubTask object
     *
     * @param $project_id - project id
     * @param $id - subtask id
     * @return object
     */
    public function findSubTaskById($project_id,$id) {
      return new ActiveCollabSubTask($project_id, $id);
    } // findSubTaskById

    /**
     * Create ActiveCollabComment object
     *
     * @param $project_id - project id
     * @param $id - comment id
     * @return object
     */
    public function findCommentById($project_id,$id) {
      return new ActiveCollabComment($project_id, $id);
    } // findCommentById

    /**
     * Create ActiveCollabFile object
     *
     * @param $project_id - project id
     * @param $id - file id
     * @return object
     */
    public function findFileById($project_id,$id) {
      return new ActiveCollabFile($project_id, $id);
    } // findFileById

    /**
     * Create ActiveCollabDiscussion object
     *
     * @param $project_id - project id
     * @param $id - discussion id
     * @return object
     */
    public function findDiscussionById($project_id,$id) {
      return new ActiveCollabDiscussion($project_id, $id);
    } // findDiscussionById


    /**
     * Create ActiveCollabProjectGroup object
     *
     * @param $id - project group id
     * @return object
     */
    public function findProjectGroupById($id) {
      return new ActiveCollabProjectGroup($id);
    } // findGroupById

    /**
     * Create ActiveCollabChecklist object
     *
     * @param $project_id - project id
     * @param $id - checklist id
     * @return object
     */
    public function findChecklistById($project_id,$id) {
      return new ActiveCollabChecklist($project_id, $id);
    } // findChecklistById

    /**
     * Create ActiveCollabPage object
     *
     * @param $project_id - project id
     * @param $id - page id
     * @return object
     */
    public function findPageById($project_id,$id) {
      return new ActiveCollabPage($project_id, $id);
    } // findPageById

    /**
     * Create ActiveCollabTime object
     *
     * @param $project_id - project id
     * @param $id - time record id
     * @return object
     */
    public function findTimeById($project_id,$id) {
      return new ActiveCollabTime($project_id, $id);
    } // findTimeById

    /**
     * Create ActiveCollabCompany object
     *
     * @param $id - company id
     * @return object
     */
    public function findCompanyById($id) {
      return new ActiveCollabCompany($id);
    } // findCompanyById

    /**
     * Create ActiveCollabUser object
     *
     * @param $company_id - company id
     * @param $id - user id
     * @return object
     */
    public function findUserById($company_id,$id) {
      return new ActiveCollabUser($company_id, $id);
    } // findUserById

    /**
     * Create ActiveCollabProject object
     *
     * @param $id - project id
     * @return object
     */
    public function findProjectById($id) {
      return new ActiveCollabProject($id);
    } // findProjectById

    /**
     * Returns all tasks that are assigned to the logged in user in a particular project.
     *
     * @param void
     * @return object - ActiveCollabTicket object
     */
    public function getUserTasks($project_id) {
        $path_info = '/projects/' . $project_id . '/user-tasks';
        self::setRequestString($path_info);
        return self::callAPI();
    }

    /**
     * Return ticket.
     *
     * @param $project_id
     * @return object - ActiveCollabTicket object
     */
    public function getTicket($project_id, $ticket_id) {
        $path_info = '/projects/' . $project_id . '/tickets/'. $ticket_id;
        self::setRequestString($path_info);
        return self::callAPI();
    }

    /**
     * Lists all companies in the system.
     */
    public function listPeople() {
        $path_info = '/people';
        self::setRequestString($path_info);
        return self::callAPI();
    }

    /**
     * Get a project by its Id.
     */
    public function getProject($id) {
        $path_info = '/projects/' . $id;
        self::setRequestString($path_info);
        return self::callAPI();
    }

    public function getProjects()
    {
        self::setRequestString('projects');
        return self::callAPI();
    }

    /**
     * Get a user by company ID and user ID.
     */
    public function getUser($companyId, $userId) {
        $path_info = '/people/' . $companyId . '/users/' . $userId;
        self::setRequestString($path_info);
        return self::callAPI();
    }

    /**
     * Get a user by project ID and user ID.
     */
    public function getUserByProject($projectId, $userId) {
        $people = self::listPeopleByProjectId($projectId);
        // Make an array keyed on user ID
        $projectUsers = array();
        foreach ($people as $projectUser) {
          $projectUsers[$projectUser['user_id']] = $projectUser;
        }
        $userInfo = self::parseUserPermalink($projectUsers[$userId]['permalink']);
        return self::getUser($userInfo['company_id'], $userInfo['user_id']);
    }

    /**
     * Parse a permalink for a user account.
     *
     * @param string $permalink
     *        A link to to the user account in the format https://{url}/people/72/users/131
     * @return array
     *         An array with the company ID and user ID for the user.
     */
    public function parseUserPermalink($permalink) {
        $user = array();
        $slash = strpos($permalink, '//');
        $path = substr($permalink, $slash + 2);
        $parts = explode('/', $path);
        $user['company_id'] = $parts[2];
        $user['user_id'] = $parts[4];
        return $user;
    }

    /**
     * Return assignment labels.
     */
    public function getAssignmentLabels() {
        $path_info = '/info/labels/assignment';
        self::setRequestString($path_info);
        return self::callAPI();
    }

    /**
     * Get ActiveCollabMilestone object
     *
     * @param $projectId - project id
     * @param $id - milestone id
     * @return object
     */
    public function getMilestoneById($projectId, $id) {
        $path_info = '/projects/' . $projectId . '/milestones/' . $id;
        self::setRequestString($path_info);
        return self::callAPI();
    }

    /**
     * Get ActiveCollabCompany object.
     *
     * @param int $companyId
     * @return object
     */
    public function getCompanyById($companyId) {
        $path_info = '/people/' . $companyId;
        self::setRequestString($path_info);
        return self::callAPI();
    }

}

