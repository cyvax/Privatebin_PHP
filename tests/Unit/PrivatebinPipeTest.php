<?php

declare(strict_types=1);

namespace Cyvax\Tests\Unit;

use Cyvax\Clients\Privatebin;
use Cyvax\Clients\PrivatebinPipe;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class PrivatebinPipeTest extends TestCase
{
    private function getOptions(Privatebin $client): array
    {
        $core = (new ReflectionProperty($client, 'core'))->getValue($client);
        return (new ReflectionProperty($core, 'options'))->getValue($core);
    }

    /** Simulates the flat response shape returned by fetch() so decrypt() can be tested offline. */
    private function fetchLike(array $encoded): array
    {
        return array_merge(['status' => 0], $encoded['data']);
    }

    public function testInitReturnsClient(): void
    {
        $this->assertInstanceOf(Privatebin::class, PrivatebinPipe::init());
    }

    public function testAllPipeClosuresAreCallable(): void
    {
        $this->assertIsCallable(PrivatebinPipe::url("https://privatebin.net/"));
        $this->assertIsCallable(PrivatebinPipe::text("Hello"));
        $this->assertIsCallable(PrivatebinPipe::password("secret"));
        $this->assertIsCallable(PrivatebinPipe::expire("1hour"));
        $this->assertIsCallable(PrivatebinPipe::formatter("plaintext"));
        $this->assertIsCallable(PrivatebinPipe::compression("zlib"));
        $this->assertIsCallable(PrivatebinPipe::discussion(true));
        $this->assertIsCallable(PrivatebinPipe::burn(true));
        $this->assertIsCallable(PrivatebinPipe::attachment("/tmp/file.txt"));
        $this->assertIsCallable(PrivatebinPipe::debug(false));
        $this->assertIsCallable(PrivatebinPipe::encode());
        $this->assertIsCallable(PrivatebinPipe::encodeAndPost());
        $this->assertIsCallable(PrivatebinPipe::fetch("abc123"));
        $this->assertIsCallable(PrivatebinPipe::fetchAndDecrypt("abc123", "b58key"));
    }

    public function testPipeChainAppliesAllOptions(): void
    {
        $client = PrivatebinPipe::init()
            |> PrivatebinPipe::url("https://privatebin.net/")
            |> PrivatebinPipe::text("Hello pipe")
            |> PrivatebinPipe::password("secret")
            |> PrivatebinPipe::expire("1hour")
            |> PrivatebinPipe::formatter("markdown")
            |> PrivatebinPipe::compression("none")
            |> PrivatebinPipe::discussion(false)
            |> PrivatebinPipe::burn(false)
            |> PrivatebinPipe::debug(false);

        $this->assertInstanceOf(Privatebin::class, $client);

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
        $result = PrivatebinPipe::init()
            |> PrivatebinPipe::text("Hello")
            |> PrivatebinPipe::encode();

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
        $result = PrivatebinPipe::init()
            |> PrivatebinPipe::text("# markdown")
            |> PrivatebinPipe::formatter("markdown")
            |> PrivatebinPipe::encode();

        $this->assertSame("markdown", $result["data"]["adata"][1]);
    }

    public function testPipeCompressionIsReflectedInAdata(): void
    {
        $result = PrivatebinPipe::init()
            |> PrivatebinPipe::text("no compression")
            |> PrivatebinPipe::compression("none")
            |> PrivatebinPipe::encode();

        $this->assertSame("none", $result["data"]["adata"][0][7]);
    }

    public function testPipeBurnFlagIsReflectedInAdata(): void
    {
        $result = PrivatebinPipe::init()
            |> PrivatebinPipe::text("burn")
            |> PrivatebinPipe::burn(true)
            |> PrivatebinPipe::encode();

        $this->assertSame(1, $result["data"]["adata"][3]);
        $this->assertSame(0, $result["data"]["adata"][2]);
    }

    public function testPipeDiscussionFlagIsReflectedInAdata(): void
    {
        $result = PrivatebinPipe::init()
            |> PrivatebinPipe::text("discussion")
            |> PrivatebinPipe::discussion(true)
            |> PrivatebinPipe::encode();

        $this->assertSame(1, $result["data"]["adata"][2]);
        $this->assertSame(0, $result["data"]["adata"][3]);
    }

    public function testPipeBurnDisablesDiscussion(): void
    {
        $client = PrivatebinPipe::init()
            |> PrivatebinPipe::text("burn")
            |> PrivatebinPipe::discussion(true)
            |> PrivatebinPipe::burn(true);

        $opts = $this->getOptions($client);
        $this->assertTrue($opts["burn"]);
        $this->assertFalse($opts["discussion"]);
    }

    public function testPipeDiscussionDisablesBurn(): void
    {
        $client = PrivatebinPipe::init()
            |> PrivatebinPipe::text("discussion")
            |> PrivatebinPipe::burn(true)
            |> PrivatebinPipe::discussion(true);

        $opts = $this->getOptions($client);
        $this->assertTrue($opts["discussion"]);
        $this->assertFalse($opts["burn"]);
    }

    public function testPipeRoundTripWithZlib(): void
    {
        $text = 'pipe round-trip zlib';

        $client = PrivatebinPipe::init()
            |> PrivatebinPipe::text($text)
            |> PrivatebinPipe::compression('zlib');

        $encoded = PrivatebinPipe::encode()($client);
        $decrypted = $client->decrypt($this->fetchLike($encoded), $encoded['b58']);

        $this->assertSame($text, $decrypted['paste']);
        $this->assertNull($decrypted['attachment']);
        $this->assertNull($decrypted['attachment_name']);
    }

    public function testPipeRoundTripWithNoCompression(): void
    {
        $text = 'pipe round-trip no compression';

        $client = PrivatebinPipe::init()
            |> PrivatebinPipe::text($text)
            |> PrivatebinPipe::compression('none');

        $encoded = PrivatebinPipe::encode()($client);
        $decrypted = $client->decrypt($this->fetchLike($encoded), $encoded['b58']);

        $this->assertSame($text, $decrypted['paste']);
    }

    public function testPipeRoundTripWithPassword(): void
    {
        $text = 'pipe round-trip with password';
        $password = 'test-secret';

        $client = PrivatebinPipe::init()
            |> PrivatebinPipe::text($text)
            |> PrivatebinPipe::password($password);

        $encoded = PrivatebinPipe::encode()($client);
        $decrypted = $client->decrypt($this->fetchLike($encoded), $encoded['b58'], $password);

        $this->assertSame($text, $decrypted['paste']);
    }

    public function testPipeRoundTripWithMarkdownAndNoCompression(): void
    {
        $text = '# markdown heading';

        $client = PrivatebinPipe::init()
            |> PrivatebinPipe::text($text)
            |> PrivatebinPipe::formatter('markdown')
            |> PrivatebinPipe::compression('none');

        $encoded = PrivatebinPipe::encode()($client);
        $decrypted = $client->decrypt($this->fetchLike($encoded), $encoded['b58']);

        $this->assertSame($text, $decrypted['paste']);
        $this->assertSame("markdown", $encoded["data"]["adata"][1]);
    }
}