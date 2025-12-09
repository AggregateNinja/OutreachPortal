#!/bin/bash

for VERSION in 5.6
do
  docker run --rm -it \
  --env-file ./env.list \
  -v $PWD:/app/pokitdok php:$VERSION \
  /bin/sh /app/pokitdok/setup_and_test_5.sh
done

for VERSION in 7.0 7.1
do
  docker run --rm -it \
  --env-file ./env.list \
  -v $PWD:/app/pokitdok php:$VERSION \
  /bin/sh /app/pokitdok/setup_and_test.sh
done