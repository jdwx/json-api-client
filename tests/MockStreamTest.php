<?php


declare( strict_types = 1 );


use JDWX\JsonApiClient\MockStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( MockStream::class )]
final class MockStreamTest extends TestCase {


    public function testClose() : void {
        $ms = new MockStream( 'hello' );
        $ms->close();
        self::assertFalse( $ms->isReadable() );
        self::assertFalse( $ms->isWritable() );
        self::assertFalse( $ms->isSeekable() );
        self::assertSame( '', $ms->getContents() );
    }


    public function testConstruct() : void {
        $ms = new MockStream( 'hello' );
        self::assertSame( 'hello', $ms->getContents() );
    }


    public function testConstructForEmpty() : void {
        $ms = new MockStream( '' );
        self::assertSame( '', $ms->getContents() );
        self::assertTrue( $ms->eof() );
    }


    public function testDetach() : void {
        $ms = new MockStream( 'hello' );
        $result = $ms->detach();
        /** @phpstan-ignore-next-line */
        self::assertNull( $result );
        self::assertFalse( $ms->isReadable() );
        self::assertFalse( $ms->isWritable() );
        self::assertFalse( $ms->isSeekable() );
    }


    public function testEof() : void {
        $ms = new MockStream( 'hi' );
        self::assertFalse( $ms->eof() );
        $ms->read( 2 );
        self::assertTrue( $ms->eof() );
    }


    public function testEofForEmpty() : void {
        $ms = new MockStream( '' );
        self::assertTrue( $ms->eof() );
    }


    public function testGetContents() : void {
        $ms = new MockStream( 'hello world' );
        $ms->read( 6 );
        self::assertSame( 'world', $ms->getContents() );
    }


    public function testGetContentsAdvancesOffset() : void {
        $ms = new MockStream( 'hello' );
        $ms->getContents();
        self::assertTrue( $ms->eof() );
        self::assertSame( '', $ms->getContents() );
    }


    public function testGetMetadata() : void {
        $ms = new MockStream( 'hello' );
        $meta = $ms->getMetadata();
        assert( is_array( $meta ) );
        self::assertFalse( $meta[ 'timed_out' ] );
        self::assertFalse( $meta[ 'blocked' ] );
        self::assertSame( 'rw', $meta[ 'mode' ] );
        self::assertSame( 'MOCK', $meta[ 'wrapper_type' ] );
        self::assertSame( 'MOCK', $meta[ 'stream_type' ] );
    }


    public function testGetMetadataForEof() : void {
        $ms = new MockStream( 'hello' );
        $ms->getContents();
        $meta = $ms->getMetadata();
        assert( is_array( $meta ) );
        self::assertTrue( $meta[ 'eof' ] );
        self::assertSame( 0, $meta[ 'unread_bytes' ] );
    }


    public function testGetMetadataForKey() : void {
        $ms = new MockStream( 'hello' );
        self::assertSame( 'rw', $ms->getMetadata( 'mode' ) );
        self::assertSame( 'MOCK', $ms->getMetadata( 'stream_type' ) );
    }


    public function testGetMetadataForMissingKey() : void {
        $ms = new MockStream( 'hello' );
        self::assertNull( $ms->getMetadata( 'nonexistent_key' ) );
    }


    public function testGetMetadataForSeekableAfterDetach() : void {
        $ms = new MockStream( 'hello' );
        self::assertTrue( $ms->getMetadata( 'seekable' ) );
        $ms->detach();
        self::assertFalse( $ms->getMetadata( 'seekable' ) );
    }


    public function testGetMetadataForUnreadBytes() : void {
        $ms = new MockStream( 'hello' );
        $ms->read( 2 );
        $meta = $ms->getMetadata();
        assert( is_array( $meta ) );
        self::assertSame( 3, $meta[ 'unread_bytes' ] );
    }


    public function testGetSize() : void {
        $ms = new MockStream( 'hello' );
        self::assertSame( 5, $ms->getSize() );
    }


    public function testIsReadable() : void {
        $ms = new MockStream( 'hello' );
        self::assertTrue( $ms->isReadable() );
        $ms->detach();
        self::assertFalse( $ms->isReadable() );
    }


