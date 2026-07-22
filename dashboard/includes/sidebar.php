<?php $u = function_exists('current_user') ? current_user() : null; $isAdmin = function_exists('can') && can('*'); ?>
<aside class="sidebar" id="sb">
<div class="brand"><div class="brand-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6a6 6 0 1 0 6 6"/><circle cx="12" cy="12" r="2"/></svg></div><div><div class="brand-name">Chrono<span>X</span></div><div class="brand-sub">Attendance Suite</div></div></div>

<div class="nav-label">Overview</div>
<a href="dashboard.php" class="side-link <?=$currentPage=='dashboard'?'active':''?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg><span>Dashboard</span></a>
<a href="analytics.php" class="side-link <?=$currentPage=='analytics'?'active':''?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/></svg><span>Analytics</span></a>

<div class="nav-label">Manage</div>
<a href="employees.php" class="side-link <?=$currentPage=='employees'?'active':''?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg><span>Employees</span></a>
<a href="attendance.php" class="side-link <?=$currentPage=='attendance'?'active':''?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg><span>Attendance</span></a>
<?php if ($isAdmin): ?>
<a href="departments.php" class="side-link <?=$currentPage=='departments'?'active':''?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M9 17h.01M15 17h.01"/></svg><span>Departments</span></a>
<?php endif; ?>
<a href="exports.php" class="side-link <?=$currentPage=='exports'?'active':''?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 18v-6M9 15l3 3 3-3"/></svg><span>Exports</span></a>

<div class="nav-label">Devices</div>
<?php if ($isAdmin): ?>
<a href="devices.php" class="side-link <?=$currentPage=='devices'?'active':''?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><circle cx="12" cy="12" r="3"/></svg><span>Clocking Machines</span></a>
<?php endif; ?>
<a href="sync_attendance.php" class="side-link <?=$currentPage=='sync'?'active':''?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg><span>Sync Devices</span></a>
<?php if ($isAdmin): ?>
<a href="enrollment.php" class="side-link <?=$currentPage=='enrollment'?'active':''?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 11c0 3.5-2 6-2 6M7 8a5 5 0 0 1 10 0v2M5 12a7 7 0 0 1 .5-2.6M12 8v5a8 8 0 0 0 2 5M9 20a12 12 0 0 1-1.5-6"/></svg><span>Fingerprint Enrolment</span><?php $pending=function_exists('db_val')?@db_val("SELECT COUNT(*) FROM enrollment_requests WHERE status='pending'"):0; if((int)$pending>0): ?><span style="margin-left:auto;background:var(--rose);color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px"><?=(int)$pending?></span><?php endif; ?></a>
<a href="enroll_finger.php" class="side-link <?=$currentPage=='enroll_finger'?'active':''?>" style="padding-left:28px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11V7a3 3 0 0 1 6 0v4M5 11h14l1 9H4z"/></svg><span>Enroll on Device</span></a>
<a href="device_test.php" class="side-link <?=$currentPage=='device_test'?'active':''?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M12 8v4M12 16h.01"/></svg><span>Device Test</span></a>
<?php endif; ?>

<?php if ($isAdmin): ?>
<div class="nav-label">System</div>
<a href="users.php" class="side-link <?=$currentPage=='users'?'active':''?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg><span>Users &amp; Roles</span></a>
<?php endif; ?>

<div class="sidebar-footer">
<div class="user-card"><div class="user-av"><?= e(strtoupper(substr($u['name'] ?? ($u['username'] ?? 'A'),0,1))) ?></div><div><div class="user-name"><?= e($u['name'] ?? ($u['username'] ?? 'Admin')) ?></div><div class="user-role"><?= e($u['role'] ?? 'Viewer') ?></div></div></div>
<a href="../logout.php" class="side-link logout-link" style="margin-top:4px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg><span>Logout</span></a>
</div></aside>
