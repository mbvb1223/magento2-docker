<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Model\Email;

use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use PHPUnit\Framework\TestCase;

/**
 * Company email sender test
 */
class SenderTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * Checks that custom logo image applied in sales representative notification email template
     *
     * @magentoDataFixture Magento/Company/_files/email_logo.php
     * @magentoDataFixture Magento/Company/_files/company_with_admin.php
     * @magentoConfigFixture default_store design/email/logo magento_logo.jpg
     */
    public function testSendSalesRepresentativeNotificationEmail(): void
    {
        /** @var TransportBuilderMock $transportBuilder */
        $transportBuilder = $this->objectManager->get(TransportBuilderMock::class);
        $message = $transportBuilder->getSentMessage();
        $this->assertNotNull($message);
        $this->assertStringContainsString(
            'magento_logo.jpg',
            $message->getBody()->getParts()[0]->getRawContent(),
            'Expected text wasn\'t found in message.'
        );
    }
}
