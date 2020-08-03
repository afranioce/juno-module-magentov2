<?php

namespace DigitalHub\Juno\Model\Service;

class Api
{
    const SANDBOX_ENDPOINT_URL = 'https://sandbox.boletobancario.com/boletofacil/integration/api/v1/';
    const PRODUCTION_ENDPOINT_URL = 'https://www.boletobancario.com/boletofacil/integration/api/v1/';

    /**
     * @var \Magento\Framework\Http\ZendClientFactory
     */
    private $zendClientFactory;

    public function __construct(
        \Magento\Framework\Http\ZendClientFactory $zendClientFactory
    ) {
        $this->zendClientFactory = $zendClientFactory;
    }

    /**
     * Make HTTP request to API endpoints
     * @param $uri
     * @param string $method
     * @param null $data
     */
    private function __makeRequest($uri, $method = 'GET', $data = null)
    {
        $client = $this->zendClientFactory->create();
        $client->setUri($uri);
        $client->setHeaders(['Content-type' => 'application/json']);
        $client->setParameterPost($data);
        $result = $client->request($method);
        return $result->getBody();
    }

    /**
     * Creates a charge on JUNO API
     *
     * @param $isSandbox
     * @param array $data
     */
    public function issueCharge($isSandbox, $data = [])
    {
        $url = self::SANDBOX_ENDPOINT_URL;
        if (!$isSandbox) {
            $uri = self::PRODUCTION_ENDPOINT_URL;
        }

        $url = $url . 'issue-charge';

        return json_decode($this->__makeRequest($url, 'POST', $data));
    }

    /**
     * Fetch payment details on JUNO API
     *
     * @param $isSandbox
     * @param string $paymentToken
     */
    public function fetchPaymentDetails($isSandbox, string $paymentToken)
    {
        $url = self::SANDBOX_ENDPOINT_URL;
        if (!$isSandbox) {
            $uri = self::PRODUCTION_ENDPOINT_URL;
        }

        $url = $url . 'fetch-payment-details';
        $url .= "?paymentToken=" . $paymentToken . "&responseType=JSON";

        return json_decode($this->__makeRequest($url, 'GET'), 1);
    }
}
