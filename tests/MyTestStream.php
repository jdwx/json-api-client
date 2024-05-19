<?php


use Psr\Http\Message\StreamInterface;


class MyTestStream implements StreamInterface {


    private int $uOffset = 0;

    private bool $bReadable = true;


    public function __construct( private readonly string $stContents ) {
    }


    public function __toString() : string {
        return $this->stContents;
    }


    public function close() : void {
    }


    public function detach() : null {
        return null;
    }


    public function getSize() : ?int {
        return strlen( $this->stContents );
    }


    public function tell() : int {
        return 0;
    }


    public function eof() : bool {
        return $this->uOffset >= strlen( $this->stContents );
    }


    public function isSeekable() : bool {
        return false;
    }


    public function seek( $offset, $whence = SEEK_SET ) : void {
    }


    public function rewind() : void {
    }


    public function isWritable() : bool {
        return false;
    }


    public function write( $string ) : int {
        return 0;
    }


    public function isReadable() : bool {
        return $this->bReadable;
    }


    public function read( $length ) : string {
        $st = substr( $this->stContents, $this->uOffset, $length );
        $this->uOffset += strlen( $st );
        if ( $this->uOffset >= strlen( $this->stContents ) ) {
            $this->uOffset = strlen( $this->stContents );
        }
        return $st;
    }


    public function getContents() : string {
        return $this->stContents;
    }


    public function getMetadata( $key = null ) : null {
        return null;
    }


    public function setReadable( bool $i_bReadable ) : void {
        $this->bReadable = $i_bReadable;
    }


}