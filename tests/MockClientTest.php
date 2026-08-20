<?php


declare( strict_types = 1 );


use GuzzleHttp\Psr7\Request;
use JDWX\JsonApiClient\MockClient;
use JDWX\JsonApiClient\MockStream;
use JDWX\JsonApiClient\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( MockClient::class )]
final class MockClientTest extends TestCase {


    public function testExtraHeaders() : void {
        $cli = new MockClient( null );
        $cli->setExtraHeader( 'Authorization', 'Bearer token123' );
        $cli->queueResponse( 'ok' );
        $cli->request( 'GET', '/secure' );
        $req = $cli->shiftRequestArray();
        self::assertSame( 'Bearer token123', $req[ 'headers' ][ 'Authorization' ] );
    }


    public function testExtraHeadersForOverwrite() : void {
        $cli = new MockClient( null );
        $cli->setExtraHeader( 'Authorization', 'Bearer extra' );
        $cli->queueResponse( 'ok' );
        $cli->request( 'GET', '/secure', i_rHeaders: [ 'Authorization' => 'Bearer override' ] );
        $req = $cli->shiftRequestArray();
        self::assertSame( 'Bearer override', $req[ 'headers' ][ 'Authorization' ] );
    }


    public function testGet() : void {
        $cli = new MockClient( null );
        $cli->queueResponse( 'get-result' );
        $rsp = $cli->get( '/path' );
        self::assertSame( 'get-result', $rsp->body() );
        $req = $cli->shiftRequestArray();
        self::assertSame( 'GET', $req[ 'method' ] );
        self::assertSame( '/path', $req[ 'path' ] );
    }


    public function testMixedRequestTypes() : void {
        $cli = new MockClient( null );
        $cli->queueResponse( 'one' );
        $cli->queueResponse( 'two' );

        $cli->request( 'GET', '/first' );
        $psr = new Request( 'DELETE', 'https://example.com/second' );
        $cli->sendRequest( $psr );

        $first = $cli->shiftRequestArray();
        self::assertSame( 'GET', $first[ 'method' ] );

        $second = $cli->shiftRequestObject();
        self::assertSame( 'DELETE', $second->getMethod() );

        self::assertNull( $cli->shiftRequest() );
    }


    public function testPost() : void {
        $cli = new MockClient( null );
        $cli->queueResponse( 'post-result' );
        $rsp = $cli->post( '/path', 'the-body', 'text/plain' );
        self::assertSame( 'post-result', $rsp->body() );
        $req = $cli->shiftRequestArray();
        self::assertSame( 'POST', $req[ 'method' ] );
        self::assertSame( '/path', $req[ 'path' ] );
        self::assertSame( 'the-body', $req[ 'body' ] );
        self::assertSame( 'text/plain', $req[ 'headers' ][ 'Content-Type' ] );
    }


    public function testPostJson() : void {
        $cli = new MockClient( null );
        $cli->queueResponse( 'json-result' );
        $rsp = $cli->postJson( '/path', [ 'key' => 'value' ] );
        self::assertSame( 'json-result', $rsp->body() );
        $req = $cli->shiftRequest();
        assert( is_array( $req ) );
        self::assertSame( 'POST', $req[ 'method' ] );
        self::assertSame( '{"key":"value"}', $req[ 'body' ] );
        self::assertSame( 'application/json', $req[ 'headers' ][ 'Content-Type' ] );
    }


    public function testQueueResponseForMultiple() : void {
        $cli = new MockClient( null );
        $cli->queueResponse( 'first' );
        $cli->queueResponse( 'second' );
        $cli->queueResponse( 'third' );
        self::assertSame( 'first', $cli->request( 'GET', '/1' )->body() );
        self::assertSame( 'second', $cli->request( 'GET', '/2' )->body() );
        self::assertSame( 'third', $cli->request( 'GET', '/3' )->body() );
    }


    public function testQueueResponseForString() : void {
        $cli = new MockClient( null );
        $cli->queueResponse( 'hello world' );
        $rsp = $cli->request( 'GET', '/test' );
        self::assertSame( 200, $rsp->status() );
        self::assertSame( 'hello world', $rsp->body() );
    }


    public function testRequest() : void {
        $cli = new MockClient( null );
        $stream = new MockStream( '{"ok":true}' );
        $rsp = new Response( 200, [ 'Content-Type' => [ 'application/json' ] ], $stream );
        $cli->queueResponse( $rsp );
        $result = $cli->request( 'GET', '/foo' );
        self::assertSame( $rsp, $result );
    }


