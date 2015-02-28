<?php
include('inc.php');

$autoload = require('./../Oppa/Autoload.php');
$autoload->register();

use \Oppa\Database;
use \Oppa\Configuration;

$cfg = [
    'agent' => 'mysqli',
    'database' => [
        'host' => 'localhost', 'name' => 'test',
        'username' => 'test',  'password' => '********',
    ]
];

$db = Database\Factory::build(new Configuration($cfg));
$db->connect();
// pre($db);

\Oppa\Orm::setDatabase($db);

class Users extends \Oppa\Orm {
    protected $table = 'users';
    protected $primaryKey = 'id';
}

$usersObject = new Users();
pre($usersObject);

// $user = $usersObject->find(111111111);
// pre($user);
// pre($user->name);
// prd($user->isFound());

// $users = $usersObject->findAll();
// $users = $usersObject->findAll([1,2,3]);
// $users = $usersObject->findAll('id in(?,?,?)', [1,2,3]);
// pre($users);
// foreach ($users as $user) {
//     pre($user->name);
// }
// $users = $usersObject->findAll([111111111,222222222,33333333]);
// prd($users->isFound());

// $user = $usersObject->entity();
// $user->name = 'Deli';
// $user->old = 35;
// pre($user);
// prd($usersObject->save($user));
// pre($user);

// $user = $usersObject->entity();
// $user->id = 930;
// $user->name = 'Veli';
// $user->old = 45;
// pre($user);
// prd($usersObject->save($user));
// pre($user);

// $result = $usersObject->remove(924);
// $result = $usersObject->remove([924,925,926]);
// prd($result);