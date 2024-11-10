<?php


declare( strict_types = 1 );


namespace JDWX\JsonApiClient;


use JsonException;


/**
 * JSON utilities. A thin wrapper around PHP's json_encode and json_decode to
 * provide consistent error handling (via JsonException) and some type
 * safety for the benefit of static analysis.
 */
final class Json {


    private static int $uDepth = 512;


    /**
     * Decode a JSON string to an arbitrary value.
     *
     * @param string $i_stJson
     * @return mixed
     * @throws JsonException
     */
    public static function decode( string $i_stJson ) : mixed {
        try {
            return json_decode( $i_stJson, true, self::$uDepth, JSON_THROW_ON_ERROR );
        } catch ( JsonException $e ) {
            throw new JsonException( "Failed to decode JSON: {$i_stJson}", 0, $e );
        }
    }


    /**
     * Decode a JSON string to an array.
     *
     * @param string $i_stJson
     * @return array<int|string, mixed>
     * @throws JsonException
     */
    public static function decodeArray( string $i_stJson ) : array {
        $x = self::decode( $i_stJson );
        if ( is_array( $x ) ) {
            return $x;
        }
        throw new JsonException( "JSON did not decode to array: {$i_stJson}" );
    }


    /**
     * Decode a JSON string to an array typed as a dictionary. Unfortunately,
     * PHP automatically converts numeric keys to integers, so our ability
     * to type-check this is very limited and involves peeking directly
     * into the JSON string. This method is therefore mostly useful for
     * static analysis.
     *
     * @param string $i_stJson
     * @return array<string, mixed>
     */
    public static function decodeDict( string $i_stJson ) : array {
        if ( ! str_starts_with( $i_stJson, '{' ) ) {
            throw new JsonException( "JSON is not a dict: {$i_stJson}" );
        }
        return self::decodeArray( $i_stJson );
    }


    /**
     * Decode a JSON string to an array typed as a list.
     *
     * @param string $i_stJson
     * @return list<mixed>
     */
    public static function decodeList( string $i_stJson ) : array {
        return self::expectList( self::decodeArray( $i_stJson ) );
    }


    public static function decodeScalar( string $i_stJson ) : bool|float|int|string {
        return self::expectScalar( self::decode( $i_stJson ) );
    }


    /**
     * Decode a JSON string to an array typed as a string map.
     *
     * @param string $i_stJson
     * @return array<string, string>
     */
    public static function decodeStringMap( string $i_stJson ) : array {
        $r = self::decodeDict( $i_stJson );
        if ( 0 !== count( $r ) && ! is_string( reset( $r ) ) ) {
            throw new JsonException( "JSON did not decode to string map: {$i_stJson}" );
        }
        return $r;
    }


    public static function decodeStringable( string $i_stJson ) : bool|float|int|string|null {
        return self::expectStringable( self::decode( $i_stJson ) );
    }


    /**
     * Encode an arbitrary value as JSON.
     *
     * @param mixed $i_x
     * @return string
     * @throws JsonException
     */
    public static function encode( mixed $i_x ) : string {
        return json_encode( $i_x, JSON_THROW_ON_ERROR, self::$uDepth );
    }


    public static function encodePretty( mixed $i_x ) : string {
        return json_encode( $i_x, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT, self::$uDepth );
    }


    /** @return mixed[] */
    public static function expectArray( mixed $i_x ) : array {
        if ( ! is_array( $i_x ) ) {
            throw new JsonException( 'Expected array, got: ' . gettype( $i_x ) );
        }
        return $i_x;
    }


    public static function expectBoolean( mixed $i_x ) : bool {
        if ( ! is_bool( $i_x ) ) {
            throw new JsonException( 'Expected boolean, got: ' . gettype( $i_x ) );
        }
        return $i_x;
    }


    /**
     * @return array<string, mixed>
     *
     * Tragically, PHP converts numeric keys to integers, so we can't
     * really type-check this. This method is mostly useful for static
     * analysis.
     */
    public static function expectDict( mixed $i_x ) : array {
        return self::expectArray( $i_x );
    }


