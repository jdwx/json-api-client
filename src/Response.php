<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use JDWX\Json\Json;
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


    public function __toString() : string {
        $stOut = "status: {$this->uStatus}\n";
        foreach ( $this->rHeaders as $stName => $rValues ) {
            $stOut .= "$stName: " . implode( ', ', $rValues ) . "\n";
        }
        $stOut .= "\n";
        if ( ! is_string( $this->nstBody ) && $this->bBodyRead ) {
            $stOut .= '[Body not available]';
        } else {
            $stOut .= $this->body();
        }
        return $stOut;
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


    public function isContentType( string $i_stType, ?string $i_stSubtype = null ) : bool {
        $nstType = $this->getBareContentType();
        if ( is_null( $nstType ) ) {
            return false;
        }
        if ( is_string( $i_stSubtype ) ) {
            $i_stType .= '/' . $i_stSubtype;
        }
        return $nstType === $i_stType;
    }


    public function isContentTypeLoose( string $i_stType, string $i_stSubtype ) : bool {
        return $this->isContentTypeType( $i_stType ) && $this->isContentTypeSubtype( $i_stSubtype );
    }


    public function isContentTypeSubtype( string $i_stSubtype ) : bool {
        $nstType = $this->getBareContentType();
        if ( is_null( $nstType ) ) {
            return false;
        }
        $r = explode( '/', $nstType );
        if ( 2 !== count( $r ) ) {
            return false;
        }
        $r = explode( '+', $r[ 1 ] );
        return in_array( $i_stSubtype, $r );
    }


    public function isContentTypeType( string $i_stType ) : bool {
        $nstType = $this->getBareContentType();
        if ( is_null( $nstType ) ) {
            return false;
        }
        return str_starts_with( $nstType, $i_stType . '/' );
    }


    public function isJson() : bool {
        return $this->isContentTypeLoose( 'application', 'json' );
    }


    public function isRedirect() : bool {
        return 3 === intval( $this->uStatus / 100 );
    }


    public function isSuccess() : bool {
        return 2 === intval( $this->uStatus / 100 );
    }


    public function json() : mixed {
        if ( is_null( $this->json ) ) {
            $this->json = Json::decode( $this->body() );
        }
        return $this->json;
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
