<?php

declare(strict_types=1);

namespace Cyvax\Tests\E2E;

use Cyvax\Clients\PrivatebinPHP;
use Cyvax\Clients\PrivatebinPHPPipe;
use Cyvax\Clients\PrivatebinSnakeCase;
use Cyvax\Encoding\Base58;
use Cyvax\Exceptions\PrivatebinException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;

/**
 * End-to-end integration tests against a real PrivateBin instance.
 *
 * These tests verify that all public client APIs can successfully:
 *
 * - Encode payloads
 * - Post encrypted pastes
 * - Fetch encrypted pastes
 * - Decrypt fetched payloads
 * - Handle attachments
 * - Use different formatters and compression modes
 * - Support password-protected pastes
 * - Support discussion and burn-after-reading options
 * - Verify native and GMP Base58 implementations interoperate
 * - Verify TTL expiration behavior
 *
 * Covered clients:
 *
 * - PrivatebinPHP (modern camelCase API)
 * - PrivatebinSnakeCase
 * - PrivatebinPHP backward-compatibility alias
 * - PrivatebinPHPPipe
 *
 * A running PrivateBin test instance is required before executing this suite.
 *
 * This suite uses the PHP 8.5 pipe operator for PrivatebinPHPPipe coverage.
 * Run these E2E tests with PHP 8.5 or later.
 */
#[Group('e2e')]
final class PrivatebinE2ETest extends TestCase
{
    private const URL = 'http://localhost/PrivateBin/';

    public function testModernClientCanEncodeAndPostText(): void
    {
        $result = (new PrivatebinPHP())
            ->setUrl(self::URL)
            ->setText('E2E modern client')
            ->setExpire('5min')
            ->setFormatter('plaintext')
            ->setCompression('zlib')
            ->setDiscussion(false)
            ->setBurn(false)
            ->encodeAndPost();

        $this->assertPostResult($result, expectedText: 'E2E modern client');
    }

    public function testModernClientCanEncodeThenPost(): void
    {
        $client = (new PrivatebinPHP())
            ->setUrl(self::URL)
            ->setText('E2E encode then post')
            ->setExpire('5min');

        $encoded = $client->encode();

        $this->assertArrayHasKey('data', $encoded);
        $this->assertArrayHasKey('b58', $encoded);

        $result = $client->post($encoded);

        $this->assertPostResult($result, expectedText: 'E2E encode then post');
    }

    public function testModernClientCanPostPasswordProtectedPaste(): void
    {
        $result = (new PrivatebinPHP())
            ->setUrl(self::URL)
            ->setText('E2E password protected')
            ->setPassword('secret')
            ->setExpire('5min')
            ->encodeAndPost();

        $this->assertPostResult($result, 'secret', 'E2E password protected');
    }

    public function testModernClientCanPostWithoutCompression(): void
    {
        $result = (new PrivatebinPHP())
            ->setUrl(self::URL)
            ->setText('E2E no compression')
            ->setCompression('none')
            ->encodeAndPost();

        $this->assertPostResult($result, expectedText: 'E2E no compression');
    }

    public function testModernClientCanPostMarkdown(): void
    {
        $result = (new PrivatebinPHP())
            ->setUrl(self::URL)
            ->setText('# E2E markdown')
            ->setFormatter('markdown')
            ->encodeAndPost();

        $this->assertPostResult($result, expectedText: '# E2E markdown');
    }

    public function testModernClientCanPostWithDiscussionEnabled(): void
    {
        $result = (new PrivatebinPHP())
            ->setUrl(self::URL)
            ->setText('E2E discussion')
            ->setDiscussion(true)
            ->encodeAndPost();

        $this->assertPostResult($result, expectedText: 'E2E discussion');
    }

    public function testModernClientCanPostBurnAfterReading(): void
    {
        $result = (new PrivatebinPHP())
            ->setUrl(self::URL)
            ->setText('E2E burn')
            ->setBurn(true)
            ->encodeAndPost();

        $this->assertPostResult($result, decrypt: false);

        $client = (new PrivatebinPHP())->setUrl(self::URL);

        $decrypted = $client->fetchAndDecrypt($result['requests_result']->id, $result['b58']);
        $this->assertSame('E2E burn', $decrypted['paste']);

        $this->expectException(PrivatebinException::class);
        $client->fetch($result['requests_result']->id);
    }

