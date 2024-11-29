FROM ubuntu:latest
LABEL authors="simoneesposito"

ENTRYPOINT ["top", "-b"]