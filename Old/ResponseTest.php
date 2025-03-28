<?php


declare( strict_types = 1 );


namespace Old;


use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Support\MyTestStream;


require_once __DIR__ . '/MyTestStream.php';


#[CoversClass( Response::class )]
final class ResponseTest {


    public function testHasHeader() : void {
        $mts = new MyTestStream( 'foo' );
        $rsp = new Response( 12345, [ 'foo' => [ 'bar' ] ], $mts );
        self::assertTrue( $rsp->hasHeader( 'foo' ) );
        self::assertFalse( $rsp->hasHeader( 'bar' ) );
    }


    public function testIsJson() : void {
        $mts = new MyTestStream( '{"foo":"bar"}' );
        $rsp = new Response( 12345, [ 'content-type' => [ 'application/json' ] ], $mts );
        self::assertTrue( $rsp->isJson() );

        $rsp = new Response( 12345, [ 'content-type' => [ 'application/json; charset=utf-8' ] ], $mts );
        self::assertTrue( $rsp->isJson() );

        $rsp = new Response( 12345, [ 'content-type' => [ 'application/json+foo' ] ], $mts );
        self::assertTrue( $rsp->isJson() );

        $rsp = new Response( 12345, [ 'content-type' => [ 'application/json+foo; charset=utf-8' ] ], $mts );
        self::assertTrue( $rsp->isJson() );

        $rsp = new Response( 12345, [ 'content-type' => [ 'application/jsonx' ] ], $mts );
        self::assertFalse( $rsp->isJson() );
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
