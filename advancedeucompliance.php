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

if (!defined('_TB_VERSION_')) {
    exit;
}

/* Include required entities */
include_once __DIR__.'/entities/AeucCMSRoleEmailEntity.php';
include_once __DIR__.'/entities/AeucEmailEntity.php';

/**
 * Class Advancedeucompliance
 *
 * @since 1.0.0
 */
class Advancedeucompliance extends Module
{
    // @codingStandardsIgnoreStart
    const LEGAL_NO_ASSOC = 'NO_ASSOC';
    const LEGAL_NOTICE = 'LEGAL_NOTICE';
    const LEGAL_CONDITIONS = 'LEGAL_CONDITIONS';
    const LEGAL_REVOCATION = 'LEGAL_REVOCATION';
    const LEGAL_REVOCATION_FORM = 'LEGAL_REVOCATION_FORM';
    const LEGAL_PRIVACY = 'LEGAL_PRIVACY';
    const LEGAL_ENVIRONMENTAL = 'LEGAL_ENVIRONMENTAL';

    const LEGAL_SHIP_PAY = 'LEGAL_SHIP_PAY';
    const DEFAULT_PS_PRODUCT_WEIGHT_PRECISION = 2;
    protected $configForm = false;
    protected $entityManager;
    protected $filesystem;
    protected $emails;
    protected $errors;
    protected $warnings;
    protected $missingTemplates = [];
    // @codingStandardsIgnoreEnd

    /**
     * Advancedeucompliance constructor.
     *
     * @param Core_Foundation_Database_EntityManager $entityManager
     * @param Core_Foundation_FileSystem_FileSystem  $fs
     * @param Core_Business_Email_EmailLister        $email
     *
     * @since 1.0.0
     */
    public function __construct(
        Core_Foundation_Database_EntityManager $entityManager,
        Core_Foundation_FileSystem_FileSystem $fs,
        Core_Business_Email_EmailLister $email
    ) {

        $this->name = 'advancedeucompliance';
        $this->tab = 'administration';
        $this->version = '3.1.0';
        $this->author = 'thirty bees';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        /* Register dependencies to module */
        $this->entityManager = $entityManager;
        $this->filesystem = $fs;
        $this->emails = $email;

        $this->displayName = $this->l('Advanced EU Compliance', 'advancedeucompliance');
        $this->description = $this->l('This module helps European merchants comply with applicable e-commerce laws.', 'advancedeucompliance');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?', 'advancedeucompliance');

        /* Init errors var */
        $this->errors = [];
    }

    /**
     * Install this module
     *
     * @return bool Indicates whether this module has been successfully installed
     *
     * @since 1.0.0
     */
    public function install()
    {
        $return = parent::install() &&
            $this->loadTables() &&
            $this->installHooks() &&
            $this->registerModulesBackwardCompatHook() &&
            $this->registerHook('header') &&
            $this->registerHook('displayProductPriceBlock') &&
            $this->registerHook('overrideTOSDisplay') &&
            $this->registerHook('actionEmailAddAfterContent') &&
            $this->registerHook('advancedPaymentOptions') &&
            $this->registerHook('displayAfterShoppingCartBlock') &&
            $this->registerHook('displayBeforeShoppingCartBlock') &&
            $this->registerHook('displayCartTotalPriceLabel') &&
            $this->createConfig();

        $this->emptyTemplatesCache();

        return (bool) $return;
    }

