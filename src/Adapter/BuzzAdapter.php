<?php

/*
 * This file is part of the DigitalOceanV2 library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOceanV2\Adapter;

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Listener\ListenerInterface;
use Buzz\Message\Response;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 * @author Graham Campbell <graham@alt-three.com>
 */
class BuzzAdapter extends AbstractAdapter implements AdapterInterface
{
    /**
     * @var Browser
     */
    protected $browser;

    /**
     * @param string                 $token
     * @param Browser|null           $browser
     * @param ListenerInterface|null $listener
     */
    public function __construct($token, Browser $browser = null, ListenerInterface $listener = null)
    {
        $this->browser = $browser ?: new Browser(new Curl());
        $this->browser->addListener($listener ?: new BuzzOAuthListener($token));
    }

    /**
     * {@inheritdoc}
     */
    public function get($url)
    {
        $response = $this->browser->get($url);

        if (!$response->isSuccessful()) {
            $this->handleResponse($response);
        }

        return $response->getContent();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($url)
    {
        $response = $this->browser->delete($url);

        if (!$response->isSuccessful()) {
            $this->handleResponse($response);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function put($url, $content = '')
    {
        $headers = [];

        if (is_array($content)) {
            $content = json_encode($content);
            $headers[] = 'Content-Type: application/json';
        }

        $response = $this->browser->put($url, $headers, $content);

        if (!$response->isSuccessful()) {
            $this->handleResponse($response);
        }

        return $response->getContent();
    }

    /**
     * {@inheritdoc}
     */
    public function post($url, $content = '')
    {
        $headers = [];

        if (is_array($content)) {
            $content = json_encode($content);
            $headers[] = 'Content-Type: application/json';
        }

        $response = $this->browser->post($url, $headers, $content);

        if (!$response->isSuccessful()) {
            throw $this->handleResponse($response);
        }

        return $response->getContent();
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestResponseHeaders()
    {
        if (null === $response = $this->browser->getLastResponse()) {
            return;
        }

        return [
            'reset' => (int) $response->getHeader('RateLimit-Reset'),
            'remaining' => (int) $response->getHeader('RateLimit-Remaining'),
            'limit' => (int) $response->getHeader('RateLimit-Limit'),
        ];
    }

    /**
     * @param Response $response
     *
     * @return \Exception
     */
    protected function handleResponse(Response $response)
    {
        $content = json_decode($response->getContent());

        throw new \RuntimeException(sprintf('[%d] %s (%s)', $response->getStatusCode(), $content->message, $content->id));
    }
}
