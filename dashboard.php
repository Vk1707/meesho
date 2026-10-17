<?php
include 'inc/chkAuth.php';
$title = 'Dashboard';
$subTitle = 'Overview';
include 'inc/dbclass.php';
$db = new Database();
$admin_id = $_SESSION['adminId'];

// Calculate last 30 days
$last_30_days = date('Y-m-d', strtotime('-30 days'));

// Stats Queries
$total_employees = $db->select_single(
    "SELECT COUNT(*) as total FROM tbl_users WHERE user_type = 3 AND create_by = :admin_id",
    ['admin_id' => $admin_id]
)['total'] ?? 0;

$total_active_events = $db->select_single(
    "SELECT COUNT(*) as total FROM tbl_events WHERE is_active = 1"
)['total'] ?? 0;

$pending_messages = $db->select_single(
    "SELECT COUNT(*) as total FROM tbl_msg_queue WHERE status = 'Pending'"
)['total'] ?? 0;

$sent_messages = $db->select_single(
    "SELECT COUNT(*) as total FROM tbl_wp_msg_logs WHERE message_status = 'sent'"
)['total'] ?? 0;

$failed_messages = $db->select_single(
    "SELECT COUNT(*) as total FROM tbl_wp_msg_logs WHERE message_status = 'failed'"
)['total'] ?? 0;

$new_employees_30 = $db->select_single(
    "SELECT COUNT(*) as new FROM tbl_users WHERE user_type = 3 AND created_at >= :last_30_days",
    ['last_30_days' => $last_30_days]
)['new'] ?? 0;

$new_messages_30 = $db->select_single(
    "SELECT COUNT(*) as new FROM tbl_wp_msg_logs WHERE created_at >= :last_30_days",
    ['last_30_days' => $last_30_days]
)['new'] ?? 0;

$sent_30 = $db->select_single(
    "SELECT COUNT(*) as sent FROM tbl_wp_msg_logs WHERE message_status = 'sent' AND created_at >= :last_30_days",
    ['last_30_days' => $last_30_days]
)['sent'] ?? 0;

$failed_30 = $db->select_single(
    "SELECT COUNT(*) as failed FROM tbl_wp_msg_logs WHERE message_status = 'failed' AND created_at >= :last_30_days",
    ['last_30_days' => $last_30_days]
)['failed'] ?? 0;

// Latest Registered Employees
$latest_employees = $db->select(
    "SELECT * FROM tbl_users WHERE user_type = 3 AND create_by = :admin_id ORDER BY created_at DESC LIMIT 5",
    ['admin_id' => $admin_id]
);

// Top Performers (Admins by sent messages)
$top_performers = $db->select(
    "SELECT admin_id, COUNT(*) as sent_count 
     FROM tbl_wp_msg_logs 
     WHERE message_status = 'sent' 
     GROUP BY admin_id 
     ORDER BY sent_count DESC LIMIT 5"
);

// Top States (from tbl_users.user_state)
$top_states = $db->select(
    "SELECT user_state, COUNT(*) as count 
     FROM tbl_users 
     WHERE user_type = 3 
     GROUP BY user_state 
     ORDER BY count DESC LIMIT 5"
);

// For Charts: Monthly Messages (last 12 months)
$monthly_messages = [];
for ($i = 11; $i >= 0; $i--) {
    $month_start = date('Y-m-01', strtotime("-$i months"));
    $month_end = date('Y-m-t', strtotime("-$i months"));
    $count = $db->select_single(
        "SELECT COUNT(*) as count FROM tbl_wp_msg_logs WHERE created_at BETWEEN :start AND :end",
        ['start' => $month_start, 'end' => $month_end]
    )['count'] ?? 0;
    $monthly_messages[] = $count;
}
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $months[] = date('M Y', strtotime("-$i months"));
}

// Message Types for Donut
$message_types = $db->select(
    "SELECT message_type, COUNT(*) as count 
     FROM tbl_wp_msg_logs 
     GROUP BY message_type"
);

// Grades for Bar Chart
$grades_count = $db->select(
    "SELECT g.grade_name, COUNT(u.id) as count 
     FROM tbl_grades g 
     LEFT JOIN tbl_users u ON g.id = u.grade_id 
     GROUP BY g.id"
);

