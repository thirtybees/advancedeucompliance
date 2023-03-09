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

namespace AdvancedEUComplianceModule;

use Db;
use DbQuery;
use ObjectModel;
use PrestaShopDatabaseException;
use PrestaShopException;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class AeucCMSRoleEmailEntity
 */
class AeucCMSRoleEmailEntity extends ObjectModel
{
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'aeuc_cmsrole_email',
        'primary' => 'id_aeuc_cmsrole_email',
        'fields'  => [
            'id_mail'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true, 'db_type' => 'INT(11) UNSIGNED'],
            'id_cms_role' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true, 'db_type' => 'INT(11) UNSIGNED'],
        ],
    ];

    /**
     * @var string name
     */
    public $id_cms_role;

    /**
     * @var int id_cms
     */
    public $id_mail;

    /**
     * Truncate Table
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function truncate()
    {
        $sql = 'TRUNCATE `'._DB_PREFIX_.static::$definition['table'].'`';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Return the complete list of cms_role_ids associated
     *
     * @param int $idCmsRole
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getIdEmailFromCMSRoleId($idCmsRole)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_mail`')
                ->from(bqSQL(static::$definition['table']))
                ->where('`id_cms_role` = '.(int) $idCmsRole)
        );
    }

    /**
     * Return the complete email collection from DB
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getAll()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from(bqSQL(static::$definition['table']))
        );
    }

    /**
     * @param int $idMail
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCMSRoleIdsFromIdMail($idMail)
    {
        return (array) Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('DISTINCT `id_cms_role`')
                ->from(bqSQL(static::$definition['table']))
                ->where('`id_mail` = '.(int) $idMail)
        );
    }
}
