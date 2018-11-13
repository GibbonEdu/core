<?php

namespace Symfony\Component\HttpKernel\Tests\Exception;

use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class TooManyRequestsHttpExceptionTest extends HttpExceptionTest
{
    public function testHeadersDefaultRertyAfter()
    {
        $exception = new TooManyRequestsHttpException(10);
        $this->assertSame(array('Retry-After' => 10), $exception->getHeaders());
    }

    public function testWithHeaderConstruct()
    {
        $headers = array(
            'Cache-Control' => 'public, s-maxage=69',
        );

        $exception = new TooManyRequestsHttpException(69, null, null, null, $headers);

        $headers['Retry-After'] = 69;

        $this->assertSame($headers, $exception->getHeaders());
    }

    /**
     * @dataProvider headerDataProvider
     */
    public function testHeadersSetter($headers)
    {
        $exception = new TooManyRequestsHttpException(10);
        $exception->setHeaders($headers);
        $this->assertSame($headers, $exception->getHeaders());
    }

    protected function createException()
    {
        return new TooManyRequestsHttpException();
    }
}
