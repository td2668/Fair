<?php

namespace MRU;

class Ldap {

    /**
     * The server hostname
     *
     * @var string
     */
    protected $host;

    /**
     * The server port
     *
     * @var int
     */
    protected $port;

    /**
     * The distinguished name / username to login with
     *
     * @var string
     */
    protected $distinguishedName;

    /**
     * The password to login with
     *
     * @var string
     */
    protected $password;

    /**
     * The DN to search
     *
     * @var string
     */
    protected $searchDn;

    /**
     * The LDAP server connection
     *
     * @var resource
     */
    protected $connection;

    /**
     * Construct the class and connect to the server
     *
     * @param string $host The LDAP server hostname
     * @param int $port The LDAP server port
     * @param string $distinguishedName The LDAP server DN/username
     * @param string $password The LDAP server password
     * @param string $searchDn The DN to search
     */
    function __construct($host, $port, $distinguishedName, $password, $searchDn) {
        $this->host = $host;
        $this->port = $port;
        $this->distinguishedName = $distinguishedName;
        $this->password = $password;
        $this->searchDn = $searchDn;
    }

    /**
     * Connect to the LDAP server
     *
     * @throws Exception if can't connect or bind to the server
     */
    function connect() {
        $this->connection = ldap_connect($this->host, $this->port);
        if (!$this->connection) {
            throw \Exception("Could not connect to LDAP server");
        }

        if (!ldap_bind($this->connection, $this->distinguishedName, $this->password)) {
            ldap_close($this->connection);
            throw \Exception("Could not bind to LDAP server");
        }
    }

    /**
     * Disconnect from the LDAP server
     *
     * @return boolean
     */
    function disconnect() {
        return ldap_close($this->connection);
    }

    /**
     * Authenticate a user against the LDAP server
     *
     * @param string $username The username
     * @param string $password The users password
     *
     * @return boolean
     */
    function authenticate($username, $password) {
        // No anonymous logins
        if (strlen($password) == 0) {
            return false;
        }

        $this->connect();

        $attributes = array("dn", "uid");
        $entry = $this->getUserEntry($username, $attributes);
        if ($entry) {
            $dn = ldap_get_dn($this->connection, $entry);
            if ($dn) {
                // Got DN for $username
                $bind = ldap_bind($this->connection, $dn, $password);
                if ($bind) {
                    // Bound to server as $username
                    ldap_close($this->connection);
                    return true;
                }
            }
        }

        $this->disconnect();

        return false;
    }

    /**
     * Get the users information
     *
     * @param string $username The username
     *
     * @return array or null if not found
     */
    function getUser($username) {

        $this->connect();

        if (strstr($username, "*")) {
            // Wildcard NOT ALLOWED in $username
            return false;
        }

        $user = null;
        $attributes = array("uid", "givenName", "sn", "employeeNumber");
        $entry = $this->getUserEntry($username, $attributes);
        if ($entry) {
            $info = ldap_get_attributes($this->connection, $entry);
            if ($info) {
                $user = array();
                foreach ($info as $k => $values) {
                    if (is_array($values)) {
                        $user[$k] = $values[0];
                    }
                }
            }
        }

        $this->disconnect();

        return $user;
    }

    /**
     * Get the LDAP entry for a user
     *
     * @param string $username The username
     * @param array $attributes The attributes to return
     *
     * @return resource
     */
    private function getUserEntry($username, $attributes) {
        $filter = "(uid=$username)";
        $results = ldap_search($this->connection, $this->searchDn, $filter, $attributes);
        if ($results) {
            // Search was successful
            if (ldap_count_entries($this->connection, $results) > 0) {
                // Found matching entries
                return ldap_first_entry($this->connection, $results);
            }
        }

        return null;
    }

}
