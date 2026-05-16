<?php

declare(strict_types=1);

namespace Cyvax\Tests\Unit;

use Cyvax\Clients\Privatebin;
use Cyvax\Exceptions\PrivatebinException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionProperty;

final class PrivatebinTest extends TestCase
{
    public function testItCanBeCreated(): void
    {
        $client = new Privatebin();

        $this->assertInstanceOf(Privatebin::class, $client);
    }

    public function testSettersAreFluent(): void
    {
        $client = new Privatebin();

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

        new Privatebin()->setExpire("invalid");
    }

    /**
     * @throws PrivatebinException
     */
    public function testInvalidExpireCanBeBypassed(): void
    {
        $client = new Privatebin();

        $this->assertSame($client, $client->setExpire("custom-expire", true));
    }

    public function testInvalidFormatterThrowsException(): void
    {
        $this->expectException(PrivatebinException::class);

        new Privatebin()->setFormatter("invalid");
    }

    public function testInvalidFormatterCanBeBypassed(): void
    {
        $client = new Privatebin();

        $this->assertSame($client, $client->setFormatter("custom-formatter", true));
    }

    public function testInvalidCompressionThrowsException(): void
    {
        $this->expectException(PrivatebinException::class);

        new Privatebin()->setCompression("gzip");
    }

    public function testDiscussionDisablesBurn(): void
    {
        $client = new Privatebin();

        $client
            ->setBurn(true)
            ->setDiscussion(true);

        $options = $this->getOptions($client);

        $this->assertTrue($options["discussion"]);
        $this->assertFalse($options["burn"]);
    }

    public function testBurnDisablesDiscussion(): void
    {
        $client = new Privatebin();

        $client
            ->setDiscussion(true)
            ->setBurn(true);

        $options = $this->getOptions($client);

        $this->assertTrue($options["burn"]);
        $this->assertFalse($options["discussion"]);
    }

    public function testAttachmentCanBeSet(): void
    {
        $client = new Privatebin();

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
        $result = new Privatebin()
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
     * @param Privatebin $client
     * @return array
     * @throws ReflectionException
     */
    private function getOptions(Privatebin $client): array
    {
        $core = (new ReflectionProperty($client, 'core'))->getValue($client);
        return (new ReflectionProperty($core, 'options'))->getValue($core);
    }
}
