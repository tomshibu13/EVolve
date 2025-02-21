<?php
session_start();
if($_SESSION['is_admin'] == true){
    header("Location: admindash.php");
}else{
    echo "Hello User";
    header("Location: profile.php");
}
?>