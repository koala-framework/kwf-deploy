# kwf-deploy
Deploy Scripts for Koala Framework based Web Applications
# Usage
Clone the git repository and install the dependencies

    git clone https://github.com/koala-framework/kwf-deploy.git
    cd kwf-deploy
    composer install

Cd to the project, you want to deploy

    cd ..
    cd my-awesome-project

Make sure you have set the following variables in the production section of your config.ini

    server.user = chiefe
    server.host = perfection.example.com
    server.dir = /var/www/my-awesome-project

In case of the first deployment, the project directory has to already exist on the server

## Test the connection
With the shell command, we can access the server via ssh
    php ../kwf-deploy/bin/deploy shell

## Deploy using rsync
    php ../kwf-deploy/bin/deploy rsync
