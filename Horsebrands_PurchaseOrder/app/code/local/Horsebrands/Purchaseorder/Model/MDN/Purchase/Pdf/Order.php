<?php

class Horsebrands_Rewrites_Model_Purchase_Pdf_Order extends MDN_Purchase_Model_Pdf_Order {
  protected $_MARGIN = 25;

  public function getPdf($orders = array()) {
      $this->initLocale($orders);

      $this->_beforeGetPdf();
      $this->_initRenderer('invoice');

      if ($this->pdf == null)
          $this->pdf = new Zend_Pdf();
      else
          $this->firstPageIndex = count($this->pdf->pages);

      $style = new Zend_Pdf_Style();
      $style->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 10);

      foreach ($orders as $order) {

          //add new page
          $settings = array();
          $settings['title'] = "Einkaufsauftrag/Purchase Order";
          $settings['store_id'] = 0;
          $page = $this->NewPage($settings);

          //page header
          $orderInformation = array(
            'order_id' => $order->getpo_order_id(),
            'order_date' => date('d.m.Y', strtotime($order->getpo_date())),
            'campaign_week_no' => $order->getpo_campaign_week(),
            'campaign_id' => $order->getpo_campaign_code(),
            'delivery_date' => $order->getpo_supply_date(),
          );
          $address_supplier = $order->getSupplier()->getAddressAsText();
          $address_warehouse = $order->getTargetWarehouse()->getstock_address();
          $this->addOrderInformationBlock($page, $address_supplier, $address_warehouse, $orderInformation);

          //table header
          $this->drawTableHeader($page);

          $this->y -=10;

          $itemsTotal = 0;
          //Display products
          foreach ($order->getProducts() as $item) {

              //font initialization
              $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);

              // sku
              $sku = $item->getsku();
              $page->drawText($this->TruncateTextToWidth($page, $sku, 95), 15, $this->y, 'UTF-8');

              // sku supploer
              $sku_sup = $item->getpop_supplier_ref();
              $page->drawText($this->TruncateTextToWidth($page, $sku_sup, 95), 110, $this->y, 'UTF-8');

              //name
              $caption = $this->WrapTextToWidth($page, $item->getpop_product_name(), 210);
              if ($item->getpop_packaging_id())
              {
                  $caption .= "\n(".$item->getpop_packaging_name().')';
              }
              $offset = $this->DrawMultilineText($page, $caption, 215, $this->y, 10, 0, 11);

              //qty
              $page->drawText((int) $item->getOrderedQty(), 420, $this->y, 'UTF-8');
              $itemsTotal += (int) $item->getOrderedQty();

              //price
              $page->drawText($order->getCurrency()->formatTxt($item->getpop_price_ht()), 480, $this->y, 'UTF-8');

              //WEEE, tax rate, row total
              if ($order->getpo_status() != MDN_Purchase_Model_Order::STATUS_INQUIRY) {
                  $page->drawText($order->getCurrency()->formatTxt($item->getRowTotal()), 530, $this->y, 'UTF-8');
                  // $page->drawText($item->getRowTotal(), 530, $this->y, 'UTF-8');
              }

              $this->y -= $offset + 20;

              //new page if required
              if ($this->y < ($this->_BLOC_FOOTER_HAUTEUR + 40)) {
                  $this->drawFooter($page);
                  $page = $this->NewPage($settings);
                  $this->drawTableHeader($page);
              }
          }

          //add shipping costs
          if (false && $order->getpo_status() != MDN_Purchase_Model_Order::STATUS_INQUIRY) {
              $style->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 10);
              $this->DrawMultilineText($page, mage::helper('purchase')->__('Shipping costs'), 125, $this->y, 10, 0.2, 11);
              $this->drawTextInBlock($page, number_format($order->getpo_tax_rate(), 2) . '%', 470, $this->y, 40, 20, 'c');
              $this->drawTextInBlock($page, $order->getCurrency()->formatTxt($order->getShippingAmountHt()), 500, $this->y, 60, 20, 'r');

