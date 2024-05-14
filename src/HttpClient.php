<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use GuzzleHttp\Client;
use RuntimeException;


class HttpClient {


    private Client $client;

    private ?int $nuStatus = null;


    public function __construct( string $i_stBaseURI, float $i_fTimeout = 5.0 ) {
        $this->client = new Client([
            'base_uri' => $i_stBaseURI,
            'timeout' => $i_fTimeout,
        ]);
    }


    /** @param array<string, string> $i_rHeaders */
    private function request( string $i_stMethod, string $i_stPath,
                              ?string $i_nstBody = null, array $i_rHeaders = [],
                              bool $i_bAllowFailure = false ) : string {
        $rRequest = [
            'headers' => $i_rHeaders,
        ];
        if ( is_string( $i_nstBody ) ) {
            $rRequest[ 'body' ] = $i_nstBody;
        }
        $response = $this->client->request( $i_stMethod, $i_stPath, $rRequest );

        if ( 200 !== $response->getStatusCode() && ! $i_bAllowFailure ) {
            throw new RuntimeException( "HTTP Error for {$i_stPath}: " . $response->getBody()->getContents() );
        }

        return $response->getBody()->getContents();
    }


    public function get( string $i_stPath ) : string {
        return $this->request( 'GET', $i_stPath );
    }


    public function post( string $i_stPath, string $i_stBody, string $i_stContentType,
                          bool $i_bAllowFailure = false ) : string {
        return $this->request( 'POST', $i_stPath, $i_stBody, [ 'Content-Type' => $i_stContentType ], $i_bAllowFailure );
    }


    /** @param mixed[] $i_rJson */
    public function postJson( string $i_stPath, array $i_rJson,
                              bool $i_bAllowFailure = false ) : string {
        return $this->post( $i_stPath, Json::encode( $i_rJson ), 'application/json', $i_bAllowFailure );
    }


    /**
     * @param mixed[] $i_rJSON
     */
    public function postJsonToJson( string $i_stPath, array $i_rJSON,
                                    bool $i_bAllowFailure = false ) : mixed {
        $stResponse = $this->postJson( $i_stPath, $i_rJSON, $i_bAllowFailure );
        return Json::decode( $stResponse );
    }


    public function status() : int {
        if ( null === $this->nuStatus ) {
            throw new RuntimeException( 'No status available yet.' );
        }
        return $this->nuStatus;
    }


    public static function dummy() : void {
        $client = new Client([
           'base_uri' => 'http://localhost:8080',
           'timeout' => 5.0
        ]);
        $rRequest = [
            'content' => 'This is a test.',
        ];
        $stRequest = json_encode( $rRequest );
        $response = $client->request('POST', '/tokenize', [
            'body' => $stRequest,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
        assert( 200 === $response->getStatusCode() );
        $stResponse = $response->getBody()->getContents();
        var_dump( $stResponse );
    }


}
