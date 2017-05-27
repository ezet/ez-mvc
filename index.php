<?php

/*
 * @author     Lars Kristian Dahl <http://www.krisd.com>
 * @version    $Id$
 * @copyright  Copyright (c) 2011 Lars Kristian Dahl <http://www.krisd.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */

namespace ezmvc;

use ezmvc\libraries\Request;
use ezmvc\controllers\FrontController;
use ezmvc\libraries\AuthException;
use ezmvc\libraries\Config;

session_start();

// for production
//ini_set("display_errors", "Off");
//error_reporting(E_ALL);

$site_path = realpath(dirname(__FILE__));

// define the site path
define('__BASE_PATH', $site_path);

// set the application path
define('__APP_PATH', __BASE_PATH . '/application');

// set the library path
define('__LIB_PATH', __APP_PATH . '/libraries');

// set the public web root path
define('__BASE_URL', 'http://' . $_SERVER['SERVER_NAME']);

include __BASE_PATH . '/bootstrapper.php';

// instantiate FrontController and perform routing
try {
    FrontController::factory()->route();

    // catch any exceptions
} catch (AuthException $e) {
    $e->redirect(Config::get('loginpage'));
} catch (\ErrorException $e) {
    trigger_error($e->getMessage(), E_USER_ERROR);
//    die('Fatal exception: ' . $e->getMessage());
} catch (\Exception $e) {
    trigger_error($e->getMessage(), E_USER_ERROR);
//    die('Exception caught: ' . $e->getMessage());
//    var_dump($e);
}