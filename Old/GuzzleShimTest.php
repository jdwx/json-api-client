<?php


declare( strict_types = 1 );


namespace Old;


use GuzzleHttp;
use PHPUnit\Framework\Attributes\CoversClass;


#[CoversClass( GuzzleShim::class )]
final class GuzzleShimTest {


    public function testCreateClient() : void {
        $client = GuzzleShim::createClient();
        self::assertInstanceOf( GuzzleHttp\Client::class, $client );

        $client = GuzzleShim::createClient( 'http://localhost:6502/' );
        self::assertInstanceOf( GuzzleHttp\Client::class, $client );
    }


    public function testCreateFactory() : void {
        $factory = GuzzleShim::createFactory();
        self::assertInstanceOf( GuzzleHttp\Psr7\HttpFactory::class, $factory );
    }


}