    /**
     * Load database tables
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function loadTables()
    {
        $state = true;

        // Create module's table
        AeucCMSRoleEmailEntity::createDatabase();
        AeucEmailEntity::createDatabase();

        // Fill in CMS ROLE
        $rolesArray = $this->getCMSRoles();
        $roles = array_keys($rolesArray);
        $cmsRoleRepository = $this->entityManager->getRepository('CMSRole');

        foreach ($roles as $role) {
            if (!$cmsRoleRepository->findOneByName($role)) {
                /** @var CMSRole $cmsRole */
                $cmsRole = $cmsRoleRepository->getNewEntity();
                $cmsRole->id_cms = 0; // No assoc at this time
                $cmsRole->name = $role;
                $state &= (bool) $cmsRole->save();
            }
        }

        $defaultPathEmail = _PS_MAIL_DIR_.'en'.DIRECTORY_SEPARATOR;
        // Fill-in aeuc_mail table
        foreach ($this->emails->getAvailableMails($defaultPathEmail) as $mail) {
            $newEmail = new AeucEmailEntity();
            $newEmail->filename = (string) $mail;
            $newEmail->display_name = $this->emails->getCleanedMailName($mail);
            $newEmail->save();
            unset($newEmail);
        }

        return $state;
    }

    /**
     * Get CMS roles
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function getCMSRoles()
    {
        return [
            Advancedeucompliance::LEGAL_NOTICE          => $this->l('Legal notice', 'advancedeucompliance'),
            Advancedeucompliance::LEGAL_CONDITIONS      => $this->l('Terms of Service (ToS)', 'advancedeucompliance'),
            Advancedeucompliance::LEGAL_REVOCATION      => $this->l('Revocation terms', 'advancedeucompliance'),
            Advancedeucompliance::LEGAL_REVOCATION_FORM => $this->l('Revocation form', 'advancedeucompliance'),
            Advancedeucompliance::LEGAL_PRIVACY         => $this->l('Privacy', 'advancedeucompliance'),
            Advancedeucompliance::LEGAL_ENVIRONMENTAL   => $this->l('Environmental notice', 'advancedeucompliance'),
            Advancedeucompliance::LEGAL_SHIP_PAY        => $this->l('Shipping and payment', 'advancedeucompliance'),
        ];
    }

    /**
     * Install general hooks
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function installHooks()
    {
        $hooks = [
            'displayBeforeShoppingCartBlock' => [
                'name'        => 'display before Shopping cart block',
                'description' => 'Display content after Shopping Cart',
            ],
            'displayAfterShoppingCartBlock'  => [
                'name'        => 'display after Shopping cart block',
                'description' => 'Display content after Shopping Cart',
            ],
            'displayPaymentEu'               => [
                'name'        => 'Display EU payment options (helper)',
                'description' => 'Hook to display payment options',
            ],
        ];

        $return = true;

        foreach ($hooks as $hookName => $hook) {
            if (Hook::getIdByName($hookName)) {
                continue;
            }

            $newHook = new Hook();
            $newHook->name = $hookName;
            $newHook->title = $hookName;
            $newHook->description = $hook['description'];
            $newHook->position = true;
            $newHook->live_edit = false;

            if (!$newHook->add()) {
                $return &= false;
                $this->errors[] = $this->l('Could not install new hook', 'advancedeucompliance').': '.$hookName;
            }

        }

        return $return;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function registerModulesBackwardCompatHook()
    {
        $return = true;
        $modulesToCheck = [
            'bankwire',
            'cheque',
            'paypal',
            'adyen',
            'hipay',
            'cashondelivery',
            'sofortbanking',
            'pigmbhpaymill',
            'ogone',
            'moneybookers',
            'syspay',
        ];
        $displayPaymentEuHookId = (int) Hook::getIdByName('displayPaymentEu');
        $alreadyHookedModulesIds = array_keys(Hook::getModulesFromHook($displayPaymentEuHookId));

        foreach ($modulesToCheck as $moduleName) {
            if (($module = Module::getInstanceByName($moduleName)) !== false &&
                Module::isInstalled($moduleName) &&
                $module->active &&
                !in_array($module->id, $alreadyHookedModulesIds) &&
                !$module->isRegisteredInHook('displayPaymentEu')
            ) {
                $return &= $module->registerHook('displayPaymentEu');
            }
        }

        return $return;
    }

    /**
     * Create config
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function createConfig()
    {
        $deliveryTimeAvailableValues = [];
        $deliveryTimeOosValues = [];
        $shoppingCartTextBefore = [];
        $shoppingCartTextAfter = [];

        $langsRepository = $this->entityManager->getRepository('Language');
        $langs = $langsRepository->findAll();

        foreach ($langs as $lang) {
            $deliveryTimeAvailableValues[(int) $lang->id] = $this->l('Delivery: 1 to 3 weeks', 'advancedeucompliance');
            $deliveryTimeOosValues[(int) $lang->id] = $this->l('Delivery: 3 to 6 weeks', 'advancedeucompliance');
            $shoppingCartTextBefore[(int) $lang->id] = '';
            $shoppingCartTextAfter[(int) $lang->id] = '';
        }

        /* Base settings */
        $this->processAeucFeatTellAFriend(true);
        $this->processAeucFeatReorder(true);
        $this->processAeucFeatAdvPaymentApi(false);
        $this->processAeucLabelRevocationTOS(false);
        $this->processAeucLabelRevocationVP(false);
        $this->processAeucLabelSpecificPrice(true);
        $this->processAeucLabelTaxIncExc(true);
        $this->processAeucLabelShippingIncExc(false);
        $this->processAeucLabelWeight(true);
        $this->processAeucLabelCombinationFrom(true);

        $isThemeCompliant = $this->isThemeCompliant();

        $psWeightPrecisionInstalled = Configuration::get('PS_PRODUCT_WEIGHT_PRECISION') ?
            (int) Configuration::get('PS_PRODUCT_WEIGHT_PRECISION') :
            Advancedeucompliance::DEFAULT_PS_PRODUCT_WEIGHT_PRECISION;

        return Configuration::updateValue('AEUC_FEAT_TELL_A_FRIEND', false) &&
            Configuration::updateValue('AEUC_FEAT_ADV_PAYMENT_API', false) &&
            Configuration::updateValue('AEUC_LABEL_DELIVERY_TIME_AVAILABLE', $deliveryTimeAvailableValues) &&
            Configuration::updateValue('AEUC_LABEL_DELIVERY_TIME_OOS', $deliveryTimeOosValues) &&
            Configuration::updateValue('AEUC_LABEL_SPECIFIC_PRICE', true) &&
            Configuration::updateValue('AEUC_LABEL_TAX_INC_EXC', true) &&
            Configuration::updateValue('AEUC_LABEL_WEIGHT', true) &&
            Configuration::updateValue('AEUC_LABEL_REVOCATION_TOS', false) &&
            Configuration::updateValue('AEUC_LABEL_REVOCATION_VP', true) &&
            Configuration::updateValue('AEUC_LABEL_SHIPPING_INC_EXC', false) &&
            Configuration::updateValue('AEUC_LABEL_COMBINATION_FROM', true) &&
            Configuration::updateValue('AEUC_SHOPPING_CART_TEXT_BEFORE', $shoppingCartTextBefore) &&
            Configuration::updateValue('AEUC_SHOPPING_CART_TEXT_AFTER', $shoppingCartTextAfter) &&
            Configuration::updateValue('AEUC_IS_THEME_COMPLIANT', (bool) $isThemeCompliant) &&
            Configuration::updateValue('PS_PRODUCT_WEIGHT_PRECISION', (int) $psWeightPrecisionInstalled);
    }

    /**
     * @param bool $isOptionActive
     *
     * @since 1.0.0
     */
    protected function processAeucFeatTellAFriend($isOptionActive)
    {
        $stafModule = Module::getInstanceByName('sendtoafriend');
        if ($stafModule) {
            if ((bool) $isOptionActive) {
                Configuration::updateValue('AEUC_FEAT_TELL_A_FRIEND', true);
                if ($stafModule->isEnabledForShopContext() === false) {
                    $stafModule->enable();
                }
            } elseif (!(bool) $isOptionActive) {
                Configuration::updateValue('AEUC_FEAT_TELL_A_FRIEND', false);
                if ($stafModule->isEnabledForShopContext() === true) {
                    $stafModule->disable();
                }
            }
        }
    }

    /**
     * @param $isOptionActive
     *
     * @since 1.0.0
     */
    protected function processAeucFeatReorder($isOptionActive)
    {

        if ((bool) $isOptionActive) {
            Configuration::updateValue('PS_DISALLOW_HISTORY_REORDERING', false);
        } else {
            Configuration::updateValue('PS_DISALLOW_HISTORY_REORDERING', true);
        }
    }

    /**
     * @param bool $isOptionActive
     *
     * @since 1.0.0
     */
    protected function processAeucFeatAdvPaymentApi($isOptionActive)
    {
        $this->refreshThemeStatus();

        if ((bool) $isOptionActive) {
            if ((bool) Configuration::get('AEUC_IS_THEME_COMPLIANT')) {
                Configuration::updateValue('PS_ADVANCED_PAYMENT_API', true);
                Configuration::updateValue('AEUC_FEAT_ADV_PAYMENT_API', true);
            } else {
                $this->errors[] = $this->l(
                    'It is not possible to enable the "Advanced Checkout Page" as your theme is not compatible with this option.',
                    'advancedeucompliance'
                );
            }
        } else {
            Configuration::updateValue('PS_ADVANCED_PAYMENT_API', false);
            Configuration::updateValue('AEUC_FEAT_ADV_PAYMENT_API', false);
        }
    }

    /**
     * @since 1.0.0
     */
    private function refreshThemeStatus()
    {
        if ((bool) Configuration::get('AEUC_IS_THEME_COMPLIANT') === false) {
            $reCheck = $this->isThemeCompliant();
            if ($reCheck === true) {
                Configuration::updateValue('AEUC_IS_THEME_COMPLIANT', (bool) $reCheck);
            }
        }
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function isThemeCompliant()
    {
        $return = true;

        foreach ($this->getRequiredThemeTemplate() as $requiredTpl) {
            if (!is_file(_PS_THEME_DIR_.$requiredTpl)) {
                $this->missingTemplates[] = $requiredTpl;
                $return = false;
            }
        }

        return $return;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getRequiredThemeTemplate()
    {
        return [
            'order-address-advanced.tpl',
            'order-carrier-advanced.tpl',
            'order-carrier-opc-advanced.tpl',
            'order-opc-advanced.tpl',
            'order-opc-new-account-advanced.tpl',
            'order-payment-advanced.tpl',
            'shopping-cart-advanced.tpl',
        ];
    }

    /**
     * @param bool $isOptionActive
     *
     * @since 1.0.0
     */
    protected function processAeucLabelRevocationTOS($isOptionActive)
    {
        // Check first if LEGAL_REVOCATION CMS Role has been set before doing anything here
        $cmsRoleRepository = $this->entityManager->getRepository('CMSRole');
        $cmsPageAssociated = $cmsRoleRepository->findOneByName(Advancedeucompliance::LEGAL_REVOCATION);
        $cmsRoles = $this->getCMSRoles();

        if ((bool) $isOptionActive) {
            if (!$cmsPageAssociated instanceof CMSRole || (int) $cmsPageAssociated->id_cms == 0) {
                $this->errors[] =
                    sprintf(
                        $this->l(
                            '\'Revocation Terms within ToS\' label cannot be activated unless you associate "%s" role with a CMS Page.',
                            'advancedeucompliance'
                        ),
                        (string) $cmsRoles[Advancedeucompliance::LEGAL_REVOCATION]
                    );

                return;
            }
            Configuration::updateValue('AEUC_LABEL_REVOCATION_TOS', true);
        } else {
            Configuration::updateValue('AEUC_LABEL_REVOCATION_TOS', false);
        }
    }

    /**
     * This hook is present to maintain backward compatibility
     *
     * @param $isOptionActive
     *
     * @since 1.0.0
     */
    protected function processAeucLabelRevocationVP($isOptionActive)
    {
        if ((bool) $isOptionActive) {
            Configuration::updateValue('AEUC_LABEL_REVOCATION_VP', true);
        } else {
            Configuration::updateValue('AEUC_LABEL_REVOCATION_VP', false);
        }
    }

    /**
     * @param $isOptionActive
     *
     * @since 1.0.0
     */
    protected function processAeucLabelSpecificPrice($isOptionActive)
    {
        if ((bool) $isOptionActive) {
            Configuration::updateValue('AEUC_LABEL_SPECIFIC_PRICE', true);
        } else {
            Configuration::updateValue('AEUC_LABEL_SPECIFIC_PRICE', false);
        }
    }

    /**
     * @param bool $isOptionActive
     *
     * @since 1.0.0
     */
    protected function processAeucLabelTaxIncExc($isOptionActive)
    {
        Configuration::updateValue('AEUC_LABEL_TAX_INC_EXC', (bool) $isOptionActive);
    }

    /**
     * @param bool $isOptionActive
     *
     * @since 1.0.0
     */
    protected function processAeucLabelShippingIncExc($isOptionActive)
    {
        // Check first if LEGAL_SHIP_PAY CMS Role has been set before doing anything here
        $cmsRoleRepository = $this->entityManager->getRepository('CMSRole');
        $cmsPageAssociated = $cmsRoleRepository->findOneByName(Advancedeucompliance::LEGAL_SHIP_PAY);
        $cmsRoles = $this->getCMSRoles();

        if ((bool) $isOptionActive) {
            if (!$cmsPageAssociated instanceof CMSRole || (int) $cmsPageAssociated->id_cms === 0) {
                $this->errors[] =
                    sprintf(
                        $this->l(
                            'Shipping fees label cannot be activated unless you associate "%s" role with a CMS Page',
                            'advancedeucompliance'
                        ),
                        (string) $cmsRoles[Advancedeucompliance::LEGAL_SHIP_PAY]
                    );

                return;
            }
            Configuration::updateValue('AEUC_LABEL_SHIPPING_INC_EXC', true);
        } else {
            Configuration::updateValue('AEUC_LABEL_SHIPPING_INC_EXC', false);
        }

    }

    /**
     * @param bool $isOptionActive
     *
     * @since 1.0.0
     */
    protected function processAeucLabelWeight($isOptionActive)
    {
        if ((bool) $isOptionActive) {
            Configuration::updateValue('PS_DISPLAY_PRODUCT_WEIGHT', true);
            Configuration::updateValue('AEUC_LABEL_WEIGHT', true);
        } elseif (!(bool) $isOptionActive) {
            Configuration::updateValue('PS_DISPLAY_PRODUCT_WEIGHT', false);
            Configuration::updateValue('AEUC_LABEL_WEIGHT', false);
        }
    }

    /**
     * @param bool $isOptionActive
     *
     * @since 1.0.0
     */
    protected function processAeucLabelCombinationFrom($isOptionActive)
    {
        if ((bool) $isOptionActive) {
            Configuration::updateValue('AEUC_LABEL_COMBINATION_FROM', true);
        } else {
            Configuration::updateValue('AEUC_LABEL_COMBINATION_FROM', false);
        }
    }

    /**
     * @since 1.0.0
     */
    protected function emptyTemplatesCache()
    {
        $this->_clearCache('product.tpl');
        $this->_clearCache('product-list.tpl');
    }

    /**
     * Uninstall this module
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function uninstall()
    {
        return parent::uninstall() &&
            $this->dropConfig() &&
            $this->uninstallTables();
    }

    /**
     * Drop config
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function dropConfig()
    {
        // Remove roles
        $rolesArray = $this->getCMSRoles();
        $roles = array_keys($rolesArray);
        $cmsRoleRepository = $this->entityManager->getRepository('CMSRole');
        $cleaned = true;

        foreach ($roles as $role) {
            /** @var CMSRole $cmsRoleTmp */
            $cmsRoleTmp = $cmsRoleRepository->findOneByName($role);
            if ($cmsRoleTmp) {
                $cleaned &= $cmsRoleTmp->delete();
            }
        }

        return Configuration::deleteByName('AEUC_FEAT_TELL_A_FRIEND') &&
            Configuration::deleteByName('AEUC_FEAT_ADV_PAYMENT_API') &&
            Configuration::deleteByName('AEUC_LABEL_DELIVERY_TIME_AVAILABLE') &&
            Configuration::deleteByName('AEUC_LABEL_DELIVERY_TIME_OOS') &&
            Configuration::deleteByName('AEUC_LABEL_SPECIFIC_PRICE') &&
            Configuration::deleteByName('AEUC_LABEL_TAX_INC_EXC') &&
            Configuration::deleteByName('AEUC_LABEL_WEIGHT') &&
            Configuration::deleteByName('AEUC_LABEL_REVOCATION_TOS') &&
            Configuration::deleteByName('AEUC_LABEL_REVOCATION_VP') &&
            Configuration::deleteByName('AEUC_LABEL_SHIPPING_INC_EXC') &&
            Configuration::deleteByName('AEUC_LABEL_COMBINATION_FROM') &&
            Configuration::deleteByName('AEUC_SHOPPING_CART_TEXT_BEFORE') &&
            Configuration::deleteByName('AEUC_SHOPPING_CART_TEXT_AFTER') &&
            Configuration::deleteByName('AEUC_IS_THEME_COMPLIANT') &&
            Configuration::updateValue('PS_ADVANCED_PAYMENT_API', false) &&
            Configuration::updateValue('PS_ATCP_SHIPWRAP', false);
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function uninstallTables()
    {
        $state = true;
        $sql = require dirname(__FILE__).'/install/sql_install.php';
        foreach ($sql as $name => $v) {
            $state &= Db::getInstance()->execute('DROP TABLE IF EXISTS '.$name);
        }

        return $state;
    }

    /**
     * @param bool $forceAll
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function disable($forceAll = false)
    {
        $isAdvancedApiDisabled = (bool) Configuration::updateValue('PS_ADVANCED_PAYMENT_API', false);
        $isAdvancedApiDisabled &= (bool) Configuration::updateValue('PS_ATCP_SHIPWRAP', false);

        return parent::disable() && $isAdvancedApiDisabled;
    }

    /**
     * @param array $param
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function hookDisplayCartTotalPriceLabel($param)
    {
        $smartyVars = [];
        if (Configuration::get('AEUC_LABEL_TAX_INC_EXC')) {
            $customerDefaultGroupId = (int) $this->context->customer->id_default_group;
            $customerDefaultGroup = new Group($customerDefaultGroupId);

            if ((bool) Configuration::get('PS_TAX') === true && $this->context->country->display_tax_label &&
                !(Validate::isLoadedObject($customerDefaultGroup) && (bool) $customerDefaultGroup->price_display_method === true)
            ) {
                $smartyVars['price']['tax_str_i18n'] = $this->l('Tax included', 'advancedeucompliance');
            } else {
                $smartyVars['price']['tax_str_i18n'] = $this->l('Tax excluded', 'advancedeucompliance');
            }
        }

        if (isset($param['from'])) {
            if ($param['from'] == 'shopping_cart') {
                $smartyVars['css_class'] = 'aeuc_tax_label_shopping_cart';
            }
            if ($param['from'] == 'blockcart') {
                $smartyVars['css_class'] = 'aeuc_tax_label_blockcart';
            }
        }

        $this->context->smarty->assign(['smartyVars' => $smartyVars]);

        return $this->display(__FILE__, 'displayCartTotalPriceLabel.tpl');
    }

    /**
     * @return array|null
     *
     * @since 1.0.0
     */
    public function hookAdvancedPaymentOptions()
    {
        $legacyOptions = Hook::exec('displayPaymentEU', [], null, true);
        $newOptions = [];

        Media::addJsDef(
            [
                'aeuc_tos_err_str' => Tools::htmlentitiesUTF8(
                    $this->l(
                        'You must agree to our Terms of Service before going any further!',
                        'advancedeucompliance'
                    )
                ),
            ]
        );
        Media::addJsDef(
            [
                'aeuc_submit_err_str' => Tools::htmlentitiesUTF8(
                    $this->l(
                        'Something went wrong. If the problem persists, please contact us.',
                        'advancedeucompliance'
                    )
                ),
            ]
        );
        Media::addJsDef(
            [
                'aeuc_no_pay_err_str' => Tools::htmlentitiesUTF8(
                    $this->l(
                        'Select a payment option first.',
                        'advancedeucompliance'
                    )
                ),
            ]
        );
        Media::addJsDef(
            [
                'aeuc_virt_prod_err_str' => Tools::htmlentitiesUTF8(
                    $this->l(
                        'Please check "Revocation of virtual products" box first !',
                        'advancedeucompliance'
                    )
                ),
            ]
        );
        if ($legacyOptions) {
            foreach ($legacyOptions as $moduleName => $legacyOption) {
                if (!$legacyOption) {
                    continue;
                }

                foreach (Core_Business_Payment_PaymentOption::convertLegacyOption($legacyOption) as $option) {
                    $option->setModuleName($moduleName);
                    $toBeCleaned = $option->getForm();
                    if ($toBeCleaned) {
                        $cleaned = str_replace('@hiddenSubmit', '', $toBeCleaned);
                        $option->setForm($cleaned);
                    }
                    $newOptions[] = $option;
                }
            }

            return $newOptions;
        }

        return null;
    }

    /**
     * @param array $param
     *
     * @since 1.0.0
     */
    public function hookActionEmailAddAfterContent($param)
    {
        if (!isset($param['template']) || !isset($param['template_html']) || !isset($param['template_txt'])) {
            return;
        }

        $tplName = (string) $param['template'];
        $tplNameExploded = explode('.', $tplName);
        if (is_array($tplNameExploded)) {
            $tplName = (string) $tplNameExploded[0];
        }

        $idLang = (int) $param['id_lang'];
        $mailId = AeucEmailEntity::getMailIdFromTplFilename($tplName);
        if (!isset($mailId['id_mail'])) {
            return;
        }

        $mailId = (int) $mailId['id_mail'];
        $cmsRoleIds = AeucCMSRoleEmailEntity::getCMSRoleIdsFromIdMail($mailId);
        if (!$cmsRoleIds) {
            return;
        }

        $tmpCmsRoleList = [];
        foreach ($cmsRoleIds as $cmsRoleId) {
            $tmpCmsRoleList[] = $cmsRoleId['id_cms_role'];
        }

        $cmsRoleRepository = $this->entityManager->getRepository('CMSRole');
        $cmsRoles = $cmsRoleRepository->findByIdCmsRole($tmpCmsRoleList);
        if (!$cmsRoles) {
            return;
        }

        $cmsRepo = $this->entityManager->getRepository('CMS');
        $cmsContents = [];

        foreach ($cmsRoles as $cmsRole) {
            $cmsPage = $cmsRepo->i10nFindOneById((int) $cmsRole->id_cms, $idLang, $this->context->shop->id);

            if (!isset($cmsPage->content)) {
                continue;
            }

            $cmsContents[] = $cmsPage->content;
            $param['template_txt'] .= strip_tags($cmsPage->content, true);
        }

        $this->context->smarty->assign(['cms_contents' => $cmsContents]);
        $param['template_html'] .= $this->display(__FILE__, 'hook-email-wrapper.tpl');

    }

    /**
     * @since 1.0.0
     */
    public function hookHeader()
    {
        $cssRequired = [
            'index',
            'product',
            'order',
            'order-opc',
            'category',
            'products-comparison',

        ];

        if (isset($this->context->controller->php_self) && in_array($this->context->controller->php_self, $cssRequired)) {
            $this->context->controller->addCSS($this->_path.'views/css/aeuc_front.css', 'all');
        }

    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function hookOverrideTOSDisplay()
    {
        $hasTosOverrideOpt = (bool) Configuration::get('AEUC_LABEL_REVOCATION_TOS');
        $cmsRepository = $this->entityManager->getRepository('CMS');

        if (!$cmsRepository instanceof Core_Business_CMS_CMSRepository) {
            return '';
        }
        // Check first if LEGAL_REVOCATION CMS Role is set
        $cmsRoleRepository = $this->entityManager->getRepository('CMSRole');
        $cmsPageAssociated = $cmsRoleRepository->findOneByName(Advancedeucompliance::LEGAL_REVOCATION);

        // Check if cart has virtual product
        $hasVirtualProduct = (bool) Configuration::get('AEUC_LABEL_REVOCATION_VP') && $this->hasCartVirtualProduct($this->context->cart);
        Media::addJsDef(
            [
                'aeuc_has_virtual_products' => (bool) $hasVirtualProduct,
                'aeuc_virt_prod_err_str'    => Tools::htmlentitiesUTF8(
                    $this->l(
                        'Please check "Revocation of virtual products" box first !',
                        'advancedeucompliance'
                    )
                ),
            ]
        );
        if ($hasTosOverrideOpt || (bool) Configuration::get('AEUC_LABEL_REVOCATION_VP')) {
            $this->context->controller->addJS($this->_path.'views/js/fo_aeuc_tnc.js', true);
        }

        $linkRevocations = '';

        // Get IDs of CMS pages required
        $cmsConditionsId = (int) Configuration::get('PS_CONDITIONS_CMS_ID');
        $cmsRevocationId = (int) $cmsPageAssociated->id_cms;

        // Get misc vars
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $isSslEnabled = (bool) Configuration::get('PS_SSL_ENABLED');
        $checkedTos = $this->context->cart->checkedTos ? true : false;

        // Get CMS OBJs
        $cmsConditions = $cmsRepository->i10nFindOneById($cmsConditionsId, $idLang, $idShop);
        if (!Validate::isLoadedObject($cmsConditions)) {
            return '';
        }
        $linkConditions = $this->context->link->getCMSLink($cmsConditions, $cmsConditions->link_rewrite, $isSslEnabled);

        if (!strpos($linkConditions, '?')) {
            $linkConditions .= '?content_only=1';
        } else {
            $linkConditions .= '&content_only=1';
        }

        if ($hasTosOverrideOpt === true) {
            $cmsRevocations = $cmsRepository->i10nFindOneById($cmsRevocationId, $idLang, $idShop);
            // Get links to revocation page
            $linkRevocations = $this->context->link->getCMSLink($cmsRevocations, $cmsRevocations->link_rewrite, $isSslEnabled);

            if (!strpos($linkRevocations, '?')) {
                $linkRevocations .= '?content_only=1';
            } else {
                $linkRevocations .= '&content_only=1';
            }
        }

        $this->context->smarty->assign(
            [
                'has_tos_override_opt' => $hasTosOverrideOpt,
                'checkedTOS'           => $checkedTos,
                'link_conditions'      => $linkConditions,
                'link_revocations'     => $linkRevocations,
                'has_virtual_product'  => $hasVirtualProduct,
            ]
        );

        return $this->display(__FILE__, 'hookOverrideTOSDisplay.tpl');
    }

    private function hasCartVirtualProduct(Cart $cart)
    {
        $products = $cart->getProducts();

        if (!count($products)) {
            return false;
        }

        foreach ($products as $product) {
            if ($product['is_virtual']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function hookDisplayBeforeShoppingCartBlock()
    {
        if ($this->context->controller instanceof OrderOpcController || property_exists($this->context->controller, 'step') && $this->context->controller->step == 3) {
            $cartText = Configuration::get('AEUC_SHOPPING_CART_TEXT_BEFORE', $this->context->language->id);

            if ($cartText) {
                $this->context->smarty->assign('cart_text', $cartText);

                return $this->display(__FILE__, 'displayShoppingCartBeforeBlock.tpl');
            }
        }

        return '';
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayAfterShoppingCartBlock($params)
    {
        $cartText = Configuration::get('AEUC_SHOPPING_CART_TEXT_AFTER', Context::getContext()->language->id);
        if ($cartText && isset($params['colspan_total'])) {
            $this->context->smarty->assign(
                [
                    'cart_text'     => $cartText,
                    'colspan_total' => (int) $params['colspan_total'],
                ]
            );

            return $this->display(__FILE__, 'displayShoppingCartAfterBlock.tpl');
        }

        return '';
    }

    /**
     * @param array $param
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function hookDisplayProductPriceBlock($param)
    {
        if (!isset($param['product']) || !isset($param['type'])) {
            return;
        }

        $product = $param['product'];

        if (is_array($product)) {
            $productRepository = $this->entityManager->getRepository('Product');
            $product = $productRepository->findOne((int) $product['id_product']);
        }
        if (!Validate::isLoadedObject($product)) {
            return;
        }

        $smartyVars = [];

        /* Handle Product Combinations label */
        if ($param['type'] == 'before_price' && (bool) Configuration::get('AEUC_LABEL_COMBINATION_FROM') === true) {
            if ($product->hasAttributes()) {
                $needDisplay = false;
                $combinations = $product->getAttributeCombinations($this->context->language->id);
                if ($combinations && is_array($combinations)) {
                    foreach ($combinations as $combination) {
                        if ((float) $combination['price'] > 0) {
                            $needDisplay = true;
                            break;
                        }
                    }

                    unset($combinations);

                    if ($needDisplay) {
                        $smartyVars['before_price'] = [];
                        $smartyVars['before_price']['from_str_i18n'] = $this->l('From', 'advancedeucompliance');

                        return $this->dumpHookDisplayProductPriceBlock($smartyVars);
                    }
                }

                return '';
            }
        }

        /* Handle Specific Price label*/
        if ($param['type'] == 'old_price' && (bool) Configuration::get('AEUC_LABEL_SPECIFIC_PRICE') === true) {
            $smartyVars['old_price'] = [];
            $smartyVars['old_price']['before_str_i18n'] = $this->l('Before', 'advancedeucompliance');

            return $this->dumpHookDisplayProductPriceBlock($smartyVars);
        }

        /* Handle taxes  Inc./Exc. and Shipping Inc./Exc.*/
        if ($param['type'] == 'price') {
            $smartyVars['price'] = [];
            $needShippingLabel = true;

            if (Configuration::get('AEUC_LABEL_TAX_INC_EXC')) {
                $customerDefaultGroupId = (int) $this->context->customer->id_default_group;
                $customerDefaultGroup = new Group($customerDefaultGroupId);

                if ((bool) Configuration::get('PS_TAX') === true && $this->context->country->display_tax_label &&
                    !(Validate::isLoadedObject($customerDefaultGroup) && (bool) $customerDefaultGroup->price_display_method === true)
                ) {
                    $smartyVars['price']['tax_str_i18n'] = $this->l('Tax included', 'advancedeucompliance');
                } else {
                    $smartyVars['price']['tax_str_i18n'] = $this->l('Tax excluded', 'advancedeucompliance');
                }

                if (isset($param['from']) && $param['from'] == 'blockcart') {
                    $smartyVars['price']['css_class'] = 'aeuc_tax_label_blockcart';
                    $needShippingLabel = false;
                }
            }
            if ((bool) Configuration::get('AEUC_LABEL_SHIPPING_INC_EXC') === true && $needShippingLabel === true) {
                if (!$product->is_virtual) {
                    $cmsRoleRepository = $this->entityManager->getRepository('CMSRole');
                    $cmsRepository = $this->entityManager->getRepository('CMS');
                    $cmsPageAssociated = $cmsRoleRepository->findOneByName(Advancedeucompliance::LEGAL_SHIP_PAY);

                    if (isset($cmsPageAssociated->id_cms) && $cmsPageAssociated->id_cms != 0) {
                        $cmsShipPayId = (int) $cmsPageAssociated->id_cms;
                        $cmsRevocations = $cmsRepository->i10nFindOneById(
                            $cmsShipPayId,
                            $this->context->language->id,
                            $this->context->shop->id
                        );
                        $isSslEnabled = (bool) Configuration::get('PS_SSL_ENABLED');
                        $linkShipPay = $this->context->link->getCMSLink($cmsRevocations, $cmsRevocations->link_rewrite, $isSslEnabled);

                        if (!strpos($linkShipPay, '?')) {
                            $linkShipPay .= '?content_only=1';
                        } else {
                            $linkShipPay .= '&content_only=1';
                        }

                        $smartyVars['ship'] = [];
                        $smartyVars['ship']['link_ship_pay'] = $linkShipPay;
                        $smartyVars['ship']['ship_str_i18n'] = $this->l('Shipping excluded', 'advancedeucompliance');
                    }
                }
            }

            return $this->dumpHookDisplayProductPriceBlock($smartyVars);
        }

        /* Handles product's weight */
        if ($param['type'] == 'weight' && (bool) Configuration::get('PS_DISPLAY_PRODUCT_WEIGHT') === true &&
            isset($param['hook_origin']) && $param['hook_origin'] == 'product_sheet'
        ) {
            if ((float) $product->weight) {
                $smartyVars['weight'] = [];
                $roundedWeight = round((float) $product->weight, Configuration::get('PS_PRODUCT_WEIGHT_PRECISION'));
                $smartyVars['weight']['rounded_weight_str_i18n'] =
                    $roundedWeight.' '.Configuration::get('PS_WEIGHT_UNIT');

                return $this->dumpHookDisplayProductPriceBlock($smartyVars);
            }
        }

        /* Handle Estimated delivery time label */
        if ($param['type'] == 'after_price' && !$product->is_virtual) {
            $contextIdLang = $this->context->language->id;
            $isProductAvailable = (StockAvailable::getQuantityAvailableByProduct($product->id) >= 1 ? true : false);
            $smartyVars['after_price'] = [];

            if ($isProductAvailable) {
                $contextualizedContent =
                    Configuration::get('AEUC_LABEL_DELIVERY_TIME_AVAILABLE', (int) $contextIdLang);
                $smartyVars['after_price']['delivery_str_i18n'] = $contextualizedContent;
            } else {
                $contextualizedContent = Configuration::get('AEUC_LABEL_DELIVERY_TIME_OOS', (int) $contextIdLang);
                $smartyVars['after_price']['delivery_str_i18n'] = $contextualizedContent;
            }

            return $this->dumpHookDisplayProductPriceBlock($smartyVars);
        }

        return '';
    }

    /**
     * @param array $smartyVars
     *
     * @return string
     *
     * @since 1.0.0
     */
    private function dumpHookDisplayProductPriceBlock(array $smartyVars)
    {
        $this->context->smarty->assign(['smartyVars' => $smartyVars]);
        $this->context->controller->addJS($this->_path.'views/js/fo_aeuc_tnc.js', true);

        return $this->display(__FILE__, 'hookDisplayProductPriceBlock.tpl');
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $themeWarning = null;
        $this->refreshThemeStatus();
        $successBand = $this->_postProcess();
        if ((bool) Configuration::get('AEUC_IS_THEME_COMPLIANT') === false) {
            $missing = '<ul>';
            foreach ($this->missingTemplates as $missingTpl) {
                $missing .= '<li>'.$missingTpl.' '.$this->l('missing').'</li>';
            }
            $missing .= '</ul><br/>';
            $discardWarningLink = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name.'&discard_tpl_warn=1'.'&token='.Tools::getAdminTokenLite('AdminModules');
            $missing .= '<a href="'.$discardWarningLink.'" type="button">'.$this->l('Hide this, I know what I am doing.', 'advancedeucompliance').'</a>';
            $themeWarning = $this->displayWarning(
                $this->l(
                    'It seems that your current theme is not compatible with this module, some mandatory templates are missing. It is possible some options may not work as expected.',
                    'advancedeucompliance'
                ).$missing
            );

        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('errors', $this->errors);
        $this->context->controller->addCSS($this->_path.'views/css/configure.css', 'all');
        // Render all required form for each 'part'
        $formLabelsManager = $this->renderFormLabelsManager();
        $formFeaturesManager = $this->renderFormFeaturesManager();
        $formLegalContentManager = $this->renderFormLegalContentManager();
        $formEmailAttachmentsManager = $this->renderFormEmailAttachmentsManager();

        return $themeWarning.$successBand.$formLabelsManager.$formFeaturesManager.$formLegalContentManager.$formEmailAttachmentsManager;
    }

    /**
     * Save form data.
     */
    protected function _postProcess()
    {
        $hasProcessedSomething = false;

        $postKeysSwitchable = array_keys(array_merge($this->getConfigFormLabelsManagerValues(), $this->getConfigFormFeaturesManagerValues()));

        $postKeysComplex = [
            'AEUC_legalContentManager',
            'AEUC_emailAttachmentsManager',
            'PS_PRODUCT_WEIGHT_PRECISION',
            'discard_tpl_warn',
            'PS_ATCP_SHIPWRAP_ROUNDING',
        ];

        $i10NInputsReceived = [];
        $receivedValues = Tools::getAllValues();

        foreach (array_keys($receivedValues) as $keyReceived) {
            /* Case its one of form with only switches in it */
            if (in_array($keyReceived, $postKeysSwitchable)) {
                $isOptionActive = Tools::getValue($keyReceived);
                $key = Tools::strtolower($keyReceived);
                $key = Tools::toCamelCase($key);

                if (method_exists($this, 'process'.$key)) {
                    $this->{'process'.$key}($isOptionActive);
                    $hasProcessedSomething = true;
                }
                continue;
            }
            /* Case we are on more complex forms */
            if (in_array($keyReceived, $postKeysComplex)) {
                // Clean key
                $key = Tools::strtolower($keyReceived);
                $key = Tools::toCamelCase($key, true);

                if (method_exists($this, 'process'.$key)) {
                    $this->{'process'.$key}();
                    $hasProcessedSomething = true;
                }
            }

            /* Case Multi-lang input */
            if (strripos($keyReceived, 'AEUC_LABEL_DELIVERY_TIME_AVAILABLE') !== false) {
                $exploded = explode('_', $keyReceived);
                $count = count($exploded);
                $idLang = (int) $exploded[$count - 1];
                $i10NInputsReceived['AEUC_LABEL_DELIVERY_TIME_AVAILABLE'][$idLang] = $receivedValues[$keyReceived];
            }
            if (strripos($keyReceived, 'AEUC_LABEL_DELIVERY_TIME_OOS') !== false) {
                $exploded = explode('_', $keyReceived);
                $count = count($exploded);
                $idLang = (int) $exploded[$count - 1];
                $i10NInputsReceived['AEUC_LABEL_DELIVERY_TIME_OOS'][$idLang] = $receivedValues[$keyReceived];
            }
            if (strripos($keyReceived, 'AEUC_SHOPPING_CART_TEXT_BEFORE') !== false) {
                $exploded = explode('_', $keyReceived);
                $count = count($exploded);
                $idLang = (int) $exploded[$count - 1];
                $i10NInputsReceived['AEUC_SHOPPING_CART_TEXT_BEFORE'][$idLang] = $receivedValues[$keyReceived];
            }
            if (strripos($keyReceived, 'AEUC_SHOPPING_CART_TEXT_AFTER') !== false) {
                $exploded = explode('_', $keyReceived);
                $count = count($exploded);
                $idLang = (int) $exploded[$count - 1];
                $i10NInputsReceived['AEUC_SHOPPING_CART_TEXT_AFTER'][$idLang] = $receivedValues[$keyReceived];
            }
        }

        if (Tools::isSubmit('PS_ATCP_SHIPWRAP_ROUNDING')) {
            Configuration::updateValue('PS_ATCP_SHIPWRAP_ROUNDING', (int) Tools::getValue('PS_ATCP_SHIPWRAP_ROUNDING'));
        }

        if (count($i10NInputsReceived) > 0) {
            $this->processAeucLabelDeliveryTime($i10NInputsReceived);
            $this->processAeucShoppingCartText($i10NInputsReceived);
            $hasProcessedSomething = true;
        }

        if ($hasProcessedSomething) {
            $this->emptyTemplatesCache();

            return (count($this->errors) ? $this->displayError($this->errors) : '').(count($this->warnings) ? $this->displayWarning($this->warnings) : '').$this->displayConfirmation($this->l('Settings saved successfully!', 'advancedeucompliance'));
        } else {
            return (count($this->errors) ? $this->displayError($this->errors) : '').(count($this->warnings) ? $this->displayWarning($this->warnings) : '').'';
        }
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormLabelsManagerValues()
    {
        $deliveryTimeAvailableValues = [];
        $deliveryTimeOosValues = [];
        $shoppingCartTextBeforeValues = [];
        $shoppingCartTextAfterValues = [];

        $langs = Language::getLanguages(false, false);

        foreach ($langs as $lang) {
            $tmpIdLang = (int) $lang['id_lang'];
            $deliveryTimeAvailableValues[$tmpIdLang] = Configuration::get('AEUC_LABEL_DELIVERY_TIME_AVAILABLE', $tmpIdLang);
            $deliveryTimeOosValues[$tmpIdLang] = Configuration::get('AEUC_LABEL_DELIVERY_TIME_OOS', $tmpIdLang);
            $shoppingCartTextBeforeValues[$tmpIdLang] = Configuration::get('AEUC_SHOPPING_CART_TEXT_BEFORE', $tmpIdLang);
            $shoppingCartTextAfterValues[$tmpIdLang] = Configuration::get('AEUC_SHOPPING_CART_TEXT_AFTER', $tmpIdLang);
        }

        return [
            'AEUC_LABEL_DELIVERY_TIME_AVAILABLE' => $deliveryTimeAvailableValues,
            'AEUC_LABEL_DELIVERY_TIME_OOS'       => $deliveryTimeOosValues,
            'AEUC_LABEL_SPECIFIC_PRICE'          => Configuration::get('AEUC_LABEL_SPECIFIC_PRICE'),
            'AEUC_LABEL_TAX_INC_EXC'             => Configuration::get('AEUC_LABEL_TAX_INC_EXC'),
            'AEUC_LABEL_WEIGHT'                  => Configuration::get('AEUC_LABEL_WEIGHT'),
            'AEUC_LABEL_REVOCATION_TOS'          => Configuration::get('AEUC_LABEL_REVOCATION_TOS'),
            'AEUC_LABEL_REVOCATION_VP'           => Configuration::get('AEUC_LABEL_REVOCATION_VP'),
            'AEUC_LABEL_SHIPPING_INC_EXC'        => Configuration::get('AEUC_LABEL_SHIPPING_INC_EXC'),
            'AEUC_LABEL_COMBINATION_FROM'        => Configuration::get('AEUC_LABEL_COMBINATION_FROM'),
            'AEUC_SHOPPING_CART_TEXT_BEFORE'     => $shoppingCartTextBeforeValues,
            'AEUC_SHOPPING_CART_TEXT_AFTER'      => $shoppingCartTextAfterValues,
            'PS_PRODUCT_WEIGHT_PRECISION'        => Configuration::get('PS_PRODUCT_WEIGHT_PRECISION'),
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormFeaturesManagerValues()
    {
        return [
            'AEUC_FEAT_TELL_A_FRIEND'   => Configuration::get('AEUC_FEAT_TELL_A_FRIEND'),
            'AEUC_FEAT_REORDER'         => !Configuration::get('PS_DISALLOW_HISTORY_REORDERING'),
            'AEUC_FEAT_ADV_PAYMENT_API' => Configuration::get('AEUC_FEAT_ADV_PAYMENT_API'),
            'PS_ATCP_SHIPWRAP'          => Configuration::get('PS_ATCP_SHIPWRAP'),
            'PS_ATCP_SHIPWRAP_ROUNDING' => Configuration::get('PS_ATCP_SHIPWRAP_ROUNDING'),
        ];
    }

    protected function processAeucLabelDeliveryTime(array $i10NInputs)
    {
        if (isset($i10NInputs['AEUC_LABEL_DELIVERY_TIME_AVAILABLE'])) {
            Configuration::updateValue('AEUC_LABEL_DELIVERY_TIME_AVAILABLE', $i10NInputs['AEUC_LABEL_DELIVERY_TIME_AVAILABLE']);
        }
        if (isset($i10NInputs['AEUC_LABEL_DELIVERY_TIME_OOS'])) {
            Configuration::updateValue('AEUC_LABEL_DELIVERY_TIME_OOS', $i10NInputs['AEUC_LABEL_DELIVERY_TIME_OOS']);
        }
    }

    protected function processAeucShoppingCartText(array $i10NInputs)
    {
        if (isset($i10NInputs['AEUC_SHOPPING_CART_TEXT_BEFORE'])) {
            Configuration::updateValue('AEUC_SHOPPING_CART_TEXT_BEFORE', $i10NInputs['AEUC_SHOPPING_CART_TEXT_BEFORE']);
        }
        if (isset($i10NInputs['AEUC_SHOPPING_CART_TEXT_AFTER'])) {
            Configuration::updateValue('AEUC_SHOPPING_CART_TEXT_AFTER', $i10NInputs['AEUC_SHOPPING_CART_TEXT_AFTER']);
        }
    }

    /**
     * Create the form that will let user choose all the wording options
     */
    protected function renderFormLabelsManager()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAEUC_labelsManager';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules');
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormLabelsManagerValues(),
            /* Add values for your inputs */
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigFormLabelsManager()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigFormLabelsManager()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Labels', 'advancedeucompliance'),
                    'icon'  => 'icon-tags',
                ],
                'input'  => [
                    [
                        'type'  => 'text',
                        'lang'  => true,
                        'label' => $this->l(
                            'Estimated delivery time label (available products)',
                            'advancedeucompliance'
                        ),
                        'name'  => 'AEUC_LABEL_DELIVERY_TIME_AVAILABLE',
                        'desc'  => $this->l(
                            'Indicate the estimated delivery time for your in-stock products. Leave the field empty to disable.',
                            'advancedeucompliance'
                        ),
                    ],
                    [
                        'type'  => 'text',
                        'lang'  => true,
                        'label' => $this->l(
                            'Estimated delivery time label (out-of-stock products)',
                            'advancedeucompliance'
                        ),
                        'name'  => 'AEUC_LABEL_DELIVERY_TIME_OOS',
                        'desc'  => $this->l(
                            'Indicate the estimated delivery time for your out-of-stock products. Leave the field empty to disable.',
                            'advancedeucompliance'
                        ),
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l(
                            '\'Before\' Base price label',
                            'advancedeucompliance'
                        ),
                        'name'    => 'AEUC_LABEL_SPECIFIC_PRICE',
                        'is_bool' => true,
                        'desc'    => $this->l(
                            'When a product is on sale, displays the base price with a \'Before\' label.',
                            'advancedeucompliance'
                        ),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->l(
                                    'Enabled',
                                    'advancedeucompliance'
                                ),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->l(
                                    'Disabled',
                                    'advancedeucompliance'
                                ),
                            ],
                        ],
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l(
                            'Tax \'inc./excl.\' label',
                            'advancedeucompliance'
                        ),
                        'name'    => 'AEUC_LABEL_TAX_INC_EXC',
                        'is_bool' => true,
                        'desc'    => $this->l(
                            'Display whether the tax is included next to the product price (\'Tax included/excluded\' label).',
                            'advancedeucompliance'
                        ),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->l(
                                    'Enabled',
                                    'advancedeucompliance'
                                ),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->l(
                                    'Disabled',
                                    'advancedeucompliance'
                                ),
                            ],
                        ],
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l(
                            'Shipping fees \'Inc./Excl.\' label',
                            'advancedeucompliance'
                        ),
                        'name'    => 'AEUC_LABEL_SHIPPING_INC_EXC',
                        'is_bool' => true,
                        'desc'    => $this->l(
                            'Display whether the shipping fees are included, next to the product price (\'Shipping included / excluded\').',
                            'advancedeucompliance'
                        ),
                        'hint'    => $this->l(
                            'If enabled, make sure the Shipping terms are associated with a CMS page below (Legal Content Management). The label will link to this content.',
                            'advancedeucompliance'
                        ),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->l(
                                    'Enabled',
                                    'advancedeucompliance'
                                ),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->l(
                                    'Disabled',
                                    'advancedeucompliance'
                                ),
                            ],
                        ],
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l(
                            'Product weight label',
                            'advancedeucompliance'
                        ),
                        'name'    => 'AEUC_LABEL_WEIGHT',
                        'is_bool' => true,

                        'desc'   => sprintf(
                            $this->l(
                                'Display the weight of a product (when information is available and product weighs more than 1 %s).',
                                'advancedeucompliance'
                            ),
                            Configuration::get('PS_WEIGHT_UNIT')
                        ),
                        'values' => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->l(
                                    'Enabled',
                                    'advancedeucompliance'
                                ),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->l(
                                    'Disabled',
                                    'advancedeucompliance'
                                ),
                            ],
                        ],
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l(
                            'Decimals for product weight',
                            'advancedeucompliance'
                        ),
                        'name'  => 'PS_PRODUCT_WEIGHT_PRECISION',
                        'desc'  => sprintf(
                            $this->l(
                                'Choose how many decimals to display for the product weight (e.g: 1 %s with 0 decimal, or 1.01 %s with 2 decimals)',
                                'advancedeucompliance'
                            ),
                            Configuration::get('PS_WEIGHT_UNIT'),
                            Configuration::get('PS_WEIGHT_UNIT')
                        ),
                        'hint'  => $this->l(
                            'This value must be positive.',
                            'advancedeucompliance'
                        ),
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l(
                            'Revocation Terms within ToS',
                            'advancedeucompliance'
                        ),
                        'name'    => 'AEUC_LABEL_REVOCATION_TOS',
                        'is_bool' => true,
                        'desc'    => $this->l(
                            'Include content from the Revocation Terms CMS page within the Terms of Services (ToS).',
                            'advancedeucompliance'
                        ),
                        'hint'    => $this->l(
                            'If enabled, make sure the Revocation Terms are associated with a CMS page below (Legal Content Management).',
                            'advancedeucompliance'
                        ),
                        'disable' => true,
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->l(
                                    'Enabled',
                                    'advancedeucompliance'
                                ),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->l(
                                    'Disabled',
                                    'advancedeucompliance'
                                ),
                            ],
                        ],
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l(
                            'Revocation for virtual products',
                            'advancedeucompliance'
                        ),
                        'name'    => 'AEUC_LABEL_REVOCATION_VP',
                        'is_bool' => true,
                        'desc'    => $this->l(
                            'Add a mandatory checkbox when the cart contains a virtual product. Use it to ensure customers are aware that a virtual product cannot be returned.',
                            'advancedeucompliance'
                        ),
                        'disable' => true,
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->l(
                                    'Enabled',
                                    'advancedeucompliance'
                                ),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->l(
                                    'Disabled',
                                    'advancedeucompliance'
                                ),
                            ],
                        ],
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('\'From\' price label (when combinations)'),
                        'name'    => 'AEUC_LABEL_COMBINATION_FROM',
                        'is_bool' => true,
                        'desc'    => $this->l(
                            'Display a \'From\' label before the price on products with combinations.',
                            'advancedeucompliance'
                        ),
                        'hint'    => $this->l(
                            'As prices can vary from a combination to another, this label indicates that the final price may be higher.',
                            'advancedeucompliance'
                        ),
                        'disable' => true,
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->l(
                                    'Enabled',
                                    'advancedeucompliance'
                                ),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->l(
                                    'Disabled',
                                    'advancedeucompliance'
                                ),
                            ],
                        ],
                    ],
                    [
                        'type'  => 'textarea',
                        'lang'  => true,
                        'label' => $this->l(
                            'Upper shopping cart text',
                            'advancedeucompliance'
                        ),
                        'name'  => 'AEUC_SHOPPING_CART_TEXT_BEFORE',
                        'desc'  => $this->l(
                            'Add a custom text above the shopping cart summary.',
                            'advancedeucompliance'
                        ),
                    ],
                    [
                        'type'  => 'textarea',
                        'lang'  => true,
                        'label' => $this->l(
                            'Lower shopping cart text',
                            'advancedeucompliance'
                        ),
                        'name'  => 'AEUC_SHOPPING_CART_TEXT_AFTER',
                        'desc'  => $this->l(
                            'Add a custom text at the bottom of the shopping cart summary.',
                            'advancedeucompliance'
                        ),
                    ],

                ],
                'submit' => [
                    'title' => $this->l('Save', 'advancedeucompliance'),
                ],
            ],
        ];
    }

    /**
     * Create the form that will let user choose all the wording options
     */
    protected function renderFormFeaturesManager()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAEUC_featuresManager';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormFeaturesManagerValues(),
            /* Add values for your inputs */
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigFormFeaturesManager()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigFormFeaturesManager()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Features', 'advancedeucompliance'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Enable \'Tell A Friend\' feature', 'advancedeucompliance'),
                        'name'    => 'AEUC_FEAT_TELL_A_FRIEND',
                        'is_bool' => true,
                        'desc'    => $this->l(
                            'Make sure you comply with your local legislation before enabling: the emails sent by this feature can be considered as unsolicited commercial emails.',
                            'advancedeucompliance'
                        ),
                        'hint'    => $this->l(
                            'If enabled, the \'Send to a Friend\' module allows customers to send to a friend an email with a link to a product\'s page.',
                            'advancedeucompliance'
                        ),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled', 'advancedeucompliance'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled', 'advancedeucompliance'),
                            ],
                        ],
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Enable \'Reordering\' feature', 'advancedeucompliance'),
                        'hint'    => $this->l(
                            'If enabled, the \'Reorder\' option allows customers to reorder in one click from their Order History page.',
                            'advancedeucompliance'
                        ),
                        'name'    => 'AEUC_FEAT_REORDER',
                        'is_bool' => true,
                        'desc'    => $this->l(
                            'Make sure you comply with your local legislation before enabling: it can be considered as unsolicited goods.',
                            'advancedeucompliance'
                        ),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled', 'advancedeucompliance'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled', 'advancedeucompliance'),
                            ],
                        ],
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Enable \'Advanced checkout page\''),
                        'hint'    => $this->l(
                            'The advanced checkout page displays the following sections: payment methods, address summary, ToS agreement, cart summary, and an \'Order with Obligation to Pay\' button.',
                            'advancedeucompliance'
                        ),
                        'name'    => 'AEUC_FEAT_ADV_PAYMENT_API',
                        'is_bool' => true,
                        'desc'    => $this->l(
                            'To address some of the latest European legal requirements, the advanced checkout page displays additional information (terms of service, payment methods, etc) as a single page.',
                            'advancedeucompliance'
                        ),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled', 'advancedeucompliance'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled', 'advancedeucompliance'),
                            ],
                        ],
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l(
                            'Proportionate tax for shipping and wrapping',
                            'advancedeucompliance'
                        ),
                        'name'    => 'PS_ATCP_SHIPWRAP',
                        'is_bool' => true,
                        'desc'    => $this->l(
                            'When enabled, tax for shipping and wrapping costs will be calculated proportionate to taxes applying to the products in the cart.',
                            'advancedeucompliance'
                        ),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled', 'advancedeucompliance'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled', 'advancedeucompliance'),
                            ],
                        ],
                    ],
                    [
                        'type'    => 'select',
                        'label'   => $this->l('Round type for calculating proportionate taxes'),
                        'desc'    => $this->l('You can choose what method to apply for rounding the proportionate tax: the same as the `round type` setting on the Preferences page, either on each item, each line or the total (of an invoice, for example). No rounding uses the product prices (up to 6 decimals) to calculate the exact proportion. This is the most accurate option. The applied method will then be used to calculate the proportionate shipping and/or gift wrapping taxes.'),
                        'name'    => 'PS_ATCP_SHIPWRAP_ROUNDING',
                        'options' => [
                            'query' => [
                                [
                                    'id'   => 0,
                                    'name' => $this->l('Same as the `round type` setting', 'advancedeucompliance'),
                                ],
                                [
                                    'id'   => Order::ROUND_ITEM,
                                    'name' => $this->l('Round on each item', 'advancedeucompliance'),
                                ],
                                [
                                    'id'   => Order::ROUND_LINE,
                                    'name' => $this->l('Round on each line', 'advancedeucompliance'),
                                ],
                                [
                                    'id'   => Order::ROUND_TOTAL,
                                    'name' => $this->l('Round on the total', 'advancedeucompliance'),
                                ],
                                [
                                    'id'   => 4,
                                    'name' => $this->l('No rounding', 'advancedeucompliance'),
                                ],
                            ],
                            'name'  => 'name',
                            'id'    => 'id',
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save', 'advancedeucompliance'),
                ],
            ],
        ];
    }

    /**
     * Create the form that will let user manage his legal page trough "CMS" feature
     */
    protected function renderFormLegalContentManager()
    {
        $cmsRolesAeuc = $this->getCMSRoles();
        $cmsRepository = $this->entityManager->getRepository('CMS');
        $cmsRoleRepository = $this->entityManager->getRepository('CMSRole');
        $cmsRoles = $cmsRoleRepository->findByName(array_keys($cmsRolesAeuc));
        $cmsRolesAssoc = [];
        $idLang = Context::getContext()->employee->id_lang;
        $idShop = Context::getContext()->shop->id;

        foreach ($cmsRoles as $cmsRole) {
            if ((int) $cmsRole->id_cms > 0) {
                $cmsEntity = $cmsRepository->findOne((int) $cmsRole->id_cms);
                $assocCmsName = $cmsEntity->meta_title[(int) $idLang];
            } else {
                $assocCmsName = $this->l('-- Select associated CMS page --', 'advancedeucompliance');
            }

            $cmsRolesAssoc[(int) $cmsRole->id] = [
                'id_cms'     => (int) $cmsRole->id_cms,
                'page_title' => (string) $assocCmsName,
                'role_title' => (string) $cmsRolesAeuc[$cmsRole->name],
            ];
        }

        $cmsPages = $cmsRepository->i10nFindAll($idLang, $idShop);
        $fakeObject = new stdClass();
        $fakeObject->id = 0;
        $fakeObject->meta_title = $this->l('-- Select associated CMS page --', 'advancedeucompliance');
        $cmsPages[-1] = $fakeObject;
        unset($fakeObject);

        $this->context->smarty->assign(
            [
                'cms_roles_assoc' => $cmsRolesAssoc,
                'cms_pages'       => $cmsPages,
                'form_action'     => $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name,
                'add_cms_link'    => $this->context->link->getAdminLink('AdminCMS'),
            ]
        );

        return $this->display(__FILE__, 'views/templates/admin/legal_cms_manager_form.tpl');
    }

    protected function renderFormEmailAttachmentsManager()
    {
        $cmsRolesAeuc = $this->getCMSRoles();
        $cmsRoleRepository = $this->entityManager->getRepository('CMSRole');
        $cmsRolesAssociated = $cmsRoleRepository->getCMSRolesAssociated();
        $legalOptions = [];
        $cleanedMailsNames = [];

        foreach ($cmsRolesAssociated as $role) {
            $listIdMailAssoc = AeucCMSRoleEmailEntity::getIdEmailFromCMSRoleId((int) $role->id);
            $cleanList = [];

            foreach ($listIdMailAssoc as $listIdMailAssoc) {
                $cleanList[] = $listIdMailAssoc['id_mail'];
            }

            $legalOptions[$role->name] = [
                'name'               => $cmsRolesAeuc[$role->name],
                'id'                 => $role->id,
                'list_id_mail_assoc' => $cleanList,
            ];
        }

        foreach (AeucEmailEntity::getAll() as $email) {
            $cleanedMailsNames[] = $email;
        }

        $this->context->smarty->assign(
            [
                'has_assoc'       => $cmsRolesAssociated,
                'mails_available' => $cleanedMailsNames,
                'legal_options'   => $legalOptions,
                'form_action'     => $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name,
            ]
        );

        // Insert JS in the page
        $this->context->controller->addJS(($this->_path).'views/js/email_attachement.js');

        return $this->display(__FILE__, 'views/templates/admin/email_attachments_form.tpl');
    }

    protected function processPsProductWeightPrecision($optionValue)
    {
        $optionValue = (int) $optionValue;

        /* Avoid negative values */
        if ($optionValue < 0) {
            $optionValue = 0;
        }

        Configuration::updateValue('PS_PRODUCT_WEIGHT_PRECISION', (int) $optionValue);
    }

    protected function processAeucEmailAttachmentsManager()
    {
        $jsonAttachAssoc = Tools::jsonDecode(Tools::getValue('emails_attach_assoc'));

        if (!$jsonAttachAssoc) {
            return;
        }

        // Empty previous assoc to make new ones
        AeucCMSRoleEmailEntity::truncate();

        foreach ($jsonAttachAssoc as $assoc) {
            $assocObj = new AeucCMSRoleEmailEntity();
            $assocObj->id_mail = $assoc->id_mail;
            $assocObj->id_cms_role = $assoc->id_cms_role;

            if (!$assocObj->save()) {
                $this->errors[] = $this->l('Failed to associate CMS content with an email template.', 'advancedeucompliance');
            }
        }
    }

    protected function processDiscardTplWarn()
    {
        Configuration::updateValue('AEUC_IS_THEME_COMPLIANT', true);
    }

    protected function processPsAtcpShipWrap($isOptionActive)
    {
        Configuration::updateValue('PS_ATCP_SHIPWRAP', $isOptionActive);
    }

    protected function processAeucLegalContentManager()
    {
        $postedValues = Tools::getAllValues();
        $cmsRoleRepository = $this->entityManager->getRepository('CMSRole');

        foreach ($postedValues as $keyName => $assocCmsId) {
            if (strpos($keyName, 'CMSROLE_') !== false) {
                $explodedKeyName = explode('_', $keyName);
                /** @var CMSRole $cmsRole */
                $cmsRole = $cmsRoleRepository->findOne((int) $explodedKeyName[1]);
                $cmsRole->id_cms = (int) $assocCmsId;
                $cmsRole->update();
            }
        }
        unset($cmsRole);
    }
}
