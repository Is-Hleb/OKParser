<?php
$content = file_get_contents('./database/migrations/users.txt');
$array = explode("\n", $content);

var_dump($array);