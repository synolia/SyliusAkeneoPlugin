// Assuming jQuery is already loaded in the back office
const Common = {
  /**
   * {Array}
   */
  organizations: ["synolia", "akeneo"],
  /**
   * @return {void}
   */
  load() {
    this.copyrights();
  },
  /**
   * @return {void}
   */
  copyrights() {
    for (let i = 0; i < this.organizations.length; i++) {
      $(`a[href*=${this.organizations[i]}]`).addClass(this.organizations[i]);
    }
  },
};

export default Common;
