// Assuming jQuery is already loaded in the back office
const Admin = {
  organizations: ["synolia", "akeneo"],
  initialize() {
    $(document).ready(() => {
      this.credits();
    });
  },
  credits() {
    for (let i = 0; i < this.organizations.length; i++) {
      $(`a[href*=${this.organizations[i]}]`).addClass(this.organizations[i]);
    }
  },
};

export default Admin;
