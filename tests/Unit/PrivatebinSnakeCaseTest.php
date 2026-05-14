<?php

declare(strict_types=1);


namespace Cyvax\Tests\Unit;

use Cyvax\Clients\PrivatebinSnakeCase;
use Cyvax\PrivatebinPHP;
use PHPUnit\Framework\TestCase;

final class

PrivatebinSnakeCaseTest extends TestCase
{
    public function testSnakeCaseClientCanBeCreated(): void
    {
        $client = new PrivatebinSnakeCase();

        $this->assertInstanceOf(PrivatebinSnakeCase::class, $client);
    }

    public function testBackwardCompatiblePrivatebinPHPAliasExists(): void
    {
        $client = new PrivatebinPHP();

        $this->assertTrue($client instanceof PrivatebinSnakeCase);
    }

    public function testSnakeCaseSettersAreFluent(): void
    {
        $client = new PrivatebinSnakeCase();

        $this->assertSame($client, $client->set_url("https://privatebin.net/"));
        $this->assertSame($client, $client->set_text("Hello"));
        $this->assertSame($client, $client->set_password("secret"));
        $this->assertSame($client, $client->set_expire("1hour"));
        $this->assertSame($client, $client->set_formatter("plaintext"));
        $this->assertSame($client, $client->set_compression("zlib"));
        $this->assertSame($client, $client->set_discussion(true));
        $this->assertSame($client, $client->set_burn(true));
        $this->assertSame($client, $client->set_debug(false));
    }

    public function testSnakeCaseEncodeAndPostMethodExists(): void
    {
        $client = new PrivatebinSnakeCase();

        $this->assertTrue(method_exists($client, "encode_and_post"));
    }
}
