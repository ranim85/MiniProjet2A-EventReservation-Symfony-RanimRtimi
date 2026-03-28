<?php
namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthApiControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testRegisterOptionsReturnsChallenge(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register/options',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'testuser_' . uniqid()])
        );

        $this->assertResponseIsSuccessful();

        $response = json_decode(
            $this->client->getResponse()->getContent(),
            true
        );

        $this->assertArrayHasKey('challenge', $response);
        $this->assertArrayHasKey('rp', $response);
        $this->assertArrayHasKey('user', $response);
        $this->assertArrayHasKey('pubKeyCredParams', $response);
        $this->assertNotEmpty($response['challenge']);
    }

    public function testLoginOptionsReturnsChallenge(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login/options',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseIsSuccessful();

        $response = json_decode(
            $this->client->getResponse()->getContent(),
            true
        );

        $this->assertArrayHasKey('challenge', $response);
        $this->assertArrayHasKey('rpId', $response);
        $this->assertNotEmpty($response['challenge']);
    }

    public function testMeEndpointRequiresAuth(): void
    {
        $this->client->request('GET', '/api/auth/me');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeEndpointWithValidToken(): void
    {
        $user = new User();
        $user->setUsername('api-test-' . uniqid());
        $user->setPasswordHash('hashed');
        $user->setRoles(['ROLE_USER']);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();

        $token = static::getContainer()
            ->get('lexik_jwt_authentication.jwt_manager')
            ->create($user);

        $this->client->request(
            'GET',
            '/api/auth/me',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();

        $response = json_decode(
            $this->client->getResponse()->getContent(),
            true
        );

        $this->assertArrayHasKey('username', $response);
    }

    public function testRegisterOptionsWithEmptyUsernameReturns400(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register/options',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => ''])
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode(
            $this->client->getResponse()->getContent(),
            true
        );

        $this->assertArrayHasKey('error', $response);
    }

    public function testRegisterVerifyWithoutDataReturns400(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register/verify',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(400);
    }
}