<?php
// Logout endpoint: accept only a CSRF-protected POST, clear the session, and return to login.
require_once __DIR__.'/helpers.php'; if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: index.php');exit;}verify_csrf();$_SESSION=[];if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',(int)time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);}session_destroy();header('Location: login.php');exit;
