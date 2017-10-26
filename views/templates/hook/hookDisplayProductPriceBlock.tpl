{**
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
 *}
{nocache}
  {if isset($smartyVars)}
    {* "From" Price Hook templating *}
    {if isset($smartyVars.before_price) && isset($smartyVars.before_price.from_str_i18n)}
      <span class="aeuc_from_label">
            {$smartyVars.before_price.from_str_i18n|escape:'htmlall'}
        </span>
    {/if}

    {* "Before" Price Hook templating *}
    {if isset($smartyVars.old_price) && isset($smartyVars.old_price.before_str_i18n)}
      <span class="aeuc_before_label">
            {$smartyVars.old_price.before_str_i18n|escape:'htmlall'}
        </span>
    {/if}

    {* "Tax Inc/Exc" Price Hook templating *}
    {if isset($smartyVars.price) && isset($smartyVars.price.tax_str_i18n)}
      <span class={if isset($smartyVars.price.css_class)}
      "{$smartyVars.price.css_class|escape:'htmlall'}"
    {else}
      "aeuc_tax_label"
    {/if}>
      {$smartyVars.price.tax_str_i18n|escape:'htmlall'}
      </span>
    {/if}

    {* "Shipping CMS content" Price Hook templating *}
    {if isset($smartyVars.ship) && isset($smartyVars.ship.link_ship_pay) &&
    isset($smartyVars.ship.ship_str_i18n)}
      <div class="aeuc_shipping_label">
        <a href="{$smartyVars.ship.link_ship_pay}" class="iframe">
          {$smartyVars.ship.ship_str_i18n|escape:'htmlall'}
        </a>
      </div>
    {/if}

    {* "Weight" Price Hook templating *}
    {if isset($smartyVars.weight) && isset($smartyVars.weight.rounded_weight_str_i18n)}
      <div class="aeuc_weight_label">
        {$smartyVars.weight.rounded_weight_str_i18n|escape:'htmlall'}
      </div>
    {/if}

    {* "Delivery Estimation" Price Hook templating *}
    {if isset($smartyVars.after_price) && isset($smartyVars.after_price.delivery_str_i18n)}
      <div class="aeuc_delivery_label">
        {$smartyVars.after_price.delivery_str_i18n|escape:'htmlall'}
      </div>
    {/if}
  {/if}
{/nocache}
