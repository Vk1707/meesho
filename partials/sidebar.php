<aside class="sidebar active">
    <button type="button" class="sidebar-close-btn">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        <a href="dashboard.php" class="sidebar-logo">
            <img src="uploads/logos/logogr.png" alt="site logo" class="light-logo">
            <img src="uploads/logos/logogr.png" alt="site logo" class="dark-logo">
            <img src="uploads/logos/logogr-icon.png" alt="site logo" class="logo-icon">
        </a>
    </div>
    <div class="sidebar-menu-area">
        <ul class="sidebar-menu" id="sidebar-menu">
            <li>
                <a href="dashboard.php">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
            </li>

            <?php if (isset($_SESSION['adminId'])) { ?>
                <!-- Employee Management Menu -->
                <li class="dropdown">
                    <a href="javascript:void(0)">
                        <iconify-icon icon="clarity:employee-group-line" class="menu-icon"></iconify-icon>
                        <span>Manage Orders</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="order-upload.php">
                                <iconify-icon icon="mdi:account-plus-outline" class="menu-icon"></iconify-icon>
                                <span>Upload Orders</span>
                            </a>
                        </li>
                        <li>
                            <a href="order-manage.php">
                                <iconify-icon icon="mdi:account-group-outline" class="menu-icon"></iconify-icon>
                                <span>Order History</span>
                            </a>
                        </li>
                    </ul>
                </li>


                <?php if ($_SESSION['userType'] == 1) { ?>
                    <!-- Settings Menu (for user_type == 1 only) -->
                    <li class="sidebar-menu-group-title">Application</li>
                    <li class="dropdown">
                        <a href="javascript:void(0)">
                            <iconify-icon icon="icon-park-outline:setting-two" class="menu-icon"></iconify-icon>
                            <span>Settings</span>
                        </a>
                        <ul class="sidebar-submenu">
                            <li>
                                <a href="product-manage.php">
                                    <iconify-icon icon="mdi:package-variant" class="menu-icon"></iconify-icon>
                                    <span>Manage Products</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php } ?>
            <?php } ?>

            <?php if ($_SESSION['userType'] == 1) { ?>
            <!-- My Profile Menu (available to all users) -->
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:user-id-bold" class="menu-icon"></iconify-icon>
                    <span>My Profile</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="password-reset.php">
                            <iconify-icon icon="solar:lock-password-unlocked-outline" class="menu-icon"></iconify-icon>
                            <span>Change Password</span>
                        </a>
                    </li>
                </ul>
            </li>
            <?php } ?>

            <!-- Logout Menu (available to all users) -->
            <li>
                <a href="logout.php">
                    <iconify-icon icon="lucide:power" class="menu-icon"></iconify-icon>
                    <span>Log Out</span>
                </a>
            </li>
        </ul>
    </div>
</aside>