<?php


declare( strict_types = 1 );


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use JDWX\JsonApiClient\HttpClient;
use JDWX\JsonApiClient\HTTPException;
use JDWX\JsonApiClient\TransportException;
use PHPUnit\Framework\TestCase;


// require_once __DIR__ . '/MyTestClient.php';


class HttpClientTest extends TestCase {


    public function testGet() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http, 'https://www.example.com/' );
        $rsp = $cli->get( '/foo' );
        static::assertEquals( 200, $rsp->status() );
        static::assertEquals( 'baz', $rsp->body() );
    }


    public function testGetWith404Error() : void {
        $mock = new MockHandler( [
            new Response( 404, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http, 'https://www.example.com/' );
        self::expectException( HTTPException::class );
        $cli->get( '/foo' );
    }


    public function testGetWith404ErrorAllowed() : void {
        $mock = new MockHandler( [
            new Response( 404, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http, 'https://www.example.com/' );
        $rsp = $cli->get( '/foo', true );
        static::assertEquals( 404, $rsp->status() );
        static::assertEquals( 'baz', $rsp->body() );
    }


    public function testGetWithFailedRequest() : void {
        $mock = new MockHandler( [
            new RequestException( 'foo', new Request( 'GET', 'https://www.example.com/foo' ) )
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http, 'https://www.example.com/' );
        self::expectException( TransportException::class );
        $cli->get( '/foo' );
    }


    public function testPost() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http, 'https://www.example.com/' );
        $rsp = $cli->post( '/foo', 'body', 'text/plain' );
        static::assertEquals( 200, $rsp->status() );
        static::assertEquals( 'baz', $rsp->body() );
    }


    public function testPostJson() : void {
        $mock = new MockHandler( [
            new Response( 200, [ 'Foo' => 'Bar' ], 'baz' ),
        ] );
        $http = new Client( [ 'handler' => $mock ] );
        $cli = new HttpClient( $http, 'https://www.example.com/' );
        $rsp = $cli->postJson( '/foo', [ 'a' => 1, 'b' => 2 ] );
        static::assertEquals( 200, $rsp->status() );
        static::assertEquals( 'baz', $rsp->body() );
    }


    public function testWithGuzzle() : void {
        $cli = HttpClient::withGuzzle( 'https://www.example.com/' );
        static::assertInstanceOf( HttpClient::class, $cli );
    }


}
