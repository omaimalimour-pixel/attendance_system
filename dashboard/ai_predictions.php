<?php
require __DIR__ . '/bootstrap.php';
require_perm('view');

$pageTitle = 'AI Absence Risk';
$currentPage = 'ai_predictions';

$tableReady = db_table_exists('absence_predictions');
$predictionDate = null;
$generatedAt = null;
$dataSource = null;
$trainingRows = 0;
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
            $modelVersion = $prediction['model_version'] ?? '';
            if (substr($modelVersion, -10) === '-synthetic') {
                $dataSource = 'synthetic';
            } elseif (substr($modelVersion, -5) === '-demo') {
                $dataSource = 'demo';
            } else {
                $dataSource = 'real';
            }

            $level = $prediction['risk_level'];
            if (isset($counts[$level])) {
                $counts[$level]++;
            }
            if ($generatedAt === null || $prediction['generated_at'] > $generatedAt) {
                $generatedAt = $prediction['generated_at'];
            }
        }

        if (db_table_exists('ai_training_data')) {
            $trainingRows = (int) db_val(
                'SELECT COUNT(*) FROM ai_training_data WHERE data_source = ?',
                [$dataSource]
            );
        }
    }
}

$totalPredictions = count($predictions);
$averageRisk = $totalPredictions > 0
    ? array_sum(array_map(static fn($row) => (float) $row['probability'], $predictions)) / $totalPredictions
    : 0;
$highStop = $totalPredictions > 0 ? ($counts['high'] / $totalPredictions) * 100 : 0;
$mediumStop = $totalPredictions > 0
    ? (($counts['high'] + $counts['medium']) / $totalPredictions) * 100
    : 0;
$distributionStyle = $totalPredictions > 0
    ? sprintf(
        'conic-gradient(#fb7185 0 %.2f%%,#fbbf24 %.2f%% %.2f%%,#34d399 %.2f%% 100%%)',
        $highStop,
        $highStop,
        $mediumStop,
        $mediumStop
    )
    : 'conic-gradient(rgba(255,255,255,.08) 0 100%)';
$sourceLabel = $dataSource === 'synthetic'
    ? 'Synthetic SQL dataset'
    : ($dataSource === 'demo' ? 'Legacy demo dataset' : 'Live attendance data');
$topPrediction = $predictions[0] ?? null;

include __DIR__ . '/includes/header.php';
?>

