<?php

/**
 * @author     Lars Kristian Dahl <http://www.krisd.com>
 * @copyright  Copyright (c) 2011 Lars Kristian Dahl <http://www.krisd.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 * @version    SVN: $Id$
 */

namespace ezmvc\libraries;

use ezmvc\models\dao\BaseDAO;
use ezmvc\libraries\Session;

/**
 * Manages user authentication and other user services
 *
 * @author  Lars Kristian Dahl <http://www.krisd.com>
 */
class WebUser {

    /**
     * Holds the usermodel if a user is logged in
     * @var <type>
     */
    private $_user;
    /**
     * Key for aiding in password hashing
     * @var <type>
     */
    private static $_key = 'bordsalt';
    /**
     * Length of generated salt
     * @var <type>
     */
    private static $_saltlen = 10;
    /**
     * Session object for handling the session
     * @var <type>
     */
    private $_session;

    /**
     * Basic factory method, injects a Session object
     * @return self
     */
    public static function factory() {
        return new self(Session::factory());
    }

    /**
     * Constructor
     * Stores the session object
     * @param Session $session
     */
    public function __construct(Session $session) {
        $this->_session = $session;
    }

    /**
     * Authenticates the given username and password against the database,
     * Logs the user in and returns a user object on success, otherwise returns false
     * @param <type> $username
     * @param <type> $password
     * @return <type>
     */
    public function authenticate($username, $password) {
        // Find the user in the db
        $dao = BaseDAO::factory('User');
        $user = $dao->findByUsername($username);

        // Perform validation
        if ($user == NULL || self::getHash($password, $user->Password) !== $user->Password) {
            return false;
        } else {
            // update information, such as login count and last login
            $dao->updateLoginInfo($user->UserId);
            $this->_user = $user;
            // complete the login
            $this->_login();
            return $user;
        }
    }

    /**
     * Performs a login by setting the session variables
     */
    private function _login() {
        $user = $this->_user;
        $this->_session->open();
        $this->_session->id = $user->UserId;
        $this->_session->firstName = $user->FirstName;
        $this->_session->lastName = $user->LastName;
        ($user->Admin) ? $this->_session->admin = $user->Admin : null;
    }

    /**
     * Performs a login, just like WebUser::login(), but is callable by the client,
     * bypassing authentication (used after registation eg.)
     * @param <type> $user
     */
    public function forceLogin($user) {
        $this->_session->open();
        $this->_session->id = $user->UserId;
        $this->_session->firstName = $user->FirstName;
        $this->_session->lastName = $user->LastName;
        ($user->Admin) ? $this->_session->admin = $user->Admin : null;
    }

    /**
     * Logs a user out
     */
    public function logout() {
//        $this->_session->destroy();
        $this->_session->remove('id');
        $this->_session->remove('admin');
    }

    /**
     * Creates a string digest, using a defined key and a runtime-generated salt.
     * The salt is then stored together with the password, so it can be retrieved and used for comparisons
     * The hash is then created by using sha256.
     * By using a separate key and a salt, an attacker would need to compromise both the DB aswell as getting
     * the key, length of the salt, and how the salt is stored.
     * @param <type> $string
     * @param <type> $salt
     * @return <type>
     */
    public static function getHash($string, $salt=null) {
        $key = self::$_key;
        if ($salt) {
            $salt = substr($salt, 0, self::$_saltlen);
        } else {
            $salt = substr(hash('sha256', uniqid(rand(), true) . $key . microtime()), 0, self::$_saltlen);
        }
        return $salt . hash('sha256', $string . $salt . $key);
    }

    /**
     * Returns the userid if logged in
     * @return <type>
     */
    public function getId() {
        return $this->_session->id;
    }

    /**
     * Returns the users name if logged in
     * @return <type>
     */
    public function getName() {
        return $this->_session->firstName . ' ' . $this->_session->lastName;
    }

    /**
     * Returns the userlevel if logged in
     * @return <type>
     */
    public function getUserLevel() {
        if ($this->isLogged()) {
            return ($this->isAdmin()) ? 2 : 1;
        } else {
            return 0;
        }
    }

    /**
     * Checks wether a user is logged in
     * @return <type>
     */
    public function isLogged() {
        return $this->getId();
    }

    /**
     * Checks whether a user is logged in as the owner of a specified entity
     * @param <type> $table
     * @param <type> $value
     * @return <type>
     */
    public function isOwner($table, $value) {
        if (!$userid = $this->getId()) {
            return false;
        }
        $dao = BaseDAO::factory($table);
        $model = $dao->findById($value);
        return ($model->UserId === $userid) ? $userid : false;
    }

    /**
     * Checks if a user is logged in as an admin
     * @return <type>
     */
    public function isAdmin() {
        return $this->_session->admin;
    }

    /**
     * Adds a post to a list of visited post in users session
     * @param <type> $postid
     */
    public function addVisited($postid) {
        $this->_session->setArrayValue('visited', $postid, true);
    }

    /**
     * Checks whether a used has visited specified page during current session
     * @param <type> $postid
     * @return <type>
     */
    public function hasVisited($postid) {
        return $this->_session->getArrayValue('visited', $postid);
    }

}