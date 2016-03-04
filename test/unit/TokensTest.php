<?php

use \Firebase\JWT\JWT;
use Talis\Persona\Client\Tokens;

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT'))
{
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class TokensTest extends TestBase {
    private $_privateKey;
    private $_publicKey;

    public function setUp()
    {
        $this->_privateKey = file_get_contents('../keys/private_key.pem');
        $this->_publicKey = file_get_contents('../keys/public_key.pem');
    }

    function testEmptyConfigThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No config provided to Persona Client'
        );
        $personaClient = new Tokens(array());
    }

    function testNullConfigThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No config provided to Persona Client'
        );
        $personaClient = new Tokens(null);
    }

    function testMissingRequiredConfigParamsThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'Config provided does not contain values for: persona_host,persona_oauth_route,tokencache_redis_host,tokencache_redis_port,tokencache_redis_db'
        );
        $personaClient = new Tokens(array(
            'persona_host' => null,
            'persona_oauth_route' => null,
            'tokencache_redis_host' => null,
            'tokencache_redis_port' => null,
            'tokencache_redis_db' => null
        ));
    }

    function testValidConfigDoesNotThrowException(){
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));
    }

    function testMissingUrlThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No url provided to sign'
        );
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('','mysecretkey',null);

    }

    function testMissingSecretThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No secret provided to sign with'
        );
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl','',null);

    }

    function testPresignUrlNoExpiry() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute','mysecretkey',null);
        $this->assertContains('?expires=',$signedUrl);
    }

    function testPresignUrlNoExpiryAnchor() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute#myAnchor','mysecretkey',null);

        // assert ?expiry comes before #
        $pieces = explode("#",$signedUrl);
        $this->assertTrue(count($pieces)==2);
        $this->assertContains('?expires=',$pieces[0]);

    }

    function testPresignUrlNoExpiryExistingQueryString() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',null);

        $this->assertContains('?myparam=foo&expires=',$signedUrl);
    }

    function testPresignUrlNoExpiryAnchorExistingQueryString() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',null);


        // assert ?expiry comes before #
        $pieces = explode("#",$signedUrl);
        $this->assertTrue(count($pieces)==2);
        $this->assertContains('?myparam=foo&expires=',$pieces[0]);
    }

    function testPresignUrlWithExpiry() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?expires=1234567890&signature=5be20a17931f220ca03d446a25748a9ef707cd508c753760db11f1f95485f1f6',$signedUrl);
    }

    function testPresignUrlWithExpiryAnchor() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute#myAnchor','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?expires=1234567890&signature=c4fbb2b15431ef08e861687bd55fd0ab98bb52eee7a1178bdd10888eadbb48bb#myAnchor',$signedUrl);
    }

    function testPresignUrlWithExpiryExistingQuerystring() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?myparam=foo&expires=1234567890&signature=7675bae38ddea8c2236d208a5003337f926af4ebd33aac03144eb40c69d58804',$signedUrl);
    }

    function testPresignUrlWithExpiryAnchorExistingQuerystring() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?myparam=foo&expires=1234567890&signature=f871db0896f6e893b607d2987ccc838786114b9778b4dbae2b554c2faf9486a1#myAnchor',$signedUrl);
    }

    function testIsPresignedUrlValidTimeInFuture() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute','mysecretkey',"+5 minutes");

        $this->assertTrue($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidTimeInFutureExistingParams() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo','mysecretkey',"+5 minutes");

        $this->assertTrue($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidTimeInFutureExistingParamsAnchor() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"+5 minutes");

        $this->assertTrue($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidTimeInPastExistingParamsAnchor() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"-5 minutes");

        $this->assertFalse($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidRemoveExpires() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"+5 minutes");

        $presignedUrl = str_replace('expires=','someothervar=',$presignedUrl);

        $this->assertFalse($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidRemoveSig() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"+5 minutes");

        $presignedUrl = str_replace('signature=','someothervar=',$presignedUrl);

        $this->assertFalse($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testUseCacheFalseOnObtainToken() {
        $mockClient = $this->getMock('Talis\Persona\Client\Tokens',array('getCacheClient','personaObtainNewToken'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        )));

        $mockClient->expects($this->once())->method("personaObtainNewToken")->will($this->returnValue(array("access_token"=>"foo","expires"=>"100","scopes"=>"su")));
        $mockClient->expects($this->never())->method("getCacheClient");

        $mockClient->obtainNewToken('client_id','client_secret',array('useCache'=>false));
    }

    function testUseCacheTrueOnObtainToken() {
        $mockClient = $this->getMock('Talis\Persona\Client\Tokens',array('getCacheClient','personaObtainNewToken'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        )));

        $mockCache = $this->getMock('\Predis\Client',array("get"),array());
        $mockCache->expects($this->once())->method("get")->will($this->returnValue('{"access_token":"foo","expires":1000,"scopes":"su"}'));

        $mockClient->expects($this->never())->method("personaObtainNewToken");
        $mockClient->expects($this->once())->method("getCacheClient")->will($this->returnValue($mockCache));

        $token = $mockClient->obtainNewToken('client_id','client_secret');
        $this->assertEquals($token['access_token'],"foo");
    }

    function testUseCacheDefaultTrueOnObtainToken() {
        $mockClient = $this->getMock('Talis\Persona\Client\Tokens',array('getCacheClient','personaObtainNewToken'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        )));

        $mockCache = $this->getMock('\Predis\Client',array("get"),array());
        $mockCache->expects($this->once())->method("get")->will($this->returnValue('{"access_token":"foo","expires":1000,"scopes":"su"}'));

        $mockClient->expects($this->never())->method("personaObtainNewToken");
        $mockClient->expects($this->once())->method("getCacheClient")->will($this->returnValue($mockCache));

        $token = $mockClient->obtainNewToken('client_id','client_secret');
        $this->assertEquals($token['access_token'],"foo");
    }

    function testUseCacheNotInCacheObtainToken() {
        $mockClient = $this->getMock('Talis\Persona\Client\Tokens',array('getCacheClient','personaObtainNewToken','cacheToken'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2
        )));

        $mockCache = $this->getMock('\Predis\Client',array("get"),array());
        $mockCache->expects($this->once())->method("get")->will($this->returnValue(''));

        $expectedToken = array("access_token"=>"foo","expires_in"=>"100","scopes"=>"su");
        $cacheKey = "obtain_token:".hash_hmac('sha256','client_id','client_secret');

        $mockClient->expects($this->once())->method("getCacheClient")->will($this->returnValue($mockCache));
        $mockClient->expects($this->once())->method("personaObtainNewToken")->will($this->returnValue($expectedToken));
        $mockClient->expects($this->once())->method("cacheToken")->with($cacheKey,$expectedToken,40);

        $token = $mockClient->obtainNewToken('client_id','client_secret');
        $this->assertEquals($token['access_token'],"foo");
    }

    /**
     * If the JWT doesn't include the user's scopes, retrieve
     * them from Persona
     */
    public function testPersonaFallbackOnJWTEmptyScopes()
    {
        $mockClient = $this->getMock(
            'Talis\Persona\Client\Tokens',
            array(
                'getCacheClient',
                'personaObtainNewToken',
                'cacheToken',
                'retrieveJWTCertificate',
                'performRequest',
            ),
            array(array(
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'tokencache_redis_host' => 'localhost',
                'tokencache_redis_port' => 6379,
                'tokencache_redis_db' => 2
            ))
        );

        $mockCache = $this->getMock('\Predis\Client', array("get"), array());

        $jwt = JWT::encode(
            array(
                'jwtid' => time(),
                'exp' => time() + 60 * 60,
                'nbf' => time() -1,
                'audience' => 'standard_user',
                'scopeCount' => 30,
            ),
            $this->_privateKey,
            'RS256'
        );

        $mockClient->expects($this->once())->method('getCacheClient')->will($this->returnValue($mockCache));
        $mockClient->expects($this->once())->method('retrieveJWTCertificate')->will($this->returnValue($this->_publicKey));
        $mockCache->expects($this->once())->method('get')->will($this->returnValue(''));
        $mockClient->expects($this->once())->method('performRequest')->will($this->returnValue(true));

        $result = $mockClient->validateToken(
            array(
                'access_token' => $jwt,
                'scope' => 'su',
            )
        );

        $this->assertEquals(Talis\Persona\Client\Tokens::VERIFIED_BY_PERSONA, $result);
    }

    /**
     * Use the cached Persona public key
     */
    public function testJWTUseCachePublicKey()
    {
        $mockClient = $this->getMock(
            'Talis\Persona\Client\Tokens',
            array('getCacheClient'),
            array(array(
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'tokencache_redis_host' => 'localhost',
                'tokencache_redis_port' => 6379,
                'tokencache_redis_db' => 2
            ))
        );

        $mockCache = $this->getMock('\Predis\Client', array("get"), array());

        $jwt = JWT::encode(
            array(
                'jwtid' => time(),
                'exp' => time() + 60 * 60,
                'nbf' => time() -1,
                'audience' => 'standard_user',
                'scopes' => array('su'),
            ),
            $this->_privateKey,
            'RS256'
        );

        $mockClient->expects($this->once())->method('getCacheClient')->will($this->returnValue($mockCache));
        $mockCache->expects($this->once())->method('get')->with('public_key')->will($this->returnValue(json_encode($this->_publicKey)));

        $result = $mockClient->validateToken(
            array(
                'access_token' => $jwt,
                'scope' => 'su',
            )
        );

        $this->assertEquals(Talis\Persona\Client\Tokens::VERIFIED_BY_JWT, $result);
    }

    /**
     * If Persona's public key hasn't been cached,
     * retrieve and cache
     */
    public function testJWTUseRemotePublicKey()
    {
        $mockClient = $this->getMock(
            'Talis\Persona\Client\Tokens',
            array('getCacheClient', 'performRequest', 'cacheToken'),
            array(array(
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'tokencache_redis_host' => 'localhost',
                'tokencache_redis_port' => 6379,
                'tokencache_redis_db' => 2
            ))
        );

        $mockCache = $this->getMock('\Predis\Client', array('get'), array());

        $jwt = JWT::encode(
            array(
                'jwtid' => time(),
                'exp' => time() + 60 * 60,
                'nbf' => time() -1,
                'audience' => 'standard_user',
                'scopes' => array('su'),
            ),
            $this->_privateKey,
            'RS256'
        );

        $mockClient->expects($this->once())->method('getCacheClient')->will($this->returnValue($mockCache));
        $mockCache->expects($this->once())->method('get')->with('public_key')->will($this->returnValue(''));
        $mockClient->expects($this->once())->method('performRequest')->will($this->returnValue($this->_publicKey));
        $mockClient->expects($this->once())->method('cacheToken')->with('public_key', $this->_publicKey, 600);

        $result = $mockClient->validateToken(
            array(
                'access_token' => $jwt,
                'scope' => 'su',
            )
        );

        $this->assertEquals(Talis\Persona\Client\Tokens::VERIFIED_BY_JWT, $result);
    }

    /**
     * A expired token should fail
     */
    public function testJWTExpiredToken()
    {
        $mockClient = $this->getMock(
            'Talis\Persona\Client\Tokens',
            array('getCacheClient'),
            array(array(
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'tokencache_redis_host' => 'localhost',
                'tokencache_redis_port' => 6379,
                'tokencache_redis_db' => 2
            ))
        );

        $mockCache = $this->getMock('\Predis\Client', array("get"), array());

        $jwt = JWT::encode(
            array(
                'jwtid' => time(),
                'exp' => time() - 50 ,
                'nbf' => time() - 100,
                'audience' => 'standard_user',
                'scopes' => array('su'),
            ),
            $this->_privateKey,
            'RS256'
        );

        $mockClient->expects($this->once())->method('getCacheClient')->will($this->returnValue($mockCache));
        $mockCache->expects($this->once())->method('get')->with('public_key')->will($this->returnValue(json_encode($this->_publicKey)));

        $result = $mockClient->validateToken(
            array(
                'access_token' => $jwt,
                'scope' => 'su',
            )
        );

        $this->assertEquals(false, $result);
    }

    /**
     * Test that if the token uses a not before assertion
     * that we cannot use the token before a given time
     */
    public function testJWTNotBeforeToken()
    {
        $mockClient = $this->getMock(
            'Talis\Persona\Client\Tokens',
            array('getCacheClient'),
            array(array(
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'tokencache_redis_host' => 'localhost',
                'tokencache_redis_port' => 6379,
                'tokencache_redis_db' => 2
            ))
        );

        $mockCache = $this->getMock('\Predis\Client', array("get"), array());

        $jwt = JWT::encode(
            array(
                'jwtid' => time(),
                'exp' => time() + 101,
                'nbf' => time() + 100,
                'audience' => 'standard_user',
                'scopes' => array('su'),
            ),
            $this->_privateKey,
            'RS256'
        );

        $mockClient->expects($this->once())->method('getCacheClient')->will($this->returnValue($mockCache));
        $mockCache->expects($this->once())->method('get')->with('public_key')->will($this->returnValue(json_encode($this->_publicKey)));

        $result = $mockClient->validateToken(
            array(
                'access_token' => $jwt,
                'scope' => 'su',
            )
        );

        $this->assertEquals(false, $result);
    }

    /**
     * Using the wrong certificate should fail the tokens
     */
    public function testJWTInvalidPublicCert()
    {
        $mockClient = $this->getMock(
            'Talis\Persona\Client\Tokens',
            array('getCacheClient'),
            array(array(
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'tokencache_redis_host' => 'localhost',
                'tokencache_redis_port' => 6379,
                'tokencache_redis_db' => 2
            ))
        );

        $mockCache = $this->getMock('\Predis\Client', array("get"), array());

        $jwt = JWT::encode(
            array(
                'jwtid' => time(),
                'exp' => time() + 100,
                'nbf' => time() - 1,
                'audience' => 'standard_user',
                'scopes' => array('su'),
            ),
            $this->_privateKey,
            'RS256'
        );

        $mockClient->expects($this->once())->method('getCacheClient')->will($this->returnValue($mockCache));
        $mockCache->expects($this->once())->method('get')->with('public_key')->will($this->returnValue(json_encode("invalid cert")));

        try {
            $mockClient->validateToken(
                array(
                    'access_token' => $jwt,
                    'scope' => 'su',
                )
            );

            $this->fail("Exception not thrown");
        } catch (InvalidArgumentException $e) {
        }
    }
}