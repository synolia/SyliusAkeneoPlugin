# Usage

## How can i show logs

### Show logs in console output

```yaml
# config/packages/monolog.yaml

monolog:
    handlers:
        ...
        console:
            type: console
            process_psr_3_messages: false
            channels: ['!event', '!doctrine', '!console']

```

### Verbosity levels

    php bin/console akeneo:import:categories
    
Will show logs levels :
- alert
- critical
- error
- emergency
- warning // hide with add `-q` 


    php bin/console akeneo:import:categories -v
    
- all previous
- notice


    php bin/console akeneo:import:categories -vv
    
- all previous
- info


    php bin/console akeneo:import:categories -vvv

- all previous
- debug
