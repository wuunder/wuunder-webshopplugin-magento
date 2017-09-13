<?php
class Wuunder_WuunderConnector_Block_Adminhtml_Info
    extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        //$magentoVersion = Mage::getVersion();
        //$moduleVersion = Mage::getConfig()->getNode()->modules->Wuunder_WuunderConnector->version;
        $logoLink = Mage::getDesign()->getSkinBaseUrl(array('_area'=>'adminhtml','_package'=>'default','_theme'=>'default')).'wuunder/images/wuunder_logo.png';


        $html = '<div style="background-color:#EAF0EE; border:1px solid #CCCCCC; margin-bottom:10px; padding:20px; min-height: 100px">
        <img src="'.$logoLink.'" style="float:left; display:inline-block; padding: 0px 30px 0px 0px; width:80px;">
        <h4>Hallo, wij zijn Wuunder</h4>
        <p>En we maken het versturen en ontvangen van documenten, pakketten en pallets makkelijk en voordelig. Met ons platform boek je een zending of retour via mobiel, Mac, PC en webshop plug-in. Wij vergelijken de bekende vervoerders, kiezen de beste prijs en halen de zending bij jou of iemand anders op. En daarna volg je de zending in het overzichtsscherm en klik je op de track &amp; trace link voor meer details. Een foto sturen, vraag stellen of iets toelichten? Dat doe je via de Wuunder-chat. Wel zo persoonlijk.</p>
        <p>Meer weten? Bezoek onze website <a href="http://www.wearewuunder.com/" target="_blank">www.wearewuunder.com</a> of stuur een e-mail naar <a href="mailto:info@WeAreWuunder.com" target="_blank">Info@WeAreWuunder.com</a>.</p>
    </div>';

        return $html;
    }
}
