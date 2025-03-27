<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use JDWX\Json\Json;
use JsonException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;


class HttpClient {


    private bool $bDebug = false;


    /** @var array<string, string> $rExtraHeaders */
    private array $rExtraHeaders = [];

    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;


    public function __construct( private readonly ClientInterface  $client,
                                 private readonly ?LoggerInterface $log = null,
                                 ?RequestFactoryInterface          $i_requestFactory = null,
                                 ?StreamFactoryInterface           $i_streamFactory = null,
    ) {
        if ( ! $i_requestFactory instanceof RequestFactoryInterface ) {
            $i_requestFactory = GuzzleShim::createFactory();
            $this->log?->warning( 'No RequestFactory provided; using default' );
        }
        $this->requestFactory = $i_requestFactory;

        if ( ! $i_streamFactory instanceof StreamFactoryInterface ) {
            if ( $i_requestFactory instanceof StreamFactoryInterface ) {
                $i_streamFactory = $i_requestFactory;
            } else {
                $i_streamFactory = GuzzleShim::createFactory();
                $this->log?->warning( 'No StreamFactory provided; using default' );
            }
        }
        $this->streamFactory = $i_streamFactory;
    }


    public static function withGuzzle( ?string          $i_stBaseURI = null, float $i_fTimeout = 5.0,
                                       ?LoggerInterface $i_log = null ) : self {
        $client = GuzzleShim::createClient( $i_stBaseURI, $i_fTimeout );
        $factory = GuzzleShim::createFactory();
        return new self( $client, $i_log, $factory, $factory );
    }


    public function get( string $i_stPath, array $i_rHeaders = [],
                         bool   $i_bAllowFailure = false ) : Response {
        return $this->request( 'GET', $i_stPath, i_rHeaders: $i_rHeaders,
            i_bAllowFailure: $i_bAllowFailure );
    }


    public function post( string $i_stPath, string $i_stBody, string $i_stContentType, array $i_rHeaders = [],
                          bool   $i_bAllowFailure = false ) : Response {
        $i_rHeaders[ 'Content-Type' ] = $i_stContentType;
        return $this->request( 'POST', $i_stPath, $i_stBody, $i_rHeaders, $i_bAllowFailure );
    }


    /**
     * @param string $i_stPath
     * @param mixed[] $i_rJson JSON to send as the request body.
     * @param string $i_stContentType Content type of the request body.
     * @param array $i_rHeaders Additional headers to send. (As header => value pairs.)
     * @param bool $i_bAllowFailure If true, don't throw an exception on non-2xx status.
     * @return Response
     * @throws JsonException
     */
    public function postJson( string $i_stPath, array $i_rJson,
                              string $i_stContentType = 'application/json',
                              array  $i_rHeaders = [],
                              bool   $i_bAllowFailure = false ) : Response {
        return $this->post( $i_stPath, Json::encode( $i_rJson ), $i_stContentType,
            $i_rHeaders, $i_bAllowFailure );
    }


    /** @param array<string, string> $i_rHeaders */
    public function request( string  $i_stMethod, string $i_stPath,
                             ?string $i_nstBody = null, array $i_rHeaders = [],
                             bool    $i_bAllowFailure = false ) : Response {
        $i_rHeaders = array_merge( $this->rExtraHeaders, $i_rHeaders );
        try {
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
            $req = $this->requestFactory->createRequest( $i_stMethod, $i_stPath );
            if ( is_string( $i_nstBody ) ) {
                $req->withBody( $this->streamFactory->createStream( $i_nstBody ) );
            }
            foreach ( $i_rHeaders as $stHeader => $stValue ) {
                $req = $req->withHeader( $stHeader, $stValue );
            }
            $response = $this->client->sendRequest( $req );

            // $response = $this->client->request( $i_stMethod, $i_stPath, $rOptions );
        } catch ( RequestExceptionInterface $ex ) {
            if ( method_exists( $ex, 'getResponse' ) ) {
                $response = $ex->getResponse();
                if ( $response instanceof ResponseInterface ) {
                    return $this->handleResponse( $response, $i_bAllowFailure, $i_stMethod, $i_stPath );
                }
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
            $response = $this->client->sendRequest( $i_request );
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
