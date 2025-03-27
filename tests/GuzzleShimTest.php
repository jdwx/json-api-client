<?php


declare( strict_types = 1 );


use JDWX\JsonApiClient\GuzzleShim;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( GuzzleShim::class )]
final class GuzzleShimTest extends TestCase {


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
