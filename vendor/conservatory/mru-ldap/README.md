MRU LDAP
========

An LDAP library including authentication and retrieving user information.

Usage
=====

```php
// Setup the class
$ldap = new \MRU\Ldap(
    $config['auth']['ldap']['host'],
    $config['auth']['ldap']['port'],
    $config['auth']['ldap']['dn'],
    $config['auth']['ldap']['password'],
    $config['auth']['ldap']['search_dn']
);

// Authenticate
$username = "jsmith";
$password = "happybirthday";
if ($ldap->authenticate($username, $password)) {
    echo "Success!";
}

// Get User Info
$info = $ldap->getUser($username);
print_r($info);

    Array
    (
        [uid] => jsmith
        [givenName] => Joe
        [sn] => Smith
        [employeeNumber] => 123456789
    )
```
