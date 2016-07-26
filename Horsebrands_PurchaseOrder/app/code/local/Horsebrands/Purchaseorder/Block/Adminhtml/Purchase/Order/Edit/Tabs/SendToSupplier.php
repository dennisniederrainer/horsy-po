<?php

class Horsebrands_Purchaseorder_Block_Adminhtml_Purchase_Order_Edit_Tabs_SendToSupplier extends MDN_Purchase_Block_Order_Edit_Tabs_SendToSupplier {

	public function __construct() {
		parent::__construct();
		$this->setTemplate('horsebrands/purchase/order/edit/tab/sendToSupplier.phtml');
	}

}
