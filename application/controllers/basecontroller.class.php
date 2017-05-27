<?php

/**
 * @author     Lars Kristian Dahl <http://www.krisd.com>
 * @copyright  Copyright (c) 2011 Lars Kristian Dahl <http://www.krisd.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 * @version    SVN: $Id$
 */

namespace ezmvc\controllers;

use ezmvc\libraries as lib;

/**
 * Base controller for all Controllers.
 *
 * @author  Lars Kristian Dahl <http://www.krisd.com>
 */
abstract class BaseController {

    protected $_req;

    /**
     * Constructor
     * @param lib\Request $req
     */
    public function __construct(lib\Request $req) {
        $this->_req = $req;
    }

    // TODO implement forward function
    protected function _forward($controller, $action) {
        
    }

    /**
     * Helper function for controllers, redirects to specified url
     * @param <type> $url
     */
    protected function _redirect($url='') {
        $this->_req->redirect(__BASE_URL . '/' . $url);
    }

    /**
     * Helper function, returns to last page
     * @param <type> $anchor
     */
    protected function _return($anchor=null) {
        $this->_req->backward($anchor);
    }

}