    public function testModernClientCanPostAttachment(): void
    {
        $result = (new PrivatebinPHP())
            ->setUrl(self::URL)
            ->setText('E2E attachment')
            ->setAttachment(dirname(__DIR__) . '/Fixtures/attachment.txt', 'attachment.txt')
            ->encodeAndPost();

        $this->assertPostResult($result, expectedText: 'E2E attachment');

        $decrypted = (new PrivatebinPHP())
            ->setUrl(self::URL)
            ->fetchAndDecrypt($result['requests_result']->id, $result['b58']);

        $this->assertSame('attachment.txt', $decrypted['attachment_name']);
        $this->assertNotNull($decrypted['attachment']);
        $this->assertStringStartsWith('data:text/plain;base64,', $decrypted['attachment']);
    }

    public function testSnakeCaseClientCanEncodeAndPost(): void
    {
        $result = (new PrivatebinSnakeCase())
            ->set_url(self::URL)
            ->set_text('E2E snake case')
            ->set_expire('5min')
            ->set_formatter('plaintext')
            ->set_compression('zlib')
            ->set_discussion(false)
            ->set_burn(false)
            ->encode_and_post();

        $this->assertPostResult($result, expectedText: 'E2E snake case');
    }

    public function testBackwardCompatiblePrivatebinPHPAliasCanEncodeAndPost(): void
    {
        $this->assertTrue(class_exists(\Cyvax\PrivatebinPHP::class));

        /** @var PrivatebinSnakeCase $client */
        $client = new \Cyvax\PrivatebinPHP();
        $result = $client
            ->set_url(self::URL)
            ->set_text('E2E backward compatible alias')
            ->set_expire('5min')
            ->encode_and_post();

        $this->assertPostResult($result, expectedText: 'E2E backward compatible alias');
    }

    public function testPipeClientCanEncodeAndPost(): void
    {
        $result = PrivatebinPHPPipe::init(self::URL)
            |> PrivatebinPHPPipe::text('E2E pipe client')
            |> PrivatebinPHPPipe::expire('5min')
            |> PrivatebinPHPPipe::formatter('plaintext')
            |> PrivatebinPHPPipe::compression('zlib')
            |> PrivatebinPHPPipe::discussion(false)
            |> PrivatebinPHPPipe::burn(false)
            |> PrivatebinPHPPipe::debug(false)
            |> PrivatebinPHPPipe::encodeAndPost();

        $this->assertPostResult($result, expectedText: 'E2E pipe client');
    }

    /**
     * @throws PrivatebinException
     */
    public function testPasteExpiresAfterTtl(): void
    {
        $result = new PrivatebinPHP()
            ->setUrl(self::URL)
            ->setText('E2E expire test')
            ->setExpire('10sec', true)
            ->encodeAndPost();

        $this->assertPostResult($result, expectedText: 'E2E expire test');

        sleep(15);

        $this->expectException(PrivatebinException::class);
        new PrivatebinPHP()->setUrl(self::URL)->fetch($result['requests_result']->id);
    }

    public function testPipeClientCanEncodeOnly(): void
    {
        $encoded = PrivatebinPHPPipe::init(self::URL)
            |> PrivatebinPHPPipe::text('E2E pipe encode only')
            |> PrivatebinPHPPipe::encode();

        $this->assertArrayHasKey('data', $encoded);
        $this->assertArrayHasKey('b58', $encoded);
        $this->assertArrayHasKey('ct', $encoded['data']);
    }

