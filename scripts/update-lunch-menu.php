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

function lunchItem(
    string $id,
    string $weekday,
    string $nameFi,
    string $nameEn,
    string $tags
): array {
    return [
        'id' => $id,
        'weekday' => $weekday,
        'name_fi' => $nameFi,
        'name_en' => $nameEn,
        'description_fi' => '',
        'description_en' => '',
        'price' => 0,
        'dietary_tags' => $tags,
        'visible' => true,
    ];
}

$tacoFi = 'Valitsemasi Summer Taco + ranut tai salaatti';
$tacoEn = 'Your choice of Summer Taco + fries or salad';

$items = [
    lunchItem('lunch-mon-taco', 'mon', $tacoFi, $tacoEn, 'L'),
    lunchItem('lunch-mon-veggie-clash', 'mon', 'Veggie Clash Burger', 'Veggie Clash Burger', 'L'),
    lunchItem('lunch-mon-chorizo-smash', 'mon', 'Chorizo Smash Burger', 'Chorizo Smash Burger', 'L'),

    lunchItem('lunch-tue-taco', 'tue', $tacoFi, $tacoEn, 'L'),
    lunchItem('lunch-tue-salmon-salad', 'tue', 'Lohisalaatti', 'Salmon Salad', 'L,G'),
    lunchItem('lunch-tue-crispy-chicken', 'tue', 'Crispy Chicken Burger', 'Crispy Chicken Burger', 'VL'),

    lunchItem('lunch-wed-taco', 'wed', $tacoFi, $tacoEn, 'L'),
    lunchItem('lunch-wed-halloumi-salad', 'wed', 'Halloumisalaatti', 'Halloumi Salad', 'L,G'),
    lunchItem('lunch-wed-double-smash', 'wed', 'Double Smash Burger', 'Double Smash Burger', 'VL'),

    lunchItem('lunch-thu-taco', 'thu', $tacoFi, $tacoEn, 'L'),
    lunchItem('lunch-thu-veggie-clash', 'thu', 'Veggie Clash Burger', 'Veggie Clash Burger', 'L'),
    lunchItem('lunch-thu-chorizo-smash', 'thu', 'Chorizo Smash Burger', 'Chorizo Smash Burger', 'L'),

    lunchItem('lunch-fri-taco', 'fri', $tacoFi, $tacoEn, 'L'),
    lunchItem('lunch-fri-chicken-salad', 'fri', 'Kanasalaatti', 'Chicken Salad', 'L,G'),
    lunchItem('lunch-fri-double-smash-aura', 'fri', 'Double Smash X Aura Burger', 'Double Smash X Aura Burger', 'L'),
    lunchItem('lunch-fri-veggie-clash', 'fri', 'Veggie Clash Burger', 'Veggie Clash Burger', 'L'),
];

RevisionLog::init(DATA_DIR);

$before = DataStore::load('lunch');
$data = ['items' => $items];
DataStore::save('lunch', $data);
RevisionLog::log('lunch', 'updated', $data, $before);

fwrite(STDOUT, 'Updated lunch menu: ' . count($items) . ' items' . PHP_EOL);
