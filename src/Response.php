<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use Psr\Http\Message\StreamInterface;


class Response {


    private ?string $nstBody = null;

    private bool $bBodyRead = false;


    /** @param array<string, list<string>> $rHeaders */
    public function __construct( private readonly int    $uStatus, private readonly array $rHeaders,
                                 private readonly StreamInterface $smBody ) {
    }


    public function body() : string {
        if ( ! is_string( $this->nstBody ) ) {
            if ( $this->bBodyRead ) {
                throw new RuntimeException( 'Body already read' );
            }
            $this->nstBody = $this->smBody->getContents();
            $this->bBodyRead = true;
        }
        return $this->nstBody;
    }


    public function json() : mixed {
        return Json::decode( $this->body() );
    }


    /** @return list<string>|null */
    public function getHeader( string $i_stName ) : ?array {
        if ( ! array_key_exists( $i_stName, $this->rHeaders ) ) {
            return null;
        }
        return $this->rHeaders[ $i_stName ];
    }


    public function getOneHeader( string $i_stName ) : ?string {
        $rOut = $this->getHeader( $i_stName );
        if ( ! is_array( $rOut ) ) {
            return null;
        }
        $uCount = count( $rOut );
        assert( 0 !== $uCount );
        if ( 1 === $uCount ) {
            return $rOut[ 0 ];
        }
        throw new RuntimeException( "Multiple headers found for {$i_stName}" );
    }


    public function getOneHeaderEx( string $i_stName ) : string {
        $nstOut = $this->getOneHeader( $i_stName );
        if ( is_string( $nstOut ) ) {
            return $nstOut;
        }
        throw new RuntimeException( "No header found for {$i_stName}" );
    }


    public function hasHeader( string $i_stName ) : bool {
        return array_key_exists( $i_stName, $this->rHeaders );
    }


    public function status() : int {
        return $this->uStatus;
    }


    public function streamBody( int $i_uLength ) : ?string {
        if ( ! $this->smBody->isReadable() ) {
            throw new RuntimeException( 'Stream is not readable' );
        }
        if ( $this->smBody->eof() ) {
            $this->bBodyRead = true;
            return null;
        }
        return $this->smBody->read( $i_uLength );
    }


}