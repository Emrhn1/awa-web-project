<?php

if (preg_match('/^\/api(\/|$)/', $_SERVER['REQUEST_URI'])) {
    require __DIR__ . '/public/api/index.php';
    return true;
}
return false; 