// Employee Status for Donut
$active_employees = $db->select_single(
    "SELECT COUNT(*) as active FROM tbl_users WHERE user_type = 3 AND user_status = 1"
)['active'] ?? 0;
$inactive_employees = $db->select_single(
    "SELECT COUNT(*) as inactive FROM tbl_users WHERE user_type = 3 AND user_status = 0"
)['inactive'] ?? 0;

include 'layouts/layoutTop.php';
?>

<div class="row row-cols-xxxl-5 row-cols-lg-3 row-cols-sm-2 row-cols-1 gy-4">
    <div class="col">
        <div class="card shadow-none border bg-gradient-start-1 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Total Employees</p>
                        <h6 class="mb-0"><?php echo number_format($total_employees); ?></h6>
                    </div>
                    <div class="w-50-px h-50-px bg-cyan rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="gridicons:multiple-users" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
                <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center gap-1 text-<?php echo $new_employees_30 >= 0 ? 'success' : 'danger'; ?>-main">
                        <iconify-icon icon="bxs:<?php echo $new_employees_30 >= 0 ? 'up' : 'down'; ?>-arrow" class="text-xs"></iconify-icon> <?php echo $new_employees_30 >= 0 ? '+' : ''; ?><?php echo number_format($new_employees_30); ?>
                    </span>
                    Last 30 days new employees
                </p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-none border bg-gradient-start-2 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Active Events</p>
                        <h6 class="mb-0"><?php echo number_format($total_active_events); ?></h6>
                    </div>
                    <div class="w-50-px h-50-px bg-purple rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="fa-solid:calendar-check" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
                <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center gap-1 text-success-main">
                        <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon> +0
                    </span>
                    Last 30 days events
                </p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-none border bg-gradient-start-3 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Pending Messages</p>
                        <h6 class="mb-0"><?php echo number_format($pending_messages); ?></h6>
                    </div>
                    <div class="w-50-px h-50-px bg-info rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="fluent:mail-20-filled" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
                <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center gap-1 text-danger-main">
                        <iconify-icon icon="bxs:down-arrow" class="text-xs"></iconify-icon> -0
                    </span>
                    Last 30 days pending
                </p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-none border bg-gradient-start-4 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Sent Messages</p>
                        <h6 class="mb-0"><?php echo number_format($sent_messages); ?></h6>
                    </div>
                    <div class="w-50-px h-50-px bg-success-main rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="akar-icons:send" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
                <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center gap-1 text-success-main">
                        <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon> +<?php echo number_format($sent_30); ?>
                    </span>
                    Last 30 days sent
                </p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-none border bg-gradient-start-5 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Failed Messages</p>
                        <h6 class="mb-0"><?php echo number_format($failed_messages); ?></h6>
                    </div>
                    <div class="w-50-px h-50-px bg-red rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="akar-icons:cross" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
                <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center gap-1 text-danger-main">
                        <iconify-icon icon="bxs:down-arrow" class="text-xs"></iconify-icon> -<?php echo number_format($failed_30); ?>
                    </span>
                    Last 30 days failed
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row gy-4 mt-1">
    <div class="col-xxl-12 col-xl-12">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <h6 class="text-lg mb-0">Message Statistics</h6>
                    <select class="form-select bg-base form-select-sm w-auto">
                        <option>Yearly</option>
                        <option selected>Monthly</option>
                        <option>Weekly</option>
                        <option>Today</option>
                    </select>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-8">
                    <h6 class="mb-0"><?php echo number_format($new_messages_30); ?></h6>
                    <span class="text-sm fw-semibold rounded-pill bg-success-focus text-success-main border br-success px-8 py-4 line-height-1 d-flex align-items-center gap-1">
                        <?php echo round(($new_messages_30 / max(1, $sent_messages + $failed_messages)) * 100, 1); ?>% <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon>
                    </span>
                    <span class="text-xs fw-medium">+ <?php echo number_format($sent_30); ?> Per Month</span>
                </div>
                <div id="messageChart" class="pt-28 apexcharts-tooltip-style-1"></div>
            </div>
        </div>
    </div>
    <div class="col-xxl-6 col-xl-6">
        <div class="card h-100 radius-8 border">
            <div class="card-body p-24">
                <h6 class="mb-12 fw-semibold text-lg mb-16">Employees by Grade</h6>
                <div class="d-flex align-items-center gap-2 mb-20">
                    <h6 class="fw-semibold mb-0"><?php echo number_format($total_employees); ?></h6>
                    <p class="text-sm mb-0">
                        <span class="bg-success-focus border br-success px-8 py-2 rounded-pill fw-semibold text-success-main text-sm d-inline-flex align-items-center gap-1">
                            <?php echo round(($active_employees / max(1, $total_employees)) * 100, 1); ?>%
                            <iconify-icon icon="iconamoon:arrow-up-2-fill" class="icon"></iconify-icon>
                        </span>
                        Active Employees
                    </p>
                </div>
                <div id="gradeBarChart"></div>
            </div>
        </div>
    </div>
    <div class="col-xxl-6 col-xl-6">
        <div class="card h-100 radius-8 border-0 overflow-hidden">
            <div class="card-body p-24">
                <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                    <h6 class="mb-2 fw-bold text-lg">Employees Overview</h6>
                    <div>
                        <select class="form-select form-select-sm w-auto bg-base border text-secondary-light">
                            <option>Today</option>
                            <option>Weekly</option>
                            <option>Monthly</option>
                            <option>Yearly</option>
                        </select>
                    </div>
                </div>
                <div id="employeeDonutChart"></div>
                <ul class="d-flex flex-wrap align-items-center justify-content-between mt-3 gap-3">
                    <li class="d-flex align-items-center gap-2">
                        <span class="w-12-px h-12-px radius-2 bg-primary-600"></span>
                        <span class="text-secondary-light text-sm fw-normal">Active:
                            <span class="text-primary-light fw-semibold"><?php echo number_format($active_employees); ?></span>
                        </span>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <span class="w-12-px h-12-px radius-2 bg-yellow"></span>
                        <span class="text-secondary-light text-sm fw-normal">Inactive:
                            <span class="text-primary-light fw-semibold"><?php echo number_format($inactive_employees); ?></span>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-xxl-12 col-xl-12">
        <div class="card h-100">
            <div class="card-body p-24">
                <div class="d-flex flex-wrap align-items-center gap-1 justify-content-between mb-16">
                    <ul class="nav border-gradient-tab nav-pills mb-0" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link d-flex align-items-center active" id="pills-employees-tab" data-bs-toggle="pill" data-bs-target="#pills-employees" type="button" role="tab" aria-controls="pills-employees" aria-selected="true">
                                Latest Registered Employees
                                <span class="text-sm fw-semibold py-6 px-12 bg-neutral-500 rounded-pill text-white line-height-1 ms-12 notification-alert"><?php echo count($latest_employees); ?></span>
                            </button>
                        </li>
                    </ul>
                    <a href="employee-manage.php" class="text-primary-600 hover-text-primary d-flex align-items-center gap-1">
                        View All
                        <iconify-icon icon="solar:alt-arrow-right-linear" class="icon"></iconify-icon>
                    </a>
                </div>
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="pills-employees" role="tabpanel" aria-labelledby="pills-employees-tab" tabindex="0">
                        <div class="table-responsive scroll-sm">
                            <table class="table bordered-table sm-table mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Employees</th>
                                        <th scope="col">Registered On</th>
                                        <th scope="col">Grade</th>
                                        <th scope="col" class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latest_employees as $emp) { 
                                        $grade = $db->select_single("SELECT grade_name FROM tbl_grades WHERE id = :id", ['id' => $emp['grade_id']])['grade_name'] ?? 'N/A';
                                        $image_path = $emp['personal_photo'] && file_exists(__DIR__ . '/uploads/employees/' . $emp['personal_photo']) 
                                            ? 'uploads/employees/' . $emp['personal_photo'] 
                                            : 'assets/images/users/default.png';
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars($image_path); ?>" alt="" class="w-40-px h-40-px rounded-circle flex-shrink-0 me-12 overflow-hidden">
                                                    <div class="flex-grow-1">
                                                        <h6 class="text-md mb-0 fw-medium"><?php echo htmlspecialchars($emp['user_name']); ?></h6>
                                                        <span class="text-sm text-secondary-light fw-medium"><?php echo htmlspecialchars($emp['user_email'] ?: $emp['user_phone']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($emp['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($grade); ?></td>
                                            <td class="text-center">
                                                <span class="bg-<?php echo $emp['user_status'] ? 'success' : 'danger'; ?>-focus text-<?php echo $emp['user_status'] ? 'success' : 'danger'; ?>-main px-24 py-4 rounded-pill fw-medium text-sm"><?php echo $emp['user_status'] ? 'Active' : 'Inactive'; ?></span>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                    <h6 class="mb-2 fw-bold text-lg mb-0">Message Types</h6>
                    <select class="form-select form-select-sm w-auto bg-base border text-secondary-light">
                        <option>Today</option>
                        <option>Weekly</option>
                        <option>Monthly</option>
                        <option>Yearly</option>
                    </select>
                </div>
                <ul class="d-flex flex-wrap align-items-center mt-3 gap-3">
                    <?php 
                    $type_names = [1 => 'Birthday', 2 => 'Anniversary', 3 => 'Manual', 4 => 'Send Now', 5 => 'Simple', 6 => 'Bulk Upload'];
                    foreach ($message_types as $type) {
                        $name = $type_names[$type['message_type']] ?? 'Type ' . $type['message_type'];
                    ?>
                        <li class="d-flex align-items-center gap-2">
                            <span class="w-12-px h-12-px rounded-circle bg-<?php echo ['primary-600', 'yellow', 'info', 'success-main', 'purple', 'success-main'][$type['message_type'] % 6]; ?>"></span>
                            <span class="text-secondary-light text-sm fw-semibold"><?php echo htmlspecialchars($name); ?>:
                                <span class="text-primary-light fw-bold"><?php echo number_format($type['count']); ?></span>
                            </span>
                        </li>
                    <?php } ?>
                </ul>
                <div class="mt-40">
                    <div id="messageTypeChart" class="margin-16-minus"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'layouts/layoutBottom.php'; ?>

<script>
// ApexCharts for Message Statistics (Area Chart)
var messageChartOptions = {
    series: [{
        name: 'Messages',
        data: <?php echo json_encode($monthly_messages); ?>
    }],
    chart: {
        type: 'area',
        height: 350,
        zoom: { enabled: false }
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth' },
    xaxis: {
        categories: <?php echo json_encode($months); ?>,
        labels: { rotate: -45 }
    },
    tooltip: { x: { format: 'MMM yyyy' } },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.3 } }
};
var messageChart = new ApexCharts(document.querySelector("#messageChart"), messageChartOptions);
messageChart.render();

// Bar Chart for Employees by Grade
var gradeBarOptions = {
    series: [{
        name: 'Employees',
        data: <?php echo json_encode(array_column($grades_count, 'count')); ?>
    }],
    chart: { type: 'bar', height: 350 },
    plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
    dataLabels: { enabled: false },
    xaxis: { categories: <?php echo json_encode(array_column($grades_count, 'grade_name')); ?> },
    yaxis: { title: { text: 'Number of Employees' } },
    tooltip: { y: { formatter: function(val) { return val + " Employees"; } } }
};
var gradeBarChart = new ApexCharts(document.querySelector("#gradeBarChart"), gradeBarOptions);
gradeBarChart.render();

// Donut Chart for Employees Overview
var employeeDonutOptions = {
    series: [<?php echo $active_employees; ?>, <?php echo $inactive_employees; ?>],
    chart: { type: 'donut', height: 350 },
    labels: ['Active', 'Inactive'],
    colors: ['#0066cc', '#ffcc00'],
    responsive: [{ breakpoint: 480, options: { chart: { width: 200 }, legend: { position: 'bottom' } } }],
    dataLabels: { enabled: false }
};
var employeeDonutChart = new ApexCharts(document.querySelector("#employeeDonutChart"), employeeDonutOptions);
employeeDonutChart.render();

// Donut Chart for Message Types
var messageTypeChartOptions = {
    series: <?php echo json_encode(array_column($message_types, 'count')); ?>,
    chart: { type: 'donut', height: 350 },
    labels: <?php echo json_encode(array_map(function($type) use ($type_names) { return $type_names[$type['message_type']] ?? 'Type ' . $type['message_type']; }, $message_types)); ?>,
    colors: ['#0066cc', '#ffcc00', '#00ccff', '#00cc00', '#cc00cc', '#00cc00'],
    responsive: [{ breakpoint: 480, options: { chart: { width: 200 }, legend: { position: 'bottom' } } }],
    dataLabels: { enabled: false }
};
var messageTypeChart = new ApexCharts(document.querySelector("#messageTypeChart"), messageTypeChartOptions);
messageTypeChart.render();
</script>
