<?php

/**
 * @author     Lars Kristian Dahl <http://www.krisd.com>
 * @copyright  Copyright (c) 2011 Lars Kristian Dahl <http://www.krisd.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 * @version    SVN: $Id$
 */

namespace ezmvc;

//set_include_path(__APP_PATH);
//set_include_path(__BASE_PATH);


include __LIB_PATH . '/config.class.php';

// disable default autoloader
spl_autoload_register(null, false);
spl_autoload_extensions('.php, .class.php');

// DEBUG autoloader vardump
function autoLoader($class) {
    $class = str_replace(__NAMESPACE__, '', $class);
    $class = str_replace('\\', '/', $class);
    $file = __APP_PATH . strtolower($class) . '.class.php';
    if (file_exists($file)) {
        include $file;
    } else {
        throw new \Exception('Could not locate class: ' . $class);
        return false;
    }
}

// register my autoloader
spl_autoload_register(__NAMESPACE__ . '\autoLoader');