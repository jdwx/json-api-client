<?php


declare( strict_types = 1 );


use JDWX\Json\Json;


require_once __DIR__ . '/../../vendor/autoload.php';


( function () : void {

    header( 'Content-Type: application/json' );
    switch ( $_SERVER[ 'REQUEST_METHOD' ] ) {
        case 'GET':
            if ( isset( $_GET[ 'error' ] ) ) {
                http_response_code( intval( $_GET[ 'error' ] ) );
                $stMessage = $_GET[ 'message' ] ?? 'Provoked error.';
                echo Json::encode( [ 'error' => $stMessage ] );
                exit;
            }
            echo Json::encode( $_GET );
            break;

        case 'POST':
            $r = Json::decode( file_get_contents( 'php://input' ) );
            array_walk_recursive( $r, function ( int|string $i_key, mixed $i_x ) : mixed {
                if ( is_string( $i_x ) ) {
                    return strtoupper( $i_x );
                }
                return $i_x;
            } );
            echo Json::encode( $r );
            break;

        default:
            http_response_code( 405 );
            echo "{\"error\":\"Method not allowed.\"}";
            exit;
    }


} )();
