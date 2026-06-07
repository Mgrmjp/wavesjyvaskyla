<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('DATA_DIR', ROOT . '/data');
define('INCLUDES_DIR', ROOT . '/includes');
define('TEMPLATES_DIR', ROOT . '/templates');
define('ADMIN_DIR', ROOT . '/admin');

require_once INCLUDES_DIR . '/bootstrap.php';
require_once INCLUDES_DIR . '/functions.php';
require_once INCLUDES_DIR . '/RevisionLog.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line." . PHP_EOL);
    exit(1);
}

const MENU_AI_BATCH_ENDPOINT = '/v1/images/generations';
const MENU_AI_BATCH_DIR = DATA_DIR . '/ai-menu-image-batches';

function menuAiUsage(): string
{
    return <<<TXT
Usage:
  php scripts/menu-ai-images.php plan [options]
  OPENAI_API_KEY=... php scripts/menu-ai-images.php submit [options]
  OPENAI_API_KEY=... php scripts/menu-ai-images.php status <batch_id>
  OPENAI_API_KEY=... php scripts/menu-ai-images.php collect <batch_id> [--apply] [--overwrite]

Options:
  --store=menu|lunch       Target store. Default: menu
  --variants=N              Images per selected item. Default: 1
  --quality=low|medium|high Image quality. Default: medium
  --size=WxH                Output size. Default: 1536x1024
  --all                     Include items that already have images
  --include-hidden          Include hidden menu items
  --include-zero-price      Include zero-price notes/dips
  --day=mon|tue|wed|thu|fri Limit lunch items to one weekday
  --item=ID_OR_NAME         Limit to one item id or name match
  --limit=N                 Limit selected items
  --apply                   On collect, assign first generated image per item
  --overwrite               With --apply, replace existing item images

Examples:
  php scripts/menu-ai-images.php plan --variants=3
  OPENAI_API_KEY=sk-... php scripts/menu-ai-images.php submit --variants=3
  OPENAI_API_KEY=sk-... php scripts/menu-ai-images.php status batch_abc123
  OPENAI_API_KEY=sk-... php scripts/menu-ai-images.php collect batch_abc123 --apply
TXT;
}

function menuAiParseOptions(array $args): array
{
    $options = ['_' => []];

    foreach ($args as $arg) {
        if (!str_starts_with($arg, '--')) {
            $options['_'][] = $arg;
            continue;
        }

        $option = substr($arg, 2);
        if ($option === '') {
            continue;
        }

        if (str_contains($option, '=')) {
            [$key, $value] = explode('=', $option, 2);
            $options[$key] = $value;
            continue;
        }

        $options[$option] = true;
    }

    return $options;
}

function menuAiOptionInt(array $options, string $key, int $default, int $min, int $max): int
{
    $raw = $options[$key] ?? $default;
    $value = filter_var($raw, FILTER_VALIDATE_INT);
    if ($value === false || $value < $min || $value > $max) {
        throw new InvalidArgumentException("--{$key} must be an integer from {$min} to {$max}.");
    }

    return $value;
}

function menuAiOptionString(array $options, string $key, string $default): string
{
    return trim((string) ($options[$key] ?? $default));
}

function menuAiStore(array $options): string
{
    $store = strtolower(menuAiOptionString($options, 'store', 'menu'));
    if (!in_array($store, ['menu', 'lunch'], true)) {
        throw new InvalidArgumentException('--store must be menu or lunch.');
    }

    return $store;
}

function menuAiEnsureBatchDir(): void
{
    if (!is_dir(MENU_AI_BATCH_DIR) && !mkdir(MENU_AI_BATCH_DIR, 0775, true) && !is_dir(MENU_AI_BATCH_DIR)) {
        throw new RuntimeException('Unable to create batch directory: ' . MENU_AI_BATCH_DIR);
    }
}

function menuAiSafeIdPart(string $value): string
{
    $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $value) ?? '';
    $safe = trim($safe, '-_');
    return $safe !== '' ? $safe : generateId();
}

function menuAiManifestPath(string $id): string
{
    return MENU_AI_BATCH_DIR . '/' . menuAiSafeIdPart($id) . '.json';
}

