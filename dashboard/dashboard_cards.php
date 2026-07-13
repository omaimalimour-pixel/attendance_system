<div class="row g-3 mb-4">

    <!-- ================= TOTAL EMPLOYEES ================= -->

    <div class="col-12 col-sm-6 col-lg-4 col-xl">

        <div class="kpi-card">

            <div class="kpi-top">

                <div class="kpi-icon ic-blue">
                    <i data-lucide="users"></i>
                </div>

                <span class="kpi-sub trend-up">
                    <i data-lucide="trending-up"></i>
                    Active
                </span>

            </div>

            <div class="kpi-label">
                Total Employees
            </div>

            <div class="kpi-value">
                <?= $totalEmployees ?>
            </div>

            <div class="kpi-sub" style="color:var(--muted);">
                Registered in system
            </div>

        </div>

    </div>

    <!-- ================= PRESENT ================= -->

    <div class="col-12 col-sm-6 col-lg-4 col-xl">

        <div class="kpi-card">

            <div class="kpi-top">

                <div class="kpi-icon ic-green">
                    <i data-lucide="check-circle-2"></i>
                </div>

                <span class="kpi-sub trend-up">
                    <i data-lucide="trending-up"></i>
                    <?= $attendanceRate ?>%
                </span>

            </div>

            <div class="kpi-label">
                Present Today
            </div>

            <div class="kpi-value" style="color:var(--success);">
                <?= $totalPresent ?>
            </div>

            <div class="kpi-sub" style="color:var(--muted);">
                Checked in today
            </div>

        </div>

    </div>

    <!-- ================= ABSENT ================= -->

    <div class="col-12 col-sm-6 col-lg-4 col-xl">

        <div class="kpi-card">

            <div class="kpi-top">

                <div class="kpi-icon ic-red">
                    <i data-lucide="user-x"></i>
                </div>

                <span class="kpi-sub trend-down">
                    <i data-lucide="trending-down"></i>
                    <?= 100 - $attendanceRate ?>%
                </span>

            </div>

            <div class="kpi-label">
                Absent Today
            </div>

            <div class="kpi-value" style="color:var(--danger);">
                <?= $totalAbsent ?>
            </div>

            <div class="kpi-sub" style="color:var(--muted);">
                Not checked in
            </div>

        </div>

    </div>

    <!-- ================= TOTAL PUNCHES ================= -->

    <div class="col-12 col-sm-6 col-lg-6 col-xl">

        <div class="kpi-card">

            <div class="kpi-top">

                <div class="kpi-icon ic-amber">
                    <i data-lucide="clock"></i>
                </div>

                <span class="kpi-sub">
                    Today
                </span>

            </div>

            <div class="kpi-label">
                Total Punches
            </div>

            <div class="kpi-value" style="color:orange;">
                <?= $totalPunches ?>
            </div>

            <div class="kpi-sub" style="color:var(--muted);">
                Recorded events
            </div>

        </div>

    </div>

    <!-- ================= DEVICE ================= -->

    <div class="col-12 col-sm-6 col-lg-6 col-xl">

        <div class="kpi-card">

            <div class="kpi-top">

                <div class="kpi-icon ic-blue">
                    <i data-lucide="server"></i>
                </div>

                <span class="status-pill pill-online">

                    <span class="live-dot"></span>

                    Online

                </span>

            </div>

            <div class="kpi-label">
                Connected Device
            </div>

            <div class="kpi-value" style="font-size:24px;color:var(--primary);">
                <?= $device ?>
            </div>

            <div class="kpi-sub" style="color:var(--muted);">
                <?= $selectedDate ?>
            </div>

        </div>

    </div>

</div>