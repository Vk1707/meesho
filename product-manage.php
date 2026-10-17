<?php
include 'inc/chkAuth.php';
include 'inc/dbclass.php';
require 'vendor/autoload.php'; // Make sure composer autoload is present
use PhpOffice\PhpSpreadsheet\IOFactory;

$title    = 'Manage Products';
$subTitle = 'Product List & Cost Management';
$db       = new Database();
$admin_id = (int)$_SESSION['adminId'];
$msg      = '';
$r        = '';

// ────────────────────────────────────────────────
// SINGLE PRODUCT SAVE / UPDATE
// ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $sku             = trim($_POST['sku'] ?? '');
    $prod_name       = trim($_POST['prod_name'] ?? '');
    $prod_gst        = (float)($_POST['prod_gst'] ?? 0);
    $prod_list_price = (float)($_POST['prod_list_price'] ?? 0);
    $cost_price      = (float)($_POST['cost_price'] ?? 0);
    $packaging_cost  = (float)($_POST['packaging_cost'] ?? 0);
    $misc_cost       = (float)($_POST['misc_cost'] ?? 0);

    if (empty($sku)) {
        $msg = "SKU is required.";
        $r   = 'f';
    } else {
        $exists = $db->select_single(
            "SELECT id FROM tbl_products WHERE admin_id = ? AND sku = ?",
            [$admin_id, $sku]
        );

        $data = [
            'prod_name'       => $prod_name,
            'prod_gst'        => $prod_gst,
            'prod_list_price' => $prod_list_price,
            'cost_price'      => $cost_price,
            'packaging_cost'  => $packaging_cost,
            'misc_cost'       => $misc_cost,
            'updated_at'      => date('Y-m-d H:i:s')
        ];

        if ($exists) {
            // Update
            $db->update('tbl_products', $data, "id = :id", ['id' => $exists['id']]);
            $msg = "Product <strong>" . htmlspecialchars($sku) . "</strong> updated successfully.";
            $r   = 's';
        } else {
            // Insert new
            $data['admin_id']   = $admin_id;
            $data['sku']        = $sku;
            $data['created_at'] = date('Y-m-d H:i:s');
            if ($db->insert('tbl_products', $data)) {
                $msg = "New product <strong>" . htmlspecialchars($sku) . "</strong> added successfully.";
                $r   = 's';
            } else {
                $msg = "Failed to add product.";
                $r   = 'f';
            }
        }
    }

    gotopage("product-manage.php?msg=" . urlencode($msg) . "&r=$r");
}

// ────────────────────────────────────────────────
// BULK UPDATE COST & PACKAGING FROM FILE
// ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_update_costs'])) {
    if (isset($_FILES['cost_file']) && $_FILES['cost_file']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['cost_file']['tmp_name'];
        $fileName = $_FILES['cost_file']['name'];
        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            $msg = "Only .xlsx, .xls or .csv files allowed.";
            $r   = 'f';
        } else {
            $updated   = 0;
            $skipped   = 0;
            $not_found = 0;

            try {
                if ($ext === 'csv') {
                    // CSV handling
                    if (($handle = fopen($file, 'r')) !== false) {
                        fgetcsv($handle); // skip header
                        while (($row = fgetcsv($handle)) !== false) {
                            if (count($row) < 3) continue;
                            $sku        = trim($row[0]);
                            $price      = (float) trim($row[1]);
                            $pack_price = (float) trim($row[2]);
                            process_sku_update($sku, $price, $pack_price);
                        }
                        fclose($handle);
                    }
                } else {
                    // Excel
                    $spreadsheet = IOFactory::load($file);
                    $worksheet   = $spreadsheet->getActiveSheet();
                    $rows        = $worksheet->toArray(null, true, true, true);

                    // Skip header (assuming row 1 = header)
                    foreach ($rows as $rowNum => $row) {
                        if ($rowNum === 1) continue;
                        if (empty($row['A'])) continue;

                        $sku        = trim($row['A']);
                        $price      = (float) ($row['B'] ?? 0);
                        $pack_price = (float) ($row['C'] ?? 0);

                        process_sku_update($sku, $price, $pack_price);
                    }
                }

                $msg = "<strong>Bulk update completed:</strong><br>
                        Updated: <strong>$updated</strong><br>
                        Skipped (no change / invalid): <strong>$skipped</strong><br>
                        Not found in database: <strong>$not_found</strong>";
                $r = ($updated > 0) ? 's' : 'i';

            } catch (Exception $e) {
                $msg = "Error reading file: " . htmlspecialchars($e->getMessage());
                $r   = 'f';
            }
        }
    } else {
        $msg = "No file uploaded or upload error.";
        $r   = 'f';
    }

    gotopage("manage-products.php?msg=" . urlencode($msg) . "&r=$r");
}

