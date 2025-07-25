<?php
/* Collect config values from env variables */
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_USER', getenv('DB_USER') ?: 'atik');
define('DB_PASS', getenv('DB_PASS'));
define('DB_NAME', getenv('DB_NAME') ?: 'secure_fs');
define('TZ', getenv('TZ') ?: 'Asia/Dhaka');
const UPLOAD_DIR = __DIR__ . '/../storage/uploads';
const DOWNLOAD_LINK_EXPIRY = 5 * 60; // 5 minutes. Set to 0 for never to expire.
