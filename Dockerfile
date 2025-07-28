FROM bitnami/kubectl:1.29.2

RUN install_packages bash curl git

CMD ["/bin/bash"]
