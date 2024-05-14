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

        $stJson = "////nope///";
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


    public function testEncode() : void {
        static::assertSame( '{"a":1,"b":2}', Json::encode( [ 'a' => 1, 'b' => 2 ] ) );
        static::assertSame( 'null', Json::encode( null ) );
        $x = fopen( 'php://memory', 'w' );
        self::expectException( JsonException::class );
        Json::encode( $x );
    }


}
