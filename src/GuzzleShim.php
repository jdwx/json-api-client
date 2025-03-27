<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;


final class GuzzleShim {


    public static function createClient( ?string $i_stBaseURI = null,
                                         float   $i_fTimeout = 5.0 ) : ClientInterface {
        $r = [
            'timeout' => $i_fTimeout,
            'http_errors' => false,
        ];
        if ( is_string( $i_stBaseURI ) ) {
            $r[ 'base_uri' ] = $i_stBaseURI;
        }
        return new Client( $r );
    }


    public static function createFactory() : HttpFactory {
        return new HttpFactory();
    }


}
