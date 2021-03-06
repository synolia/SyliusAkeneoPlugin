const Form = {
  /**
   * {jQuery}
   */
  trigger: $(
    "#switchForm, #switchForm + label, #product_configuration_importMediaFiles, #product_configuration_importMediaFiles + label"
  ),
  /**
   * {Object}
   */
  options: {
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
  },
  /**
   * @return {void}
   */
  load() {
    this.toggleForms();
    this.toggleFields();
  },
  /**
   * @return {void}
   */
  toggleForms() {
    this.trigger.removeClass("hidden");
    if (this.trigger.length) {
      this.trigger.on("click", function () {
        $(this).is(":checked")
          ? $(".switch > span").addClass("muted")
          : $(".switch > span").removeClass("muted");
        $(".togglable").toggle();
      });
    }
  },
  /**
   * @return {void}
   */
  toggleFields() {
    const self = this;
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
        $(this.options[k].trigger).on("change", function () {
          self.options[k].values.includes($(this).val())
            ? $(self.options[k].selector).parent(".field").removeClass("hidden")
            : $(self.options[k].selector).parent(".field").addClass("hidden");
        });
      }
    }

    if ($(`${toHide.join()}`).length) {
      $(`${toHide.join()}`).parent(".field").addClass("hidden");
    }
  },
};

$(document).ready(() => {
  Form.load();
});
