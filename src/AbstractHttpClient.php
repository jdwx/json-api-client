<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use JDWX\Json\Json;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;


abstract class AbstractHttpClient implements HttpClientInterface {


    /** @var array<string, string> $rExtraHeaders */
    private array $rExtraHeaders = [];


    public function __construct( protected ?LoggerInterface $log ) {}


    public function get( string $i_stPath, array $i_rHeaders = [],
                         bool   $i_bAllowFailure = false, bool $i_bStream = false ) : Response {
        return $this->request( 'GET', $i_stPath, i_rHeaders: $i_rHeaders,
            i_bAllowFailure: $i_bAllowFailure, i_bStream: $i_bStream );
    }


    /** @return array<string, string> */
    public function getExtraHeaders() : array {
        return $this->rExtraHeaders;
    }


    public function post( string $i_stPath, string $i_stBody, string $i_stContentType, array $i_rHeaders = [],
                          bool   $i_bAllowFailure = false, bool $i_bStream = false ) : Response {
        $i_rHeaders[ 'Content-Type' ] = $i_stContentType;
        return $this->request( 'POST', $i_stPath, $i_stBody,
            $i_rHeaders, $i_bAllowFailure, $i_bStream );
    }


    /**
     * @param string $i_stPath
     * @param mixed[] $i_rJson JSON to send as the request body.
     * @param string $i_stContentType Content type of the request body.
     * @param array $i_rHeaders Additional headers to send. (As header => value pairs.)
     * @param bool $i_bAllowFailure If true, don't throw an exception on non-2xx status.
     * @param bool $i_bStream If true, don't wait for the entire response body.
     * @return Response
     * @throws JsonException
     */
    public function postJson( string $i_stPath, array $i_rJson,
                              string $i_stContentType = 'application/json',
                              array  $i_rHeaders = [],
                              bool   $i_bAllowFailure = false,
                              bool   $i_bStream = false ) : Response {
        return $this->post( $i_stPath, Json::encode( $i_rJson ), $i_stContentType,
            $i_rHeaders, $i_bAllowFailure, $i_bStream );
    }


    public function setExtraHeader( string $i_stHeader, string $i_stValue ) : void {
        $this->rExtraHeaders[ $i_stHeader ] = $i_stValue;
    }


    protected function handleResponse( ResponseInterface $response, bool $i_bAllowFailure,
                                       string            $i_stMethod, string $i_stPath ) : Response {
        $uStatus = $response->getStatusCode();
        $uFirst = intval( $uStatus / 100 );
        $rHeaders = $response->getHeaders();
        $body = $response->getBody();
        if ( 2 !== $uFirst && ! $i_bAllowFailure ) {
            $stBody = $body->getContents() ?: '(no body)';
            $stHeaders = '';
            foreach ( $rHeaders as $stHeader => $xValue ) {
                $stHeaders .= "{$stHeader}: " . implode( ', ', $xValue ) . "\n";
            }
            throw new HTTPException(
                "HTTP Error {$uStatus} for {$i_stMethod} {$i_stPath} [{$stHeaders}]: {$stBody}"
            );
        }

        return new Response(
            $uStatus,
            $rHeaders,
            $body,
            $this->log
        );

    }


}
