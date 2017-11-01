# coding=UTF-8

import os
import myDeployerConstants

TEMPLATE_FILE_POSTFIX = '.yaml.template'
TARGET_FILE_POSTFIX = '.yaml'

ANSIBLE_LOOP_PREFIX = '      - '
ANSIBLE_INCLUDE_PREFIX = '- include: '

KUBERNETES_CONFIG = 'config'
KUBERNETES_ITEMS = '{{ KUBERNETE_ITEMS }}'

def setAnsibleMain(config, ansibleMainPath):
    
    if config == None:
        print "The config map should not be Null"
        return -1
    
    mainFileDir = ansibleMainPath[:ansibleMainPath.rindex("/")]
    if not os.path.exists(mainFileDir):
        os.makedirs(mainFileDir) 
        
    if os.path.exists(ansibleMainPath):
        os.remove(ansibleMainPath)
    
    mainFile = os.open(ansibleMainPath, os.O_CREAT|os.O_RDWR)
    mainFileHeader = '---\n'
    os.write(mainFile, mainFileHeader)
    
    buildAnsibleMainForInitJob(config, mainFile)
    buildAnsibleMainForPreJob(config, mainFile)
    buildAnsibleMainForMainJob(config, mainFile)
    buildAnsibleMainForAfterJob(config, mainFile)
    os.close(mainFile)
    
def buildAnsibleMainForInitJob(config, mainFile):
    templateFilePath = myDeployerConstants.ANSIBLE_TEMPLATE_INITJOB_PATH
    targetFilePath = myDeployerConstants.ANSIBLE_TARGET_INITJOB_PATH
    createTargetFileByTemplateFile(config, templateFilePath, targetFilePath)
    mainFileContext = getAnsibleIncludeContext(config, targetFilePath)
    os.write(mainFile, mainFileContext)

def buildAnsibleMainForPreJob(config, mainFile):
    
    preJobListStr = config[myDeployerConstants.PRE_JOB_LIST_KEY]
    if preJobListStr == None or preJobListStr == '':
        return
    
    preJobList = preJobListStr.split(',')
    for preJob in preJobList:
        templateFilePath = myDeployerConstants.ANSIBLE_TEMPLATE_PREJOB_DIR + '/' + preJob + '/' + preJob + TEMPLATE_FILE_POSTFIX
        targetFilePath = myDeployerConstants.ANSIBLE_TARGET_PREJOB_DIR + '/' + preJob + '/' + preJob + TARGET_FILE_POSTFIX
        createTargetFileByTemplateFile(config, templateFilePath, targetFilePath)
        mainFileContext = getAnsibleIncludeContext(config, targetFilePath)
        os.write(mainFile, mainFileContext)

def buildAnsibleMainForMainJob(config, mainFile):
    #读取mainJobList，并将需要优先执行的服务拍到前面
    mainJobListStr = config[myDeployerConstants.MAIN_JOB_LIST_KEY]
    if mainJobListStr == None or mainJobListStr == '':
        return
    mainJobList = mainJobListStr.split(',')
    
    mainPrioritizedJobStr = config[myDeployerConstants.MAIN_PRIORITIZED_JOB_LIST_KEY]
    mainPrioritizedJobList = []
    if mainJobListStr != None:
        mainPrioritizedJobList = mainPrioritizedJobStr.split(',')
        
    mainJobListLevel1 = []
    mainJobListLevel2 = []
    
    for mainJob in mainJobList:
        if mainJob in mainPrioritizedJobList:
            mainJobListLevel1.append(mainJob)
        else:
            mainJobListLevel2.append(mainJob)
    
    mainJobList = []
    mainJobList.append(KUBERNETES_CONFIG)
    mainJobList += mainJobListLevel1 + mainJobListLevel2
    
    #读取mainJobFile的template，进行替换，还需要进行添加
    templateMainPath = myDeployerConstants.ANSIBLE_TEMPLATE_MAINJOB_PATH
    targetMainPath = myDeployerConstants.ANSIBLE_TARGET_MAINJOB_PATH
    createTargetFileByTemplateFile(config, templateMainPath, targetMainPath)
    
    targetMainLoopContext = ''
    
    for mainJob in mainJobList:
        templateFilePath = myDeployerConstants.ANSIBLE_TEMPLATE_KUBERNETES_DIR + '/' + mainJob + '/' + mainJob + TEMPLATE_FILE_POSTFIX
        targetFilePath = myDeployerConstants.ANSIBLE_TARGET_KUBERNETES_DIR + '/' + mainJob + '/' + mainJob + TARGET_FILE_POSTFIX
        createTargetFileByTemplateFile(config, templateFilePath, targetFilePath)
        targetMainLoopContext += ANSIBLE_LOOP_PREFIX + mainJob + '\n'
   
    targetMainFileContext = open(config[myDeployerConstants.PROJECT_HOME_DIR_KEY] + '/' + targetMainPath).read()
    targetMainFileContext = targetMainFileContext.replace(KUBERNETES_ITEMS, targetMainLoopContext)
    targetMainFile = os.open(config[myDeployerConstants.PROJECT_HOME_DIR_KEY] + '/' + targetMainPath, os.O_CREAT|os.O_RDWR)
    os.write(targetMainFile, targetMainFileContext)
    
    mainFileContext = getAnsibleIncludeContext(config, targetMainPath)
    os.write(mainFile, mainFileContext)

def buildAnsibleMainForAfterJob(config, mainFile):
    
    afterJobListStr = config[myDeployerConstants.AFTER_JOB_LIST_KEY]
    if afterJobListStr == None or afterJobListStr == '':
        return
    
    afterJobList = afterJobListStr.split(',')
    for afterJob in afterJobList:
        templateFilePath = myDeployerConstants.ANSIBLE_TEMPLATE_AFTERJOB_DIR + '/' + afterJob + '/' + afterJob + TEMPLATE_FILE_POSTFIX
        targetFilePath = myDeployerConstants.ANSIBLE_TARGET_AFTERJOB_DIR + '/' + afterJob + '/' + afterJob + TARGET_FILE_POSTFIX
        createTargetFileByTemplateFile(config, templateFilePath, targetFilePath)
        mainFileContext = getAnsibleIncludeContext(config, targetFilePath)
        os.write(mainFile, mainFileContext)
    

def getAnsibleIncludeContext(config, targetFilePath):
    projectHostDir = config[myDeployerConstants.PROJECT_HOME_DIR_KEY]
    return ANSIBLE_INCLUDE_PREFIX + projectHostDir + '/' + targetFilePath + '\n'

def createTargetFileByTemplateFile(config, templateFilePath, targetFilePath):
    
    templateFilePath = config[myDeployerConstants.PROJECT_HOME_DIR_KEY] + '/' + templateFilePath
    targetFilePath = config[myDeployerConstants.PROJECT_HOME_DIR_KEY] + '/' + targetFilePath
    
    if not os.path.exists(templateFilePath):
        print "The template file is missing" + templateFilePath
    
    targetFileDir = targetFilePath[:targetFilePath.rindex("/")]
    if not os.path.exists(targetFileDir):
        os.makedirs(targetFileDir) 
        
    if os.path.exists(targetFilePath):
        os.remove(targetFilePath)
    
    template = open(templateFilePath).read()
    
    for configKey in config.keys():
        template = template.replace('{{ ' + configKey + ' }}', str(config[configKey]))
    
    targetFile = os.open(targetFilePath, os.O_CREAT|os.O_RDWR)
    os.write(targetFile, template)
    os.close(targetFile)
        