<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use JDWX\Json\Streaming\AbstractInput;
use Psr\Http\Message\StreamInterface;


class StreamInput extends AbstractInput {


    public function __construct( private readonly StreamInterface $stream,
                                 bool                             $i_bSkipOuterArray = false,
                                 int                              $i_uBufferSize = self::DEFAULT_BUFFER_SIZE,
                                 int                              $i_uMaxReadSize = self::DEFAULT_MAX_READ_SIZE,
                                 string|null                      $i_elementDelimiters = null ) {
        parent::__construct( $i_bSkipOuterArray, $i_uBufferSize, $i_uMaxReadSize, $i_elementDelimiters );
    }


    protected function eof() : bool {
        return $this->stream->eof();
    }


    protected function read( int $i_uLength ) : string {
        return $this->stream->read( $i_uLength );
    }


}
