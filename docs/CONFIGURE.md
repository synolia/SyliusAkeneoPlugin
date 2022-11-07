# Configure your Akeneo Account

## Akeneo authentication

You must be authenticated to the Akeneo API to use this plugin.

If you don't have any client id, please take a look at [this page](https://api.akeneo.com/documentation/authentication.html#client-idsecret-generation) to create it.

## Configure authentication in the plugin

The Akeneo API configuration can be setup using env variables.

```dotenv
SYNOLIA_AKENEO_BASE_URL=https://
SYNOLIA_AKENEO_CLIENT_ID=
SYNOLIA_AKENEO_CLIENT_SECRET=
SYNOLIA_AKENEO_USERNAME=
SYNOLIA_AKENEO_PASSWORD=

# See Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum
SYNOLIA_AKENEO_EDITION=ee
# Integer between 1 and 100
SYNOLIA_AKENEO_PAGINATION=100
```

![Api Configuration](media/api_configuration.png)
---

Next step: [Advanced configuration](CONFIGURE_DETAIL.md)
