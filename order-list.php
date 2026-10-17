<?php
include 'inc/chkAuth.php';
include 'inc/dbclass.php';

$title    = 'Order List';
$subTitle = 'Orders';
$db       = new Database();
$admin_id = (int)$_SESSION['adminId'];

// ─── Pagination ───────────────────────────────────────────────
$perPage    = 100;
$page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset     = ($page - 1) * $perPage;

// ─── Optional month/year filter ───────────────────────────────
$where      = "o.admin_id = :admin_id";
$params     = ['admin_id' => $admin_id];
$titleExtra = '';

if (!empty($_GET['year']) && !empty($_GET['month'])) {
    $year  = (int)$_GET['year'];
    $month = (int)$_GET['month'];
    if ($year >= 2000 && $year <= 2099 && $month >= 1 && $month <= 12) {
        $where .= " AND YEAR(o.order_date) = :yr AND MONTH(o.order_date) = :mo";
        $params['yr'] = $year;
        $params['mo'] = $month;
        $monthName   = date('F Y', mktime(0,0,0,$month,1,$year));
        $titleExtra  = " - $monthName";
    }
}

// ─── Count total for pagination ───────────────────────────────
$countQuery = "SELECT COUNT(DISTINCT o.sub_order_no) AS total 
               FROM tbl_orders o 
               WHERE $where";
$totalRow   = $db->select_single($countQuery, $params);
$total      = $totalRow['total'] ?? 0;
$totalPages = max(1, ceil($total / $perPage));

// ─── Main query with LEFT JOINs and aggregation ───────────────
$orders = $db->select("
    SELECT 
        o.sub_order_no,
        o.order_date,
        o.sku,
        o.qty,
        o.supp_list_price AS original_sale_price,  -- from tbl_orders (reference)
        p.prod_gst,
        p.cost_price,
        p.packaging_cost,
        p.misc_cost,
        
        -- Aggregated from tbl_order_pymts
        GROUP_CONCAT(DISTINCT py.live_order_status ORDER BY py.created_at DESC SEPARATOR ' / ') AS all_statuses,
        SUM(py.final_settlement) AS total_final_settlement,
        SUM(py.total_sale_amount) AS summed_total_sale_amount
        
    FROM tbl_orders o
    
    LEFT JOIN tbl_products p 
           ON p.admin_id = o.admin_id 
          AND p.sku = o.sku
          
    LEFT JOIN tbl_order_pymts py 
           ON py.admin_id = o.admin_id 
          AND py.sub_order_no = o.sub_order_no
          
    WHERE $where
    
    GROUP BY o.sub_order_no
    ORDER BY o.order_date DESC, o.id DESC
    LIMIT :offset, :perpage
", array_merge($params, [
    'offset'  => $offset,
    'perpage' => $perPage
]));

include 'layouts/layoutTop.php';
?>

<div class="card basic-data-table">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Order List<?= htmlspecialchars($titleExtra) ?></h5>
        <a href="order-manage.php" class="btn btn-sm btn-secondary">Back to Monthly View</a>
    </div>

    <div class="card-body">
        <?php if ($total > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered mb-0 fs-13" id="orderTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sub Order No</th>
                        <th>Order Date</th>
                        <th>SKU</th>
                        <th>Qty</th>
                        <th>Sale Amount</th>
                        <th>GST %</th>
                        <th>Final Settlement</th>
                        <th>Packaging Cost</th>
                        <th>Cost Price (Net)</th>
                        <th>Status</th>
                        <th>Net P/L</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = $offset + 1;
                    foreach ($orders as $order):
                        $qty                = (int)($order['qty'] ?? 1);
                        $gst_perc           = (float)($order['prod_gst'] ?? 0);
                        $cost_price_unit    = (float)($order['cost_price'] ?? 0);
                        $packaging_unit     = (float)($order['packaging_cost'] ?? 0);
                        $misc_unit          = (float)($order['misc_cost'] ?? 0);

                        $sale_amount        = (float)($order['summed_total_sale_amount'] ?: $order['original_sale_price'] ?: 0);
                        $final_settlement   = (float)($order['total_final_settlement'] ?? 0);

                        // Packaging total
                        $packaging_total    = $packaging_unit * $qty;

                        // Net cost price calculation (GST difference logic)
                        $gst_on_sale        = $sale_amount * ($gst_perc / 100);
                        $gst_on_cost        = $cost_price_unit * ($gst_perc / 100);
                        $gst_difference     = $gst_on_sale - $gst_on_cost;
                        $net_cost_per_unit  = $cost_price_unit + $gst_difference;
                        $net_cost_total     = $net_cost_per_unit * $qty;

                        // Total cost (including packaging & misc)
                        $total_cost         = $net_cost_total + $packaging_total + ($misc_unit * $qty);

                        // Net Profit / Loss
                        $net_pl             = $final_settlement - $total_cost;

                        $status_display     = $order['all_statuses'] ?: 'Pending';
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($order['sub_order_no']) ?></td>
                        <td><?= $order['order_date'] ? date('d-M-Y', strtotime($order['order_date'])) : '-' ?></td>
                        <td><?= htmlspecialchars($order['sku'] ?: '-') ?></td>
                        <td class="text-end"><?= number_format($qty) ?></td>
                        <td class="text-end"><?= number_format($sale_amount, 2) ?></td>
                        <td class="text-end"><?= number_format($gst_perc, 2) ?>%</td>
                        <td class="text-end <?= $final_settlement >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= number_format($final_settlement, 2) ?>
                        </td>
                        <td class="text-end"><?= number_format($packaging_total, 2) ?></td>
                        <td class="text-end"><?= number_format($net_cost_total, 2) ?></td>
                        <td>
                            <span class="badge bg-<?= strpos($status_display, 'Delivered') !== false ? 'success' : (strpos($status_display, 'Return') !== false || strpos($status_display, 'RTO') !== false ? 'warning' : 'secondary') ?>">
                                <?= htmlspecialchars($status_display) ?>
                            </span>
                        </td>
                        <td class="text-end fw-bold <?= $net_pl >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= number_format($net_pl, 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Order pagination" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page-1 ?>&year=<?= $_GET['year'] ?? '' ?>&month=<?= $_GET['month'] ?? '' ?>">Previous</a>
                </li>
                <?php
                $range = 4;
                $start = max(1, $page - $range);
                $end   = min($totalPages, $page + $range);
                for ($p = $start; $p <= $end; $p++):
                ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>&year=<?= $_GET['year'] ?? '' ?>&month=<?= $_GET['month'] ?? '' ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page+1 ?>&year=<?= $_GET['year'] ?? '' ?>&month=<?= $_GET['month'] ?? '' ?>">Next</a>
                </li>
            </ul>
            <p class="text-center text-muted mt-2">
                Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= number_format($total) ?> unique orders
            </p>
        </nav>
        <?php endif; ?>

        <?php else: ?>
        <div class="alert alert-info text-center my-4">
            No orders found<?= $titleExtra ? " for $titleExtra" : "" ?>.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'layouts/layoutBottom.php'; ?>

<script>
$(document).ready(function() {
    $('#orderTable').DataTable({
        "paging": false,        // manual pagination
        "searching": true,
        "ordering": true,
        "info": false,
        "responsive": true,
        "order": [[2, 'desc']]  // default sort by order date desc
    });
});
</script>