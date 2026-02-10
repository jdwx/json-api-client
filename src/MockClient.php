<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use Psr\Http\Message\RequestInterface;


class MockClient extends AbstractHttpClient {


    /** @var list<Response|\Throwable> */
    private array $rResponses = [];

    /** @var list<RequestInterface|array<string, mixed>> */
    private array $rRequests = [];


    public function queueResponse( Response|\Throwable|string $i_rsp ) : void {
        if ( is_string( $i_rsp ) ) {
            $stream = new MockStream( $i_rsp );
            $i_rsp = new Response( 200, [], $stream, $this->log );
        }
        $this->rResponses[] = $i_rsp;
    }


    public function request( string $i_stMethod, string $i_stPath, ?string $i_nstBody = null, array $i_rHeaders = [],
                             bool   $i_bAllowFailure = false, bool $i_bStream = false ) : Response {
        $rHeaders = array_merge( $this->getExtraHeaders(), $i_rHeaders );
        $this->rRequests[] = [
            'method' => $i_stMethod,
            'path' => $i_stPath,
            'body' => $i_nstBody,
            'headers' => $rHeaders,
            'stream' => $i_bStream,
        ];
        return $this->fakeResponse();
    }


    public function sendRequest( RequestInterface $i_request, bool $i_bAllowFailure = false ) : Response {
        foreach ( $this->getExtraHeaders() as $stHeader => $stValue ) {
            if ( $i_request->hasHeader( $stHeader ) ) {
                continue;
            }
            $i_request = $i_request->withHeader( $stHeader, $stValue );
        }
        $this->rRequests[] = $i_request;
        return $this->fakeResponse();
    }


    /** @return RequestInterface|array<string,mixed>|null */
    public function shiftRequest() : RequestInterface|array|null {
        return array_shift( $this->rRequests );
    }


    public function shiftRequestArray() : array {
        $x = $this->shiftRequest();
        if ( is_array( $x ) ) {
            return $x;
        }
        throw new \RuntimeException( 'Expected array, got ' . get_debug_type( $x ) );
    }


    public function shiftRequestObject() : RequestInterface {
        $x = $this->shiftRequest();
        if ( $x instanceof RequestInterface ) {
            return $x;
        }
        throw new \RuntimeException( 'Expected RequestInterface, got ' . get_debug_type( $x ) );
    }


    private function fakeResponse() : Response {
        if ( empty( $this->rResponses ) ) {
            throw new \RuntimeException( 'No responses queued' );
        }

        $rsp = array_shift( $this->rResponses );
        if ( $rsp instanceof \Throwable ) {
            throw $rsp;
        }
        return $rsp;
    }


}
