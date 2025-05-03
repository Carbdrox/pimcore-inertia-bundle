<?php declare(strict_types=1);

namespace InertiaBundle\Support;

use InertiaBundle\Service\Inertia;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SsrGateway
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private Inertia $inertia
    ) {
    }

    public function dispatch(array $page): SsrResponse
    {
        $ssrResponse = $this->httpClient->request('POST', $this->inertia->getSsrUrl(), [
                'headers' => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                'body' => json_encode($page)
            ]
        )->toArray();

        return new SsrResponse(implode("\n", $ssrResponse['head']), $ssrResponse['body']);
    }
}
