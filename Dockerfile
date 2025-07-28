FROM bitnami/kubectl:1.29.2

USER root

RUN mkdir -p /var/lib/apt/lists/partial \
    && apt-get update \
    && apt-get install -y bash curl git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

USER 1001
