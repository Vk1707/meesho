<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<?php include 'partials/head.php' ?>

<body>
    
    <?php include 'partials/sidebar.php' ?>
    
    <main class="dashboard-main active">
    <?php include 'partials/navbar.php' ?>

        <div class="dashboard-main-body">

            <?php include 'partials/breadcrumb.php' ?>