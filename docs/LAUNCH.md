# Developers - Launch imports

## By CLI on server

### Commands

You can launch each command by using 

```shell
bin/console 
```

and add one of

    akeneo:import:categories                Import Categories from Akeneo PIM.
    akeneo:import:attributes                Import Attributes and Options from Akeneo PIM.
    akeneo:import:families                  Import product's families from Akeneo PIM.
    akeneo:import:association-type          Import Associations type from Akeneo PIM.
    akeneo:import:product-models            Import Product Models from Akeneo PIM.
    akeneo:import:products                  Import Products from Akeneo PIM.

> This is the recommended order to launch imports

#### Arguments

`--disable-batch`

Fetch all pages then start processing the data

`--parallel`

Fetch all pages but start processing as soon as batch size is reached

`--batch-size 100`

Define how many data you need in each batch

`--max-concurrency 5`

Define how many parallel processes to be launched (5 by default)

`--continue`

Import from where it stopped.
This option is not fetching new data from akeneo.
It only processes the rest of the data in the temp table.

`--batch-after-fetch`

Fetch all pages then start processing the batches.
This allows you to recover the import process by launching the command again with the `--continue` argument

### Logs

#### Show logs in console output

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

#### Verbosity levels

Command without verbosity option like :

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

## Launch in Sylius Back Office

You can configure and launch every CLI command by using our [SyliusSchedulerCommandPlugin](https://github.com/synolia/SyliusSchedulerCommandPlugin)

---

Previous step: [Customization](CUSTOMIZE.md)
