<?php

class Wuunder_WuunderConnector_Helper_Data extends Mage_Core_Helper_Abstract
{
    const WUUNERCONNECTOR_LOG_FILE = 'wuunder.log';
    const XPATH_DEBUG_MODE = 'wuunderconnector/connect/debug_mode';
    const MIN_PHP_VERSION = '5.3.0';
    public $tblPrfx;


    function __construct()
    {
        $this->tblPrfx = (string)Mage::getConfig()->getTablePrefix();
    }

    public function log($message, $level = null, $file = null, $forced = false, $isError = false)
    {
        if ($isError === true && !$this->isExceptionLoggingEnabled() && !$forced) {
            return $this;
        } elseif ($isError !== true && !$this->isLoggingEnabled() && !$forced) {
            return $this;
        }

        if (is_null($level)) {
            $level = Zend_Log::DEBUG;
        }

        if (is_null($file)) {
            $file = static::WUUNERCONNECTOR_LOG_FILE;
        }

        Mage::log($message, $level, $file, $forced);

        return $this;
    }

    public function isLoggingEnabled()
    {
        if (version_compare(phpversion(), self::MIN_PHP_VERSION, '<')) {
            return false;
        }

        $debugMode = $this->getDebugMode();
        if ($debugMode > 0) {
            return true;
        }

        return false;
    }

    public function getDebugMode()
    {
        if (Mage::registry('wuunderconnector_debug_mode') !== null) {
            return Mage::registry('wuunderconnector_debug_mode');
        }

        $debugMode = (int)Mage::getStoreConfig(self::XPATH_DEBUG_MODE, Mage_Core_Model_App::ADMIN_STORE_ID);
        Mage::register('wuunderconnector_debug_mode', $debugMode);
        return $debugMode;
    }

    public function getAPIHost($storeId)
    {
        $test_mode = Mage::getStoreConfig('wuunderconnector/connect/testmode', $storeId);

        if ($test_mode == 1) {
            $apiUrl = 'https://api-staging.wearewuunder.com/api/';
//            $apiUrl = 'https://api-playground.wearewuunder.com/api/';
        } else {
            $apiUrl = 'https://api.wearewuunder.com/api/';
        }

        return $apiUrl;
    }

    public function getAPIKey($storeId)
    {
        $test_mode = Mage::getStoreConfig('wuunderconnector/connect/testmode', $storeId);

        if ($test_mode == 1) {
            $apiKey = Mage::getStoreConfig('wuunderconnector/connect/api_key_test', $storeId);
//            $apiKey = "pN2XAviEVCRgTsRPU3xWNOp4_4npbv8L";
        } else {
            $apiKey = Mage::getStoreConfig('wuunderconnector/connect/api_key_live', $storeId);
        }

        return $apiKey;
    }


    public function getWuunderOptions()
    {
        return array(
            'header' => 'Wuunder',
            'index' => 'wuunder_options',
            'type' => 'text',
            'width' => '40px',
            'renderer' => 'Wuunder_WuunderConnector_Block_Adminhtml_Order_Renderer_WuunderIcons',
            'filter' => false,
            'sortable' => false,
        );
    }

    /**
     * Retrieve shipment data from the wuunder database table
     *
     * @param $orderId
     * @return array
     */
    public function getShipmentInfo($orderId)
    {
        $shipment = Mage::getModel('wuunderconnector/wuundershipment');
        $shipment->load(intval($orderId), 'order_id');

        if ($shipment) {
            $returnArray = array(
                'shipment_id' => $shipment->getShipmentId(),
                'label_id' => $shipment->getLabelId(),
                'label_url' => $shipment->getLabelUrl(),
                'booking_url' => $shipment->getBookingUrl(),
                'booking_token' => $shipment->getBookingToken()
            );
        } else {
            $returnArray = array(
                'shipment_id' => '',
                'label_id' => '',
                'booking_url' => '',
                'booking_token' => ''
            );
        }

        return $returnArray;
    }