    public function testIsSeekable() : void {
        $ms = new MockStream( 'hello' );
        self::assertTrue( $ms->isSeekable() );
        $ms->detach();
        self::assertFalse( $ms->isSeekable() );
    }


    public function testIsWritable() : void {
        $ms = new MockStream( 'hello' );
        self::assertTrue( $ms->isWritable() );
        $ms->detach();
        self::assertFalse( $ms->isWritable() );
    }


    public function testRead() : void {
        $ms = new MockStream( 'hello' );
        self::assertSame( 'hel', $ms->read( 3 ) );
        self::assertSame( 'lo', $ms->read( 3 ) );
    }


    public function testReadForOneByteAtATime() : void {
        $ms = new MockStream( 'abc' );
        self::assertSame( 'a', $ms->read( 1 ) );
        self::assertSame( 'b', $ms->read( 1 ) );
        self::assertSame( 'c', $ms->read( 1 ) );
        self::assertTrue( $ms->eof() );
    }


    public function testReadForRequestShorterThanPresetSize() : void {
        $ms = new MockStream( 'abcdefgh' );
        $ms->rReadSizes = [ 6 ];

        # Requested 3 is shorter than the preset 6, so the preset is split
        # and the remaining 3 bytes stay queued for the next read.
        self::assertSame( 'abc', $ms->read( 3 ) );
        self::assertSame( [ 3 ], $ms->rReadSizes );

        # The remainder still caps a larger request.
        self::assertSame( 'def', $ms->read( 5 ) );

        # With no presets left, reads revert to the requested length.
        self::assertSame( 'gh', $ms->read( 5 ) );
        self::assertTrue( $ms->eof() );
    }


    public function testRewind() : void {
        $ms = new MockStream( 'hello' );
        $ms->read( 3 );
        self::assertSame( 3, $ms->tell() );
        $ms->rewind();
        self::assertSame( 0, $ms->tell() );
        self::assertSame( 'hello', $ms->getContents() );
    }


    public function testSeekCur() : void {
        $ms = new MockStream( 'hello world' );
        $ms->read( 3 );
        $ms->seek( 3, SEEK_CUR );
        self::assertSame( 6, $ms->tell() );
        self::assertSame( 'world', $ms->getContents() );
    }


    public function testSeekEnd() : void {
        $ms = new MockStream( 'hello world' );
        $ms->seek( -5, SEEK_END );
        self::assertSame( 6, $ms->tell() );
        self::assertSame( 'world', $ms->getContents() );
    }


    public function testSeekForInvalidWhence() : void {
        $ms = new MockStream( 'hello' );
        $this->expectException( InvalidArgumentException::class );
        $ms->seek( 0, 999 );
    }


    public function testSeekSet() : void {
        $ms = new MockStream( 'hello world' );
        $ms->seek( 6 );
        self::assertSame( 6, $ms->tell() );
        self::assertSame( 'world', $ms->getContents() );
    }


    public function testTell() : void {
        $ms = new MockStream( 'hello' );
        self::assertSame( 0, $ms->tell() );
        $ms->read( 3 );
        self::assertSame( 3, $ms->tell() );
    }


    public function testToString() : void {
        $ms = new MockStream( 'hello' );
        self::assertSame( 'hello', (string) $ms );
    }


    public function testToStringAdvancesOffset() : void {
        $ms = new MockStream( 'hello' );
        $x = (string) $ms;
        unset( $x );
        self::assertTrue( $ms->eof() );
        self::assertSame( 5, $ms->tell() );
    }


    public function testWrite() : void {
        $ms = new MockStream( 'hello' );
        $written = $ms->write( ' world' );
        self::assertSame( 6, $written );
        $ms->rewind();
        self::assertSame( 'hello world', $ms->getContents() );
    }


    public function testWriteForEmpty() : void {
        $ms = new MockStream( '' );
        $ms->write( 'hello' );
        $ms->rewind();
        self::assertSame( 'hello', $ms->getContents() );
    }


}
