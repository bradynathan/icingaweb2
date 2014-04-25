<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web;

use Mockery;
use Icinga\Web\Url;
use Icinga\Test\BaseTestCase;

class UrlTest extends BaseTestCase
{
    public function testWhetherFromRequestWorksWithoutARequest()
    {
        $request = Mockery::mock('Icinga\Web\Request');
        $request->shouldReceive('getPathInfo')->andReturn('my/test/url.html')
            ->shouldReceive('getBaseUrl')->andReturn('/path/to')
            ->shouldReceive('getQuery')->andReturn(array('param1' => 'value1', 'param2' => 'value2'));
        $this->setupIcingaMock($request);

        $url = Url::fromRequest();
        $this->assertEquals(
            '/path/to/my/test/url.html?param1=value1&amp;param2=value2',
            $url->getAbsoluteUrl(),
            'Url::fromRequest does not reassemble the correct url from the global request'
        );
    }

    public function testWhetherFromRequestWorksWithARequest()
    {
        $request = Mockery::mock('Icinga\Web\Request');
        $request->shouldReceive('getPathInfo')->andReturn('my/test/url.html')
            ->shouldReceive('getBaseUrl')->andReturn('/path/to')
            ->shouldReceive('getQuery')->andReturn(array());

        $url = Url::fromRequest(array(), $request);
        $this->assertEquals(
            '/path/to/my/test/url.html',
            $url->getAbsoluteUrl(),
            'Url::fromRequest does not reassemble the correct url from a given request'
        );
    }

