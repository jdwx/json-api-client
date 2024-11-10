<?php


declare( strict_types = 1 );


use JDWX\JsonApiClient\Json;
use PHPUnit\Framework\TestCase;


require_once __DIR__ . '/../vendor/autoload.php';


class JsonTest extends TestCase {


    public function testDecode() : void {
        $stJson = '{"a":1,"b":2}';
        $decode = Json::decode( $stJson );
        static::assertSame( [ 'a' => 1, 'b' => 2 ], $decode );

        $stJson = 'null';
        static::assertNull( Json::decode( $stJson ) );

        $stJson = '5';
        static::assertSame( 5, Json::decode( $stJson ) );

        $stJson = '////nope///';
        self::expectException( JsonException::class );
        Json::decode( $stJson );
    }


    public function testDecodeArray() : void {
        $stJson = '[1,2]';
        $rDecode = Json::decodeArray( $stJson );
        static::assertSame( [ 1, 2 ], $rDecode );

        $stJson = '{"a":1,"b":2}';
        $rDecode = Json::decodeArray( $stJson );
        static::assertSame( [ 'a' => 1, 'b' => 2 ], $rDecode );

        $stJson = 'null';
        self::expectException( JsonException::class );
        Json::decodeArray( $stJson );
    }


    public function testDecodeDict() : void {
        $stJson = '{"a":1,"b":2}';
        $rDecode = Json::decodeDict( $stJson );
        static::assertSame( [ 'a' => 1, 'b' => 2 ], $rDecode );

        $stJson = '[1,2]';
        self::expectException( JsonException::class );
        Json::decodeDict( $stJson );
    }


    public function testDecodeDictEmpty() : void {
        $stJson = '{}';
        $rDecode = Json::decodeDict( $stJson );
        static::assertSame( [], $rDecode );
    }


    public function testDecodeDictWithInt() : void {
        $stJson = '5';
        self::expectException( JsonException::class );
        Json::decodeDict( $stJson );
    }


    public function testDecodeList() : void {
        $stJson = '[1,2]';
        $rDecode = Json::decodeList( $stJson );
        static::assertSame( [ 1, 2 ], $rDecode );

        $stJson = '{"a":1,"b":2}';
        self::expectException( JsonException::class );
        Json::decodeList( $stJson );
    }


    public function testDecodeListEmpty() : void {
        $stJson = '[]';
        $rDecode = Json::decodeList( $stJson );
        static::assertSame( [], $rDecode );
    }


    public function testDecodeListWithInt() : void {
        $stJson = '5';
        self::expectException( JsonException::class );
        Json::decodeList( $stJson );
    }


    public function testDecodeScalar() : void {
        $stJson = '5';
        $rDecode = Json::decodeScalar( $stJson );
        static::assertSame( 5, $rDecode );

        $stJson = 'true';
        $rDecode = Json::decodeScalar( $stJson );
        static::assertTrue( $rDecode );

        $stJson = '{"a":1,"b":2}';
        self::expectException( JsonException::class );
        Json::decodeScalar( $stJson );
    }


    public function testDecodeStringMap() : void {
        $stJson = '{"a":"1","b":"2"}';
        $rDecode = Json::decodeStringMap( $stJson );
        static::assertSame( [ 'a' => '1', 'b' => '2' ], $rDecode );

        $stJson = '{"a":1,"b":2}';
        self::expectException( JsonException::class );
        $r = Json::decodeStringMap( $stJson );
        var_dump( $r );
    }


    public function testDecodeStringMapEmpty() : void {
        $stJson = '{}';
        $rDecode = Json::decodeStringMap( $stJson );
        static::assertSame( [], $rDecode );
    }


    public function testDecodeStringMapWithInt() : void {
        $stJson = '5';
        self::expectException( JsonException::class );
        Json::decodeStringMap( $stJson );
    }