<style>
.ai-shell{--ai-indigo:#818cf8;--ai-cyan:#22d3ee;--ai-violet:#a78bfa;--ai-high:#fb7185;--ai-medium:#fbbf24;--ai-low:#34d399;position:relative}
.ai-hero{position:relative;isolation:isolate;display:grid;grid-template-columns:minmax(0,1.45fr) minmax(280px,.55fr);min-height:300px;overflow:hidden;margin-bottom:20px;padding:34px 38px;border:1px solid rgba(129,140,248,.25);border-radius:24px;background:radial-gradient(circle at 82% 42%,rgba(34,211,238,.12),transparent 26%),radial-gradient(circle at 70% 10%,rgba(129,140,248,.19),transparent 38%),linear-gradient(125deg,#0d1020 0%,#090b17 52%,#080b16 100%);box-shadow:0 28px 80px rgba(0,0,0,.28)}
.ai-hero::before{content:"";position:absolute;inset:0;z-index:-1;opacity:.22;background-image:linear-gradient(rgba(129,140,248,.16) 1px,transparent 1px),linear-gradient(90deg,rgba(129,140,248,.16) 1px,transparent 1px);background-size:42px 42px;mask-image:linear-gradient(90deg,#000,transparent 74%)}
.ai-hero::after{content:"";position:absolute;width:340px;height:340px;right:-110px;bottom:-210px;z-index:-1;border-radius:50%;background:rgba(129,140,248,.14);filter:blur(30px)}
.ai-kicker{display:inline-flex;align-items:center;gap:8px;margin-bottom:18px;color:#7dd3fc;font-size:11px;font-weight:800;letter-spacing:.17em;text-transform:uppercase}
.ai-kicker::before{content:"";width:7px;height:7px;border-radius:50%;background:#22d3ee;box-shadow:0 0 0 5px rgba(34,211,238,.1),0 0 18px #22d3ee}
.ai-hero-copy{position:relative;z-index:2;align-self:center;max-width:760px}
.ai-hero h2{max-width:700px;margin:0 0 12px;font-family:'Sora',sans-serif;font-size:clamp(30px,3.2vw,50px);line-height:1.07;letter-spacing:-.045em;color:#f8faff}
.ai-hero h2 span{background:linear-gradient(100deg,#fff 0%,#a5b4fc 42%,#67e8f9 78%,#c4b5fd 100%);-webkit-background-clip:text;background-clip:text;color:transparent}
.ai-hero-copy>p{max-width:650px;margin:0;color:#abb4cf;font-size:15px;line-height:1.75}
.ai-meta{display:flex;flex-wrap:wrap;gap:9px;margin-top:23px}
.ai-meta-item{display:inline-flex;align-items:center;gap:8px;padding:7px 11px;border:1px solid rgba(255,255,255,.09);border-radius:999px;background:rgba(255,255,255,.04);color:#cdd3e8;font-size:11.5px;font-weight:600;backdrop-filter:blur(8px)}
.ai-meta-item svg{width:14px;height:14px;color:#818cf8}
.ai-orb-stage{position:relative;display:grid;place-items:center;min-height:230px}
.ai-orb-glow{position:absolute;width:190px;height:190px;border-radius:50%;background:radial-gradient(circle,rgba(34,211,238,.22),rgba(129,140,248,.09) 48%,transparent 72%);filter:blur(8px);animation:ai-breathe 3.8s ease-in-out infinite}
.ai-orb{position:relative;display:grid;place-items:center;width:132px;height:132px;border:1px solid rgba(129,140,248,.42);border-radius:50%;background:radial-gradient(circle at 35% 28%,rgba(255,255,255,.16),transparent 16%),radial-gradient(circle,rgba(34,211,238,.2),rgba(129,140,248,.18) 45%,rgba(10,12,24,.92) 72%);box-shadow:inset 0 0 35px rgba(34,211,238,.13),0 0 60px rgba(99,102,241,.25)}
.ai-orb::before,.ai-orb::after{content:"";position:absolute;border:1px solid rgba(129,140,248,.3);border-radius:50%}
.ai-orb::before{inset:-30px;border-style:dashed;animation:ai-spin 15s linear infinite}
.ai-orb::after{inset:-63px;border-color:rgba(34,211,238,.15);animation:ai-spin 24s linear infinite reverse}
.ai-orb-core{display:grid;place-items:center;width:72px;height:72px;border-radius:24px;background:linear-gradient(145deg,#818cf8,#22d3ee 68%,#a78bfa);color:#05060d;font-family:'Sora',sans-serif;font-size:25px;font-weight:800;box-shadow:0 0 28px rgba(34,211,238,.34);transform:rotate(-8deg)}
.ai-orb-core span{transform:rotate(8deg)}
.ai-node{position:absolute;width:9px;height:9px;border:2px solid #0a0c18;border-radius:50%;background:#22d3ee;box-shadow:0 0 14px #22d3ee}
.ai-node.n1{top:31px;right:25px}.ai-node.n2{left:12px;bottom:57px;background:#a78bfa;box-shadow:0 0 14px #a78bfa}.ai-node.n3{right:3px;bottom:38px;background:#818cf8;box-shadow:0 0 14px #818cf8}
.ai-online{position:absolute;bottom:3px;display:flex;align-items:center;gap:7px;padding:6px 10px;border:1px solid rgba(52,211,153,.2);border-radius:999px;background:rgba(5,6,13,.74);color:#86efac;font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;backdrop-filter:blur(10px)}
.ai-online i{width:6px;height:6px;border-radius:50%;background:#34d399;box-shadow:0 0 9px #34d399}
.ai-notice{display:flex;align-items:flex-start;gap:12px;margin-bottom:18px;padding:13px 15px;border:1px solid rgba(251,191,36,.2);border-radius:13px;background:linear-gradient(90deg,rgba(251,191,36,.09),rgba(251,191,36,.025));color:#d8c99c;font-size:12.5px}
.ai-notice svg{width:18px;height:18px;flex:0 0 auto;color:#fbbf24;margin-top:1px}
.ai-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin-bottom:18px}
.ai-stat{position:relative;overflow:hidden;padding:18px;border:1px solid rgba(255,255,255,.085);border-radius:17px;background:linear-gradient(145deg,rgba(15,18,34,.96),rgba(8,10,20,.96));transition:transform .2s,border-color .2s}
.ai-stat:hover{transform:translateY(-2px);border-color:rgba(129,140,248,.28)}
.ai-stat::after{content:"";position:absolute;width:75px;height:75px;right:-28px;top:-30px;border-radius:50%;background:var(--stat-color,#818cf8);opacity:.1;filter:blur(4px)}
.ai-stat-head{display:flex;align-items:center;justify-content:space-between;color:#939db9;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
.ai-stat-icon{display:grid;place-items:center;width:30px;height:30px;border-radius:9px;background:color-mix(in srgb,var(--stat-color,#818cf8) 12%,transparent);color:var(--stat-color,#818cf8)}
.ai-stat-icon svg{width:15px;height:15px}
.ai-stat-value{display:flex;align-items:baseline;gap:4px;margin-top:13px;font-family:'Sora',sans-serif;color:#f5f7ff}
.ai-stat-value strong{font-size:29px;line-height:1}.ai-stat-value span{color:#8993ad;font-size:12px;font-family:'Inter',sans-serif}
.ai-stat-foot{margin-top:9px;color:#7f89a5;font-size:11.5px}
.ai-insights{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(300px,.9fr);gap:16px;margin-bottom:18px}
.ai-card{border:1px solid rgba(255,255,255,.085);border-radius:18px;background:linear-gradient(150deg,rgba(13,16,30,.98),rgba(7,9,18,.98));box-shadow:0 18px 45px rgba(0,0,0,.13)}
.ai-card-title{font-family:'Sora',sans-serif;font-size:15px;color:#eef1fb}.ai-card-sub{margin-top:3px;color:#7f89a5;font-size:11.5px}
.ai-distribution{display:grid;grid-template-columns:190px 1fr;align-items:center;gap:22px;padding:22px}
.ai-donut{position:relative;display:grid;place-items:center;width:148px;height:148px;margin:auto;border-radius:50%;background:<?= e($distributionStyle) ?>;box-shadow:0 0 34px rgba(129,140,248,.08)}
.ai-donut::after{content:"";position:absolute;inset:17px;border:1px solid rgba(255,255,255,.07);border-radius:50%;background:#0a0c18}
.ai-donut-center{position:relative;z-index:1;text-align:center}.ai-donut-center strong{display:block;font-family:'Sora',sans-serif;font-size:30px;line-height:1;color:#f5f7ff}.ai-donut-center span{color:#7f89a5;font-size:10px;text-transform:uppercase;letter-spacing:.09em}
.ai-legend{display:grid;gap:11px;margin-top:18px}
.ai-legend-row{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:9px;color:#aab2c9;font-size:12px}
.ai-legend-dot{width:8px;height:8px;border-radius:50%;background:var(--tone);box-shadow:0 0 10px color-mix(in srgb,var(--tone) 65%,transparent)}
.ai-legend-track{height:5px;overflow:hidden;border-radius:99px;background:rgba(255,255,255,.055)}
.ai-legend-track i{display:block;width:var(--bar);height:100%;border-radius:inherit;background:var(--tone)}
.ai-legend-row strong{min-width:18px;color:#e5e8f3;text-align:right}
.ai-snapshot{display:flex;flex-direction:column;padding:22px}
.ai-snapshot-top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
.ai-model-mark{display:grid;place-items:center;width:42px;height:42px;border-radius:13px;background:linear-gradient(140deg,rgba(129,140,248,.18),rgba(34,211,238,.1));border:1px solid rgba(129,140,248,.22);color:#a5b4fc}
.ai-model-mark svg{width:21px;height:21px}
.ai-snapshot-list{display:grid;gap:0;margin-top:18px;border-top:1px solid rgba(255,255,255,.07)}
.ai-snapshot-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:11px 0;border-bottom:1px solid rgba(255,255,255,.06);font-size:12px}.ai-snapshot-row span{color:#7f89a5}.ai-snapshot-row strong{color:#dce1f0;text-align:right;font-weight:600}
.ai-snapshot-row strong.source{color:#67e8f9}
.ai-predictions{overflow:hidden}
.ai-predictions-head{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:20px 22px;border-bottom:1px solid rgba(255,255,255,.07)}
.ai-filters{display:flex;flex-wrap:wrap;gap:6px}
.ai-filter{padding:7px 11px;border:1px solid rgba(255,255,255,.08);border-radius:9px;background:rgba(255,255,255,.025);color:#8f99b5;font-family:inherit;font-size:11.5px;font-weight:700;cursor:pointer;transition:.15s}
.ai-filter:hover,.ai-filter.active{border-color:rgba(129,140,248,.3);background:rgba(129,140,248,.13);color:#c7d2fe}
.ai-people-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:16px}
.ai-person{position:relative;display:grid;grid-template-columns:minmax(0,1fr) 88px;gap:18px;overflow:hidden;padding:17px;border:1px solid rgba(255,255,255,.07);border-radius:15px;background:rgba(255,255,255,.022);transition:transform .18s,border-color .18s,background .18s}
.ai-person:hover{transform:translateY(-2px);border-color:color-mix(in srgb,var(--tone) 35%,transparent);background:rgba(255,255,255,.035)}
.ai-person::before{content:"";position:absolute;left:0;top:18px;bottom:18px;width:2px;border-radius:0 3px 3px 0;background:var(--tone);box-shadow:0 0 12px var(--tone)}
.ai-person.is-hidden{display:none}
.ai-person-main{min-width:0;padding-left:5px}
.ai-person-id{display:flex;align-items:center;gap:10px;min-width:0}
.ai-avatar{display:grid;place-items:center;width:38px;height:38px;flex:0 0 auto;border:1px solid color-mix(in srgb,var(--tone) 28%,transparent);border-radius:12px;background:color-mix(in srgb,var(--tone) 12%,#0a0c18);color:var(--tone);font-family:'Sora',sans-serif;font-size:13px;font-weight:800}
.ai-person-name{min-width:0}.ai-person-name strong{display:block;overflow:hidden;color:#eef1fb;font-size:13.5px;text-overflow:ellipsis;white-space:nowrap}.ai-person-name span{display:block;margin-top:1px;color:#77819d;font-size:10.5px}
.ai-level{display:inline-flex;align-items:center;gap:6px;margin-top:13px;padding:4px 8px;border-radius:999px;background:color-mix(in srgb,var(--tone) 10%,transparent);color:var(--tone);font-size:9.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.ai-level i{width:5px;height:5px;border-radius:50%;background:var(--tone);box-shadow:0 0 7px var(--tone)}
.ai-signal{display:flex;align-items:flex-start;gap:7px;margin-top:10px;color:#9ba5bf;font-size:11.5px;line-height:1.45}.ai-signal svg{width:13px;height:13px;flex:0 0 auto;margin-top:2px;color:var(--tone)}
.ai-ring{position:relative;display:grid;place-items:center;width:78px;height:78px;align-self:center;border-radius:50%;background:conic-gradient(var(--tone) var(--angle),rgba(255,255,255,.055) 0);box-shadow:0 0 23px color-mix(in srgb,var(--tone) 10%,transparent)}
.ai-ring::before{content:"";position:absolute;inset:7px;border:1px solid rgba(255,255,255,.07);border-radius:50%;background:#0b0d18}
.ai-ring-value{position:relative;z-index:1;text-align:center}.ai-ring-value strong{display:block;font-family:'Sora',sans-serif;font-size:17px;line-height:1;color:#f4f6fd}.ai-ring-value span{display:block;margin-top:3px;color:#7e88a4;font-size:8px;letter-spacing:.07em;text-transform:uppercase}
.ai-empty{display:grid;place-items:center;min-height:280px;padding:40px;text-align:center}.ai-empty-icon{display:grid;place-items:center;width:64px;height:64px;margin-bottom:16px;border:1px solid rgba(129,140,248,.22);border-radius:20px;background:rgba(129,140,248,.09);color:#a5b4fc}.ai-empty-icon svg{width:28px;height:28px}.ai-empty h3{font-family:'Sora',sans-serif;font-size:17px;color:#eef1fb}.ai-empty p{max-width:520px;margin:7px auto 0;color:#8993ad;font-size:13px}.ai-empty code{display:inline-block;margin-top:15px;padding:8px 12px;border:1px solid rgba(34,211,238,.15);border-radius:9px;background:#05060d;color:#67e8f9}
.ai-footnote{display:flex;align-items:flex-start;gap:8px;margin-top:14px;color:#737d98;font-size:11px}.ai-footnote svg{width:14px;height:14px;flex:0 0 auto;margin-top:2px}
@keyframes ai-spin{to{transform:rotate(360deg)}}@keyframes ai-breathe{50%{transform:scale(1.12);opacity:.72}}
@media(max-width:1100px){.ai-hero{grid-template-columns:1fr 250px}.ai-stats{grid-template-columns:repeat(2,1fr)}.ai-people-grid{grid-template-columns:1fr}}
@media(max-width:800px){.ai-hero{grid-template-columns:1fr;padding:26px}.ai-orb-stage{min-height:210px}.ai-insights{grid-template-columns:1fr}.ai-distribution{grid-template-columns:160px 1fr}.ai-predictions-head{align-items:flex-start;flex-direction:column}}
@media(max-width:540px){.ai-hero h2{font-size:29px}.ai-stats{grid-template-columns:1fr}.ai-distribution{grid-template-columns:1fr}.ai-people-grid{padding:10px}.ai-person{grid-template-columns:minmax(0,1fr) 75px;padding:14px}.ai-ring{width:68px;height:68px}}
@media(prefers-reduced-motion:reduce){.ai-orb::before,.ai-orb::after,.ai-orb-glow{animation:none}.ai-stat,.ai-person{transition:none}}
</style>

<div class="ai-shell">
    <section class="ai-hero">
        <div class="ai-hero-copy">
            <div class="ai-kicker">ChronoX Intelligence Layer</div>
            <h2>See absence risk <span>before it affects the day.</span></h2>
            <p>Random Forest transforms attendance patterns into an early planning signal, helping managers focus attention where it matters most.</p>
            <div class="ai-meta">
                <span class="ai-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                    Random Forest v1
                </span>
                <span class="ai-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <?= e($predictionDate ? date('M j, Y', strtotime($predictionDate)) : 'Waiting for prediction') ?>
                </span>
                <span class="ai-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12a8 8 0 1 1-2.34-5.66M20 4v6h-6"/></svg>
                    <?= e($generatedAt ? date('M j · H:i', strtotime($generatedAt)) : 'Not generated yet') ?>
                </span>
            </div>
        </div>
        <div class="ai-orb-stage" aria-hidden="true">
            <div class="ai-orb-glow"></div>
            <div class="ai-orb"><div class="ai-orb-core"><span>AI</span></div></div>
            <i class="ai-node n1"></i><i class="ai-node n2"></i><i class="ai-node n3"></i>
            <div class="ai-online"><i></i><?= $totalPredictions ? 'Model online' : 'Awaiting data' ?></div>
        </div>
    </section>

    <?php if ($dataSource === 'synthetic'): ?>
        <div class="ai-notice">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.7 2.7 17a2 2 0 0 0 1.74 3h15.12a2 2 0 0 0 1.74-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/></svg>
            <div><strong>Synthetic training environment.</strong> These SQL-generated records validate the complete AI workflow; they are not forecasts about real employees.</div>
        </div>
    <?php elseif ($dataSource === 'demo'): ?>
        <div class="ai-notice"><div><strong>Legacy demonstration results.</strong> Run the current Python command to refresh the model.</div></div>
    <?php endif; ?>

    <?php if (!$tableReady || !$predictionDate || !$predictions): ?>
        <section class="ai-card ai-empty">
            <div>
                <div class="ai-empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M9.5 2A3.5 3.5 0 0 0 6 5.5V7a4 4 0 0 0-2 7.46V17a3 3 0 0 0 3 3h2.5M14.5 2A3.5 3.5 0 0 1 18 5.5V7a4 4 0 0 1 2 7.46V17a3 3 0 0 1-3 3h-2.5M12 2v20M8 10h4M12 15h4"/></svg></div>
                <h3><?= !$tableReady ? 'The intelligence layer is ready to initialize' : 'No prediction is available yet' ?></h3>
                <p>The attendance data stays untouched. Run the model once to prepare its features and save the next-workday predictions.</p>
                <code>python ai\predict_absences.py</code>
            </div>
        </section>
    <?php else: ?>
        <section class="ai-stats">
            <article class="ai-stat" style="--stat-color:#818cf8">
                <div class="ai-stat-head"><span>Predictions</span><span class="ai-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m7 16 4-5 4 3 5-7"/></svg></span></div>
                <div class="ai-stat-value"><strong><?= $totalPredictions ?></strong><span>employees</span></div>
                <div class="ai-stat-foot">Next workday coverage</div>
            </article>
            <article class="ai-stat" style="--stat-color:#22d3ee">
                <div class="ai-stat-head"><span>Average risk</span><span class="ai-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m12 12 4-4"/><path d="M7 16h10"/></svg></span></div>
                <div class="ai-stat-value"><strong><?= e(number_format($averageRisk, 1)) ?></strong><span>%</span></div>
                <div class="ai-stat-foot">Across all active employees</div>
            </article>
            <article class="ai-stat" style="--stat-color:#fb7185">
                <div class="ai-stat-head"><span>High priority</span><span class="ai-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.7 2.7 17a2 2 0 0 0 1.74 3h15.12a2 2 0 0 0 1.74-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/></svg></span></div>
                <div class="ai-stat-value"><strong><?= (int) $counts['high'] ?></strong><span>signals</span></div>
                <div class="ai-stat-foot"><?= $topPrediction ? e($topPrediction['first_name'].' '.$topPrediction['last_name']).' ranks first' : 'No urgent signal' ?></div>
            </article>
            <article class="ai-stat" style="--stat-color:#a78bfa">
                <div class="ai-stat-head"><span>Training signals</span><span class="ai-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V9M10 19V5M16 19v-7M22 19V3"/></svg></span></div>
                <div class="ai-stat-value"><strong><?= (int) $trainingRows ?></strong><span>rows</span></div>
                <div class="ai-stat-foot">Prepared in MySQL</div>
            </article>
        </section>

        <section class="ai-insights">
            <article class="ai-card ai-distribution">
                <div class="ai-donut"><div class="ai-donut-center"><strong><?= $totalPredictions ?></strong><span>profiles</span></div></div>
                <div>
                    <h3 class="ai-card-title">Risk distribution</h3>
                    <p class="ai-card-sub">A live map of the next workday forecast.</p>
                    <div class="ai-legend">
                        <?php foreach ([['high','High risk','#fb7185'],['medium','Medium risk','#fbbf24'],['low','Low risk','#34d399']] as [$key,$label,$tone]): ?>
                            <?php $bar = $totalPredictions ? ($counts[$key] / $totalPredictions) * 100 : 0; ?>
                            <div class="ai-legend-row" style="--tone:<?= $tone ?>;--bar:<?= e(number_format($bar, 2, '.', '')) ?>%"><i class="ai-legend-dot"></i><div class="ai-legend-track"><i></i></div><strong><?= (int) $counts[$key] ?></strong></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>

            <article class="ai-card ai-snapshot">
                <div class="ai-snapshot-top">
                    <div><h3 class="ai-card-title">Model snapshot</h3><p class="ai-card-sub">What powers this prediction cycle.</p></div>
                    <div class="ai-model-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M9.5 2A3.5 3.5 0 0 0 6 5.5V7a4 4 0 0 0-2 7.46V17a3 3 0 0 0 3 3h2.5M14.5 2A3.5 3.5 0 0 1 18 5.5V7a4 4 0 0 1 2 7.46V17a3 3 0 0 1-3 3h-2.5M12 2v20M8 10h4M12 15h4"/></svg></div>
                </div>
                <div class="ai-snapshot-list">
                    <div class="ai-snapshot-row"><span>Algorithm</span><strong>Random Forest</strong></div>
                    <div class="ai-snapshot-row"><span>Prediction target</span><strong><?= e(date('F j, Y', strtotime($predictionDate))) ?></strong></div>
                    <div class="ai-snapshot-row"><span>Data source</span><strong class="source"><?= e($sourceLabel) ?></strong></div>
                    <div class="ai-snapshot-row"><span>Latest generation</span><strong><?= e($generatedAt ? date('M j, Y · H:i', strtotime($generatedAt)) : '—') ?></strong></div>
                </div>
            </article>
        </section>

        <section class="ai-card ai-predictions">
            <div class="ai-predictions-head">
                <div><h3 class="ai-card-title">Employee intelligence feed</h3><p class="ai-card-sub"><span id="aiVisibleCount"><?= $totalPredictions ?></span> profiles ranked by predicted absence risk.</p></div>
                <div class="ai-filters" aria-label="Filter predictions">
                    <button class="ai-filter active" type="button" data-ai-filter="all">All <?= $totalPredictions ?></button>
                    <button class="ai-filter" type="button" data-ai-filter="high">High <?= (int) $counts['high'] ?></button>
                    <button class="ai-filter" type="button" data-ai-filter="medium">Medium <?= (int) $counts['medium'] ?></button>
                    <button class="ai-filter" type="button" data-ai-filter="low">Low <?= (int) $counts['low'] ?></button>
                </div>
            </div>
            <div class="ai-people-grid">
                <?php foreach ($predictions as $row): ?>
                    <?php
                        $risk = max(0, min(100, (float) $row['probability']));
                        $level = in_array($row['risk_level'], ['high','medium','low'], true) ? $row['risk_level'] : 'low';
                        $tone = $level === 'high' ? '#fb7185' : ($level === 'medium' ? '#fbbf24' : '#34d399');
                        $angle = number_format($risk * 3.6, 2, '.', '');
                    ?>
                    <article class="ai-person" data-ai-risk="<?= e($level) ?>" style="--tone:<?= $tone ?>;--angle:<?= $angle ?>deg">
                        <div class="ai-person-main">
                            <div class="ai-person-id">
                                <div class="ai-avatar"><?= e(strtoupper(substr($row['first_name'], 0, 1))) ?></div>
                                <div class="ai-person-name"><strong><?= e($row['first_name'].' '.$row['last_name']) ?></strong><span>UID <?= (int) $row['user_id'] ?> · <?= e($row['department']) ?></span></div>
                            </div>
                            <span class="ai-level"><i></i><?= e($level) ?> risk</span>
                            <div class="ai-signal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h4l3-8 4 16 3-8h4"/></svg><span><?= e($row['reason'] ?: 'Pattern estimated from recent attendance history') ?></span></div>
                        </div>
                        <div class="ai-ring"><div class="ai-ring-value"><strong><?= e(number_format($risk, 1)) ?>%</strong><span><?= (int) $row['history_days'] ?> days</span></div></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <p class="ai-footnote"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>This prediction supports planning only and must never be used alone for disciplinary or employment decisions.</p>
</div>

<script>
document.querySelectorAll('[data-ai-filter]').forEach(function(button){
    button.addEventListener('click',function(){
        var filter=button.getAttribute('data-ai-filter');
        var visible=0;
        document.querySelectorAll('[data-ai-filter]').forEach(function(item){item.classList.remove('active')});
        button.classList.add('active');
        document.querySelectorAll('[data-ai-risk]').forEach(function(card){
            var show=filter==='all'||card.getAttribute('data-ai-risk')===filter;
            card.classList.toggle('is-hidden',!show);
            if(show){visible++}
        });
        var count=document.getElementById('aiVisibleCount');
        if(count){count.textContent=visible}
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
