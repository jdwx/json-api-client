<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use Psr\Http\Message\RequestInterface;


class Client extends \JDWX\HttpClient\Client {


    private bool $bAllowNonJsonResponse = false;


    public function __construct( object ...$i_rSources ) {
        parent::__construct( ...$i_rSources );
    }


    public function sendRequest( RequestInterface $request ) : Response {
        $response = parent::sendRequest( $request );
        assert( $response instanceof Response );
        if ( ! $response->isJson() && ! $this->bAllowNonJsonResponse ) {
            throw new NonJsonResponseException(
                $response, $request, 'Expected JSON response but got non-JSON content.'
            );
        }
        return $response;
    }


    protected function upgradeResponse( RequestInterface                    $i_request,
                                        \Psr\Http\Message\ResponseInterface $i_response ) : Response {
        return new Response( $i_request, $i_response, $this->logger );
    }


}
