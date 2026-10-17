<?php
include 'inc/chkAuth.php';
include 'inc/dbclass.php';

$title    = 'Monthly Statistics & Orders';
$subTitle = 'Detailed Overview';
$db       = new Database();
$admin_id = (int)$_SESSION['adminId'];

// ─── Validate & get month/year ───────────────────────────────────────────
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : 0;
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;

if ($year < 2000 || $year > 2099 || $month < 1 || $month > 12) {
    die("Invalid month or year.");
}

$monthName = date('F Y', mktime(0, 0, 0, $month, 1, $year));
$monthStart = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
$monthEnd   = date('Y-m-t', strtotime($monthStart));

// ─── Filters ─────────────────────────────────────────────────────────────
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$fromDate     = isset($_GET['from'])   ? trim($_GET['from'])   : $monthStart;
$toDate       = isset($_GET['to'])     ? trim($_GET['to'])     : $monthEnd;

// Sanitize dates
$fromDate = date('Y-m-d', strtotime($fromDate));
$toDate   = date('Y-m-d', strtotime($toDate));

// Build WHERE clause
$where = "o.admin_id = :admin_id AND o.order_date >= :from AND o.order_date <= :to";
$params = [
    'admin_id' => $admin_id,
    'from'     => $fromDate,
    'to'       => $toDate
];

if ($statusFilter !== '') {
    if ($statusFilter === 'Pending') {
        // Pending = no payment record or empty status
        $where .= " AND NOT EXISTS (
            SELECT 1 FROM tbl_order_pymts py 
            WHERE py.sub_order_no = o.sub_order_no 
              AND py.live_order_status IS NOT NULL 
              AND py.live_order_status != ''
        )";
    } else {
        // Specific status
        $where .= " AND EXISTS (
            SELECT 1 FROM tbl_order_pymts py 
            WHERE py.sub_order_no = o.sub_order_no 
              AND py.live_order_status = :status
        )";
        $params['status'] = $statusFilter;
    }
}

