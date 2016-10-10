<?php

require_once 'MDN/Purchase/controllers/OrdersController.php';

/*
  ACHTUNG:  es ist mir nicht möglich den Purchase Controller zu überschreiben.
            Deswegen ist alles im MDN Controller.
*/


class Horsebrands_Purchaseorder_Adminhtml_OrdersController extends MDN_Purchase_OrdersController {

  public function printLogisticsAction() {
      //get order
      $po_num = $this->getRequest()->getParam('po_num');
      $order = Mage::getModel('Purchase/Order')->load($po_num);
      try {
          $obj = mage::getModel('horsebrands_purchaseorder/MDN_Purchase_Pdf_Logistics');
          $pdf = $obj->getPdf(array($order));
          $this->_prepareDownloadResponse(mage::helper('purchase')->__('Purchase Order #') . $order->getpo_order_id() . '.pdf', $pdf->render(), 'application/pdf');
      } catch (Exception $ex) {
          Mage::getSingleton('adminhtml/session')->addError($this->__('An error occured') . ' : ' . $ex->getMessage());
          $this->_redirect('Purchase/Orders/Edit', array('po_num' => $order->getId(), 'tab' => $currentTab));
      }
  }

    public function saveAction() {
      die('fukc');
      Mage::log('zack', null, 'kerstin.log');
      parent::saveAction();
      return;

        $order = mage::getModel('Purchase/Order')->load($this->getRequest()->getPost('po_num'));
        $currentTab = $this->getRequest()->getPost('current_tab');

        //init updater
        $purchaseOrderUpdater = mage::getModel('Purchase/Order_Updater')->init($order);

        try {

            $order = mage::getModel('Purchase/Order')->load($this->getRequest()->getPost('po_num'));

            //init data
            $data = $this->getRequest()->getPost();
            $dateFields = array('po_supply_date', 'po_invoice_date', 'po_payment_date');
            foreach ($dateFields as $dateField) {
                if (($data[$dateField] == '') || ($data[$dateField] == '0000-00-00'))
                    $data[$dateField] = new Zend_Db_Expr('null');
            }

            //save order data
            foreach ($data as $key => $value)
                $order->setData($key, $value);
            $order->save();

            //update products
            $productLogDatas = $this->productsDataToArray($data['order_product_log']);
            $products = $order->getProducts();
            $hasDeleted = false;
            foreach ($products as $product) {
                //if we have to update datas
                if (isset($productLogDatas[$product->getId()])) {
                    $currentProductData = $productLogDatas[$product->getId()];
                    if (isset($currentProductData['delete'])) {
                        $product->delete();
                        $hasDeleted = true;
                    } else {
                        //update datas
                        $dateField = array('pop_delivery_date');
                        foreach ($currentProductData as $key => $value) {
                            if (in_array($key, $dateField)) {
                                if (($value == '') || ($value == '0000-00-00'))
                                    $value = new Zend_Db_Expr('null');
                            }
                            $product->setData($key, $value);
                        }
                        $product->save();
                    }
                } else {
                    //if currency change rate has changed, just call save on products to update base values
                    if ($order->getpo_currency_change_rate() != $order->getOrigData('po_currency_change_rate')) {
                        $product->save();
                    }
                }
            }
            if ($hasDeleted)
                $order->resetProducts();

            //check if we have to add products
            $productAdded = false;
            if ($this->getRequest()->getPost('add_product') != '') {
                $productsToAdd = $this->_decodeInput($this->getRequest()->getPost('add_product'));
                foreach ($productsToAdd as $key => $value) {
                    //retrieves values
                    $productId = $key;
                    $qty = $value['qty'];
                    if ($qty == '')
                        $qty = 1;

                    //add product
                    $order->AddProduct($productId, $qty);
                    $productAdded = true;
                }

                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Products added'));
                $currentTab = 'tab_products';
                $order->resetProducts();
            }

            //convert supply needs data to array
            $supplyNeedsIds = array();
            $supplyNeedsData = explode(';', $this->getRequest()->getPost('supply_needs_ids'));
            foreach ($supplyNeedsData as $item) {
                $tmp = explode('=', $item);
                if (count($tmp) == 2) {
                    $qty = $tmp[1];
                    $productId = str_replace('qty_', '', $tmp[0]);
                    if ($qty > 0) {
                        $supplyNeedsIds[$productId] = $qty;
                    }
                }
            }

            //Add supply needs
            foreach ($supplyNeedsIds as $productId => $qty) {
                try {
                    $order->AddProduct($productId, $qty);
                } catch (Exception $ex) {
                    Mage::getSingleton('adminhtml/session')->addError($ex->getMessage());
                }

                $productAdded = true;
            }
            if ($this->getRequest()->getPost('supply_needs_ids') != '') {
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Supply Needs added'));
                $currentTab = 'tab_products';
                $order->resetProducts();
            }

            //check for deliveries
            $targetWarehouseId = $this->getRequest()->getPost('add_sm_warehouse_id');
            $targetWarehouse = mage::getModel('AdvancedStock/Warehouse')->load($targetWarehouseId);
            $defectWarehouseId = mage::getStoreConfig('purchase/purchase_order/defect_products_warehouse');
            $deliveryData = $this->deliveryDataToArray($this->getRequest()->getPost('delivery_log'));

            foreach ($products as $item) {

                //skip product if no delivery information
                if (!isset($deliveryData[$item->getId()]))
                    continue;


                Mage::log(print_r($deliveryData[$item->getId()],true), null, 'kerstin.log');
                if (isset($deliveryData[$item->getId()]['supplier_out_of_stock'])) {

                  // $order->createDelivery($product, 0, $this->getRequest()->getPost('add_sm_date'), 'Nicht lieferbar!!', $this->getRequest()->getPost('add_sm_warehouse_id'));
                  //   $product->delete();
                  //   $hasDeleted = true;
                }

                //retrieve datas
                $qty = (int) $deliveryData[$item->getId()]['delivery_qty'];
                $defectQty = (int) (isset($deliveryData[$item->getId()]['delivery_defect_qty']) ? $deliveryData[$item->getId()]['delivery_defect_qty'] : 0);
                if (($qty == 0) && ($defectQty == 0))
                    continue;
                $barcode = (isset($deliveryData[$item->getId()]['delivery_barcode']) ? $deliveryData[$item->getId()]['delivery_barcode'] : '');
                $serials = (isset($deliveryData[$item->getId()]['delivery_serials']) ? $deliveryData[$item->getId()]['delivery_serials'] : '');
                $location = (isset($deliveryData[$item->getId()]['delivery_location']) ? $deliveryData[$item->getId()]['delivery_location'] : '');
                $productId = $item->getpop_product_id();
                $deliveryDate = $this->getRequest()->getPost('add_sm_date');
                $deliveryDescription = mage::helper('purchase')->__('Purchase Order #') . $order->getpo_order_id() . mage::helper('purchase')->__(' from ') . $order->getSupplier()->getsup_name();

                //create delivery
                if ($qty > 0)
                    $order->createDelivery($item, $qty, $deliveryDate, $deliveryDescription, $targetWarehouseId);
                if ($defectQty > 0)
                    $order->createDelivery($item, $defectQty, $deliveryDate, $deliveryDescription, $defectWarehouseId);

                //save barcode
                if ($barcode)
                    mage::helper('AdvancedStock/Product_Barcode')->addBarcodeIfNotExists($productId, $barcode);

                //save location
                if ($location != '')
                    $targetWarehouse->setProductLocation($item->getpop_product_id(), $location);

                //save serials
                if ($serials != '')
                    mage::helper('AdvancedStock/Product_Serial')->addSerialsFromDelivery($productId, $order, $serials);
            }

            //If completely delivered, set status to complete
            if ($order->isCompletelyDelivered())
                $order->setpo_status(MDN_Purchase_Model_Order::STATUS_COMPLETE);

            //update missing prices flag
            $order->setpo_missing_price($order->hasMissingPrices());

            //notify supplier
            $Notify = $this->getRequest()->getPost('send_to_customer');
            if ($Notify == 1) {
                $order->notifySupplier($this->getRequest()->getPost('email_comment'));
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Supplier notified'));

                //change status
                switch ($this->getRequest()->getPost('change_status')) {
                    case 'waiting_for_supplier':
                        $order->setpo_status(MDN_Purchase_Model_Order::STATUS_WAITING_FOR_SUPPLIER);
                        break;
                    case 'waiting_for_delivery':
                        $order->setpo_status(MDN_Purchase_Model_Order::STATUS_WAITING_FOR_DELIVERY);
                        break;
                }
            }
            $order->save();

            //apply updater
            $result = $purchaseOrderUpdater->checkForChangesAndLaunchUpdates($order);

            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Order successfully Saved'));
        } catch (Exception $ex) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('An error occured') . ' : ' . $ex->getMessage());
        }

        //confirm & redirect
        $this->_redirect('Purchase/Orders/Edit', array('po_num' => $order->getId(), 'tab' => $currentTab));
    }

}
