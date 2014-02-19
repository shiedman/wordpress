<?php
if(defined('DB_NAME') || !defined('SAE_MYSQL_DB'))return;

define('DB_NAME', SAE_MYSQL_DB);
define('DB_USER', SAE_MYSQL_USER);
define('DB_PASSWORD', SAE_MYSQL_PASS);
define('DB_HOST', SAE_MYSQL_HOST_M.':'.SAE_MYSQL_PORT);

defined('SAE_STORAGE') or die('<b>you must set SAE_STORAGE in wp-config.php, set it to FALSE if you do not need any uploads. ANYWAY, SET IT!</b>');
/** SAE Storage Domain */
if(SAE_STORAGE) {
    /** File Upload Dir */
    define('SAE_UPLOAD_DIR','saestor://'.SAE_STORAGE.'/uploads');
    /** File URL PATH */
    define('SAE_UPLOAD_URL', 'http://' . $_SERVER['HTTP_APPNAME'] . '-'.SAE_STORAGE.'.stor.sinaapp.com/uploads');
}

