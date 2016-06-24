<?php
$installer = $this;
$installer->startSetup();

$installer->run("ALTER TABLE purchase_order ADD COLUMN po_campaign_code VARCHAR(255) NULL");
$installer->run("ALTER TABLE purchase_order ADD COLUMN po_campaign_week VARCHAR(255) NULL");

$installer->endSetup();
