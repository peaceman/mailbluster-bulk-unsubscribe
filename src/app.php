<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Uri;
use League\Csv\Reader;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(getcwd());
$env = $dotenv->load();
$dotenv->required('SOURCE_FILE')->notEmpty();
$dotenv->required('MAILBLUSTER_API_KEY')->notEmpty();

$httpClient = new Client([
    'base_uri' => 'https://api.mailbluster.com',
    'headers' => [
        'Authorization' => $env['MAILBLUSTER_API_KEY'],
    ]
]);

$reader = Reader::createFromFileObject(new SplFileObject($env['SOURCE_FILE']));
$reader->setDelimiter(';');
$reader->setHeaderOffset(0);

$recIter = $reader->getRecords(['customerNumber', 'email']);

foreach ($recIter as $record) {
    if (empty($email = $record['email'] ?? '')) continue;

    $emailMd5 = md5($email);
    $uri = new Uri("/api/leads/$emailMd5");

    try {
        $httpClient->put($uri, ['json' => [
            'subscribed' => false,
        ]]);

        echo "Unsubscribed $email\n";
    } catch (RequestException $e) {
        echo "Failed to unsubscribe $email\n";
    }
}
