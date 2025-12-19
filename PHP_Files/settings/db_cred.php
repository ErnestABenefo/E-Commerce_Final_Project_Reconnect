<?php

//Database credentials
// Settings/db_cred.php
// IMPORTANT: Update these with your InfinityFree credentials from control panel

if (!defined("SERVER")) {
    // For InfinityFree: sql###.infinityfree.com (e.g., sql306.infinityfree.com)
    // For localhost: localhost
    define("SERVER", "localhost");
}

if (!defined("USERNAME")) {
    // For InfinityFree: epiz_#########
    // For localhost: root
    define("USERNAME", "root");
}

if (!defined("PASSWD")) {
    // For InfinityFree: Your MySQL password from control panel
    // For localhost: "" (empty)
    define("PASSWD", "");
}

if (!defined("DATABASE")) {
    // For InfinityFree: epiz_#########_reconnectdb2
    // For localhost: reconnectdb2
    define("DATABASE", "reconnectdb2");
}


?>