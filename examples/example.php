<?php


require __DIR__ . '/../vendor/autoload.php';


$client = JDWX\JsonApiClient\HttpClient::withGuzzle( 'https://api.example.com' );
$rsp = $client->request( 'GET', '/v1/resource' );
if ( ! $rsp->isSuccess() ) {
    $uStatus = $rsp->status();
    echo "{$uStatus} Error: ", $rsp->body(), "\n";
    exit( 1 );
}
if ( ! $rsp->isJson() ) {
    $stContentType = $rsp->getOneHeader( 'content-type' ) ?? 'none';
    echo "Error: Expected JSON content-type, got: {$stContentType}\n";
    exit( 1 );
}
$data = $rsp->json();
var_dump( $data );
