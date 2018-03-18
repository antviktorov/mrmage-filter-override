<?php
/**
 * Default helper class.
 *
 * @package     Mrmage_FilterOverride
 * @author      Anton Viktorv <antvik85@gmail.com>
 */
class Mrmage_FilterOverride_Helper_Data extends Mage_Core_Helper_Abstract
{
    const SETTING_ENABLED = 'mrmage_filteroverride/general/enabled';

    /**
     * Check if is module enabled.
     * @return bool
     */
    public function isEnabled()
    {
        return Mage::getStoreConfigFlag(self::SETTING_ENABLED);
    }
}