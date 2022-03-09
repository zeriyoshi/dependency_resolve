FROM php:8.1-cli-alpine

ARG INSTALL_PACKAGES="wget bash"
ARG PACKAGING_BINARIES="/bin/sh /bin/bash /usr/bin/wget /usr/bin/curl /usr/local/bin/php"

ENV INSTALL_PACKAGES    ${INSTALL_PACKAGES}
ENV PACKAGING_BINARIES  ${PACKAGING_BINARIES}

COPY "dependency_resolve.php" "/usr/local/bin/dependency_resolve"

RUN set -eux && \
    chmod +x "/usr/local/bin/dependency_resolve" && \
    dependency_resolve -v && \
    apk add ${INSTALL_PACKAGES} && \
    dependency_resolve "$(which ldd)" ${PACKAGING_BINARIES} >> "/dependencies.txt" && \
    ( \
        cat "/dependencies.txt" | xargs strip -Sv || true; \
    ) && \
    tar cfPpv "/rootfs.tar" --files-from="/dependencies.txt"