              //Tax & duties
              $this->y -= $this->_ITEM_HEIGHT;
              $style->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 10);
              $this->DrawMultilineText($page, mage::helper('purchase')->__('Taxes and Duties'), 125, $this->y, 10, 0.2, 11);
              $this->drawTextInBlock($page, number_format($order->getpo_tax_rate(), 2) . '%', 470, $this->y, 40, 20, 'c');
              $this->drawTextInBlock($page, $order->getCurrency()->formatTxt($order->getZollAmountHt()), 500, $this->y, 60, 20, 'r');
          }

          //new page if required
          if ($this->y < (150)) {
              $this->drawFooter($page);
              $page = $this->NewPage($settings);
              $this->drawTableHeader($page);
          }

          //grey line
          $this->y -= 40;
          $page->drawLine(10, $this->y, $this->_BLOC_ENTETE_LARGEUR, $this->y);

          //totals font
          $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
          $this->y -= 30;

          //Comments
          $comments = $order->getpo_comments();
          if (($comments != '') && ($comments != null)) {
              $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.3));
              $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 12);
              $page->drawText(mage::helper('purchase')->__('Comments'), 15, $this->y, 'UTF-8');
              $comments = $this->WrapTextToWidth($page, $comments, $this->_PAGE_WIDTH / 2);
              $this->DrawMultilineText($page, $comments, 15, $this->y - 15, 10, 0.2, 11);
          }
          if ($order->getpo_status() != MDN_Purchase_Model_Order::STATUS_INQUIRY) {
              $page->drawText('Gesamt:', 420, $this->y, 'UTF-8');
              $page->drawText('Gesamtbetrag:', 500, $this->y, 'UTF-8');
              $this->y -= 16;
              $page->drawText($itemsTotal, 420, $this->y, 'UTF-8');
              $page->drawText($order->getCurrency()->formatTxt($order->getTotalHt()), 530, $this->y, 'UTF-8');
          }
      }

      $this->_afterGetPdf();
      //reset language
      Mage::app()->getLocale()->revert();

      return $this->pdf;
  }

   public function drawTableHeader(&$page) {

       //entetes de colonnes
       $this->y -= 15;
       $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);

       $page->drawText(mage::helper('purchase')->__('Sku Horsebrands'), 15, $this->y, 'UTF-8');
       $page->drawText(mage::helper('purchase')->__('Sku Supplier'), 110, $this->y, 'UTF-8');
       $page->drawText(mage::helper('purchase')->__('Product Name'), 215, $this->y, 'UTF-8');
       $page->drawText(mage::helper('purchase')->__('Qty'), 420, $this->y, 'UTF-8');
       $page->drawText(mage::helper('purchase')->__('Price'), 480, $this->y, 'UTF-8');
       $page->drawText(mage::helper('purchase')->__('Subtotal'), 530, $this->y, 'UTF-8');

       //barre grise fin entete colonnes
       $this->y -= 8;
       $page->drawLine(10, $this->y, $this->_BLOC_ENTETE_LARGEUR, $this->y);

       $this->y -= 15;
   }

   protected function insertLogo(&$page, $StoreId = null) {
       $image = Mage::getStoreConfig('sales/identity/logo', $StoreId);
       if ($image) {
           $image = Mage::getBaseDir('media') . '/sales/store/logo/' . $image;
           if (is_file($image)) {
               $image = Zend_Pdf_Image::imageWithPath($image);
               $page->drawImage($image,
                   $this->_PAGE_WIDTH - $this->_MARGIN - $this->_LOGO_LARGEUR,
                   $this->_PAGE_HEIGHT - $this->_MARGIN - $this->_LOGO_HAUTEUR,
                   $this->_PAGE_WIDTH - $this->_MARGIN,
                   $this->_PAGE_HEIGHT - $this->_MARGIN);

               $this->y = $this->_PAGE_HEIGHT - $this->_MARGIN - $this->_LOGO_HAUTEUR;
           }
       }
       return $page;
   }

   public function drawHeader(&$page, $title, $StoreId = null) {
       $this->insertLogo($page, $StoreId);

       $name = $title;
       $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 14);
       $page->drawText($title, 25, $this->y, 'UTF-8');

       $this->y -= 10;
   }

   public function addOrderInformationBlock(&$page, $supplier, $warehouse, $orderInformation) {
       $info_right_col_start = 130;

       // Order Information
       $this->y -= 20;
       $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);

       $page->drawText('Auftragsnummer', 25, $this->y, 'UTF-8');
       $page->drawText($orderInformation['order_id'], $info_right_col_start, $this->y, 'UTF-8');
       $this->y -= 15;
       $page->drawText('Auftragsdatum', 25, $this->y, 'UTF-8');
       $page->drawText($orderInformation['order_date'], $info_right_col_start, $this->y, 'UTF-8');
       $this->y -= 15;
       if(isset($orderInformation['campaign_week_no'])) {
         $page->drawText('Aktionswoche', 25, $this->y, 'UTF-8');
         $page->drawText($orderInformation['campaign_week_no'], $info_right_col_start, $this->y, 'UTF-8');
         $this->y -= 15;
       }
       if(isset($orderInformation['campaign_id'])) {
         $page->drawText('Aktionscode', 25, $this->y, 'UTF-8');
         $page->drawText($orderInformation['campaign_id'], $info_right_col_start, $this->y, 'UTF-8');
         $this->y -= 15;
       }
       $page->drawText('Liefertermin', 25, $this->y, 'UTF-8');
       $page->drawText($orderInformation['delivery_date'], $info_right_col_start, $this->y, 'UTF-8');

       $this->y -= 30;
       $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 10);
       $page->drawText('Lieferadresse/Shipping Address', 25, $this->y, 'UTF-8');
       $page->drawText('Lieferant/Supplier', $this->_PAGE_WIDTH / 2 + 10, $this->y, 'UTF-8');
       $this->y -= 15;
       $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
       $this->DrawMultilineText($page, $warehouse, 25, $this->y, 10, 0, 16);
       $this->DrawMultilineText($page, $supplier, $this->_PAGE_WIDTH / 2 + 10, $this->y, 10, 0, 16);

       $this->y -= 110;
   }
}
