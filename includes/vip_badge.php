<?php
function getVipBadge($vipLevel) {
    $badges = [
        1 => [
            'symbol' => '🥉',
            'name' => 'VIP1',
            'color' => '#cd7f32',
            'gradient' => 'linear-gradient(135deg, #cd7f32 0%, #b8860b 100%)'
        ],
        2 => [
            'symbol' => '🥈',
            'name' => 'VIP2',
            'color' => '#c0c0c0',
            'gradient' => 'linear-gradient(135deg, #e8e8e8 0%, #c0c0c0 100%)'
        ],
        3 => [
            'symbol' => '🥇',
            'name' => 'VIP3',
            'color' => '#ffd700',
            'gradient' => 'linear-gradient(135deg, #ffd700 0%, #ffed4e 100%)'
        ],
        4 => [
            'symbol' => '💎',
            'name' => 'VIP4',
            'color' => '#00bfff',
            'gradient' => 'linear-gradient(135deg, #00bfff 0%, #1e90ff 100%)'
        ],
        5 => [
            'symbol' => '👑',
            'name' => 'VIP5',
            'color' => '#9370db',
            'gradient' => 'linear-gradient(135deg, #9370db 0%, #8a2be2 100%)'
        ]
    ];

    if (!isset($badges[$vipLevel])) {
        $vipLevel = 1;
    }

    return $badges[$vipLevel];
}

function renderVipBadge($vipLevel, $size = 'medium') {
    $badge = getVipBadge($vipLevel);

    $sizes = [
        'small' => ['width' => '60px', 'height' => '60px', 'fontSize' => '0.7rem', 'symbolSize' => '1.2rem'],
        'medium' => ['width' => '90px', 'height' => '90px', 'fontSize' => '0.9rem', 'symbolSize' => '1.8rem'],
        'large' => ['width' => '120px', 'height' => '120px', 'fontSize' => '1.1rem', 'symbolSize' => '2.4rem']
    ];

    $s = $sizes[$size] ?? $sizes['medium'];

    ob_start();
    ?>
    <div class="vip-badge-container" style="
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
        width: <?php echo $s['width']; ?>;
        height: <?php echo $s['height']; ?>;
    ">
        <div class="vip-badge" style="
            width: 100%;
            height: 100%;
            background: <?php echo $badge['gradient']; ?>;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3), inset 0 2px 10px rgba(255, 255, 255, 0.3);
            border: 3px solid rgba(255, 255, 255, 0.8);
            position: relative;
            overflow: hidden;
            animation: vip-pulse 3s ease-in-out infinite;
        ">
            <div class="vip-shine" style="
                position: absolute;
                top: -50%;
                left: -50%;
                width: 200%;
                height: 200%;
                background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
                animation: vip-shine 3s ease-in-out infinite;
            "></div>

            <div class="vip-laurel" style="
                position: absolute;
                top: 8%;
                left: 50%;
                transform: translateX(-50%);
                width: 80%;
                height: 20%;
                background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 20\"><text x=\"10\" y=\"15\" font-size=\"12\" fill=\"%23FFD700\">🌿</text><text x=\"80\" y=\"15\" font-size=\"12\" fill=\"%23FFD700\">🌿</text></svg>');
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center;
                opacity: 0.8;
            "></div>

            <div class="vip-symbol" style="
                font-size: <?php echo $s['symbolSize']; ?>;
                margin-bottom: 2px;
                filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
                z-index: 2;
            "><?php echo $badge['symbol']; ?></div>

            <div class="vip-text" style="
                font-size: <?php echo $s['fontSize']; ?>;
                font-weight: 800;
                color: white;
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
                letter-spacing: 1px;
                z-index: 2;
            "><?php echo $badge['name']; ?></div>

            <div class="vip-laurel-bottom" style="
                position: absolute;
                bottom: 8%;
                left: 50%;
                transform: translateX(-50%);
                width: 80%;
                height: 20%;
                background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 20\"><text x=\"10\" y=\"15\" font-size=\"12\" fill=\"%23FFD700\">🌿</text><text x=\"80\" y=\"15\" font-size=\"12\" fill=\"%23FFD700\">🌿</text></svg>');
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center;
                opacity: 0.8;
            "></div>
        </div>
    </div>

    <style>
        @keyframes vip-pulse {
            0%, 100% {
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3),
                            inset 0 2px 10px rgba(255, 255, 255, 0.3),
                            0 0 20px <?php echo $badge['color']; ?>40;
            }
            50% {
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4),
                            inset 0 2px 15px rgba(255, 255, 255, 0.4),
                            0 0 30px <?php echo $badge['color']; ?>60;
            }
        }

        @keyframes vip-shine {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
            }
            100% {
                transform: translateX(100%) translateY(100%) rotate(45deg);
            }
        }
    </style>
    <?php
    return ob_get_clean();
}

function renderVipBadgeInline($vipLevel) {
    $badge = getVipBadge($vipLevel);

    ob_start();
    ?>
    <span class="vip-badge-inline" style="
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        background: <?php echo $badge['gradient']; ?>;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 700;
        color: white;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.5);
    ">
        <span style="font-size: 1.1rem;"><?php echo $badge['symbol']; ?></span>
        <span><?php echo $badge['name']; ?></span>
    </span>
    <?php
    return ob_get_clean();
}
?>
