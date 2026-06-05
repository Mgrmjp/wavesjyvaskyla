<?php

declare(strict_types=1);

// Google Places API (New) requires a field mask and exposes regularOpeningHours.
// Sources:
// - https://developers.google.com/maps/documentation/places/web-service/text-search
// - https://developers.google.com/maps/documentation/places/web-service/place-details
// - https://developers.google.com/maps/documentation/places/web-service/reference/rest/v1/places#OpeningHours

define('ROOT', dirname(__DIR__));
define('DATA_DIR', ROOT . '/data');

const DEFAULT_QUERY = 'waves jkl';
const DEFAULT_DB_PATH = DATA_DIR . '/waves.sqlite';
const TEXT_SEARCH_URL = 'https://places.googleapis.com/v1/places:searchText';
const DAY_ORDER = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
const GOOGLE_DAY_TO_KEY = [
    0 => 'sun',
    1 => 'mon',
    2 => 'tue',
    3 => 'wed',
    4 => 'thu',
    5 => 'fri',
    6 => 'sat',
];
const EN_DAY_TO_KEY = [
    'monday' => 'mon',
    'tuesday' => 'tue',
    'wednesday' => 'wed',
    'thursday' => 'thu',
    'friday' => 'fri',
    'saturday' => 'sat',
    'sunday' => 'sun',
];

function usage(): void
{
    $script = basename(__FILE__);
    echo <<<TXT
Usage:
  GOOGLE_PLACES_API_KEY=... php scripts/{$script} [--apply] [--query="waves jkl"]
  GOOGLE_PLACES_API_KEY=... php scripts/{$script} [--apply] --place-id=PLACE_ID
  php scripts/{$script} --from-json=response.json [--apply]

Options:
  --apply             Write the parsed hours to SQLite. Default is dry-run.
  --dry-run           Parse and print proposed hours without writing.
  --query=TEXT        Text Search query. Defaults to "waves jkl".
  --place-id=ID       Use Place Details directly for a known Google place ID.
  --db=PATH           SQLite path. Defaults to APP_DB_PATH or data/waves.sqlite.
  --from-json=PATH    Parse a saved Places API response instead of calling Google.
  --help              Show this help.

Environment:
  GOOGLE_PLACES_API_KEY  Required unless --from-json is used.
  GOOGLE_PLACE_ID        Optional default for --place-id.
  GOOGLE_HOURS_QUERY     Optional default for --query.
  GOOGLE_HOURS_APPLY=1   Same as --apply.
  APP_DB_PATH            Optional SQLite path.

TXT;
}

function fail(string $message, int $code = 1): never
{
    fwrite(STDERR, 'Error: ' . $message . PHP_EOL);
    exit($code);
}

function optionString(array $options, string $key, string $default = ''): string
{
    $value = $options[$key] ?? $default;
    if (is_array($value)) {
        $value = end($value);
    }
    return trim((string) $value);
}

function requestJson(string $url, string $apiKey, string $fieldMask, ?array $body = null): array
{
    $headers = [
        'X-Goog-Api-Key: ' . $apiKey,
        'X-Goog-FieldMask: ' . $fieldMask,
    ];

    $context = [
        'http' => [
            'ignore_errors' => true,
            'method' => $body === null ? 'GET' : 'POST',
            'header' => implode("\r\n", $headers),
            'timeout' => 20,
        ],
    ];

    if ($body !== null) {
        $context['http']['header'] .= "\r\nContent-Type: application/json";
        $context['http']['content'] = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    $raw = file_get_contents($url, false, stream_context_create($context));
    if ($raw === false) {
        fail('Google Places request failed.');
    }

    $status = parseHttpStatus($http_response_header ?? []);
    $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

    if ($status < 200 || $status >= 300) {
        $error = is_array($decoded) ? ($decoded['error']['message'] ?? '') : '';
        fail('Google Places request returned HTTP ' . $status . ($error !== '' ? ': ' . $error : '.'));
    }

    if (!is_array($decoded)) {
        fail('Google Places response was not a JSON object.');
    }

    return $decoded;
}

function parseHttpStatus(array $headers): int
{
    foreach ($headers as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $header, $matches)) {
            return (int) $matches[1];
        }
    }

    return 0;
}

