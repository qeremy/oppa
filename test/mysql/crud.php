<?php
include('_inc.php');

use Oppa\Database;

$cfg = [
    'agent' => 'mysql',
    'profiling' => true,
    'database' => [
        'fetch_type' => 'object',
        'charset'    => 'utf8',
        'timezone'   => '+00:00',
        'host'       => 'localhost',
        'name'       => 'test',
        'username'   => 'test',
        'password'   => '********',
    ]
];

$db = new Database($cfg);
$db->connect();

$agent = $db->getLink()->getAgent();

$result = $agent->select('users', ['id','name']);
// $result = $agent->selectOne('users', '*', 'old > ?', [50]);
// $result = $agent->insert('users', ['name' => 'Ferhat', 'old' => 50]);
// $result = $agent->insert('users', [['name' => 'Ferhat', 'old' => 50],['name' => 'Serhat', 'old' => 60]]);
// $result = $agent->update('users', ['name' => 'Veli', 'old' => 60], 'id=?', [6]);
// $result = $agent->delete('users', 'id=?', [6]);
// $result = $agent->delete('users', 'id in (?,?,?)', [4,5,6]);
// pre($result);

pre($agent);
// pre($db);
