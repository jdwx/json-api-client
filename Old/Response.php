<?php


declare( strict_types = 1 );


namespace Old;


use JDWX\JsonApiClient\Exceptions\AbstractRequestException;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Stringable;


class Response implements Stringable {


    private ?string $nstBody = null;

    private bool $bBodyRead = false;

    private readonly array $rHeaders;

    private mixed $json = null;


    /** @param array<string, list<string>> $rHeaders */
    public function __construct( private readonly int              $uStatus, array $rHeaders,
                                 private readonly StreamInterface  $smBody,
                                 private readonly ?LoggerInterface $log = null ) {
        $r = [];
        foreach ( $rHeaders as $stName => $xValue ) {
            $r[ strtolower( $stName ) ] = $xValue;
        }
        $this->rHeaders = $r;
    }


    public function getBareContentType() : ?string {
        $nstType = $this->getOneHeader( 'content-type' );
        if ( is_null( $nstType ) ) {
            return null;
        }
        $r = explode( ';', $nstType );
        return trim( $r[ 0 ] );
    }


    /** @return list<string>|null */
    public function getHeader( string $i_stName ) : ?array {
        $i_stName = strtolower( $i_stName );
        if ( ! array_key_exists( $i_stName, $this->rHeaders ) ) {
            return null;
        }
        return $this->rHeaders[ $i_stName ];
    }


    public function getOneHeader( string $i_stName, bool $i_bConsolidateMultiple = false ) : ?string {
        $rOut = $this->getHeader( $i_stName );
        if ( ! is_array( $rOut ) ) {
            return null;
        }
        $uCount = count( $rOut );
        assert( 0 !== $uCount );
        if ( 1 === $uCount ) {
            return $rOut[ 0 ];
        }
        if ( $i_bConsolidateMultiple ) {
            return implode( ', ', $rOut );
        }
        $this->log?->warning( 'Unexpected multiple headers found.', [
            $i_stName => $rOut,
        ] );
        return null;
    }


    public function hasHeader( string $i_stName ) : bool {
        return array_key_exists( $i_stName, $this->rHeaders );
    }


    public function status() : int {
        return $this->uStatus;
    }


    public function streamBody( int $i_uLength ) : ?string {
        if ( ! $this->smBody->isReadable() ) {
            throw new AbstractRequestException( 'Stream is not readable' );
        }
        if ( $this->smBody->eof() ) {
            $this->bBodyRead = true;
            return null;
        }
        return $this->smBody->read( $i_uLength );
    }


}