    /**
     * Generates data array with total weight (Sum of the weights of all products), and largest dimensions
     *
     * @param $orderId
     * @return array
     */
    public function getInfoFromOrder($orderId)
    {
        $weightUnit = Mage::getStoreConfig('wuunderconnector/magentoconfig/weight_units');
        // Get Magento order
        $orderInfo = Mage::getModel('sales/order')->load($orderId);
        $totalWeight = 0;
        $maxLength = 0;
        $maxWidth = 0;
        $maxHeight = 0;

        $order = Mage::getModel('sales/order')->load($orderId);
        $storeId = $order->getStoreId();

        // Get total weight from ordered items
        foreach ($orderInfo->getAllItems() AS $orderedItem) {
            // Calculate weight
            if (intval($this->getProductAttribute($storeId, $orderedItem->getProductId(), "length")) > $maxLength) {
                $maxLength = intval($this->getProductAttribute($storeId, $orderedItem->getProductId(), "length"));
            }
            if (intval($this->getProductAttribute($storeId, $orderedItem->getProductId(), "width")) > $maxWidth) {
                $maxWidth = intval($this->getProductAttribute($storeId, $orderedItem->getProductId(), "width"));
            }
            if (intval($this->getProductAttribute($storeId, $orderedItem->getProductId(), "height")) > $maxHeight) {
                $maxHeight = intval($this->getProductAttribute($storeId, $orderedItem->getProductId(), "height"));
            }

            if ($orderedItem->getWeight() > 0) {
                if ($weightUnit === 'kg') {
                    $productWeight = round($orderedItem->getQtyOrdered() * $orderedItem->getWeight() * 1000);
                } else {
                    $productWeight = round($orderedItem->getQtyOrdered() * $orderedItem->getWeight());
                }

                $totalWeight += $productWeight;
            }
        }
        return array(
            'total_weight' => $totalWeight,
            'length' => $maxLength,
            'width' => $maxWidth,
            'height' => $maxHeight
        );
    }

    private function getProductAttribute($storeId, $productId, $attributeCode)
    {
        return Mage::getResourceModel('catalog/product')->getAttributeRawValue($productId, $attributeCode, $storeId);
    }

    /**
     * Send curl request with shipment data to fetch booking url
     * @param $infoArray
     * @return array
     */
    public function processLabelInfo($infoArray)
    {
        // Fetch order
        $order = Mage::getModel('sales/order')->load($infoArray['order_id']);
        $storeId = $order->getStoreId();

        // Get configuration
        $booking_token = uniqid();
        $infoArray['booking_token'] = $booking_token;

        $apiUrl = $this->getAPIHost($storeId) . 'bookings';
        $apiKey = $this->getAPIKey($storeId);

        // Combine wuunder info and order data
        $wuunderJsonData = json_encode($this->buildWuunderData($infoArray, $order, $booking_token));

        // Setup API connection
        $cc = curl_init($apiUrl);
        $this->log('API connection established');

        curl_setopt($cc, CURLOPT_HTTPHEADER,
            array('Authorization: Bearer ' . $apiKey, 'Content-type: application/json'));
        curl_setopt($cc, CURLOPT_POST, 1);
        curl_setopt($cc, CURLOPT_POSTFIELDS, $wuunderJsonData);
        curl_setopt($cc, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cc, CURLOPT_VERBOSE, 1);
        curl_setopt($cc, CURLOPT_HEADER, 1);

        // Execute the cURL, fetch the XML
        $result = curl_exec($cc);
        $header_size = curl_getinfo($cc, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $header_size);
        preg_match("!\r\n(?:Location|URI): *(.*?) *\r\n!i", $header, $matches);

        // Close connection
        curl_close($cc);

        if (count($matches) >= 2) {
            $url = $matches[1];
            $infoArray['booking_url'] = $url;

            // Create or update wuunder_shipment
            if (!$this->saveWuunderShipment($infoArray)) {
                $this->log("Something went wrong with saving wuunder shipment booking");
                return array(
                    'error' => true,
                    'message' => 'Unable to create / update wuunder_shipment for order ' . $infoArray['order_id']
                );
            }
        } else {
            $this->log("Something went wrong:");
            $this->log($apiUrl);
            $this->log($header);
            $this->log($result);
            return array(
                'error' => true,
                'message' => 'Unable to create / update wuunder_shipment for order ' . $infoArray['order_id']
            );
        }

        Mage::helper('wuunderconnector')->log('API response string: ' . $result);

        if (empty($url)) {
            return array(
                'error' => true,
                'message' => 'Er ging iets fout bij het booken van het order. Controleer de logging.',
                'booking_url' => ""
            );
        } else {
            return array(
                'error' => false,
                'booking_url' => $url
            );
        }
    }

