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

$(document).ready(function () {
  aeuc_controller = new AEUC_Controller();

  if ($.prototype.fancybox) {
    $('a.iframe').fancybox({
      'type': 'iframe',
      'width': 600,
      'height': 600
    });
  }

  $('button[name="processCarrier"]').click(function (event) {
    /* Avoid any further action */
    event.preventDefault();
    event.stopPropagation();

    if (aeuc_has_virtual_products === true && aeuc_controller.checkVirtualProductRevocation() === false) {
      var to_display = $('<div/>').html(aeuc_virt_prod_err_str).text();
      $.fancybox(to_display, {
        minWidth: 'auto',
        minHeight: 'auto'
      });
      return;
    }
    $('<input>', {
      type: 'hidden',
      id: 'processCarrier',
      name: 'processCarrier',
      value: '1'
    }).appendTo('#form');

    $("#form").submit();
  });

});

var AEUC_Controller = function () {

  this.checkVirtualProductRevocation = function () {
    if ($('#revocation_vp_terms_agreed').prop('checked')) {
      return true;
    }

    return false;
  }
};
