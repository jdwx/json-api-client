<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


interface ResponseInterface extends \JDWX\HttpClient\ResponseInterface {


    public function isJson() : bool;


    public function json() : mixed;


    /** @return \Generator<mixed> */
    public function streamJson( bool        $i_bSkipOuterArray = false,
                                int         $i_uBufferSize = StreamInput::DEFAULT_BUFFER_SIZE,
                                int         $i_uMaxReadSize = StreamInput::DEFAULT_MAX_READ_SIZE,
                                string|null $i_elementDelimiters = null ) : \Generator;


}