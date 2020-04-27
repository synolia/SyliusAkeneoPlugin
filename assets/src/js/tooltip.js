const Tooltip = {
  /**
   * {Array}
   */
  selectors: [
    "#api_configuration_username",
    "#api_configuration_paginationSize",
    "#product_filter_rule_simple_completeness_type",
    "#product_filter_rule_simple_completeness_value",
    "#product_filter_rule_simple_status",
    "#product_filter_rule_simple_families",
  ],
  /**
   * @return {void}
   */
  load() {
    this.toggleTooltip();
  },
  /**
   * @return {void}
   */
  toggleTooltip() {
    if ($(this.selectors.join()).length) {
      $(this.selectors.join()).on("click", function () {
        $(this).parent(".field").siblings(".pointing").show();
      });
    }
  },
};

$(document).ready(() => {
  Tooltip.load();
});
