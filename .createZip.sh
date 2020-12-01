#!/bin/bash

plugin=$(sed 's/.*"version": "\(.*\)".*/\1/;t;d' ./composer.json)

filename=semknox-magento2_$plugin.zip

cd ../..

if [ -e $filename ]; then
	rm $filename
fi


zip -rq $filename semknox -x 'semknox/semknox-core/examples/*' -x 'semknox/semknox-core/tests/*' -x '*/codeception.yml' -x '*/.*'
echo "$filename Archive created."