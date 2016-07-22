<?php

class Horsebrands_Purchaseorder_Block_Widget_Column_Filter_ProductCategories extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Text {

    public function getCondition() {
        if ($this->getValue()) {
            //get product ids matching to the reference
            $collection = mage::getModel('catalog/product')
                    ->getCollection()->getSelect()
                    ->join(
                        array('cat_prod' => 'catalog_category_product'),
                        'main_table.entity_id = cat_prod.product_id',
                        array('cat_prod.category_id')
                      // )->where("cat.category_id = ?",$value);
                      )->join(
                        array('cat' => 'catalog_category_flat_store_1'),
                        'cat_prod.category_id = cat.entity_id',
                        array('cat.name')
                      )->where("cat.name Like '%".$value."%'")->group("main_table.entity_id");
                    // ->addFieldToFilter('pps_reference', array('like' => '%'.$this->getValue().'%'));

            $ids = array();
            foreach ($collection as $item) {
                $ids[] = $item->getId();
            }

            return array('in' => $ids);
        }
    }

}
