const Form = {
  /**
   * {jQuery}
   */
  trigger: $("#switchForm, #switchForm + label"),
  /**
   * {Object}
   */
  options: {
    locale: {
      trigger: "#product_filter_rule_simple_completeness_type",
      selector: "#product_filter_rule_simple_locales",
      values: ["6", "7", "8", "9"],
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
    this.trigger.on("click", function () {
      $(this).is(":checked")
        ? $(".switch > span").addClass("muted")
        : $(".switch > span").removeClass("muted");
      $(".togglable").toggle();
    });
  },
  /**
   * @return {void}
   */
  toggleFields() {
    const self = this;
    const toHide = [this.options.after.selector, this.options.since.selector];
    if (!$(this.options.locale.selector + ">option:selected").length) {
      toHide.push(self.options.locale.selector);
    }
    if ($(`${toHide.join()}`).length) {
      $(`${toHide.join()}`).parent(".field").addClass("hidden");
    }
    for (let k in this.options) {
      if ($(this.options[k].trigger).length) {
        $(this.options[k].trigger).on("change", function () {
          self.options[k].values.includes($(this).val())
            ? $(self.options[k].selector).parent(".field").removeClass("hidden")
            : $(self.options[k].selector).parent(".field").addClass("hidden");
        });
      }
    }
  },
};

$(document).ready(() => {
  Form.load();
});
