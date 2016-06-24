<?php

class Horsebrands_Purchaseorder_Block_Adminhtml_Purchase_SupplyNeeds_Renderer_Categories
  extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

  public function render(Varien_Object $row) {
    $productid =  $row->getData('product_id');
    $categories = Mage::getModel('catalog/product')->load($productid)->getCategoryCollection();
    $value = '';

    foreach ($categories as $cat) {
      $value .= Mage::getModel('catalog/category')->load($cat->getId())->getName().' (id: '.$cat->getId().'), ';
    }

    $value = substr($value, 0, -2);

    return $value;
  }
}
