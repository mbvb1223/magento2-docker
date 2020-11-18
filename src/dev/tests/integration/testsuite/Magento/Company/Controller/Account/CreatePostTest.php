<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Controller\Account;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Test for CreatePost controller.
 *
 * @see \Magento\Company\Controller\Account\CreatePost
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class CreatePostTest extends AbstractController
{
    /**
     * Try to create company with customer.
     *
     * @return void
     */
    public function testCreatePost(): void
    {
        $data = $this->getPostData();
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST)->setPostValue($data);
        $this->dispatch('company/account/createPost');

        $this->assertSessionMessages(
            $this->equalTo(['Thank you! We&#039;re reviewing your request and will contact you soon']),
            MessageInterface::TYPE_SUCCESS
        );
    }

    /**
     * Try to create company with customer without required company attribute.
     *
     * @return void
     */
    public function testCreatePostWithoutRequiredCompanyAttribute(): void
    {
        $data = $this->getPostData();
        unset($data['company']['company_email']);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST)->setPostValue($data);
        $this->dispatch('company/account/createPost');

        $this->assertSessionMessages(
            $this->equalTo(['&quot;company_email&quot; is required. Enter and try again.']),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * Try to create company with customer without required customer attribute.
     *
     * @return void
     */
    public function testCreatePostWithoutRequiredCustomerAttribute(): void
    {
        $data = $this->getPostData();
        unset($data['customer']['lastname']);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST)->setPostValue($data);
        $this->dispatch('company/account/createPost');

        $this->assertSessionMessages(
            $this->equalTo(['&quot;Last Name&quot; is a required value.']),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * Return test data.
     *
     * @return array
     */
    private function getPostData(): array
    {
        return [
            'company' => [
                'company_name' => 'TSG',
                'legal_name' => 'TSG Company',
                'company_email' => 'tsg@example.com',
                'country_id' => 'UA',
                'region' => 'Kyiv region',
                'city' => 'Kyiv',
                'street' => [
                    0 => 'Somewhere',
                ],
                'postcode' => '01001',
                'telephone' => '+1255555555',
                'job_title' => 'Owner',
            ],
            'customer' => [
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john.doetsg@example.com',
            ],
        ];
    }
}
