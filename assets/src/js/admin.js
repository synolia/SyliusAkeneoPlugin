const Admin = {
  organizations: ['synolia', 'akeneo'],
  initialize() {
    $(document).ready(() => {
      for (let i = 0; i < this.organizations.length; i++) {
        $(`a[href*=${this.organizations[i]}]`).addClass(this.organizations[i]);
      }
    });
  },
};

export default Admin;
