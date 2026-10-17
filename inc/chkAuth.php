<?php
session_start();
if(!isset($_SESSION['adminId']))
  header("Location: index.php");
?>