<!-- ================= TABLE ATTENDANCE ================= -->

<div class="panel">

    <div class="panel-head">

        <div>

            <h3>Today's Attendance</h3>

            <p class="sub">

                <?= date("d/m/Y",strtotime($selectedDate)); ?>

            </p>

        </div>

    </div>

    <div class="table-scroll">

        <table class="att">

            <thead>

            <tr>

                <th>Employee</th>

                <th>First IN</th>

                <th>Last OUT</th>

                <th>Work Hours</th>

                <th>Punches</th>

                <th>Status</th>

            </tr>

            </thead>

            <tbody>

            <?php

            while($row=mysqli_fetch_assoc($resultAttendanceTable)){

                $status="Absent";

                $badge="b-absent";

                if($row['first_in']){

                    $status="Present";

                    $badge="b-present";

                }

                $hours="--";

                if($row['first_in'] && $row['last_out']){

                    $start=strtotime($row['first_in']);

                    $end=strtotime($row['last_out']);

                    $hours=gmdate("H:i",$end-$start);

                }

                $letter=strtoupper(substr($row['first_name'],0,1));

            ?>

            <tr>

                <td>

                    <div class="emp">

                        <div class="emp-av">

                            <?= $letter ?>

                        </div>

                        <div>

                            <div class="emp-name">

                                <?= htmlspecialchars($row['first_name']." ".$row['last_name']) ?>

                            </div>

                            <div class="emp-id">

                                ID : <?= $row['user_id'] ?>

                            </div>

                        </div>

                    </div>

                </td>

                <td class="mono">

                    <?= $row['first_in'] ? $row['first_in'] : "--" ?>

                </td>

                <td class="mono">

                    <?= $row['last_out'] ? $row['last_out'] : "--" ?>

                </td>

                <td class="mono">

                    <?= $hours ?>

                </td>

                <td class="mono">

                    <?= $row['punches'] ?>

                </td>

                <td>

                    <span class="badge-status <?= $badge ?>">

                        <span class="bdot"></span>

                        <?= $status ?>

                    </span>

                </td>

            </tr>

            <?php } ?>

            </tbody>

        </table>

    </div>

</div>