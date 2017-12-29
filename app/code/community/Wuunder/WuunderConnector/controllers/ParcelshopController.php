<?php

class Wuunder_WuunderConnector_ParcelshopController extends Mage_Core_Controller_Front_Action
{

    public function shopsAction()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $this->loadLayout();
        $this->renderLayout();
    }
}