<?php

require_once 'MDN/Purchase/controllers/OrdersController.php';

class Horsebrands_Purchaseorder_OrdersController extends MDN_Purchase_OrdersController {

    /**
     * Print
     *
     */
    public function printLogisticsAction() {
        //get order
        $po_num = $this->getRequest()->getParam('po_num');
        $order = Mage::getModel('Purchase/Order')->load($po_num);
        try {
            $obj = mage::getModel('Horsebrands_Purchaseorder/Pdf_Logistics');
            $pdf = $obj->getPdf(array($order));
            $this->_prepareDownloadResponse(mage::helper('purchase')->__('Purchase Order #') . $order->getpo_order_id() . '.pdf', $pdf->render(), 'application/pdf');
        } catch (Exception $ex) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('An error occured') . ' : ' . $ex->getMessage());
            $this->_redirect('Purchase/Orders/Edit', array('po_num' => $order->getId(), 'tab' => $currentTab));
        }
    }

}
