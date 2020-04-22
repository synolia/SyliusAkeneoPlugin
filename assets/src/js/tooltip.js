const Tooltip = {
  /**
   * {Array}
   */
  selectors: [
    "#api_configuration_username",
    "#api_configuration_paginationSize",
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
