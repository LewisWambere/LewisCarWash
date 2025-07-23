<?php
session_start();
session_unset();
session_destroy();
header("Location: login.php?message=logout_success");
exit;
