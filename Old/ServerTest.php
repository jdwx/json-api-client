<?php


declare( strict_types = 1 );


namespace Old;


use PHPUnit\Framework\Attributes\CoversClass;


#[CoversClass( HttpClient::class )]
final class ServerTest {


    public function testFor404() : void {
        $api = $this->newHttpClient();
        self::expectException( \JDWX\JsonApiClient\Exceptions\HttpStatusException::class );
        $api->get( 'nonexistent?error=404&message=TEST_MESSAGE' );
    }


    public function testFor404OK() : void {
        $api = $this->newHttpClient();
        $response = $api->get( 'nonexistent?error=404&message=TEST_MESSAGE', i_bAllowFailure: true );
        self::assertSame( 404, $response->status() );
        self::assertSame( 'application/json', $response->getOneHeader( 'Content-Type' ) );
        self::assertSame( [ 'error' => 'TEST_MESSAGE' ], $response->json() );
    }


    public function testGetFor200OK() : void {
        $api = $this->newHttpClient();
        $response = $api->get( 'test?foo=bar' );
        self::assertSame( 200, $response->status() );
        self::assertSame( 'application/json', $response->getOneHeader( 'Content-Type' ) );
        self::assertSame( [ 'foo' => 'bar' ], $response->json() );
    }


    public function testGetFor500() : void {
        $api = $this->newHttpClient();
        self::expectException( \JDWX\JsonApiClient\Exceptions\HttpStatusException::class );
        $api->get( 'test?error=500&message=TEST_MESSAGE' );
    }


    public function testGetFor500OK() : void {
        $api = $this->newHttpClient();
        $response = $api->get( 'test?error=500&message=TEST_MESSAGE&what=where', i_bAllowFailure: true );
        self::assertSame( 500, $response->status() );
        self::assertSame( 'application/json', $response->getOneHeader( 'Content-Type' ) );
        self::assertSame( [ 'error' => 'TEST_MESSAGE' ], $response->json() );
    }


    protected function setUp() : void {
        parent::setUp();

        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $check = @file_get_contents( 'http://localhost:6502/' );
        if ( '[]' !== $check ) {
            self::markTestSkipped( 'Test server not running' );
        }
    }


    private function newHttpClient() : HttpClient {
        return HttpClient::withGuzzle( 'http://localhost:6502/' );
    }


}
