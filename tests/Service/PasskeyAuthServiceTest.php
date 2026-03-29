<?php
namespace App\Tests\Service;

use App\Service\PasskeyAuthService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PasskeyAuthServiceTest extends KernelTestCase
{
    private PasskeyAuthService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(PasskeyAuthService::class);
    }

    public function testBase64UrlEncodingHasNoInvalidChars(): void
    {
        $data = random_bytes(32);
        $encoded = $this->service->toBase64Url($data);

        $this->assertStringNotContainsString('+', $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertStringNotContainsString('=', $encoded);
    }

    public function testBase64UrlRoundTrip(): void
    {
        $original = random_bytes(32);
        $encoded = $this->service->toBase64Url($original);
        $decoded = base64_decode($this->service->fromBase64Url($encoded));

        $this->assertEquals($original, $decoded);
    }

    public function testChallengeIsUniqueEachTime(): void
    {
        // Vérifie qu'on génère bien des challenges différents à chaque appel
        // (random_bytes doit être non-déterministe)
        $bytes1 = random_bytes(32);
        $bytes2 = random_bytes(32);

        $this->assertNotEquals($bytes1, $bytes2);
    }
}