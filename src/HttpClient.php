<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use JsonException;
use Psr\Http\Client\ClientInterface;
use Throwable;


readonly class HttpClient {


    public function __construct( private ClientInterface $client ) {
    }


    /** @param array<string, string> $i_rHeaders */
    public function request( string $i_stMethod, string $i_stPath,
                              ?string $i_nstBody = null, array $i_rHeaders = [],
                              bool $i_bAllowFailure = false ) : Response {
        $req = new Request( $i_stMethod, $i_stPath, $i_rHeaders, $i_nstBody );
        return $this->sendRequest( $req, $i_bAllowFailure );
    }


    public function sendRequest( Request $req, bool $i_bAllowFailure = false ) : Response {
        try {
            $response = $this->client->sendRequest( $req );
        } catch ( Throwable $ex ) {
            $stMethod = $req->getMethod();
            $stPath = $req->getUri();
            throw new TransportException(
                "Transport Error for {$stMethod} {$stPath}: " . $ex->getMessage(),
                $ex->getCode(),
                $ex
            );
        }

        $uStatus = $response->getStatusCode();
		$uFirst = intval( $uStatus / 100 );
        if ( 2 !== $uFirst && ! $i_bAllowFailure ) {
            $stMethod = $req->getMethod();
            $stPath = $req->getUri();
			$stBody = $response->getBody()->getContents() ?: '(no body)';
            throw new HTTPException( "HTTP Error {$uStatus} for {$stMethod} {$stPath}: " . $stBody );
        }

        return new Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody()
        );
    }


    public function get( string $i_stPath, bool $i_bAllowFailure = false ) : Response {
        return $this->request( 'GET', $i_stPath, i_bAllowFailure: $i_bAllowFailure );
    }


    public function post( string $i_stPath, string $i_stBody, string $i_stContentType,
                          bool $i_bAllowFailure = false ) : Response {
        return $this->request( 'POST', $i_stPath, $i_stBody, [ 'Content-Type' => $i_stContentType ], $i_bAllowFailure );
    }


    /**
     * @param string $i_stPath
     * @param mixed[] $i_rJson
     * @param string $i_stContentType
     * @param bool $i_bAllowFailure
     * @return Response
     * @throws JsonException
     */
    public function postJson( string $i_stPath, array $i_rJson,
                              string $i_stContentType = 'application/json',
                              bool $i_bAllowFailure = false ) : Response {
        return $this->post( $i_stPath, Json::encode( $i_rJson ), $i_stContentType, $i_bAllowFailure );
    }


    public static function withGuzzle( ?string $i_stBaseURI = null, float $i_fTimeout = 5.0 ) : self {
        $r = [
            'timeout' => $i_fTimeout,
        ];
        if ( is_string( $i_stBaseURI ) ) {
            $r[ 'base_uri' ] = $i_stBaseURI;
        }
        return new self( new Client( $r ) );
    }


}
