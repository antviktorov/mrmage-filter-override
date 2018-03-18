<?php

/**
 * Override Catalog Product Eav Select and Multiply Select Attributes Indexer resource model
 *
 * @package     Mrmage_FilterOverride
 * @author      Anton Viktorv <antvik85@gmail.com>
 */
class Mrmage_FilterOverride_Model_Rewrite_Catalog_Resource_Product_Indexer_Eav_Source
    extends Mage_Catalog_Model_Resource_Product_Indexer_Eav_Source
{
    /**
     * Prepare data index for indexable select attributes
     *
     * @param array $entityIds the entity ids limitation
     * @param int $attributeId the attribute id limitation
     * @return Mage_Catalog_Model_Resource_Product_Indexer_Eav_Source
     */
    protected function _prepareSelectIndex($entityIds = null, $attributeId = null)
    {
        if (!Mage::helper('mrmage_filteroverride')->isEnabled()) {
            return parent::_prepareSelectIndex($entityIds, $attributeId);
        }

        $adapter = $this->_getWriteAdapter();
        $idxTable = $this->getIdxTable();
        // prepare select attributes
        if (is_null($attributeId)) {
            $attrIds = $this->_getIndexableAttributes(false);
        } else {
            $attrIds = array($attributeId);
        }

        if (!$attrIds) {
            return $this;
        }

        $selectSimples = $this->_prepareSelectIndexAdvanced($attrIds, true, $entityIds);

        if (!empty($selectSimples)) {
            Mage::dispatchEvent('prepare_catalog_product_index_select', array(
                'select'        => $selectSimples,
                'entity_field'  => new Zend_Db_Expr('pid.entity_id'),
                'website_field' => new Zend_Db_Expr('pid.website_id'),
                'store_field'   => new Zend_Db_Expr('pid.store_id'),
            ));

            $query = $selectSimples->insertFromSelect($idxTable);
            $adapter->query($query);
        }

        $select = $this->_prepareSelectIndexAdvanced($attrIds, false, $entityIds);

        /**
         * Add additional external limitation
         */
        Mage::dispatchEvent('prepare_catalog_product_index_select', array(
            'select'        => $select,
            'entity_field'  => new Zend_Db_Expr('pid.entity_id'),
            'website_field' => new Zend_Db_Expr('pid.website_id'),
            'store_field'   => new Zend_Db_Expr('pid.store_id'),
        ));

        $query = $select->insertFromSelect($idxTable);
        $adapter->query($query);

        return $this;
    }

    private function _prepareSelectIndexAdvanced($attrIds, $simples = false, $entityIds = null)
    {
        //color attribute ID
        $colorAttrID = 92;

        if ($simples && !in_array($colorAttrID, $attrIds)) {
            return null;
        }

        $adapter = $this->_getWriteAdapter();

        /**@var $subSelect Varien_Db_Select */
        $subSelect = $adapter->select()
            ->from(
                array('s' => $this->getTable('core/store')),
                array('store_id', 'website_id')
            )
            ->joinLeft(
                array('d' => $this->getValueTable('catalog/product', 'int')),
                '1 = 1 AND d.store_id = 0',
                array('entity_id', 'attribute_id', 'value')
            )
            ->where('s.store_id != 0');

        $statusCond = $adapter->quoteInto(' = ?', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $this->_addAttributeToSelect($subSelect, 'status', 'd.entity_id', 's.store_id', $statusCond);

        if (!is_null($entityIds)) {
            $subSelect->where('d.entity_id IN(?)', $entityIds);
        }

        if ($simples) {
            $attrIds = array($colorAttrID);
        }

        /**@var $select Varien_Db_Select */
        $select = $adapter->select()
            ->from(
                array('pid' => new Zend_Db_Expr(sprintf('(%s)', $subSelect->assemble()))),
                array()
            )
            ->joinLeft(
                array('pis' => $this->getValueTable('catalog/product', 'int')),
                'pis.entity_id = pid.entity_id AND pis.attribute_id = pid.attribute_id AND pis.store_id = pid.store_id',
                array()
            )
            ->join(
                array('type' => $this->getTable('catalog/product')),
                'type.entity_id = pid.entity_id',
                array()
            )
            ->columns(
                array(
                    'pid.entity_id',
                    'pid.attribute_id',
                    'pid.store_id',
                    'value' => $adapter->getIfNullSql('pis.value', 'pid.value'),
                )
            )
            ->where('pid.attribute_id IN(?)', $attrIds);

        if ($simples) {
            $select->where('type.type_id = ?', 'simple');
        } else {
            $select->where('type.type_id != ?', 'simple');
        }

        $select->where(Mage::getResourceHelper('catalog')->getIsNullNotNullCondition('pis.value', 'pid.value'));

        return $select;
    }
}