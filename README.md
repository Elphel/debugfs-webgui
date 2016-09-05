# debugfs-webgui
Simplify up the Linux kernel dynamic debug

## target system requirements
* webserver + php

## install
* copy files to the target system 
* make them accessible to the webserver

## user manual
* http://\<target-ip\>/\<webserver-path-to-degufs.html\>/debugfs.html

## behind the scenes

1. On page load debugfs.php restores the global config (if found) from disk or creates a new one from the /sys/kernel/debug/dynamic_debug/control.

2. Current global config is stored and changed in tmpfs (/tmp/debugfs.json)

3. Permanent global config is stored in the same folder as the debugfs.php.

4. jquery.ajax.queue.js plugin is used to send ajax requests sequentlially to deal with a simultaneous access to the config file.

5. 