<?php

namespace Mbvb1223\Store\Controller\Adminhtml\Store;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Mbvb1223\Store\Controller\Adminhtml\Store;
use Mbvb1223\Store\Model\StoreFactory;
use Magento\Framework\Registry;

class Index extends Store
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        StoreFactory $postsFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $productFactory,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $a = $this->forAllProduct($productCollectionFactory);

        foreach ($a as $item) {
            var_dump($item);
        }
        die();

        $this->logger = $logger;

        parent::__construct($context, $coreRegistry, $resultPageFactory, $postsFactory);

        /* Test performance */
//        $collection = $productCollectionFactory->create();
//        $collection->getSize();
//        var_dump($collection->getSize());
//        die($collection->getSize());
//        $collection->addAttributeToSelect('*');
//        $collection->setPageSize(3); // fetching only 3 products
//        $collection->count();
//        foreach ($collection as $item) {
//            $item->getId();
//        }

        /*----------------------------------*/
//        $product = $productFactory->create();
//        $product->countAll();

        /*----------------------------------*/
//        $page = 1;
//        $break = true;
////        $collection = $productCollectionFactory->create();
//        do {
//            $collection = $productCollectionFactory->create();
//            $collection
//                ->addAttributeToSelect('id')
//                ->setPageSize(300)
//                ->setCurPage($page);
//
//            $results = $collection->load();
//            foreach ($results as $item) {
//                echo $item->getId() . "<br>";
//            }
//            $this->logger->warning('khien_log_abc_' . $page);
//            $lPage = $collection->getLastPageNumber();
//
//            $page++;
//            if($lPage == $page) {
//                $break = false;
//            }
//        } while ($break);

//        $pageSize = 25;
//        $break = false;
//        $page = 1;
//        $count = null;
//        $lPage = null;
//        while ($break !== true) {
//            $collection = $productCollectionFactory->create();
//            $collection
//                ->addAttributeToSelect('id')
//                ->setOrder('entity_id', 'ASC')
//                ->setPageSize($pageSize)
//                ->setCurPage($page)
//                ->load();
//
//            if ($count === null) {
//                $count = $collection->getSize();
//                $lPage = $collection->getLastPageNumber();
//            }
//
//            if ($lPage == $page) {
//                $break = true;
//            }
//
//            $page++;
//
//            foreach ($collection as $product) {
////                echo $product->getId() . "<br>";
//            }
//        }
//    }

    }

    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('TITLE'));

        return $resultPage;
    }

    public function forAllProduct($productCollectionFactory): \Generator
    {
        $collection = $productCollectionFactory->create();

        $collection->addAttributeToSelect('id');
        $collection->setPageSize(5);
        $pageCount = $collection->getLastPageNumber();
        $currentPage = 1;
        while ($currentPage <= $pageCount) {
            $collection->setCurPage($currentPage);
            foreach ($collection as $key => $product) {
                yield $key => $product->getId();
            }
            $collection->clear();
            $currentPage++;
        }
    }

    function gen_one_to_three() {
        for ($i = 1; $i <= 3; $i++) {
            // Note that $i is preserved between yields.
            yield $i;
        }
    }
}
