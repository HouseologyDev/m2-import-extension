<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace B2bapp\ImportHandler\Api;

/**
 * Provides product data
 *
 * @api
 */
interface ImportHandlerInterface
{
    /**
     * Return the product data.
     *
     * @api
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return array
     */
    public function getProducts(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}