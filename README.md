# debugfs-webgui

Simpler, more flexible and convenient Linux kernel dynamic debug (dyndbg)

## Features
* multiple configs for each file
* display only files of interest
* update, save and restore configs

<img src="http://community.elphel.com/pictures/debugfs-webgui/debugfs-webgui.png" width="560" alt="debugfs-webgui screenshot" align='middle' >

## Target system requirements
* web server (e.g. Apache2) + PHP

## Install
* copy files to the target system 
* make them accessible through the web server

## Manual
* Open http://target-ip/webserver-path-to-degufs.html/debugfs.html
 *  **f**, **l**, **m**, **t** flags are applied on page load/refresh, also use checkboxes
 * click **Edit list** to display all files and select the files of interest
 * click **Apply to debugfs** to apply restored config to debugfs - restored config is not applied on page load/refresh - it's just read from the stored config file.
 * click on a filename to expand available debug options
 * use checkboxes to control (command is sent on change)
 * click **read lines from debugfs** if the source has changed (line numbers got shifted) to update config - checkboxes will keep their states.
 * use the green dropdown menu in the file's table to create and select individual configs.
 * click **Save to persistent storage** - copies current config from *tmpfs* to the target system's persistent storage (mounted rootfs, the same dir as *debugfs.php*)
 * click **Switch off debug** to turn off debug in all of the files of interest.

## Notes
* *debugfs.json* stored the same dir as *debugfs.php* is the global file in *json* format. It stores all individual configs.
* *jquery.ajax.queue.js* plugin is used to send ajax requests sequentially to resolve racing condition in accessing the global config file.
* On page load/refresh debugfs.php tries to restore the global config in the following order: 
 * from *tmpfs* (*/tmp/debugfs.json*) - is a working copy, don't not forget to save it before reboot.
 * from the target system's persistent storage (*debugfs.json* - mounted rootfs, the same dir as *debugfs.php*). Copied to */tmp/debugfs.json*.
 * from debugfs (*/sys/kernel/debug/dynamic_debug/control*). Copied into */tmp/debugfs.json*. **Save to persistent storage** - to create persistent config.
* Depending on the number of selected files ajax requests can take some time: 
     * single debug message - quick
     * **f**,**l**,**m**,**t**, **apply to debugfs** - 100+ ms - depends on the number of files.

## Behind the scenes
* PHP - converts json config to queries and back, applies queries to dyndbg, stores configs, responds to ajax requests.
* JavaScript + libraries:
 * [jQuery](https://jquery.com/)
 * [jquery.ajax.queue.js](https://blog.alexmaccaw.com/queuing-ajax-requests)
 * [Bootstrap](http://getbootstrap.com/)