    /**
     * Save shipment data to existing wuunder shipment according to an orderid and booking token
     *
     * @param $wuunderApiResult
     * @param $orderId
     * @param $booking_token
     * @return bool
     */
    public function processDataFromApi($wuunderApiResult, $orderId, $booking_token)
    {
        $shipment = Mage::getModel('wuunderconnector/wuundershipment');
        $shipment->load(intval($orderId), 'order_id');
        if (!$shipment) {
            return false;
        }

        if ($shipment->getBookingToken() !== $booking_token) {
            return false;
        }

        $shipment->setLabelId($wuunderApiResult['id']);
        $shipment->setLabelUrl($wuunderApiResult['label_url']);
        $shipment->setLabelTtUrl($wuunderApiResult['track_and_trace_url']);
        $shipment->save();
        return true;
    }

    public function processTrackingDataFromApi($carrierCode, $trackingCode, $orderId, $bookingToken) {
        $shipment = Mage::getModel('wuunderconnector/wuundershipment');
        $shipment->load(intval($orderId), 'order_id');
        if (!$shipment) {
            return false;
        }
        if ($shipment->getBookingToken() !== $bookingToken) {
            return false;
        }

        $shipment->setCarrierTrackingCode($trackingCode);
        $shipment->setCarrierCode($carrierCode);
        $shipment->save();
        return true;
    }