function fetchPlace(array $options): array
{
    $fromJson = optionString($options, 'from-json');
    if ($fromJson !== '') {
        if ($fromJson === '-') {
            $raw = (string) stream_get_contents(STDIN);
        } elseif (is_file($fromJson)) {
            $raw = (string) file_get_contents($fromJson);
        } else {
            fail('Response JSON file not found: ' . $fromJson);
        }
        $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            fail('Response JSON file did not contain an object.');
        }
        return normalizePlaceResponse($decoded);
    }

    $apiKey = trim((string) getenv('GOOGLE_PLACES_API_KEY'));
    if ($apiKey === '') {
        fail('Set GOOGLE_PLACES_API_KEY or use --from-json for a saved response.');
    }

    $placeId = optionString($options, 'place-id', (string) getenv('GOOGLE_PLACE_ID'));
    if ($placeId !== '') {
        $url = 'https://places.googleapis.com/v1/places/' . rawurlencode($placeId);
        $response = requestJson($url, $apiKey, 'id,displayName,formattedAddress,regularOpeningHours.periods,regularOpeningHours.weekdayDescriptions');
        return normalizePlaceResponse($response);
    }

    $query = optionString($options, 'query', (string) getenv('GOOGLE_HOURS_QUERY'));
    if ($query === '') {
        $query = DEFAULT_QUERY;
    }

    $response = requestJson(
        TEXT_SEARCH_URL,
        $apiKey,
        'places.id,places.displayName,places.formattedAddress,places.regularOpeningHours.periods,places.regularOpeningHours.weekdayDescriptions',
        [
            'textQuery' => $query,
            'languageCode' => 'en',
            'regionCode' => 'FI',
            'locationBias' => [
                'circle' => [
                    'center' => ['latitude' => 62.2386, 'longitude' => 25.7531],
                    'radius' => 5000.0,
                ],
            ],
        ]
    );

    $places = $response['places'] ?? [];
    if (!is_array($places) || $places === []) {
        fail('No Google Places matches found for query: ' . $query);
    }

    return choosePlace($places);
}

function normalizePlaceResponse(array $response): array
{
    if (isset($response['places']) && is_array($response['places'])) {
        return choosePlace($response['places']);
    }

    return $response;
}

function choosePlace(array $places): array
{
    foreach ($places as $place) {
        if (!is_array($place)) {
            continue;
        }

        $name = strtolower((string) ($place['displayName']['text'] ?? ''));
        $address = strtolower((string) ($place['formattedAddress'] ?? ''));
        if (str_contains($name, 'waves') && (str_contains($address, 'jyv') || str_contains($address, 'satamakatu'))) {
            return $place;
        }
    }

    $first = $places[0] ?? null;
    if (!is_array($first)) {
        fail('Google Places response did not contain usable places.');
    }

    return $first;
}

function rowsFromPlace(array $place): array
{
    $hours = $place['regularOpeningHours'] ?? null;
    if (!is_array($hours)) {
        fail('Selected place does not include regular opening hours.');
    }

    $periodRows = rowsFromPeriods($hours['periods'] ?? []);
    if ($periodRows !== null) {
        return $periodRows;
    }

    $weekdayRows = rowsFromWeekdayDescriptions($hours['weekdayDescriptions'] ?? []);
    if ($weekdayRows !== null) {
        return $weekdayRows;
    }

    fail('Could not parse regular opening hours from Google response.');
}

function emptyRows(): array
{
    $rows = [];
    foreach (DAY_ORDER as $index => $day) {
        $rows[$day] = [
            'day' => $day,
            'row_order' => $index,
            'open_time' => '',
            'close_time' => '',
            'is_closed' => 1,
        ];
    }
    return $rows;
}

