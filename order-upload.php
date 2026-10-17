<?php
include 'inc/chkAuth.php';
include 'inc/dbclass.php';

$title = 'Order Sheet Upload';
$subTitle = 'Order Sheet Upload';

$db = new Database();
$admin_id = (int)$_SESSION['adminId'];

$msg = '';
$r = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['order_file']['tmp_name'])) {
    $file = $_FILES['order_file']['tmp_name'];
    $fileName = $_FILES['order_file']['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($ext !== 'csv') {
        $msg = "Only .csv files are allowed.";
        $r = "f";
    } elseif (!is_readable($file)) {
        $msg = "Cannot read the uploaded file.";
        $r = "f";
    } else {
        // ────────────────────────────────────────────────
        // Process CSV
        // ────────────────────────────────────────────────
        $inserted = 0;
        $skipped = 0;
        $errors = 0;
        $lineNumber = 0;

        if (($handle = fopen($file, "r")) !== false) {
            // Skip header row
            $header = fgetcsv($handle);
            $lineNumber = 1;

            $db->beginTransaction();

            try {
                while (($row = fgetcsv($handle)) !== false) {
                    $lineNumber++;

                    // Skip completely empty rows
                    if (empty(array_filter($row))) continue;

                    // Your CSV has 13 columns (including first "Reason for Credit Entry")
                    if (count($row) < 13) {
                        $errors++;
                        continue;
                    }

                    // Remove first column (Reason for Credit Entry) — we don't use it
                    array_shift($row);

                    // Now unpack the remaining 12 columns
                    [
                        $sub_order_no,
                        $catalog_id,
                        $order_date_str,
                        $order_source,
                        $customer_state,
                        $product_name,
                        $sku,
                        $size,
                        $quantity,
                        $listed_price,
                        $discounted_price,
                        $packet_id
                    ] = array_pad($row, 12, '');

                    $sub_order_no = trim($sub_order_no);
                    if (empty($sub_order_no)) {
                        $skipped++;
                        continue;
                    }

                    // ─── Date parsing ───────────────────────────────────────
                    $order_date = null;
                    $order_date_str = trim($order_date_str);

                    if (!empty($order_date_str)) {
                        $possible_formats = [
                            'n/j/Y',     // 1/1/2026
                            'm/d/Y',     // 01/01/2026
                            'd/m/Y',     // 01/01/2026 (EU)
                            'd-m-Y',     // 01-01-2026
                            'Y-m-d',     // 2026-01-01 (ISO)
                            'm-d-Y',     // 01-01-2026 (US dashed)
                            'Y/m/d',     // 2026/01/01
                        ];

                        foreach ($possible_formats as $fmt) {
                            $dt = DateTime::createFromFormat($fmt, $order_date_str);
                            if ($dt !== false) {
                                $year = (int)$dt->format('Y');
                                if ($year >= 2020 && $year <= 2035) { // reasonable range
                                    $order_date = $dt->format('Y-m-d');
                                    break;
                                }
                            }
                        }

                        // Last resort fallback
                        if ($order_date === null && strtotime($order_date_str)) {
                            $order_date = date('Y-m-d', strtotime($order_date_str));
                        }

                        // Optional: log dates that still fail (remove in production)
                        if ($order_date === null) {
                            error_log("Date parse failed on line $lineNumber - sub_order: $sub_order_no - value: '$order_date_str'");
                        }
                    }

                    // ─── Clean numeric values ───────────────────────────────
                    $quantity = (int) filter_var($quantity, FILTER_SANITIZE_NUMBER_INT) ?: 0;
                    $listed_price = (float) str_replace(',', '', trim($listed_price));
                    $discounted_price = (float) str_replace(',', '', trim($discounted_price));

                    // ─── Check for duplicate ────────────────────────────────
                    $exists = $db->select_single(
                        "SELECT 1 FROM tbl_orders WHERE sub_order_no = ?",
                        [$sub_order_no]
                    );

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    // ─── Prepare insert data ────────────────────────────────
                    $data = [
                        'admin_id'          => $admin_id,
                        'od_status'         => trim($status ?? 'Pending'), // fallback if status missing
                        'sub_order_no'      => $sub_order_no,
                        'catalog_id'        => trim($catalog_id),
                        'order_date'        => $order_date,              // now correctly parsed
                        'order_source'      => trim($order_source),
                        'cust_state'        => trim($customer_state),
                        'prod_name'         => trim($product_name),
                        'sku'               => trim($sku),
                        'size'              => trim($size),
                        'qty'               => $quantity,
                        'supp_list_price'   => $listed_price,
                        'supp_disc_price'   => $discounted_price,
                        'packet_id'         => trim($packet_id),
                        'created_at'        => date('Y-m-d H:i:s'),
                    ];

                    if ($db->insert('tbl_orders', $data)) {
                        $inserted++;
                    } else {
                        $errors++;
                    }
                }

                fclose($handle);
                $db->commit();

                $msg = "Upload completed.<br>
                        <strong>Inserted:</strong> $inserted<br>
                        <strong>Skipped (duplicates/empty):</strong> $skipped<br>
                        <strong>Errors (bad format):</strong> $errors";

                $r = $inserted > 0 ? "s" : "i"; // success / info
            } catch (Exception $e) {
                $db->rollBack();
                $msg = "Database error: " . htmlspecialchars($e->getMessage());
                $r = "f";
                fclose($handle);
            }
        } else {
            $msg = "Failed to open the CSV file.";
            $r = "f";
        }
    }

    gotopage("order-upload.php?msg=" . urlencode($msg) . "&r=$r");
}
?>

<?php include 'layouts/layoutTop.php'; ?>

<div class="card h-100 p-0 radius-12">
    <div class="card-body p-24">
        <div class="row justify-content-center">
            <div class="col-xxl-12 col-xl-8 col-lg-10">
                <div class="card border">
                    <div class="card-body">

                        <?php if (!empty($msg)): ?>
                        <div class="alert alert-<?= $r === 's' ? 'success' : ($r === 'f' ? 'danger' : 'info') ?> mb-20">
                            <?= nl2br($msg) ?>
                        </div>
                        <?php endif; ?>

                        <form action="" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate id="orderUploadForm">
                            <div class="row mb-20">
                                <div class="col-md-6">
                                    <label for="order_file" class="form-label fw-semibold text-primary-light text-sm mb-8">
                                        Order Sheet File (CSV) <span class="text-danger-600">*</span>
                                    </label>
                                    <input type="file" class="form-control radius-8" id="order_file" name="order_file" accept=".csv" required>
                                    <div class="invalid-feedback">Please upload a valid CSV file.</div>
                                    <p class="text-sm text-secondary-light mt-2">
                                        Expected columns (in this order):<br>
                                        Reason for Credit Entry, Sub Order No, Catalog ID, Order Date, Order source, 
                                        Customer State, Product Name, SKU, Size, Quantity, 
                                        Supplier Listed Price (Incl. GST + Commission), 
                                        Supplier Discounted Price (Incl GST and Commision), Packet Id
                                    </p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-center gap-3">
                                <button type="submit" class="btn btn-primary border border-primary-600 text-md px-56 py-12 radius-8">
                                    Upload Orders
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

<script>
$(document).ready(function () {
    "use strict";
    const form = document.querySelector("#orderUploadForm");
    form.addEventListener("submit", function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add("was-validated");
    }, false);
});
</script>