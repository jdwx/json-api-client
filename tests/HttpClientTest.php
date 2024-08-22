<?php


declare( strict_types = 1 );


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use JDWX\JsonApiClient\HttpClient;
use JDWX\JsonApiClient\HTTPException;
use JDWX\JsonApiClient\TransportException;
use PHPUnit\Framework\TestCase;


// require_once __DIR__ . '/MyTestClient.php';


class HttpClientTest extends TestCase {


    public function testDebug() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $cli->setDebug( true );
        ob_start();
        $cli->post( '/foo', 'body-text', 'text/plain', i_rHeaders: [ 'qux' => 'Qux' ] );
        $out = ob_get_clean();
        static::assertStringContainsString( 'POST /foo', $out );
        static::assertStringContainsString( 'qux: Qux', $out );
        static::assertStringContainsString( 'body-text', $out );

        $cli->setDebug( false );
        ob_start();
        $cli->get( '/foo' );
        $out = ob_get_clean();
        static::assertSame( '', $out );
    }


    public function testGet() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $rsp = $cli->get( '/foo' );
        static::assertEquals( 200, $rsp->status() );
        static::assertEquals( 'baz', $rsp->body() );
    }


    public function testGetForCustomHeaders() : void {
        $r = [];
        $stack = $this->makeHistoryMock( $r );
        $http = new Client( [ 'handler' => $stack ] );
        $cli = new HttpClient( $http );
        $cli->setExtraHeader( 'X-Foo', 'Bar' );
        $cli->get( 'https://www.example.com/foo', i_rHeaders: [ 'X-Baz' => 'Qux' ] );
        $req = $r[ 0 ][ 'request' ];
        static::assertEquals( 'Bar', $req->getHeader( 'X-Foo' )[ 0 ] );
        static::assertEquals( 'Qux', $req->getHeader( 'X-Baz' )[ 0 ] );
    }


    public function testGetForStream() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $rsp = $cli->get( '/foo', i_bStream: true );
        $st = $rsp->streamBody( 12 );
        static::assertEquals( 200, $rsp->status() );
        static::assertEquals( 'baz', $st );
    }


    public function testGetWith404Error() : void {
        $mock = new MockHandler( [
            new Response( 404, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        self::expectException( HTTPException::class );
        $cli->get( '/foo' );
    }


    public function testGetWith404ErrorAllowed() : void {
        $mock = new MockHandler( [
            new Response( 404, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $rsp = $cli->get( '/foo', i_bAllowFailure: true );
        static::assertEquals( 404, $rsp->status() );
        static::assertEquals( 'baz', $rsp->body() );
    }


    public function testGetWithFailedRequest() : void {
        $mock = new MockHandler( [
            new RequestException( 'foo', new Request( 'GET', 'https://www.example.com/foo' ) ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        self::expectException( HTTPException::class );
        $cli->get( '/foo' );
    }


    public function testPost() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $rsp = $cli->post( '/foo', 'body', 'text/plain' );
        static::assertEquals( 200, $rsp->status() );
        static::assertEquals( 'baz', $rsp->body() );
    }


    public function testPostForExtraHeader() : void {
        $r = [];
        $stack = $this->makeHistoryMock( $r, [
            new Response( 200, [ 'Baz' => 'Quz' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $stack ] );
        $cli = new HttpClient( $http );
        $cli->setExtraHeader( 'X-Foo', 'Bar' );
        $cli->post( 'https://www.example.com/foo', '', 'application/json' );
        $req = $r[ 0 ][ 'request' ];
        static::assertEquals( 'Bar', $req->getHeader( 'X-Foo' )[ 0 ] );
    }


    public function testPostForInjectedHeader() : void {
        $r = [];
        $stack = $this->makeHistoryMock( $r );
        $http = new Client( [ 'handler' => $stack ] );
        $cli = new HttpClient( $http );
        $cli->post( 'https://www.example.com/foo', '', 'application/json', [ 'X-Foo' => 'Bar' ] );
        $req = $r[ 0 ][ 'request' ];
        static::assertEquals( 'Bar', $req->getHeader( 'X-Foo' )[ 0 ] );
    }


    public function testPostJson() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $rsp = $cli->postJson( '/foo', [ 'a' => 1, 'b' => 2 ] );
        static::assertEquals( 200, $rsp->status() );
        static::assertEquals( 'baz', $rsp->body() );
    }


    public function testSendRequest() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $req = new Request( 'GET', 'https://www.example.com/foo' );
        $rsp = $cli->sendRequest( $req );
        static::assertEquals( 200, $rsp->status() );
        static::assertEquals( 'baz', $rsp->body() );
    }


    public function testSendRequestWithFailedRequest() : void {
        $mock = new MockHandler( [
            new RequestException( 'foo', new Request( 'GET', 'https://www.example.com/foo' ) ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        self::expectException( TransportException::class );
        $req = new Request( 'GET', 'https://www.example.com/foo' );
        $cli->sendRequest( $req );
    }


    public function testWithGuzzle() : void {
        $cli = HttpClient::withGuzzle( 'https://www.example.com/' );
        static::assertInstanceOf( HttpClient::class, $cli );
    }


    private function makeHistoryMock( array &$o_rHistory, ?array $i_nrResponses = null ) : HandlerStack {
        $i_nrResponses = $i_nrResponses ?? [
            new Response( 200, [], '' ),
        ];
        $mock = new MockHandler( $i_nrResponses );
        $stack = HandlerStack::create( $mock );
        $history = Middleware::history( $o_rHistory );
        $stack->push( $history );
        return $stack;
    }


}
