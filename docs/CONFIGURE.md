# Configure your Akeneo Account

## Akeneo authentication

You must be authenticated to the Akeneo API to use this plugin.

If you don't have any client id, please take a look at [this page](https://api.akeneo.com/documentation/authentication.html#client-idsecret-generation) to create it.

## Configure authentication in the plugin

### The Akeneo API configuration can be setup using env variables.

```dotenv
# .env.local

SYNOLIA_AKENEO_BASE_URL=https://
SYNOLIA_AKENEO_CLIENT_ID=
SYNOLIA_AKENEO_CLIENT_SECRET=
SYNOLIA_AKENEO_USERNAME=
SYNOLIA_AKENEO_PASSWORD=

# See Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum
SYNOLIA_AKENEO_EDITION=ee

# See Synolia\SyliusAkeneoPlugin\Config\AkeneoAxesEnum
SYNOLIA_AKENEO_AXE_AS_MODEL=first

# Integer between 1 and 100
SYNOLIA_AKENEO_PAGINATION=100
```

```yaml
# config/packages/synolia_akeneo_plugin.yaml

synolia_sylius_akeneo:
    api_configuration:
        base_url: '%env(resolve:SYNOLIA_AKENEO_BASE_URL)%'
        client_id: '%env(resolve:SYNOLIA_AKENEO_CLIENT_ID)%'
        client_secret: '%env(resolve:SYNOLIA_AKENEO_CLIENT_SECRET)%'
        username: '%env(resolve:SYNOLIA_AKENEO_USERNAME)%'
        password: '%env(resolve:SYNOLIA_AKENEO_PASSWORD)%'
        edition: '%env(resolve:SYNOLIA_AKENEO_EDITION)%'
        axe_as_model: '%env(resolve:SYNOLIA_AKENEO_AXE_AS_MODEL)%'
        pagination: '%env(int:SYNOLIA_AKENEO_PAGINATION)%'
```

#### Axe as model configuration

Option "**first**" :
The "common" model on Akeneo will not be imported. The first axe on akeneo will become the product on sylius and all the other axes on akeneo will become a combination of options for the product variant.

Option "**common**" : 
The common model on akeneo will become the product on sylius and all the other axes on akeneo will become a combination of options for the product variant.


### The Akeneo API configuration can be setup in Sylius Admin.

⚠️ Deprecated, use env variables instead ⚠️

![Api Configuration](media/api_configuration.png)
---

Next step: [Advanced configuration](CONFIGURE_DETAIL.md)
