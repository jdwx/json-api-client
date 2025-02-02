<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use JDWX\Json\Json;
use JsonException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;


class HttpClient {


    private bool $bDebug = false;


    /** @var array<string, string> $rExtraHeaders */
    private array $rExtraHeaders = [];


    public function __construct( private readonly Client $client ) {}


    public static function withGuzzle( ?string $i_stBaseURI = null, float $i_fTimeout = 5.0 ) : self {
        $r = [
            'timeout' => $i_fTimeout,
        ];
        if ( is_string( $i_stBaseURI ) ) {
            $r[ 'base_uri' ] = $i_stBaseURI;
        }
        return new self( new Client( $r ) );
    }


    public function get( string $i_stPath, array $i_rHeaders = [],
                         bool   $i_bAllowFailure = false, bool $i_bStream = false ) : Response {
        return $this->request( 'GET', $i_stPath, i_rHeaders: $i_rHeaders,
            i_bAllowFailure: $i_bAllowFailure, i_bStream: $i_bStream );
    }


    public function post( string $i_stPath, string $i_stBody, string $i_stContentType, array $i_rHeaders = [],
                          bool   $i_bAllowFailure = false, bool $i_bStream = false ) : Response {
        $i_rHeaders[ 'Content-Type' ] = $i_stContentType;
        return $this->request( 'POST', $i_stPath, $i_stBody,
            $i_rHeaders, $i_bAllowFailure, $i_bStream
        );
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


    /** @param array<string, string> $i_rHeaders */
    public function request( string  $i_stMethod, string $i_stPath,
                             ?string $i_nstBody = null, array $i_rHeaders = [],
                             bool    $i_bAllowFailure = false, bool $i_bStream = false ) : Response {
        $i_rHeaders = array_merge( $this->rExtraHeaders, $i_rHeaders );
        try {
            $rOptions = [];
            if ( is_string( $i_nstBody ) ) {
                $rOptions[ 'body' ] = $i_nstBody;
            }
            if ( ! empty( $i_rHeaders ) ) {
                $rOptions[ 'headers' ] = $i_rHeaders;
            }
            if ( $i_bStream ) {
                $rOptions[ 'stream' ] = true;
            }
            if ( $i_bAllowFailure ) {
                $rOptions[ 'http_errors' ] = false;
            }

            if ( $this->bDebug ) {
                echo "Request: {$i_stMethod} {$i_stPath}\n";
                echo "Headers:\n";
                foreach ( $i_rHeaders as $stHeader => $stValue ) {
                    echo "  {$stHeader}: {$stValue}\n";
                }
                if ( is_string( $i_nstBody ) ) {
                    echo "Body:\n{$i_nstBody}\n";
                }
            }
            $response = $this->client->request( $i_stMethod, $i_stPath, $rOptions );
        } catch ( RequestException $ex ) {
            $response = $ex->getResponse();
            if ( $response ) {
                return $this->handleResponse( $response, $i_bAllowFailure, $i_stMethod, $i_stPath );
            }
            throw new HTTPException(
                "HTTP Error without response for {$i_stMethod} {$i_stPath}: " . $ex->getMessage(),
                $ex->getCode(),
                $ex
            );
        } catch ( Throwable $ex ) {
            throw new TransportException(
                "Transport Error for {$i_stMethod} {$i_stPath}: " . $ex->getMessage(),
                $ex->getCode(),
                $ex
            );
        }

        return $this->handleResponse( $response, $i_bAllowFailure, $i_stMethod, $i_stPath );

    }


    public function sendRequest( RequestInterface $i_request, bool $i_bAllowFailure = false ) : Response {
        try {
            $response = $this->client->send( $i_request );
        } catch ( Throwable $ex ) {
            throw new TransportException(
                "Transport Error for {$i_request->getMethod()} {$i_request->getUri()}: " . $ex->getMessage(),
                $ex->getCode(),
                $ex
            );
        }

        return $this->handleResponse( $response, $i_bAllowFailure, $i_request->getMethod(),
            $i_request->getUri()->getPath() );
    }


    public function setDebug( bool $i_b ) : void {
        $this->bDebug = $i_b;
    }


    public function setExtraHeader( string $i_stHeader, string $i_stValue ) : void {
        $this->rExtraHeaders[ $i_stHeader ] = $i_stValue;
    }


    private function handleResponse( ResponseInterface $response, bool $i_bAllowFailure,
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
            throw new HTTPException( "HTTP Error {$uStatus} for {$i_stMethod} {$i_stPath} [{$stHeaders}]: " . $stBody );
        }

        return new Response(
            $uStatus,
            $rHeaders,
            $body
        );

    }


}
