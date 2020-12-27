![Landing Page Screenshot](https://res.cloudinary.com/harishdurga/image/upload/v1609072977/Screenshot_from_2020-12-27_18-12-42_vzt6np.png "Landing Page Screenshot")

## About

Laravel Video Chat is a project where one to one video/audio chat and text chat with translation features are available.
With the help of **laravel web sockets** and **Twilio Video** we are able to transmit the video/audio data from **peer to peer** without the intervention of server. Where as the text based messages are being sent in realtime via server translating the text into different languages on the fly. We are using **Vue, Laravel Echo and Pusher** in the front-end.

### Features:

1. Basic Login(Email And Password)
2. Friends
3. Search Users
4. Showing Online Users
5. Real time chat with typing indication to the other user
6. Chat's text translation into other user's preferred language
7. My Profile(Name,Preferred Language)
8. Messages/Texts stored in encrypted format in database
9. New message indication
10. Video/audio with Twilio programmable video

### Setup:

#### Setting up credentials:

-   in `.env` file set `TWILLIO_ACCOUNT_ID`,`TWILIO_ACCOUNT_SID` to your twillio account sid, also set `TWILLIO_AUTH_TOKEN`, `TWILIO_API_KEY_SID`, `TWILIO_API_KEY_SECRET` as per the values from your twillio console.
-   for Google translation to work set `GOOGLE_APPLICATION_CREDENTIALS` to the full path of the google service account json file. And set `GOOGLE_PROJECT_ID`.
-   Set all the values for pusher server.
    Ex:
    ```bash
    PUSHER_APP_ID=123456
    PUSHER_APP_KEY=ABCD123
    PUSHER_APP_SECRET=ABCD123
    PUSHER_APP_CLUSTER=mt1
    ```

#### Run Websocket Server:

Run `php artisan websockets:serve` to start the websocket server. I use supervisor to manage the websocket server.
Here is my configuration for reference:

```bash
    [program:videochat-websocket-worker]
    process_name=%(program_name)s_%(process_num)02d
    command=php /home/laravel-video-chat/artisan websockets:serve
    autostart=false
    autorestart=true
    user=harish
    numprocs=1
    redirect_stderr=true
    stdout_logfile=/home/laravel-video-chat/websocket-server.log
```

#### Run Laravel Workers:

We also need to run workers to process channel subscribe/unsubscribe events inorder for the user online/offline feature to work. Here is my supervisor config for reference.

```bash
    [program:videochat-laravel-worker]
    process_name=%(program_name)s_%(process_num)02d
    command=php /home/laravel-video-chat/artisan queue:work --sleep=3 --tries=3
    autostart=true
    autorestart=true
    stopasgroup=true
    killasgroup=true
    user=harish
    numprocs=1
    redirect_stderr=true
    stdout_logfile=/home/laravel-video-chat/worker.log
    stopwaitsecs=3600
```

#### Setting Up Nginx:

Here is my nginx config for reference.

```nginx
    map $http_upgrade $type {
        default "web";
        websocket "ws";
    }
    server {
        listen 443 ssl;
        listen [::]:443 ssl;
        # include snippets/self-signed.conf;
        # include snippets/ssl-params.conf;
        ssl_certificate     /home/ssl-certs/videochat.test.pem;
        ssl_certificate_key /home/ssl-certs/videochat.test-key.pem;

        server_name videochat.test www.videochat.test;
        root /home/harish/Projects/laravel-video-chat/public;

        add_header X-Frame-Options "SAMEORIGIN";
        add_header X-XSS-Protection "1; mode=block";
        add_header X-Content-Type-Options "nosniff";

        index index.html index.htm index.php;

        charset utf-8;

        location / {
            try_files /nonexistent @$type;
        }

        location @web {
            try_files $uri $uri/ /index.php?$query_string;
        }
        location @ws {
            proxy_pass             http://127.0.0.1:6001;
            proxy_set_header Host  $host;
            proxy_read_timeout     60;
            proxy_connect_timeout  60;
            proxy_redirect         off;

            # Allow the use of websockets
            proxy_http_version 1.1;
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection 'upgrade';
            proxy_set_header Host $host;
            proxy_cache_bypass $http_upgrade;
        }

        location = /favicon.ico { access_log off; log_not_found off; }
        location = /robots.txt  { access_log off; log_not_found off; }

        error_page 404 /index.php;

        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
        }

        location ~ /\.(?!well-known).* {
            deny all;
        }
    }
    server {
        listen 80;
        listen [::]:80;

        server_name videochat.test www.videochat.test;

        return 301 https://$server_name$request_uri;
    }

```

Refer https://beyondco.de/docs/laravel-websockets/basic-usage/ssl#usage-with-a-reverse-proxy-like-nginx for more information.

![Chat/Main Page Screenshot](https://res.cloudinary.com/harishdurga/image/upload/v1609072856/Screenshot_from_2020-12-27_18-00-56_kuj7mt.png "Chat/Main Page Screenshot")
![Call In Progress Screenshot](https://res.cloudinary.com/harishdurga/image/upload/v1609073215/Screenshot_from_2020-12-27_18-16-03_kpmk8j.png "Call in progress Screenshot")