    /**
     * @throws PrivatebinException
     */
    public function testPasswordProtectedPasteFailsWithWrongPassword(): void
    {
        $result = new PrivatebinPHP()
            ->setUrl(self::URL)
            ->setText('E2E wrong password')
            ->setPassword('correct')
            ->setExpire('5min')
            ->encodeAndPost();

        $this->assertPostResult($result, 'correct', 'E2E wrong password');

        $this->expectException(PrivatebinException::class);
        new PrivatebinPHP()
            ->setUrl(self::URL)
            ->fetchAndDecrypt($result['requests_result']->id, $result['b58'], 'wrong');
    }

    /**
     * @throws PrivatebinException
     * @throws ReflectionException
     */
    public function testBase58GmpEncodeNativeDecodeInteroperability(): void
    {
        if (!extension_loaded('gmp')) {
            $this->markTestSkipped('ext-gmp not available');
        }

        $text = 'E2E Base58 GMP encode native decode';
        $client = new PrivatebinPHP()->setUrl(self::URL)->setText($text)->setExpire('5min');

        $encoded = $client->encode();
        $base58 = new Base58();

        $rawKey = $this->callBase58('decodeGmp', $base58, $encoded['b58']);
        $nativeB58 = $this->callBase58('encodeNative', $base58, $rawKey);

        $this->assertSame($encoded['b58'], $nativeB58);

        $encoded['b58'] = $nativeB58;
        $result = $client->post($encoded);

        $decrypted = $client->fetchAndDecrypt($result['requests_result']->id, $result['b58']);

        $this->assertSame($text, $decrypted['paste']);
    }

    /**
     * @throws PrivatebinException
     * @throws ReflectionException
     */
    public function testBase58NativeEncodeGmpDecodeInteroperability(): void
    {
        if (!extension_loaded('gmp')) {
            $this->markTestSkipped('ext-gmp not available');
        }

        $text = 'E2E Base58 native encode GMP decode';
        $client = new PrivatebinPHP()->setUrl(self::URL)->setText($text)->setExpire('5min');

        $encoded = $client->encode();
        $base58 = new Base58();

        $rawKey = $this->callBase58('decodeNative', $base58, $encoded['b58']);
        $gmpB58 = $this->callBase58('encodeGmp', $base58, $rawKey);

        $this->assertSame($encoded['b58'], $gmpB58);

        $encoded['b58'] = $gmpB58;
        $result = $client->post($encoded);

        $decrypted = $client->fetchAndDecrypt($result['requests_result']->id, $result['b58']);

        $this->assertSame($text, $decrypted['paste']);
    }

    /**
     * @param string $method
     * @param Base58 $base58
     * @param string $value
     * @return string
     * @throws ReflectionException
     */
    private function callBase58(string $method, Base58 $base58, string $value): string
    {
        return (string) new ReflectionMethod(Base58::class, $method)->invoke($base58, $value);
    }

    /**
     * @throws PrivatebinException
     */
    private function assertPostResult(
        array $result,
        ?string $password = null,
        ?string $expectedText = null,
        bool $decrypt = true
    ): void {
        $this->assertArrayHasKey('requests_result', $result);
        $this->assertArrayHasKey('b58', $result);

        $response = $result['requests_result'];

        $this->assertIsObject($response);
        $this->assertObjectHasProperty('status', $response);

        $message = property_exists($response, 'message')
            ? $response->message
            : print_r($response, true);

        $this->assertSame(0, $response->status, $message);

        $this->assertObjectHasProperty('id', $response);
        $this->assertNotEmpty($response->id);

        $this->assertObjectHasProperty('url', $response);
        $this->assertNotEmpty($response->url);
        $this->assertStringContainsString($response->id, $response->url);

        $this->assertObjectHasProperty('deletetoken', $response);
        $this->assertNotEmpty($response->deletetoken);

        $this->assertNotEmpty($result['b58']);

        if ($decrypt) {
            $decrypted = new PrivatebinPHP()
                ->setUrl(self::URL)
                ->fetchAndDecrypt($response->id, $result['b58'], $password);

            $this->assertIsArray($decrypted);
            $this->assertArrayHasKey('paste', $decrypted);

            if ($expectedText !== null) {
                $this->assertSame($expectedText, $decrypted['paste']);
            }
        }
    }
}
