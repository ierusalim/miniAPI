<?php
namespace ierusalim\miniAPI;

class miniAPIclientTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var miniAPIclient
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new miniAPIclient;
        $this->resetServerUrl();
    }

    protected function resetServerUrl()
    {
        $ch = $this->object;

        $localenv = "../localenv.php";
        if (\is_file($localenv)) {
            include $localenv;
        } else {
            $miniAPI_url = null;
        }
        $ch->setServerUrl($miniAPI_url);
    }

    public function testConstructEmpty()
    {
        echo "PHP " . phpversion();
        $r = new miniAPIclient();

        //check "use miniAPIslots" on/off
        if (isset($r->slot_hooks)) {
            echo " slot trait included (async-mode on)\n";
        } else {
            echo " slot trait NOT included (async-mode off)\n";
        }

        //check default host value for empty-constructor mode
        $this->assertEquals('127.0.0.1', $r->host);
    }
    public function testConstructWithURL()
    {
        $anyhost = "anyhost";
        $anyport = 888;
        $anypath = "/anypath";
        foreach(['https', 'http'] as $scheme) {
            $tstuser = 'usertest' . $scheme;
            $tstpass = 'passtest' . $scheme;
            $server_url = "{$scheme}://{$tstuser}:{$tstpass}@{$anyhost}:{$anyport}{$anypath}";
            $r = new miniAPIclient($server_url);
            $this->assertEquals($scheme, $r->scheme);
            $this->assertEquals($anyhost, $r->host);
            $this->assertEquals($anyport, $r->port);
            $this->assertEquals($anypath, $r->path);
            $this->assertTrue($r->checkAuthPars(['user' => $tstuser, 'pass' => $tstpass]));
        }
        // test checkAuthPars for return false
        $this->assertFalse($r->checkAuthPars(['user'=>'test', 'pass'=>'test']));
    }

    public function testConstructWithHostEtc()
    {
        $anyhost = '1.2.3.4';
        $anyport = 5678;
        $tstuser = 'username-test';
        $tstpass = 'test-pass';
        $r = new miniAPIclient($anyhost, $anyport, $tstuser, $tstpass);
        $this->assertEquals($anyhost, $r->host);
        $this->assertEquals($anyport, $r->port);
        $this->assertTrue($r->checkAuthPars(['user' => $tstuser, 'pass' => $tstpass]));
    }

    /**
     * @covers ierusalim\miniAPI\miniAPIclient::setServerUrl
     * @todo   Implement testSetServerUrl().
     */
    public function testSetServerUrl()
    {
        $ch = $this->object;
        $anyhost = "anyhost";
        $anyport = 888;
        $anypath = "/anypath";
        foreach(['https', 'http'] as $scheme) {
            $server_url = "{$scheme}://{$anyhost}:{$anyport}{$anypath}";
            $ch->setServerUrl($server_url);
            $this->assertEquals($scheme, $ch->scheme);
            $this->assertEquals($anyhost, $ch->host);
            $this->assertEquals($anyport, $ch->port);
            $this->assertEquals($anypath, $ch->path);
        }
        $this->resetServerUrl();
    }

    public function testSetServerUrlException()
    {
        $ch = $this->object;
        $this->setExpectedException("\Exception");
        //because ftp is illegal for api, must throw exception
        $ch->setServerUrl("ftp://8.8.8.8");
    }

    /**
     * @covers ierusalim\miniAPI\miniAPIclient::doApiCall
     * @todo   Implement testDoApiCall().
     */
    public function testDoApiCall()
    {
        $ch = $this->object;

        $ans = $ch->doApiCall(0, ['query' => 'SELECT version()']);

        $curl_error = $ans['curl_error'];
        if ($curl_error) {
            echo "\nCURL_ERROR: $curl_error";
            $this->assertTrue(empty($curl_error));
        } else {
            echo "Version response: {$ans['response']}Starting tests...\n";
        }

        $ch->debug = true;
        $ch->hook_before_api_call = function ($url, $obj) {
            return "https://ierusalim.github.io";
        };

        $file = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . '.gitignore';

        $ans = $ch->doApiCall("empty", [], true, [], $file);
        $this->assertEquals($ans['code'], 405);

    }

    /**
     * @covers ierusalim\miniAPI\miniAPIclient::yiDoApiCall
     * @todo   Implement testYiDoApiCall().
     */
    public function testYiDoApiCall()
    {

    }

    /**
     * @covers ierusalim\miniAPI\miniAPIclient::setCompression
     * @todo   Implement testSetCompression().
     */
    public function testSetCompression()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers ierusalim\miniAPI\miniAPIclient::setOption
     * @todo   Implement testSetOption().
     */
    public function testSetOption()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers ierusalim\miniAPI\miniAPIclient::getOption
     * @todo   Implement testGetOption().
     */
    public function testGetOption()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers ierusalim\miniAPI\miniAPIclient::delOption
     * @todo   Implement testDelOption().
     */
    public function testDelOption()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers ierusalim\miniAPI\miniAPIclient::query
     * @todo   Implement testQuery().
     */
    public function testQuery()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers ierusalim\miniAPI\miniAPIclient::anyQuery
     * @todo   Implement testAnyQuery().
     */
    public function testAnyQuery()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers ierusalim\miniAPI\miniAPIclient::getQuery
     * @todo   Implement testGetQuery().
     */
    public function testGetQuery()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers ierusalim\miniAPI\miniAPIclient::postQuery
     * @todo   Implement testPostQuery().
     */
    public function testPostQuery()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers ierusalim\miniAPI\miniAPIclient::doQuery
     * @todo   Implement testDoQuery().
     */
    public function testDoQuery()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


}
