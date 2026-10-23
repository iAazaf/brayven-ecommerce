<?php
/**
 * BRAYVEN — dev utility: generates the seeded SVG product artwork.
 * Run from CLI:  php tools/generate-images.php
 * Artwork is deliberately minimal/editorial so the catalogue looks coherent
 * without external photography.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

const OUT_DIR = __DIR__ . '/../assets/images/products';
const W = 600;
const H = 750;

/** Minimal colour map (mirrors color_hex in includes/init.php). */
function tone(string $color): array
{
    $map = [
        'black' => ['#1E1E1E', '#0F0F0F'], 'charcoal' => ['#3E3E3C', '#2A2A28'],
        'white' => ['#F4F3EF', '#D9D7D0'], 'ivory' => ['#EFE9DC', '#D6CDB8'],
        'sand' => ['#D9C9AC', '#C2AE8B'], 'stone' => ['#BDB5A8', '#A49B8C'],
        'tan' => ['#C7A47C', '#AE8A61'], 'rust' => ['#A85B3D', '#8F4A30'],
        'olive' => ['#6F6F4F', '#5A5A3E'], 'navy' => ['#2E3A50', '#212B3C'],
        'indigo' => ['#354262', '#26314A'], 'slate blue' => ['#5E6D87', '#4A5870'],
        'light blue' => ['#A3B6C8', '#8BA2B6'], 'stone blue' => ['#8E9AA6', '#77838F'],
    ];
    return $map[strtolower(trim($color))] ?? ['#C9C2B6', '#B0A89A'];
}

function bg_for(string $color): string
{
    $map = [
        'black' => '#E3E0DA', 'charcoal' => '#ECE9E3', 'white' => '#E7E3DA', 'ivory' => '#E9E4D8',
        'sand' => '#EDE8DE', 'stone' => '#EAE6DF', 'tan' => '#EDE5D7', 'rust' => '#EDE4DA',
        'olive' => '#E9E8DE', 'navy' => '#E6E5E1', 'indigo' => '#E7E6E2', 'slate blue' => '#E9E8E5',
        'light blue' => '#EDEDEA', 'stone blue' => '#EBEAE7',
    ];
    return $map[strtolower(trim($color))] ?? '#EAE6DF';
}

/**
 * Garment silhouettes. Each returns inner SVG (fill/stroke inherit garment tones).
 * Canvas: 600x750, garment roughly centred on (300, ~390).
 */
