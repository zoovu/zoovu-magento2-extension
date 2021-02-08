#!/bin/bash

plugin=$(sed 's/.*"version": "\(.*\)".*/\1/;t;d' ./composer.json)

filename=semknox-magento2_$plugin.zip

cd ../..

if [ -e $filename ]; then
	rm $filename
fi

cd ..
zip -rq extensions/$filename extensions -x 'extensions/semknox/semknox-core/examples/*' -x 'extensions/semknox/semknox-core/tests/*' -x '*/codeception.yml' -x '*/.*' -x '*.zip'
echo "$filename Archive created."