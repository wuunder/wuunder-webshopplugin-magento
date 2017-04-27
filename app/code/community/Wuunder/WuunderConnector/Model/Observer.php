<?php
class Wuunder_WuunderConnector_Model_Observer extends Varien_Event_Observer {

	public function salesOrderGridCollectionLoadBefore($observer)
	{
		$collection = $observer->getOrderGridCollection();
        $select = $collection->getSelect();
        $select->joinLeft(
        	array(
        		'wuunder' => $collection->getTable('wuunderconnector/shipments')
        	),
			'wuunder.order_id = main_table.entity_id',
			array('label_id','label_url','label_tt_url','retour_id','retour_url','retour_tt_url')
		);
	}
}