function rowsFromPeriods(mixed $periods): ?array
{
    if (!is_array($periods) || $periods === []) {
        return null;
    }

    $rows = emptyRows();

    foreach ($periods as $period) {
        if (!is_array($period) || !isset($period['open'], $period['close']) || !is_array($period['open']) || !is_array($period['close'])) {
            fail('Unsupported Google hours period: missing open or close time.');
        }

        $openDay = (int) ($period['open']['day'] ?? -1);
        $closeDay = (int) ($period['close']['day'] ?? -1);
        if (!isset(GOOGLE_DAY_TO_KEY[$openDay], GOOGLE_DAY_TO_KEY[$closeDay])) {
            fail('Unsupported Google day in opening-hours period.');
        }
        if ($openDay !== $closeDay) {
            fail('Overnight opening hours are not supported by the current site schema.');
        }

        $key = GOOGLE_DAY_TO_KEY[$openDay];
        if ($rows[$key]['is_closed'] === 0) {
            fail('Multiple opening intervals per day are not supported by the current site schema.');
        }

        $rows[$key]['open_time'] = formatGoogleTimePoint($period['open']);
        $rows[$key]['close_time'] = formatGoogleTimePoint($period['close']);
        $rows[$key]['is_closed'] = 0;
    }

    return $rows;
}

function formatGoogleTimePoint(array $point): string
{
    $hour = (int) ($point['hour'] ?? 0);
    $minute = (int) ($point['minute'] ?? 0);
    if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
        fail('Invalid Google time point in opening-hours period.');
    }

    return sprintf('%02d:%02d', $hour, $minute);
}

function rowsFromWeekdayDescriptions(mixed $descriptions): ?array
{
    if (!is_array($descriptions) || $descriptions === []) {
        return null;
    }

    $rows = emptyRows();
    foreach ($descriptions as $description) {
        if (!is_string($description)) {
            continue;
        }

        $description = preg_replace('/\x{202f}|\x{00a0}/u', ' ', trim($description)) ?? trim($description);
        [$dayLabel, $hoursText] = array_pad(explode(':', $description, 2), 2, '');
        $key = EN_DAY_TO_KEY[strtolower(trim($dayLabel))] ?? null;
        if ($key === null) {
            continue;
        }

        $hoursText = trim($hoursText);
        if (preg_match('/^closed$/i', $hoursText)) {
            $rows[$key]['open_time'] = '';
            $rows[$key]['close_time'] = '';
            $rows[$key]['is_closed'] = 1;
            continue;
        }

        $parts = preg_split('/\s*(?:-|\x{2013}|\x{2014})\s*/u', $hoursText);
        if (!is_array($parts) || count($parts) !== 2) {
            fail('Unsupported weekday hours format: ' . $description);
        }

        $rows[$key]['open_time'] = parseGoogleTextTime($parts[0]);
        $rows[$key]['close_time'] = parseGoogleTextTime($parts[1]);
        $rows[$key]['is_closed'] = 0;
    }

    return $rows;
}

function parseGoogleTextTime(string $value): string
{
    $value = strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
    $value = preg_replace('/^(\d{1,2})\.(\d{2})/', '$1:$2', $value) ?? $value;
    $value = preg_replace('/\b([ap])\.?\s*m\.?\b/', '$1m', $value) ?? $value;

    if (preg_match('/^(\d{1,2})(?::(\d{2}))?\s*([ap])m$/', $value, $matches)) {
        $hour = (int) $matches[1];
        $minute = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : 0;
        $meridiem = $matches[3];
        if ($hour < 1 || $hour > 12 || $minute < 0 || $minute > 59) {
            fail('Invalid 12-hour time: ' . $value);
        }
        if ($meridiem === 'a' && $hour === 12) {
            $hour = 0;
        } elseif ($meridiem === 'p' && $hour !== 12) {
            $hour += 12;
        }
        return sprintf('%02d:%02d', $hour, $minute);
    }

    if (preg_match('/^(\d{1,2})(?::(\d{2}))?$/', $value, $matches)) {
        $hour = (int) $matches[1];
        $minute = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : 0;
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            fail('Invalid 24-hour time: ' . $value);
        }
        return sprintf('%02d:%02d', $hour, $minute);
    }

    fail('Unsupported time value: ' . $value);
}