    public function testWhetherFromRequestAcceptsAdditionalParameters()
    {
        $request = Mockery::mock('Icinga\Web\Request');
        $request->shouldReceive('getPathInfo')->andReturn('')
            ->shouldReceive('getBaseUrl')->andReturn('/')
            ->shouldReceive('getQuery')->andReturn(array('key1' => 'val1'));

        $url = Url::fromRequest(array('key1' => 'newval1', 'key2' => 'val2'), $request);
        $this->assertEquals(
            'val2',
            $url->getParam('key2', 'wrongval'),
            'Url::fromRequest does not accept additional parameters'
        );
        $this->assertEquals(
            'newval1',
            $url->getParam('key1', 'wrongval1'),
            'Url::fromRequest does not overwrite existing parameters with additional ones'
        );
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     */
    public function testWhetherFromPathProperlyHandlesInvalidUrls()
    {
        Url::fromPath(null);
    }

    public function testWhetherFromPathAcceptsAdditionalParameters()
    {
        $url = Url::fromPath('/my/test/url.html', array('key' => 'value'));

        $this->assertEquals(
            'value',
            $url->getParam('key', 'wrongvalue'),
            'Url::fromPath does not accept additional parameters'
        );
    }

    public function testWhetherFromPathProperlyParsesUrlsWithoutQuery()
    {
        $url = Url::fromPath('/my/test/url.html');

        $this->assertEquals(
            '/',
            $url->getBaseUrl(),
            'Url::fromPath does not recognize the correct base url'
        );
        $this->assertEquals(
            'my/test/url.html',
            $url->getPath(),
            'Url::fromPath does not recognize the correct url path'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyParsesUrlsWithoutQuery
     */
    public function testWhetherFromPathProperlyRecognizesTheBaseUrl()
    {
        $url = Url::fromPath(
            '/path/to/my/test/url.html',
            array(),
            Mockery::mock(array('getBaseUrl' => '/path/to'))
        );

        $this->assertEquals(
            '/path/to/my/test/url.html',
            $url->getAbsoluteUrl(),
            'Url::fromPath does not properly differentiate between the base url and its path'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesTheBaseUrl
     */
    public function testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters()
    {
        $url = Url::fromPath('/my/test/url.html?param1=%25arg1&param2=arg+2'
            . '&param3[]=1&param3[]=2&param3[]=3&param4[key1]=val1&param4[key2]=val2');

        $this->assertEquals(
            '%arg1',
            $url->getParam('param1', 'wrongval'),
            'Url::fromPath does not properly decode escaped characters in query parameter values'
        );
        $this->assertEquals(
            'arg 2',
            $url->getParam('param2', 'wrongval'),
            'Url::fromPath does not properly decode aliases characters in query parameter values'
        );
        $this->assertEquals(
            array('1', '2', '3'),
            $url->getParam('param3'),
            'Url::fromPath does not properly reassemble query parameter values as sequenced values'
        );
        $this->assertEquals(
            array('key1' => 'val1', 'key2' => 'val2'),
            $url->getParam('param4'),
            'Url::fromPath does not properly reassemble query parameters as associative arrays'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherTranslateAliasTranslatesKnownAliases()
    {
        $url = Url::fromPath('/my/test/url.html');
        $url->setAliases(array('foo' => 'bar'));

        $this->assertEquals(
            'bar',
            $url->translateAlias('foo'),
            'Url::translateAlias does not translate a known alias'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherTranslateAliasDoesNotTranslateUnknownAliases()
    {
        $url = Url::fromPath('/my/test/url.html');
        $url->setAliases(array('foo' => 'bar'));

        $this->assertEquals(
            'fo',
            $url->translateAlias('fo'),
            'Url::translateAlias does translate an unknown alias'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherGetAbsoluteUrlReturnsTheAbsoluteUrl()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2');

        $this->assertEquals(
            '/my/test/url.html?param=val&amp;param2=val2',
            $url->getAbsoluteUrl(),
            'Url::getAbsoluteUrl does not return the absolute url'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherGetRelativeUrlReturnsTheRelativeUrl()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2');

        $this->assertEquals(
            'my/test/url.html?param=val&amp;param2=val2',
            $url->getRelativeUrl(),
            'Url::getRelativeUrl does not return the relative url'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherGetParamReturnsTheCorrectParameter()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2');

        $this->assertEquals(
            'val',
            $url->getParam('param', 'wrongval'),
            'Url::getParam does not return the correct value for an existing parameter'
        );
        $this->assertEquals(
            'val2',
            $url->getParam('param2', 'wrongval2'),
            'Url::getParam does not return the correct value for an existing parameter'
        );
        $this->assertEquals(
            'nonexisting',
            $url->getParam('param3', 'nonexisting'),
            'Url::getParam does not return the default value for a non existing parameter'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherRemoveRemovesAGivenSingleParameter()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2');
        $url->remove('param');

        $this->assertEquals(
            'val2',
            $url->getParam('param2', 'wrongval2'),
            'Url::remove removes not only the given parameter'
        );
        $this->assertEquals(
            'rightval',
            $url->getParam('param', 'rightval'),
            'Url::remove does not remove the given parameter'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherRemoveRemovesAGivenSetOfParameters()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2&param3=val3');
        $url->remove(array('param', 'param2'));

        $this->assertEquals(
            'val3',
            $url->getParam('param3', 'wrongval'),
            'Url::remove removes not only the given parameters'
        );
        $this->assertEquals(
            'rightval',
            $url->getParam('param', 'rightval'),
            'Url::remove does not remove all given parameters'
        );
        $this->assertEquals(
            'rightval',
            $url->getParam('param2', 'rightval'),
            'Url::remove does not remove all given parameters'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherGetUrlWithoutReturnsACopyOfTheUrlWithoutAGivenSetOfParameters()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2&param3=val3');
        $url2 = $url->getUrlWithout(array('param', 'param2'));

        $this->assertNotSame($url, $url2, 'Url::getUrlWithout does not return a new copy of the url');
        $this->assertEquals(
            array('param3' => 'val3'),
            $url2->getParams(),
            'Url::getUrlWithout does not remove a given set of parameters from the url'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherAddParamsDoesNotOverwriteExistingParameters()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2&param3=val3');
        $url->addParams(array('param4' => 'val4', 'param3' => 'newval3'));

        $this->assertEquals(
            'val4',
            $url->getParam('param4', 'wrongval'),
            'Url::addParams does not add new parameters'
        );
        $this->assertEquals(
            'val3',
            $url->getParam('param3', 'wrongval'),
            'Url::addParams overwrites existing parameters'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherOverwriteParamsOverwritesExistingParameters()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2&param3=val3');
        $url->overwriteParams(array('param4' => 'val4', 'param3' => 'newval3'));

        $this->assertEquals(
            'val4',
            $url->getParam('param4', 'wrongval'),
            'Url::addParams does not add new parameters'
        );
        $this->assertEquals(
            'newval3',
            $url->getParam('param3', 'wrongval'),
            'Url::addParams does not overwrite existing parameters'
        );
    }

    /**
     * @depends testWhetherGetAbsoluteUrlReturnsTheAbsoluteUrl
     */
    public function testWhetherToStringConversionReturnsTheAbsoluteUrl()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2&param3=val3');

        $this->assertEquals(
            $url->getAbsoluteUrl(),
            (string) $url,
            'Converting a url to string does not return the absolute url'
        );
    }
}