    public function buildWuunderData($infoArray, $order, $bookingToken)
    {
        Mage::helper('wuunderconnector')->log("Building data object for api.");
        $shippingAddress = $order->getShippingAddress();

        $shippingLastname = $shippingAddress->lastname;

        if (!empty($shippingAddress->middlename)) {
            $shippingLastname = $shippingAddress->middlename . ' ' . $shippingLastname;
        }

        // Get full address, strip enters/newlines etc
        $addressLine = trim(preg_replace('/\s+/', ' ', $shippingAddress->street));

        // Split address in 3 parts
        $addressParts = $this->addressSplitter($addressLine);
        $streetName = $addressParts['streetName'];
        $houseNumber = $addressParts['houseNumber'] . $addressParts['houseNumberSuffix'];

        // Fix DPD parcelshop first- and lastname override fix
        $firstname = $shippingAddress->firstname;
        $lastname = $shippingLastname;
        $company = $shippingAddress->company;

        $customerAdr = array(
            'business' => $company,
            'email_address' => ($order->getCustomerEmail() !== '' ? $order->getCustomerEmail() : $shippingAddress->email),
            'family_name' => $lastname,
            'given_name' => $firstname,
            'locality' => $shippingAddress->city,
            'phone_number' => $infoArray['phone_number'],
            'street_name' => $streetName,
            'house_number' => $houseNumber,
            'zip_code' => $shippingAddress->postcode,
            'country' => $shippingAddress->country_id
        );

        $webshopAdr = array(
            'business' => Mage::getStoreConfig('wuunderconnector/connect/company'),
            'email_address' => Mage::getStoreConfig('wuunderconnector/connect/email'),
            'family_name' => Mage::getStoreConfig('wuunderconnector/connect/lastname'),
            'given_name' => Mage::getStoreConfig('wuunderconnector/connect/firstname'),
            'locality' => Mage::getStoreConfig('wuunderconnector/connect/city'),
            'phone_number' => Mage::getStoreConfig('wuunderconnector/connect/phone'),
            'street_name' => Mage::getStoreConfig('wuunderconnector/connect/streetname'),
            'house_number' => Mage::getStoreConfig('wuunderconnector/connect/housenumber'),
            'zip_code' => Mage::getStoreConfig('wuunderconnector/connect/zipcode'),
            'country' => Mage::getStoreConfig('wuunderconnector/connect/country')
        );

        $orderAmountExclVat = round(($order->getGrandTotal() - $order->getTaxAmount() - $order->getShippingAmount()) * 100);
        if ($orderAmountExclVat <= 0) {
            $orderAmountExclVat = 2500;
        }

        // Load product image for first ordered item
        $image = null;
        $orderedItems = $order->getAllVisibleItems();
        if (count($orderedItems) > 0) {
            foreach ($orderedItems AS $orderedItem) {
                $_product = Mage::getModel('catalog/product')->load($orderedItem->getProductId());
                try {
                    $base64Image = base64_encode(file_get_contents(Mage::helper('catalog/image')->init($_product,
                        'image')));
                } catch (Exception $e) {
                    //Do nothing, base64image is already NULL
                }
                if (!empty($base64Image)) {
                    // Break after first image
                    $image = $base64Image;
                    break;
                }
            }
        }

        $shipping_method = $order->getShippingMethod();
        $preferredServiceLevel = "";
        $shippingMethodCount = 5;
        for ($i = 1; $i <= $shippingMethodCount; $i++) {
            if ($shipping_method === Mage::getStoreConfig('wuunderconnector/connect/filterconnect' . $i . '_value')) {
                $preferredServiceLevel = Mage::getStoreConfig('wuunderconnector/connect/filterconnect' . $i . '_name');
                break;
            }
        }

        $description = $infoArray['description'];
        $picture = $image;
        if (file_exists("app/code/community/Wuunder/WuunderConnector/Override/override.php")) {
            require_once("app/code/community/Wuunder/WuunderConnector/Override/override.php");
            if (isset($overrideShippingDescription)) {
                $description = $overrideShippingDescription;
            }
            if (isset($overrideShippingImage) && file_exists("app/code/community/Wuunder/WuunderConnector/Override/" . $overrideShippingImage)) {
                $picture = base64_encode(file_get_contents("app/code/community/Wuunder/WuunderConnector/Override/" . $overrideShippingImage));
            }
        }


        $parcelshopId = null;
        if ($order->getShippingMethod() == 'wuunderparcelshop_wuunderparcelshop') {
            $parcelshopId = $this->getParcelshopIdForQuote($order->getQuoteId());
        }

        $sourceObj = array(
            "product" => "Magento 1 extension",
            "version" => array(
                "build" => "4.0.1",
                "plugin" => "4.0"
            ),
            "platform" => array(
                "name" => "Magento",
                "build" => Mage::getVersion()
            )
        );

        $staticDescription = Mage::getStoreConfig('wuunderconnector/connect/order_description');

        if (!empty($staticDescription))
            $description = $staticDescription;

        return array(
            'description' => $description,
            'picture' => $picture,
            'customer_reference' => $order->getIncrementId(),
            'value' => $orderAmountExclVat,
            'kind' => $infoArray['packing_type'],
            'length' => $infoArray['length'],
            'width' => $infoArray['width'],
            'height' => $infoArray['height'],
            'weight' => $infoArray['weight'],
            'delivery_address' => $customerAdr,
            'pickup_address' => $webshopAdr,
            'preferred_service_level' => $preferredServiceLevel,
            'parcelshop_id' => $parcelshopId,
            'source' => $sourceObj,
            'redirect_url' => Mage::getUrl('adminhtml') . 'sales_order?label_order=' . $infoArray['order_id'],
            'webhook_url' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'wuunderconnector/webhook/call/order_id/' . $infoArray['order_id'] . "/token/" . $bookingToken
        );
    }

    /*
     * Save shipment data to wuunder database table
     */
    public function saveWuunderShipment($infoArray)
    {
        // Check if wuunder_shipment already exists
        $shipment = Mage::getModel('wuunderconnector/wuundershipment');
        $shipment->load(intval($infoArray['order_id']), 'order_id');

        if ($shipment && $shipment->getShipmentId() > 0) {
            $shipment->setOrderId($infoArray['order_id']);
            $shipment->setBookingUrl($infoArray['booking_url']);
            $shipment->setBookingToken($infoArray['booking_token']);
        } else {
            $shipment->setData(array(
                "order_id" => $infoArray['order_id'],
                "booking_url" => $infoArray['booking_url'],
                "booking_token" => $infoArray['booking_token']
            ));
        }

        try {
            $shipment->save();
        } catch (Mage_Core_Exception $e) {
            $this->log('ERROR saveWuunderShipment : ' . $e);
            return false;
        }
        return true;
    }

