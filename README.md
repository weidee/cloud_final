# Cloud Final Docker Setup

This project uses Docker to run a PHP web server and multiple compute nodes.

## Build Images

```
docker-compose build
```

## Run Containers

Launch the web server and three compute nodes:

```
docker-compose up -d
```

The web server will be available on [http://localhost:8080](http://localhost:8080). All containers share the `./share` directory.
