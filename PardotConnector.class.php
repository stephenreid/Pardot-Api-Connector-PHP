<?php

/**
 * This is a basic class for connecting to the Pardot API
 * The important parts here are the authenticate, send, and various prospect functions
 * Check out Prospect.class.php as a way to manipulate prospects.
 *
 * @author stephenreid
 *
 * @since 7/10/2012
 * @desc A connecting class to the pardot api
 *
 * @method simpleXmlObject accountRead() Read account information
 * @method simpleXmlObject create($objectType,$object)
 * @method array query($objectType,$queryParameters) Runs the query action against an object type
 * @method simpleXmlObject read($objectType, $identifierAssociativeArray)
 * @method simpleXmlObject update($objectType, $object)
 * @method simpleXmlObject upsert($objectType, $object)
 * @method object campaignCreate($object)
 * @method object campaignQuery($criteria)
 * @method object campaignRead($criteria)
 * @method object campaignUpdate($object)
 * @method object customFieldQuery($criteria)
 * @method object customFieldRead($criteria)
 * @method object customRedirectQuery($criteria)
 * @method object customRedirectRead($criteria)
 * @method object emailRead($criteria)
 * @method object emailSend($object)
 * @method object visitorQuery($criteria)
 * @method object visitorRead($criteria)
 * @method object formQuery($criteria)
 * @method object formRead($criteria)
 * @method object listQuery($criteria)
 * @method object listRead($criteria)
 * @method object opportunityQuery($criteria)
 * @method object opportunityRead($criteria)
 * @method object opportunityUpdate($criteria)
 * @method object prospectCreate($object)
 * @method object prospectQuery($criteria)
 * @method object prospectRead($criteria)
 * @method object prospectUpdate($object)
 * @method object prospectUpsert($object)
 * @method object userQuery($criteria)
 * @method object userRead($criteria)
 * @method object visitorActivityQuery($criteria)
 * @method object visitorActivityRead($criteria)
 * @method object visitQuery($criteria)
 * @method object visitRead($criteria)
 */
class PardotConnector
{
    //A flag for echoing debug output
    private $debug = false;
    private $apiKey = null;
    private $outputMode = 'simple'; // choose between 'simple','full','mobile'

    /** It's Best if you set Authentication Through Your Server Environment Vars not Files **/
    private $email = '';
    private $password = '';
    private $userKey = '';

    private $objectTypes = array(
        'account',
        'campaign',
        'customField',
        'customRedirect',
        'dynamicContent',
        'email',
        'form',
        'list',
        'listMembership',
        'opportunity',
        'opportunityProspect',
        'prospect',
        'user',
        'visitorActivity',
        'visitor',
    );

    /**
     * __construct PardotConnector()
     * Dummy Constructor, Run authenticate() to be able to do anything.
     */
    public function __construct()
    {
    }
    public function __call($name, $args = array())
    {
        if ($name === 'create') {
            return $this->sendRequest($args['0'], $name, $args['1'])->$args['0'];
        } elseif ($name === 'query') {
            return $this->baseQuery($args['0'], $args['1']);
        } elseif ($name === 'read') {
            return $this->sendRequest($args['0'], $name, $args['1'])->$args['0'];
        } elseif ($name === 'update') {
            return $this->sendRequest($args['0'], $name, $args['1'])->$args['0'];
        } elseif ($name === 'upsert') {
            return $this->sendRequest($args['0'], $name, $args['1'])->$args['0'];
        } elseif ($name === 'send') {
            return $this->sendRequest($args['0'], $name, $args['1'])->$args['0'];
        }

        //Reverse sort this, so we have longest words first
        sort($this->objectTypes);
        $objectTypes = array_reverse($this->objectTypes);

        foreach ($objectTypes as $type) {
            if (strpos($name, $type) === 0) {
                $action = strtolower(str_replace($type, '', $name));
                if (count($args) === 0) {
                    return $this->$action($type, $args);
                } else {
                    return $this->$action($type, $args[0]);
                }
            }
        }

        return false;
    }

    /**
     * Must call this function before making any other API calls.
     *
     * @param string|null $username
     * @param string|null $password
     * @param string|null $userKey
     *
     * @return SimpleXMLElement
     *
     * @throws PardotConnectorException
     */
    public function authenticate($username = null, $password = null, $userKey = null)
    {
        //gets a user api key back
        if ($username != null) {
            $params = array('email' => $username,'password' => $password,'user_key' => $userKey);
            $this->userKey = $userKey;
        } else {
            $params = array('email' => $this->email,'password' => $this->password,'user_key' => $this->userKey);
        }
        $ret = $this->sendRequest('login', '', $params);

        $this->apiKey = $ret->api_key;//add error handling to this later
        return $ret;
    }

    /**
     * baseQuery.
     *
     * @param $objectType (The type of object we're querying)
     * @param $queryParams (array of query critiera)
     *
     * @return SimpleXMLElement
     */
    private function baseQuery($objectType, $queryParams)
    {
        $objects = $this->sendRequest($objectType, 'query', $queryParams)->result;

        return $objects;
    }

    /**
     * sendRequest.
     *
     * @desc Sends a web request to the api
     *
     * @param string $module
     * @param string $action
     * @param array  $parameters A Key Value Store of Parameters
     *
     * @return SimpleXMLElement Response from Server
     *
     * @throws Exception
     */
    private function sendRequest($module = 'prospect', $action = 'query', $parameters = array())
    {
        $baseUrl = 'https://pi.pardot.com/api/';
        $version = 'version/3/';

        if ($this->apiKey && $this->userKey) {
            $login = array('api_key' => $this->apiKey,'user_key' => $this->userKey);
            $parameters = array_merge($login, $parameters);
            $action = 'do/'.$action;
        }
        if ($this->outputMode) {
            $output = array('output' => $this->outputMode);
            $parameters = array_merge($output, $parameters);
        }
        $url = $baseUrl.$module.'/'.$version.$action;
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST', //never want to send credentials over GET
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query($parameters),
                'timeout' => 30.0, //in seconds
                'user_agent' => 'PardotPHPClient',
                //'proxy'		=> '',
                //'ignore_errors'	=> false,
            ),
        ));

        $res = file_get_contents($url, false, $context);
        $ret = simplexml_load_string($res);
        if ($ret->err) {
            throw new PardotConnectorException($ret->err.' '.$url.' '.http_build_query($parameters), '1');
        }

        return $ret;
    }
}
class PardotConnectorException extends Exception
{
    public function __construct($message = '', $code = 1)
    {
        parent::__construct($message, $code);
    }
}
