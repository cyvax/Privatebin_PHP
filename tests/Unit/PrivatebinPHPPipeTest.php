<?php

declare(strict_types=1);

namespace Cyvax\Tests\Unit;

use Cyvax\Clients\PrivatebinPHP;
use Cyvax\Clients\PrivatebinPHPPipe;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class PrivatebinPHPPipeTest extends TestCase
{
    private function getOptions(PrivatebinPHP $client): array
    {
        return (new ReflectionProperty($client, 'options'))->getValue($client);
    }

    /** Simulates the flat response shape returned by fetch() so decrypt() can be tested offline. */
    private function fetchLike(array $encoded): array
    {
        return array_merge(['status' => 0], $encoded['data']);
    }

    public function testInitReturnsClient(): void
    {
        $this->assertInstanceOf(PrivatebinPHP::class, PrivatebinPHPPipe::init());
    }

    public function testAllPipeClosuresAreCallable(): void
    {
        $this->assertIsCallable(PrivatebinPHPPipe::url("https://privatebin.net/"));
        $this->assertIsCallable(PrivatebinPHPPipe::text("Hello"));
        $this->assertIsCallable(PrivatebinPHPPipe::password("secret"));
        $this->assertIsCallable(PrivatebinPHPPipe::expire("1hour"));
        $this->assertIsCallable(PrivatebinPHPPipe::formatter("plaintext"));
        $this->assertIsCallable(PrivatebinPHPPipe::compression("zlib"));
        $this->assertIsCallable(PrivatebinPHPPipe::discussion(true));
        $this->assertIsCallable(PrivatebinPHPPipe::burn(true));
        $this->assertIsCallable(PrivatebinPHPPipe::attachment("/tmp/file.txt"));
        $this->assertIsCallable(PrivatebinPHPPipe::debug(false));
        $this->assertIsCallable(PrivatebinPHPPipe::encode());
        $this->assertIsCallable(PrivatebinPHPPipe::encodeAndPost());
        $this->assertIsCallable(PrivatebinPHPPipe::fetch("abc123"));
        $this->assertIsCallable(PrivatebinPHPPipe::fetchAndDecrypt("abc123", "b58key"));
    }

    public function testPipeChainAppliesAllOptions(): void
    {
        $client = PrivatebinPHPPipe::init()
            |> PrivatebinPHPPipe::url("https://privatebin.net/")
            |> PrivatebinPHPPipe::text("Hello pipe")
            |> PrivatebinPHPPipe::password("secret")
            |> PrivatebinPHPPipe::expire("1hour")
            |> PrivatebinPHPPipe::formatter("markdown")
            |> PrivatebinPHPPipe::compression("none")
            |> PrivatebinPHPPipe::discussion(false)
            |> PrivatebinPHPPipe::burn(false)
            |> PrivatebinPHPPipe::debug(false);

        $this->assertInstanceOf(PrivatebinPHP::class, $client);

        $opts = $this->getOptions($client);
        $this->assertSame("https://privatebin.net/", $opts["url"]);
        $this->assertSame("Hello pipe", $opts["text"]);
        $this->assertSame("secret", $opts["password"]);
        $this->assertSame("1hour", $opts["expire"]);
        $this->assertSame("markdown", $opts["formatter"]);
        $this->assertSame("none", $opts["compression"]);
        $this->assertFalse($opts["discussion"]);
        $this->assertFalse($opts["burn"]);
        $this->assertFalse($opts["debug"]);
    }

    public function testPipeEncodeReturnsCorrectStructure(): void
    {
        $result = PrivatebinPHPPipe::init()
            |> PrivatebinPHPPipe::text("Hello")
            |> PrivatebinPHPPipe::encode();

        $this->assertArrayHasKey("data", $result);
        $this->assertArrayHasKey("b58", $result);
        $this->assertSame(2, $result["data"]["v"]);
        $this->assertArrayHasKey("adata", $result["data"]);
        $this->assertArrayHasKey("ct", $result["data"]);
        $this->assertArrayHasKey("meta", $result["data"]);
        $this->assertNotEmpty($result["b58"]);
    }

    public function testPipeFormatterIsReflectedInAdata(): void
    {
        $result = PrivatebinPHPPipe::init()
            |> PrivatebinPHPPipe::text("# markdown")
            |> PrivatebinPHPPipe::formatter("markdown")
            |> PrivatebinPHPPipe::encode();

        $this->assertSame("markdown", $result["data"]["adata"][1]);
    }

    public function testPipeCompressionIsReflectedInAdata(): void
    {
        $result = PrivatebinPHPPipe::init()
            |> PrivatebinPHPPipe::text("no compression")
            |> PrivatebinPHPPipe::compression("none")
            |> PrivatebinPHPPipe::encode();

        $this->assertSame("none", $result["data"]["adata"][0][7]);
    }

    public function testPipeBurnFlagIsReflectedInAdata(): void
    {
        $result = PrivatebinPHPPipe::init()
            |> PrivatebinPHPPipe::text("burn")
            |> PrivatebinPHPPipe::burn(true)
            |> PrivatebinPHPPipe::encode();

        $this->assertSame(1, $result["data"]["adata"][3]);
        $this->assertSame(0, $result["data"]["adata"][2]);
    }

    public function testPipeDiscussionFlagIsReflectedInAdata(): void
    {
        $result = PrivatebinPHPPipe::init()
            |> PrivatebinPHPPipe::text("discussion")
            |> PrivatebinPHPPipe::discussion(true)
            |> PrivatebinPHPPipe::encode();

        $this->assertSame(1, $result["data"]["adata"][2]);
        $this->assertSame(0, $result["data"]["adata"][3]);
    }

    public function testPipeBurnDisablesDiscussion(): void
    {
        $client = PrivatebinPHPPipe::init()
            |> PrivatebinPHPPipe::text("burn")
            |> PrivatebinPHPPipe::discussion(true)
            |> PrivatebinPHPPipe::burn(true);

        $opts = $this->getOptions($client);
        $this->assertTrue($opts["burn"]);
        $this->assertFalse($opts["discussion"]);
    }

    public function testPipeDiscussionDisablesBurn(): void
    {
        $client = PrivatebinPHPPipe::init()
            |> PrivatebinPHPPipe::text("discussion")
            |> PrivatebinPHPPipe::burn(true)
            |> PrivatebinPHPPipe::discussion(true);

        $opts = $this->getOptions($client);
        $this->assertTrue($opts["discussion"]);
        $this->assertFalse($opts["burn"]);
    }

    public function testPipeRoundTripWithZlib(): void
    {
        $text = 'pipe round-trip zlib';

        $client = PrivatebinPHPPipe::init()
            |> PrivatebinPHPPipe::text($text)
            |> PrivatebinPHPPipe::compression('zlib');

        $encoded = PrivatebinPHPPipe::encode()($client);
        $decrypted = $client->decrypt($this->fetchLike($encoded), $encoded['b58']);

        $this->assertSame($text, $decrypted['paste']);
        $this->assertNull($decrypted['attachment']);
        $this->assertNull($decrypted['attachment_name']);
    }

    public function testPipeRoundTripWithNoCompression(): void
    {
        $text = 'pipe round-trip no compression';

        $client = PrivatebinPHPPipe::init()
            |> PrivatebinPHPPipe::text($text)
            |> PrivatebinPHPPipe::compression('none');

        $encoded = PrivatebinPHPPipe::encode()($client);
        $decrypted = $client->decrypt($this->fetchLike($encoded), $encoded['b58']);

        $this->assertSame($text, $decrypted['paste']);
    }

    public function testPipeRoundTripWithPassword(): void
    {
        $text = 'pipe round-trip with password';
        $password = 'test-secret';

        $client = PrivatebinPHPPipe::init()
            |> PrivatebinPHPPipe::text($text)
            |> PrivatebinPHPPipe::password($password);

        $encoded = PrivatebinPHPPipe::encode()($client);
        $decrypted = $client->decrypt($this->fetchLike($encoded), $encoded['b58'], $password);

        $this->assertSame($text, $decrypted['paste']);
    }

    public function testPipeRoundTripWithMarkdownAndNoCompression(): void
    {
        $text = '# markdown heading';

        $client = PrivatebinPHPPipe::init()
            |> PrivatebinPHPPipe::text($text)
            |> PrivatebinPHPPipe::formatter('markdown')
            |> PrivatebinPHPPipe::compression('none');

        $encoded = PrivatebinPHPPipe::encode()($client);
        $decrypted = $client->decrypt($this->fetchLike($encoded), $encoded['b58']);

        $this->assertSame($text, $decrypted['paste']);
        $this->assertSame("markdown", $encoded["data"]["adata"][1]);
    }
}