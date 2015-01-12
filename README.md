# PushApi

## Index [![Analytics](https://ga-beacon.appspot.com/UA-57718174-1/pushapi/readme?pixel)](https://github.com/watzenare/pushapi)

- [Introduction](#introduction)
  - [How it works](#how-it-works)
  - [Targets](#targets)
    - [Email](#email)
    - [Smartphones](#smartphones)
    - [Twitter](#twitter)
  - [Schemes](#schemes)
    - [General view](#general-view)
    - [Inside the API](#inside-the-api)
    - [DataBase](#database)
- [Used tools](#used-tools)
- [Client](#client)
- [Run Workers with Forever](#run-workers-with-forever)
- [Comments](#comments)
- [Support](#support)
- [Pending](#pending)
- [Documentation](#documentation)


## Introduction

The PushApi is a server side project using PHP. It provides a way to notify users of different kind of events. There is the possibility to send notifications using unicast (target user), multicast (interested group) or broadcast (all users).

> This is a huge project that it is being implemented during the final degree project. Once finished, it will be able to accept external contributions.


### How it works

The API has an internal database (the tables will be described in the database scheme [DataBase](#database)).
In order to receive events, users must be registered into the API and then they can be subscribed into different Themes (this themes will be set by the administrator of the API). When user subscribes into a new Theme, user can choose where he wants to receive the notification (mail, smartphone, all, ...), by default, notifications will be sent via all the devices in order to force him to set its preferences.
The multicast Themes are assigned to different Channels that users can also subscribe.

When a notification is sent, API always returns the result directly to the client but it will send the notification when it can. For each target it has a Redis queue that sends step by step the different notifications that are being added continually to the various queues (soon it will be added [Forever](http://github.com/nodejitsu/forever) in order to ensure that a given script runs continuously).


### Targets

The API is being developed in order to support all kinds of targets if all these targets are configured correctly but the initial expected targets that is wanted to reach before the end of this project are the following ones:


#### Email

The basic notification method it is done via email (sometimes is called as SPAM due to its bad use). This API will send all mails to subscribed users without using external mailing services.


#### Smartphones

The other targets of this project are the most used smartphones (mainly Android and iOs) using the official servers for each company:
- GCM ([Google Cloud Messaging](https://developer.android.com/google/gcm/index.html)).
- APNS ([Apple Push Notification Service](https://developer.apple.com/library/ios/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/Chapters/ApplePushService.html)).

Both servers let sending notifications to various users with only one message. That is an advantage against the mail service.
At the beginning it was proposed to send notifications directly to the different smartphones without using the official services but the idea was deprecated because the lack of time and experience were a fisic solid wall.


#### Twitter

This is a new target that has been proposed during the project but it won't be applied until the main targets are finished. The purpose of this target is to make a Twitter tweet mentioning the target users interested on receive the notifications.

[Back to index](#index)


### Schemes

The following schemes wants to be descriptive parts of the project in order to make it easier to understand how it works or what is its functionality.


#### General view

This is the scheme of what the project will support with the basic targets.

![pushApi](img/general_structure.png)


#### Inside the API

The following figure shows how the API is structured internally and what the client can see.

![pushApi](img/inside_api.png)

From left to right:
- an agent has a PushApi app created and it can use the API calls (each call can check the API database).
- once the app receives a send call, it checks the users that can receive the notifications and sort all of them by its preference (email, android, ios), then it stores the sending information into the queue that should go and returns a response to the agent.
- internally, the server has senders that are running all the time sending the messages that are being stored into the queues. Each queue has a different worker and it can be set a determinate number of daemons running that workers.


#### DataBase

The current MySQL tables used are the following ones:

![pushApi](img/db_design.png)

- Agent, it stores the apps that are being created.
- Users, all the users that will use the service are stored in here, it is only stored its receive identifications.
- Themes, the different themes that are established for the notifications.
- Preferences, foreach theme an user can set its preferences in order to choose how he wants to receive the notification.
- Channels, groups that users can follow in order to get customized notifications.
- Subscriptions, users that wants to receive channel notifications should be subscribed before.
- Logs, a sending log that is stored each time a send request is done in order to register the call params request.
- Subjects, the themes names could not be good names for sending as mail subjects and this table contains a customizable translation (example: user_comment => User has commented your profile).


There are also 3 Redis Lists used in order to queue the notifications before send them properly (one list for each possible destination).

[Back to index](#index)


## Used tools

- MySQL
- Redis
- PHP 5.5+ (PHP 5.5 recommended)
- [Forever](http://github.com/nodejitsu/forever)

[Back to index](#index)


## Client

In order to use the API more easily, there are diferent standalone Clients that facilitates the use of the PushApi by using diferent languages. You can find your Client at the following points:

- PHP: [PushApi_Client](https://github.com/watzenare/PushApi_Client)

Currently there is only the PHP Client but soon there will be more. Also you can create your own (i.e. Python Client).

[Back to index](#index)

## Run Workers with Forever

It is recommended to install [Forever](http://github.com/nodejitsu/forever) at the server side and run the Workers in as a daemon in background.

Here is an example:

``` bash
  $ forever start -c php --minUptime 1500 --spinSleepTime 1500 EmailSender.php
  $ forever start -c php --minUptime 1500 --spinSleepTime 1500 AndroidSender.php
```

For more info you can see the [Forever](http://github.com/nodejitsu/forever) commands.

[Back to index](#index)

## Comments

> It doesn't want to be the best notification system because I haven't got too much experience and the main target is to learn as much as I can, but I am trying to do something that I think that can improve my programing skills. As it says the beginning of the description, this is a degree project and it isn't expected to be the best system (but I am doing all my best).

[Back to index](#index)


## Support

If you want to give your opinion, you can send me an [email](mailto:eloi@tviso.com), comment the project directly (if you want to contribute with information or resources) or fork the project and make a pull request.

Also I will be grateful if you want to make a donation, this project hasn't got a death date and it wants to be improved constantly:

[![Website Button](http://www.rahmenversand.com/images/paypal_logo_klein.gif "Donate!")](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=eloi.ballara%40gmail%2ecom&lc=US&item_name=PushApi%20Developers&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest&amount=5 "Contribute to the project")

[Back to index](#index)


## Pending

Here are some pending tasks that aren't developed yet.

- Unit testing (do mock objects simulating the DB and checking the routes and controllers).
- To log most of the functionalities.
- Add multilevel security (One App to rule them all).
- Create mail template in order to send a better email.

[Back to index](#index)


## Documentation

If you want to see more information about the PushApi you can check the [wiki](https://github.com/watzenare/PushApi/wiki).


Thank you!