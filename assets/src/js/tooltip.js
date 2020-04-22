const Tooltip = {
  /**
   * @return {void}
   */
  load() {
    this.toggleTooltip(
      "#api_configuration_username",
      "#api_configuration_paginationSize"
    );
  },
  /**
   * @param {string} selectors
   */
  toggleTooltip(...selectors) {
    if ($(selectors.join()).length) {
      $(selectors.join()).on("click", function () {
        $(this).parent(".field").siblings(".pointing").show();
      });
    }
  },
};

Tooltip.load();
