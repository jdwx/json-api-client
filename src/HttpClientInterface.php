<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use JsonException;
use Psr\Http\Message\RequestInterface;


interface HttpClientInterface {


    public function get( string $i_stPath, array $i_rHeaders = [],
                         bool   $i_bAllowFailure = false, bool $i_bStream = false ) : Response;


    public function post( string $i_stPath, string $i_stBody, string $i_stContentType, array $i_rHeaders = [],
                          bool   $i_bAllowFailure = false, bool $i_bStream = false ) : Response;


    /**
     * @param string  $i_stPath
     * @param mixed[] $i_rJson         JSON to send as the request body.
     * @param string  $i_stContentType Content type of the request body.
     * @param array   $i_rHeaders      Additional headers to send. (As header => value pairs.)
     * @param bool    $i_bAllowFailure If true, don't throw an exception on non-2xx status.
     * @param bool    $i_bStream       If true, don't wait for the entire response body.
     * @return Response
     * @throws JsonException
     */
    public function postJson( string $i_stPath, array $i_rJson,
                              string $i_stContentType = 'application/json',
                              array  $i_rHeaders = [],
                              bool   $i_bAllowFailure = false,
                              bool   $i_bStream = false ) : Response;


    /** @param array<string, string> $i_rHeaders */
    public function request( string  $i_stMethod, string $i_stPath,
                             ?string $i_nstBody = null, array $i_rHeaders = [],
                             bool    $i_bAllowFailure = false, bool $i_bStream = false ) : Response;


    public function sendRequest( RequestInterface $i_request, bool $i_bAllowFailure = false ) : Response;


    public function setBasicAuth( string $i_stUser, string $i_stPassword ) : void;


    public function setBearerToken( string $i_stToken ) : void;


    public function setExtraHeader( string $i_stHeader, string $i_stValue ) : void;


}