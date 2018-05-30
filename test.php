<?php

use Elasticsearch\ClientBuilder;
require 'vendor/autoload.php';

$client = ClientBuilder::create()->build();

$es_index_name = 'eventsource';


# delete existing index
$deleteParams = [
    'index' => $es_index_name
];
$response = $client->indices()->delete($deleteParams);


# create an index
$params = [
    'index' => $es_index_name,
    'body' => [
        'settings' => [
            'number_of_shards' => 2,
            'number_of_replicas' => 0
        ]
    ]
];

$response = $client->indices()->create($params);
print_r($response);

/***/
$xml_file = 'eventsource0416/events0416.xml';
$xml_content = utf8_encode(file_get_contents($xml_file));
$xml = simplexml_load_string($xml_content);
# print_r($xml);

foreach ( $xml->event as $event ) {


    if ( ! $event->age_info->count )
        $event->age_info = '';

    if ( ! $event->payment_options->count )
        $event->payment_options = '';

    if ( ! $event->theatertype->count )
        $event->theatertype = '';

    if ( ! $event->showtimes->count )
        $event->showtimes = '';

    $json = json_encode( $event );
    print $json . "\n\n"; # exit;

    $params = [
        'index' => $es_index_name,
        'type' => 'event',
        'id' => $event->event_id,
        'body' => $event,
    ];
    $response = $client->index($params);
    # print_r($response);
    # exit;
}

/***/

# search for a doc
/***
$params = [
    'index' => $es_index_name,
    'type' => 'event',
    'body' => [
        'query' => [
            'match' => [
                'slug' => 'pitch'
            ]
        ]
    ]
];

$response = $client->search($params);
print_r($response);
***/

?>

