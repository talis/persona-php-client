<?php

use Talis\Persona\Client\OAuthClients;

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT')) {
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class OAuthClientsTest extends TestBase
{
    // Get oauth client tests
    function testGetOAuthClientEmptyClientIdThrowsException()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid clientId');
        $personaClient = new OAuthClients(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->getOAuthClient('', '');
    }

    function testGetOAuthClientEmptyTokenThrowsException()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid token');
        $personaClient = new OAuthClients(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->getOAuthClient('123', '');
    }

    function testGetOAuthClientThrowsExceptionWhenClientNotFound()
    {
        $this->setExpectedException('Exception', 'Did not retrieve successful response code');
        $mockClient = $this->getMock('Talis\Persona\Client\OAuthClients', ['personaGetOAuthClient'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $mockClient->expects($this->once())
            ->method('personaGetOAuthClient')
            ->will($this->throwException(new Exception('Did not retrieve successful response code')));

        $mockClient->getOAuthClient('123', '456');
    }

    function testGetOAuthClientReturnsClientWhenGupidFound()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\OAuthClients', ['personaGetOAuthClient'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $expectedResponse = [
            'rate_limit' => 1000,
            'rate_duration' => 1800,
            'rate_expires' => 1433516934,
            'call_count' => 0,
            'scope' => [
                'su'
            ]
        ];
        $mockClient->expects($this->once())
            ->method('personaGetOAuthClient')
            ->will($this->returnValue($expectedResponse));

        $client = $mockClient->getOAuthClient('123', '456');
        $this->assertEquals($expectedResponse['rate_limit'], $client['rate_limit']);
        $this->assertEquals($expectedResponse['rate_duration'], $client['rate_duration']);
        $this->assertEquals($expectedResponse['rate_expires'], $client['rate_expires']);
        $this->assertEquals($expectedResponse['call_count'], $client['call_count']);
        $this->assertEquals($expectedResponse['scope'], $client['scope']);
    }

    function testUpdateOAuthClientEmptyGuid()
    {
        $this->setExpectedException('Exception', 'Invalid guid');
        $personaClient = new OAuthClients(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateOAuthClient('', [], '987');
    }

    function testUpdateOAuthClientInvalidGuid()
    {
        $this->setExpectedException('Exception', 'Invalid guid');
        $personaClient = new OAuthClients(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateOAuthClient([], [], '987');
    }

    function testUpdateOAuthClientEmptyProperties()
    {
        $this->setExpectedException('Exception', 'Invalid properties');
        $personaClient = new OAuthClients(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateOAuthClient('123', [], '987');
    }

    function testUpdateOAuthClientInvalidProperties()
    {
        $this->setExpectedException('Exception', 'Invalid properties');
        $personaClient = new OAuthClients(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateOAuthClient('123', 'PROPERTIES', '987');
    }

    function testUpdateOAuthClientInvalidPropertiesKeys()
    {
        $this->setExpectedException('Exception', 'Invalid properties');
        $personaClient = new OAuthClients(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateOAuthClient('123', ['INVALID' => []], '987');
    }

    function testUpdateOAuthClientInvalidPropertiesScopeKeys1()
    {
        $this->setExpectedException('Exception', 'Invalid properties');
        $personaClient = new OAuthClients(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateOAuthClient('123', ['scope' => []], '987');
    }

    function testUpdateOAuthClientInvalidPropertiesScopeKeys2()
    {
        $this->setExpectedException('Exception', 'Invalid properties');
        $personaClient = new OAuthClients(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateOAuthClient('123', ['scope' => ['blah' => '']], '987');
    }

    function testUpdateOAuthClientInvalidPropertiesScopeKeys3()
    {
        $this->setExpectedException('Exception', 'Invalid properties');
        $personaClient = new OAuthClients(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateOAuthClient('123', ['scope' => ['blah' => '', '$add' => 'test']], '987');
    }

    function testUpdateOAuthClientInvalidPropertiesScopeKeys4()
    {
        $this->setExpectedException('Exception', 'Invalid properties');
        $personaClient = new OAuthClients(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateOAuthClient('123',
            ['scope' => ['blah' => '', '$remove' => 'remove-scope', '$add' => 'add-scope']], '987');
    }

    function testUpdateOAuthClientsEmptyToken()
    {
        $this->setExpectedException('Exception', 'Invalid token');
        $personaClient = new OAuthClients(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateOAuthClient('123', ['scope' => ['$add' => 'additional-scope']], '');
    }

    function testUpdateOAuthClientsInvalidToken()
    {
        $this->setExpectedException('Exception', 'Invalid token');
        $personaClient = new OAuthClients(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateOAuthClient('123', ['scope' => ['$add' => 'additional-scope']], ['']);
    }

    function testUpdateOAuthClientPutFails()
    {
        $this->setExpectedException('Exception', 'Could not retrieve OAuth response code');
        $mockClient = $this->getMock('Talis\Persona\Client\OAuthClients', ['personaPatchOAuthClient'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $mockClient->expects($this->once())
            ->method('personaPatchOAuthClient')
            ->will($this->throwException(new Exception('Could not retrieve OAuth response code')));

        $mockClient->updateOAuthClient('guid', ['scope' => ['$add' => 'additional-scope']], '123');
    }

    function testUpdateOAuthClientPutSucceeds()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\OAuthClients', ['personaPatchOAuthClient'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);

        $expectedResponse = []; // 204 has no content
        $mockClient->expects($this->once())
            ->method('personaPatchOAuthClient')
            ->will($this->returnValue($expectedResponse));
        $this->assertEquals($expectedResponse,
            $mockClient->updateOAuthClient('123', ['scope' => ['$add' => 'additional-scope']], '123'));
    }
}
