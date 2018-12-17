<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace B2bapp\ImportHandler\Api;

/**
 * Provides attribute data
 *
 * @api
 */
interface AttributeInterface
{
    /**
     * Return the attribute data.
     *
     * @api
     * @return array
     */
    public function getAttributes();
}