    public function testDecodeStringable() : void {
        $stJson = '5';
        $rDecode = Json::decodeStringable( $stJson );
        static::assertSame( 5, $rDecode );

        $stJson = 'true';
        $rDecode = Json::decodeStringable( $stJson );
        static::assertTrue( $rDecode );

        $stJson = '{"a":1,"b":2}';
        self::expectException( JsonException::class );
        Json::decodeStringable( $stJson );
    }


    public function testEncode() : void {
        static::assertSame( '{"a":1,"b":2}', Json::encode( [ 'a' => 1, 'b' => 2 ] ) );
        static::assertSame( 'null', Json::encode( null ) );
        $x = fopen( 'php://memory', 'w' );
        self::expectException( JsonException::class );
        Json::encode( $x );
    }


    public function testEncodePretty() : void {
        $r = [
            'a' => 1,
            'b' => 2,
            'c' => [
                'd' => 3,
                'e' => 4,
            ],
        ];
        $stJson = Json::encodePretty( $r );
        $r2 = Json::decode( $stJson );
        static::assertSame( $r, $r2 );
    }


    public function testExpectArray() : void {
        static::assertSame( [ 1, 2 ], Json::expectArray( [ 1, 2 ] ) );
        self::expectException( JsonException::class );
        Json::expectArray( 5 );
    }


    public function testExpectBoolean() : void {
        static::assertTrue( Json::expectBoolean( true ) );
        static::assertFalse( Json::expectBoolean( false ) );
        self::expectException( JsonException::class );
        Json::expectBoolean( 5 );
    }


    public function testExpectDict() : void {
        static::assertSame( [ 'a' => 1, 'b' => 2 ], Json::expectDict( [ 'a' => 1, 'b' => 2 ] ) );
        self::expectException( JsonException::class );
        Json::expectDict( 5 );
    }


    public function testExpectScalar() : void {
        static::assertSame( 5, Json::expectScalar( 5 ) );
        static::assertSame( 5.5, Json::expectScalar( 5.5 ) );
        static::assertTrue( Json::expectScalar( true ) );
        static::assertFalse( Json::expectScalar( false ) );
        static::assertSame( 'foo', Json::expectScalar( 'foo' ) );
        self::expectException( JsonException::class );
        Json::expectScalar( null );
    }


    public function testExpectScalarWithArray() : void {
        self::expectException( JsonException::class );
        Json::expectScalar( [ 'foo' => 'bar' ] );
    }


    public function testExpectStringable() : void {
        static::assertSame( 5, Json::expectStringable( 5 ) );
        static::assertSame( 5.5, Json::expectStringable( 5.5 ) );
        static::assertTrue( Json::expectStringable( true ) );
        static::assertFalse( Json::expectStringable( false ) );
        static::assertSame( 'foo', Json::expectStringable( 'foo' ) );
        static::assertNull( Json::expectStringable( null ) );
        self::expectException( JsonException::class );
        Json::expectStringable( [ 'foo' => 'bar' ] );
    }


    public function testFromFile() : void {
        $stJson = '{"a":1,"b":2}';
        $stFilename = tempnam( sys_get_temp_dir(), 'jdwx_json-api-client' );
        file_put_contents( $stFilename, $stJson );
        $rDecode = Json::fromFile( $stFilename );
        static::assertSame( [ 'a' => 1, 'b' => 2 ], $rDecode );
        unlink( $stFilename );

        self::expectException( JsonException::class );
        Json::fromFile( $stFilename );

    }


    public function testGet() : void {
        $stJson = '{"a":1,"b":"foo"}';
        $r = Json::decode( $stJson );
        static::assertSame( 1, Json::get( $r, 'a' ) );
        static::assertSame( 'foo', Json::get( $r, 'b' ) );
        static::assertSame( null, Json::get( $r, 'c', i_bNullOK: true ) );
        static::assertSame( 5, Json::get( $r, 'c', 5 ) );
        static::expectException( JsonException::class );
        Json::get( $r, 'c' );
    }


