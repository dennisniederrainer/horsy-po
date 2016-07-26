<?php

class Horsebrands_Purchaseorder_Block_Adminhtml_Purchase_SupplyNeeds_Grid
    extends MDN_Purchase_Block_SupplyNeeds_Grid {

      protected function _prepareCollection() {
          $warehouseId = Mage::helper('purchase/SupplyNeeds')->getCurrentWarehouse();
          if (!$warehouseId)
          {
              $collection = Mage::getModel('Purchase/SupplyNeeds')
                              ->getCollection();
          }
          else
          {
              $collection = Mage::getModel('Purchase/SupplyNeedsWarehouse')
                              ->getCollection()
                              ->addFieldToFilter('stock_id', $warehouseId);
          }
          $this->setCollection($collection);
          return parent::_prepareCollection();
      }

      protected function _prepareColumns() {

          $this->addColumn('manufacturer_id', array(
              'header' => Mage::helper('purchase')->__('Manufacturer'),
              'index' => 'manufacturer_id',
              'type' => 'options',
              'options' => $this->getManufacturersAsArray(),
          ));

          $this->addColumn('sku', array(
              'header' => Mage::helper('purchase')->__('Sku'),
              'index' => 'sku'
          ));

          $this->addColumn('categories', array(
              'header' => Mage::helper('purchase')->__('Categories'),
              'renderer' => 'Horsebrands_Purchaseorder_Block_Adminhtml_Purchase_SupplyNeeds_Renderer_Categories',
              // 'filter_condition_callback' => array($this, '_callbackCategoryFilter'),
              'filter' => 'horsebrands_purchaseorder/Widget_Column_Filter_ProductCategories',
              // 'filter_condition_callback' => array($this, '_categoryFilter'),
          ));

          $this->addColumn('name', array(
              'header' => Mage::helper('purchase')->__('Name'),
              'index' => 'name'
          ));

          mage::helper('AdvancedStock/Product_ConfigurableAttributes')->addConfigurableAttributesColumn($this, 'product_id');

          $this->addColumn('status', array(
              'header' => Mage::helper('purchase')->__('Status'),
              'index' => 'status',
              'align' => 'center',
              'type' => 'options',
              'options' => mage::getModel('Purchase/SupplyNeeds')->getStatuses(),
          ));

          $this->addColumn('sn_details', array(
              'header' => Mage::helper('purchase')->__('Details'),
              'index' => 'sn_details',
              'renderer' => 'MDN_Purchase_Block_Widget_Column_Renderer_SupplyNeedsDetails',
              'align' => 'center',
              'filter' => false,
              'sortable' => false,
              'product_id_field_name' => 'product_id',
              'product_name_field_name' => 'name'
          ));

          $this->addColumn('sn_needed_qty', array(
              'header' => Mage::helper('purchase')->__('Qty'),
              'index' => 'qty_min',
              'align' => 'center',
              'renderer' => 'MDN_Purchase_Block_Widget_Column_Renderer_SupplyNeeds_NeededQty',
              'filter' => false
          ));

          $this->addColumn('qty_for_po', array(
              'header' => Mage::helper('purchase')->__('Qty for PO'),
              'align' => 'center',
              'renderer' => 'MDN_Purchase_Block_Widget_Column_Renderer_SupplyNeeds_QtyForPo',
              'filter' => false,
              'sortable' => false,
          ));

          $this->addColumn('waiting_for_delivery_qty', array(
              'header' => Mage::helper('purchase')->__('Waiting for<br>delivery'),
              'index' => 'waiting_for_delivery_qty',
              'type' => 'number'
          ));

          $this->addColumn('sn_suppliers_name', array(
              'header' => Mage::helper('purchase')->__('Suppliers'),
              'index' => 'product_id',
              'filter' => 'Purchase/Widget_Column_Filter_SupplyNeeds_Suppliers',
              'renderer' => 'MDN_Purchase_Block_Widget_Column_Renderer_SupplyNeeds_Suppliers',
              'sortable' => false
          ));

          $this->addColumn('action',
                  array(
                      'header' => Mage::helper('purchase')->__('Action'),
                      'width' => '50px',
                      'type' => 'action',
                      'getter' => 'getproduct_id',
                      'actions' => array(
                          array(
                              'caption' => Mage::helper('purchase')->__('View'),
                              'url' => array('base' => 'AdvancedStock/Products/Edit'),
                              'field' => 'product_id'
                          )
                      ),
                      'filter' => false,
                      'sortable' => false,
                      'index' => 'stores',
                      'is_system' => true,
          ));

          return parent::_prepareColumns();
      }

    protected function _callbackCategoryFilter($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return null;
        }

        $collection->getSelect()->joinLeft(
            array('cat_prod' => 'catalog_category_product'),
            'main_table.product_id = cat_prod.product_id',
            array('cat_prod.category_id')
          )->where("cat_prod.category_id = '".$column->getFilter()->getValue()."'");
        // $collection->joinField(
        //     'category_id',
        //     'catalog/category_product',
        //     'category_id',
        //     'product_id = entity_id',
        //     '{{table}}.category_id=' . $column->getFilter()->getValue(),
        //     'inner'
        // );
    }

    protected function _categoryFilter($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $this->getCollection()->getSelect()->joinLeft(
            array('cat_prod' => 'catalog_category_product'),
            'main_table.product_id = cat_prod.product_id',
            array('cat_prod.category_id')
          // )->where("cat.category_id = ?",$value);
          // )->join(
          //   array('cat' => 'catalog_category_flat_store_1'),
          //   'cat_prod.category_id = cat.entity_id',
          //   array('cat.name')
          // )
          )->where("cat_prod.category_id = '".$value."'")
          ->group("main_table.product_id");

        // mage::log($this->getCollection()->getSelect(), null, 'zack.log');

        return $this;
    }
}
