<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use JDWX\HttpClient\Exceptions\ClientException;
use JDWX\HttpClient\Exceptions\HttpStatusException;
use JDWX\HttpClient\Exceptions\NetworkException;
use JDWX\HttpClient\Exceptions\RequestException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;


class Client implements ClientInterface {


    public function __construct( private readonly ClientInterface  $client,
                                 private readonly ?LoggerInterface $log = null ) {}


    public function jsonifyResponse( RequestInterface                    $request,
                                     \Psr\Http\Message\ResponseInterface $response ) : ResponseInterface {
        $stBody = $response->getBody()->getContents();
        $json = json_decode( $stBody, true );
        if ( null === $json && JSON_ERROR_NONE !== json_last_error() ) {
            throw new ClientException( 'JSON decode error: ' . json_last_error_msg() );
        }

        return new Response( $request, $response, $this->log );
    }


    public function sendRequest( RequestInterface $request,
                                 bool             $i_bAllowFailure = false ) : \Psr\Http\Message\ResponseInterface {
        try {
            $response = $this->client->sendRequest( $request );
        } catch ( NetworkExceptionInterface $ex ) {
            throw NetworkException::from( $ex );
        } catch ( RequestExceptionInterface $ex ) {
            throw RequestException::from( $ex );
        } catch ( ClientExceptionInterface $ex ) {
            throw ClientException::from( $ex );
        } catch ( Throwable $ex ) {
            throw new ClientException( 'Unexpected client error: ' . $ex->getMessage(), $ex->getCode(), $ex );
        }

        $response = $this->jsonifyResponse( $request, $response );
        if ( ! $response->isSuccess() && ! $i_bAllowFailure ) {
            $uStatus = $response->getStatusCode();
            throw new HttpStatusException( $response, $request, "HTTP Status {$uStatus}", $uStatus );
        }

        if ( ! $response->isJson() ) {
            // If the response is not JSON and we expected it to be, we can throw an exception.
            // This could be a server error or some other issue.
            // You can customize this behavior based on your needs.
            throw new NonJsonResponseException(
                $response, $request, 'Expected JSON response but got non-JSON content.'
            );
        }

        return $response;
    }


}
