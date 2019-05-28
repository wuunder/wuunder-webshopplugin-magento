# Wuunder Magento Plugin

## Hello, we are [Wuunder](https://wearewuunder.com/) ##
We make shipping any parcel, pallet and document easy, personal and efficient. As a business owner, you can book your shipment using your mobile, tablet or computer. We select the best price and pick up your parcel wherever you want. You and the receiver can both track and trace the shipment. You can also stay in contact with each other via our Wuunder chat. Everything without a contract. Why complicate things?
Wuunder Magento 1 Extension. Create bookings in your magento webshop.

More info regarding the installation: https://wearewuunder.com/verzend-module-magento/

## Before you start ##
* You need to create a free Wuunder account: https://app.wearewuunder.com and request an API-key to use the module: https://wearewuunder.com/en/contact/ 

* You can download and install the latest release before you sign-up: https://github.com/wuunder/wuunder-webshopplugin-magento/releases/latest

* With this module you connect your Magento1 store to your Wuunder account.

## Install ##

Download the latest [release](https://github.com/wuunder/wuunder-webshopplugin-magento/releases/latest).
Download the dependencies added zip if you are unfamiliar with composer.
Copy the contents of the wuunder-webshopplugin-magento-xxxxxxx folder to the root of the magento installation. All files will be placed in the correct directories. The extension works with PHP 5.6 and higher. (Tested until PHP 7.1)
Clear the magento cache afterwards.

### Dependencies

-   Soap support, for PHP 7 do `sudo apt-get install php7.0-soap`

## Debugging

-   Enable debugging in the configuration of the extension
-   Check /magentoroot/var/log/wuunder.log for debugging

## Changelog ##
Changelog can be found [here](CHANGELOG.md).
