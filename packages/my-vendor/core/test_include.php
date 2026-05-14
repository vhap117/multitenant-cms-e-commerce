<?php
file_put_contents('test_stub.php', '<?php return new class { public $prop = 1; };');
$a = include 'test_stub.php';
$b = include 'test_stub.php';
var_dump($a);
var_dump($b);
