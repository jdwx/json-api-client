# jdwx/json-api-client

A simple PHP module for interacting with JSON API services.

## Installation

You can require it directly with Composer:

```bash
composer require jdwx/json-api-client
```

Or download the source from GitHub: https://github.com/jdwx/json-api-client.git

## Requirements

This module requires PHP 8.3 or later.  The default implementation
depends on the excellent [Guzzle](https://docs.guzzlephp.org/en/stable/)
HTTP client implementation. It also requires the JSON extension.

The goal is to stick pretty close to the PSR HTTP client interfaces, but
more work remains to be done in that area.

## Usage

Here is a basic usage example:

```php
$client = JDWX\JsonApiClient\HttpClient::withGuzzle( 'https://api.example.com' );
$rsp = $client->request( 'GET', '/v1/resource' );
if ( ! $rsp->isSuccess() ) {
    $uStatus = $rsp->status();
    echo "{$uStatus} Error: ", $rsp->body(), "\n";
    exit( 1 );
}
if ( ! $rsp->isJson() ) {
    $stContentType = $rsp->getOneHeader( 'content-type' ) ?? 'none';
    echo "Error: Expected JSON content-type, got: {$stContentType}\n";
    exit( 1 );
}
$data = $rsp->json();
var_dump( $data );
```

There is also 100% test coverage for this module, which provides additional 
examples of usage.

## Stability

This module is considered stable and is used in production code. However, it was
newly-developed in 2024 based on multiple previous implementations, so it hasn't
received the same level of testing and extensive use as some of the other modules 
in this suite.  That said, it successfully processes hundreds of thousands of API
calls per day.

## History

This module was refactored out of four separate existing modules for 
interacting with JSON APIs in a larger codebase.  It has been rewritten
essentially from scratch.  It was initially released in 2024.
