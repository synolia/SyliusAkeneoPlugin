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
  initialize() {
    $(document).ready(() => {
      this.credits();
      if (window.location.href.includes("/product_filter/rules")) {
        this.toggleForms();
      }
    });
  },
  credits() {
    for (let i = 0; i < this.organizations.length; i++) {
      $(`a[href*=${this.organizations[i]}]`).addClass(this.organizations[i]);
    }
  },
  toggleForms() {
    this.trigger.removeClass("hidden");
    this.trigger.on("click", function () {
      $(".togglable").toggle();
    });
  },
};

export default Admin;
