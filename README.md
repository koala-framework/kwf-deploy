# kwf-deploy
Deploy Scripts for Koala Framework based Web Applications
# Usage
Add kwf-deploy to your project

    composer require koala-framework/kwf-deploy

Make sure you have set the following variables in the production section of your config.ini

    server.user = chiefe
    server.host = perfection.example.com
    server.dir = /var/www/my-awesome-project

In case of the first deployment, the project directory has to already exist on the server

## Test the connection
With the shell command, we can access the server via ssh

    php ./vendor/bin/deploy shell

## Deploy using rsync

    php ./vendor/bin/deploy rsync