function menuAiInputPath(string $id): string
{
    return MENU_AI_BATCH_DIR . '/' . menuAiSafeIdPart($id) . '.jsonl';
}

function menuAiJsonEncode(array $payload): string
{
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new RuntimeException('Unable to encode JSON.');
    }

    return $json;
}

function menuAiWriteJson(string $path, array $payload): void
{
    if (file_put_contents($path, menuAiJsonEncode($payload) . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('Unable to write file: ' . $path);
    }
}

function menuAiLoadManifest(string $batchId): array
{
    $path = is_file($batchId) ? $batchId : menuAiManifestPath($batchId);
    if (!is_file($path)) {
        throw new RuntimeException('Manifest not found: ' . $path);
    }

    $json = file_get_contents($path);
    $manifest = is_string($json) ? json_decode($json, true) : null;
    if (!is_array($manifest)) {
        throw new RuntimeException('Manifest is not valid JSON: ' . $path);
    }

    $manifest['_path'] = $path;
    return $manifest;
}

function menuAiCategoryTitles(array $categories): array
{
    $titles = [];
    foreach ($categories as $category) {
        $slug = (string) ($category['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        $titles[$slug] = trim((string) (($category['title_en'] ?? '') ?: ($category['title_fi'] ?? '')));
    }

    return $titles;
}

function menuAiLunchDayLabel(string $weekday): string
{
    return match ($weekday) {
        'mon' => 'Monday lunch',
        'tue' => 'Tuesday lunch',
        'wed' => 'Wednesday lunch',
        'thu' => 'Thursday lunch',
        'fri' => 'Friday lunch',
        default => 'Weekday lunch',
    };
}

function menuAiLoadStoreData(string $store): array
{
    return match ($store) {
        'menu' => DataStore::ensure('menu', ['categories' => defaultMenuCategories(), 'items' => defaultMenuItems()]),
        'lunch' => DataStore::ensure('lunch', ['items' => []]),
        default => throw new InvalidArgumentException('Unsupported store: ' . $store),
    };
}

function menuAiSelectedItems(array $data, array $options, string $store): array
{
    $includeExisting = !empty($options['all']);
    $includeHidden = !empty($options['include-hidden']);
    $includeZeroPrice = !empty($options['include-zero-price']);
    $needle = strtolower(menuAiOptionString($options, 'item', ''));
    $limit = isset($options['limit']) ? menuAiOptionInt($options, 'limit', 1, 1, 500) : null;
    $day = strtolower(menuAiOptionString($options, 'day', ''));
    if ($store === 'lunch' && $day !== '' && !in_array($day, ['mon', 'tue', 'wed', 'thu', 'fri'], true)) {
        throw new InvalidArgumentException('--day must be mon, tue, wed, thu, or fri.');
    }

    $categoryTitles = $store === 'menu' ? menuAiCategoryTitles($data['categories'] ?? []) : [];
    $selected = [];

    foreach (array_values($data['items'] ?? []) as $index => $item) {
        $itemId = (string) ($item['id'] ?? '');
        $name = trim((string) (($item['name_en'] ?? '') ?: ($item['name_fi'] ?? '')));
        if ($itemId === '' || $name === '') {
            continue;
        }
        if (!$includeHidden && empty($item['visible'])) {
            continue;
        }
        if (!$includeExisting && trim((string) ($item['image'] ?? '')) !== '') {
            continue;
        }
        if ($store === 'menu' && !$includeZeroPrice && (float) ($item['price'] ?? 0) <= 0) {
            continue;
        }
        if ($store === 'lunch' && $day !== '' && strtolower((string) ($item['weekday'] ?? '')) !== $day) {
            continue;
        }
        if ($needle !== '') {
            $haystack = strtolower($itemId . ' ' . $name . ' ' . (string) ($item['name_fi'] ?? '') . ' ' . (string) ($item['name_en'] ?? ''));
            if (!str_contains($haystack, $needle)) {
                continue;
            }
        }

        $category = $store === 'menu'
            ? (string) ($item['category'] ?? '')
            : menuAiLunchDayLabel(strtolower((string) ($item['weekday'] ?? '')));
        $selected[] = [
            'index' => $index,
            'item' => $item,
            'category_title' => $categoryTitles[$category] ?? $category,
        ];

        if ($limit !== null && count($selected) >= $limit) {
            break;
        }
    }

    return $selected;
}

function menuAiPrompt(array $item, string $categoryTitle, string $store): string
{
    $name = trim((string) (($item['name_en'] ?? '') ?: ($item['name_fi'] ?? '')));
    $description = trim((string) (($item['description_en'] ?? '') ?: ($item['description_fi'] ?? '')));
    $tags = trim((string) ($item['dietary_tags'] ?? ''));

    $parts = [
        $store === 'lunch'
            ? 'Create a realistic food photography image for a restaurant lunch menu illustration.'
            : 'Create a realistic food photography image for a restaurant menu illustration.',
        '',
        'Dish: ' . $name,
    ];

    if ($categoryTitle !== '') {
        $parts[] = 'Category: ' . $categoryTitle;
    }
    if ($description !== '') {
        $parts[] = 'Description: ' . $description;
    }
    if ($tags !== '') {
        $parts[] = 'Dietary tags: ' . $tags;
    }
    if ($store === 'lunch') {
        $parts[] = 'Service: weekday lunch special.';
    }

    $parts[] = '';
    $parts[] = 'Visual direction: photorealistic casual restaurant food photography, realistic portion size, simple plate or basket, natural daylight, relaxed Nordic harbour container restaurant feeling, shallow depth of field.';
    $parts[] = 'Constraints: no people, no hands, no menu text, no captions, no logos, no watermark, no packaging text. Do not invent a restaurant logo or written label.';
    $parts[] = 'Purpose: illustrative AI image that gives customers a feeling for the dish, not an exact product photo.';

    return implode("\n", $parts);
}

function menuAiBuildRequests(array $menu, array $options): array
{
    $store = menuAiStore($options);
    $variants = menuAiOptionInt($options, 'variants', 1, 1, 8);
    $quality = menuAiOptionString($options, 'quality', 'medium');
    if (!in_array($quality, ['low', 'medium', 'high'], true)) {
        throw new InvalidArgumentException('--quality must be low, medium, or high.');
    }

    $size = menuAiOptionString($options, 'size', '1536x1024');
    if (!preg_match('/^\d+x\d+$/', $size)) {
        throw new InvalidArgumentException('--size must look like 1536x1024.');
    }

    $requests = [];
    foreach (menuAiSelectedItems($menu, $options, $store) as $entry) {
        $item = $entry['item'];
        $itemId = (string) ($item['id'] ?? '');
        $prompt = menuAiPrompt($item, (string) ($entry['category_title'] ?? ''), $store);
        for ($variant = 1; $variant <= $variants; $variant++) {
            $variantPrompt = $prompt;
            if ($variants > 1) {
                $variantPrompt .= "\nVariant: {$variant}. Use a distinct camera angle and plating while keeping the dish recognizable.";
            }

            $customId = $store . '_ai_' . menuAiSafeIdPart($itemId) . '_v' . $variant;
            $body = [
                'model' => 'gpt-image-2',
                'prompt' => $variantPrompt,
                'size' => $size,
                'quality' => $quality,
                'output_format' => 'jpeg',
                'output_compression' => 90,
                'n' => 1,
            ];

            $requests[] = [
                'store' => $store,
                'custom_id' => $customId,
                'item_id' => $itemId,
                'item_index' => (int) ($entry['index'] ?? 0),
                'item_name' => trim((string) (($item['name_en'] ?? '') ?: ($item['name_fi'] ?? ''))),
                'variant' => $variant,
                'request' => [
                    'custom_id' => $customId,
                    'method' => 'POST',
                    'url' => MENU_AI_BATCH_ENDPOINT,
                    'body' => $body,
                ],
            ];
        }
    }

    return $requests;
}

function menuAiWriteInputFile(string $path, array $requests): void
{
    $handle = fopen($path, 'wb');
    if ($handle === false) {
        throw new RuntimeException('Unable to write input file: ' . $path);
    }

    foreach ($requests as $request) {
        $line = json_encode($request['request'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($line === false || fwrite($handle, $line . PHP_EOL) === false) {
            fclose($handle);
            throw new RuntimeException('Unable to write JSONL request.');
        }
    }

    fclose($handle);
}

function menuAiEstimateStandardCost(array $requests): ?float
{
    $costs = [
        'low' => ['1024x1024' => 0.006, '1024x1536' => 0.005, '1536x1024' => 0.005],
        'medium' => ['1024x1024' => 0.053, '1024x1536' => 0.041, '1536x1024' => 0.041],
        'high' => ['1024x1024' => 0.211, '1024x1536' => 0.165, '1536x1024' => 0.165],
    ];

    $total = 0.0;
    foreach ($requests as $request) {
        $body = $request['request']['body'] ?? [];
        $quality = (string) ($body['quality'] ?? '');
        $size = (string) ($body['size'] ?? '');
        if (!isset($costs[$quality][$size])) {
            return null;
        }
        $total += $costs[$quality][$size];
    }

    return $total;
}

function menuAiCreatePlan(array $options, string $id): array
{
    menuAiEnsureBatchDir();
    $store = menuAiStore($options);
    $menu = menuAiLoadStoreData($store);
    $requests = menuAiBuildRequests($menu, $options);
    if ($requests === []) {
        throw new RuntimeException('No ' . $store . ' items matched the selected options.');
    }

    $inputPath = menuAiInputPath($id);
    $manifestPath = menuAiManifestPath($id);
    menuAiWriteInputFile($inputPath, $requests);

    $standardCost = menuAiEstimateStandardCost($requests);
    $manifest = [
        'id' => $id,
        'store' => $store,
        'batch_id' => null,
        'endpoint' => MENU_AI_BATCH_ENDPOINT,
        'created_at' => date('c'),
        'input_path' => $inputPath,
        'request_count' => count($requests),
        'selected_item_count' => count(array_unique(array_column($requests, 'item_id'))),
        'estimated_standard_output_cost_usd' => $standardCost,
        'estimated_batch_output_cost_usd' => $standardCost !== null ? $standardCost / 2 : null,
        'requests' => $requests,
    ];
    menuAiWriteJson($manifestPath, $manifest);
    $manifest['_path'] = $manifestPath;

    return $manifest;
}

function menuAiApiKey(): string
{
    $apiKey = trim((string) getenv('OPENAI_API_KEY'));
    if ($apiKey === '') {
        throw new RuntimeException('Set OPENAI_API_KEY before using submit, status, or collect.');
    }

    return $apiKey;
}

function menuAiOpenAiJson(string $method, string $path, string $apiKey, ?array $payload = null): array
{
    $body = $payload !== null ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
    if ($payload !== null && $body === false) {
        throw new RuntimeException('Unable to encode OpenAI request JSON.');
    }

    $response = menuAiOpenAiRaw($method, $path, $apiKey, $body, ['Content-Type: application/json']);
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('OpenAI response was not JSON: ' . substr($response, 0, 500));
    }

    return $decoded;
}

function menuAiOpenAiRaw(string $method, string $path, string $apiKey, mixed $body = null, array $headers = []): string
{
    $ch = curl_init('https://api.openai.com' . $path);
    if ($ch === false) {
        throw new RuntimeException('Unable to initialize curl.');
    }

    $allHeaders = array_merge(['Authorization: Bearer ' . $apiKey], $headers);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $allHeaders,
        CURLOPT_TIMEOUT => 120,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if (!is_string($response)) {
        throw new RuntimeException('OpenAI request failed: ' . ($error !== '' ? $error : 'unknown curl error'));
    }
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException("OpenAI request failed with HTTP {$status}: " . substr($response, 0, 1000));
    }

    return $response;
}

function menuAiUploadBatchFile(string $path, string $apiKey): array
{
    if (!is_file($path)) {
        throw new RuntimeException('Input file not found: ' . $path);
    }

    $payload = [
        'purpose' => 'batch',
        'file' => curl_file_create($path, 'application/jsonl', basename($path)),
    ];

    $response = menuAiOpenAiRaw('POST', '/v1/files', $apiKey, $payload);
    $decoded = json_decode($response, true);
    if (!is_array($decoded) || empty($decoded['id'])) {
        throw new RuntimeException('File upload response did not include a file id.');
    }

    return $decoded;
}

function menuAiSubmit(array $options): void
{
    $apiKey = menuAiApiKey();
    $pendingId = 'pending_' . date('Ymd_His') . '_' . generateId();
    $manifest = menuAiCreatePlan($options, $pendingId);

    $file = menuAiUploadBatchFile((string) $manifest['input_path'], $apiKey);
    $batch = menuAiOpenAiJson('POST', '/v1/batches', $apiKey, [
        'input_file_id' => (string) $file['id'],
        'endpoint' => MENU_AI_BATCH_ENDPOINT,
        'completion_window' => '24h',
        'metadata' => ['kind' => 'waves_menu_ai_images', 'store' => (string) ($manifest['store'] ?? 'menu')],
    ]);

    $batchId = (string) ($batch['id'] ?? '');
    if ($batchId === '') {
        throw new RuntimeException('Batch creation response did not include a batch id.');
    }

    $finalInputPath = menuAiInputPath($batchId);
    if (!rename((string) $manifest['input_path'], $finalInputPath)) {
        $finalInputPath = (string) $manifest['input_path'];
    }

    $finalManifestPath = menuAiManifestPath($batchId);
    $manifest['id'] = $batchId;
    $manifest['batch_id'] = $batchId;
    $manifest['input_file_id'] = (string) $file['id'];
    $manifest['input_path'] = $finalInputPath;
    $manifest['submitted_at'] = date('c');
    $manifest['batch'] = $batch;
    unset($manifest['_path']);
    menuAiWriteJson($finalManifestPath, $manifest);
    @unlink(menuAiManifestPath($pendingId));

    menuAiPrintPlanSummary($manifest, true);
    fwrite(STDOUT, "Batch created: {$batchId}" . PHP_EOL);
    fwrite(STDOUT, "Next: OPENAI_API_KEY=... php scripts/menu-ai-images.php status {$batchId}" . PHP_EOL);
}

function menuAiPrintPlanSummary(array $manifest, bool $batch): void
{
    fwrite(STDOUT, 'Store: ' . (string) ($manifest['store'] ?? 'menu') . PHP_EOL);
    fwrite(STDOUT, 'Requests: ' . (int) ($manifest['request_count'] ?? 0) . PHP_EOL);
    fwrite(STDOUT, 'Items: ' . (int) ($manifest['selected_item_count'] ?? 0) . PHP_EOL);
    fwrite(STDOUT, 'Input: ' . (string) ($manifest['input_path'] ?? '') . PHP_EOL);
    fwrite(STDOUT, 'Manifest: ' . (string) ($manifest['_path'] ?? menuAiManifestPath((string) ($manifest['id'] ?? ''))) . PHP_EOL);

    $cost = $batch ? ($manifest['estimated_batch_output_cost_usd'] ?? null) : ($manifest['estimated_standard_output_cost_usd'] ?? null);
    if (is_float($cost) || is_int($cost)) {
        $label = $batch ? 'Estimated batch output cost' : 'Estimated standard output cost';
        fwrite(STDOUT, $label . ': $' . number_format((float) $cost, 4, '.', '') . PHP_EOL);
    } else {
        fwrite(STDOUT, 'Estimated cost: use the OpenAI calculator for this custom size.' . PHP_EOL);
    }
}

function menuAiStatus(string $batchId): void
{
    $batch = menuAiOpenAiJson('GET', '/v1/batches/' . rawurlencode($batchId), menuAiApiKey());
    $counts = is_array($batch['request_counts'] ?? null) ? $batch['request_counts'] : [];

    fwrite(STDOUT, 'Batch: ' . (string) ($batch['id'] ?? $batchId) . PHP_EOL);
    fwrite(STDOUT, 'Status: ' . (string) ($batch['status'] ?? 'unknown') . PHP_EOL);
    fwrite(STDOUT, 'Requests: ' . (int) ($counts['completed'] ?? 0) . '/' . (int) ($counts['total'] ?? 0) . ' completed, ' . (int) ($counts['failed'] ?? 0) . ' failed' . PHP_EOL);

    if (!empty($batch['output_file_id'])) {
        fwrite(STDOUT, 'Output file: ' . (string) $batch['output_file_id'] . PHP_EOL);
    }
    if (!empty($batch['error_file_id'])) {
        fwrite(STDOUT, 'Error file: ' . (string) $batch['error_file_id'] . PHP_EOL);
    }
}

function menuAiImageExtensionFromBytes(string $bytes): string
{
    $info = @getimagesizefromstring($bytes);
    $mime = strtolower((string) ($info['mime'] ?? ''));

    return match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        default => 'img',
    };
}

function menuAiSaveGeneratedImage(array $request, string $bytes): string
{
    if (!ensureUploadDirectory()) {
        throw new RuntimeException('Uploads directory is not writable.');
    }

    $store = in_array((string) ($request['store'] ?? ''), ['menu', 'lunch'], true) ? (string) $request['store'] : 'menu';
    $itemId = menuAiSafeIdPart((string) ($request['item_id'] ?? 'item'));
    $variant = (int) ($request['variant'] ?? 1);
    $hash = substr(sha1($bytes), 0, 12);
    $ext = menuAiImageExtensionFromBytes($bytes);
    if ($ext === 'img') {
        throw new RuntimeException('Generated image bytes were not a supported image.');
    }

    $tmpPath = ROOT . '/uploads/tmp_' . $store . '_ai_' . generateId() . '.' . $ext;
    $filename = $store . '_ai_' . $itemId . '_v' . $variant . '_' . $hash . '.avif';
    $destPath = ROOT . '/uploads/' . $filename;

    if (file_put_contents($tmpPath, $bytes, LOCK_EX) === false) {
        throw new RuntimeException('Unable to write temporary image.');
    }

    $ok = optimizeImage($tmpPath, $destPath);
    @unlink($tmpPath);
    if (!$ok) {
        throw new RuntimeException('Unable to optimize generated image: ' . $filename);
    }

    return $filename;
}

function menuAiRequestsByCustomId(array $manifest): array
{
    $byId = [];
    foreach ($manifest['requests'] ?? [] as $request) {
        if (!is_array($request)) {
            continue;
        }
        $customId = (string) ($request['custom_id'] ?? '');
        if ($customId !== '') {
            $byId[$customId] = $request;
        }
    }

    return $byId;
}

function menuAiCollect(string $batchId, array $options): void
{
    $apiKey = menuAiApiKey();
    $manifest = menuAiLoadManifest($batchId);
    $batchId = (string) (($manifest['batch_id'] ?? '') ?: $batchId);
    $batch = menuAiOpenAiJson('GET', '/v1/batches/' . rawurlencode($batchId), $apiKey);
    $status = (string) ($batch['status'] ?? '');
    if ($status !== 'completed') {
        throw new RuntimeException("Batch is not completed yet. Current status: {$status}");
    }

    $outputFileId = (string) ($batch['output_file_id'] ?? '');
    if ($outputFileId === '') {
        throw new RuntimeException('Completed batch did not include an output file id.');
    }

    $output = menuAiOpenAiRaw('GET', '/v1/files/' . rawurlencode($outputFileId) . '/content', $apiKey);
    $requestsById = menuAiRequestsByCustomId($manifest);
    $generated = [];
    $failed = 0;

    foreach (preg_split('/\R/', trim($output)) ?: [] as $line) {
        if (trim($line) === '') {
            continue;
        }
        $row = json_decode($line, true);
        if (!is_array($row)) {
            $failed++;
            continue;
        }

        $customId = (string) ($row['custom_id'] ?? '');
        $request = $requestsById[$customId] ?? null;
        $body = $row['response']['body'] ?? null;
        $b64 = is_array($body) ? (string) ($body['data'][0]['b64_json'] ?? '') : '';
        if (!is_array($request) || $b64 === '') {
            $failed++;
            continue;
        }

        $bytes = base64_decode($b64, true);
        if (!is_string($bytes) || $bytes === '') {
            $failed++;
            continue;
        }

        $filename = menuAiSaveGeneratedImage($request, $bytes);
        $generated[] = [
            'store' => (string) ($request['store'] ?? ($manifest['store'] ?? 'menu')),
            'custom_id' => $customId,
            'item_id' => (string) ($request['item_id'] ?? ''),
            'item_name' => (string) ($request['item_name'] ?? ''),
            'variant' => (int) ($request['variant'] ?? 1),
            'filename' => $filename,
        ];
        fwrite(STDOUT, 'Saved ' . $filename . ' for ' . (string) ($request['item_name'] ?? $customId) . PHP_EOL);
    }

    $manifest['batch'] = $batch;
    $manifest['output_file_id'] = $outputFileId;
    $manifest['collected_at'] = date('c');
    $manifest['generated'] = $generated;
    unset($manifest['_path']);
    menuAiWriteJson(menuAiManifestPath($batchId), $manifest);

    if (!empty($options['apply'])) {
        menuAiApplyGenerated((string) ($manifest['store'] ?? 'menu'), $generated, !empty($options['overwrite']));
    }

    fwrite(STDOUT, 'Collected images: ' . count($generated) . PHP_EOL);
    if ($failed > 0) {
        fwrite(STDOUT, 'Skipped failed output rows: ' . $failed . PHP_EOL);
    }
}

function menuAiApplyGenerated(string $store, array $generated, bool $overwrite): void
{
    usort($generated, static function (array $a, array $b): int {
        $itemCompare = strcmp((string) ($a['item_id'] ?? ''), (string) ($b['item_id'] ?? ''));
        if ($itemCompare !== 0) {
            return $itemCompare;
        }

        return ((int) ($a['variant'] ?? 1)) <=> ((int) ($b['variant'] ?? 1));
    });

    $firstByItem = [];
    foreach ($generated as $image) {
        $itemId = (string) ($image['item_id'] ?? '');
        if ($itemId === '' || isset($firstByItem[$itemId])) {
            continue;
        }
        $firstByItem[$itemId] = (string) ($image['filename'] ?? '');
    }

    if ($firstByItem === []) {
        fwrite(STDOUT, 'No generated images to apply.' . PHP_EOL);
        return;
    }

    RevisionLog::init(DATA_DIR);
    $data = menuAiLoadStoreData($store);
    $before = $data;
    $updated = 0;

    if (!isset($data['items']) || !is_array($data['items'])) {
        fwrite(STDOUT, 'No ' . $store . ' items are available to update.' . PHP_EOL);
        return;
    }

    foreach ($data['items'] as &$item) {
        $itemId = (string) ($item['id'] ?? '');
        if (!isset($firstByItem[$itemId])) {
            continue;
        }
        if (!$overwrite && trim((string) ($item['image'] ?? '')) !== '') {
            continue;
        }

        $item['image'] = safeUploadFilename($firstByItem[$itemId]);
        if ($store === 'menu') {
            $item['updated_at'] = date('c');
        }
        $updated++;
    }
    unset($item);

    if ($updated === 0) {
        fwrite(STDOUT, 'No ' . $store . ' items were updated. Use --overwrite to replace existing images.' . PHP_EOL);
        return;
    }

    DataStore::save($store, $data);
    RevisionLog::log($store, 'updated', $data, $before);
    fwrite(STDOUT, 'Applied generated images to ' . $store . ' items: ' . $updated . PHP_EOL);
}

function menuAiMain(array $argv): int
{
    $command = (string) ($argv[1] ?? '');
    if ($command === '' || in_array($command, ['help', '--help', '-h'], true)) {
        fwrite(STDOUT, menuAiUsage() . PHP_EOL);
        return $command === '' ? 1 : 0;
    }

    $options = menuAiParseOptions(array_slice($argv, 2));
    $positionals = $options['_'] ?? [];

    switch ($command) {
        case 'plan':
            $id = 'plan_' . date('Ymd_His') . '_' . generateId();
            $manifest = menuAiCreatePlan($options, $id);
            menuAiPrintPlanSummary($manifest, false);
            return 0;

        case 'submit':
            menuAiSubmit($options);
            return 0;

        case 'status':
            $batchId = (string) ($positionals[0] ?? '');
            if ($batchId === '') {
                throw new InvalidArgumentException('Missing batch id.');
            }
            menuAiStatus($batchId);
            return 0;

        case 'collect':
            $batchId = (string) ($positionals[0] ?? '');
            if ($batchId === '') {
                throw new InvalidArgumentException('Missing batch id.');
            }
            menuAiCollect($batchId, $options);
            return 0;

        default:
            throw new InvalidArgumentException('Unknown command: ' . $command);
    }
}

try {
    exit(menuAiMain($argv));
} catch (Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL . PHP_EOL);
    fwrite(STDERR, menuAiUsage() . PHP_EOL);
    exit(1);
}
