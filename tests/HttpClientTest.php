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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


// require_once __DIR__ . '/MyTestClient.php';


#[CoversClass( HttpClient::class )]
final class HttpClientTest extends TestCase {


    public function testConstruct() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $api = new HttpClient( new Client( [ 'handler' => $mock ] ) );
        $r = $api->get( '/' );
        self::assertEquals( 200, $r->status() );
        self::assertEquals( 'baz', $r->body() );
        self::assertEquals( 'Bar', $r->getOneHeader( 'Foo' ) );
    }


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
        self::assertStringContainsString( 'POST /foo', $out );
        self::assertStringContainsString( 'qux: Qux', $out );
        self::assertStringContainsString( 'body-text', $out );

        $cli->setDebug( false );
        ob_start();
        $cli->get( '/foo' );
        $out = ob_get_clean();
        self::assertSame( '', $out );
    }


    public function testGet() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $rsp = $cli->get( '/foo' );
        self::assertEquals( 200, $rsp->status() );
        self::assertEquals( 'baz', $rsp->body() );
    }


    public function testGetForCustomHeaders() : void {
        $r = [];
        $stack = $this->makeHistoryMock( $r );
        $http = new Client( [ 'handler' => $stack ] );
        $cli = new HttpClient( $http );
        $cli->setExtraHeader( 'X-Foo', 'Bar' );
        $cli->get( 'https://www.example.com/foo', i_rHeaders: [ 'X-Baz' => 'Qux' ] );
        $req = $r[ 0 ][ 'request' ];
        self::assertEquals( 'Bar', $req->getHeader( 'X-Foo' )[ 0 ] );
        self::assertEquals( 'Qux', $req->getHeader( 'X-Baz' )[ 0 ] );
    }


    public function testGetForRequestException() : void {
        $response = new Response( body: 'TEST_RESPONSE' );
        $mock = new MockHandler( [
            new RequestException( 'foo', new Request( 'GET', 'https://www.example.com/foo' ),
                response: $response ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $r = $cli->get( '/foo' );
        self::assertSame( 'TEST_RESPONSE', $r->body() );
    }


    public function testGetForStream() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $rsp = $cli->get( '/foo' );
        $st = $rsp->streamBody( 12 );
        self::assertEquals( 200, $rsp->status() );
        self::assertEquals( 'baz', $st );
    }


    public function testGetForTransportError() : void {
        $mock = new MockHandler( [
            new RuntimeException( 'Nope.' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $this->expectException( TransportException::class );
        $cli->get( '/foo' );
    }


    public function testGetWith404Error() : void {
        $mock = new MockHandler( [
            new Response( 404, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $this->expectException( HTTPException::class );
        $cli->get( '/foo' );
    }


    public function testGetWith404ErrorAllowed() : void {
        $mock = new MockHandler( [
            new Response( 404, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $rsp = $cli->get( '/foo', i_bAllowFailure: true );
        self::assertEquals( 404, $rsp->status() );
        self::assertEquals( 'baz', $rsp->body() );
    }


    public function testGetWithFailedRequest() : void {
        $mock = new MockHandler( [
            new RequestException( 'foo', new Request( 'GET', 'https://www.example.com/foo' ) ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $this->expectException( HTTPException::class );
        $cli->get( '/foo' );
    }


    public function testGetWithStreamOption() : void {
        $r = [];
        $stack = $this->makeHistoryMock( $r, [ new Response( 200, [], 'Hello' ) ] );
        $http = new Client( [ 'handler' => $stack ] );
        $cli = new HttpClient( $http );
        $rsp = $cli->get( 'https://www.example.com/foo', i_bStream: true );
        assert( is_array( $r[ 0 ][ 'options' ] ) );
        self::assertTrue( $r[ 0 ][ 'options' ][ 'stream' ] );
        self::assertSame( 'H', $rsp->streamBody( 1 ) );
        self::assertSame( 'e', $rsp->streamBody( 1 ) );
        self::assertSame( 'l', $rsp->streamBody( 1 ) );
        self::assertSame( 'l', $rsp->streamBody( 1 ) );
        self::assertSame( 'o', $rsp->streamBody( 1 ) );
    }


    public function testPost() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $rsp = $cli->post( '/foo', 'body', 'text/plain' );
        self::assertEquals( 200, $rsp->status() );
        self::assertEquals( 'baz', $rsp->body() );
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
        self::assertEquals( 'Bar', $req->getHeader( 'X-Foo' )[ 0 ] );
    }


    public function testPostForInjectedHeader() : void {
        $r = [];
        $stack = $this->makeHistoryMock( $r );
        $http = new Client( [ 'handler' => $stack ] );
        $cli = new HttpClient( $http );
        $cli->post( 'https://www.example.com/foo', '', 'application/json', [ 'X-Foo' => 'Bar' ] );
        $req = $r[ 0 ][ 'request' ];
        self::assertEquals( 'Bar', $req->getHeader( 'X-Foo' )[ 0 ] );
    }


    public function testPostJson() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $rsp = $cli->postJson( '/foo', [ 'a' => 1, 'b' => 2 ] );
        self::assertEquals( 200, $rsp->status() );
        self::assertEquals( 'baz', $rsp->body() );
    }


    public function testSendRequest() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $req = new Request( 'GET', 'https://www.example.com/foo' );
        $rsp = $cli->sendRequest( $req );
        self::assertEquals( 200, $rsp->status() );
        self::assertEquals( 'baz', $rsp->body() );
    }


    public function testSendRequestWithFailedRequest() : void {
        $mock = new MockHandler( [
            new RequestException( 'foo', new Request( 'GET', 'https://www.example.com/foo' ) ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $req = new Request( 'GET', 'https://www.example.com/foo' );
        $this->expectException( HTTPException::class );
        $cli->sendRequest( $req );
    }


    public function testSendRequestWithFailedRequestAllowed() : void {
        $mock = new MockHandler( [
            new Response( 500, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $req = new Request( 'GET', 'https://www.example.com/foo' );
        $rsp = $cli->sendRequest( $req, i_bAllowFailure: true );
        self::assertEquals( 500, $rsp->status() );
        self::assertEquals( 'baz', $rsp->body() );
    }


    public function testSendRequestWithRequestExceptionAndResponse() : void {
        $response = new Response( 404, [], 'Not Found' );
        $mock = new MockHandler( [
            new RequestException( 'Not found', new Request( 'GET', 'https://www.example.com/foo' ), $response ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $req = new Request( 'GET', 'https://www.example.com/foo' );
        $this->expectException( HTTPException::class );
        $this->expectExceptionMessage( 'HTTP Error 404' );
        $cli->sendRequest( $req );
    }


    public function testSendRequestWithTransportError() : void {
        $mock = new MockHandler( [
            new RuntimeException( 'Network error' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http );
        $req = new Request( 'GET', 'https://www.example.com/foo' );
        $this->expectException( TransportException::class );
        $this->expectExceptionMessage( 'Transport Error for GET https://www.example.com/foo: Network error' );
        $cli->sendRequest( $req );
    }


    public function testWithGuzzle() : void {
        $cli = HttpClient::withGuzzle( 'https://www.example.com/' );
        /** @noinspection UnnecessaryAssertionInspection */
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertInstanceOf( HttpClient::class, $cli );
    }


    /** @param array|ArrayAccess<int, array> &$o_rHistory */
    private function makeHistoryMock( array|ArrayAccess &$o_rHistory, ?array $i_nrResponses = null ) : HandlerStack {
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
