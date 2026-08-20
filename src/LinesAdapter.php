<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use Psr\Http\Message\StreamInterface;


class LinesAdapter {


    private string $stBuffer = '';


    public function __construct( private readonly StreamInterface $stream ) {}


    /** @return iterable<string> */
    public function lines() : iterable {
        while ( true ) {
            $bu = strpos( $this->stBuffer, "\n" );
            if ( is_int( $bu ) ) {
                $stOut = substr( $this->stBuffer, 0, $bu );
                $this->stBuffer = substr( $this->stBuffer, $bu + 1 );
                yield $stOut;
                continue;
            }

            if ( $this->stream->eof() ) {
                if ( $this->stBuffer ) {
                    yield $this->stBuffer;
                }
                break;
            }

            $this->stBuffer .= $this->stream->read( 4096 );
        }
    }


}
