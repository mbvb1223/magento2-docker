<?php
namespace Khien\Blog\Setup;

use \Magento\Framework\Setup\UpgradeDataInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{

    /**
     * Creates sample blog posts
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
//        $setup->startSetup();
//
//        if ($context->getVersion()
//            && version_compare($context->getVersion(), '1.0.5') < 0
//        ) {
//            $tableName = $setup->getTable('khien_post');
//
//            $data = [
//                [
//                    'title' => '404 Not Found',
//                    'content' => "<dl>\r\n<dt>The page you requested was not found, and we have a fine guess why.</dt>\r\n"
//                        . "<dd>\r\n<ul class=\"disc\">\r\n<li>If you typed the URL directly, please make sure the spelling"
//                        . " is correct.</li>\r\n<li>If you clicked on a link to get here, the link is outdated.</li>\r\n"
//                        . "</ul></dd>\r\n</dl>\r\n<dl>\r\n<dt>What can you do?</dt>\r\n<dd>Have no fear, help is near!"
//                        . " There are many ways you can get back on track with Magento Store.</dd>\r\n<dd>\r\n"
//                        . "<ul class=\"disc\">\r\n<li><a href=\"#\" onclick=\"history.go(-1); return false;\">Go back</a> "
//                        . "to the previous page.</li>\r\n<li>Use the search bar at the top of the page to search for your"
//                        . " products.</li>\r\n<li>Follow these links to get you back on track!<br />"
//                        . "<a href=\"{{store url=\"\"}}\">Store Home</a> <span class=\"separator\">|</span> "
//                        . "<a href=\"{{store url=\"customer/account\"}}\">My Account</a></li></ul></dd></dl>\r\n",
//                    'is_active' => 1,
//                ],
//                [
//                    'title' => 'Home page',
//                    'content' => "<p>CMS homepage content goes here.</p>\r\n",
//                    'is_active' => 1,
//                ],
//                [
//                    'title' => 'Enable Cookies',
//                    'content' => "<div class=\"enable-cookies cms-content\">\r\n<p>\"Cookies\" are little pieces of data"
//                        . " we send when you visit our store. Cookies help us get to know you better and personalize your"
//                        . " experience. Plus they help protect you and other shoppers from fraud.</p>\r\n"
//                        . "<p style=\"margin-bottom: 20px;\">Set your browser to accept cookies so you can buy items, "
//                        . "save items, and receive customized recommendations. Here’s how:</p>\r\n<ul>\r\n<li>"
//                        . "<a href=\"https://support.google.com/accounts/answer/61416?hl=en\" target=\"_blank\">Google "
//                        . "Chrome</a></li>\r\n<li>"
//                        . "<a href=\"http://windows.microsoft.com/en-us/internet-explorer/delete-manage-cookies\""
//                        . " target=\"_blank\">Internet Explorer</a></li>\r\n<li>"
//                        . "<a href=\"http://support.apple.com/kb/PH19214\" target=\"_blank\">Safari</a></li>\r\n<li>"
//                        . "<a href=\"https://support.mozilla.org/en-US/kb/enable-and-disable-cookies-website-preferences\""
//                        . " target=\"_blank\">Mozilla/Firefox</a></li>\r\n</ul>\r\n</div>",
//                    'is_active' => 1,
//                ]
//            ];
//
//            $setup
//                ->getConnection()
//                ->insertMultiple($tableName, $data);
//        }
//
//        $setup->endSetup();
    }
}
