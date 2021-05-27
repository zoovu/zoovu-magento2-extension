# SEMKNOX SiteSearch360 Magento 2.x Module

This is the SEMKNOX SiteSearch360 module for Magento 2

1. [Installation](#installation-via-composer)
2. [Update Installation](#update-via-composer)
3. [Troubleshooting & Magento 2 CLI](#troubleshooting-and-usefull-magento-2-cli-commands)


## Installation via composer

1.  unpack Extension archive in magento 2 root folder

2.  log in to your server via a command line and navigate to your magento 2 root folder

3.  add/update semknox-extension repository in your composer.json:
    ~~~shell
    composer config repositories.semknox path "extensions/semknox/*"
    ~~~

4.  requires semknox-extension and all of its dependencies in your magento 2 Installation
    ~~~shell
    composer require semknox/semknox-magento2
    ~~~

5.  enable semknox-extension:
    ~~~shell
    bin/magento module:enable Semknox_Productsearch
    ~~~

    disable would be:
    ~~~shell
    bin/magento module:disable Semknox_Productsearch
    ~~~

6.  upgrade
    ~~~shell
    bin/magento setup:upgrade
    ~~~

7.  compile frontend and empty cache:
    ~~~shell
    bin/magento setup:di:compile
    bin/magento cache:clean
    ~~~



## Update via composer

1.  update composer package
    ~~~shell
    composer update semknox/semknox-magento2
    ~~~

2.  prepare system
    ~~~shell
    bin/magento setup:upgrade --keep-generated
    bin/magento setup:static-content:deploy
    bin/magento cache:clean
    ~~~


## Troubleshooting and usefull magento 2 cli commands

*  run semknox cronjob only:
    ~~~shell
    php bin/magento cron:run --group=semknox_productsearch
    ~~~

*  compile without memory limit:
    ~~~shell
    php bin/magento -d memory_limit=-1 setup:di:compile
    ~~~

*  empty cache:
    ~~~shell
    php bin/magento c:c
    ~~~

*  disable cache
    ~~~shell
    php bin/magento cache:disable
    ~~~

*  Fix for elasticsearch low Diskspace error:

    *source: https://stackoverflow.com/questions/50609417/elasticsearch-error-cluster-block-exception-forbidden-12-index-read-only-all*
    ~~~shell
    curl -XPUT -H "Content-Type: application/json" http://localhost:9200/_cluster/settings -d '{ "transient": { "cluster.routing.allocation.disk.threshold_enabled": false } }'
    curl -XPUT -H "Content-Type: application/json" http://localhost:9200/_all/_settings -d '{"index.blocks.read_only_allow_delete": null}'
    ~~~