    public function testRequestForNoResponsesQueued() : void {
        $cli = new MockClient( null );
        $this->expectException( RuntimeException::class );
        $cli->request( 'GET', '/foo' );
    }


    public function testRequestForQueuedThrowable() : void {
        $cli = new MockClient( null );
        $cli->queueResponse( new \JDWX\JsonApiClient\HTTPException( 'test error' ) );
        $this->expectException( \JDWX\JsonApiClient\HTTPException::class );
        $this->expectExceptionMessage( 'test error' );
        $cli->request( 'GET', '/foo' );
    }


    public function testRequestRecordsParameters() : void {
        $cli = new MockClient( null );
        $cli->queueResponse( 'ok' );
        $cli->request( 'POST', '/bar', 'body-data', [ 'X-Foo' => 'Bar' ], true, true );
        $req = $cli->shiftRequest();
        assert( is_array( $req ) );
        self::assertSame( 'POST', $req[ 'method' ] );
        self::assertSame( '/bar', $req[ 'path' ] );
        self::assertSame( 'body-data', $req[ 'body' ] );
        self::assertSame( [ 'X-Foo' => 'Bar' ], $req[ 'headers' ] );
        self::assertTrue( $req[ 'stream' ] );
    }


    public function testSendRequest() : void {
        $cli = new MockClient( null );
        $cli->queueResponse( 'ok' );
        $req = new Request( 'GET', 'https://example.com/foo' );
        $rsp = $cli->sendRequest( $req );
        self::assertSame( 200, $rsp->status() );
        self::assertSame( 'ok', $rsp->body() );
    }


    public function testSendRequestForExtraHeaderOverwrite() : void {
        $cli = new MockClient( null );
        $cli->setExtraHeader( 'Authorization', 'Bearer extra' );
        $cli->queueResponse( 'ok' );
        $req = new Request( 'GET', 'https://example.com/secure', [ 'Authorization' => 'Bearer override' ] );
        $cli->sendRequest( $req );
        $recorded = $cli->shiftRequestObject();
        self::assertSame( 'Bearer override', $recorded->getHeaderLine( 'Authorization' ) );
    }


    public function testSendRequestForExtraHeaders() : void {
        $cli = new MockClient( null );
        $cli->setExtraHeader( 'Authorization', 'Bearer token123' );
        $cli->queueResponse( 'ok' );
        $req = new Request( 'GET', 'https://example.com/secure' );
        $cli->sendRequest( $req );
        $recorded = $cli->shiftRequestObject();
        self::assertSame( 'Bearer token123', $recorded->getHeaderLine( 'Authorization' ) );
    }


    public function testSendRequestRecordsRequest() : void {
        $cli = new MockClient( null );
        $cli->queueResponse( 'ok' );
        $req = new Request( 'PUT', 'https://example.com/bar' );
        $cli->sendRequest( $req );
        $recorded = $cli->shiftRequestObject();
        self::assertSame( 'PUT', $recorded->getMethod() );
    }


    public function testShiftRequest() : void {
        $cli = new MockClient( null );
        $cli->queueResponse( 'first' );
        $cli->queueResponse( 'second' );
        $cli->request( 'GET', '/a' );
        $cli->request( 'POST', '/b', 'body' );
        $first = $cli->shiftRequestArray();
        self::assertSame( 'GET', $first[ 'method' ] );
        $second = $cli->shiftRequestArray();
        self::assertSame( 'POST', $second[ 'method' ] );
    }


    public function testShiftRequestArrayForEmpty() : void {
        $cli = new MockClient( null );
        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Expected array, got null' );
        $cli->shiftRequestArray();
    }


    public function testShiftRequestArrayForObjectRequest() : void {
        $cli = new MockClient( null );
        $cli->queueResponse( 'foo' );
        $cli->sendRequest( new Request( 'GET', 'https://example.com/' ) );
        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Expected array, got GuzzleHttp\Psr7\Request' );
        $cli->shiftRequestArray();
    }


    public function testShiftRequestForEmpty() : void {
        $cli = new MockClient( null );
        self::assertNull( $cli->shiftRequest() );
    }


    public function testShiftRequestObjectForArrayRequest() : void {
        $cli = new MockClient( null );
        $cli->queueResponse( 'foo' );
        $cli->request( 'GET', '/a' );
        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Expected RequestInterface, got array' );
        $cli->shiftRequestObject();
    }


    public function testShiftRequestObjectForEmpty() : void {
        $cli = new MockClient( null );
        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Expected RequestInterface, got null' );
        $cli->shiftRequestObject();
    }


}
