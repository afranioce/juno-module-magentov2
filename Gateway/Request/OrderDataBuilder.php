<?php

namespace DigitalHub\Juno\Gateway\Request;

use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use DigitalHub\Juno\Observer\CreditCard\DataAssignObserver;

/**
 * Class CustomerDataBuilder
 */
class OrderDataBuilder implements BuilderInterface
{
    private $helper;
    private $logger;
    private $checkoutSession;
    private $customerFactory;

    /**
     * CustomerDataBuilder constructor.
     *
     * @param \DigitalHub\Juno\Helper\Data $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \DigitalHub\Juno\Logger\Logger $logger
     */
    public function __construct(
        \DigitalHub\Juno\Helper\Data $helper,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \DigitalHub\Juno\Logger\Logger $logger
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->customerFactory = $customerFactory;
    }

    /**
     * Add shopper data into request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObject $paymentDataObject */
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($buildSubject);

        $this->logger->info('Order Data Builder');

        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();

        $isSandbox = (int)$this->helper->getConfigData('digitalhub_juno_global', 'sandbox');
        $token = $this->helper->getConfigData('digitalhub_juno_global', 'sandbox_private_token');
        if (!$isSandbox) {
            $token = $this->helper->getConfigData('digitalhub_juno_global', 'production_private_token');
        }

        try {
            $request['order'] = $order;

            $firstname = $order->getCustomerFirstname()
                ? $order->getCustomerFirstname()
                : $order->getBillingAddress()->getFirstname();

            $lastname = $order->getCustomerLastname()
                ? $order->getCustomerLastname()
                : $order->getBillingAddress()->getLastname();

            $documentNumber = $this->helper->getCustomerDocumentNumber($order);
            $telephone = $this->helper->getCustomerTelephone($order);
            $customerDob = $order->getCustomerDob() ? $order->getCustomerDob() : $order->getBillingAddress()->getDob();
            $street = $this->helper->getCustomerAddressAttribute($order, 'address_street');
            $streetNumber = $this->helper->getCustomerAddressAttribute($order, 'address_street_number');
            $complement = $this->helper->getCustomerAddressAttribute($order, 'address_complement');
            $neighborhood = $this->helper->getCustomerAddressAttribute($order, 'address_neighborhood');

            $request['transaction'] = [
                'token' => $token,
                'description' => $this->helper->getConfigData('digitalhub_juno_global', 'sales_description'),
                'reference' => $order->getIncrementId(),
                'payerName' => $firstname . ' ' . $lastname,
                'payerCpfCnpj' => $documentNumber,
                'payerEmail' => $order->getCustomerEmail(),
                'payerPhone' => $telephone,
                'payerBirthDate' => $customerDob,
                'billingAddressStreet' => $street,
                'billingAddressNumber' => $streetNumber,
                'billingAddressComplement' => $complement,
                'billingAddressNeighborhood' => $neighborhood,
                'billingAddressCity' => $order->getBillingAddress()->getCity(),
                'billingAddressState' => $order->getBillingAddress()->getRegionCode(),
                'billingAddressPostcode' => $order->getBillingAddress()->getPostcode(),
                'notifyPayer' => (boolean)$this->helper->getConfigData('digitalhub_juno_global', 'notify'),
                'notificationUrl' => $this->helper->getNotificationUrl(),
                'responseType' => 'JSON'
            ];

        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $request;
    }
}
