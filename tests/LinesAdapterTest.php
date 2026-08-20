<?php


declare( strict_types = 1 );


use JDWX\JsonApiClient\LinesAdapter;
use JDWX\JsonApiClient\MockStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( LinesAdapter::class )]
final class LinesAdapterTest extends TestCase {


    public function testLines() : void {
        $stream = new MockStream( "foo\nbar\nbaz\nqux" );
        $adapter = new LinesAdapter( $stream );
        $lines = iterator_to_array( $adapter->lines() );
        self::assertSame( [ 'foo', 'bar', 'baz', 'qux' ], $lines );
    }


    public function testLinesForBlankLineInMiddle() : void {
        $stream = new MockStream( "foo\n\nbar" );
        $adapter = new LinesAdapter( $stream );
        $lines = iterator_to_array( $adapter->lines(), false );
        self::assertSame( [ 'foo', '', 'bar' ], $lines );
    }


    public function testLinesForEmptyStream() : void {
        $stream = new MockStream( '' );
        $adapter = new LinesAdapter( $stream );
        $lines = iterator_to_array( $adapter->lines(), false );
        self::assertSame( [], $lines );
    }


    public function testLinesForTrailingNewline() : void {
        $stream = new MockStream( "foo\nbar\nbaz\n" );
        $adapter = new LinesAdapter( $stream );
        $lines = iterator_to_array( $adapter->lines(), false );
        self::assertSame( [ 'foo', 'bar', 'baz' ], $lines );
    }


    public function testLinesWithPartialReads() : void {
        $stream = new MockStream( "foo_bar\nbaz_qux\nquux_corge\ngrault_garply" );
        $stream->rReadSizes = [ 3, 4, 5, 6, 7, 8, 9, 10 ];
        $adapter = new LinesAdapter( $stream );
        $lines = iterator_to_array( $adapter->lines() );
        self::assertSame( [ 'foo_bar', 'baz_qux', 'quux_corge', 'grault_garply' ], $lines );
    }


}
