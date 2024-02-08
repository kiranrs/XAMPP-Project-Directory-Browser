# XAMPP-Project-Directory-Browser
This is a small pieces of code that will help you to browse projects inside htdocs in XAMPP

# Prerequisite

1) XAMPP 3 or later
2) Operating System: Any OS where [XAMPP](https://www.apachefriends.org/download.html) support.

# Installation

1) Copy **htdocs_listing.php and xpdb** and replace **index.php** in **installation path to xampp/htdocs/**
2) Start XAMPP Control panel
3) Open your favourite browser, and head to **http://localhost**


**That's it. Now you can browse projects inside your htdocs**

 
 # Change Logs
 
 **V-0.1**
 
 1) jquery & Bootstrap upgraded to version 3.5.1 and 5 beta 1 respectively.
 
 If you're facing any issues while upgrade, Please change **v2** to **v1** in the following lines in header section.<br />
```javascript
 <link href="/themes/v2/assets/css/bootstrap.min.css" rel="stylesheet">
 <script src="/themes/v2/assets/js/bootstrap.min.js"></script>
 <script src="/themes/v2/assets/js/jquery-3.5.1.min.js"></script>
 <link href='/themes/v2/assets/css/font-awesome.min.css' rel='stylesheet'/>
 ```
 
 **V-1.0**
 
 # This is a BIG UPDATE 1.0

## What's New

1) Filegator - A light weight PHP-Vue.js file manager for managing your files in htdocs. You may directly edit,delete,add your project files directly through this file manager.
2) phpSysInfo - A tool which help you to check your system details like processor, Operating system details etc.
3) Adminer - A light weight, single page PHP script for managing your MySQL databases.
 
## Improvements

1) Now Bootstrap beta 1 upgraded to version 5.1
2) Improved UI, hide default directories inside htdocs, added new icons in Tools section.

## Installation

You can delete your existing files safely and replace V 1.0 in your root folder (htdocs)

1) Simply clone this script to your htdocs folder or download and extract files.
2) While opening Filegator filemanager, it'll ask for enter username and password. Default username and password is admin,admin123 respectively. <br/><br/> **Basic configuration** <br/><br/>You may want to change your project folder, so you have to edit Default Local Disk Adapter. The main configuration file is in xpdb/plugins/filemanager/configuration.php. You've to update the line number #88 
```php
    return new \League\Flysystem\Adapter\Local(
                        'C:\xampp\htdocs'      // Change this path with your base directory
                    ); ?>
```
For more configuration and details goto [filegator](https://docs.filegator.io/)

## That's it :smiley:





