<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use Psr\Http\Message\StreamInterface;


class MockStream implements StreamInterface {


    private int $uOffset = 0;

    private bool $bDetached = false;


    public function __construct( private string $stStream ) {}


    public function __toString() : string {
        $this->uOffset = strlen( $this->stStream );
        return $this->stStream;
    }


    public function close() : void {
        $this->stStream = '';
        $this->bDetached = true;
    }


    public function detach() : null {
        $this->close();
        return null;
    }


    public function eof() : bool {
        return $this->uOffset >= strlen( $this->stStream );
    }


    public function getContents() : string {
        $st = substr( $this->stStream, $this->uOffset );
        $this->uOffset = strlen( $this->stStream );
        return $st;
    }


    public function getMetadata( ?string $key = null ) : array|bool|int|string|null {
        $rMetadata = [
            'timed_out' => false,
            'blocked' => false,
            'eof' => $this->eof(),
            'wrapper_data' => [
                'MOCK/1.0 200 Sure, why not?',
                'Connection: close',
            ],
            'wrapper_type' => 'MOCK',
            'stream_type' => 'MOCK',
            'mode' => 'rw',
            'unread_bytes' => strlen( $this->stStream ) - $this->uOffset,
            'seekable' => ! $this->bDetached,
            'uri' => 'nope',
        ];
        if ( is_string( $key ) ) {
            return $rMetadata[ $key ] ?? null;
        }
        return $rMetadata;
    }


    public function getSize() : int {
        return strlen( $this->stStream );
    }


    public function isReadable() : bool {
        return ! $this->bDetached;
    }


    public function isSeekable() : bool {
        return ! $this->bDetached;
    }


    public function isWritable() : bool {
        return ! $this->bDetached;
    }


    public function read( int $length ) : string {
        $st = substr( $this->stStream, $this->uOffset, $length );
        $this->uOffset += strlen( $st );
        return $st;
    }


    public function rewind() : void {
        $this->uOffset = 0;
    }


    public function seek( int $offset, int $whence = SEEK_SET ) : void {
        if ( $whence === SEEK_SET ) {
            $this->uOffset = $offset;
        } elseif ( $whence === SEEK_CUR ) {
            $this->uOffset += $offset;
        } elseif ( $whence === SEEK_END ) {
            $this->uOffset = strlen( $this->stStream ) + $offset;
        } else {
            throw new \InvalidArgumentException( 'Invalid whence value' );
        }
    }


    public function tell() : int {
        return $this->uOffset;
    }


    public function write( string $string ) : int {
        $this->stStream .= $string;
        return strlen( $string );
    }


}
