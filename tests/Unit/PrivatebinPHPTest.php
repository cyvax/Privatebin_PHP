<?php

declare(strict_types=1);

namespace Cyvax\Tests\Unit;

use Cyvax\Clients\PrivatebinPHP;
use Cyvax\Exceptions\PrivatebinException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionProperty;

final class PrivatebinPHPTest extends TestCase
{
    public function testItCanBeCreated(): void
    {
        $client = new PrivatebinPHP();

        $this->assertInstanceOf(PrivatebinPHP::class, $client);
    }

    public function testSettersAreFluent(): void
    {
        $client = new PrivatebinPHP();

        $this->assertSame($client, $client->setUrl("https://privatebin.net/"));
        $this->assertSame($client, $client->setText("Hello"));
        $this->assertSame($client, $client->setPassword("secret"));
        $this->assertSame($client, $client->setExpire("1hour"));
        $this->assertSame($client, $client->setFormatter("plaintext"));
        $this->assertSame($client, $client->setCompression("zlib"));
        $this->assertSame($client, $client->setDiscussion(true));
        $this->assertSame($client, $client->setBurn(true));
        $this->assertSame($client, $client->setDebug(false));
    }

    public function testInvalidExpireThrowsException(): void
    {
        $this->expectException(PrivatebinException::class);

        new PrivatebinPHP()->setExpire("invalid");
    }

    /**
     * @throws PrivatebinException
     */
    public function testInvalidExpireCanBeBypassed(): void
    {
        $client = new PrivatebinPHP();

        $this->assertSame($client, $client->setExpire("custom-expire", true));
    }

    public function testInvalidFormatterThrowsException(): void
    {
        $this->expectException(PrivatebinException::class);

        new PrivatebinPHP()->setFormatter("invalid");
    }

    public function testInvalidFormatterCanBeBypassed(): void
    {
        $client = new PrivatebinPHP();

        $this->assertSame($client, $client->setFormatter("custom-formatter", true));
    }

    public function testInvalidCompressionThrowsException(): void
    {
        $this->expectException(PrivatebinException::class);

        new PrivatebinPHP()->setCompression("gzip");
    }

    public function testDiscussionDisablesBurn(): void
    {
        $client = new PrivatebinPHP();

        $client
            ->setBurn(true)
            ->setDiscussion(true);

        $options = $this->getOptions($client);

        $this->assertTrue($options["discussion"]);
        $this->assertFalse($options["burn"]);
    }

    public function testBurnDisablesDiscussion(): void
    {
        $client = new PrivatebinPHP();

        $client
            ->setDiscussion(true)
            ->setBurn(true);

        $options = $this->getOptions($client);

        $this->assertTrue($options["burn"]);
        $this->assertFalse($options["discussion"]);
    }

    public function testAttachmentCanBeSet(): void
    {
        $client = new PrivatebinPHP();

        $client->setAttachment(dirname(__DIR__) . "/fixtures/attachment.txt", "custom.txt");

        $options = $this->getOptions($client);

        $this->assertNotEmpty($options["attachment"]);
        $this->assertSame("custom.txt", $options["attachment_name"]);
        $this->assertStringStartsWith("data:text/plain;base64,", $options["attachment"]);
    }

    /**
     * @throws PrivatebinException
     */
    public function testEncodeReturnsDataAndB58(): void
    {
        $result = new PrivatebinPHP()
            ->setText("Hello")
            ->encode();

        $this->assertArrayHasKey("data", $result);
        $this->assertArrayHasKey("b58", $result);
        $this->assertArrayHasKey("v", $result["data"]);
        $this->assertArrayHasKey("adata", $result["data"]);
        $this->assertArrayHasKey("ct", $result["data"]);
        $this->assertArrayHasKey("meta", $result["data"]);

        $this->assertSame(2, $result["data"]["v"]);
    }

    /**
     * @param PrivatebinPHP $client
     * @return array
     * @throws ReflectionException
     */
    private function getOptions(PrivatebinPHP $client): array
    {
        $property = new ReflectionProperty($client, "options");

        return $property->getValue($client);
    }
}