function writeRows(string $dbPath, array $rows, array $place): void
{
    if (!extension_loaded('pdo_sqlite')) {
        fail('pdo_sqlite is required to write opening hours.');
    }
    if (!is_file($dbPath)) {
        fail('SQLite database not found: ' . $dbPath);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        'INSERT INTO opening_hours (
            day, row_order, open_time, close_time, kitchen_closes, is_closed, note
        ) VALUES (
            :day, :row_order, :open_time, :close_time, "", :is_closed, ""
        )
        ON CONFLICT(day) DO UPDATE SET
            row_order = excluded.row_order,
            open_time = excluded.open_time,
            close_time = excluded.close_time,
            kitchen_closes = "",
            is_closed = excluded.is_closed,
            note = ""'
    );

    $metaStmt = $pdo->prepare(
        'INSERT INTO app_meta (key, value) VALUES (:key, :value)
         ON CONFLICT(key) DO UPDATE SET value = excluded.value'
    );

    $pdo->beginTransaction();
    try {
        foreach ($rows as $row) {
            $stmt->execute([
                ':day' => $row['day'],
                ':row_order' => $row['row_order'],
                ':open_time' => $row['open_time'],
                ':close_time' => $row['close_time'],
                ':is_closed' => $row['is_closed'],
            ]);
        }

        $metaStmt->execute([':key' => 'google_hours_synced_at', ':value' => date('c')]);
        $metaStmt->execute([':key' => 'google_hours_place_id', ':value' => (string) ($place['id'] ?? '')]);
        $pdo->commit();
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $throwable;
    }
}

function printSummary(array $place, array $rows, bool $apply, string $dbPath): void
{
    $name = (string) ($place['displayName']['text'] ?? 'Unknown place');
    $id = (string) ($place['id'] ?? '');
    $address = (string) ($place['formattedAddress'] ?? '');

    echo 'Place: ' . $name . ($id !== '' ? ' (' . $id . ')' : '') . PHP_EOL;
    if ($address !== '') {
        echo 'Address: ' . $address . PHP_EOL;
    }
    echo 'Database: ' . $dbPath . PHP_EOL;
    echo 'Mode: ' . ($apply ? 'apply' : 'dry-run') . PHP_EOL;
    echo PHP_EOL;

    foreach (DAY_ORDER as $day) {
        $row = $rows[$day];
        $label = ucfirst($day);
        $value = $row['is_closed'] ? 'Closed' : $row['open_time'] . '-' . $row['close_time'];
        echo str_pad($label, 9) . $value . PHP_EOL;
    }
}

$options = getopt('', ['apply', 'dry-run', 'query:', 'place-id:', 'db:', 'from-json:', 'help']);
if (isset($options['help'])) {
    usage();
    exit(0);
}

$apply = isset($options['apply']) || getenv('GOOGLE_HOURS_APPLY') === '1';
if (isset($options['dry-run'])) {
    $apply = false;
}

$dbPath = optionString($options, 'db', (string) getenv('APP_DB_PATH'));
if ($dbPath === '') {
    $dbPath = DEFAULT_DB_PATH;
}

try {
    $place = fetchPlace($options);
    $rows = rowsFromPlace($place);
    printSummary($place, $rows, $apply, $dbPath);

    if (!$apply) {
        echo PHP_EOL . 'Dry run only. Re-run with --apply to update SQLite.' . PHP_EOL;
        exit(0);
    }

    writeRows($dbPath, $rows, $place);
    echo PHP_EOL . 'Opening hours updated.' . PHP_EOL;
} catch (JsonException $exception) {
    fail('Invalid JSON: ' . $exception->getMessage());
} catch (Throwable $throwable) {
    fail($throwable->getMessage());
}
