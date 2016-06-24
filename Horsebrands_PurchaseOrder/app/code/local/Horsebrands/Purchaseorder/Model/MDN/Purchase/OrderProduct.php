<?php

class Horsebrands_Purchaseorder_Model_MDN_Purchase_OrderProduct extends MDN_Purchase_Model_OrderProduct {

    protected function _beforeSave() {
        parent::_beforeSave();

        // Set supplier sku when no other reference is set
        if ($this->getpop_supplier_ref() == '') {
            $this->setpop_supplier_ref($this->getProduct()->getSupplierId());
        }
    }
}
