<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use JDWX\Json\Json;


class Response extends \JDWX\HttpClient\Response implements ResponseInterface {


    private mixed $json = null;


    public function isJson() : bool {
        return $this->isContentTypeLoose( 'application', 'json' );
    }


    public function json() : mixed {
        if ( is_null( $this->json ) ) {
            $this->json = Json::decode( $this->body() );
        }
        return $this->json;
    }


    /** @return \Generator<mixed> */
    public function streamJson( bool        $i_bSkipOuterArray = false,
                                int         $i_uBufferSize = StreamInput::DEFAULT_BUFFER_SIZE,
                                int         $i_uMaxReadSize = StreamInput::DEFAULT_MAX_READ_SIZE,
                                string|null $i_elementDelimiters = null ) : \Generator {
        $stream = $this->getBody();
        if ( $stream->isSeekable() ) {
            $stream->rewind();
        }
        yield from ( new StreamInput( $stream, $i_bSkipOuterArray, $i_uBufferSize,
            $i_uMaxReadSize, $i_elementDelimiters ) );
    }


}
