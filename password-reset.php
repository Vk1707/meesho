<?php
include('inc/chkAuth.php');
if(isset($_GET['uid'])){
    $title = 'Reset Password';
    $subTitle = 'Reset Password';
}else{
    $title = 'Change Password';
    $subTitle = 'Change Password';
}
?>

<?php
include('layouts/layoutTop.php'); 
include('inc/dbclass.php'); 

if(isset($_GET['uid'])){
    $user_id = $_GET['uid'];
}else{
    $user_id = $_SESSION['adminId'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_pass = $_POST['user_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    $db = new Database();

    if (empty($user_pass) || empty($confirm_pass)) {
        $msg = "All fields are required.";
        $r = "f";
    } elseif ($user_pass !== $confirm_pass) {
        $msg = "Passwords do not match.";
        $r = "f";
    } else {
        $user = $db->select_single("SELECT * FROM tbl_users WHERE id = :id", ['id' => $user_id]);
        // echo $user['user_pass'];
        if (!$user) {
            $msg = "Email not found.";
            $r = "f";
        } else {
            $salt = bin2hex(random_bytes(16)); // Generate a new salt
            $hashed_password = password_hash($user_pass . $salt, PASSWORD_DEFAULT);

            $update_data = [
                'user_pass' => $hashed_password,
                'user_salt' => $salt
            ];

            if ($db->update("tbl_users",$update_data,'id=:id',['id'=>$user_id])) {
                $msg = "Password reset successfully!";
                $r = "s";
            } else {
                $msg = "Failed to reset password.";
                $r = "f";
            }
        }
    }

    $url = "password-reset.php?msg=$msg&r=$r";
    if (isset($_GET['uid'])) {
        $url .= "&uid=" . urlencode($_GET['uid']);
    }
    gotopage($url);
    exit();
}
?>

<div class="card h-100 p-0 radius-12">
    <div class="card-body p-24">
        <div class="row">
            <div class="col-xxl-6 col-xl-8 col-lg-10 mx-auto">
                <div class="card border">
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="row mb-20">
                                <div class="col-12">
                                    <label for="user_pass" class="form-label fw-semibold text-primary-light text-sm mb-8">
                                        New Password <span class="text-danger-600">*</span>
                                    </label>
                                    <input type="password" class="form-control radius-8" id="user_pass" name="user_pass" placeholder="Enter new password" required>
                                </div>
                            </div>

                            <div class="row mb-20">
                                <div class="col-12">
                                    <label for="confirm_pass" class="form-label fw-semibold text-primary-light text-sm mb-8">
                                        Confirm Password <span class="text-danger-600">*</span>
                                    </label>
                                    <input type="password" class="form-control radius-8" id="confirm_pass" name="confirm_pass" placeholder="Confirm new password" required>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-center gap-3">
                                <button type="submit" class="btn btn-primary border border-primary-600 text-md px-56 py-12 radius-8">
                                    <?= $title ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/layoutBottom.php'; ?>
