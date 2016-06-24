<?php

class Horsebrands_Purchaseorder_Block_Adminhtml_Purchase_SupplyNeeds_Renderer_Categories
  extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

  public function render(Varien_Object $row) {
    $productid =  $row->getData('product_id');
    $product = Mage::getModel('catalog/product')->load($productid);
    $categories = $product->getCategoryCollection();
    $value = '';

    $this->getCategoryString($categories, $value);

    $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
    if( $parentIds ) {
      foreach ($parentIds as $parentId) {
        $product = Mage::getModel('catalog/product')->load($parentId);
        $categories = $product->getCategoryCollection();
        $this->getCategoryString($categories, $value);
      }
    }

    $value = substr($value, 0, -6);

    return $value;
  }

  protected function getCategoryString($categories, &$value) {
    foreach ($categories as $cat) {
      $value .= Mage::getModel('catalog/category')->load($cat->getId())->getName().' (id: '.$cat->getId().'),<br/>';
    }
  }
}
