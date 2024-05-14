<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use CurlHandle;


class Curl {


    private CurlHandle $ch;

    /** @var array<string, string> */
    private array $rHeaders = [];

    private static string $stUserAgent = 'JDWX/LLM';


    public function __construct( private readonly string $stURL ) {
        $ch = curl_init( $stURL );
        if ( ! $ch ) {
            throw new RuntimeException( 'Failed to initialize cURL: ' . $stURL );
        }
        $this->ch = $ch;
        curl_setopt( $this->ch, CURLOPT_URL, $stURL );
        curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $this->ch, CURLOPT_USERAGENT, self::$stUserAgent );
    }


    public function __destruct() {
        curl_close( $this->ch );
    }


    private function exec( bool $i_bAllowFailure = false ) : string {
        $rHeaders = [];
        foreach ( $this->rHeaders as $stKey => $stValue ) {
            $rHeaders[] = $stKey . ': ' . $stValue;
        }
        curl_setopt( $this->ch, CURLOPT_HTTPHEADER, $rHeaders );
        $bstResponse = curl_exec( $this->ch );
        if ( $bstResponse === false ) {
            throw new NetworkException( "cURL Network Error for {$this->stURL}: " . $this->getError() );
        }
        assert( is_string( $bstResponse ) );
        if ( ! $i_bAllowFailure && ! $this->isSuccess() ) {
            throw new ServerException( "cURL Server Error for {$this->stURL}: " . $bstResponse );
        }
        return $bstResponse;
    }


    public function getError() : string {
        return curl_error( $this->ch );
    }


    public function getReturnCode() : int {
        return curl_getinfo( $this->ch, CURLINFO_RESPONSE_CODE );
    }


    public function isSuccess() : bool {
        return 200 === $this->getReturnCode();
    }


    public function post( string $i_stData, bool $i_bAllowFailure = false ) : string {
        curl_setopt( $this->ch, CURLOPT_POST, true );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $i_stData );
        return $this->exec( $i_bAllowFailure );
    }


    /** @param mixed[] $i_rData */
    public function postJson( array $i_rData, bool $i_bAllowFailure = false ) : string {
        $stData = Json::encode( $i_rData );
        $this->setHeader( 'Content-Type', 'application/json' );
        return $this->post( $stData, $i_bAllowFailure );
    }


    /**
     * @param mixed[] $i_rData
     * @return mixed[]
     */
    public function postJsonToJson( array $i_rData, bool $i_bAllowFailure = false ) : array {
        $stResponse = $this->postJson( $i_rData, $i_bAllowFailure );
        return Json::decodeDict( $stResponse );
    }


    public function setHeader( string $i_stHeader, string $i_stValue ) : void {
        $this->rHeaders[ $i_stHeader ] = $i_stValue;
    }


    public static function setUserAgent( string $i_stUserAgent ) : void {
        self::$stUserAgent = $i_stUserAgent;
    }


    public static function simplePost( string $i_stURL, string $i_stData ) : string {
        $curl = new Curl( $i_stURL );
        return $curl->post( $i_stData );
    }


    /** @param mixed[] $i_rData */
    public static function simplePostJson( string $i_stURL, array $i_rData ) : string {
        $curl = new Curl( $i_stURL );
        return $curl->postJson( $i_rData );
    }


    /**
     * @param mixed[] $i_rData
     * @return mixed[]
     */
    public static function simplePostJsonToJson( string $i_stURL, array $i_rData ) : array {
        $curl = new Curl( $i_stURL );
        return $curl->postJsonToJson( $i_rData );
    }


}