// ─── Fetch ALL orders (no pagination) ────────────────────────────────────
$orders = $db->select("
    SELECT 
        o.sub_order_no,
        o.order_date,
        o.sku,
        o.qty,
        o.supp_list_price AS original_sale_price,
        p.prod_gst,
        p.cost_price,
        p.packaging_cost,
        p.misc_cost,
        GROUP_CONCAT(DISTINCT py.live_order_status ORDER BY py.created_at DESC SEPARATOR ' / ') AS all_statuses,
        SUM(py.final_settlement) AS total_final_settlement,
        SUM(py.total_sale_amount) AS summed_total_sale_amount
    FROM tbl_orders o
    LEFT JOIN tbl_products p ON p.admin_id = o.admin_id AND p.sku = o.sku
    LEFT JOIN tbl_order_pymts py ON py.admin_id = o.admin_id AND py.sub_order_no = o.sub_order_no
    WHERE $where
    GROUP BY o.sub_order_no
    ORDER BY o.order_date DESC, o.id DESC
", $params);

// ─── Calculate ALL stats from fetched orders (same logic as table) ───────
$total_orders      = 0;
$total_qty         = 0;
$total_sale        = 0;
$total_settlement  = 0;
$total_packaging   = 0;
$total_net_cost    = 0;
$total_net_pl      = 0;
$total_return_fees = 0;

$status_counts = [
    'delivered'     => 0,
    'return'        => 0,
    'rto'           => 0,
    'cancelled'     => 0,
    'cancelled_not_pickup' => 0,
    'affiliate_fee' => 0,
    'wrongclaim'    => 0,
    'pending'       => 0,
];

foreach ($orders as $order) {
    $total_orders++;
    $qty = (int)($order['qty'] ?? 1);
    $total_qty += $qty;

    $sale_amount      = (float)($order['summed_total_sale_amount'] ?: $order['original_sale_price'] ?: 0);
    $final_settlement = (float)($order['total_final_settlement'] ?? 0);
    $statuses         = $order['all_statuses'] ?: 'Pending';

    $total_sale       += $sale_amount;
    $total_settlement += $final_settlement;

    $packaging_unit    = (float)($order['packaging_cost'] ?? 0);
    $packaging_total   = $packaging_unit * $qty;
    $total_packaging   += $packaging_total;

    // Net cost with GST diff
    $gst_perc        = (float)($order['prod_gst'] ?? 0);
    $cost_price_unit = (float)($order['cost_price'] ?? 0);
    $gst_on_sale     = $sale_amount * ($gst_perc / 100);
    $gst_on_cost     = $cost_price_unit * ($gst_perc / 100);
    $gst_diff        = $gst_on_sale - $gst_on_cost;
    $net_cost_unit   = $cost_price_unit + $gst_diff;
    $net_cost_total  = $net_cost_unit * $qty;
    $total_net_cost  += $net_cost_total;

    // Normalize status: lowercase + trim + single spaces
    $status_lower = preg_replace('/\s+/', ' ', trim(strtolower($statuses)));

    // Net P/L calculation (exactly same as table)
    if ($status_lower === 'delivered' || $status_lower === 'affiliate fee' ||
        $status_lower === 'delivered / affiliate fee' || $status_lower === 'affiliate fee / delivered' ||
        $status_lower === 'shipped' || $status_lower === 'shipped / return' || $status_lower === 'return / shipped' ||
        $status_lower === 'exchange' || $status_lower === 'exchange / affiliate fee' ||
        $status_lower === 'cancelled / rto') {
        $total_cost = $net_cost_total + $packaging_total + ((float)($order['misc_cost'] ?? 0) * $qty);
        $net_pl = $final_settlement - $total_cost;
    } elseif ($status_lower === 'rto') {
        $net_pl = $final_settlement - $packaging_total;
    } elseif ($status_lower === 'return') {
        $net_pl = $final_settlement - $packaging_total;
        if ($final_settlement < 0) {
            $total_return_fees += abs($final_settlement);
        }
    } elseif ($status_lower === 'wrongclaim / return' || $status_lower === 'return / wrongclaim') {
        if ($final_settlement < 0) {
            $net_pl = $final_settlement - $packaging_total - $net_cost_total;
            $total_return_fees += abs($final_settlement);
        } else {
            $net_pl = $final_settlement + $packaging_total + $net_cost_total;
        }
    } elseif ($status_lower === 'wrongclaim / rto' || $status_lower === 'rto / wrongclaim') {
        $net_pl = $final_settlement - $packaging_total;
    } elseif ($status_lower === 'wrongclaim') {
        $net_pl = $final_settlement + $packaging_total - $net_cost_total;
        if ($final_settlement < 0) {
            $total_return_fees += abs($final_settlement);
        }
    } else {
        $net_pl = $final_settlement - $packaging_total;
    }

    // Special case: no payment record → lose packaging only
    if ($final_settlement == 0 && empty($order['all_statuses']) || $status_lower === 'pending') {
        $net_pl = - $packaging_total;
    }

    $total_net_pl += $net_pl;

    // Status counts (using exact ===)
    if ($status_lower === 'delivered' || $status_lower === 'delivered / affiliate fee' || $status_lower === 'affiliate fee / delivered' ||
        $status_lower === 'shipped' || $status_lower === 'exchange' || $status_lower === 'shipped / return' ||
        $status_lower === 'return / shipped' || $status_lower === 'cancelled / rto' || $status_lower === 'exchange / affiliate fee') {
        $status_counts['delivered']++;
    }
    if ($status_lower === 'return' || $status_lower === 'shipped / return' || $status_lower === 'return / shipped' ||
        $status_lower === 'wrongclaim / return' || $status_lower === 'return / wrongclaim') {
        $status_counts['return']++;
    }
    if ($status_lower === 'rto' || $status_lower === 'wrongclaim / rto' || $status_lower === 'rto / wrongclaim') {
        $status_counts['rto']++;
    }
    if ($status_lower === 'cancelled') {
        $status_counts['cancelled']++;
    }
    if ($status_lower === 'affiliate fee' || $status_lower === 'delivered / affiliate fee' || $status_lower === 'affiliate fee / delivered' ||
        $status_lower === 'exchange / affiliate fee') {
        $status_counts['affiliate_fee']++;
    }
    if ($status_lower === 'wrongclaim' || $status_lower === 'wrongclaim / return' || $status_lower === 'return / wrongclaim' ||
        $status_lower === 'wrongclaim / rto' || $status_lower === 'rto / wrongclaim') {
        $status_counts['wrongclaim']++;
    }
    if ($status_lower === 'pending') {
        $status_counts['pending']++;
    }
}

$avg_order_value = $total_orders > 0 ? $total_sale / $total_orders : 0;

// ─── Fetch Total Ads Cost ─────────────────────────────────────────────────
$adsTotal = $db->select_single("
    SELECT COALESCE(SUM(total_ads_cost), 0) AS total_ads
    FROM tbl_ads_cost
    WHERE admin_id = ?
      AND deduction_date >= ?
      AND deduction_date <= ?
", [$admin_id, $monthStart, $monthEnd]);

$total_ads = $adsTotal['total_ads'] ?? 0;
$final_total_net_pl = $total_net_pl - $total_ads;

include 'layouts/layoutTop.php';
?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h5 class="card-title mb-0">Monthly Statistics – <?= htmlspecialchars($monthName) ?></h5>
        <a href="order-history.php" class="btn btn-sm btn-secondary">Back to Monthly View</a>
    </div>

    <div class="card-body">

        <!-- Filter Form -->
        <form method="GET" class="mb-4">
            <input type="hidden" name="year" value="<?= $year ?>">
            <input type="hidden" name="month" value="<?= $month ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Order Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Delivered"    <?= $statusFilter === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="Return"       <?= $statusFilter === 'Return' ? 'selected' : '' ?>>Return</option>
                        <option value="RTO"          <?= $statusFilter === 'RTO' ? 'selected' : '' ?>>RTO</option>
                        <option value="Cancelled"    <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="Affiliate Fee"<?= $statusFilter === 'Affiliate Fee' ? 'selected' : '' ?>>Affiliate Fee</option>
                        <option value="WrongClaim"   <?= $statusFilter === 'WrongClaim' ? 'selected' : '' ?>>WrongClaim</option>
                        <option value="Pending"      <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($fromDate) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($toDate) ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                </div>
            </div>
        </form>

        <!-- Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient-start-1">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-1">Total Orders</h6>
                        <h3 class="fw-bold"><?= number_format($total_orders) ?></h3>
                        <small>Qty: <?= number_format($total_qty) ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient-start-2">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-1">Total Sale</h6>
                        <h3 class="fw-bold">₹ <?= number_format($total_sale, 2) ?></h3>
                        <small>AOV: ₹ <?= number_format($avg_order_value, 2) ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient-start-3">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-1">Net Settlement</h6>
                        <h3 class="fw-bold <?= $total_settlement >= 0 ? 'text-success' : 'text-danger' ?>">
                            ₹ <?= number_format($total_settlement, 2) ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient-start-4">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-1">Overall Net P/L (after Ads)</h6>
                        <h3 class="fw-bold <?= $final_total_net_pl >= 0 ? 'text-success' : 'text-danger' ?>">
                            ₹ <?= number_format($final_total_net_pl, 2) ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status & Cost Breakdown -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="mb-3">Order Status Breakdown</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Delivered</span><strong><?= number_format($status_counts['delivered']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Return</span><strong><?= number_format($status_counts['return']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Cancelled Before Pickup</span><strong><?= number_format($status_counts['cancelled_not_pickup']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>RTO</span><strong><?= number_format($status_counts['rto']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Lost</span><strong><?= number_format($status_counts['cancelled']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Affiliate Fee</span><strong><?= number_format($status_counts['affiliate_fee']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Wrong Claim</span><strong><?= number_format($status_counts['wrongclaim']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Pending / No Payment</span><strong><?= number_format($status_counts['pending']) ?></strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="mb-3">Financial Breakdown</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Total Sale Amount</span><strong>₹ <?= number_format($total_sale, 2) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Total Settlement</span><strong>₹ <?= number_format($total_settlement, 2) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Total Packaging Cost</span><strong>₹ <?= number_format($total_packaging, 2) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Approx. Return Fees</span><strong>₹ <?= number_format($total_return_fees, 2) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Total Ads Cost</span><strong>₹ <?= number_format($total_ads, 2) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between fw-bold">
                                <span>Overall Net P/L (after Ads)</span>
                                <span class="<?= $final_total_net_pl >= 0 ? 'text-success' : 'text-danger' ?>">
                                    ₹ <?= number_format($final_total_net_pl, 2) ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ORDER LIST TABLE -->
<div class="card basic-data-table">
    <div class="card-header">
        <h5 class="card-title mb-0">Order List – <?= htmlspecialchars($monthName) ?></h5>
    </div>

    <div class="card-body">
        <?php if ($total_orders > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered mb-0 fs-13" id="orderTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sub Order No</th>
                        <th>Order Date</th>
                        <th>SKU</th>
                        <th>Qty</th>
                        <!-- <th>Sale Amount</th> -->
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
                    $i = 1;
                    foreach ($orders as $order):
                        $qty                = (int)($order['qty'] ?? 1);
                        $gst_perc           = (float)($order['prod_gst'] ?? 0);
                        $cost_price_unit    = (float)($order['cost_price'] ?? 0);
                        $packaging_unit     = (float)($order['packaging_cost'] ?? 0);
                        $misc_unit          = (float)($order['misc_cost'] ?? 0);

                        $sale_amount        = (float)($order['summed_total_sale_amount'] ?: $order['original_sale_price'] ?: 0);
                        $final_settlement   = (float)($order['total_final_settlement'] ?? 0);
                        $statuses           = $order['all_statuses'] ?: 'Pending';
                        $status_lower       = preg_replace('/\s+/', ' ', trim(strtolower($statuses)));

                        $packaging_total    = $packaging_unit * $qty;

                        $gst_on_sale        = $sale_amount * ($gst_perc / 100);
                        $gst_on_cost        = $cost_price_unit * ($gst_perc / 100);
                        $gst_difference     = $gst_on_sale - $gst_on_cost;
                        $net_cost_per_unit  = $cost_price_unit + $gst_on_cost + $gst_difference;
                        $net_cost_total     = $net_cost_per_unit * $qty;

                        // Display net cost based on status (same logic as P/L)
                        $display_net_cost = 0;
                        if ($status_lower === 'delivered' || $status_lower === 'affiliate fee' ||
                            $status_lower === 'delivered / affiliate fee' || $status_lower === 'affiliate fee / delivered' ||
                            $status_lower === 'shipped' || $status_lower === 'shipped / return' || $status_lower === 'return / shipped' ||
                            $status_lower === 'exchange' || $status_lower === 'exchange / affiliate fee' ||
                            $status_lower === 'cancelled / rto') {
                            $display_net_cost = $net_cost_total;
                        } elseif ($status_lower === 'wrongclaim' || $status_lower === 'wrongclaim / return' || $status_lower === 'return / wrongclaim' ||
                                  $status_lower === 'wrongclaim / rto' || $status_lower === 'rto / wrongclaim') {
                            $display_net_cost = $cost_price_unit;
                        } // others = 0

                        // Net P/L - exact same logic as above
                        if ($status_lower === 'delivered' || $status_lower === 'affiliate fee' ||
                            $status_lower === 'delivered / affiliate fee' || $status_lower === 'affiliate fee / delivered' ||
                            $status_lower === 'shipped' || $status_lower === 'shipped / return' || $status_lower === 'return / shipped' ||
                            $status_lower === 'exchange' || $status_lower === 'exchange / affiliate fee' ||
                            $status_lower === 'cancelled / rto') {
                            $total_cost = $net_cost_total + $packaging_total + ($misc_unit * $qty);
                            $net_pl     = $final_settlement - $total_cost;
                        } elseif ($status_lower === 'rto') {
                            $net_pl = $final_settlement - $packaging_total;
                        } elseif ($status_lower === 'return') {
                            $net_pl = $final_settlement - $packaging_total;
                        } elseif ($status_lower === 'wrongclaim / return' || $status_lower === 'return / wrongclaim') {
                            if ($final_settlement < 0) {
                                $net_pl = $final_settlement - $packaging_total - $net_cost_total;
                            } else {
                                $net_pl = $final_settlement + $packaging_total + $net_cost_total;
                            }
                        } elseif ($status_lower === 'wrongclaim / rto' || $status_lower === 'rto / wrongclaim') {
                            $net_pl = $final_settlement - $packaging_total;
                        } elseif ($status_lower === 'wrongclaim') {
                            $net_pl = $final_settlement + $packaging_total - $net_cost_total;
                        } else {
                            $net_pl = $final_settlement - $packaging_total;
                        }

                        if ($final_settlement == 0 && $status_lower === 'pending') {
                            $net_pl = 0;
                        }
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($order['sub_order_no']) ?></td>
                        <td><?= $order['order_date'] ? date('d-M-Y', strtotime($order['order_date'])) : '-' ?></td>
                        <td><?= htmlspecialchars($order['sku'] ?: '-') ?></td>
                        <td class="text-end"><?= number_format($qty) ?></td>
                        <!-- <td class="text-end"><?= number_format($sale_amount, 2) ?></td> -->
                        <td class="text-end"><?= number_format($gst_perc, 2) ?>%</td>
                        <td class="text-end <?= $final_settlement >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= number_format($final_settlement, 2) ?>
                        </td>
                        <td class="text-end"><?= number_format($packaging_total, 2) ?></td>
                        <td class="text-end">
                            <?= number_format($display_net_cost, 2) ?>
                                <small class="text-muted d-block">CP: <?= number_format($cost_price_unit, 2) ?></small>
                                <small class="text-muted">GstonSale: <?= number_format($gst_on_sale, 2) ?> | GstOnCost: <?= number_format($gst_on_cost, 2) ?></small>
                                <small class="text-muted d-block">GST Diff: <?= number_format($gst_difference, 2) ?></small>
                                <small class="text-muted d-block">Total CostPrice: <?= number_format($net_cost_total, 2) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-<?= $status_lower === 'delivered' || $status_lower === 'affiliate fee' ? 'success' : ($status_lower === 'return' || $status_lower === 'rto' ? 'warning' : 'secondary') ?>">
                                <?= htmlspecialchars($statuses) ?>
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
        <?php else: ?>
        <div class="alert alert-info text-center my-4">
            No orders found for <?= htmlspecialchars($monthName) ?> with current filters.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'layouts/layoutBottom.php'; ?>

<script>
$(document).ready(function() {
    $('#orderTable').DataTable({
        "paging": true,
        "lengthMenu": [[100, 200, 500, 1000, -1], [100, 200, 500, 1000, "All"]],
        "pageLength": 100,
        "dom": '<"dt-buttons-center"lBf>tip',
        "buttons": [
            {
                extend: 'csv',
                text: '<iconify-icon icon="mdi:file-csv"></iconify-icon>',
                className: 'btn btn-sm btn-success fs-5 text-success rounded-pill',
            }
        ],
        "searching": true,
        "ordering": true,
        "info": false,
        "responsive": true,
    });
});
</script>