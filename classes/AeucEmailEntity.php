<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    Thirty Bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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
 * Class AeucEmailEntity
 */
class AeucEmailEntity extends ObjectModel
{
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'aeuc_email',
        'primary' => 'id_aeuc_email',
        'fields'  => [
            'filename'     => ['type' => self::TYPE_STRING, 'required' => true, 'db_type' => 'VARCHAR(64)', 'size' => 64],
            'display_name' => ['type' => self::TYPE_STRING, 'required' => true, 'db_type' => 'VARCHAR(64)', 'size' => 64],
        ],
    ];

    /**
     * @var int id_mail
     */
    public $id_mail;

    /**
     * @var string filename
     */
    public $filename;

    /**
     * @var string display_name
     */
    public $display_name;

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
     * @param string $tplName
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getMailIdFromTplFilename($tplName)
    {
        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`'.bqSQL(static::$definition['primary']).'`')
                ->from(bqSQL(static::$definition['table']))
                ->where('`filename` = \''.pSQL($tplName).'\'')
        );
    }
}
