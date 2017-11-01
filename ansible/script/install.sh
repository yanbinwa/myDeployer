#!/bin/bash

# change for diff env
ROOT_PATH=/home/deployer/yanbinwa/ansible

cd $ROOT_PATH

#read the manifest file to build the host template
python $ROOT_PATH/script/myDeployerSetup.py $ROOT_PATH/config/config.yaml

#run ansible script
ansible-playbook $ROOT_PATH/target/main.yaml -i $ROOT_PATH/target/host.yaml