function silhouette(string $kind, array $t): string
{
    [$fill, $line] = $t;
    $s = '<g fill="' . $fill . '" stroke="' . $line . '" stroke-width="3" stroke-linejoin="round">';

    switch ($kind) {
        case 'tee':
            $s .= '<path d="M225 245 L262 218 Q300 205 338 218 L375 245 L428 288 L398 362 L372 340 L372 545 Q300 558 228 545 L228 340 L202 362 L172 288 Z"/>
                   <path d="M262 218 Q300 248 338 218" fill="none"/>';
            break;
        case 'longtee':
            $s .= '<path d="M225 245 L262 218 Q300 205 338 218 L375 245 L428 288 L398 362 L372 340 L372 585 Q300 598 228 585 L228 340 L202 362 L172 288 Z"/>
                   <path d="M262 218 Q300 248 338 218" fill="none"/>';
            break;
        case 'fitted-top':
            $s .= '<path d="M240 250 L268 222 Q300 210 332 222 L360 250 L395 292 L372 352 L356 330 L356 520 Q300 532 244 520 L244 330 L228 352 L205 292 Z"/>
                   <path d="M268 222 Q300 246 332 222" fill="none"/>';
            break;
        case 'bodysuit':
            $s .= '<path d="M240 250 L268 222 Q300 210 332 222 L360 250 L395 292 L372 352 L356 330 L356 540 Q300 552 244 540 L244 330 L228 352 L205 292 Z"/>
                   <path d="M268 222 Q300 246 332 222" fill="none"/>';
            for ($i = 0; $i < 3; $i++) {
                $s .= '<circle cx="' . (284 + $i * 16) . '" cy="546" r="4" fill="' . $line . '" stroke="none"/>';
            }
            break;
        case 'shirt':
            $s .= '<path d="M225 250 L258 222 L300 250 L342 222 L375 250 L432 296 L402 378 L372 352 L372 568 Q300 580 228 568 L228 352 L198 378 L168 296 Z"/>
                   <path d="M258 222 L300 250 L342 222" fill="none"/>
                   <path d="M300 250 L300 560" fill="none" stroke-width="2"/>';
            for ($y = 285; $y <= 545; $y += 42) {
                $s .= '<circle cx="300" cy="' . $y . '" r="3.5" fill="' . $line . '" stroke="none"/>';
            }
            break;
        case 'overshirt':
            $s .= '<path d="M220 252 L256 220 L300 252 L344 220 L380 252 L440 300 L410 392 L376 364 L376 585 Q300 598 224 585 L224 364 L190 392 L160 300 Z"/>
                   <path d="M256 220 L300 252 L344 220" fill="none"/>
                   <path d="M300 252 L300 578" fill="none" stroke-width="2"/>
                   <rect x="240" y="430" width="52" height="64" fill="none" stroke-width="2"/>
                   <rect x="308" y="430" width="52" height="64" fill="none" stroke-width="2"/>';
            break;
        case 'hoodie':
            $s .= '<path d="M212 258 L258 226 Q300 214 342 226 L388 258 L442 306 L410 392 L378 362 L378 578 Q300 592 222 578 L222 362 L190 392 L158 306 Z"/>
                   <path d="M244 232 Q300 172 356 232 Q356 268 300 276 Q244 268 244 232 Z"/>
                   <path d="M284 272 L280 330 M316 272 L320 330" fill="none" stroke-width="2"/>
                   <path d="M258 470 Q300 500 342 470 L342 540 Q300 556 258 540 Z" fill="none" stroke-width="2"/>';
            break;
        case 'zip-hoodie':
            $s .= '<path d="M212 258 L258 226 Q300 214 342 226 L388 258 L442 306 L410 392 L378 362 L378 578 Q300 592 222 578 L222 362 L190 392 L158 306 Z"/>
                   <path d="M244 232 Q300 172 356 232 Q356 268 300 276 Q244 268 244 232 Z"/>
                   <path d="M300 276 L300 588" fill="none" stroke-width="2"/>
                   <rect x="240" y="430" width="52" height="64" fill="none" stroke-width="2"/>
                   <rect x="308" y="430" width="52" height="64" fill="none" stroke-width="2"/>';
            break;
        case 'sweatshirt':
            $s .= '<path d="M222 252 L260 222 Q300 210 340 222 L378 252 L432 300 L402 382 L374 354 L374 552 L226 552 L226 354 L198 382 L168 300 Z"/>
                   <path d="M260 222 Q300 250 340 222" fill="none"/>
                   <path d="M226 552 L374 552 L374 574 L226 574 Z" stroke-width="2"/>
                   <path d="M198 382 L226 354 M402 382 L374 354" fill="none"/>';
            break;
        case 'trousers':
            $s .= '<rect x="222" y="212" width="156" height="30"/>
                   <path d="M222 242 L378 242 L396 612 L316 612 L300 356 L284 612 L204 612 Z"/>
                   <path d="M300 242 L300 340" fill="none" stroke-width="2"/>';
            break;
        case 'pleated-trousers':
            $s .= '<rect x="222" y="212" width="156" height="30"/>
                   <path d="M222 242 L378 242 L392 612 L318 612 L302 360 L292 612 L208 612 Z"/>
                   <path d="M262 242 L258 340 M338 242 L342 340" fill="none" stroke-width="2"/>';
            break;
        case 'wide-leg':
            $s .= '<rect x="214" y="216" width="172" height="26"/>
                   <path d="M214 242 L386 242 L404 590 L320 590 L300 340 L280 590 L196 590 Z"/>
                   <path d="M300 242 L300 330" fill="none" stroke-width="2"/>';
            break;
        case 'jeans':
            $s .= '<rect x="222" y="212" width="156" height="32"/>
                   <path d="M222 244 L378 244 L396 612 L316 612 L300 356 L284 612 L204 612 Z"/>
                   <path d="M300 244 L300 330" fill="none" stroke-width="2"/>
                   <path d="M236 250 Q262 286 284 252 M364 250 Q338 286 316 252" fill="none" stroke-width="2"/>';
            break;
        case 'bomber':
            $s .= '<path d="M220 256 L262 224 Q300 212 338 224 L380 256 L436 302 L406 386 L376 358 L376 540 L224 540 L224 358 L194 386 L164 302 Z"/>
                   <path d="M262 224 Q300 252 338 224" fill="none"/>
                   <path d="M300 252 L300 546" fill="none" stroke-width="2"/>
                   <path d="M224 540 L376 540 L376 566 L224 566 Z" stroke-width="2"/>';
            break;
        case 'field':
            $s .= '<path d="M220 254 L258 222 L300 254 L342 222 L380 254 L438 300 L408 390 L376 362 L376 592 Q300 604 224 592 L224 362 L192 390 L162 300 Z"/>
                   <path d="M258 222 L300 254 L342 222" fill="none"/>
                   <path d="M300 254 L300 598" fill="none" stroke-width="2"/>
                   <rect x="240" y="396" width="52" height="58" fill="none" stroke-width="2"/>
                   <rect x="308" y="396" width="52" height="58" fill="none" stroke-width="2"/>
                   <rect x="240" y="486" width="52" height="58" fill="none" stroke-width="2"/>
                   <rect x="308" y="486" width="52" height="58" fill="none" stroke-width="2"/>';
            break;
        case 'quilted':
            $s .= '<path d="M222 256 L262 226 Q300 214 338 226 L378 256 L434 302 L404 386 L376 358 L376 556 Q300 570 224 556 L224 358 L196 386 L166 302 Z"/>
                   <path d="M262 226 Q300 254 338 226" fill="none"/>
                   <path d="M300 254 L300 560" fill="none" stroke-width="2"/>
                   <path d="M196 320 L404 468 M166 356 L388 512 M224 404 L412 420" fill="none" stroke-width="1.5" opacity="0.5"/>';
            break;
        case 'overcoat':
            $s .= '<path d="M218 268 L258 230 L300 262 L342 230 L382 268 L436 318 L406 404 L376 376 L376 640 Q300 654 224 640 L224 376 L194 404 L164 318 Z"/>
                   <path d="M258 230 L278 320 L300 262 M342 230 L322 320 L300 262" fill="none" stroke-width="2"/>
                   <path d="M300 320 L300 648" fill="none" stroke-width="2"/>';
            for ($y = 350; $y <= 560; $y += 60) {
                $s .= '<circle cx="300" cy="' . $y . '" r="4" fill="' . $line . '" stroke="none"/>';
            }
            break;
        case 'blazer':
            $s .= '<path d="M224 258 L262 228 L300 260 L338 228 L376 258 L428 306 L400 392 L372 364 L384 586 Q300 600 216 586 L228 364 L200 392 L172 306 Z"/>
                   <path d="M262 228 L292 330 L300 260 M338 228 L308 330 L300 260" fill="none" stroke-width="2"/>
                   <path d="M300 330 L300 592" fill="none" stroke-width="2"/>';
            break;
        case 'dress':
            $s .= '<path d="M262 196 L268 240 M338 196 L332 240" fill="none" stroke-width="4"/>
                   <path d="M268 236 L332 236 L390 606 Q300 632 210 606 Z"/>
                   <path d="M268 236 L300 282 L332 236" fill="none" stroke-width="2"/>';
            break;
        case 'card-holder':
            $s .= '<rect x="215" y="270" width="170" height="240" rx="10"/>
                   <path d="M240 330 L360 330 M240 380 L360 380 M240 430 L330 430" fill="none" stroke-width="2"/>';
            break;
        case 'weekender':
            $s .= '<rect x="150" y="330" width="300" height="200" rx="14"/>
                   <path d="M230 330 Q300 240 370 330" fill="none" stroke-width="4"/>
                   <path d="M150 400 L450 400" fill="none" stroke-width="2"/>
                   <path d="M285 330 L285 530 M315 330 L315 530" fill="none" stroke-width="2"/>';
            break;
        case 'beanie':
            $s .= '<path d="M215 420 Q215 280 300 280 Q385 280 385 420 Z"/>
                   <rect x="207" y="416" width="186" height="52" rx="8"/>
                   <path d="M240 300 L240 410 M270 288 L270 402 M300 282 L300 396 M330 288 L330 402 M360 300 L360 410" fill="none" stroke-width="1.5" opacity="0.5"/>';
            break;
        case 'scarf':
            $s .= '<path d="M250 200 Q300 185 350 200 L360 560 L340 560 L350 620 L300 630 L250 620 L260 560 L240 560 Z"/>
                   <path d="M262 220 L338 220 M258 270 L342 270 M256 320 L344 320" fill="none" stroke-width="1.5" opacity="0.5"/>';
            break;
        case 'belt':
            $s .= '<rect x="120" y="330" width="360" height="60" rx="6"/>
                   <rect x="392" y="318" width="44" height="84" rx="8" fill="none" stroke-width="4"/>
                   <path d="M120 360 L392 360" fill="none" stroke-width="2"/>';
            break;
        default:
            $s .= '<rect x="220" y="280" width="160" height="240" rx="8"/>';
    }
    return $s . '</g>';
}

