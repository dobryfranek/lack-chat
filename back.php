<?php

session_start();

define("MESSAGES_FILE", "messages.json");

foreach (array(MESSAGES_FILE, ) as $path) {
    if (!file_exists($path)) {touch($path);}
}

?>