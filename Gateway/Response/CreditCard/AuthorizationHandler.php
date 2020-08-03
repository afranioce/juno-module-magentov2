<?php
namespace DigitalHub\Juno\Gateway\Response\CreditCard;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Payment\Gateway\Response\HandlerInterface;

class AuthorizationHandler implements HandlerInterface
{
    protected $logger;
    protected $helper;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var ManagerInterface
     */
    private $eventManagerInterface;

    public function __construct(
        \DigitalHub\Juno\Helper\Data $helper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Event\ManagerInterface $eventManagerInterface,
        \DigitalHub\Juno\Logger\Logger $logger
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->eventManagerInterface = $eventManagerInterface;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);
        $payment = $payment->getPayment();

        $this->logger->info('AUTHORIZATION HANDLER', [$response]);

        $saveCc = $this->registry->registry('digitalhub_juno_save_cc');
        $paymentResult = $response['payment_result'];

        try {
            $payment->setTransactionId($paymentResult->data->charges[0]->code);
            $payment->setAdditionalInformation('juno_data', json_encode($paymentResult));
            $payment->setIsTransactionPending(false);

            if ($saveCc) {
                $this->eventManagerInterface->dispatch('digitalhub_juno_save_cc_handle', [
                    'payment' => $payment,
                    'payment_result' => $paymentResult
                ]);
            }

            // important
            $payment->setIsTransactionClosed(false);
            $payment->setShouldCloseParentTransaction(false);

            // send order confirmation mail
            $payment->getOrder()->setCanSendNewEmailFlag(true);
        } catch (\Exception $e) {
            $this->logger->info('AUTHORIZATION HANDLER ERROR', [$e->getMessage()]);
        }
    }
}
