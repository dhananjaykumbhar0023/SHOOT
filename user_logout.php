<?php
session_start();
include 'auth_helper.php';

// Destroy user session
destroyUserSession();

// Redirect to sign in page
header('Location: sign.php');
exit;
?>