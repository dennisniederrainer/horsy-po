<?php

class Horsebrands_Purchaseorder_Block_Adminhtml_Purchase_Order_Edit extends MDN_Purchase_Block_Order_Edit {

	public function __construct()	{

    parent::__construct();
		$this->_addButton(
        'print_logistics',
        array(
            'label'     => Mage::helper('purchase')->__('Print Logistics'),
            'onclick'   => "window.location.href='".$this->getUrl('Purchase/Orders/printLogistics').'po_num/'.$this->getOrder()->getId()."'",
            'class'     => 'go'
        ),
				0,1,'header'
    );
	}

}
