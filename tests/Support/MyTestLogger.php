<?php


declare( strict_types = 1 );


namespace Support;


use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;


class MyTestLogger implements LoggerInterface {


    use LoggerTrait;


    public int|string|null $level = null;

    public string|null $message = null;

    public ?array $context = null;


    public function log( $level, \Stringable|string $message, array $context = [] ) : void {
        $this->level = $level;
        $this->message = strval( $message );
        $this->context = $context;
    }


}
