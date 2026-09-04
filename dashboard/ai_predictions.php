<?php
require __DIR__ . '/bootstrap.php';
require_perm('view');

$pageTitle = 'AI Absence Risk';
$currentPage = 'ai_predictions';

$tableReady = db_table_exists('absence_predictions');
$predictionDate = null;
$generatedAt = null;
$predictions = [];
$counts = ['high' => 0, 'medium' => 0, 'low' => 0];

if ($tableReady) {
    $predictionDate = db_val('SELECT MAX(prediction_date) FROM absence_predictions');
    if ($predictionDate) {
        $predictions = db_all(
            "SELECT ap.user_id, ap.prediction_date, ap.probability, ap.risk_level,
                    ap.reason, ap.history_days, ap.model_version, ap.generated_at,
                    e.first_name, e.last_name,
                    COALESCE(d.name, 'Unassigned') AS department
             FROM absence_predictions ap
             INNER JOIN employees e ON e.user_id = ap.user_id
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE ap.prediction_date = ? AND e.status = 'active'
             ORDER BY ap.probability DESC, e.first_name, e.last_name",
            [$predictionDate]
        );

        foreach ($predictions as $prediction) {
            $level = $prediction['risk_level'];
            if (isset($counts[$level])) {
                $counts[$level]++;
            }
            if ($generatedAt === null || $prediction['generated_at'] > $generatedAt) {
                $generatedAt = $prediction['generated_at'];
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<style>
.ai-intro{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:20px;padding:18px 20px;border:1px solid rgba(129,140,248,.22);border-radius:14px;background:linear-gradient(120deg,rgba(129,140,248,.11),rgba(34,211,238,.05))}
.ai-intro h2{font-family:'Sora',sans-serif;font-size:20px;margin-bottom:5px}.ai-intro p{color:#B0B8D0;max-width:760px}
.ai-chip{white-space:nowrap;padding:6px 11px;border-radius:999px;background:rgba(34,211,238,.1);border:1px solid rgba(34,211,238,.2);color:#22D3EE;font-size:12px;font-weight:700}
.ai-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:20px}
.ai-stat{padding:17px 18px;border-radius:12px;background:#0A0C18;border:1px solid rgba(255,255,255,.09)}
.ai-stat span{display:block;color:#9AA2C0;font-size:13px}.ai-stat strong{display:block;margin-top:3px;font-family:'Sora',sans-serif;font-size:28px}
.ai-high strong{color:#FB7185}.ai-medium strong{color:#FBBF24}.ai-low strong{color:#34D399}
.risk-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:999px;font-size:12px;font-weight:700;text-transform:capitalize}
.risk-high{color:#FB7185;background:rgba(251,113,133,.1);border:1px solid rgba(251,113,133,.2)}
.risk-medium{color:#FBBF24;background:rgba(251,191,36,.1);border:1px solid rgba(251,191,36,.2)}
.risk-low{color:#34D399;background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.2)}
.ai-help{padding:20px;border-radius:12px;background:#0A0C18;border:1px solid rgba(255,255,255,.09);color:#B0B8D0}
.ai-help code{display:inline-block;margin-top:10px;padding:7px 10px;border-radius:7px;background:#05060D;color:#22D3EE}
.ai-footnote{margin-top:14px;color:#9AA2C0;font-size:12.5px}
@media(max-width:760px){.ai-grid{grid-template-columns:1fr}.ai-intro{flex-direction:column}.ai-chip{white-space:normal}}
</style>

<div class="ai-intro">
    <div>
        <h2>Predicted absence risk</h2>
        <p>Random Forest estimates the next absence risk from each employee's previous attendance pattern.</p>
    </div>
    <div class="ai-chip">Random Forest · <?= e($predictionDate ?: 'No prediction yet') ?></div>
</div>

<?php if (!$tableReady): ?>
    <div class="ai-help">
        <strong>The AI module has not been initialized.</strong><br>
        From the project root, install the Python requirements and run:
        <br><code>python ai\predict_absences.py</code>
    </div>
<?php elseif (!$predictionDate || !$predictions): ?>
    <div class="ai-help">
        <strong>No prediction is available yet.</strong><br>
        Synchronize the latest punches, then run:
        <br><code>python ai\predict_absences.py</code>
    </div>
<?php else: ?>
    <div class="ai-grid">
        <div class="ai-stat ai-high"><span>High risk</span><strong><?= (int)$counts['high'] ?></strong></div>
        <div class="ai-stat ai-medium"><span>Medium risk</span><strong><?= (int)$counts['medium'] ?></strong></div>
        <div class="ai-stat ai-low"><span>Low risk</span><strong><?= (int)$counts['low'] ?></strong></div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <div>
                <h3>Employee predictions</h3>
                <p class="sub">Target date: <?= e(date('F j, Y', strtotime($predictionDate))) ?><?= $generatedAt ? ' · Generated ' . e(date('M j, Y H:i', strtotime($generatedAt))) : '' ?></p>
            </div>
        </div>
        <div class="table-scroll">
            <table class="att">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Estimated risk</th>
                        <th>Level</th>
                        <th>Main recent factor</th>
                        <th>History</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($predictions as $row): ?>
                    <tr>
                        <td>
                            <div class="emp">
                                <div class="emp-av" style="background:linear-gradient(135deg,#818CF8,#22D3EE)">
                                    <?= e(strtoupper(substr($row['first_name'], 0, 1))) ?>
                                </div>
                                <div>
                                    <div class="emp-name"><?= e($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                    <div class="emp-id">UID <?= (int)$row['user_id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= e($row['department']) ?></td>
                        <td class="mono"><?= e(number_format((float)$row['probability'], 1)) ?>%</td>
                        <td><span class="risk-pill risk-<?= e($row['risk_level']) ?>"><?= e($row['risk_level']) ?></span></td>
                        <td><?= e($row['reason'] ?: 'Recent attendance pattern') ?></td>
                        <td class="mono"><?= (int)$row['history_days'] ?> days</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<p class="ai-footnote">This prediction supports planning only. It must not be used alone for disciplinary or employment decisions.</p>

<?php include __DIR__ . '/includes/footer.php'; ?>
