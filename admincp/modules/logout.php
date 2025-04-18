<?php
session_start();
session_unset();
session_destroy();
header("Location:http://localhost/web_mysql1/admincp/modules/login_admin.php");
exit();
?>