<?php
include 'inc/chkAuth.php';
include 'inc/dbclass.php';
require 'vendor/autoload.php';  // For PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;

$title    = 'Order History - Monthly Summary';
$subTitle = 'Orders by Month';
$db       = new Database();
$admin_id = (int)$_SESSION['adminId'];
$msg      = '';
$r        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_payment'])) {
    // Handle upload for one or two files
    $files = [$_FILES['payment_file']];
    if (isset($_FILES['payment_file_next_month']) && !empty($_FILES['payment_file_next_month']['tmp_name'])) {
        $files[] = $_FILES['payment_file_next_month'];
    }

    $inserted_pymts = 0;
    $skipped_pymts   = 0;
    $errors_pymts   = 0;
    $inserted_ads   = 0;
    $skipped_ads     = 0;
    $errors_ads     = 0;
    $inserted_prods = 0;
    $skipped_prods   = 0;

    foreach ($files as $file) {
        $fileName = $file['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $tmp = $file['tmp_name'];

        if ($ext !== 'xlsx') {
            $msg .= "Only .xlsx files are allowed.<br>";
            $r = 'f';
            continue;
        }

        try {
            $spreadsheet = IOFactory::load($tmp);

            // ─── Process Order Payments Sheet ───────────────────────────
            $sheet = $spreadsheet->getSheetByName('Order Payments');
            if ($sheet) {
                $rows = $sheet->toArray(null, true, true, true);  // Keys: 'A' => value, etc.
                $db->beginTransaction();

                // Data starts from row 3 (based on structure)
                for ($rowNum = 3; $rowNum <= $sheet->getHighestRow(); $rowNum++) {
                    $row = $rows[$rowNum] ?? [];
                    if (empty(array_filter($row))) continue;  // Skip empty

                    // Map columns (A to AO ~43 columns)
                    $sub_order_no              = trim($row['A'] ?? '');
                    $order_date_str            = trim($row['B'] ?? '');
                    $dispatch_date_str         = trim($row['C'] ?? '');
                    $product_name              = trim($row['D'] ?? '');
                    $supplier_sku              = trim($row['E'] ?? '');
                    $catalog_id                 = trim($row['F'] ?? '');
                    $order_source                 = trim($row['G'] ?? '');
                    $live_order_status         = trim($row['H'] ?? '');
                    $product_gst               = (float) ($row['I'] ?? 0);
                    $listing_price             = (float) ($row['J'] ?? 0);
                    $quantity                  = (int) ($row['K'] ?? 1);
                    $transaction_id            = trim($row['L'] ?? '');
                    $payment_date_str          = trim($row['M'] ?? '');
                    $final_settlement          = (float) ($row['N'] ?? 0);
                    $price_type                = trim($row['O'] ?? '');
                    $total_sale_amount         = (float) ($row['P'] ?? 0);
                    $total_sale_return_amount  = (float) ($row['Q'] ?? 0);
                    $fixed_fee                 = (float) ($row['R'] ?? 0);
                    $warehousing_fee           = (float) ($row['S'] ?? 0);
                    $return_premium            = (float) ($row['T'] ?? 0);
                    $return_premium_return     = (float) ($row['U'] ?? 0);
                    $meesho_commission_perc    = (float) ($row['V'] ?? 0);
                    $meesho_commission         = (float) ($row['W'] ?? 0);
                    $meesho_gold_platform_fee  = (float) ($row['X'] ?? 0);
                    $meesho_mall_platform_fee  = (float) ($row['Y'] ?? 0);
                    $fixed_fee2                = (float) ($row['Z'] ?? 0);
                    $warehousing_fee2          = (float) ($row['AA'] ?? 0);
                    $return_shipping_charge    = (float) ($row['AB'] ?? 0);
                    $gst_compensation          = (float) ($row['AC'] ?? 0);
                    $shipping_charge           = (float) ($row['AD'] ?? 0);
                    $other_support_service     = (float) ($row['AE'] ?? 0);
                    $waivers                   = (float) ($row['AF'] ?? 0);
                    $net_other_support         = (float) ($row['AG'] ?? 0);
                    $gst_on_net_other          = (float) ($row['AH'] ?? 0);
                    $tcs                       = (float) ($row['AI'] ?? 0);
                    $tds_rate                  = (float) ($row['AJ'] ?? 0);
                    $tds                       = (float) ($row['AK'] ?? 0);
                    $compensation              = (float) ($row['AL'] ?? 0);
                    $claims                    = (float) ($row['AM'] ?? 0);
                    $recovery                  = (float) ($row['AN'] ?? 0);
                    $compensation_reason       = trim($row['AO'] ?? '');
                    $claims_reason             = trim($row['AP'] ?? '');
                    $recovery_reason           = trim($row['AQ'] ?? '');

                    if (empty($sub_order_no)) {
                        $skipped_pymts++;
                        continue;
                    }

                    // Parse dates
                    $order_date = null;
                    if ($order_date_str) {
                        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $order_date_str);
                        if ($dt) $order_date = $dt->format('Y-m-d H:i:s');
                    }
                    $dispatch_date = DateTime::createFromFormat('Y-m-d', $dispatch_date_str) ? DateTime::createFromFormat('Y-m-d', $dispatch_date_str)->format('Y-m-d') : null;
                    $payment_date = DateTime::createFromFormat('Y-m-d', $payment_date_str) ? DateTime::createFromFormat('Y-m-d', $payment_date_str)->format('Y-m-d') : null;

                    // Determine status if blank
                    if (empty($live_order_status)) {
                        if ($final_settlement < 0 && stripos($recovery_reason, 'Affiliate Fee') !== false) {
                            $live_order_status = 'Affiliate Fee';
                        } elseif ($final_settlement > 0 && !empty($claims_reason)) {
                            $live_order_status = 'Wrong Claim';
                        } elseif ($recovery_reason === "Commission Fee on the NMV generated from the short videos shown on the app") {
                            $live_order_status = 'Commission Fee';
                        } else {
                            $live_order_status = 'Unknown';
                        }
                    }

                    // Check duplicate: same sub_order_no AND same status
                    $exists = $db->select_single("SELECT 1 FROM tbl_order_pymts WHERE admin_id =:admin_id AND sub_order_no =:sub_order_no AND live_order_status =:live_order_status", [
                        'admin_id' => $admin_id,
                        'sub_order_no' => $sub_order_no,
                        'live_order_status' => $live_order_status
                    ]);

                    if ($exists) {
                        $skipped_pymts++;
                        error_log("Duplicate payment skipped: Sub Order No: $sub_order_no, Status: $live_order_status");
                        continue;
                    }

                    // Insert payment
                    $data = [
                        'admin_id' => $admin_id,
                        'sub_order_no' => $sub_order_no,
                        'order_date' => $order_date,
                        'dispatch_date' => $dispatch_date,
                        'product_name' => $product_name,
                        'supplier_sku' => $supplier_sku,
                        'catalog_id' => $catalog_id,
                        'order_source' => $order_source,
                        'live_order_status' => $live_order_status,
                        'product_gst' => $product_gst,
                        'listing_price' => $listing_price,
                        'quantity' => $quantity,
                        'transaction_id' => $transaction_id,
                        'payment_date' => $payment_date,
                        'final_settlement' => $final_settlement,
                        'price_type' => $price_type,
                        'total_sale_amount' => $total_sale_amount,
                        'total_sale_return_amount' => $total_sale_return_amount,
                        'fixed_fee' => $fixed_fee,
                        'warehousing_fee' => $warehousing_fee,
                        'return_premium' => $return_premium,
                        'return_premium_return' => $return_premium_return,
                        'meesho_commission_perc' => $meesho_commission_perc,
                        'meesho_commission' => $meesho_commission,
                        'meesho_gold_platform_fee' => $meesho_gold_platform_fee,
                        'meesho_mall_platform_fee' => $meesho_mall_platform_fee,
                        'fixed_fee2' => $fixed_fee2,
                        'warehousing_fee2' => $warehousing_fee2,
                        'return_shipping_charge' => $return_shipping_charge,
                        'gst_compensation' => $gst_compensation,
                        'shipping_charge' => $shipping_charge,
                        'other_support_service' => $other_support_service,
                        'waivers' => $waivers,
                        'net_other_support' => $net_other_support,
                        'gst_on_net_other' => $gst_on_net_other,
                        'tcs' => $tcs,
                        'tds_rate' => $tds_rate,
                        'tds' => $tds,
                        'compensation' => $compensation,
                        'claims' => $claims,
                        'recovery' => $recovery,
                        'compensation_reason' => $compensation_reason,
                        'claims_reason' => $claims_reason,
                        'recovery_reason' => $recovery_reason,
                    ];

                    if ($db->insert('tbl_order_pymts', $data)) {
                        $inserted_pymts++;

                        // Insert/update product if SKU new
                        $prod_exists = $db->select_single(
                            "SELECT 1 FROM tbl_products WHERE admin_id =:admin_id AND sku =:sku",
                            ['admin_id' => $admin_id, 'sku' => $supplier_sku]
                        );
                        if (!$prod_exists && !empty($supplier_sku)) {
                            $prod_data = [
                                'admin_id' => $admin_id,
                                'prod_name' => $product_name,
                                'sku' => $supplier_sku,
                                'prod_gst' => $product_gst,
                                'prod_list_price' => $listing_price,
                                'cost_price' => 0,  // Not in file
                                'packaging_cost' => 0,
                                'misc_cost' => 0,
                            ];
                            if ($db->insert('tbl_products', $prod_data)) {
                                $inserted_prods++;
                            } else {
                                $skipped_prods++;
                            }
                        } else {
                            $skipped_prods++;
                        }
                    } else {
                        $errors_pymts++;
                    }
                }
                $db->commit();
            }

            // ─── Process Ads Cost Sheet ───────────────────────────
            $sheet = $spreadsheet->getSheetByName('Ads Cost');
            if ($sheet) {
                $rows = $sheet->toArray(null, true, true, true);
                $db->beginTransaction();

                // Data starts from row 3 (based on image)
                for ($rowNum = 3; $rowNum <= $sheet->getHighestRow(); $rowNum++) {
                    $row = $rows[$rowNum] ?? [];
                    if (empty(array_filter($row))) continue;

                    $deduction_duration_str = trim($row['A'] ?? '');
                    $deduction_date_str     = trim($row['B'] ?? '');
                    $campaign_id            = trim($row['C'] ?? '');
                    $ad_cost                = (float) ($row['D'] ?? 0);
                    $credits_waivers_discounts = (float) ($row['E'] ?? 0);
                    $ad_cost_incl           = (float) ($row['F'] ?? 0);
                    $gst                    = (float) ($row['G'] ?? 0);
                    $total_ads_cost         = (float) ($row['H'] ?? 0);

                    if (empty($campaign_id)) {
                        $skipped_ads++;
                        continue;
                    }

                    // Parse dates
                    $deduction_duration = DateTime::createFromFormat('Y-m-d', $deduction_duration_str) ? DateTime::createFromFormat('Y-m-d', $deduction_duration_str)->format('Y-m-d') : null;
                    $deduction_date = DateTime::createFromFormat('Y-m-d', $deduction_date_str) ? DateTime::createFromFormat('Y-m-d', $deduction_date_str)->format('Y-m-d') : null;

                    // Check duplicate: campaign_id + deduction_date
                    $exists = $db->select_single(
                        "SELECT 1 FROM tbl_ads_cost WHERE admin_id = :admin_id AND campaign_id = :campaign_id AND deduction_date = :deduction_date AND deduction_duration = :deduction_duration AND total_ads_cost = :total_ads_cost",
                        [
                            'admin_id' => $admin_id,
                            'campaign_id' => $campaign_id,
                            'deduction_date' => $deduction_date,
                            'deduction_duration' => $deduction_duration,
                            'total_ads_cost' => $total_ads_cost
                        ]
                    );

                    if ($exists) {
                        $skipped_ads++;
                        error_log("Duplicate ad cost skipped: Campaign ID: $campaign_id, Deduction Date: $deduction_date, Duration: $deduction_duration, Total Cost: $total_ads_cost");
                        continue;
                    }

                    // Insert
                    $data = [
                        'admin_id' => $admin_id,
                        'deduction_duration' => $deduction_duration,
                        'deduction_date' => $deduction_date,
                        'campaign_id' => $campaign_id,
                        'ad_cost' => $ad_cost,
                        'credits_waivers_discounts' => $credits_waivers_discounts,
                        'ad_cost_incl' => $ad_cost_incl,
                        'gst' => $gst,
                        'total_ads_cost' => $total_ads_cost,
                    ];

                    if ($db->insert('tbl_ads_cost', $data)) {
                        $inserted_ads++;
                    } else {
                        $errors_ads++;
                    }
                }
                $db->commit();
            }

        } catch (Exception $e) {
            $db->rollBack();
            $msg .= "Error processing file: " . htmlspecialchars($e->getMessage()) . "<br>";
            $r = 'f';
        }
    }

    $msg .= "Processing completed.<br>
             <strong>Payments Inserted:</strong> $inserted_pymts | Skipped: $skipped_pymts | Errors: $errors_pymts<br>
             <strong>Products Inserted:</strong> $inserted_prods | Skipped: $skipped_prods<br>
             <strong>Ads Inserted:</strong> $inserted_ads | Skipped: $skipped_ads | Errors: $errors_ads";

    $r = ($inserted_pymts + $inserted_ads > 0) ? 's' : 'i';
    gotopage("order-manage.php?msg=" . urlencode($msg) . "&r=$r");
}

