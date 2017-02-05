<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class AeucCMSRoleEmailEntity
 *
 * @since 1.0.0
 */
class AeucCMSRoleEmailEntity extends ObjectModel
{
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'aeuc_cmsrole_email',
        'primary' => 'id',
        'fields'  => [
            'id_mail'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_cms_role' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
        ],
    ];
    // @codingStandardsIgnoreStart
    /** @var string name */
    public $id_cms_role;
    /** @var integer id_cms */
    public $id_mail;
    // @codingStandardsIgnoreEnd

    /**
     * Truncate Table
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     */
    public static function truncate()
    {
        $sql = 'TRUNCATE `'._DB_PREFIX_.AeucCMSRoleEmailEntity::$definition['table'].'`';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Return the complete list of cms_role_ids associated
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     */
    public static function getIdEmailFromCMSRoleId($idCmsRole)
    {
        $sql = '
		SELECT `id_mail`
		FROM `'._DB_PREFIX_.AeucCMSRoleEmailEntity::$definition['table'].'`
		WHERE `id_cms_role` = '.(int) $idCmsRole;

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Return the complete email collection from DB
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     */
    public static function getAll()
    {
        $sql = '
		SELECT *
		FROM `'._DB_PREFIX_.AeucCMSRoleEmailEntity::$definition['table'].'`';

        return Db::getInstance()->executeS($sql);
    }

    public static function getCMSRoleIdsFromIdMail($idMail)
    {
        $sql = '
		SELECT DISTINCT(`id_cms_role`)
		FROM `'._DB_PREFIX_.AeucCMSRoleEmailEntity::$definition['table'].'`
		WHERE `id_mail` = '.(int) $idMail;

        return Db::getInstance()->executeS($sql);
    }

}
