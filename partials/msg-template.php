<?php 
if(isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']); // for security
    $type = (isset($_GET['r']) && $_GET['r'] === 'f') ? 'danger' : 'success';
    $icon = ($type === 'danger') ? 'mdi:alert-circle-outline' : 'akar-icons:double-check';
    $toastId = uniqid('toast_');

    echo "
    <div id='toast-container' style='position: fixed; top: 20px; right: 20px; z-index: 9999;'>
        <div id='{$toastId}' class='toast alert alert-{$type} bg-{$type}-100 text-{$type}-600 border-{$type}-600 px-4 py-3 mb-3 fw-semibold radius-4 d-flex align-items-center justify-content-between' role='alert'>
            <div class='d-flex align-items-center gap-2'>
                <iconify-icon icon='{$icon}' class='icon text-xl'></iconify-icon>
                {$msg}
            </div>
            <button class='remove-button text-{$type}-600 text-xxl line-height-1' data-toast-id='{$toastId}'>
                <iconify-icon icon='iconamoon:sign-times-light' class='icon'></iconify-icon>
            </button>
        </div>
    </div>";
}
?>