include 'layouts/layoutTop.php';
?>

<?php if (!empty($msg)): ?>
<div class="alert alert-<?= $r === 's' ? 'success' : ($r === 'f' ? 'danger' : 'info') ?> mb-20">
    <?= nl2br($msg) ?>
</div>
<?php endif; ?>

<div class="card basic-data-table">
    <div class="card-header">
        <h5 class="card-title mb-0">Monthly Order Summary</h5>
    </div>
    <div class="result-<?= $r ?> p-3 mb-3">
        <?= nl2br($msg) ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered mb-0 fs-6 text-sm" id="monthlyTable" data-page-length='10'>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Month / Year</th>
                        <th>Total Orders</th>
                        <th>Action</th>
                        <th>Payment Upload</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "
                        SELECT 
                            YEAR(order_date) AS yr,
                            MONTH(order_date) AS mo,
                            COUNT(*) AS total_orders
                        FROM tbl_orders 
                        WHERE admin_id = :admin_id 
                          AND order_date IS NOT NULL
                        GROUP BY YEAR(order_date), MONTH(order_date)
                        ORDER BY yr DESC, mo DESC
                    ";
                    $months = $db->select($query, ['admin_id' => $admin_id]);

                    $i = 1;
                    foreach ($months as $row) {
                        $monthName = date('F', mktime(0, 0, 0, $row['mo'], 1));
                        $displayMonth = "$monthName {$row['yr']}";
                        $link = "order-list.php?year={$row['yr']}&month={$row['mo']}";
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($displayMonth) ?></td>
                        <td class="text-center fw-bold"><?= number_format($row['total_orders']) ?></td>
                        <td>
                            <a href="<?= $link ?>" class="btn btn-sm btn-primary">View Orders</a>
                            <a href="monthly-stats.php?year=<?= $row['yr'] ?>&month=<?= $row['mo'] ?>" 
                                class="btn btn-sm btn-info">
                                    Monthly Stats
                            </a>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info upload-payment" data-year="<?= $row['yr'] ?>" data-month="<?= $row['mo'] ?>">Upload Payment</button>
                        </td>
                    </tr>
                    <?php } ?>

                    <?php if (empty($months)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No orders found yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Payment Upload Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Upload Payment File for <span id="monthTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" enctype="multipart/form-data" id="paymentForm" class="needs-validation" novalidate>
                    <input type="hidden" name="upload_payment" value="1">
                    <input type="hidden" name="year" id="uploadYear">
                    <input type="hidden" name="month" id="uploadMonth">
                    <div class="mb-3">
                        <label for="payment_file" class="form-label fw-semibold">Payment XLSX File for Current Order Month <span class="text-danger-600">*</span></label>
                        <input type="file" class="form-control" id="payment_file" name="payment_file" accept=".xlsx" required>
                        <div class="invalid-feedback">Please upload a valid XLSX file.</div>
                    </div>
                    <div class="mb-3">
                        <label for="payment_file_next_month" class="form-label fw-semibold">Payment XLSX File for Next Month (for late credits)</label>
                        <input type="file" class="form-control" id="payment_file_next_month" name="payment_file_next_month" accept=".xlsx">
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/layoutBottom.php'; ?>

<script>
$(document).ready(function() {
    $('#monthlyTable').DataTable({
        "ordering": true,
        "searching": true,
        "order": [[1, 'desc']],
        "language": {
            "search": "Filter months:",
            "lengthMenu": "Show _MENU_ months",
            "info": "Showing _START_ to _END_ of _TOTAL_ months"
        }
    });

    $('.upload-payment').click(function() {
        $('#paymentModal').modal('show');
    });

    // Form validation
    const form = document.querySelector("#paymentForm");
    form.addEventListener("submit", function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add("was-validated");
    }, false);
});
</script>