com.osseed.civibarcode
======================

Introduction
-------------
The extension creates a token {event_registration_barcode.bar} that can be used in message templates of Event Registration mail .

Installation
-------------
Extract folder to your extension folder and install it.
refer to below link:
http://wiki.civicrm.org/confluence/display/CRMDOC/Extensions

Usage
---------------
Add the token 'Event Registration Barcode' to event registration message template which will get replace with barcode generated with currentdate and participant_id in confirmation email.

Note
------
The 'code 39' type is used to create the Barcode.(The library used is barcodegen http://barcodephp.com).   
