<?php

// Include the Database class file
require_once 'dbclass.php';

// Instantiate the Database class
$db = new Database();

// Example usage of insert method
$data = ['name' => 'John', 'email' => 'john@example.com'];
$db->insert('users', $data);

// Example usage of select method
$users = $db->select("SELECT * FROM users WHERE id = :id", ['id' => 1]);
print_r($users);

// Example usage of update method
$data = ['name' => 'Updated Name', 'email' => 'updated@example.com'];
$db->update('users', $data, 'id = :id', ['id' => 1]);

// Example usage of delete method
$db->delete('users', 'id = :id', ['id' => 1]);

?>
