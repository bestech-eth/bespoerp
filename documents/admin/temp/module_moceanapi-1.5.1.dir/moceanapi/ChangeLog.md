# CHANGELOG MOCEANAPI FOR [DOLIBARR ERP CRM](https://www.bespo.et)

## 1.5.1
= Shipment Module =
Minor bugfix

## 1.5.0
= Invoice Module =
Added SMS Reminder Management (Send SMS reminder before or after the sms overdue date)

= Bulk SMS Module =
Added keyword customization (You can personalise each SMS sent using the keywords available, for eg: First Name, Last Name)

= SMS Outbox =
Added Source (You can know where the SMS was sent from [Automation, Send SMS, Bulk SMS, SMS Reminder])

## 1.4.2
Added compatibility for PHP version <7.4

## 1.4.1
Added Members notification module
Added Third party notifications module
Added Shipment notification module

## 1.4.0
Added analytics

## 1.3.5
Added consent check

## 1.3.4
Modified implementation to check user's rights

## 1.3.3
Added appropriate permissions for all actions

## 1.3.2
Modified SMS Outbox to follow timezone setting in Dolibarr
Added option to clear logs for both SMS Outbox and Log file
Made minor UX improvements

## 1.3.1
Fixed Sending SMS issue when sending Automated SMS notification when invoice is paid or project status changed
Added a button to download log file
## 1.3.0
Added a new function to automatically add country code when sending to specific mobile numbers in Bulk SMS

## 1.2.0
Refactor code to be more maintainable
Added new tab section `Send SMS` to send SMS to thirdparty and contacts within Dolibarr

## 1.1.0
Added Automation for Ticketing Module

## 1.0.1
Fix a bug where user not able to logout
Fix a bug where user not able to see the module after installation

## 1.0

Initial version
