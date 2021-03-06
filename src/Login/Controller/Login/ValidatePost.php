<?php
/**
 * Copyright 2016 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 *  http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */
namespace Amazon\Login\Controller\Login;

use Amazon\Login\Api\CustomerManagerInterface;
use Amazon\Login\Domain\ValidationCredentials;
use Amazon\Login\Helper\Session;
use Amazon\Login\Model\Customer\Account\Redirect as AccountRedirect;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Encryption\Encryptor;

class ValidatePost extends Action
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var AccountRedirect
     */
    protected $accountRedirect;

    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var Encryptor
     */
    protected $encryptor;

    /**
     * @var CustomerManagerInterface
     */
    protected $customerManager;

    /**
     * ValidatePost constructor.
     *
     * @param Context                  $context
     * @param Session                  $session
     * @param AccountRedirect          $accountRedirect
     * @param CustomerRegistry         $customerRegistry
     * @param Encryptor                $encryptor
     * @param CustomerManagerInterface $customerManager
     */
    public function __construct(
        Context $context,
        Session $session,
        AccountRedirect $accountRedirect,
        CustomerRegistry $customerRegistry,
        Encryptor $encryptor,
        CustomerManagerInterface $customerManager
    ) {
        parent::__construct($context);

        $this->session          = $session;
        $this->accountRedirect  = $accountRedirect;
        $this->customerRegistry = $customerRegistry;
        $this->encryptor        = $encryptor;
        $this->customerManager  = $customerManager;
    }

    public function execute()
    {
        $credentials = $this->session->getValidationCredentials();

        if (null !== $credentials && $credentials instanceof ValidationCredentials) {
            $password = $this->getRequest()->getParam('password');
            $hash     = $this->customerRegistry->retrieveSecureData($credentials->getCustomerId())->getPasswordHash();

            if ($this->encryptor->validateHash($password, $hash)) {
                $this->customerManager->updateLink($credentials->getCustomerId(), $credentials->getAmazonId());
                $this->session->loginById($credentials->getCustomerId());
            } else {
                $this->messageManager->addErrorMessage('The password supplied was incorrect');
                return $this->_redirect($this->_url->getRouteUrl('*/*/validate'));
            }
        }

        return $this->accountRedirect->getRedirect();
    }
}
