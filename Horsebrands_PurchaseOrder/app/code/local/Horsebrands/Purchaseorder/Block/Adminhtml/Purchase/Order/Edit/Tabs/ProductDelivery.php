<?php

class Horsebrands_Purchaseorder_Block_Adminhtml_Purchase_Order_Edit_Tabs_ProductDelivery
  extends MDN_Purchase_Block_Order_Edit_Tabs_ProductDelivery {

    protected function _prepareColumns() {
      parent::_prepareColumns();

      $this->addColumnAfter('supplier_out_of_stock', array(
          'header' => Mage::helper('purchase')->__('Nicht lieferbar'),
          'index' => 'sku',
          'filter' => false,
          'sortable' => false,
          'align' => 'center',
          'renderer' => 'Horsebrands_Purchaseorder_Block_Adminhtml_Purchase_Widget_Column_Renderer_ProductDelivery_Outofstock'
      ), 'remaining_qty');
    }

}
