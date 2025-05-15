import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import '../legacy/js/sylius-form-collection.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    std: String,
    adv: String,
  };
  static targets = ['switch'];
  initialize() {
    this.options = {
      locale: {
        trigger: "#product_filter_rule_simple_completeness_type",
          selector: "#product_filter_rule_simple_locales",
          values: [
          "GREATER THAN ON ALL LOCALES",
          "GREATER OR EQUALS THAN ON ALL LOCALES",
          "LOWER THAN ON ALL LOCALES",
          "LOWER OR EQUALS THAN ON ALL LOCALES",
        ],
      },
      before: {
        trigger: "#product_filter_rule_simple_updated_mode",
          selector: "#product_filter_rule_simple_updated_before",
          values: ["BETWEEN", "<"],
      },
      after: {
        trigger: "#product_filter_rule_simple_updated_mode",
          selector: "#product_filter_rule_simple_updated_after",
          values: ["BETWEEN", ">"],
      },
      since: {
        trigger: "#product_filter_rule_simple_updated_mode",
          selector: "#product_filter_rule_simple_updated",
          values: ["SINCE LAST N DAYS"],
      },
    }
  }
  connect() {
    this.toggleFields();
    $('[data-form-type="collection"]').CollectionForm();
    if (!this.hasSwitchTarget) {
      return;
    }
    if (this.switchTarget.checked || this.switchTarget.querySelector('input:checked')) {
      this.switchTarget.dispatchEvent(new CustomEvent('input'));
    }
  }
  toggleForms(e) {
    if (this.hasStdValue && this.hasAdvValue) {
      $(e.currentTarget).is(':checked')
        ? $('button[type=submit]').attr('form', this.advValue)
        : $('button[type=submit]').attr('form', this.stdValue);
    }
    $(".form-switch > label").toggleClass("opacity-50");
    $(".togglable").toggle();
  }
  toggleFields() {
    const toHide = [
      this.options.locale.selector,
      this.options.before.selector,
      this.options.after.selector,
      this.options.since.selector,
    ];
    for (let k in this.options) {
      if (this.options[k].values.includes($(this.options[k].trigger).val())) {
        let index = toHide.indexOf(this.options[k].selector);
        if (index !== -1) {
          toHide.splice(index, 1);
        }
      }
      if ($(this.options[k].trigger).length) {
        $(this.options[k].trigger).on("change", (e) => {
          this.options[k].values.includes($(e.currentTarget).val())
            ? $(this.options[k].selector).parent(".field").removeClass("d-none")
            : $(this.options[k].selector).parent(".field").addClass("d-none");
        });
      }
    }
    if ($(`${toHide.join()}`).length) {
      $(`${toHide.join()}`).parent(".field").addClass("d-none");
    }
  }
}