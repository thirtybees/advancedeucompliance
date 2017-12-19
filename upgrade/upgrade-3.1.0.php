<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    Thirty Bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

function upgrade_module_3_1_0($module)
{
    try {
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'aeuc_email` CHANGE `id_mail` id_aeuc_email INT(11) UNSIGNED NOT NULL AUTO_INCREMENT');
    } catch (PrestaShopDatabaseException $e) {
    }

    try {
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'aeuc_cmsrole_email` CHANGE `id` id_aeuc_cmsrole_email INT(11) UNSIGNED NOT NULL AUTO_INCREMENT');
    } catch (PrestaShopDatabaseException $e) {
    }

    return true;
}
