<?php

class Horsebrands_Purchaseorder_Block_Adminhtml_Purchase_Order_Edit_Tabs_Info
    extends MDN_Purchase_Block_Order_Edit_Tabs_Info {

    public function __construct() {

        $this->_blockGroup = 'Purchase';
        $this->_objectId = 'id';
        $this->_controller = 'order';

        parent::__construct();

        $po_num = Mage::app()->getRequest()->getParam('po_num', false);
        $model = Mage::getModel('Purchase/Order');
        $this->_order = $model->load($po_num);
        $this->_supplier = mage::getModel('Purchase/Supplier')->load($this->_order->getpo_sup_num());

        $this->setTemplate('horsebrands/purchase/order/edit/tab/info.phtml');
    }
}
