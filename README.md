# SEMKNOX Magento 2.x Module

This is the SEMKNOX SiteSearch360 module for Magento 2


## Installation via composer

Step 1:
unpack Extension archive in magento 2 root folder

Step 2:
log in to your server via a command line and navigate to your magento 2 root folder

Step 3:
add/update semknox-extension repository in your composer.json:
composer config repositories.semknox path "extensions/semknox/*"

Step 4:
requires semknox-extension and all of its dependencies in your magento 2 Installation
composer require semknox/semknox-magento2

Step 5:
enable semknox-extension:
bin/magento module:enable Semknox_Productsearch

disable would be:
bin/magento module:disable Semknox_Productsearch

Step 6:
bin/magento setup:upgrade

Step 7:
compile frontend and empty cache:
bin/magento setup:di:compile
bin/magento cache:clean



## Update via composer

Step 1:
composer update semknox/semknox-magento2

Step 2:
~~~php
bin/magento setup:upgrade --keep-generated
bin/magento setup:static-content:deploy
bin/magento cache:clean
~~~


## offtopic: usefull magento cli commands

run semknox cronjob only:
~~~php
php bin/magento cron:run --group=semknox_productsearch
~~~

compile without memory limit:
php bin/magento -d memory_limit=-1 setup:di:compile

empty cache:
php bin/magento c:c

disable cache
php bin/magento cache:disable

Fix for elasticsearch low Diskspace error:
curl -XPUT -H "Content-Type: application/json" http://localhost:9200/_cluster/settings -d '{ "transient": { "cluster.routing.allocation.disk.threshold_enabled": false } }'
curl -XPUT -H "Content-Type: application/json" http://localhost:9200/_all/_settings -d '{"index.blocks.read_only_allow_delete": null}'
// source: https://stackoverflow.com/questions/50609417/elasticsearch-error-cluster-block-exception-forbidden-12-index-read-only-all