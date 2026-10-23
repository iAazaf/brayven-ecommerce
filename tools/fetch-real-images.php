<?php
/**
 * BRAYVEN — dev utility: downloads real Unsplash photography for the catalogue.
 * Reads candidates harvested by the browser session from .candidates.jsonl
 * (one JSON line per query: {"q": "...", "v": [{id,d,u},...]}).
 * Run from CLI:  php tools/fetch-real-images.php
 */

declare(strict_types=1);

const CANDIDATES = __DIR__ . '/../assets/images/products/.candidates.jsonl';
const OUT_DIR    = __DIR__ . '/../assets/images/products';
const CREDITS    = __DIR__ . '/../assets/images/products/credits.csv';

/* filename => [search query, preferred candidate index, width, height] */
const MAP = [
    // --- catalogue: tops ---
    'essential-crew-tee-1' => ['white tshirt', 6, 1000, 1250],
    'essential-crew-tee-2'        => ['white tshirt', 1, 1000, 1250],
    'pima-cotton-pocket-tee-1'    => ['white tshirt', 2, 1000, 1250],
    'pima-cotton-pocket-tee-2' => ['white tshirt', 7, 1000, 1250],
    'heavyweight-oversized-tee-1' => ['black tshirt', 5, 1000, 1250],
    'slub-jersey-longline-tee-1'  => ['beige tshirt', 0, 1000, 1250],
    'oxford-button-down-shirt-1'  => ['blue oxford shirt', 0, 1000, 1250],
    'oxford-button-down-shirt-2'  => ['blue oxford shirt', 1, 1000, 1250],
    'linen-camp-collar-shirt-1' => ['linen shirt men', 5, 1000, 1250],
    'flannel-overshirt-1' => ['overshirt men', 3, 1000, 1250],
    'poplin-stretch-shirt-1'      => ['white dress shirt', 0, 1000, 1250],
    // --- catalogue: sweats ---
    'classic-pullover-hoodie-1' => ['hoodie', 2, 1000, 1250],
    'classic-pullover-hoodie-2' => ['grey hoodie model', 6, 1000, 1250],
    'heavyweight-zip-hoodie-1' => ['black hoodie', 9, 1000, 1250],
    'cropped-boxy-hoodie-1' => ['cropped hoodie', 6, 1000, 1250],
    'crewneck-sweatshirt-1'       => ['grey sweatshirt', 0, 1000, 1250],
    'crewneck-sweatshirt-2'       => ['grey sweatshirt', 1, 1000, 1250],
    'fleece-lined-sweatshirt-1' => ['olive sweatshirt', 2, 1000, 1250],
    'cropped-crew-sweatshirt-1'   => ['cropped hoodie', 1, 1000, 1250],
    // --- catalogue: trousers & denim ---
    'tailored-wool-trousers-1' => ['wool trousers', 3, 1000, 1250],
    'tailored-wool-trousers-2'    => ['wool trousers', 1, 1000, 1250],
    'pleated-linen-trousers-1'    => ['beige trousers women', 0, 1000, 1250],
    'drawstring-cotton-trousers-1'=> ['navy trousers men', 0, 1000, 1250],
    'wide-leg-cropped-trousers-1' => ['beige trousers women', 3, 1000, 1250],
    'straight-leg-selvedge-jeans-1' => ['raw denim jeans', 5, 1000, 1250],
    'straight-leg-selvedge-jeans-2' => ['raw denim jeans', 1, 1000, 1250],
    'relaxed-tapered-jeans-1'    => ['blue jeans men', 0, 1000, 1250],
    'high-rise-mom-jeans-1' => ['mom jeans women', 7, 1000, 1250],
    // --- catalogue: outerwear ---
    'minimal-bomber-jacket-1' => ['bomber jacket fashion model', 6, 1000, 1250],
    'minimal-bomber-jacket-2'     => ['bomber jacket', 1, 1000, 1250],
    'field-jacket-1' => ['military green jacket', 0, 1000, 1250],
    'quilted-liner-jacket-1' => ['quilted jacket', 6, 1000, 1250],
    'wool-overcoat-1' => ['wool overcoat men', 1, 1000, 1250],
    'wool-overcoat-2' => ['wool overcoat men', 7, 1000, 1250],
    // --- catalogue: womenswear ---
    'ribbed-knit-top-1' => ['ribbed knit top', 1, 1000, 1250],
    'midi-slip-dress-1' => ['slip dress', 3, 1000, 1250],
    'midi-slip-dress-2' => ['slip dress', 5, 1000, 1250],
    'boxy-linen-blazer-1' => ['linen blazer women', 3, 1000, 1250],
    'high-neck-ribbed-bodysuit-1' => ['black bodysuit', 7, 1000, 1250],
    // --- catalogue: accessories ---
    'leather-card-holder-1' => ['leather card holder', 2, 1000, 1250],
    'canvas-weekender-bag-1' => ['weekender bag', 8, 1000, 1250],
    'canvas-weekender-bag-2' => ['weekender bag', 4, 1000, 1250],
    'ribbed-cotton-beanie-1' => ['knit beanie', 3, 1000, 1250],
    'silk-blend-scarf-1'          => ['orange scarf', 0, 1000, 1250],
    'leather-belt-1' => ['leather belt men', 2, 1000, 1250],
    // --- editorial ---
    'editorial-hero-a' => ['overcoat street style', 6, 1000, 1250],
    'editorial-hero-b'            => ['oxford shirt style', 0, 1000, 1250],
    'editorial-hero-c' => ['hoodie street style', 2, 1000, 1250],
    'banner-men' => ['menswear fashion', 2, 1400, 800],
    'banner-women' => ['womenswear fashion', 2, 1400, 800],
    'campaign'                    => ['autumn fashion layering', 0, 1000, 1250],
    'story-01' => ['tailor atelier', 2, 1200, 800],
    'story-02'                    => ['tailoring suit fitting', 0, 900, 1125],
    'story-03' => ['fabric texture closeup', 1, 900, 1125],
    // --- instagram tiles ---
    'insta-essential-crew-tee-1'        => ['white tshirt', 4, 800, 800],
    'insta-midi-slip-dress-1' => ['slip dress', 7, 800, 800],
    'insta-straight-leg-selvedge-jeans-1' => ['raw denim jeans', 2, 800, 800],
    'insta-classic-pullover-hoodie-1' => ['hoodie street style', 3, 800, 800],
    'insta-canvas-weekender-bag-1' => ['weekender bag', 3, 800, 800],
    'insta-linen-camp-collar-shirt-1'   => ['linen shirt men', 1, 800, 800],
];

