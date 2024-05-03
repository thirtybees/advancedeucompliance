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
 * @c0pyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

$(document).ready(function(){
    var email_attacher = new EmailAttach();
    email_attacher.init();
});

var EmailAttach;
EmailAttach = function () {

    this.left_column_checkbox_id = 'input[id^=mail_]';
    this.email_attach_form_id = '#emailAttachementsManager';
    this.right_column_checked_checkboxes = 'input[id^=attach_]:checked';
    this.select_all_left_column_id = '#selectall_attach';
    this.select_all_right_column_id = 'input[id^=selectall_opt_]';

    this.init = function () {

        var that = this;

        $(this.left_column_checkbox_id).on('click', function () {
            var id_clicked = $(this).prop('id');
            id_clicked = that.cleanLeftColumnId(id_clicked);
            var have_to_check_checkbox = $(this).prop('checked') ? true : false;
            that.selectAllFollowingOptions(id_clicked, have_to_check_checkbox);
        });

        $(this.select_all_left_column_id).on('click', function () {
            var checked_status = $(this).prop('checked') ? true : false;
            that.selectEverything(checked_status);
        });

        $(this.select_all_right_column_id).on('click', function () {
            var checked_status = $(this).prop('checked') ? true : false;
            var id_clicked = $(this).prop('id');
            id_clicked = that.cleanTopRowId(id_clicked);
            that.selectEverythingRight(id_clicked, checked_status);
        });

        $(this.email_attach_form_id).on('submit', function (e) {
            // Avoid any other behavior but this one
            e.stopPropagation();
            e.preventDefault();
            var assoc_data_array = [];
            // Loop on all selection to get only the checked ones and pass to the controller
            $(that.right_column_checked_checkboxes).each(function () {
                var full_id = $(this).attr('id');
                // mail id should be at 1 and cms_role_id at 2
                var splitted_id = full_id.split('_');
                var id_mail = splitted_id[1];
                var id_cms_role = splitted_id[2];
                assoc_data_array.push({id_mail: id_mail, id_cms_role: id_cms_role});
            });
            that.submitEmailAttachments($(this).attr('action'), assoc_data_array, $(this).attr('method'));
        });
    }

    this.cleanLeftColumnId = function (full_id) {
        var splitted_id = full_id.split('_');
        return splitted_id[1];
    }

    this.cleanTopRowId = function (full_id) {
        var splitted_id = full_id.split('_');
        return splitted_id[2];
    }

    this.selectAllFollowingOptions = function (base_id, checked_status) {
        $('input[id^=attach_' + base_id + '_]').each(function () {
            $(this).prop('checked', checked_status);
        });
    }

    this.selectEverything = function (checked_status) {
        $('input[id^=mail_]').each(function () {
            $(this).prop('checked', checked_status);
        });

        $('input[id^=attach_]').each(function () {
            $(this).prop('checked', checked_status);
        });

        $('input[id^=selectall_opt_]').each(function () {
            $(this).prop('checked', checked_status);
        });
    }

    this.selectEverythingRight = function (base_id, checked_status) {
        $('input[id$=_'+base_id+']').each(function () {
            $(this).prop('checked', checked_status);
        });
    }

    this.submitEmailAttachments = function (action, params, method) {

        var form = document.createElement("form");
        form.setAttribute("method", method);
        form.setAttribute("action", action);

        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("type", "hidden");
        hiddenField.setAttribute("name", 'AEUC_emailAttachmentsManager');
        form.appendChild(hiddenField);

        hiddenField = document.createElement("input");
        hiddenField.setAttribute("type", "hidden");
        hiddenField.setAttribute("name", 'emails_attach_assoc');
        hiddenField.setAttribute("value", JSON.stringify(params));
        form.appendChild(hiddenField);
        $('body').append(form);
        form.submit();
    }
};
