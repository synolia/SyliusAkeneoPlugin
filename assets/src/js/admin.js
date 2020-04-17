// Assuming jQuery is already loaded in the back office
const Admin = {
  /**
   * {Array}
   */
  organizations: ["synolia", "akeneo"],
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
  initialize() {
    $(document).ready(() => {
      this.copyrights();
      if (window.location.href.includes("/product_filter/rules")) {
        this.toggleForms();
        this.toggleFields();
      }
    });
  },
  /**
   * @return {void}
   */
  copyrights() {
    for (let i = 0; i < this.organizations.length; i++) {
      $(`a[href*=${this.organizations[i]}]`).addClass(this.organizations[i]);
    }
  },
  /**
   * @return {void}
   */
  toggleForms() {
    this.trigger.removeClass("hidden");
    this.trigger.on("click", function () {
      $(".togglable").toggle();
    });
  },
  /**
   * @return {void}
   */
  toggleFields() {
    const self = this;
    $(`
      ${this.options.locale.selector}, 
      ${this.options.after.selector},
      ${this.options.since.selector}
    `)
      .parent(".field")
      .addClass("hidden");
    for (let k in this.options) {
      $(this.options[k].trigger).on("change", function () {
        self.options[k].values.includes($(this).val())
          ? $(self.options[k].selector).parent(".field").removeClass("hidden")
          : $(self.options[k].selector).parent(".field").addClass("hidden");
      });
    }
  },
};

export default Admin;
