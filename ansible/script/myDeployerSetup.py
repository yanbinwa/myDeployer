# coding=UTF-8

import yaml
import sys
import myDeployerAnsibleHost
import myDeployerAnsibleMain
import myDeployerConstants

class myDeployerSetup:
    
    def __init__(self):
        if len(sys.argv) < 2:
            print "Should input the manifest file path"
            exit(-1)
            
        self.configFile = sys.argv[1]
        self.config = yaml.safe_load(open(self.configFile))
        
        myDeployerAnsibleHost.setupAnsibleHost(self.config, myDeployerConstants.ANSIBLE_TARGET_HOST_PATH)
        myDeployerAnsibleMain.setAnsibleMain(self.config, myDeployerConstants.ANSIBLE_TARGET_MAIN_PATH)
        
if __name__ == "__main__":
    setup = myDeployerSetup()
