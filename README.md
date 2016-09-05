# debugfs-webgui

Simplify the Linux kernel dynamic debug

## Features
* multiple configs for each file
* display only files of interest
* update, save and restore configs

## Target system requirements
* web server (e.g. apache2) + PHP

## Install
* transfer files to the target system 
* make them accessible through the web server

## Manual
* http://target-ip/webserver-path-to-degufs.html/debugfs.html
 * **Edit list** - displays all and allows to select the files of interest
 * **Save to persistent storage** - copies current config from tmpfs to disk of the target system
 * **Apply to debugfs** - applies config from the selected in web GUI files to debugfs
 * **Switch off debug** - turns off debug of all of the files of interest
 * Click on the filename to expand available debug options
 * Check the checkbox next to the line number to enable line (adds **+p**) - uncheck to disable (**-p**) - the command is sent on change
 * **read lines from debugfs** - updates line numbers if the source has changed but the old config is loaded, the checkboxes states pattern is kept.
 * Use the green dropdown menu in the file's table to create and select individual configs.


## Behind the scenes
*  **f**, **l**, **m**, **t** flags are applied on load/refresh of the page
* On page load/refresh debugfs.php tries to restore the global config in the following order: 
 * from tmpfs (*/tmp/debugfs.json*) - it is a working copy
 * from target system's persistent storage (mounted rootfs, in the same folder where *debugfs.php*). Copies to the working copy.
 * from debugfs (*/sys/kernel/debug/dynamic_debug/control*). Reads and creates the working copy.
* Global config format is *json*.
* *jquery.ajax.queue.js* plugin is used to send ajax requests sequentially to resolve racing condition in access the global config file.
