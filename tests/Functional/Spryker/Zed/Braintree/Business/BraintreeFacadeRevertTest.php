<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Functional\Spryker\Zed\Braintree\Business;

use Generated\Shared\Transfer\OrderTransfer;
use Spryker\Zed\Braintree\BraintreeConfig;
use Spryker\Zed\Braintree\Business\BraintreeBusinessFactory;
use Spryker\Zed\Braintree\Business\BraintreeFacade;
use Spryker\Zed\Braintree\Business\Payment\Handler\Transaction\Transaction;
use Spryker\Zed\Braintree\Persistence\BraintreeQueryContainer;

/**
 * @group Zed
 * @group Business
 * @group Braintree
 * @group BraintreeFacadeRevertTest
 */
class BraintreeFacadeRevertTest extends AbstractFacadeTest
{

    /**
     * @return void
     */
    public function testRevertPaymentWithSuccessResponse()
    {
        $orderTransfer = $this->createOrderTransfer();

        $idPayment = $this->getPaymentEntity()->getIdPaymentBraintree();
        $facade = $this->getFacadeMockAuthorize($orderTransfer);

        $response = $facade->revertPayment($orderTransfer, $idPayment);

        $this->assertTrue($response->getIsSuccess());
    }

    /**
     * @param OrderTransfer $orderTransfer
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|BraintreeFacade
     */
    private function getFacadeMockAuthorize(OrderTransfer $orderTransfer)
    {
        $facade = new BraintreeFacade();

        $factoryMock = $this->getMock(BraintreeBusinessFactory::class, ['createPaymentTransactionHandler']);

        $queryContainer = new BraintreeQueryContainer();
        $config = new BraintreeConfig();
        $transactionMock = $this->getMock(Transaction::class, ['revert'], [$queryContainer, $config]);

        $factoryMock->expects($this->once())
            ->method('createPaymentTransactionHandler')
            ->willReturn($transactionMock);

        $response = new \Braintree\Result\Successful();
        $response->transaction = \Braintree\Transaction::factory([
            'processorResponseCode' => '1000',
            'processorResponseText' => 'Approved',
            'createdAt' => new \DateTime(),
            'status' => 'revert',
            'type' => 'sale',
            'amount' => $orderTransfer->getTotals()->getGrandTotal() / 100,
            'merchantAccountId' => 'abc',
            'statusHistory' => new \Braintree\Transaction\StatusDetails([
                'timestamp' => new \DateTime(),
                'status' => 'voided'
            ])
        ]);

        $transactionMock->expects($this->once())
            ->method('revert')
            ->willReturn($response);

        $facade->setFactory($factoryMock);

        return $facade;
    }

}