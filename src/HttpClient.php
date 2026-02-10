<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;


class HttpClient extends AbstractHttpClient {


    private bool $bDebug = false;


    public function __construct( private readonly Client $client,
                                 ?LoggerInterface        $log = null ) {
        parent::__construct( $log );
    }


    public static function withGuzzle( ?string          $i_stBaseURI = null, float $i_fTimeout = 5.0,
                                       ?LoggerInterface $i_log = null ) : self {
        $r = [
            'timeout' => $i_fTimeout,
            'http_errors' => false,
        ];
        if ( is_string( $i_stBaseURI ) ) {
            $r[ 'base_uri' ] = $i_stBaseURI;
        }
        return new self( new Client( $r ), $i_log );
    }


    /** @param array<string, string> $i_rHeaders */
    public function request( string  $i_stMethod, string $i_stPath,
                             ?string $i_nstBody = null, array $i_rHeaders = [],
                             bool    $i_bAllowFailure = false, bool $i_bStream = false ) : Response {
        $i_rHeaders = array_merge( $this->getExtraHeaders(), $i_rHeaders );
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
            $r = [];
            if ( $i_bAllowFailure ) {
                $r[ 'http_errors' ] = false;
            }
            $response = $this->client->send( $i_request, $r );
        } catch ( RequestException $ex ) {
            $response = $ex->getResponse();
            if ( $response ) {
                return $this->handleResponse( $response, $i_bAllowFailure, $i_request->getMethod(),
                    $i_request->getUri()->getPath() );
            }
            throw new HTTPException(
                "HTTP Error without response for {$i_request->getMethod()} {$i_request->getUri()}: " . $ex->getMessage(),
                $ex->getCode(),
                $ex
            );
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


}