    public function testGetArray() : void {
        $stJson = '{"a":[1,2], "b":"foo"}';
        $r = Json::decode( $stJson );
        static::assertSame( [ 1, 2 ], Json::getArray( $r, 'a' ) );
        static::assertSame( [], Json::getArray( $r, 'c', [] ) );
        static::expectException( JsonException::class );
        Json::getArray( $r, 'b' );
    }


    public function testGetArrayWithMissingKey() : void {
        $stJson = '{"a":[1,2]}';
        $r = Json::decode( $stJson );
        self::expectException( JsonException::class );
        Json::getArray( $r, 'b' );
    }


    public function testGetBoolean() : void {
        $stJson = '{"a":true,"b":false,"c":"foo"}';
        $r = Json::decode( $stJson );
        static::assertTrue( Json::getBoolean( $r, 'a' ) );
        static::assertFalse( Json::getBoolean( $r, 'b' ) );
        static::assertTrue( Json::getBoolean( $r, 'd', true ) );
        static::assertFalse( Json::getBoolean( $r, 'd', false ) );
        static::expectException( JsonException::class );
        Json::getBoolean( $r, 'c' );
    }


    public function testGetBooleanWithMissingKey() : void {
        $stJson = '{"a":true}';
        $r = Json::decode( $stJson );
        self::expectException( JsonException::class );
        Json::getBoolean( $r, 'b' );
    }


    public function testGetNull() : void {
        $stJson = '{"a":null, "b":5}';
        $r = Json::decode( $stJson );
        static::assertNull( Json::getNull( $r, 'a' ) );
        static::expectException( JsonException::class );
        Json::getNull( $r, 'b' );
    }


    public function testGetNullWithMissingKey() : void {
        $stJson = '{"a":null}';
        $r = Json::decode( $stJson );
        self::expectException( JsonException::class );
        Json::getNull( $r, 'b' );
    }


    public function testGetNumber() : void {
        $stJson = '{"a":1,"b":"foo"}';
        $r = Json::decode( $stJson );
        static::assertSame( 1, Json::getNumber( $r, 'a' ) );
        static::assertSame( 5, Json::getNumber( $r, 'c', 5 ) );
        static::expectException( JsonException::class );
        Json::getNumber( $r, 'b' );
    }


    public function testGetString() : void {
        $stJson = '{"a":"foo","b":5}';
        $r = Json::decode( $stJson );
        static::assertSame( 'foo', Json::getString( $r, 'a' ) );
        static::assertSame( 'bar', Json::getString( $r, 'c', 'bar' ) );
        static::expectException( JsonException::class );
        Json::getString( $r, 'b' );
    }


    public function testGetWithMissingKey() : void {
        $stJson = '{"a":1}';
        $r = Json::decode( $stJson );
        self::expectException( JsonException::class );
        Json::get( $r, 'b' );
    }


    public function testSafeString() : void {
        static::assertSame( 'true', Json::safeString( true ) );
        static::assertSame( 'false', Json::safeString( false ) );
        static::assertSame( 'null', Json::safeString( null ) );
        static::assertSame( '5', Json::safeString( 5 ) );
        static::assertSame( '5.5', Json::safeString( 5.5 ) );
        static::assertSame( 'foo', Json::safeString( 'foo' ) );
        static::assertSame( '{"a":1,"b":2}', Json::safeString( [ 'a' => 1, 'b' => 2 ] ) );
    }


    public function testToFile() : void {
        $r = [ 'a' => 1, 'b' => 2 ];
        $stFilename = tempnam( sys_get_temp_dir(), 'jdwx_json-api-client' );
        Json::toFile( $stFilename, $r );

        $r2 = Json::fromFile( $stFilename );
        static::assertSame( $r, $r2 );
        unlink( $stFilename );

        self::expectException( JsonException::class );
        Json::toFile( $stFilename, fopen( 'php://memory', 'w' ) );
    }


    public function testToFileForDirectory() : void {
        $stFilename = sys_get_temp_dir();
        self::expectException( JsonException::class );
        Json::toFile( $stFilename, [ 'a' => 1, 'b' => 2 ] );
    }


}