/* ---- load candidates ---- */
$pool = [];
foreach (file(CANDIDATES, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $row = json_decode($line, true);
    if ($row && isset($row['q'], $row['v']) && is_array($row['v'])) {
        $pool[$row['q']] = $row['v'];
    }
}
if (!$pool) { fwrite(STDERR, "No candidates found.\n"); exit(1); }

/** Download one URL, return bytes or null. */
function fetch(string $url): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126.0 Safari/537.36',
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code === 200 && is_string($body) && strlen($body) >= 20000
            && substr($body, 0, 2) === "\xFF\xD8") ? $body : null;
}

$credits = [];
$ok = $fail = 0;

foreach (MAP as $file => [$query, $want, $w, $h]) {
    $cands = $pool[$query] ?? [];
    $done = false;
    // try preferred index, then neighbours, so one bad pick doesn't stall a file
    foreach (array_slice($cands, $want, 4) ?: $cands as $c) {
        if (empty($c['u'])) continue;
        $url = $c['u'] . "?fm=jpg&q=80&w={$w}&h={$h}&fit=crop";
        $body = fetch($url);
        if ($body === null) continue;
        file_put_contents(OUT_DIR . '/' . $file . '.jpg', $body);
        $credits[] = [$file . '.jpg', 'https://unsplash.com/photos/' . $c['id'], $query];
        printf("OK   %-38s %s\n", $file, mb_substr($c['d'] ?? '', 0, 60));
        $done = true; $ok++;
        break;
    }
    if (!$done) { printf("FAIL %-38s (query: %s)\n", $file, $query); $fail++; }
}

$fh = fopen(CREDITS, 'w');
foreach ($credits as $c) { fputcsv($fh, $c); }
fclose($fh);
printf("\nDone: %d ok, %d failed. Credits: %s\n", $ok, $fail, CREDITS);
