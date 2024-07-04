# RabbitMQ Remote procedure call (RPC) for credentials checking

We can think of RabbitMQ RPC as request/response communication. We have a client asking a server to process some input and return the output in its response. However, this all happens asynchronously.

This code is an example about how to implement a RabbitMQ Remote procedure call (RPC) for checking login credentials of user (implementation of checking credentials has been simplified as main topic is demostrate how to use RabbitMQ RPC).

<img src=img/01.jpg width=500>

## Requirements

- Composer.
- PHP v7 or higher.
- RabbitMQ server.

## Instalation

1. Clone repository and access to project directory.
2. Make copy of `.env.example` to `.env` and configure parameters.
3. Execute ​`composer install`

## How to use:

1. Run client executing: `php receiver.php`
2. Run sender executing: `php sender.php`

If username/password credentials sent by sender matches client's one, it will appears message "Auth success".
.
