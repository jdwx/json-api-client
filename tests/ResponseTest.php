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


    public function testIsContentType() : void {
        $mts = new MyTestStream( 'foo' );
        $rsp = new Response( 12345, [ 'content-type' => [ 'text/plain' ] ], $mts );
        self::assertTrue( $rsp->isContentType( 'text', 'plain' ) );
        self::assertTrue( $rsp->isContentType( 'text/plain' ) );
        self::assertFalse( $rsp->isContentType( 'text', 'html' ) );
        self::assertFalse( $rsp->isContentType( 'text/plainx' ) );

        $rsp = new Response( 12345, [ 'content-type' => [ 'text/plain; charset=utf-8' ] ], $mts );
        self::assertTrue( $rsp->isContentType( 'text', 'plain' ) );
        self::assertTrue( $rsp->isContentType( 'text/plain' ) );
        self::assertFalse( $rsp->isContentType( 'text', 'html' ) );
        self::assertFalse( $rsp->isContentType( 'text/plainx' ) );

        $rsp = new Response( 12345, [], $mts );
        self::assertFalse( $rsp->isContentType( 'text', 'plain' ) );
    }


    public function testIsContentTypeLoose() : void {
        $mts = new MyTestStream( 'foo' );
        $rsp = new Response( 12345, [ 'content-type' => [ 'text/plain' ] ], $mts );
        self::assertTrue( $rsp->isContentTypeLoose( 'text', 'plain' ) );
        self::assertFalse( $rsp->isContentTypeLoose( 'text', 'plainx' ) );
        self::assertFalse( $rsp->isContentTypeLoose( 'text', 'html' ) );

        $rsp = new Response( 12345, [ 'content-type' => [ 'text/plain; charset=utf-8' ] ], $mts );
        self::assertTrue( $rsp->isContentTypeLoose( 'text', 'plain' ) );
        self::assertFalse( $rsp->isContentTypeLoose( 'text', 'plainx' ) );
        self::assertFalse( $rsp->isContentTypeLoose( 'text', 'html' ) );

        $rsp = new Response( 12345, [], $mts );
        self::assertFalse( $rsp->isContentTypeLoose( 'text', 'plain' ) );

        $rsp = new Response( 12345, [ 'content-type' => [ 'text/plain+json' ] ], $mts );
        self::assertTrue( $rsp->isContentTypeLoose( 'text', 'plain' ) );
        self::assertTrue( $rsp->isContentTypeLoose( 'text', 'json' ) );
        self::assertFalse( $rsp->isContentTypeLoose( 'text', 'jsonx' ) );

    }


    public function testIsContentTypeSubtype() : void {
        $mts = new MyTestStream( 'foo' );
        $rsp = new Response( 12345, [ 'content-type' => [ 'text/plain' ] ], $mts );
        self::assertTrue( $rsp->isContentTypeSubtype( 'plain' ) );
        self::assertFalse( $rsp->isContentTypeSubtype( 'html' ) );
        self::assertFalse( $rsp->isContentTypeSubtype( 'plainx' ) );

        $rsp = new Response( 12345, [ 'content-type' => [ 'text/plain; charset=utf-8' ] ], $mts );
        self::assertTrue( $rsp->isContentTypeSubtype( 'plain' ) );
        self::assertFalse( $rsp->isContentTypeSubtype( 'html' ) );

        $rsp = new Response( 12345, [], $mts );
        self::assertFalse( $rsp->isContentTypeSubtype( 'plain' ) );

        $rsp = new Response( 12345, [ 'content-type' => [ 'foo-bar' ] ], $mts );
        self::assertFalse( $rsp->isContentTypeSubtype( 'plain' ) );
    }


    public function testIsContentTypeType() : void {
        $mts = new MyTestStream( 'foo' );
        $rsp = new Response( 12345, [ 'content-type' => [ 'text/plain' ] ], $mts );
        self::assertTrue( $rsp->isContentTypeType( 'text' ) );
        self::assertFalse( $rsp->isContentTypeType( 'text/plain' ) );
        self::assertFalse( $rsp->isContentTypeType( 'text/html' ) );
        self::assertFalse( $rsp->isContentTypeType( 'text/plainx' ) );

        $rsp = new Response( 12345, [ 'content-type' => [ 'text/plain; charset=utf-8' ] ], $mts );
        self::assertTrue( $rsp->isContentTypeType( 'text' ) );
        self::assertFalse( $rsp->isContentTypeType( 'text/plain' ) );

        $rsp = new Response( 12345, [], $mts );
        self::assertFalse( $rsp->isContentTypeType( 'text' ) );
    }


    public function testJson() : void {
        $mts = new MyTestStream( '{"foo":"bar"}' );
        $rsp = new Response( 12345, [], $mts );
        self::assertEquals( [ 'foo' => 'bar' ], $rsp->json() );

        # json() is repeatable.
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
