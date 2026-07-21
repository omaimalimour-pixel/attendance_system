<aside class="app-sidebar" id="sidebar">
    <div class="brand">
        <div class="logo">
            <i data-lucide="fingerprint"></i>
        </div>
        <div class="brand-text">
            <div class="brand-name">ZKTeco</div>
            <div class="brand-sub">Attendance Suite</div>
        </div>
    </div>

    <div class="nav-section-label">Menu</div>

    <a href="dashboard.php" class="side-link <?= ($currentPage == 'dashboard') ? 'active' : '' ?>">
        <i data-lucide="layout-dashboard"></i>
        <span class="link-text">Dashboard</span>
    </a>

    <a href="employees.php" class="side-link <?= ($currentPage == 'employees') ? 'active' : '' ?>">
        <i data-lucide="users"></i>
        <span class="link-text">Employees</span>
    </a>

    <a href="attendance.php" class="side-link <?= ($currentPage == 'attendance') ? 'active' : '' ?>">
        <i data-lucide="calendar-check"></i>
        <span class="link-text">Attendance</span>
    </a>

    <a href="users.php" class="side-link <?= ($currentPage == 'users') ? 'active' : '' ?>">
        <i data-lucide="shield"></i>
        <span class="link-text">Users</span>
    </a>

    <div class="nav-section-label">Reports</div>

    <a href="analytics.php" class="side-link <?= ($currentPage == 'analytics') ? 'active' : '' ?>">
        <i data-lucide="bar-chart-3"></i>
        <span class="link-text">Analytics</span>
    </a>

    <a href="exports.php" class="side-link <?= ($currentPage == 'exports') ? 'active' : '' ?>">
        <i data-lucide="file-text"></i>
        <span class="link-text">Exports</span>
    </a>

    <div class="nav-section-label">System</div>

    <a href="sync_attendance.php" class="side-link <?= ($currentPage == 'sync') ? 'active' : '' ?>">
        <i data-lucide="refresh-cw"></i>
        <span class="link-text">Sync Device</span>
    </a>

    <div class="sidebar-footer">
        <div class="user-mini">
            <div class="avatar">
                <?= isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'],0,1)) : 'A' ?>
            </div>
            <div class="um-text">
                <div style="font-weight:600"><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin' ?></div>
                <div style="font-size:12px;color:gray"><?= isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'Administrator' ?></div>
            </div>
        </div>
        <a href="../logout.php" class="side-link logout-link" style="margin-top:10px;color:var(--danger);">
            <i data-lucide="log-out"></i>
            <span class="link-text">Logout</span>
        </a>
    </div>
</aside>
