FROM bitnami/kubectl:1.29.2

USER root

# Install tools
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    bash curl git && \
    rm -rf /var/lib/apt/lists/*

# Set working shell
SHELL ["/bin/bash", "-c"]

CMD ["sleep", "infinity"]
