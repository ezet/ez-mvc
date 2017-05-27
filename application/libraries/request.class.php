<?php

/**
 * @author     Lars Kristian Dahl <http://www.krisd.com>
 * @copyright  Copyright (c) 2011 Lars Kristian Dahl <http://www.krisd.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 * @version    SVN: $Id$
 */

namespace ezmvc\libraries;

use ezmvc\libraries\WebUser;
use ezmvc\libraries\Config;
use ezmvc\controllers\ActionController;

/**
 * Handles user requests, queries, cookies and formdata
 *
 * @author  Lars Kristian Dahl <http://www.krisd.com>
 */
class Request {

    /**
     * The parsed query fragments
     * @var <type>
     */
    private $_query = array();
    /**
     * The response view
     * @var <type>
     */
    private $_response;

    /**
     * Basic factory method
     * @return Request
     */
    public static function factory() {
        return new Request();
    }

    /**
     * Constructor
     */
    public function __construct() {

    }

    /**
     * Magic getter for the parsed query fragments
     * @param <type> $key
     * @return <type>
     */
    public function __get($key) {
        return $this->getQuery($key);
    }

    /**
     * Magic isset for the query fragments
     * @param <type> $key
     * @return <type>
     */
    public function __isset($key) {
        return isset($this->_query[$key]);
    }

    /**
     * Performs a redirect to the requested url
     * @param <type> $url
     */
    public function redirect($url) {
        header('Location:' . $url);
        exit(1);
    }

    /**
     * Redirects to the HTTP referrer if the request came from us,
     * otherwise redirects to the index
     * @param <type> $anchor
     */
    public function backward($anchor=null) {
        $returnurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        if ($anchor)
            $returnurl .= '#' . $anchor;
        if (strpos($returnurl, $_SERVER['HTTP_HOST'])) {
            $this->redirect($returnurl);
            exit(1);
        } else {
            $this->redirect(__PUBLIC_PATH);
            exit(1);
        }
    }

    /**
     * Redirects to given url after a specified delay
     * @param <type> $delay
     * @param <type> $url
     */
    public function refresh($delay=5, $url='#') {
        header('Refresh:' . $delay . '; Url=' . __PUBLIC_PATH . '/' . $url);
        exit(1);
    }

    /**
     * Forwards the request to a different controller/action
     * @param <type> $controller
     * @param <type> $action
     * @param <type> $id
     */
    public function forward($controller, $action=null, $id=null) {
        // FIXME Fix the function
        $prevcontroller = $this->getController();
        $prevaction = $this->getAction();
        $this->route("/$controller/$action/$id");
        $controller = $this->invokeController();
        $this->controller = $prevcontroller;
        $this->action = $prevaction;
    }

    /**
     * Sets the response view
     * @param <type> $response
     */
    public function setResponse($response) {
//    TODO refactor response handling in Request
        $this->_response = $response;
    }

    /**
     * Renders the response view
     */
    public function renderResponse() {
        echo $this->_response->render();
    }

    /**
     * Parses and validates the request
     * @param <type> $string
     */
    public function route($string=null) {
        // parse the query string
        ($string == null) ? $string = $_SERVER['QUERY_STRING'] : null;

        // strip any GET data
        if ($pos = strpos($string,'&')) {
            $string = substr($string, 0, $pos);
        }
        
        $query = explode('/', $string);

        // remove empty entries, also removes '0' !
        $query = array_filter($query);

        // setting requested controller, using default from config if none requested
        $controller = (isset($query[0])) ? $query[0] : Config::get('default_controller');
        $this->_query['controller'] = 'ezmvc\controllers\\' . $controller . '\\' . $controller . 'Controller';

        // setting requested action, using defalt from config if none requested
        $action = (isset($query[1])) ? $query[1] : Config::get('default_action');
        $this->_query['action'] = 'action' . ucfirst($action);

        // setting id
        $this->_query['id'] = (isset($query[2])) ? $query[2] : null;

        // storing any additional parameters
        if (isset($query[3])) {
            $this->_query['params'] = array_splice($query, 3);
        }

        // validate the route
        $this->validateRoute();
    }

    /*
     * Validates the route, making sure the controllers and actions exist
     */

    public function validateRoute() {
        $controller = $this->getController();
        $action = $this->getAction();

        // if controller ! exists
        if (!class_exists($controller)) {
            throw new \Exception("Controller does not exist: $controller");
        }
        // if action is ! valid
        if (!is_callable(array($controller, $action))) {
            throw new \Exception("Action not defined in $controller: $action");
        }
        // if controller ! extends required basecontroller
        if (!is_subclass_of($controller, 'ezmvc\controllers\ActionController')) {
            throw new \Exception("Required ActionController not inherited by controller: $controller");
        }
    }

    /**
     * Returns the query array, or a specific fragment
     * @param <type> $key
     * @return <type>
     */
    public function getQuery($key=null) {
        if ($key == null)
            return $this->_query;
        return (isset($this->_query[$key])) ? $this->_query[$key] : null;
    }

    /**
     * Returns requested Controller
     * @return <type>
     */
    public function getController() {
        return $this->getQuery('controller');
    }

    /**
     * Returns requested Action
     * @return <type>
     */
    public function getAction() {
        return $this->getQuery('action');
    }

    /**
     * Returns requested Id
     * @return <type>
     */
    public function getId() {
        return $this->getQuery('id');
    }

    /**
     * Returns array of additional parameters, beyond the 3 first
     * @return <type>
     */
    public function getParams($num=null) {
        $params = $this->getQuery('params');
        return (isset($params[$num])) ? $params[$num] : $params;
    }

    /**
     * Returns a value from $_GET, or the whole array if no key is specified
     * @param <type> $key
     * @return <type>
     */
    public function getGet($key=null) {
        // TODO perform sanitazion
        if ($key == null)
            return $_GET;
        else
            return (isset($_GET[$key]) ? $_GET[$key] : null);
    }

    /**
     * Returns a value from $_POST, or the whole array if no key is specified
     * @param <type> $key
     * @return <type>
     */
    public function getPost($key=null) {
        // TODO perform sanitazion
        if ($key == null)
            return $_POST;
        else
            return (isset($_POST[$key]) ? $_POST[$key] : null);
    }

    /**
     * Returns a value from _COOKIE, or the whole array if no key is specified
     * @param <type> $key
     * @return <type>
     */
    public function getCookie($key=null) {
        // TODO perform sanitazion
        if ($key == null)
            return $_COOKIE;
        else
            return (isset($_COOKIE[$key]) ? $_COOKIE[$key] : null);
    }

    /**
     * Sets a value in _GET
     * @param <type> $key
     * @param <type> $value
     */
    public function setGet($key, $value) {
        $_GET[$key] = $value;
    }

    /**
     * Sets a value in _POST
     * @param <type> $key
     * @param <type> $value
     */
    public function setPost($key, $value) {
        $_POST[$key] = $value;
    }

    /**
     * Sets a value in _COOKIE
     * @param <type> $key
     * @param <type> $value
     */
    public function setCookie($key, $value) {
        $_COOKIE[$key] = $value;
    }

}