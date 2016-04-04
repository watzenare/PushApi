# PushApi [![Analytics](https://ga-beacon.appspot.com/UA-57718174-1/pushapi/readme?pixel)](https://github.com/watzenare/pushapi)

The PushApi is a server side project using PHP. It provides a way to notify users of different kind of events. There is the possibility to send notifications using unicast (target user), multicast (interested group) or broadcast (all users).

> It is being tested in a real system and it is obtaining the goals for which PushApi was designed for.
- It is being tested handling a database of more than 400k users.
- It is sending an average of 50k daily mails.
- The same values than before with android smartphones.


## Index

- [Documentation](#documentation)
- [Support](#support)
- [TODOs](#todos)


## Documentation

Read the documentation [here](https://push-api.readme.io/).

[Back to index](#index)

## Support

If you want to give your opinion, you can send me an [email](mailto:eloi@tviso.com), comment the project directly (if you want to contribute with information or resources) or fork the project and make a pull request.

Also, I will be grateful if you want to make a donation, this project hasn't got a death date and it wants to be improved constantly:

[![Website Button](https://www.paypalobjects.com/en_US/i/logo/pp_secure_213wx37h.gif "Donate!")](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=eloi.ballara%40gmail%2ecom&lc=US&item_name=PushApi%20Developers&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest&amount=5 "Contribute to the project")

[Back to index](#index)

## TODOs

You are free to help with them:

- Add 'collapse_key' param when sending a notification in order that PushApi sends more than one of that theme.
- Create a *plugin system* that would interact with the sending system and depending of the plugin would able to filter messages or whatever the plugin does. Developers will be able to create plugins. The target of this system is to avoid modify the base structure of the PushApi.
- Unit testing (do mock objects simulating the DB and checking the routes and controllers).
- Add multilevel security (One App to rule them all).

[Back to index](#index)
