#!/usr/bin/expect 

set timeout 1200

# change for diff env
set MAC_PROJECT_HOME "/Users/emotibot/Documents/workspace/other/myDeployer/ansible"
set WORK_DIR "/home/deployer/yanbinwa"
set HOST_IP "172.16.101.43"
set HOST_USERNAME "deployer"
set PASSWD "Emotibot1"

spawn scp -r $MAC_PROJECT_HOME $HOST_USERNAME@$HOST_IP:$WORK_DIR

expect {
    "(yes/no)?" {  
        send "yes\r"  
        expect "password:"  
        send "$PASSWD\r"  
    }  
    "password:" {  
        send "$PASSWD\r" 
    }
}
expect eof

spawn ssh -l $HOST_USERNAME $HOST_IP
expect {
    "(yes/no)?" {  
        send "yes\r"  
        expect "password:"  
        send "$PASSWD\r"  
    }  
    "password:" {  
        send "$PASSWD\r" 
    }
}
expect "]\\$"
send "cd $WORK_DIR/ansible\r"
expect "]\\$"
send "/bin/bash script/install.sh\r"
expect "]\\$"
exit