function artwork(string $slug, string $color, string $kind, bool $detail = false): string
{
    $t = tone($color);
    $bg = bg_for($color);
    $inner = silhouette($kind, $t);
    if ($detail) {
        // Cropped "detail shot": zoom into the upper garment area.
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="60 80 340 425">'
            . '<rect x="60" y="80" width="340" height="425" fill="' . $bg . '"/>'
            . $inner
            . '</svg>';
    } else {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . W . ' ' . H . '">'
            . '<rect width="' . W . '" height="' . H . '" fill="' . $bg . '"/>'
            . '<ellipse cx="300" cy="648" rx="170" ry="16" fill="' . $t[1] . '" opacity="0.12"/>'
            . $inner
            . '<text x="300" y="706" text-anchor="middle" font-family="Georgia, serif" font-size="17" letter-spacing="7" fill="#171717" opacity="0.55">BRAYVEN</text>'
            . '</svg>';
    }
    return $svg;
}

/** Slug → silhouette kind + primary colour. */
function kind_for(string $slug): array
{
    $map = [
        'essential-crew-tee'            => ['tee', 'Sand'],
        'heavyweight-oversized-tee'     => ['tee', 'Charcoal'],
        'pima-cotton-pocket-tee'        => ['tee', 'White'],
        'slub-jersey-longline-tee'      => ['longtee', 'Stone'],
        'oxford-button-down-shirt'      => ['shirt', 'Ivory'],
        'linen-camp-collar-shirt'       => ['shirt', 'Sand'],
        'flannel-overshirt'             => ['overshirt', 'Rust'],
        'poplin-stretch-shirt'          => ['shirt', 'Navy'],
        'classic-pullover-hoodie'       => ['hoodie', 'Charcoal'],
        'heavyweight-zip-hoodie'        => ['zip-hoodie', 'Black'],
        'cropped-boxy-hoodie'           => ['hoodie', 'Stone'],
        'crewneck-sweatshirt'           => ['sweatshirt', 'Sand'],
        'fleece-lined-sweatshirt'       => ['sweatshirt', 'Olive'],
        'cropped-crew-sweatshirt'       => ['sweatshirt', 'Ivory'],
        'tailored-wool-trousers'        => ['trousers', 'Charcoal'],
        'pleated-linen-trousers'        => ['pleated-trousers', 'Sand'],
        'drawstring-cotton-trousers'    => ['trousers', 'Stone'],
        'wide-leg-cropped-trousers'     => ['wide-leg', 'Black'],
        'straight-leg-selvedge-jeans'   => ['jeans', 'Indigo'],
        'relaxed-tapered-jeans'         => ['jeans', 'Stone Blue'],
        'high-rise-mom-jeans'           => ['jeans', 'Light Blue'],
        'minimal-bomber-jacket'         => ['bomber', 'Black'],
        'field-jacket'                  => ['field', 'Olive'],
        'quilted-liner-jacket'          => ['quilted', 'Sand'],
        'wool-overcoat'                 => ['overcoat', 'Charcoal'],
        'ribbed-knit-top'               => ['fitted-top', 'Ivory'],
        'midi-slip-dress'               => ['dress', 'Sand'],
        'boxy-linen-blazer'             => ['blazer', 'Stone'],
        'high-neck-ribbed-bodysuit'     => ['bodysuit', 'Black'],
        'leather-card-holder'           => ['card-holder', 'Tan'],
        'canvas-weekender-bag'          => ['weekender', 'Olive'],
        'ribbed-cotton-beanie'          => ['beanie', 'Charcoal'],
        'silk-blend-scarf'              => ['scarf', 'Rust'],
        'leather-belt'                  => ['belt', 'Black'],
    ];
    return $map[$slug] ?? ['tee', 'Stone'];
}

