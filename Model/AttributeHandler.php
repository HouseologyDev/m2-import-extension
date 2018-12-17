<?php

namespace B2bapp\ImportHandler\Model;

use B2bapp\ImportHandler\Api\AttributeInterface;

class AttributeHandler implements AttributeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        $setId = 4;
        $eavAttributeManagement = \Magento\Framework\App\ObjectManager::getInstance()
            ->create('\Magento\Eav\Api\AttributeManagementInterface');

        $attributeCollection = $eavAttributeManagement->getAttributes(
            \Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE,
            $setId
        );
        
        $attributes = array();
        foreach($attributeCollection as $item) {
            array_push($attributes, $this->_prepareAttributeForResponse($item));
        }
        return $attributes;
    }

    /**
     * Add special fields to attribute get response
     *
     * @param \Magento\Eav\Api\Data\AttributeInterface $attribute
     */
    protected function _prepareAttributeForResponse(\Magento\Eav\Api\Data\AttributeInterface $attribute)
    {
        $result = null;
        $scope = 'global';

        $optionsCollection = $attribute->getOptions();

        $options = array();
        foreach($optionsCollection as $item) {
            array_push($options,
                array(
                    'value' => $item->getValue(),
                    'label' => $item->getLabel()
                )
            );
        }

        $result = array(
            'attribute_id' => $attribute->getAttributeId(),
            'code' => $attribute->getAttributeCode(),
            'label' => $attribute->getDefaultFrontendLabel(),
            'backend_type' => $attribute->getBackendType(),
            'frontend_type' => $attribute->getFrontendInput(),
            'is_searchable' => 0,
            'is_filterable' => 0,
            'is_visible' => 1,
            'required' => 0,
            'scope' => $scope,
            'options' => $options
        );

        return $result;
    }
}