    /** @return list<mixed> */
    public static function expectList( mixed $i_x ) : array {
        $r = self::expectArray( $i_x );
        if ( 0 !== count( $r ) && 0 !== array_key_first( $r ) ) {
            throw new JsonException( 'Expected list, got: ' . gettype( $r ) );
        }
        return $r;
    }


    public static function expectScalar( mixed $i_x ) : bool|float|int|string {
        if ( ! is_scalar( $i_x ) ) {
            throw new JsonException( 'Expected scalar, got: ' . gettype( $i_x ) );
        }
        return $i_x;
    }


    public static function expectStringable( mixed $i_x ) : bool|float|int|string|null {
        if ( ! is_scalar( $i_x ) && ! is_null( $i_x ) ) {
            throw new JsonException( 'Expected stringable, got: ' . gettype( $i_x ) );
        }
        return $i_x;
    }


    /** @return mixed[] */
    public static function fromFile( string $i_stFilename ) : array {
        set_error_handler( null );
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $st = @file_get_contents( $i_stFilename );
        restore_error_handler();
        if ( ! is_string( $st ) ) {
            throw new JsonException( "Failed to read: {$i_stFilename}" );
        }
        return self::decodeArray( $st );
    }


    public static function get( mixed $i_x, string $i_stKey, mixed $i_xDefault = null ) : mixed {
        $x = self::expectArray( $i_x );
        return $x[ $i_stKey ] ?? $i_xDefault;
    }


    public static function getArray( mixed $i_x, string $i_stKey, array $i_rDefault = [] ) : array {
        $x = self::expectArray( $i_x );
        $x = self::get( $x, $i_stKey, $i_rDefault );
        return self::expectArray( $x );
    }


    public static function getBoolean( mixed $i_x, string $i_stKey, bool $i_bDefault = false ) : bool {
        $x = self::expectArray( $i_x );
        $x = self::get( $x, $i_stKey, $i_bDefault );
        return self::expectBoolean( $x );
    }


    public static function getNull( mixed $i_x, string $i_stKey ) : null {
        $x = self::expectArray( $i_x );
        $x = self::get( $x, $i_stKey );
        if ( is_null( $x ) ) {
            return null;
        }
        throw new JsonException( 'Expected null, got ' . gettype( $x ) . ': ' . self::safeString( $x ) );
    }


    public static function getNumber( mixed $i_x, string $i_stKey, float|int $i_xDefault = 0 ) : float|int {
        $x = self::get( $i_x, $i_stKey, $i_xDefault );
        if ( is_int( $x ) || is_float( $x ) ) {
            return $x;
        }
        throw new JsonException( 'Expected number, got ' . gettype( $x ) . ': ' . self::safeString( $x ) );
    }


    public static function getString( mixed $i_x, string $i_stKey, string $i_stDefault = '' ) : string {
        $x = self::expectArray( $i_x );
        $x = self::get( $x, $i_stKey, $i_stDefault );
        if ( is_string( $x ) ) {
            return $x;
        }
        throw new JsonException( 'Expected string, got ' . gettype( $x ) . ': ' . self::safeString( $x ) );
    }


    /**
     * @param mixed[]|bool|float|int|string|null $i_x Input
     * @return string A printable string representing the input.
     *
     * This is useful for logging and debugging. It is not intended for
     * serialization.
     */
    public static function safeString( mixed $i_x ) : string {
        if ( is_array( $i_x ) ) {
            return self::encode( $i_x );
        }
        if ( is_bool( $i_x ) ) {
            return $i_x ? 'true' : 'false';
        }
        if ( is_null( $i_x ) ) {
            return 'null';
        }
        return strval( $i_x );
    }


    /**
     * @param string $i_stFileName
     * @param mixed $i_x
     * @throws JsonException
     */
    public static function toFile( string $i_stFileName, mixed $i_x ) : void {
        $st = self::encode( $i_x );
        set_error_handler( null );
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $bi = @file_put_contents( $i_stFileName, $st );
        restore_error_handler();
        if ( $bi === false ) {
            throw new JsonException( "Failed to write: {$i_stFileName}" );
        }
    }


}
