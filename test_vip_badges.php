<?php
require_once __DIR__ . '/includes/vip_badge.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIP Badge System - Demo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            padding: 40px 20px;
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 50px;
            font-size: 1.1rem;
        }

        .section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .section h2 {
            margin-bottom: 30px;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .badge-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .badge-item {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
        }

        .badge-label {
            margin-top: 15px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .size-demo {
            display: flex;
            align-items: center;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 40px;
            margin-bottom: 30px;
        }

        .size-item {
            text-align: center;
        }

        .inline-demo {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .demo-card {
            background: rgba(255, 255, 255, 0.03);
            padding: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .code-block {
            background: rgba(0, 0, 0, 0.3);
            padding: 20px;
            border-radius: 12px;
            overflow-x: auto;
            margin-top: 20px;
        }

        .code-block code {
            color: #10b981;
            font-family: 'Courier New', monospace;
        }

        @media (max-width: 768px) {
            .section {
                padding: 25px 20px;
            }

            h1 {
                font-size: 2rem;
            }

            .badge-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎖️ VIP Badge System</h1>
        <p class="subtitle">Beautiful animated badges for all VIP levels</p>

        <div class="section">
            <h2>🌟 All VIP Levels (Large Size)</h2>
            <div class="badge-grid">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="badge-item">
                        <?php echo renderVipBadge($i, 'large'); ?>
                        <div class="badge-label">VIP Level <?php echo $i; ?></div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="section">
            <h2>📏 Size Comparison (VIP2)</h2>
            <div class="size-demo">
                <div class="size-item">
                    <?php echo renderVipBadge(2, 'small'); ?>
                    <div class="badge-label">Small (60px)</div>
                </div>
                <div class="size-item">
                    <?php echo renderVipBadge(2, 'medium'); ?>
                    <div class="badge-label">Medium (90px)</div>
                </div>
                <div class="size-item">
                    <?php echo renderVipBadge(2, 'large'); ?>
                    <div class="badge-label">Large (120px)</div>
                </div>
            </div>

            <div class="code-block">
                <code>
&lt;?php echo renderVipBadge(2, 'small'); ?&gt;<br>
&lt;?php echo renderVipBadge(2, 'medium'); ?&gt;<br>
&lt;?php echo renderVipBadge(2, 'large'); ?&gt;
                </code>
            </div>
        </div>

        <div class="section">
            <h2>💼 Inline Badges</h2>
            <div class="inline-demo">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="demo-card">
                        <span style="font-size: 1.1rem; font-weight: 600;">User Level <?php echo $i; ?></span>
                        <?php echo renderVipBadgeInline($i); ?>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="code-block">
                <code>
&lt;?php echo renderVipBadgeInline($vipLevel); ?&gt;
                </code>
            </div>
        </div>

        <div class="section">
            <h2>🎨 Usage Examples</h2>

            <h3 style="margin-bottom: 20px; font-size: 1.3rem;">1. Profile Header</h3>
            <div style="background: rgba(255, 255, 255, 0.03); padding: 30px; border-radius: 12px; text-align: center; margin-bottom: 30px;">
                <?php echo renderVipBadge(4, 'large'); ?>
                <h2 style="margin: 15px 0 5px 0;">John Doe</h2>
                <p style="color: rgba(255, 255, 255, 0.7);">john.doe@example.com</p>
                <p style="color: rgba(255, 255, 255, 0.6); margin-top: 5px;">Member since January 2024</p>
            </div>

            <h3 style="margin-bottom: 20px; font-size: 1.3rem;">2. Task Card</h3>
            <div style="background: white; color: #1e293b; padding: 25px; border-radius: 12px; position: relative; margin-bottom: 30px;">
                <div style="position: absolute; top: 15px; right: 15px;">
                    <?php echo renderVipBadgeInline(3); ?>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <div style="width: 100%; height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                        🎯
                    </div>
                    <h3 style="margin: 20px 0 10px 0; font-size: 1.4rem;">PROD-8631B90F</h3>
                    <p style="color: #10b981; font-size: 1.2rem; font-weight: 700;">Profit: USD 2.25</p>
                    <button style="width: 100%; padding: 15px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; border-radius: 10px; font-weight: 700; margin-top: 15px; cursor: pointer;">
                        Click submit to complete this task
                    </button>
                </div>
            </div>

            <h3 style="margin-bottom: 20px; font-size: 1.3rem;">3. Leaderboard</h3>
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px; border-radius: 12px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(255, 255, 255, 0.1);">
                            <th style="padding: 15px; text-align: left;">Rank</th>
                            <th style="padding: 15px; text-align: left;">Badge</th>
                            <th style="padding: 15px; text-align: left;">User</th>
                            <th style="padding: 15px; text-align: right;">Earnings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $leaderboard = [
                            ['rank' => 1, 'vip' => 5, 'name' => 'Sarah Johnson', 'earnings' => 15420.50],
                            ['rank' => 2, 'vip' => 4, 'name' => 'Mike Chen', 'earnings' => 12330.25],
                            ['rank' => 3, 'vip' => 4, 'name' => 'Emma Davis', 'earnings' => 10890.75],
                            ['rank' => 4, 'vip' => 3, 'name' => 'Alex Wilson', 'earnings' => 8765.00],
                            ['rank' => 5, 'vip' => 2, 'name' => 'Lisa Brown', 'earnings' => 6543.50],
                        ];
                        foreach ($leaderboard as $entry):
                        ?>
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                                <td style="padding: 15px; font-weight: 700; font-size: 1.2rem;">#<?php echo $entry['rank']; ?></td>
                                <td style="padding: 15px;">
                                    <div style="display: inline-block;"><?php echo renderVipBadge($entry['vip'], 'small'); ?></div>
                                </td>
                                <td style="padding: 15px; font-weight: 600;"><?php echo $entry['name']; ?></td>
                                <td style="padding: 15px; text-align: right; color: #10b981; font-weight: 700;">$<?php echo number_format($entry['earnings'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <h2>📚 Quick Reference</h2>
            <div style="display: grid; gap: 20px;">
                <div style="background: rgba(255, 255, 255, 0.03); padding: 20px; border-radius: 12px;">
                    <h4 style="margin-bottom: 10px; color: #3b82f6;">Include the System</h4>
                    <div class="code-block" style="margin-top: 10px;">
                        <code>&lt;?php require_once __DIR__ . '/includes/vip_badge.php'; ?&gt;</code>
                    </div>
                </div>

                <div style="background: rgba(255, 255, 255, 0.03); padding: 20px; border-radius: 12px;">
                    <h4 style="margin-bottom: 10px; color: #10b981;">Circular Badge</h4>
                    <div class="code-block" style="margin-top: 10px;">
                        <code>&lt;?php echo renderVipBadge($vipLevel, 'large'); ?&gt;</code>
                    </div>
                </div>

                <div style="background: rgba(255, 255, 255, 0.03); padding: 20px; border-radius: 12px;">
                    <h4 style="margin-bottom: 10px; color: #f59e0b;">Inline Badge</h4>
                    <div class="code-block" style="margin-top: 10px;">
                        <code>&lt;?php echo renderVipBadgeInline($vipLevel); ?&gt;</code>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: center; padding: 40px 20px; color: rgba(255, 255, 255, 0.6);">
            <p style="font-size: 1.1rem;">VIP Badge System v1.0</p>
            <p style="margin-top: 10px;">Ready for production use</p>
        </div>
    </div>
</body>
</html>
