<?php
$line1 = [
    'Frontendentwickler',
    'Videografen',
    'Fotografen',
    'Grafikdesigner',
    'SEO-Experten',
    'Texter'
];

$line2 = [
    'Marketing-Profis',
    'Softwareprogrammierer',
    'Übersetzer',
    'Eventmanager',
    'DJs',
    'Webentwickler',
    'Werbemittelgestaltung'
];

function renderLine($words)
{
    $output = '';
    foreach ($words as $index => $word) {
        $isPrimary = $index % 2 === 1 ? 'text-primary' : '';
        $output .= '<span class="' . $isPrimary . '" data-animation="animate__fadeIn">' . $word . '</span>. ';
    }
    return $output;
}

?>

<div class="container pb-0">
    <span data-animation="animate__fadeIn" class="we-are">Wir sind</span>
</div>


<div class="ticker-wrapper">
    <div class="ticker ticker-left">
        <?php for ($i = 0; $i < 4; $i++) : ?>
            <div class="ticker-content">
                <p>
                    <?php echo renderLine($line1); ?>
                </p>
            </div>
        <?php endfor; ?>
    </div>

    <div class="ticker ticker-right">
        <?php for ($i = 0; $i < 4; $i++) : ?>
            <div class="ticker-content">
                <p>
                    <?php echo renderLine($line2); ?>
                </p>
            </div>
        <?php endfor; ?>
    </div>
</div>

<style>

    .we-are {
        font-size: 38px;
        font-weight: 900;
        font-style: italic;
    }

    .ticker-wrapper {
        width: 100%;
        transform: rotate(-3.5deg);
        overflow: hidden;
        font-size: 120px;
        font-weight: 800;
        text-transform: uppercase;
        line-height: 1;
    }

    .ticker {
        display: flex;
        position: relative;
        width: fit-content;
    }

    .ticker-content {
        flex-shrink: 0;
        padding: 0px 40px;
        white-space: nowrap;
    }

    .ticker-content p {
        line-height: 1;
    }

    .ticker-left {
        animation: moveLeft 250s linear infinite;
    }

    .ticker-right {
        animation: moveRight 500s linear infinite;
    }

    @keyframes moveLeft {
        from {
            transform: translateX(0);
        }
        to {
            transform: translateX(-50%);
        }
    }

    @keyframes moveRight {
        from {
            transform: translateX(-50%);
        }
        to {
            transform: translateX(0);
        }
    }

    .ticker:hover {
        animation-play-state: paused;
    }

    @media (prefers-reduced-motion: reduce) {
        .ticker {
            animation: none;
        }
    }
</style>