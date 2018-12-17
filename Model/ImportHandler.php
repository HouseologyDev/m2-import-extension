<?php

namespace B2bapp\ImportHandler\Model;

use B2bapp\ImportHandler\Api\ImportHandlerInterface;

class ImportHandler implements ImportHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getProducts(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $collectionFactory = \Magento\Framework\App\ObjectManager::getInstance()
            ->create('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $collectionFactory->create();

        $extensionAttributesJoinProcessor = \Magento\Framework\App\ObjectManager::getInstance()
            ->create('\Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface');
        $extensionAttributesJoinProcessor->process($collection);

        $collection->addAttributeToSelect('*');
        $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
        $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');

        $collectionProcessor = \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Magento\Catalog\Model\Api\SearchCriteria\ProductCollectionProcessor'
        );
        $collectionProcessor->process($searchCriteria, $collection);

        $collection->load();

        $searchResultsFactory = \Magento\Framework\App\ObjectManager::getInstance()
            ->create('\Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory');
        $collection->addCategoryIds();
        $searchResult = $searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());

        $products = array();
        foreach($searchResult->getItems() as $item) {
            array_push($products, $this->_prepareProductForResponse($item));
        }
        return $products;
    }
    
    //TODO
    public function getProduct($id)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $prodObj = $objectManager->create('Magento\Catalog\Model\Product')->load($id);
    }

    private function _prepareProductForResponse($item)
    {
        $product = array();
        $product['id'] = $item->getEntityId();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $prodObj = $objectManager->create('Magento\Catalog\Model\Product')->load($product['id']);

        $product['sku'] = $prodObj->getSku();
        $product['type_id'] = $prodObj->getTypeId();
        $product['name'] = $prodObj->getName();
        $product['status'] = $prodObj->getStatus();
        $product['price'] = $prodObj->getPrice();
        $product['description'] = strip_tags($prodObj->getDescription());
        $product['part_number'] = $prodObj->getPartNumber();
        $product['visibility'] = $prodObj->getVisibility();
        $product['search_keywords'] = $prodObj->getSearchKeywords();
        $image = $prodObj->getImage() ? $prodObj->getImage() : null;
        $swatch_image_json = null;

        $gallery = $prodObj->getMediaGalleryImages();

        $galleries = array();
        if($gallery)
        {
            foreach($gallery as $gal_image) {
                if($gal_image['file'] && $gal_image['file'] != '') {
                    array_push($galleries, $gal_image['file']);
                }
            }
        }

        $product['images'] = array(
            'image' => $image,
            'swatch_image_json' => $swatch_image_json,
            'gallery' => $galleries
        );

        $product['supplier_id'] = $prodObj->getSupplierId();
        $product['supplier'] = array();
        $product['supplier']['id'] = $prodObj->getSupplier();
//        if ($brandModel) {
//            $product['supplier']['image'] = $brandModel->getMediaGalleryImagePath();
//            $product['supplier']['description'] = $brandModel->getDescription();
//        }

        $customAttributes = $item->getCustomAttributes();
        $product['attributes'] = array();

        foreach($customAttributes as $attribute) {
            array_push($product['attributes'],
                array(
                'attribute_code' => $attribute->getAttributeCode(),
                'value' =>$attribute->getValue()
                )
            );
        }

        if ($prodObj->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $product['configuration'] = array();

            $children = $prodObj->getTypeInstance(true)
                ->getChildrenIds($product['id']);

            if (count($children)) {
                $product['configuration']['children'] = array();
                foreach ($children[0] as $key => $id) {
                    array_push($product['configuration']['children'], $id);
                }
                $child = $objectManager->create('Magento\Catalog\Model\Product')->load($product['configuration']['children'][0]);

                if($child) {
                    $product['supplier_id'] = $child->getSupplierId();

//                    $brandModel = $child->getData('brand_model');

                    $product['supplier'] = array();
                    $product['supplier']['id'] = $child->getSupplier();
//                    if ($brandModel) {
//                        $product['supplier']['image'] = $brandModel->getMediaGalleryImagePath();
//                        $product['supplier']['description'] = $brandModel->getDescription();
//                    }
                }

                $configAttributes = $prodObj->getTypeInstance(true)->getConfigurableAttributesAsArray($prodObj);
                $options = $prodObj->getTypeInstance(true)->getConfigurableOptions($prodObj);

                $configOptions = array();
                foreach ($options as $option) {

                    foreach ($option as $optionData) {
//                        if (!isset($configOptions[$optionData['sku']])) {
                        $configOptions[$optionData['sku']][] = $optionData;
//                            $configOptions[$optionData['sku']]['attribute_code'] = '[' . $optionData['attribute_code'] . ']';
//                            $configOptions[$optionData['sku']]['key'] = $optionData['option_title'];
//                        } else {
//                              $configOptions[$optionData['sku']][1] = $optionData;
//                            $configOptions[$optionData['sku']]['attribute_code'] .= '_[' . $optionData['attribute_code'] . ']';
//                            $configOptions[$optionData['sku']]['key'] .= '_' . $optionData['option_title'];
//                        }
//                        unset($configOptions[$optionData['sku']]['product_id']);
//                        unset($configOptions[$optionData['sku']]['option_title']);
//                        unset($configOptions[$optionData['sku']]['pricing_value']);
//                        unset($configOptions[$optionData['sku']]['pricing_is_percent']);
                    }
                }
                $product['configuration']['attributes'] = $configAttributes;
                $product['configuration']['options'][] = $configOptions;
            }
        }

        return $product;
    }
}