<?php
/**
 * Very simple test
 */
error_log("API Called");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST: " . print_r($_POST, true));
echo "OK";
