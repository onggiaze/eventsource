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
$events_xml = 'eventsource0416/events0416.xml';
$perfs_xml = 'eventsource0416/perfs0416.xml';
$venues_xml = 'eventsource0416/venues0416.xml';

# Load all venues into an array
$venues = load_venues( $venues_xml );

# Load all performers into an array
$performers = load_performers( $perfs_xml );

# Load all events into an array
$events = load_events( $events_xml );

#
# Processing each event
#

$arr_fields = array( 'age_info', 'payment_options', 'theatertype', 'showtimes' );

foreach ( $events->event as $event ) {

    foreach ( $arr_fields as $fld ) {
        // print "$fld: " . empty( $event->{$fld} ) . "\n";
        // if ( empty($event->{$fld}) ) {
            $event->{$fld} = '';
        // }
    }


    # Adding full venue info
    if ( isset( $event->venue_id ) ) {
        $event->venue = $venues->{$event->venue_id};
    }
    $event->venue->venue_phone = '';
    $event->venue->venue_fax = '';
    $event->venue->venue_payment_options = '';
    $event->venue->venue_general = '';


    # Adding full performer info
    if ( isset( $event->perf_id ) ) {
        $event->performer = $performers->{$event->perf_id}; 
    }


    print "\n\n"; # exit;
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

function load_events( $xml_file ) {
    $content = utf8_encode( file_get_contents( $xml_file ) );
    $xml = simplexml_load_string( $content, 'SimpleXMLElement', LIBXML_NOCDATA );
    $xml = json_decode( str_replace( '{}', '""', json_encode( $xml ) ) );

    return $xml;
}



function load_venues( $venue_xml ) {
    $venues_content = utf8_encode( file_get_contents( $venue_xml ) );
    $xml = simplexml_load_string( $venues_content, 'SimpleXMLElement', LIBXML_NOCDATA );
    $xml = json_decode( str_replace( '{}', '""', json_encode( $xml ) ) );

    $venues = new ArrayObject();
    foreach ($xml->venue as $venue) {
        $venue_id = $venue->venue_id;
        $venues->$venue_id = $venue;
    }
    return $venues;
}


function load_performers( $performer_xml ) {
    $perf_content = utf8_encode( file_get_contents($performer_xml ) );
    $xml = simplexml_load_string( $perf_content, 'SimpleXMLElement', LIBXML_NOCDATA );
    $xml = json_decode( str_replace( '{}', '""', json_encode( $xml ) ) );

    $performers = new ArrayObject();
    foreach ( $xml->performer as $performer ) {
        $perf_id = $performer->perf_id;
        $performers->$perf_id = $performer;
    }
    
    return $performers;
}

?>

