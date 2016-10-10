<?php

class Horsebrands_Purchaseorder_Block_Adminhtml_Purchase_Widget_Column_Renderer_ProductDelivery_Outofstock
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

  public function render(Varien_Object $row) {
		$checkBoxName = 'supplier_out_of_stock_'.$row->getId();
		$html = '<input onclick="persistantDeliveryGrid.logChange(this.name, 0)" type="checkbox" name="'.$checkBoxName.'" id="'.$checkBoxName.'" value="1">';

		return $html;
  }

}
