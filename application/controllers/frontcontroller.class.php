<?php

/**
 * @author     Lars Kristian Dahl <http://www.krisd.com>
 * @copyright  Copyright (c) 2011 Lars Kristian Dahl <http://www.krisd.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 * @version    SVN: $Id$
 */

namespace ezmvc\controllers;

use ezmvc\libraries\Request;
use ezmvc\libraries\WebUser;
use ezmvc\libraries as lib;

/**
 * Performs routing, dispatching and response rendering, depending on the request
 *
 * @author  Lars Kristian Dahl <http://www.krisd.com>
 */
class FrontController extends BaseController {

    /**
     * Basic factory method
     * @return self
     */
    public static function factory() {
        return new self(Request::factory());
    }

    /**
     * Routes the request to the correct actioncontroller
     */
    public function route() {
        $request = $this->_req;

        // Parsing the request and setting up a route
        $request->route();

        // Invoking requested controller
        $controller = $this->invokeController();

        // Pre-dispatching the controller
        $controller->preDispatch();

        // Dispatching to the controller
        $this->dispatch($controller);

        // Rendering the response
        $request->renderResponse();

        // Post-dispatching the controller
        $controller->postDispatch();
    }

    /**
     * Gets the action from Request and dispatches to the controller
     * @param ActionController $controller
     */
    public function dispatch(ActionController $controller) {
        $action = $this->_req->getAction();
        $id = $this->_req->getId();
        $controller->$action($id);
    }

    /**
     * Gets the controller from Request and returns a controller object
     * @return <type>
     */
    public function invokeController() {
        $controller = $this->_req->getController();
        return ActionController::factory($controller, $this->_req);
    }

}