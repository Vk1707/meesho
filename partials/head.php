<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title)?$title:'TMS' ?></title>
    <link rel="icon" type="image/png" href="uploads/logos/<?= $_SESSION['logoIcon']??'logogr-icon.png' ?>" sizes="16x16">
    <!-- remix icon font css  -->
    <link rel="stylesheet" href="assets/css/remixicon.css">
    <!-- BootStrap css -->
    <link rel="stylesheet" href="assets/css/lib/bootstrap.min.css">
    <!-- Apex Chart css -->
    <link rel="stylesheet" href="assets/css/lib/apexcharts.css">
    <!-- Data Table css -->
    <link rel="stylesheet" href="assets/css/lib/dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <!-- Text Editor css -->
    <link rel="stylesheet" href="assets/css/lib/editor-katex.min.css">
    <link rel="stylesheet" href="assets/css/lib/editor.atom-one-dark.min.css">
    <link rel="stylesheet" href="assets/css/lib/editor.quill.snow.css">
    <!-- Date picker css -->
    <link rel="stylesheet" href="assets/css/lib/flatpickr.min.css">
    <!-- Calendar css -->
    <link rel="stylesheet" href="assets/css/lib/full-calendar.css">
    <!-- Vector Map css -->
    <link rel="stylesheet" href="assets/css/lib/jquery-jvectormap-2.0.5.css">
    <!-- Popup css -->
    <link rel="stylesheet" href="assets/css/lib/magnific-popup.css">
    <!-- Slick Slider css -->
    <link rel="stylesheet" href="assets/css/lib/slick.css">
    <!-- prism css -->
    <link rel="stylesheet" href="assets/css/lib/prism.css">
    <!-- file upload css -->
    <link rel="stylesheet" href="assets/css/lib/file-upload.css">
    <!-- selectize css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.13.6/css/selectize.default.min.css">

    <link rel="stylesheet" href="assets/css/lib/audioplayer.css">
    <!-- main css -->
    <link rel="stylesheet" href="assets/css/style.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.min.css" rel="stylesheet" />
    <!-- Cropper.js for Image Cropping -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">

    <style>
    /* Ensure Chosen dropdowns fit within Bootstrap modals */
    .chosen-container {
        width: 100% !important;
    }
    .chosen-drop {
        z-index: 2000;
    }
    .chosen-choices{
        border: 1px solid var(--input-form-light);
        border-radius: 0.5rem;
        padding: 0.375rem 0.75rem !important;
    }
</style>
    <?php echo (isset($style) ? $style   : '')?>
</head>