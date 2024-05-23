<?php


declare( strict_types = 1 );


use JDWX\JsonApiClient\Response;
use PHPUnit\Framework\TestCase;


require_once __DIR__ . '/MyTestStream.php';


class ResponseTest extends TestCase {


    public function testBody() : void {
        $mts = new MyTestStream( 'foo' );
        $rsp = new Response( 12345, [], $mts );
        self::assertEquals( 'foo', $rsp->body() );

        # body() is repeatable.
        self::assertEquals( 'foo', $rsp->body() );
    }


    public function testGetHeader() : void {
        $mts = new MyTestStream( 'foo' );
        $rsp = new Response( 12345, [ 'foo' => [ 'bar', 'baz' ] ], $mts );
        self::assertEquals( [ 'bar', 'baz' ], $rsp->getHeader( 'foo' ) );
        self::assertNull( $rsp->getHeader( 'bar' ) );
    }


    public function testGetHeaderForWrongCase() : void {
        $mts = new MyTestStream( 'foo' );
        $rsp = new Response( 12345, [ 'foo' => [ 'bar', 'baz' ] ], $mts );
        self::assertEquals( [ 'bar', 'baz' ], $rsp->getHeader( 'Foo' ) );
    }


    public function testGetOneHeader() : void {
        $mts = new MyTestStream( 'foo' );
        $rsp = new Response( 12345, [ 'foo' => [ 'bar' ] ], $mts );
        self::assertEquals( 'bar', $rsp->getOneHeader( 'foo' ) );
        self::assertNull( $rsp->getOneHeader( 'bar' ) );

        $rsp = new Response( 12345, [ 'foo' => [ 'bar', 'baz' ] ], $mts );
        $this->expectException( RuntimeException::class );
        $r = $rsp->getOneHeader( 'foo' );
        var_dump( $r );
    }


    public function testGetOneHeaderEx() : void {
        $mts = new MyTestStream( 'foo' );
        $rsp = new Response( 12345, [ 'foo' => [ 'bar' ] ], $mts );
        self::assertEquals( 'bar', $rsp->getOneHeaderEx( 'foo' ) );

        $rsp = new Response( 12345, [], $mts );
        $this->expectException( RuntimeException::class );
        $r = $rsp->getOneHeaderEx( 'foo' );
        var_dump( $r );
    }


    public function testHasHeader() : void {
        $mts = new MyTestStream( 'foo' );
        $rsp = new Response( 12345, [ 'foo' => [ 'bar' ] ], $mts );
        self::assertTrue( $rsp->hasHeader( 'foo' ) );
        self::assertFalse( $rsp->hasHeader( 'bar' ) );
    }


    public function testJson() : void {
        $mts = new MyTestStream( '{"foo":"bar"}' );
        $rsp = new Response( 12345, [], $mts );
        self::assertEquals( [ 'foo' => 'bar' ], $rsp->json() );

        $mts = new MyTestStream( 'foo' );
        $rsp = new Response( 12345, [], $mts );
        $this->expectException( JsonException::class );
        $rsp->json();
    }


    public function testStatus() : void {
        $mts = new MyTestStream( 'foo' );
        $rsp = new Response( 12345, [], $mts );
        self::assertEquals( 12345, $rsp->status() );
    }


    public function testStreamBody() : void {
        $mts = new MyTestStream( 'foo' );
        $rsp = new Response( 12345, [], $mts );
        self::assertEquals( 'f', $rsp->streamBody( 1 ) );
        self::assertEquals( 'o', $rsp->streamBody( 1 ) );
        self::assertEquals( 'o', $rsp->streamBody( 1 ) );
        self::assertNull( $rsp->streamBody( 1 ) );

        # Body cannot now be retrieved.
        $this->expectException( RuntimeException::class );
        $rsp->body();
    }


    public function testStreamBodyForNotReadable() : void {
        $mts = new MyTestStream( 'foo' );
        $mts->setReadable( false );
        $rsp = new Response( 12345, [], $mts );
        $this->expectException( RuntimeException::class );
        $rsp->streamBody( 1 );
    }


    public function testToString() : void {
        $mts = new MyTestStream( 'baz' );
        $rsp = new Response( 12345, [ 'foo' => [ 'bar', 'qux' ] ], $mts );
        $stResponse = "status: 12345\nfoo: bar, qux\n\nbaz";
        self::assertEquals( $stResponse, (string) $rsp );
    }


    public function testToStringForBodyAlreadyGone() : void {
        $mts = new MyTestStream( 'baz' );
        $rsp = new Response( 12345, [ 'foo' => [ 'bar', 'qux' ] ], $mts );
        $rsp->streamBody( 1024 );
        $rsp->streamBody( 1024 );
        $stResponse = "status: 12345\nfoo: bar, qux\n\n[Body not available]";
        self::assertEquals( $stResponse, (string) $rsp );
    }


}
