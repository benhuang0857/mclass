## Run App Manually
- Create .env file for the Laravel environment from .env.example on src folder
- Run command ```docker-compose build``` on your terminal
- Run command ```docker-compose up -d``` on your terminal
- Run command ```composer install``` on your terminal after going into the php container on docker
- Run command ```docker exec -it mclass_php /bin/sh``` on your terminal
- Run command ```chmod -R 777 storage``` on your terminal after going into the php container on docker
- If app:key still empty on .env run ```php artisan key:generate``` on your terminal after going into the php container on docker
- To run artisan commands like migrate, etc. go to php container using ```docker exec -it php /bin/sh```
- Go to http://localhost:8001 or any port you set to open Laravel