<?php

namespace Extend\Warranty\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Extend\Warranty\Model\Api\Sync\Contract\ContractsRequest;
use Extend\Warranty\Model\Api\Request\ContractBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class WarrantyContract
{
    /**
     * @var ContractsRequest
     */
    protected $contractsRequest;

    /**
     * @var ContractBuilder
     */
    protected $contractBuilder;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct
    (
        ContractsRequest $contractsRequest,
        ContractBuilder $contractBuilder,
        LoggerInterface $logger
    )
    {
        $this->contractsRequest = $contractsRequest;
        $this->contractBuilder = $contractBuilder;
        $this->logger = $logger;
    }

    /**
     * @param OrderInterface $order
     * @param $warranties
     */
    public function createContract($order, $warranties)
    {

        try {
            $contracts = $this->contractBuilder->prepareInfo($order, $warranties);

            foreach ($contracts as $key => $contract) {
                //validate qty of contracts required
                if ($contract['product']['qty']>1) {
                    $contractIds = [];
                    $tempcontract = $contract;
                    unset($tempcontract['product']['qty']);
                    for ($x=1; $x<=$contract['product']['qty']; $x++) {
                        $contractIds[$x]= $this->contractsRequest->create($contract);
                        //array_push($contractIds,$this->contractsRequest->create($contract));
                    }
                    unset($tempcontract);
                    $contractId=json_encode($contractIds);
                    unset($contractIds);
                } else {
                    $contractId = json_encode(array('1'=>$this->contractsRequest->create($contract)));
                }

                if (!empty($contractId)) {
                    $items = $order->getAllItems();
                    if (isset($items[$key]) && empty($items[$key]->getContractId())) {
                        $items[$key]->setContractId($contractId);

                        $options = $items[$key]->getProductOptions();

                        $options = array_merge($options, ['refund' => false]);

                        $items[$key]->setProductOptions($options);

                        if ($order->getId()) {
                            $items[$key]->save();
                        }
                    }
                }
            }

        } catch (NoSuchEntityException $exception) {
            $this->logger->error('Error while creating warranty contract');
        }

    }

}