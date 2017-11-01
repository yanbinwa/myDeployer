# coding=UTF-8

import os
import myDeployerConstants

ANSIBLE_HOST_NAME_KEY = "name"
ANSIBLE_HOST_USER_KEY = "user"
ANSIBLE_HOST_IP_KEY = "ip"
ANSIBLE_HOST_PASSWORD_KEY = "password"

ANSIBLE_HOST_USER_TAG = "ansible_ssh_user"
ANSIBLE_HOST_PASSWORD_TAG = "ansible_ssh_pass"
ANSIBLE_HOST_SUDO_PASSWORD_TAG = "ansible_sudo_pass"

def setupAnsibleHost(config, ansibleHostPath):
    if config == None:
        print "The config map should not be Null"
        return -1
    
    ansible_host_map = {}
    
    #ansible server host 
    ansible_host = {}
    host_key = config[myDeployerConstants.HOST_KEY]
    ansible_host[ANSIBLE_HOST_NAME_KEY] = config[myDeployerConstants.HOST_KEY]
    ansible_host[ANSIBLE_HOST_USER_KEY] = config[myDeployerConstants.USER_KEY]
    ansible_host[ANSIBLE_HOST_IP_KEY] = config[myDeployerConstants.IP_KEY]
    ansible_host[ANSIBLE_HOST_PASSWORD_KEY] = config[myDeployerConstants.PASSWORD_KEY]
    
    ansible_host_map[host_key] = ansible_host
    
    #k8s server host
    ansible_host = {}
    host_key = config[myDeployerConstants.K8S_HOST_KEY]
    ansible_host[ANSIBLE_HOST_NAME_KEY] = config[myDeployerConstants.K8S_HOST_KEY]
    ansible_host[ANSIBLE_HOST_USER_KEY] = config[myDeployerConstants.K8S_USER_KEY]
    ansible_host[ANSIBLE_HOST_IP_KEY] = config[myDeployerConstants.K8S_IP_KEY]
    ansible_host[ANSIBLE_HOST_PASSWORD_KEY] = config[myDeployerConstants.K8S_PASSWORD_KEY]
    
    ansible_host_map[host_key] = ansible_host
    
    buildAnsibleHost(ansibleHostPath, ansible_host_map)
    
    
def buildAnsibleHost(ansibleHostPath, ansible_host_map):
    
    ansibleHostDir = ansibleHostPath[:ansibleHostPath.rindex("/")]
    
    if not os.path.exists(ansibleHostDir):
        os.makedirs(ansibleHostDir)
    
    if os.path.exists(ansibleHostPath):
        os.remove(ansibleHostPath)
        
    ansibleHostFile = os.open(ansibleHostPath, os.O_CREAT|os.O_RDWR)
    for hostKey in ansible_host_map.keys():
        hostHeader = "[" + hostKey + "]" + "\n"
        os.write(ansibleHostFile, hostHeader)
        host = ansible_host_map[hostKey]
        hostBody = host[ANSIBLE_HOST_IP_KEY] + " " + ANSIBLE_HOST_USER_TAG + "=" + host[ANSIBLE_HOST_USER_KEY] + \
                                               " " + ANSIBLE_HOST_PASSWORD_TAG + "=" + host[ANSIBLE_HOST_PASSWORD_KEY] + \
                                               " " + ANSIBLE_HOST_SUDO_PASSWORD_TAG + "=" + host[ANSIBLE_HOST_PASSWORD_KEY] + "\n"
        os.write(ansibleHostFile, hostBody)
        
    os.close(ansibleHostFile)
    