if (!is_dir(OUT_DIR)) {
    mkdir(OUT_DIR, 0775, true);
}

$pdo = db();
$products = $pdo->query('SELECT slug, colors, is_featured FROM products')->fetchAll();
$count = 0;
foreach ($products as $p) {
    [$kind, $color] = kind_for($p['slug']);
    file_put_contents(OUT_DIR . '/' . $p['slug'] . '-1.svg', artwork($p['slug'], $color, $kind));
    $count++;
    if ((int)$p['is_featured'] === 1) {
        file_put_contents(OUT_DIR . '/' . $p['slug'] . '-2.svg', artwork($p['slug'], $color, $kind, true));
        $count++;
    }
}

file_put_contents(OUT_DIR . '/placeholder.svg', artwork('placeholder', 'Stone', 'tee'));

/* Editorial extras used by the homepage collage / category banners. */
$extras = [
    'editorial-hero-a.svg'  => artwork('hero-a', 'Sand', 'overcoat'),
    'editorial-hero-b.svg'  => artwork('hero-b', 'Ivory', 'shirt'),
    'editorial-hero-c.svg'  => artwork('hero-c', 'Olive', 'hoodie'),
    'banner-men.svg'        => artwork('banner-men', 'Charcoal', 'overshirt'),
    'banner-women.svg'      => artwork('banner-women', 'Tan', 'dress'),
    'campaign.svg'          => artwork('campaign', 'Indigo', 'bomber'),
    'story-01.svg'          => artwork('story-01', 'Sand', 'sweatshirt'),
    'story-02.svg'          => artwork('story-02', 'Stone', 'trousers'),
    'story-03.svg'          => artwork('story-03', 'Rust', 'scarf'),
];
foreach ($extras as $name => $svg) {
    file_put_contents(OUT_DIR . '/' . $name, $svg);
    $count++;
}

/* Instagram-style grid: reuse a fixed set of catalogue images at build time. */
$insta = ['essential-crew-tee-1.svg', 'midi-slip-dress-1.svg', 'straight-leg-selvedge-jeans-1.svg',
          'classic-pullover-hoodie-1.svg', 'canvas-weekender-bag-1.svg', 'linen-camp-collar-shirt-1.svg'];
foreach ($insta as $src) {
    copy(OUT_DIR . '/' . $src, OUT_DIR . '/insta-' . $src);
    $count++;
}

echo "Generated {$count} SVG files in assets/images/products\n";