    public function getWuunderShipment($id)
    {
        try {
            //check for a label id
            $shipment = Mage::getModel('wuunderconnector/wuundershipment');
            $shipment->load(intval($id), 'label_id');


            if ($shipment) {
                return $shipment;
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->log('ERROR getWuunderShipment : ' . $e);
            return false;
        }
    }

    public function addressSplitter($address, $address2 = null, $address3 = null)
    {

        if (!isset($address)) {
            return false;
        }

        if (isset($address2) && $address2 != '' && isset($address3) && $address3 != '') {

            $result['streetName'] = $address;
            $result['houseNumber'] = $address2;
            $result['houseNumberSuffix'] = $address3;

        } else {
            if (isset($address2) && $address2 != '') {

                $result['streetName'] = $address;

                // Pregmatch pattern, dutch addresses
                $pattern = '#^([0-9]{1,5})([a-z0-9 \-/]{0,})$#i';

                preg_match($pattern, $address2, $houseNumbers);

                $result['houseNumber'] = $houseNumbers[1];
                $result['houseNumberSuffix'] = (isset($houseNumbers[2])) ? $houseNumbers[2] : '';

            } else {

                // Pregmatch pattern, dutch addresses
                $pattern = '#^([a-z0-9 [:punct:]\']*) ([0-9]{1,5})([a-z0-9 \-/]{0,})$#i';

                preg_match($pattern, $address, $addressParts);

                $result['streetName'] = isset($addressParts[1]) ? $addressParts[1] : $address;
                $result['houseNumber'] = isset($addressParts[2]) ? $addressParts[2] : "";
                $result['houseNumberSuffix'] = (isset($addressParts[3])) ? $addressParts[3] : '';
            }
        }

        return $result;
    }

    public function getLatitudeAndLongitude($address)
    {
        Mage::getSingleton('core/session', array('name' => 'frontend'));
        if (!is_null($address)) {
            $addressToInsert = $address;
        } else {
            $addressToInsert = $this->getAddressFromQuote();
        }

        $url = 'http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($addressToInsert) . '&sensor=false&language=nl';
        $data = json_decode(file_get_contents($url));

        if (count($data->results) < 1) {
            $source = file_get_contents($url);
            $data = json_decode($source);
        }
        if (count($data->results) > 0) {
            return array(
                "lat" => $data->results[0]->geometry->location->lat,
                "long" => $data->results[0]->geometry->location->lng,
                "formatted_address" => $data->results[0]->formatted_address,
                "error" => $url
            );
        }
        return array(
            "error" => $url
        );

    }

    public function getAddressFromQuote()
    {
        $address = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress();

        $addressToInsert = $address->getStreet(1) . " ";
        if ($address->getStreet(2)) {
            $addressToInsert .= $address->getStreet(2) . " ";
        }

        $addressToInsert .= $address->getPostcode() . " " . $address->getCity() . " " . $address->getCountry();

        return $addressToInsert;
    }

    public function getParcelshops($address)
    {
        $carrierData = Mage::getStoreConfig('carriers/wuunderparcelshop/parcelshop_carriers');
        if (empty($carrierData)) {
            return false;
        }
        $carrierData = unserialize($carrierData);
        $carriers = array();

        foreach ($carrierData as $carrier) {
            array_push($carriers, $carrier['carrier']);
        }
        $addCarriers = "providers[]=" . implode('&providers[]=', $carriers);

        $countryString = "&search_countries[]=" . Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getCountry();

        if (!empty($address)) {
            return json_decode($this->doParcelshopRequest("parcelshops_by_address?" . $addCarriers . "&address=" . urlencode($address) . "&radius=&hide_closed=true" . $countryString));
        }

        return false;
    }

    public function getParcelshopById($id)
    {
        if (!empty($id)) {
            return $this->doParcelshopRequest("parcelshops/" . $id);
        }

        return false;
    }

    private function doParcelshopRequest($uriPath)
    {
        $storeId = Mage::app()->getStore();
        $apiUrl = $this->getAPIHost($storeId) . $uriPath;
        $apiKey = $this->getAPIKey($storeId);

        $cc = curl_init($apiUrl);

        curl_setopt($cc, CURLOPT_HTTPHEADER,
            array('Authorization: Bearer ' . $apiKey, 'Content-type: application/json'));
        curl_setopt($cc, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cc, CURLOPT_VERBOSE, 1);

        // Execute the cURL, fetch the XML
        $result = curl_exec($cc);
        curl_close($cc);
        return $result;
    }

    public function addParcelshopsHTML($html)
    {
        preg_match('!<label for="(.*?)wuunderparcelshop">(.*?)<\/label>!s', $html, $matches);
        if (isset($matches[0])) {

            //get allowed carriers
            $carriers = array();
            $carrierData = Mage::getStoreConfig('carriers/wuunderparcelshop/parcelshop_carriers');
            if (!empty($carrierData)) {
                $carrierData = unserialize($carrierData);
                $carriers = array();
                foreach ($carrierData as $carrier) {
                    array_push($carriers, $carrier['carrier']);
                }
            }

            $parcelshopHtml = Mage::app()
                ->getLayout()
                ->createBlock('core/template')
                ->setOneStepCheckoutHtml($this->getOneStepValidationField($html))
                ->setCurrentParcelshopInfo($this->getCurrentSetParcelshopInfo())
                ->setWebshopBaseUrl(Mage::getUrl('', array('_secure' => Mage::app()->getStore()->isFrontUrlSecure())))
                ->setApiBaseUrl(str_replace("api/", "", $this->getAPIHost(Mage::app()->getStore()->getStoreId())))
                ->setAllowedCarriers($carriers)
                ->setTemplate('wuunder/parcelshopsContainer.phtml')
                ->toHtml();

            $html = str_replace($matches[0],
                $matches[0] . $parcelshopHtml,
                $html);
                $html = str_replace("name=\"shipping_method\"",
                    "name=\"shipping_method\" onclick=\"switchShippingMethodValidation(event);" . ($this->getIsOnestepCheckout() ? "switchShippingMethodValidation(event);" : "") . "\"", $html);
        }
        return $html;
    }

    private function getOneStepValidationField($html)
    {
        if ($this->getIsOnestepCheckout() && $this->_checkIfParcelShippingIsSelected($html)) {
            $quote_id = Mage::getSingleton('checkout/session')->getQuote()->getEntityId();
            $parcelshopId = $this->getParcelshopIdForQuote($quote_id);
            return '<input id="onestepValidationField" class="validate-text required-entry" value="' . $parcelshopId . '">';
        }
        return '';
    }

    private function _checkIfParcelShippingIsSelected($html)
    {
        preg_match('(<input[^>]+id="s_method_wuunderparcelshop_wuunderparcelshop"[^>]+checked="checked"[^>]+>)s', $html,
            $matches);
        return isset($matches[0]);
    }

    public function getCurrentSetParcelshopInfo()
    {
        $quote_id = Mage::getSingleton('checkout/session')->getQuote()->getEntityId();
        $parcelshop_id = $this->getParcelshopIdForQuote($quote_id);
        if (is_null($parcelshop_id)) {
            return "<div id='parcelShopsSelected'></div>";
        } else {
            $parcelshop_info = json_decode($this->getParcelshopById($parcelshop_id));
            $selectedParcelshopHtml = Mage::app()
                ->getLayout()
                ->createBlock('core/template')
                ->setParcelshopInfo($parcelshop_info)
                ->setTemplate('wuunder/selectedParcelshop.phtml')
                ->toHtml();
            return $selectedParcelshopHtml;
        }
    }

    public function setParcelshopIdForQuote($quote_id, $parcelshop_id)
    {
        $mageDbW = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sqlQuery = "SELECT `id` FROM `" . $this->tblPrfx . "wuunder_quote_data` WHERE `quote_id` = ? LIMIT 1";
        $id = $mageDbW->fetchOne($sqlQuery, array($quote_id));

        if ($id > 0) {
            $sqlQuery = "UPDATE `" . $this->tblPrfx . "wuunder_quote_data` SET
                        `quote_id`          = ?,
                        `parcelshop_id`       = ?
                    WHERE
                        `id` = ?";

            $sqlValues = array($quote_id, $parcelshop_id, $id);
        } else {
            $sqlQuery = "INSERT INTO `" . $this->tblPrfx . "wuunder_quote_data` SET
                        `quote_id`          = ?,
                        `parcelshop_id`       = ?";

            $sqlValues = array($quote_id, $parcelshop_id);
        }

        try {
            $mageDbW->query($sqlQuery, $sqlValues);
            return true;
        } catch (Mage_Core_Exception $e) {
            $this->log('ERROR saveWuunderShipment : ' . $e);
            return false;
        }
    }

    public function getParcelshopIdForQuote($id)
    {
        try {
            $mageDbW = Mage::getSingleton('core/resource')->getConnection('core_write');

            //check for a label id
            $sqlQuery = "SELECT `parcelshop_id` FROM `" . $this->tblPrfx . "wuunder_quote_data` WHERE `quote_id` = " . $id . " LIMIT 1";
            $parcelshopId = $mageDbW->fetchOne($sqlQuery);

            if ($parcelshopId) {
                return $parcelshopId;
            } else {
                return null;
            }
        } catch (Exception $e) {
            $this->log('ERROR getWuunderShipment : ' . $e);
            return null;
        }
    }

    public function removeParcelshopIdForQuote($quoteId)
    {
        try {
            $mageDbW = Mage::getSingleton('core/resource')->getConnection('core_write');
            $sqlQuery = "DELETE FROM `" . $this->tblPrfx . "wuunder_quote_data` WHERE `quote_id` = " . $quoteId;
            $mageDbW->query($sqlQuery);
            return true;
        } catch (Exception $e) {
            $this->log('ERROR getWuunderShipment : ' . $e);
            return false;
        }
    }

    public function getParcelshopCarriers()
    {
        return array(
            array(
                "value" => "DPD",
                "label" => "DPD"
            ),
            array(
                "value" => "DHL_PARCEL",
                "label" => "DHL"
            ),
            array(
                "value" => "POST_NL",
                "label" => "PostNL"
            )
        );
    }

    /**
     * Selects the radiobutton for default selected shipping method.
     *
     * @param $html
     * @param $node
     * @return mixed
     */
    protected function _selectNode($html, $node)
    {
        preg_match('(<input[^>]+id="' . $node . '"[^>]+>)s', $html, $matches);
        if (isset($matches[0])) {
            $checked = str_replace('/>', ' checked="checked" />', $matches[0]);
            $html = str_replace($matches[0],
                $checked, $html);
        }
        return $html;
    }

    /**
     * Calculates total weight of a shipment.
     *
     * @param $shipment
     * @return int
     */
    public function calculateTotalShippingWeight($shipment)
    {
        $weight = 0;
        $shipmentItems = $shipment->getAllItems();
        foreach ($shipmentItems as $shipmentItem) {
            $orderItem = $shipmentItem->getOrderItem();
            if (!$orderItem->getParentItemId()) {
                $weight = $weight + ($shipmentItem->getWeight() * $shipmentItem->getQty());
            }
        }
        return $weight;
    }

    /**
     * Check if on Onestepcheckout page or if Onestepcheckout is the refferer
     *
     * @return bool
     */
    public function getIsOnestepCheckout()
    {
        if (strpos(Mage::helper("core/url")->getCurrentUrl(),
                'onestep') !== false || strpos(Mage::app()->getRequest()->getHeader('referer'),
                'onestepcheckout') !== false
        ) {
            return true;
        }
        return false;
    }

    /**
     * Return our custom js when the check for onestepcheckout returns true.
     *
     * @return string
     */
    public function getOnestepCheckoutJs()
    {
        if ($this->getIsOnestepCheckout()) {
            return 'wuunder/onestepcheckout.js';
        }
        return '';
    }
}