/**
 * Helper: Update cost_price and/or packaging_cost for one SKU
 */
function process_sku_update($sku, $price, $pack_price) {
    global $db, $admin_id, $updated, $skipped, $not_found;

    if (empty($sku) || ($price <= 0 && $pack_price <= 0)) {
        $skipped++;
        return;
    }

    $exists = $db->select_single(
        "SELECT id FROM tbl_products WHERE admin_id = ? AND sku = ?",
        [$admin_id, $sku]
    );

    if (!$exists) {
        $not_found++;
        return;
    }

    $data = [];
    if ($price > 0) {
        $data['cost_price'] = $price;
    }
    if ($pack_price > 0) {
        $data['packaging_cost'] = $pack_price;
    }
    $data['updated_at'] = date('Y-m-d H:i:s');

    if (!empty($data)) {
        $db->update('tbl_products', $data, "id = :id", ['id' => $exists['id']]);
        $updated++;
    } else {
        $skipped++;
    }
}

include 'layouts/layoutTop.php';
?>

<div class="card basic-data-table">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h5 class="card-title mb-0">Manage Products</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#productModal" onclick="clearModal()">
                <i class="fas fa-plus"></i> Add New Product
            </button>
            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#bulkCostModal">
                <i class="fas fa-file-upload"></i> Bulk Update Costs
            </button>
        </div>
    </div>

    <div class="card-body">
        <?php if (!empty($_GET['msg'])): ?>
        <div class="alert alert-<?= $_GET['r'] === 's' ? 'success' : ($_GET['r'] === 'f' ? 'danger' : 'info') ?> mb-20">
            <?= nl2br(htmlspecialchars(urldecode($_GET['msg']))) ?>
        </div>
        <?php endif; ?>

        <!-- Bulk Cost Upload Modal -->
        <div class="modal fade" id="bulkCostModal" tabindex="-1" aria-labelledby="bulkCostModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkCostModalLabel">Bulk Update Cost & Packaging Price</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3 text-muted">
                            File format (.xlsx / .csv):<br>
                            Column A: <strong>Supplier SKU</strong><br>
                            Column B: <strong>Price</strong> (new cost price)<br>
                            Column C: <strong>New Packaging Price</strong>
                        </p>
                        <form action="" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <input type="hidden" name="bulk_update_costs" value="1">
                            <div class="mb-3">
                                <label for="cost_file" class="form-label fw-semibold">
                                    Upload File <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" id="cost_file" name="cost_file" accept=".xlsx,.xls,.csv" required>
                                <div class="invalid-feedback">Please choose a valid file.</div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary px-4">Upload & Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product List Table -->
        <div class="table-responsive">
            <table class="table table-bordered mb-0 fs-13" id="productTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>SKU</th>
                        <th>Product Name</th>
                        <th>GST %</th>
                        <th>Listing Price</th>
                        <th>Cost Price</th>
                        <th>Packaging Cost</th>
                        <th>Misc Cost</th>
                        <th>Total Cost</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $products = $db->select("
                        SELECT 
                            id, sku, prod_name, prod_gst, prod_list_price,
                            cost_price, packaging_cost, misc_cost,
                            (COALESCE(cost_price,0) + COALESCE(packaging_cost,0) + COALESCE(misc_cost,0)) AS total_cost
                        FROM tbl_products 
                        WHERE admin_id = :admin_id 
                        ORDER BY sku ASC
                    ", ['admin_id' => $admin_id]);

                    $i = 1;
                    foreach ($products as $prod):
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($prod['sku'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($prod['prod_name'] ?? '-') ?></td>
                        <td class="text-end"><?= number_format($prod['prod_gst'] ?? 0, 2) ?>%</td>
                        <td class="text-end"><?= number_format($prod['prod_list_price'] ?? 0, 2) ?></td>
                        <td class="text-end"><?= number_format($prod['cost_price'] ?? 0, 2) ?></td>
                        <td class="text-end"><?= number_format($prod['packaging_cost'] ?? 0, 2) ?></td>
                        <td class="text-end"><?= number_format($prod['misc_cost'] ?? 0, 2) ?></td>
                        <td class="text-end fw-bold"><?= number_format($prod['total_cost'] ?? 0, 2) ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary edit-product"
                                    data-id="<?= $prod['id'] ?>"
                                    data-sku="<?= htmlspecialchars($prod['sku'] ?? '') ?>"
                                    data-name="<?= htmlspecialchars($prod['prod_name'] ?? '') ?>"
                                    data-gst="<?= $prod['prod_gst'] ?? 0 ?>"
                                    data-list="<?= $prod['prod_list_price'] ?? 0 ?>"
                                    data-cost="<?= $prod['cost_price'] ?? 0 ?>"
                                    data-pack="<?= $prod['packaging_cost'] ?? 0 ?>"
                                    data-misc="<?= $prod['misc_cost'] ?? 0 ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            No products found yet.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- SINGLE PRODUCT EDIT / ADD MODAL -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" class="needs-validation" novalidate id="productForm">
                <div class="modal-body">
                    <input type="hidden" name="save_product" value="1">
                    <input type="hidden" name="id" id="prod_id">

                    <div class="mb-3">
                        <label for="sku" class="form-label fw-semibold">SKU <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sku" name="sku" required readonly>
                        <div class="invalid-feedback">SKU is required.</div>
                    </div>

                    <div class="mb-3">
                        <label for="prod_name" class="form-label fw-semibold">Product Name</label>
                        <input type="text" class="form-control" id="prod_name" name="prod_name">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="prod_gst" class="form-label fw-semibold">GST %</label>
                            <input type="number" step="0.01" class="form-control" id="prod_gst" name="prod_gst" min="0" max="100">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="prod_list_price" class="form-label fw-semibold">Listing Price (Incl. Tax)</label>
                            <input type="number" step="0.01" class="form-control" id="prod_list_price" name="prod_list_price" min="0">
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="mb-3">Cost Details</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="cost_price" class="form-label fw-semibold">Cost Price</label>
                            <input type="number" step="0.01" class="form-control" id="cost_price" name="cost_price" min="0" required>
                            <div class="invalid-feedback">Enter cost price.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="packaging_cost" class="form-label fw-semibold">Packaging Cost</label>
                            <input type="number" step="0.01" class="form-control" id="packaging_cost" name="packaging_cost" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="misc_cost" class="form-label fw-semibold">Misc Cost</label>
                            <input type="number" step="0.01" class="form-control" id="misc_cost" name="misc_cost" min="0">
                        </div>
                    </div>

                    <div class="mt-3">
                        <strong>Total Cost:</strong> <span id="totalCostDisplay">0.00</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'layouts/layoutBottom.php'; ?>

<script>
$(document).ready(function() {
    // DataTable
    $('#productTable').DataTable({
        "pageLength": 25,
        "responsive": true,
        "ordering": true,
        "searching": true,
        "order": [[1, 'asc']],
        "language": {
            "search": "Filter products:",
            "lengthMenu": "Show _MENU_ products"
        }
    });

    // Edit button
    $('.edit-product').click(function() {
        $('#productModalLabel').text('Edit Product');
        $('#prod_id').val($(this).data('id'));
        $('#sku').val($(this).data('sku'));
        $('#prod_name').val($(this).data('name'));
        $('#prod_gst').val($(this).data('gst'));
        $('#prod_list_price').val($(this).data('list'));
        $('#cost_price').val($(this).data('cost'));
        $('#packaging_cost').val($(this).data('pack'));
        $('#misc_cost').val($(this).data('misc'));
        calculateTotalCost();
        $('#productModal').modal('show');
    });

    // Clear modal for ADD NEW
    window.clearModal = function() {
        $('#productModalLabel').text('Add New Product');
        $('#productForm')[0].reset();
        $('#prod_id').val('');
        $('#sku').prop('readonly', false);
        $('#totalCostDisplay').text('0.00');
    };

    // Live total cost calculation
    function calculateTotalCost() {
        let cost = parseFloat($('#cost_price').val()) || 0;
        let pack = parseFloat($('#packaging_cost').val()) || 0;
        let misc = parseFloat($('#misc_cost').val()) || 0;
        $('#totalCostDisplay').text((cost + pack + misc).toFixed(2));
    }

    $('#cost_price, #packaging_cost, #misc_cost').on('input', calculateTotalCost);

    // Bootstrap form validation
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
</script>